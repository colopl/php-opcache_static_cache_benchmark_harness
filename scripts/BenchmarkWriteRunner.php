<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/BenchmarkScenarioCatalog.php';

final class BenchmarkWriteRunner
{
	private string $baseUrl = 'http://127.0.0.1:8080/index.php';
	private int $iterations = 15;
	private int $warmup = 2;
	private int $operations = 128;
	private int $concurrency = 1;
	private string $keyMode = 'distinct';
	private string $writeMode = 'store';
	private int $keySpace = 32;
	private bool $resetEachBatch = false;
	private string $runtimeLabel = 'PHP runtime';
	private string $scenarioName = '';
	private string $outputDir;
	private array $selectedCases = [];
	private array $selectedBackends = [];
	private string $stamp;
	private string $rawFile;
	private string $summaryFile;
	private string $comparisonFile;
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
		$cases = $this->selectNames(array_keys($description['payload_labels'] ?? []), $this->selectedCases);
		$backends = $this->selectNames($this->availableExplicitBackends($description), $this->selectedBackends);

		$this->writeMetadata($description, $cases, $backends);
		foreach ($cases as $caseName) {
			foreach ($backends as $backendName) {
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
				case '--concurrency':
					$this->concurrency = $this->parsePositiveInt($arg, $this->requireValue($arg, $args));
					break;
				case '--key-mode':
					$this->keyMode = $this->parseKeyMode($this->requireValue($arg, $args));
					break;
				case '--write-mode':
					$this->writeMode = $this->parseWriteMode($this->requireValue($arg, $args));
					break;
				case '--key-space':
					$this->keySpace = $this->parsePositiveInt($arg, $this->requireValue($arg, $args));
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
		if (($scenario['runner'] ?? '') !== 'write') {
			throw new InvalidArgumentException('Scenario is not a write scenario: ' . $this->scenarioName);
		}

		if ($this->selectedCases === []) {
			$this->selectedCases = $scenario['cases'];
		}
		if ($this->selectedBackends === []) {
			$this->selectedBackends = $scenario['backends'];
		}
		if ($this->iterations === 15 && isset($scenario['iterations'])) {
			$this->iterations = (int) $scenario['iterations'];
		}
		if ($this->warmup === 2 && isset($scenario['warmup'])) {
			$this->warmup = (int) $scenario['warmup'];
		}
		if ($this->operations === 128 && isset($scenario['operations'])) {
			$this->operations = (int) $scenario['operations'];
		}
		if ($this->concurrency === 1 && isset($scenario['concurrency'])) {
			$this->concurrency = (int) $scenario['concurrency'];
		}
		if ($this->keyMode === 'distinct' && isset($scenario['key_mode'])) {
			$this->keyMode = (string) $scenario['key_mode'];
		}
		if ($this->keySpace === 32 && isset($scenario['key_space'])) {
			$this->keySpace = (int) $scenario['key_space'];
		}
		if ($this->writeMode === 'store' && isset($scenario['write_mode'])) {
			$this->writeMode = $this->parseWriteMode((string) $scenario['write_mode']);
		}
		if (isset($scenario['reset_each_batch'])) {
			$this->resetEachBatch = (bool) $scenario['reset_each_batch'];
		}
	}

	private function usage(): void
	{
		fwrite(STDERR, "Usage: php scripts/BenchmarkWriteRunner.php --base-url URL [--scenario NAME] [--iterations N] [--warmup N] [--operations N] [--concurrency N] [--key-mode shared|distinct] [--write-mode store|entry_reservation] [--key-space N] [--runtime-label LABEL] [--output-dir DIR] [--cases a,b] [--backends a,b]\n");
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

	private function parseKeyMode(string $value): string
	{
		if ($value !== 'shared' && $value !== 'distinct') {
			throw new InvalidArgumentException('--key-mode must be shared or distinct');
		}

		return $value;
	}

	private function parseWriteMode(string $value): string
	{
		if ($value !== 'store' && $value !== 'entry_reservation') {
			throw new InvalidArgumentException('--write-mode must be store or entry_reservation');
		}

		return $value;
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

		$this->rawFile = $this->outputDir . '/write-raw-' . $this->stamp . '.tsv';
		$this->summaryFile = $this->outputDir . '/write-summary-' . $this->stamp . '.tsv';
		$this->comparisonFile = $this->outputDir . '/write-apcu-comparison-' . $this->stamp . '.tsv';
		$this->reportFile = $this->outputDir . '/write-report-' . $this->stamp . '.dokuwiki.txt';

		file_put_contents($this->rawFile, "runtime\tscenario\tcase\tbackend\titeration\tconcurrency\tkey_mode\twrite_mode\tkey_space\toperation_count\twall_us\tmean_worker_us\tmean_memory_delta\tmean_peak_delta\tstore_count\tbuild_count\tchecksum\twrite_score\n");
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
			'concurrency' => $this->concurrency,
			'key_mode' => $this->keyMode,
			'write_mode' => $this->writeMode,
			'key_space' => $this->keySpace,
			'php_version' => $description['php_version'] ?? PHP_VERSION,
			'php_sapi' => $description['php_sapi'] ?? null,
			'architecture' => $description['architecture'] ?? php_uname('m'),
			'jit' => $description['jit'] ?? null,
			'volatile_cache' => $description['volatile_cache'] ?? null,
			'pinned_cache' => $description['pinned_cache'] ?? null,
			'apcu' => $description['apcu'] ?? null,
			'cases' => $cases,
			'backends' => $backends,
		];
		file_put_contents($this->outputDir . '/write-metadata-' . $this->stamp . '.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
	}

	private function availableExplicitBackends(array $description): array
	{
		$backends = [];
		if (($description['apcu']['enabled'] ?? false) && ($description['apcu']['available'] ?? false)) {
			$backends[] = 'apcu';
		}
		if (($description['volatile_cache']['enabled'] ?? false) && ($description['volatile_cache']['available'] ?? false)) {
			$backends[] = 'volatile_cache';
		}
		if (($description['pinned_cache']['enabled'] ?? false) && ($description['pinned_cache']['available'] ?? false)) {
			$backends[] = 'pinned_cache';
		}

		return $backends;
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

	private function runScenario(string $caseName, string $backendName): void
	{
		$this->progress('reset ' . $caseName . ' / ' . $backendName);
		$this->requestText(['action' => 'reset', 'case' => $caseName, 'backend' => $backendName]);

		for ($iteration = 0; $iteration < $this->warmup; $iteration++) {
			$this->progress('warmup ' . ($iteration + 1) . '/' . $this->warmup . ' ' . $caseName . ' / ' . $backendName);
			if ($this->resetEachBatch) {
				$this->requestText(['action' => 'reset', 'case' => $caseName, 'backend' => $backendName]);
			}
			$this->requestMeasureBatch($caseName, $backendName);
		}

		for ($iteration = 1; $iteration <= $this->iterations; $iteration++) {
			$this->progress('measure ' . $iteration . '/' . $this->iterations . ' ' . $caseName . ' / ' . $backendName);
			if ($this->resetEachBatch) {
				$this->requestText(['action' => 'reset', 'case' => $caseName, 'backend' => $backendName]);
			}
			$sample = $this->requestMeasureBatch($caseName, $backendName);
			if ((int) $sample['store_count'] !== (int) $sample['operation_count']) {
				throw new RuntimeException($caseName . ' / ' . $backendName . ' did not store every operation');
			}
			$this->appendRawSample($caseName, $backendName, $iteration, $sample);
			$this->samples[$caseName][$backendName][] = $sample;
		}

		$this->progress('verify ' . $caseName . ' / ' . $backendName);
		$verifyWorkerIds = $this->keyMode === 'distinct' && $this->concurrency > 1
			? range(0, $this->concurrency - 1)
			: [0];
		foreach ($verifyWorkerIds as $workerId) {
			$verify = $this->requestVerifyWrite($caseName, $backendName, $workerId);
			if (($verify['cache_retention'] ?? '') !== 'retained') {
				throw new RuntimeException('Write-cache verification failed for ' . $caseName . ' / ' . $backendName . ' worker ' . $workerId . ': ' . ($verify['reason'] ?? 'unknown'));
			}
		}
	}

	private function requestMeasureBatch(string $caseName, string $backendName): array
	{
		if ($this->concurrency === 1) {
			return $this->requestMeasureWorker($caseName, $backendName, 0);
		}

		if (!function_exists('pcntl_fork')) {
			throw new RuntimeException('pcntl is required for concurrent write benchmarking');
		}

		$tempDir = sys_get_temp_dir() . '/opcache-static-cache-write-' . bin2hex(random_bytes(6));
		if (!mkdir($tempDir, 0700, true) && !is_dir($tempDir)) {
			throw new RuntimeException('Unable to create temporary directory: ' . $tempDir);
		}

		$pids = [];
		$start = hrtime(true);
		for ($workerId = 0; $workerId < $this->concurrency; $workerId++) {
			$pid = pcntl_fork();
			if ($pid === -1) {
				$this->removeTempDir($tempDir);
				throw new RuntimeException('pcntl_fork() failed');
			}
			if ($pid === 0) {
				$resultFile = $tempDir . '/' . $workerId . '.json';
				$errorFile = $tempDir . '/' . $workerId . '.error';
				try {
					$sample = $this->requestMeasureWorker($caseName, $backendName, $workerId);
					file_put_contents($resultFile, json_encode($sample, JSON_UNESCAPED_SLASHES));
					exit(0);
				} catch (Throwable $throwable) {
					file_put_contents($errorFile, $throwable->getMessage());
					exit(1);
				}
			}

			$pids[] = $pid;
		}

		foreach ($pids as $pid) {
			pcntl_waitpid($pid, $status);
			if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
				foreach (range(0, $this->concurrency - 1) as $workerId) {
					$errorFile = $tempDir . '/' . $workerId . '.error';
					if (is_file($errorFile)) {
						$message = trim((string) file_get_contents($errorFile));
						$this->removeTempDir($tempDir);
						throw new RuntimeException($message === '' ? 'Concurrent worker failed' : $message);
					}
				}
				$this->removeTempDir($tempDir);
				throw new RuntimeException('Concurrent write worker exited unsuccessfully');
			}
		}

		$wallUs = (int) ((hrtime(true) - $start) / 1000);
		$samples = [];
		foreach (range(0, $this->concurrency - 1) as $workerId) {
			$resultFile = $tempDir . '/' . $workerId . '.json';
			if (!is_file($resultFile)) {
				$this->removeTempDir($tempDir);
				throw new RuntimeException('Missing concurrent worker result for worker ' . $workerId);
			}
			$data = json_decode((string) file_get_contents($resultFile), true);
			if (!is_array($data)) {
				$this->removeTempDir($tempDir);
				throw new RuntimeException('Invalid concurrent worker result for worker ' . $workerId);
			}
			$samples[] = $data;
		}

		$this->removeTempDir($tempDir);

		$checksums = array_unique(array_column($samples, 'checksum'));
		if (count($checksums) !== 1) {
			throw new RuntimeException('Concurrent write workers produced mismatched checksums');
		}

		$operationCount = (int) ($samples[0]['operation_count'] ?? 0) * count($samples);

		return [
			'operation_count' => $operationCount,
			'wall_us' => $wallUs,
			'worker_us' => $this->mean($samples, 'worker_us'),
			'memory_delta' => $this->mean($samples, 'memory_delta'),
			'peak_delta' => $this->mean($samples, 'peak_delta'),
			'store_count' => array_sum(array_column($samples, 'store_count')),
			'build_count' => array_sum(array_column($samples, 'build_count')),
			'checksum' => $samples[0]['checksum'],
			'write_score' => array_sum(array_column($samples, 'write_score')),
		];
	}

	private function requestMeasureWorker(string $caseName, string $backendName, int $workerId): array
	{
		$start = hrtime(true);
		$body = $this->requestText([
			'action' => 'measure_write',
			'case' => $caseName,
			'backend' => $backendName,
			'operations' => (string) $this->operations,
			'key_mode' => $this->keyMode,
			'write_mode' => $this->writeMode,
			'key_space' => (string) $this->keySpace,
			'worker' => (string) $workerId,
			'format' => 'tsv',
		]);
		$wallUs = (int) ((hrtime(true) - $start) / 1000);
		$fields = explode("\t", trim($body));
		if (count($fields) < 8) {
			throw new RuntimeException('Unexpected write TSV response: ' . $body);
		}

		return [
			'operation_count' => (int) $fields[0],
			'worker_us' => (int) $fields[1],
			'memory_delta' => (int) $fields[2],
			'peak_delta' => (int) $fields[3],
			'store_count' => (int) $fields[4],
			'build_count' => (int) $fields[5],
			'checksum' => $fields[6],
			'write_score' => (int) $fields[7],
			'wall_us' => $wallUs,
		];
	}

	private function requestVerifyWrite(string $caseName, string $backendName, int $workerId): array
	{
		$body = $this->requestText([
			'action' => 'verify_write',
			'case' => $caseName,
			'backend' => $backendName,
			'key_mode' => $this->keyMode,
			'write_mode' => $this->writeMode,
			'key_space' => (string) $this->keySpace,
			'worker' => (string) $workerId,
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
			$this->scenarioName,
			$caseName,
			$backendName,
			(string) $iteration,
			(string) $this->concurrency,
			$this->keyMode,
			$this->writeMode,
			(string) $this->keySpace,
			(string) $sample['operation_count'],
			(string) $sample['wall_us'],
			$this->formatNumber((float) $sample['worker_us'], 2),
			$this->formatNumber((float) $sample['memory_delta'], 2),
			$this->formatNumber((float) $sample['peak_delta'], 2),
			(string) $sample['store_count'],
			(string) $sample['build_count'],
			$sample['checksum'],
			(string) $sample['write_score'],
		];
		file_put_contents($this->rawFile, implode("\t", array_map([$this, 'tsv'], $row)) . "\n", FILE_APPEND);
	}

	private function writeSummaryFiles(): void
	{
		file_put_contents($this->summaryFile, "runtime\tscenario\tcase\tbackend\tconcurrency\tkey_mode\twrite_mode\tkey_space\toperation_count\tsamples\tmean_wall_us\tmean_wall_ms\tmean_worker_us\tmean_store_us\tmean_store_ms\tmean_store_rate_ops_s\tmean_memory_delta\tmean_peak_delta\tmax_build_count\tmean_write_score\n");
		foreach ($this->samples as $caseName => $backendSamples) {
			foreach ($backendSamples as $backendName => $samples) {
				$row = $this->summarize($caseName, $backendName, $samples);
				$this->summaryRows[$caseName][$backendName] = $row;
				file_put_contents($this->summaryFile, implode("\t", array_map([$this, 'tsv'], array_values($row))) . "\n", FILE_APPEND);
			}
		}

		$this->writeApcuComparison();
	}

	private function summarize(string $caseName, string $backendName, array $samples): array
	{
		$count = count($samples);
		$operationCount = $samples[0]['operation_count'] ?? ($this->operations * $this->concurrency);
		$meanWallUs = $this->mean($samples, 'wall_us');
		$meanStoreUs = $meanWallUs / max(1, $operationCount);
		$meanStoreRate = 1000000 / max(0.000001, $meanStoreUs);

		return [
			'runtime' => $this->runtimeLabel,
			'scenario' => $this->scenarioName,
			'case' => $caseName,
			'backend' => $backendName,
			'concurrency' => (string) $this->concurrency,
			'key_mode' => $this->keyMode,
			'write_mode' => $this->writeMode,
			'key_space' => (string) $this->keySpace,
			'operation_count' => (string) $operationCount,
			'samples' => (string) $count,
			'mean_wall_us' => $this->formatNumber($meanWallUs, 2),
			'mean_wall_ms' => $this->formatNumber($meanWallUs / 1000, 3),
			'mean_worker_us' => $this->formatNumber($this->mean($samples, 'worker_us'), 2),
			'mean_store_us' => $this->formatNumber($meanStoreUs, 3),
			'mean_store_ms' => $this->formatNumber($meanStoreUs / 1000, 6),
			'mean_store_rate_ops_s' => $this->formatNumber($meanStoreRate, 2),
			'mean_memory_delta' => $this->formatNumber($this->mean($samples, 'memory_delta'), 2),
			'mean_peak_delta' => $this->formatNumber($this->mean($samples, 'peak_delta'), 2),
			'max_build_count' => (string) max(array_column($samples, 'build_count')),
			'mean_write_score' => $this->formatNumber($this->mean($samples, 'write_score'), 2),
		];
	}

	private function writeApcuComparison(): void
	{
		file_put_contents($this->comparisonFile, "runtime\tscenario\tcase\tbackend\toperation_count\tapcu_mean_store_us\tcandidate_mean_store_us\tcandidate_delta_percent\tcandidate_relation\n");
		foreach ($this->summaryRows as $caseName => $rows) {
			if (!isset($rows['apcu'])) {
				continue;
			}
			$apcuUs = (float) $rows['apcu']['mean_store_us'];
			foreach ($rows as $backendName => $row) {
				if ($backendName === 'apcu') {
					continue;
				}
				[$delta, $relation] = $this->relation($apcuUs, (float) $row['mean_store_us'], 'APCu');
				$out = [$this->runtimeLabel, $this->scenarioName, $caseName, $backendName, $row['operation_count'], $this->formatNumber($apcuUs, 3), $row['mean_store_us'], $delta, $relation];
				file_put_contents($this->comparisonFile, implode("\t", array_map([$this, 'tsv'], $out)) . "\n", FILE_APPEND);
			}
		}
	}

	private function buildDokuWikiReport(): string
	{
		$lines = [];
		$lines[] = '==== ' . $this->runtimeLabel . ' write scenario ==== ';
		$lines[] = '';
		$lines[] = '^ Setting ^ Value ^';
		$lines[] = '| Timestamp | ' . $this->stamp . ' |';
		$lines[] = '| Scenario | ' . ($this->scenarioName !== '' ? $this->scenarioName : 'custom') . ' |';
		$lines[] = '| Iterations | ' . $this->iterations . ' |';
		$lines[] = '| Warmup requests | ' . $this->warmup . ' |';
		$lines[] = '| Operations/request | ' . $this->operations . ' |';
		$lines[] = '| Concurrency | ' . $this->concurrency . ' |';
		$lines[] = '| Key mode | ' . $this->keyMode . ' |';
		$lines[] = '| Write mode | ' . $this->writeMode . ' |';
		$lines[] = '| Key space | ' . $this->keySpace . ' |';
		$lines[] = '';
		$lines[] = '^ Workload ^ Backend ^ Mean batch ^ Mean store vs APCu ^ Store throughput ^ Max builds ^';
		foreach ($this->summaryRows as $caseName => $rows) {
			$apcuUs = isset($rows['apcu']) ? (float) $rows['apcu']['mean_store_us'] : null;
			foreach ($rows as $backendName => $row) {
				$operation = $this->formatOperationVsApcu((float) $row['mean_store_us'], $apcuUs, $backendName);
				$lines[] = '| ' . $caseName . ' | ' . $backendName . ' | ' . $row['mean_wall_ms'] . ' ms | ' . $operation . ' | ' . $row['mean_store_rate_ops_s'] . ' ops/s | ' . $row['max_build_count'] . ' |';
			}
		}
		$lines[] = '';
		$lines[] = 'Raw results: ' . basename($this->rawFile) . ', ' . basename($this->summaryFile) . ', ' . basename($this->comparisonFile);
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

	private function removeTempDir(string $dir): void
	{
		foreach (glob($dir . '/*') ?: [] as $path) {
			@unlink($path);
		}
		@rmdir($dir);
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
		exit((new BenchmarkWriteRunner())->run($argv));
	} catch (Throwable $throwable) {
		fwrite(STDERR, "\nBenchmark failed: " . $throwable->getMessage() . "\n");
		exit(1);
	}
}
