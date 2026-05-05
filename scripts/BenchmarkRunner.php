<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/BenchmarkScenarioCatalog.php';

final class BenchmarkRunner
{
	private string $baseUrl = 'http://127.0.0.1:8080/index.php';
	private int $iterations = 60;
	private int $warmup = 5;
	private int $operations = 5000;
	private string $runtimeLabel = 'PHP runtime';
	private string $scenarioName = '';
	private string $outputDir;
	private bool $requireFullMatrix = false;
	private array $selectedCases = [];
	private array $selectedBackends = [];
	private string $stamp;
	private string $rawFile;
	private string $summaryFile;
	private string $comparisonFile;
	private string $strategyFile;
	private string $reportFile;
	private array $samples = [];
	private array $summaryRows = [];

	public function __construct()
	{
		$this->outputDir = dirname(__DIR__) . '/results';
		$this->stamp = gmdate('Ymd\THis\Z');
	}

	public function run(array $argv): int
	{
		$this->parseArguments($argv);
		$this->applyScenarioDefaults();
		$this->prepareOutputFiles();

		$description = $this->requestJson(['action' => 'describe']);
		$matrix = $this->fetchMatrix();
		$cases = $this->selectNames(array_keys($matrix), $this->selectedCases);
		$backends = $this->selectNames($this->matrixBackends($matrix), $this->selectedBackends);

		if ($this->requireFullMatrix) {
			$this->assertMatrix($matrix, $cases, $backends);
		}

		$this->writeMetadata($description, $cases, $backends);
		foreach ($cases as $caseName) {
			foreach ($backends as $backendName) {
				if (!$this->matrixHasPair($matrix, $caseName, $backendName)) {
					continue;
				}
				$this->runScenario($caseName, $backendName);
			}
		}

		$this->writeSummaryFiles();
		$report = $this->buildDokuWikiReport();
		file_put_contents($this->reportFile, $report);
		$this->clearProgress();
		echo $report;

		return 0;
	}

