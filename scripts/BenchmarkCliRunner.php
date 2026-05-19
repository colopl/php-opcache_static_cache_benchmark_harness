<?php

declare(strict_types=1);

final class BenchmarkCliRunner
{
	private string $currentNtsPhp = '';
	private string $currentZtsPhp = '';
	private string $baseNtsPhp = '';
	private string $baseZtsPhp = '';
	private string $sourceRoot = '';
	private string $outputDir;
	private string $mode = 'all';
	private int $runs = 10;
	private string $stamp;

	public function __construct()
	{
		$this->outputDir = dirname(__DIR__) . '/results';
		$this->sourceRoot = dirname(__DIR__, 2);
		$this->stamp = gmdate('Ymd\THis\Z');
	}

	public function run(array $argv): int
	{
		$this->parseArguments($argv);
		$this->validate();

		if (!is_dir($this->outputDir) && !mkdir($this->outputDir, 0777, true) && !is_dir($this->outputDir)) {
			throw new RuntimeException('Unable to create output directory: ' . $this->outputDir);
		}

		if ($this->mode === 'all' || $this->mode === 'startup') {
			$report = $this->runStartupBenchmark();
			echo $report;
		}
		if ($this->mode === 'all' || $this->mode === 'zend') {
			$report = $this->runZendBenchmark();
			echo $report;
		}

		return 0;
	}

	private function parseArguments(array $argv): void
	{
		$args = $argv;
		array_shift($args);
		while ($args !== []) {
			$arg = array_shift($args);
			switch ($arg) {
				case '--current-nts-php':
					$this->currentNtsPhp = $this->requireValue($arg, $args);
					break;
				case '--current-zts-php':
					$this->currentZtsPhp = $this->requireValue($arg, $args);
					break;
				case '--base-nts-php':
					$this->baseNtsPhp = $this->requireValue($arg, $args);
					break;
				case '--base-zts-php':
					$this->baseZtsPhp = $this->requireValue($arg, $args);
					break;
				case '--source-root':
					$this->sourceRoot = $this->requireValue($arg, $args);
					break;
				case '--output-dir':
					$this->outputDir = $this->requireValue($arg, $args);
					break;
				case '--runs':
					$this->runs = $this->parsePositiveInt($arg, $this->requireValue($arg, $args));
					break;
				case '--mode':
					$this->mode = $this->parseMode($this->requireValue($arg, $args));
					break;
				case '-h':
				case '--help':
					$this->usage();
					exit(0);
				default:
					throw new InvalidArgumentException('Unknown argument: ' . $arg);
			}
		}
	}

	private function validate(): void
	{
		$this->requireExecutable($this->currentNtsPhp, 'current NTS PHP CLI');
		$this->requireExecutable($this->currentZtsPhp, 'current ZTS PHP CLI');
		if ($this->mode === 'all' || $this->mode === 'zend') {
			$this->requireExecutable($this->baseNtsPhp, 'base NTS PHP CLI');
			$this->requireExecutable($this->baseZtsPhp, 'base ZTS PHP CLI');
			$bench = $this->sourceRoot . '/Zend/bench.php';
			if (!is_file($bench)) {
				throw new RuntimeException('Zend/bench.php is missing: ' . $bench);
			}
		}
	}

	private function requireValue(string $option, array &$args): string
	{
		if ($args === []) {
			throw new InvalidArgumentException($option . ' requires a value');
		}

		return array_shift($args);
	}

	private function parsePositiveInt(string $option, string $value): int
	{
		if (!preg_match('/^[0-9]+$/', $value) || (int) $value < 1) {
			throw new InvalidArgumentException($option . ' must be a positive integer');
		}

		return (int) $value;
	}

	private function parseMode(string $value): string
	{
		if (!in_array($value, ['all', 'startup', 'zend'], true)) {
			throw new InvalidArgumentException('--mode must be all, startup, or zend');
		}

		return $value;
	}

	private function requireExecutable(string $path, string $description): void
	{
		if ($path === '' || !is_executable($path)) {
			throw new RuntimeException($description . ' is missing or not executable: ' . $path);
		}
	}

	private function usage(): void
	{
		fwrite(STDERR, "Usage: php scripts/BenchmarkCliRunner.php --current-nts-php PHP --current-zts-php PHP [--base-nts-php PHP --base-zts-php PHP] [--source-root DIR] [--mode all|startup|zend] [--runs N] [--output-dir DIR]\n");
	}

