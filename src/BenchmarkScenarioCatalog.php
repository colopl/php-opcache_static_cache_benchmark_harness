<?php

declare(strict_types=1);

final class BenchmarkScenarioCatalog
{
	private const SCENARIOS = [
		'vote_read_long' => [
			'runner' => 'read',
			'description' => 'Longer steady-state read-after-prime measurements on representative repeated-read workloads.',
			'cases' => [
				'route_table_read',
				'large_array',
				'metadata_object_read',
				'safe_direct_object',
				'spl_collection_object',
				'carbon_datetime_object',
				'nested_array_assignment',
			],
			'backends' => [
				'apcu',
				'volatile_cache',
				'persistent_cache',
				'volatile_static_immediate_property',
				'volatile_static_tracking_property',
				'persistent_static_property',
				'volatile_static_immediate_method',
				'volatile_static_tracking_method',
				'persistent_static_method',
			],
			'iterations' => 20,
			'warmup' => 3,
			'operations' => 3000,
		],
		'carbon_datetime_compare' => [
			'runner' => 'read',
			'description' => 'Focused DateTimeImmutable direct-restore and Carbon DateTime serializer comparison.',
			'cases' => [
				'safe_direct_object',
				'carbon_datetime_object',
			],
			'backends' => [
				'apcu',
				'volatile_cache',
				'persistent_cache',
				'volatile_static_immediate_property',
				'volatile_static_tracking_property',
				'persistent_static_property',
				'volatile_static_immediate_method',
				'volatile_static_tracking_method',
				'persistent_static_method',
			],
			'iterations' => 20,
			'warmup' => 3,
			'operations' => 3000,
		],
		'fetch_mutate_object' => [
			'runner' => 'read',
			'description' => 'Explicit cache fetches where the fetched object graph is mutated before the next fetch.',
			'cases' => [
				'metadata_object_fetch_mutate',
			],
			'backends' => [
				'apcu',
				'volatile_cache',
				'persistent_cache',
			],
			'iterations' => 20,
			'warmup' => 3,
			'operations' => 3000,
		],
		'vote_write_throughput' => [
			'runner' => 'write',
			'description' => 'Sequential explicit-cache write throughput against a bounded distinct-key ring.',
			'cases' => [
				'route_table_read',
				'metadata_object_read',
				'safe_direct_object',
				'spl_collection_object',
				'nested_array_assignment',
			],
			'backends' => [
				'apcu',
				'volatile_cache',
				'persistent_cache',
			],
			'iterations' => 15,
			'warmup' => 2,
			'operations' => 128,
			'concurrency' => 1,
			'key_mode' => 'distinct',
			'key_space' => 32,
		],
		'vote_write_contention_shared' => [
			'runner' => 'write',
			'description' => 'Concurrent explicit-cache writes to one shared hot key.',
			'cases' => [
				'route_table_read',
				'metadata_object_read',
				'safe_direct_object',
				'spl_collection_object',
				'nested_array_assignment',
			],
			'backends' => [
				'apcu',
				'volatile_cache',
				'persistent_cache',
			],
			'iterations' => 8,
			'warmup' => 1,
			'operations' => 32,
			'concurrency' => 5,
			'key_mode' => 'shared',
			'key_space' => 1,
		],
		'vote_write_contention_distinct' => [
			'runner' => 'write',
			'description' => 'Concurrent explicit-cache writes where each worker publishes to its own key ring.',
			'cases' => [
				'route_table_read',
				'metadata_object_read',
				'safe_direct_object',
				'spl_collection_object',
				'nested_array_assignment',
			],
			'backends' => [
				'apcu',
				'volatile_cache',
				'persistent_cache',
			],
			'iterations' => 8,
			'warmup' => 1,
			'operations' => 32,
			'concurrency' => 5,
			'key_mode' => 'distinct',
			'key_space' => 16,
		],
		'vote_entry_reservation_contention' => [
			'runner' => 'write',
			'description' => 'Concurrent single-builder entry reservation against one shared hot key.',
			'cases' => [
				'route_table_read',
				'metadata_object_read',
			],
			'backends' => [
				'apcu',
				'volatile_cache',
				'persistent_cache',
			],
			'iterations' => 8,
			'warmup' => 1,
			'operations' => 32,
			'concurrency' => 5,
			'key_mode' => 'shared',
			'key_space' => 1,
			'write_mode' => 'entry_reservation',
			'reset_each_batch' => true,
		],
	];

	public static function all(): array
	{
		return self::SCENARIOS;
	}

	public static function names(): array
	{
		return array_keys(self::SCENARIOS);
	}

	public static function get(string $name): array
	{
		if (!isset(self::SCENARIOS[$name])) {
			throw new InvalidArgumentException('Unknown benchmark scenario: ' . $name);
		}

		return self::SCENARIOS[$name];
	}
}