	private function parseArguments(array $argv): void
	{
		$args = $argv;
		array_shift($args);
		while ($args !== []) {
			$arg = array_shift($args);
			switch ($arg) {
				case '--runner':
					$this->requireValue($arg, $args);
					break;
				case '--base-url':
					$this->baseUrl = $this->requireValue($arg, $args);
					break;
				case '--iterations':
					$this->iterations = $this->parsePositiveInt($arg, $this->requireValue($arg, $args));
					break;
				case '--warmup':
					$this->warmup = $this->parseNonNegativeInt($arg, $this->requireValue($arg, $args));
					break;
				case '--operations':
					$this->operations = $this->parsePositiveInt($arg, $this->requireValue($arg, $args));
					break;
				case '--runtime-label':
					$this->runtimeLabel = $this->requireValue($arg, $args);
					break;
				case '--scenario':
					$this->scenarioName = $this->requireValue($arg, $args);
					break;
				case '--output-dir':
					$this->outputDir = $this->requireValue($arg, $args);
					break;
				case '--cases':
					$this->selectedCases = $this->parseList($this->requireValue($arg, $args));
					break;
				case '--backends':
					$this->selectedBackends = $this->parseList($this->requireValue($arg, $args));
					break;
				case '--require-full-matrix':
					$this->requireFullMatrix = true;
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

	private function applyScenarioDefaults(): void
	{
		if ($this->scenarioName === '') {
			return;
		}

		$scenario = BenchmarkScenarioCatalog::get($this->scenarioName);
		if (($scenario['runner'] ?? '') !== 'read') {
			throw new InvalidArgumentException('Scenario is not a read scenario: ' . $this->scenarioName);
		}

		if ($this->selectedCases === []) {
			$this->selectedCases = $scenario['cases'];
		}
		if ($this->selectedBackends === []) {
			$this->selectedBackends = $scenario['backends'];
		}
		if ($this->iterations === 60 && isset($scenario['iterations'])) {
			$this->iterations = (int) $scenario['iterations'];
		}
		if ($this->warmup === 5 && isset($scenario['warmup'])) {
			$this->warmup = (int) $scenario['warmup'];
		}
		if ($this->operations === 5000 && isset($scenario['operations'])) {
			$this->operations = (int) $scenario['operations'];
		}
	}

	private function usage(): void
	{
		fwrite(STDERR, "Usage: php scripts/BenchmarkRunner.php --base-url URL [--scenario NAME] [--iterations N] [--warmup N] [--operations N] [--runtime-label LABEL] [--output-dir DIR] [--cases a,b] [--backends a,b] [--require-full-matrix]\n");
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

	private function parseNonNegativeInt(string $option, string $value): int
	{
		if (!preg_match('/^[0-9]+$/', $value)) {
			throw new InvalidArgumentException($option . ' must be a non-negative integer');
		}

		return (int) $value;
	}

	private function parseList(string $value): array
	{
		if ($value === '' || $value === 'all') {
			return [];
		}

		$items = [];
		foreach (explode(',', $value) as $item) {
			$item = trim($item);
			if ($item !== '') {
				$items[] = $item;
			}
		}

		return $items;
	}

	private function prepareOutputFiles(): void
	{
		if (!is_dir($this->outputDir) && !mkdir($this->outputDir, 0777, true) && !is_dir($this->outputDir)) {
			throw new RuntimeException('Unable to create output directory: ' . $this->outputDir);
		}

		$this->rawFile = $this->outputDir . '/raw-' . $this->stamp . '.tsv';
		$this->summaryFile = $this->outputDir . '/summary-' . $this->stamp . '.tsv';
		$this->comparisonFile = $this->outputDir . '/apcu-comparison-' . $this->stamp . '.tsv';
		$this->strategyFile = $this->outputDir . '/strategy-comparison-' . $this->stamp . '.tsv';
		$this->reportFile = $this->outputDir . '/report-' . $this->stamp . '.dokuwiki.txt';

		file_put_contents($this->rawFile, "runtime\tcase\tbackend\titeration\toperation_count\tworker_us\tmemory_delta\tpeak_delta\tcache_hit_count\tbuild_count\tchecksum\tread_score\tclient_us\n");
	}

	private function writeMetadata(array $description, array $cases, array $backends): void
	{
		$metadata = [
			'runtime_label' => $this->runtimeLabel,
			'scenario' => $this->scenarioName,
			'timestamp_utc' => $this->stamp,
			'base_url' => $this->baseUrl,
			'iterations' => $this->iterations,
			'warmup' => $this->warmup,
			'operations' => $this->operations,
			'php_version' => $description['php_version'] ?? PHP_VERSION,
			'php_sapi' => $description['php_sapi'] ?? null,
			'architecture' => $description['architecture'] ?? php_uname('m'),
			'jit' => $description['jit'] ?? null,
			'volatile_cache' => $description['volatile_cache'] ?? null,
			'persistent_cache' => $description['persistent_cache'] ?? null,
			'apcu' => $description['apcu'] ?? null,
			'cases' => $cases,
			'backends' => $backends,
		];
		file_put_contents($this->outputDir . '/metadata-' . $this->stamp . '.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
	}

	private function fetchMatrix(): array
	{
		$body = $this->requestText(['action' => 'describe', 'format' => 'matrix']);
		$matrix = [];
		foreach (preg_split('/\r?\n/', trim($body)) as $lineNumber => $line) {
			if ($lineNumber === 0 || $line === '') {
				continue;
			}
			$fields = explode("\t", $line);
			if (count($fields) >= 2) {
				$matrix[$fields[0]][$fields[1]] = true;
			}
		}

		return $matrix;
	}

	private function selectNames(array $available, array $selected): array
	{
		if ($selected === []) {
			return $available;
		}

		$availableMap = array_fill_keys($available, true);
		foreach ($selected as $name) {
			if (!isset($availableMap[$name])) {
				throw new InvalidArgumentException('Requested name is not available: ' . $name);
			}
		}

		return $selected;
	}

	private function assertMatrix(array $matrix, array $cases, array $backends): void
	{
		$missing = [];
		foreach ($cases as $caseName) {
			foreach ($backends as $backendName) {
				if (!$this->matrixHasPair($matrix, $caseName, $backendName)) {
					$missing[] = $caseName . ' / ' . $backendName;
				}
			}
		}
		if ($missing !== []) {
			throw new RuntimeException('Benchmark matrix is incomplete: ' . implode(', ', $missing));
		}
	}

	private function matrixHasPair(array $matrix, string $caseName, string $backendName): bool
	{
		return isset($matrix[$caseName][$backendName]);
	}

	private function matrixBackends(array $matrix): array
	{
		$backends = [];
		foreach ($matrix as $caseBackends) {
			foreach ($caseBackends as $backendName => $_present) {
				$backends[$backendName] = true;
			}
		}

		return array_keys($backends);
	}

	private function runScenario(string $caseName, string $backendName): void
	{
		$this->progress('reset ' . $caseName . ' / ' . $backendName);
		$this->requestText(['action' => 'reset', 'case' => $caseName, 'backend' => $backendName]);
		$this->progress('prime ' . $caseName . ' / ' . $backendName);
		$this->requestText(['action' => 'prime', 'case' => $caseName, 'backend' => $backendName]);

		for ($iteration = 0; $iteration < $this->warmup; $iteration++) {
			$this->progress('warmup ' . ($iteration + 1) . '/' . $this->warmup . ' ' . $caseName . ' / ' . $backendName);
			$this->requestMeasure($caseName, $backendName);
		}

		for ($iteration = 1; $iteration <= $this->iterations; $iteration++) {
			$this->progress('measure ' . $iteration . '/' . $this->iterations . ' ' . $caseName . ' / ' . $backendName);
			$sample = $this->requestMeasure($caseName, $backendName);
			if ((int) $sample['build_count'] !== 0 || (int) $sample['cache_hit_count'] !== $this->operations) {
				throw new RuntimeException($caseName . ' / ' . $backendName . ' missed during read-only measurement');
			}
			$this->appendRawSample($caseName, $backendName, $iteration, $sample);
			$this->samples[$caseName][$backendName][] = $sample;
		}

		$this->progress('verify ' . $caseName . ' / ' . $backendName);
		$verify = $this->requestVerify($caseName, $backendName);
		if (($verify['cache_retention'] ?? '') !== 'retained') {
			throw new RuntimeException('Cache verification failed for ' . $caseName . ' / ' . $backendName . ': ' . ($verify['reason'] ?? 'unknown'));
		}
	}

	private function requestMeasure(string $caseName, string $backendName): array
	{
		$start = hrtime(true);
		$body = $this->requestText([
			'action' => 'measure',
			'case' => $caseName,
			'backend' => $backendName,
			'operations' => (string) $this->operations,
			'read_only' => '1',
			'format' => 'tsv',
		]);
		$clientUs = (int) ((hrtime(true) - $start) / 1000);
		$fields = explode("\t", trim($body));
		if (count($fields) < 8) {
			throw new RuntimeException('Unexpected measure TSV response: ' . $body);
		}

		return [
			'operation_count' => (int) $fields[0],
			'worker_us' => (int) $fields[1],
			'memory_delta' => (int) $fields[2],
			'peak_delta' => (int) $fields[3],
			'cache_hit_count' => (int) $fields[4],
			'build_count' => (int) $fields[5],
			'checksum' => $fields[6],
			'read_score' => (int) $fields[7],
			'client_us' => $clientUs,
		];
	}

	private function requestVerify(string $caseName, string $backendName): array
	{
		$body = $this->requestText([
			'action' => 'verify',
			'case' => $caseName,
			'backend' => $backendName,
			'format' => 'tsv',
		]);
		$fields = explode("\t", trim($body));

		return [
			'cache_retention' => $fields[0] ?? '',
			'reason' => $fields[1] ?? '',
			'checksum' => $fields[2] ?? '',
			'expected_checksum' => $fields[3] ?? '',
		];
	}

	private function appendRawSample(string $caseName, string $backendName, int $iteration, array $sample): void
	{
		$row = [
			$this->runtimeLabel,
			$caseName,
			$backendName,
			(string) $iteration,
			(string) $sample['operation_count'],
			(string) $sample['worker_us'],
			(string) $sample['memory_delta'],
			(string) $sample['peak_delta'],
			(string) $sample['cache_hit_count'],
			(string) $sample['build_count'],
			$sample['checksum'],
			(string) $sample['read_score'],
			(string) $sample['client_us'],
		];
		file_put_contents($this->rawFile, implode("\t", array_map([$this, 'tsv'], $row)) . "\n", FILE_APPEND);
	}

	private function writeSummaryFiles(): void
	{
		file_put_contents($this->summaryFile, "runtime\tcase\tbackend\toperation_count\tsamples\tmean_worker_us\tmean_worker_ms\tmean_operation_us\tmean_operation_ms\tmean_memory_delta\tmean_peak_delta\tcache_hit_ratio\tmax_build_count\tmean_read_score\n");
		foreach ($this->samples as $caseName => $backendSamples) {
			foreach ($backendSamples as $backendName => $samples) {
				$row = $this->summarize($caseName, $backendName, $samples);
				$this->summaryRows[$caseName][$backendName] = $row;
				file_put_contents($this->summaryFile, implode("\t", array_map([$this, 'tsv'], array_values($row))) . "\n", FILE_APPEND);
			}
		}

		$this->writeApcuComparison();
		$this->writeStrategyComparison();
	}

	private function summarize(string $caseName, string $backendName, array $samples): array
	{
		$count = count($samples);
		$operationCount = $samples[0]['operation_count'] ?? $this->operations;
		$meanWorkerUs = $this->mean($samples, 'worker_us');
		$meanOperationUs = $meanWorkerUs / max(1, $operationCount);
		$hitCount = array_sum(array_column($samples, 'cache_hit_count'));
		$totalOperations = $operationCount * $count;

		return [
			'runtime' => $this->runtimeLabel,
			'case' => $caseName,
			'backend' => $backendName,
			'operation_count' => (string) $operationCount,
			'samples' => (string) $count,
			'mean_worker_us' => $this->formatNumber($meanWorkerUs, 2),
			'mean_worker_ms' => $this->formatNumber($meanWorkerUs / 1000, 3),
			'mean_operation_us' => $this->formatNumber($meanOperationUs, 3),
			'mean_operation_ms' => $this->formatNumber($meanOperationUs / 1000, 6),
			'mean_memory_delta' => $this->formatNumber($this->mean($samples, 'memory_delta'), 2),
			'mean_peak_delta' => $this->formatNumber($this->mean($samples, 'peak_delta'), 2),
			'cache_hit_ratio' => $this->formatNumber(($hitCount * 100) / max(1, $totalOperations), 2) . '%',
			'max_build_count' => (string) max(array_column($samples, 'build_count')),
			'mean_read_score' => $this->formatNumber($this->mean($samples, 'read_score'), 2),
		];
	}

	private function writeApcuComparison(): void
	{
		file_put_contents($this->comparisonFile, "runtime\tcase\tbackend\toperation_count\tapcu_mean_operation_us\tcandidate_mean_operation_us\tcandidate_delta_percent\tcandidate_relation\n");
		foreach ($this->summaryRows as $caseName => $rows) {
			if (!isset($rows['apcu'])) {
				continue;
			}
			$apcuUs = (float) $rows['apcu']['mean_operation_us'];
			foreach ($rows as $backendName => $row) {
				if ($backendName === 'apcu') {
					continue;
				}
				[$delta, $relation] = $this->relation($apcuUs, (float) $row['mean_operation_us'], 'APCu');
				$out = [$this->runtimeLabel, $caseName, $backendName, $row['operation_count'], $this->formatNumber($apcuUs, 3), $row['mean_operation_us'], $delta, $relation];
				file_put_contents($this->comparisonFile, implode("\t", array_map([$this, 'tsv'], $out)) . "\n", FILE_APPEND);
			}
		}
	}

	private function writeStrategyComparison(): void
	{
		file_put_contents($this->strategyFile, "runtime\tcase\ttarget\timmediate_us\ttracking_us\tpersistent_us\ttracking_vs_immediate_percent\tpersistent_vs_immediate_percent\n");
		foreach ($this->summaryRows as $caseName => $rows) {
			foreach (['class', 'property', 'method'] as $target) {
				$immediate = $rows['volatile_static_immediate_' . $target]['mean_operation_us'] ?? null;
				$tracking = $rows['volatile_static_tracking_' . $target]['mean_operation_us'] ?? null;
				$persistent = $rows['persistent_static_' . $target]['mean_operation_us'] ?? null;
				if ($immediate === null || $tracking === null || $persistent === null) {
					continue;
				}
				$immediateFloat = (float) $immediate;
				$out = [
					$this->runtimeLabel,
					$caseName,
					$target,
					$immediate,
					$tracking,
					$persistent,
					$this->formatNumber((((float) $tracking - $immediateFloat) / max(0.000001, $immediateFloat)) * 100, 2),
					$this->formatNumber((((float) $persistent - $immediateFloat) / max(0.000001, $immediateFloat)) * 100, 2),
				];
				file_put_contents($this->strategyFile, implode("\t", array_map([$this, 'tsv'], $out)) . "\n", FILE_APPEND);
			}
		}
	}

	private function buildDokuWikiReport(): string
	{
		$lines = [];
		$lines[] = '==== ' . $this->runtimeLabel . ' ====';
		$lines[] = '';
		$lines[] = '^ Setting ^ Value ^';
		$lines[] = '| Timestamp | ' . $this->stamp . ' |';
		$lines[] = '| Iterations | ' . $this->iterations . ' |';
		$lines[] = '| Warmup requests | ' . $this->warmup . ' |';
		$lines[] = '| Operations/request | ' . $this->operations . ' |';
		$lines[] = '';
		$lines[] = '^ Workload ^ Backend ^ Mean request ^ Mean operation vs APCu ^ Hit ratio ^ Max builds ^';
		foreach ($this->summaryRows as $caseName => $rows) {
			$apcuUs = isset($rows['apcu']) ? (float) $rows['apcu']['mean_operation_us'] : null;
			foreach ($rows as $backendName => $row) {
				$operation = $this->formatOperationVsApcu((float) $row['mean_operation_us'], $apcuUs, $backendName);
				$lines[] = '| ' . $caseName . ' | ' . $backendName . ' | ' . $row['mean_worker_ms'] . ' ms | ' . $operation . ' | ' . $row['cache_hit_ratio'] . ' | ' . $row['max_build_count'] . ' |';
			}
		}
		$lines[] = '';
		$lines[] = '^ Workload ^ Target ^ VolatileStatic Immediate ^ VolatileStatic Tracking ^ PersistentStatic ^ Tracking vs Immediate ^ Persistent vs Immediate ^';
		foreach ($this->summaryRows as $caseName => $rows) {
			foreach (['class', 'property', 'method'] as $target) {
				$immediate = $rows['volatile_static_immediate_' . $target]['mean_operation_us'] ?? null;
				$tracking = $rows['volatile_static_tracking_' . $target]['mean_operation_us'] ?? null;
				$persistent = $rows['persistent_static_' . $target]['mean_operation_us'] ?? null;
				if ($immediate === null || $tracking === null || $persistent === null) {
					continue;
				}
				$immediateFloat = (float) $immediate;
				$trackingDelta = $this->formatNumber((((float) $tracking - $immediateFloat) / max(0.000001, $immediateFloat)) * 100, 2);
				$persistentDelta = $this->formatNumber((((float) $persistent - $immediateFloat) / max(0.000001, $immediateFloat)) * 100, 2);
				$lines[] = '| ' . $caseName . ' | ' . $target . ' | ' . $immediate . ' us | ' . $tracking . ' us | ' . $persistent . ' us | ' . $trackingDelta . '% | ' . $persistentDelta . '% |';
			}
		}
		$lines[] = '';
		$lines[] = 'Raw results: ' . basename($this->rawFile) . ', ' . basename($this->summaryFile) . ', ' . basename($this->comparisonFile) . ', ' . basename($this->strategyFile);
		$lines[] = '';

		return implode("\n", $lines);
	}

	private function requestJson(array $query): array
	{
		$body = $this->requestText($query);
		$data = json_decode($body, true);
		if (!is_array($data)) {
			throw new RuntimeException('Invalid JSON response from benchmark server');
		}
		if (($data['ok'] ?? true) === false) {
			throw new RuntimeException('Benchmark server error: ' . ($data['error'] ?? 'unknown error'));
		}

		return $data;
	}

	private function requestText(array $query): string
	{
		$url = $this->buildUrl($query);
		$context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 300]]);
		$body = @file_get_contents($url, false, $context);
		$status = $this->httpStatus($http_response_header ?? []);
		if ($body === false || $status < 200 || $status >= 300) {
			throw new RuntimeException('HTTP request failed (' . $status . '): ' . $url . "\n" . (is_string($body) ? $body : ''));
		}

		return $body;
	}

	private function buildUrl(array $query): string
	{
		$separator = str_contains($this->baseUrl, '?') ? '&' : '?';
		return $this->baseUrl . $separator . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
	}

	private function httpStatus(array $headers): int
	{
		foreach ($headers as $header) {
			if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
				return (int) $matches[1];
			}
		}

		return 0;
	}

	private function progress(string $message): void
	{
		if (function_exists('stream_isatty') && stream_isatty(STDERR)) {
			fwrite(STDERR, "\r\033[K" . $message);
			return;
		}
		fwrite(STDERR, $message . "\n");
	}

	private function clearProgress(): void
	{
		if (function_exists('stream_isatty') && stream_isatty(STDERR)) {
			fwrite(STDERR, "\r\033[K");
		}
	}

	private function mean(array $samples, string $field): float
	{
		return array_sum(array_column($samples, $field)) / max(1, count($samples));
	}

	private function relation(float $base, float $candidate, string $baseLabel): array
	{
		if ($base <= 0.0) {
			return ['0.00', 'same as ' . $baseLabel];
		}
		$delta = (($base - $candidate) / $base) * 100;
		if ($candidate < $base) {
			return [$this->formatNumber($delta, 2), '+'];
		}
		if ($candidate > $base) {
			return [$this->formatNumber(-$delta, 2), '-'];
		}

		return ['0.00', 'same as ' . $baseLabel];
	}

	private function formatOperationVsApcu(float $operationUs, ?float $apcuUs, string $backendName): string
	{
		$operation = $this->formatNumber($operationUs, 3) . ' us';
		if ($apcuUs === null) {
			return $operation;
		}

		if ($backendName === 'apcu') {
			return $operation . ' (APCu baseline)';
		}

		[$delta, $relation] = $this->relation($apcuUs, $operationUs, 'APCu');
		if ($relation === 'same as APCu') {
			return $operation . ' (same as APCu)';
		}

		return $operation . ' (APCu ' . $relation . $delta . '%)';
	}

	private function formatNumber(float $value, int $precision): string
	{
		return number_format($value, $precision, '.', '');
	}

	private function tsv(string $value): string
	{
		return str_replace(["\t", "\r", "\n"], ' ', $value);
	}
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
	try {
		exit((new BenchmarkRunner())->run($argv));
	} catch (Throwable $throwable) {
		fwrite(STDERR, "\nBenchmark failed: " . $throwable->getMessage() . "\n");
		exit(1);
	}
}