	private function runStartupBenchmark(): string
	{
		$rows = [];
		foreach ([
			'NTS CLI' => $this->currentNtsPhp,
			'ZTS CLI' => $this->currentZtsPhp,
		] as $runtime => $php) {
			$baseline = null;
			foreach ([0, 128, 256, 512] as $sizeMb) {
				$totalMs = $this->measureStartup($php, $sizeMb);
				$meanMs = $totalMs / $this->runs;
				$baseline ??= $meanMs;
				$overheadMs = $meanMs - $baseline;
				$overheadPct = $baseline == 0.0 ? 0.0 : ($overheadMs / $baseline) * 100;
				$rows[] = [
					'runtime' => $runtime,
					'size_mb' => $sizeMb,
					'runs' => $this->runs,
					'total_ms' => $totalMs,
					'mean_ms' => $meanMs,
					'overhead_ms' => $overheadMs,
					'overhead_pct' => $overheadPct,
				];
			}
		}

		$tsvFile = $this->outputDir . '/cli-startup-' . $this->stamp . '.tsv';
		$reportFile = $this->outputDir . '/cli-startup-report-' . $this->stamp . '.dokuwiki.txt';
		$tsv = "runtime\tmemory_mb\truns\ttotal_ms\tmean_ms\toverhead_ms\toverhead_percent\n";
		foreach ($rows as $row) {
			$tsv .= implode("\t", [
				$row['runtime'],
				(string) $row['size_mb'],
				(string) $row['runs'],
				$this->formatNumber($row['total_ms'], 3),
				$this->formatNumber($row['mean_ms'], 3),
				$this->formatNumber($row['overhead_ms'], 3),
				$this->formatNumber($row['overhead_pct'], 1),
			]) . "\n";
		}
		file_put_contents($tsvFile, $tsv);

		$report = "==== CLI startup overhead ====\n\n";
		$report .= "^ Runtime ^ volatile/pinned cache memory ^ CLI runs ^ Total time ^ Mean per run ^ Overhead vs disabled ^ Overhead ^\n";
		foreach ($rows as $row) {
			$report .= '| ' . $row['runtime']
				. ' | ' . $row['size_mb'] . ' MiB'
				. ' | ' . $row['runs']
				. ' | ' . $this->formatNumber($row['total_ms'], 3) . ' ms'
				. ' | ' . $this->formatNumber($row['mean_ms'], 3) . ' ms'
				. ' | ' . $this->formatSignedNumber($row['overhead_ms'], 3) . ' ms'
				. ' | ' . $this->formatSignedNumber($row['overhead_pct'], 1) . '% |' . "\n";
		}
		$report .= "\nRaw results: " . basename($tsvFile) . "\n\n";
		file_put_contents($reportFile, $report);

		return $report;
	}

	private function measureStartup(string $php, int $sizeMb): float
	{
		$start = hrtime(true);
		for ($run = 0; $run < $this->runs; $run++) {
			$this->runProcess([
				$php,
				'-n',
				'-d', 'opcache.enable=1',
				'-d', 'opcache.enable_cli=1',
				'-d', 'opcache.jit=0',
				'-d', 'opcache.static_cache.volatile_size_mb=' . $sizeMb,
				'-d', 'opcache.static_cache.pinned_size_mb=' . $sizeMb,
				'-r', '',
			]);
		}

		return (hrtime(true) - $start) / 1000000;
	}

