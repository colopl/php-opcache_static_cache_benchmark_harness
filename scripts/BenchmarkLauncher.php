<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/BenchmarkScenarioCatalog.php';
require_once __DIR__ . '/BenchmarkRunner.php';
require_once __DIR__ . '/BenchmarkWriteRunner.php';

final class BenchmarkLauncher
{
	public function run(array $argv): int
	{
		$runner = null;
		$scenario = null;

		for ($index = 1, $count = count($argv); $index < $count; $index++) {
			$arg = $argv[$index];
			if ($arg === '--runner' && isset($argv[$index + 1])) {
				$runner = $argv[$index + 1];
				$index++;
				continue;
			}
			if ($arg === '--scenario' && isset($argv[$index + 1])) {
				$scenario = $argv[$index + 1];
				$index++;
			}
		}

		if ($runner === null && $scenario !== null) {
			$runner = BenchmarkScenarioCatalog::get($scenario)['runner'] ?? null;
		}
		$runner ??= 'read';

		return match ($runner) {
			'read' => (new BenchmarkRunner())->run($argv),
			'write' => (new BenchmarkWriteRunner())->run($argv),
			default => throw new InvalidArgumentException('Unknown benchmark runner: ' . $runner),
		};
	}
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
	try {
		exit((new BenchmarkLauncher())->run($argv));
	} catch (Throwable $throwable) {
		fwrite(STDERR, "\nBenchmark failed: " . $throwable->getMessage() . "\n");
		exit(1);
	}
}