	private function runZendBenchmark(): string
	{
		$rows = [];
		foreach ([
			'NTS CLI' => [$this->baseNtsPhp, $this->currentNtsPhp],
			'ZTS CLI' => [$this->baseZtsPhp, $this->currentZtsPhp],
		] as $runtime => [$basePhp, $currentPhp]) {
			foreach (['off', 'on'] as $jit) {
				foreach ($this->zendIniCases() as $case) {
					$base = $this->measureZendBench($basePhp, $jit, $case);
					$current = $this->measureZendBench($currentPhp, $jit, $case);
					$delta = $current - $base;
					$change = $base == 0.0 ? 0.0 : ($delta / $base) * 100;
					$rows[] = [
						'runtime' => $runtime,
						'jit' => $jit,
						'label' => $case['label'],
						'base_ms' => $base * 1000,
						'current_ms' => $current * 1000,
						'delta_ms' => $delta * 1000,
						'change_pct' => $change,
					];
				}
			}
		}

		$tsvFile = $this->outputDir . '/zend-bench-' . $this->stamp . '.tsv';
		$reportFile = $this->outputDir . '/zend-bench-report-' . $this->stamp . '.dokuwiki.txt';
		$tsv = "runtime\tjit\tstatic_cache_ini\truns\tbase_mean_ms\tcurrent_mean_ms\tdelta_ms\tchange_percent\n";
		foreach ($rows as $row) {
			$tsv .= implode("\t", [
				$row['runtime'],
				$row['jit'],
				$row['label'],
				(string) $this->runs,
				$this->formatNumber($row['base_ms'], 3),
				$this->formatNumber($row['current_ms'], 3),
				$this->formatNumber($row['delta_ms'], 3),
				$this->formatNumber($row['change_pct'], 1),
			]) . "\n";
		}
		file_put_contents($tsvFile, $tsv);

		$report = "==== Zend VM/JIT baseline overhead ====\n\n";
		$report .= "^ Runtime ^ JIT ^ Static-cache INI ^ 43b56c96 mean ^ Current mean ^ Delta ^ Change ^\n";
		foreach ($rows as $row) {
			$report .= '| ' . $row['runtime']
				. ' | ' . $row['jit']
				. ' | <php>' . $row['label'] . '</php>'
				. ' | ' . $this->formatNumber($row['base_ms'], 3) . ' ms'
				. ' | ' . $this->formatNumber($row['current_ms'], 3) . ' ms'
				. ' | ' . $this->formatSignedNumber($row['delta_ms'], 3) . ' ms'
				. ' | ' . $this->formatSignedNumber($row['change_pct'], 1) . '% |' . "\n";
		}
		$report .= "\nRaw results: " . basename($tsvFile) . "\n\n";
		file_put_contents($reportFile, $report);

		return $report;
	}

	private function zendIniCases(): array
	{
		return [
			[
				'label' => 'opcache.static_cache.volatile_size_mb=32',
				'volatile' => 32,
				'pinned' => 0,
			],
			[
				'label' => 'opcache.static_cache.pinned_size_mb=32',
				'volatile' => 0,
				'pinned' => 32,
			],
			[
				'label' => 'opcache.static_cache.volatile_size_mb=32, pinned_size_mb=32',
				'volatile' => 32,
				'pinned' => 32,
			],
		];
	}

	private function measureZendBench(string $php, string $jit, array $case): float
	{
		$totals = [];
		for ($run = 0; $run < $this->runs; $run++) {
			$command = [
				$php,
				'-n',
				'-d', 'opcache.enable=1',
				'-d', 'opcache.enable_cli=1',
				'-d', 'opcache.static_cache.volatile_size_mb=' . $case['volatile'],
				'-d', 'opcache.static_cache.pinned_size_mb=' . $case['pinned'],
			];
			if ($jit === 'on') {
				$command[] = '-d';
				$command[] = 'opcache.jit_buffer_size=64M';
				$command[] = '-d';
				$command[] = 'opcache.jit=tracing';
			} else {
				$command[] = '-d';
				$command[] = 'opcache.jit=0';
			}
			$command[] = $this->sourceRoot . '/Zend/bench.php';
			$output = $this->runProcess($command);
			if (!preg_match('/^Total\s+([0-9.]+)/m', $output, $matches)) {
				throw new RuntimeException('Unable to parse Zend/bench.php Total line');
			}
			$totals[] = (float) $matches[1];
		}

		return array_sum($totals) / count($totals);
	}

	private function runProcess(array $command): string
	{
		$descriptorSpec = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptorSpec, $pipes, $this->sourceRoot);
		if (!is_resource($process)) {
			throw new RuntimeException('Unable to start process: ' . implode(' ', $command));
		}
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$status = proc_close($process);
		if ($status !== 0) {
			throw new RuntimeException('Process failed with status ' . $status . ': ' . implode(' ', $command) . "\n" . $stderr);
		}

		return (string) $stdout;
	}

	private function formatNumber(float $value, int $precision): string
	{
		return number_format($value, $precision, '.', '');
	}

	private function formatSignedNumber(float $value, int $precision): string
	{
		return ($value >= 0 ? '+' : '') . $this->formatNumber($value, $precision);
	}
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
	try {
		exit((new BenchmarkCliRunner())->run($argv));
	} catch (Throwable $throwable) {
		fwrite(STDERR, "\nCLI benchmark failed: " . $throwable->getMessage() . "\n");
		exit(1);
	}
}
