<?php

declare(strict_types=1);

final class BenchmarkReferencedPayload
{
	public function __construct(
		public string $label,
		public int $revision,
	) {
	}
}

final class BenchmarkReferencePayload
{
	public function __construct(
		public string $name,
		public BenchmarkReferencedPayload $child,
	) {
	}
}

final class BenchmarkCyclePayload
{
	public ?BenchmarkCyclePayload $peer = null;

	public function __construct(
		public string $name,
		public int $revision,
	) {
	}
}

final class BenchmarkFastPathPayload
{
	public function __construct(
		public DateTimeImmutable $createdAt,
		public DateTimeZone $timezone,
		public DateTimeImmutable $expiresAt,
		public DateInterval $gracePeriod,
	) {
	}
}

final class BenchmarkCarbonDateTimePayload
{
	public function __construct(
		public Carbon\CarbonImmutable $createdAt,
		public Carbon\Carbon $updatedAt,
		public Carbon\CarbonTimeZone $timezone,
		public array $timeline,
	) {
	}
}

final class BenchmarkSplCollectionPayload extends ArrayObject
{
	public function __construct(
		array $storage,
		public string $name,
		public int $revision,
	) {
		parent::__construct($storage, ArrayObject::ARRAY_AS_PROPS);
	}
}

final class BenchmarkLargeObjectPayload
{
	public function __construct(
		public string $name,
		public array $rows,
		public BenchmarkReferencedPayload $child,
	) {
	}
}

final class BenchmarkMetadataPayload
{
	public function __construct(
		public string $name,
		public array $routes,
		public array $services,
		public BenchmarkReferencedPayload $owner,
	) {
	}
}

#[\OPcache\VolatileStatic(strategy: OPcache\CacheStrategy::Immediate)]
final class BenchmarkVolatileStaticImmediateClassCache
{
	public static mixed $payload = null;
	public static ?string $payloadKind = null;

	public static function resolve(string $payloadKind): array
	{
		$cacheHit = BenchmarkApplication::attributeCacheHit($payloadKind, self::$payload, self::$payloadKind);
		if (!$cacheHit) {
			self::$payloadKind = $payloadKind;
			self::$payload = BenchmarkApplication::buildPayload($payloadKind);
			if (BenchmarkApplication::isArrayAssignmentPayload($payloadKind)) {
				BenchmarkApplication::mutateAssignedPayload($payloadKind, self::$payload);
			}
		}

		return BenchmarkApplication::attributeResult($payloadKind, self::$payload, $cacheHit);
	}
}

#[\OPcache\VolatileStatic(strategy: OPcache\CacheStrategy::Tracking)]
final class BenchmarkVolatileStaticTrackingClassCache
{
	public static mixed $payload = null;
	public static ?string $payloadKind = null;

	public static function resolve(string $payloadKind): array
	{
		$cacheHit = BenchmarkApplication::attributeCacheHit($payloadKind, self::$payload, self::$payloadKind);
		if (!$cacheHit) {
			self::$payloadKind = $payloadKind;
			self::$payload = BenchmarkApplication::buildPayload($payloadKind);
			if (BenchmarkApplication::isArrayAssignmentPayload($payloadKind)) {
				BenchmarkApplication::mutateAssignedPayload($payloadKind, self::$payload);
			}
		}

		return BenchmarkApplication::attributeResult($payloadKind, self::$payload, $cacheHit);
	}
}

#[\OPcache\PersistentStatic]
final class BenchmarkPersistentStaticClassCache
{
	public static mixed $payload = null;
	public static ?string $payloadKind = null;

	public static function resolve(string $payloadKind): array
	{
		$cacheHit = BenchmarkApplication::attributeCacheHit($payloadKind, self::$payload, self::$payloadKind);
		if (!$cacheHit) {
			self::$payloadKind = $payloadKind;
			self::$payload = BenchmarkApplication::buildPayload($payloadKind);
			if (BenchmarkApplication::isArrayAssignmentPayload($payloadKind)) {
				BenchmarkApplication::mutateAssignedPayload($payloadKind, self::$payload);
			}
		}

		return BenchmarkApplication::attributeResult($payloadKind, self::$payload, $cacheHit);
	}
}

final class BenchmarkVolatileStaticImmediatePropertyCache
{
	#[\OPcache\VolatileStatic(strategy: OPcache\CacheStrategy::Immediate)]
	public static mixed $payload = null;

	#[\OPcache\VolatileStatic(strategy: OPcache\CacheStrategy::Immediate)]
	public static ?string $payloadKind = null;

	public static function resolve(string $payloadKind): array
	{
		$cacheHit = BenchmarkApplication::attributeCacheHit($payloadKind, self::$payload, self::$payloadKind);
		if (!$cacheHit) {
			self::$payloadKind = $payloadKind;
			self::$payload = BenchmarkApplication::buildPayload($payloadKind);
			if (BenchmarkApplication::isArrayAssignmentPayload($payloadKind)) {
				BenchmarkApplication::mutateAssignedPayload($payloadKind, self::$payload);
			}
		}

		return BenchmarkApplication::attributeResult($payloadKind, self::$payload, $cacheHit);
	}
}

final class BenchmarkVolatileStaticTrackingPropertyCache
{
	#[\OPcache\VolatileStatic(strategy: \OPcache\CacheStrategy::Tracking)]
	public static mixed $payload = null;

	#[\OPcache\VolatileStatic(strategy: \OPcache\CacheStrategy::Tracking)]
	public static ?string $payloadKind = null;

	public static function resolve(string $payloadKind): array
	{
		$cacheHit = BenchmarkApplication::attributeCacheHit($payloadKind, self::$payload, self::$payloadKind);
		if (!$cacheHit) {
			self::$payloadKind = $payloadKind;
			self::$payload = BenchmarkApplication::buildPayload($payloadKind);
			if (BenchmarkApplication::isArrayAssignmentPayload($payloadKind)) {
				BenchmarkApplication::mutateAssignedPayload($payloadKind, self::$payload);
			}
		}

		return BenchmarkApplication::attributeResult($payloadKind, self::$payload, $cacheHit);
	}
}

final class BenchmarkPersistentStaticPropertyCache
{
	#[\OPcache\PersistentStatic]
	public static mixed $payload = null;

	#[\OPcache\PersistentStatic]
	public static ?string $payloadKind = null;

	public static function resolve(string $payloadKind): array
	{
		$cacheHit = BenchmarkApplication::attributeCacheHit($payloadKind, self::$payload, self::$payloadKind);
		if (!$cacheHit) {
			self::$payloadKind = $payloadKind;
			self::$payload = BenchmarkApplication::buildPayload($payloadKind);
			if (BenchmarkApplication::isArrayAssignmentPayload($payloadKind)) {
				BenchmarkApplication::mutateAssignedPayload($payloadKind, self::$payload);
			}
		}

		return BenchmarkApplication::attributeResult($payloadKind, self::$payload, $cacheHit);
	}
}

final class BenchmarkVolatileStaticImmediateMethodCache
{
	#[\OPcache\VolatileStatic(strategy: \OPcache\CacheStrategy::Immediate)]
	public static function resolve(string $payloadKind): array
	{
		static $payload = null;
		static $storedPayloadKind = null;

		$cacheHit = BenchmarkApplication::attributeCacheHit($payloadKind, $payload, $storedPayloadKind);
		if (!$cacheHit) {
			$storedPayloadKind = $payloadKind;
			$payload = BenchmarkApplication::buildPayload($payloadKind);
			if (BenchmarkApplication::isArrayAssignmentPayload($payloadKind)) {
				BenchmarkApplication::mutateAssignedPayload($payloadKind, $payload);
			}
		}

		return BenchmarkApplication::attributeResult($payloadKind, $payload, $cacheHit);
	}
}

final class BenchmarkVolatileStaticTrackingMethodCache
{
	#[\OPcache\VolatileStatic(strategy: \OPcache\CacheStrategy::Tracking)]
	public static function resolve(string $payloadKind): array
	{
		static $payload = null;
		static $storedPayloadKind = null;

		$cacheHit = BenchmarkApplication::attributeCacheHit($payloadKind, $payload, $storedPayloadKind);
		if (!$cacheHit) {
			$storedPayloadKind = $payloadKind;
			$payload = BenchmarkApplication::buildPayload($payloadKind);
			if (BenchmarkApplication::isArrayAssignmentPayload($payloadKind)) {
				BenchmarkApplication::mutateAssignedPayload($payloadKind, $payload);
			}
		}

		return BenchmarkApplication::attributeResult($payloadKind, $payload, $cacheHit);
	}
}

final class BenchmarkPersistentStaticMethodCache
{
	#[\OPcache\PersistentStatic]
	public static function resolve(string $payloadKind): array
	{
		static $payload = null;
		static $storedPayloadKind = null;

		$cacheHit = BenchmarkApplication::attributeCacheHit($payloadKind, $payload, $storedPayloadKind);
		if (!$cacheHit) {
			$storedPayloadKind = $payloadKind;
			$payload = BenchmarkApplication::buildPayload($payloadKind);
			if (BenchmarkApplication::isArrayAssignmentPayload($payloadKind)) {
				BenchmarkApplication::mutateAssignedPayload($payloadKind, $payload);
			}
		}

		return BenchmarkApplication::attributeResult($payloadKind, $payload, $cacheHit);
	}
}

final class BenchmarkApplication
{
	private const VERSION = 1;
	private const MAX_OPERATION_COUNT = 100000;
	private const LARGE_ROW_COUNT = 192;
	private const CARBON_TIMELINE_COUNT = 64;
	private const SPL_COLLECTION_COUNT = 64;
	private const MULTI_KEY_CONFIG_COUNT = 32;

	private const CONSTANT_ARRAY_PAYLOAD = [
		'routes' => [
			'/catalog/books/0' => ['controller' => 'CatalogController::show', 'methods' => ['GET'], 'flags' => [true, false, true], 'score' => 100],
			'/catalog/books/1' => ['controller' => 'CatalogController::show', 'methods' => ['GET'], 'flags' => [false, true, true], 'score' => 101],
			'/catalog/books/2' => ['controller' => 'CatalogController::show', 'methods' => ['GET'], 'flags' => [true, true, false], 'score' => 102],
			'/catalog/books/3' => ['controller' => 'CatalogController::show', 'methods' => ['GET'], 'flags' => [false, false, true], 'score' => 103],
			'/catalog/books/4' => ['controller' => 'CatalogController::show', 'methods' => ['GET'], 'flags' => [true, false, false], 'score' => 104],
			'/catalog/books/5' => ['controller' => 'CatalogController::show', 'methods' => ['GET'], 'flags' => [false, true, false], 'score' => 105],
			'/catalog/books/6' => ['controller' => 'CatalogController::show', 'methods' => ['GET'], 'flags' => [true, true, true], 'score' => 106],
			'/catalog/books/7' => ['controller' => 'CatalogController::show', 'methods' => ['GET'], 'flags' => [false, false, false], 'score' => 107],
		],
		'headers' => [
			'cache-control' => 'public, max-age=60',
			'x-benchmark' => 'opcache-static-cache',
		],
		'weights' => [3, 5, 8, 13, 21, 34, 55, 89],
	];

	private const PAYLOAD_LABELS = [
		'constant_array' => 'Constant array',
		'route_table_read' => 'Compiled route table read',
		'large_array' => 'Large nested array',
		'large_object_graph' => 'Large direct userland-object graph',
		'metadata_object_read' => 'Application metadata object read',
		'metadata_object_fetch_mutate' => 'Application metadata object fetch and mutate',
		'multi_key_config_read' => 'Multi-key configuration read',
		'safe_direct_object' => 'DateTime/DateInterval direct-restore object',
		'spl_collection_object' => 'SPL collection direct-restore object',
		'carbon_datetime_object' => 'Carbon DateTime serializer object',
		'serialized_cycle_object' => 'Serialized cyclic object',
		'reference_assignment_object' => 'Assigned object with child reference',
		'cycle_assignment_object' => 'Assigned cyclic object',
		'nested_array_assignment' => 'Assigned nested array mutation',
	];

	private const BACKEND_LABELS = [
		'apcu' => 'apcu_store / apcu_fetch',
		'volatile_cache' => 'volatile_store / volatile_fetch',
		'persistent_cache' => 'persistent_store / persistent_fetch',
		'volatile_static_immediate_class' => 'Class #[\\OPcache\\VolatileStatic(Immediate)]',
		'volatile_static_immediate_property' => 'Property #[\\OPcache\\VolatileStatic(Immediate)]',
		'volatile_static_immediate_method' => 'Method #[\\OPcache\\VolatileStatic(Immediate)]',
		'volatile_static_tracking_class' => 'Class #[\\OPcache\\VolatileStatic(Tracking)]',
		'volatile_static_tracking_property' => 'Property #[\\OPcache\\VolatileStatic(Tracking)]',
		'volatile_static_tracking_method' => 'Method #[\\OPcache\\VolatileStatic(Tracking)]',
		'persistent_static_class' => 'Class #[\\OPcache\\PersistentStatic]',
		'persistent_static_property' => 'Property #[\\OPcache\\PersistentStatic]',
		'persistent_static_method' => 'Method #[\\OPcache\\PersistentStatic]',
	];

	public function handle(): void
	{
		$action = (string) ($_GET['action'] ?? 'describe');
		$format = (string) ($_GET['format'] ?? 'json');

		try {
			switch ($action) {
				case 'describe':
					$description = $this->describe();
					if ($format === 'matrix') {
						$this->respondCaseMatrix($description['cases']);
					} else {
						$this->respondJson($description);
					}
					return;

				case 'reset':
					$case = array_key_exists('case', $_GET) ? (string) $_GET['case'] : null;
					$backend = array_key_exists('backend', $_GET) ? (string) $_GET['backend'] : null;
					$this->respondJson($this->resetState($case, $backend));
					return;

				case 'prime':
					$case = (string) ($_GET['case'] ?? '');
					$backend = (string) ($_GET['backend'] ?? '');
					$this->respondJson($this->prime($case, $backend));
					return;

				case 'measure':
					$case = (string) ($_GET['case'] ?? '');
					$backend = (string) ($_GET['backend'] ?? '');
					$operations = self::parseOperationCount($_GET['operations'] ?? '1');
					$readOnly = (string) ($_GET['read_only'] ?? '0') === '1';
					$result = $this->measure($case, $backend, $operations, $readOnly);
					if ($format === 'tsv') {
						$this->respondTsv($result);
					} else {
						$this->respondJson($result);
					}
					return;

				case 'measure_write':
					$case = (string) ($_GET['case'] ?? '');
					$backend = (string) ($_GET['backend'] ?? '');
					$operations = self::parseOperationCount($_GET['operations'] ?? '1');
					$keyMode = self::parseWriteKeyMode($_GET['key_mode'] ?? 'shared');
					$writeMode = self::parseWriteMode($_GET['write_mode'] ?? 'store');
					$workerId = self::parseBoundedInt($_GET['worker'] ?? '0', 'worker', 0, 1024);
					$keySpace = self::parseBoundedInt($_GET['key_space'] ?? '1', 'key_space', 1, 4096);
					$result = $this->measureWrite($case, $backend, $operations, $keyMode, $writeMode, $workerId, $keySpace);
					if ($format === 'tsv') {
						$this->respondWriteTsv($result);
					} else {
						$this->respondJson($result);
					}
					return;

				case 'verify':
					$case = (string) ($_GET['case'] ?? '');
					$backend = (string) ($_GET['backend'] ?? '');
					$result = $this->verify($case, $backend);
					if ($format === 'tsv') {
						$this->respondVerifyTsv($result);
					} else {
						$this->respondJson($result);
					}
					return;

				case 'verify_write':
					$case = (string) ($_GET['case'] ?? '');
					$backend = (string) ($_GET['backend'] ?? '');
					$keyMode = self::parseWriteKeyMode($_GET['key_mode'] ?? 'shared');
					$writeMode = self::parseWriteMode($_GET['write_mode'] ?? 'store');
					$workerId = self::parseBoundedInt($_GET['worker'] ?? '0', 'worker', 0, 1024);
					$keySpace = self::parseBoundedInt($_GET['key_space'] ?? '1', 'key_space', 1, 4096);
					$result = $this->verifyWrite($case, $backend, $keyMode, $writeMode, $workerId, $keySpace);
					if ($format === 'tsv') {
						$this->respondVerifyTsv($result);
					} else {
						$this->respondJson($result);
					}
					return;
			}

			throw new InvalidArgumentException('Unknown action: ' . $action);
		} catch (Throwable $throwable) {
			if ($format === 'tsv') {
				header('Content-Type: text/plain; charset=UTF-8', true, 500);
				echo $throwable->getMessage(), "\n";
			} else {
				$this->respondJson([
					'ok' => false,
					'error' => $throwable->getMessage(),
				], 500);
			}
		}
	}

	public static function payloadLabels(): array
	{
		return self::PAYLOAD_LABELS;
	}

	public static function backendLabels(): array
	{
		return self::BACKEND_LABELS;
	}

	private static function parseOperationCount(mixed $value): int
	{
		if (!is_scalar($value)) {
			throw new InvalidArgumentException('operations must be an integer between 1 and ' . self::MAX_OPERATION_COUNT);
		}

		$stringValue = (string) $value;
		if ($stringValue === '' || strspn($stringValue, '0123456789') !== strlen($stringValue)) {
			throw new InvalidArgumentException('operations must be an integer between 1 and ' . self::MAX_OPERATION_COUNT);
		}

		$operations = (int) $stringValue;
		if ($operations < 1 || $operations > self::MAX_OPERATION_COUNT) {
			throw new InvalidArgumentException('operations must be an integer between 1 and ' . self::MAX_OPERATION_COUNT);
		}

		return $operations;
	}

	private static function parseBoundedInt(mixed $value, string $name, int $minimum, int $maximum): int
	{
		if (!is_scalar($value)) {
			throw new InvalidArgumentException($name . ' must be an integer between ' . $minimum . ' and ' . $maximum);
		}

		$stringValue = (string) $value;
		if ($stringValue === '' || strspn($stringValue, '0123456789') !== strlen($stringValue)) {
			throw new InvalidArgumentException($name . ' must be an integer between ' . $minimum . ' and ' . $maximum);
		}

		$parsedValue = (int) $stringValue;
		if ($parsedValue < $minimum || $parsedValue > $maximum) {
			throw new InvalidArgumentException($name . ' must be an integer between ' . $minimum . ' and ' . $maximum);
		}

		return $parsedValue;
	}

	private static function parseWriteKeyMode(mixed $value): string
	{
		if (!is_scalar($value)) {
			throw new InvalidArgumentException('key_mode must be one of: shared, distinct');
		}

		$mode = (string) $value;
		if ($mode !== 'shared' && $mode !== 'distinct') {
			throw new InvalidArgumentException('key_mode must be one of: shared, distinct');
		}

		return $mode;
	}

	private static function parseWriteMode(mixed $value): string
	{
		if (!is_scalar($value)) {
			throw new InvalidArgumentException('write_mode must be one of: store, entry_reservation');
		}

		$mode = (string) $value;
		if ($mode !== 'store' && $mode !== 'entry_reservation') {
			throw new InvalidArgumentException('write_mode must be one of: store, entry_reservation');
		}

		return $mode;
	}

	public static function attributeCacheHit(string $payloadKind, mixed $payload, ?string $storedPayloadKind): bool
	{
		return $storedPayloadKind === $payloadKind && $payload !== null;
	}

	public static function resolveAttributePayload(string $payloadKind, mixed &$payload, ?string &$storedPayloadKind): array
	{
		$cacheHit = self::attributeCacheHit($payloadKind, $payload, $storedPayloadKind);
		if (!$cacheHit) {
			$storedPayloadKind = $payloadKind;
			$payload = self::buildPayload($payloadKind);
			if (self::isArrayAssignmentPayload($payloadKind)) {
				self::mutateAssignedPayload($payloadKind, $payload);
			}
		}

		return self::attributeResult($payloadKind, $payload, $cacheHit);
	}

	public static function attributeResult(string $payloadKind, mixed $payload, bool $cacheHit): array
	{
		return [
			'payload' => $payload,
			'cache_hit' => $cacheHit,
			'build_count' => $cacheHit ? 0 : 1,
		];
	}

	public static function buildPayload(string $payloadKind): mixed
	{
		return match ($payloadKind) {
			'constant_array' => self::CONSTANT_ARRAY_PAYLOAD,
			'route_table_read' => [
				'name' => 'compiled-route-table',
				'routes' => self::buildRouteTable(),
				'generators' => self::buildUrlGenerators(),
			],
			'large_array' => [
				'name' => 'large-array',
				'rows' => self::buildLargeRows('array'),
				'flags' => [true, false, true, true],
			],
			'large_object_graph' => new BenchmarkLargeObjectPayload(
				'large-object-graph',
				self::buildLargeRows('object'),
				new BenchmarkReferencedPayload('large-object-child', 3),
			),
			'metadata_object_read' => new BenchmarkMetadataPayload(
				'application-metadata',
				self::buildRouteTable(),
				self::buildServiceMetadata(),
				new BenchmarkReferencedPayload('metadata-owner', 7),
			),
			'metadata_object_fetch_mutate' => new BenchmarkMetadataPayload(
				'application-metadata-fetch-mutate',
				self::buildRouteTable(),
				self::buildServiceMetadata(),
				new BenchmarkReferencedPayload('metadata-owner', 7),
			),
			'multi_key_config_read' => [
				'entries' => self::buildConfigEntries(),
			],
			'safe_direct_object' => new BenchmarkFastPathPayload(
				new DateTimeImmutable('2026-05-01 10:30:45.123456', new DateTimeZone('Asia/Tokyo')),
				new DateTimeZone('Europe/Paris'),
				new DateTimeImmutable('2027-07-04 14:35:51.654321', new DateTimeZone('Europe/Paris')),
				new DateInterval('P3DT5H8M13S'),
			),
			'spl_collection_object' => self::buildSplCollectionPayload(),
			'carbon_datetime_object' => new BenchmarkCarbonDateTimePayload(
				new Carbon\CarbonImmutable('2026-05-01 10:30:45.123456', new Carbon\CarbonTimeZone('Asia/Tokyo')),
				new Carbon\Carbon('2027-07-04 14:35:51.654321', new Carbon\CarbonTimeZone('Europe/Paris')),
				new Carbon\CarbonTimeZone('Europe/Paris'),
				self::buildCarbonTimeline(),
			),
			'serialized_cycle_object' => self::buildCyclePayload('serialized-root', 'serialized-peer', 1),
			'reference_assignment_object' => new BenchmarkReferencePayload(
				'reference-root',
				new BenchmarkReferencedPayload('reference-child', 1),
			),
			'cycle_assignment_object' => self::buildCyclePayload('cycle-root', 'cycle-peer', 1),
			'nested_array_assignment' => [
				'name' => 'nested-array-root',
				'nodes' => self::buildNestedArrayNodes(1),
			],
			default => throw new InvalidArgumentException('Unknown payload kind: ' . $payloadKind),
		};
	}

	public static function mutateAssignedPayload(string $payloadKind, mixed &$payload): void
	{
		switch ($payloadKind) {
			case 'reference_assignment_object':
				if (!$payload instanceof BenchmarkReferencePayload) {
					throw new RuntimeException('Reference assignment payload has an unexpected type');
				}
				$payload->child->label = 'reference-child-mutated';
				$payload->child->revision = 2;
				return;

			case 'cycle_assignment_object':
				if (!$payload instanceof BenchmarkCyclePayload || !$payload->peer instanceof BenchmarkCyclePayload) {
					throw new RuntimeException('Cycle assignment payload has an unexpected type');
				}
				$payload->peer->name = 'cycle-peer-mutated';
				$payload->peer->revision = 2;
				return;

			case 'nested_array_assignment':
				if (!is_array($payload) || !isset($payload['nodes'][7]) || !is_array($payload['nodes'][7])) {
					throw new RuntimeException('Nested array assignment payload has an unexpected type');
				}
				$payload['nodes'][7]['label'] = 'node-7-mutated';
				$payload['nodes'][7]['revision'] = 2;
				$payload['nodes'][7]['children'][2]['enabled'] = true;
				return;
		}
	}

	public static function payloadDigest(string $payloadKind, mixed $payload): string
	{
		return match ($payloadKind) {
			'constant_array' => self::constantArrayDigest($payload),
			'route_table_read' => self::routeTableDigest($payload),
			'large_array' => self::largeArrayDigest($payload),
			'large_object_graph' => self::largeObjectDigest($payload),
			'metadata_object_read', 'metadata_object_fetch_mutate' => self::metadataObjectDigest($payload),
			'multi_key_config_read' => self::multiKeyConfigDigest($payload),
			'safe_direct_object' => self::safeDirectDigest($payload),
			'spl_collection_object' => self::splCollectionDigest($payload),
			'carbon_datetime_object' => self::carbonDateTimeDigest($payload),
			'serialized_cycle_object', 'cycle_assignment_object' => self::serializedCycleDigest($payload),
			'reference_assignment_object' => self::referenceDigest($payload),
			'nested_array_assignment' => self::nestedArrayDigest($payload),
			default => throw new InvalidArgumentException('Unknown payload kind: ' . $payloadKind),
		};
	}

	public static function expectedDigest(string $payloadKind, bool $mutated): string
	{
		$payload = self::buildPayload($payloadKind);
		if ($mutated) {
			self::mutateAssignedPayload($payloadKind, $payload);
		}

		return self::payloadDigest($payloadKind, $payload);
	}

	public static function isAssignmentPayload(string $payloadKind): bool
	{
		return self::isObjectAssignmentPayload($payloadKind)
			|| self::isArrayAssignmentPayload($payloadKind);
	}

	private static function isObjectAssignmentPayload(string $payloadKind): bool
	{
		return $payloadKind === 'reference_assignment_object'
			|| $payloadKind === 'cycle_assignment_object';
	}

	public static function isArrayAssignmentPayload(string $payloadKind): bool
	{
		return $payloadKind === 'nested_array_assignment';
	}

	private static function isMultiKeyPayload(string $payloadKind): bool
	{
		return $payloadKind === 'multi_key_config_read';
	}

	private static function isFetchMutationPayload(string $payloadKind): bool
	{
		return $payloadKind === 'metadata_object_fetch_mutate';
	}

	private static function supportedBackendsForPayload(string $payloadKind, array $backends): array
	{
		if (!self::isFetchMutationPayload($payloadKind)) {
			return $backends;
		}

		return array_values(array_filter(
			$backends,
			static fn (string $backend): bool => $backend === 'apcu'
				|| $backend === 'volatile_cache'
				|| $backend === 'persistent_cache',
		));
	}

	private static function mutateFetchedPayload(string $payloadKind, mixed $payload, int $operationIndex): void
	{
		if (!self::isFetchMutationPayload($payloadKind)) {
			return;
		}

		if (!$payload instanceof BenchmarkMetadataPayload) {
			throw new RuntimeException('Fetch-mutation payload has an unexpected type');
		}

		$payload->owner->label = 'metadata-owner-mutated-' . ($operationIndex % 17);
		$payload->owner->revision = 100000 + $operationIndex;
	}

	private static function multiKeyEntryNames(): array
	{
		$entryNames = [];
		for ($index = 0; $index < self::MULTI_KEY_CONFIG_COUNT; $index++) {
			$entryNames[] = 'config_' . $index;
		}

		return $entryNames;
	}

	private static function buildCyclePayload(string $rootName, string $peerName, int $revision): BenchmarkCyclePayload
	{
		$root = new BenchmarkCyclePayload($rootName, $revision);
		$peer = new BenchmarkCyclePayload($peerName, $revision);
		$root->peer = $peer;
		$peer->peer = $root;

		return $root;
	}

	private static function buildCarbonTimeline(): array
	{
		$timeline = [];
		for ($index = 0; $index < self::CARBON_TIMELINE_COUNT; $index++) {
			$timezone = new Carbon\CarbonTimeZone($index % 2 === 0 ? 'Asia/Tokyo' : 'Europe/Paris');
			$base = new Carbon\CarbonImmutable('2026-05-01 10:30:45.123456', $timezone);
			$timeline[] = [
				'created' => $base->addDays($index),
				'updated' => Carbon\Carbon::instance($base->addDays($index + 1)->toMutable()),
				'timezone' => $timezone,
				'label' => 'carbon-timeline-' . $index,
			];
		}

		return $timeline;
	}

	private static function buildSplCollectionPayload(): BenchmarkSplCollectionPayload
	{
		$fixed = new SplFixedArray(self::SPL_COLLECTION_COUNT);
		$rows = [];
		for ($index = 0; $index < self::SPL_COLLECTION_COUNT; $index++) {
			$row = [
				'id' => $index,
				'label' => 'spl-row-' . $index,
				'score' => 1000 + ($index * 7),
			];
			$fixed[$index] = $row;
			$rows['row_' . $index] = $row;
		}

		return new BenchmarkSplCollectionPayload([
			'fixed' => $fixed,
			'map' => new ArrayObject($rows, ArrayObject::ARRAY_AS_PROPS),
			'iterator' => new ArrayIterator($rows),
			'recursive' => new RecursiveArrayIterator([
				'branch' => [
					'leaf_17' => ['score' => 17017],
					'leaf_31' => ['score' => 31031],
				],
			]),
		], 'spl-collection', 11);
	}

	private static function buildLargeRows(string $label): array
	{
		$rows = [];
		for ($index = 0; $index < self::LARGE_ROW_COUNT; $index++) {
			$rows[] = [
				'id' => $index,
				'path' => '/catalog/large/' . $index,
				'controller' => 'LargeCatalogController::show',
				'title' => $label . '-route-' . $index . '-' . str_repeat((string) ($index % 10), 64),
				'flags' => [$index % 2 === 0, $index % 3 === 0, $index % 5 === 0],
				'weights' => [$index, $index + 1, $index + 2, $index + 3, $index + 5],
				'metadata' => [
					'tenant' => 'tenant-' . ($index % 8),
					'locale' => $index % 2 === 0 ? 'ja_JP' : 'en_US',
					'tags' => ['cache', 'benchmark', 'row-' . ($index % 16)],
				],
			];
		}

		return $rows;
	}

	private static function buildRouteTable(): array
	{
		$routes = [];
		for ($index = 0; $index < self::LARGE_ROW_COUNT * 2; $index++) {
			$routes['route_' . $index] = [
				'path' => '/tenant/{tenant}/catalog/' . $index . '/{slug}',
				'controller' => 'App\\Controller\\CatalogController::show' . ($index % 8),
				'methods' => $index % 3 === 0 ? ['GET', 'HEAD'] : ['GET'],
				'variables' => ['tenant', 'slug'],
				'defaults' => [
					'_locale' => $index % 2 === 0 ? 'ja' : 'en',
					'_format' => 'html',
				],
				'requirements' => [
					'tenant' => '[a-z0-9_-]+',
					'slug' => '[a-z0-9-]+',
				],
				'score' => 1000 + $index,
			];
		}

		return $routes;
	}

	private static function buildUrlGenerators(): array
	{
		$generators = [];
		for ($index = 0; $index < 64; $index++) {
			$generators['route_' . $index] = [
				'prefix' => '/tenant/' . ($index % 12),
				'tokens' => ['text', 'variable', 'separator', 'variable'],
				'host' => 'example' . ($index % 4) . '.test',
			];
		}

		return $generators;
	}

	private static function buildServiceMetadata(): array
	{
		$services = [];
		for ($index = 0; $index < 96; $index++) {
			$services['service.' . $index] = [
				'class' => 'App\\Service\\GeneratedService' . $index,
				'factory' => $index % 5 === 0 ? ['container', 'make'] : null,
				'tags' => ['cache.warmable', 'tenant.' . ($index % 8)],
				'arguments' => ['@logger', '%kernel.project_dir%', $index],
			];
		}

		return $services;
	}

	private static function buildConfigEntries(): array
	{
		$entries = [];
		for ($index = 0; $index < self::MULTI_KEY_CONFIG_COUNT; $index++) {
			$entries['config_' . $index] = [
				'name' => 'config_' . $index,
				'tenant' => 'tenant-' . ($index % 8),
				'features' => [
					'search' => $index % 2 === 0,
					'recommendations' => $index % 3 === 0,
					'checkout' => true,
				],
				'limits' => [
					'items' => 100 + $index,
					'burst' => 10 + ($index % 5),
				],
				'checksum_seed' => str_repeat((string) ($index % 10), 48),
			];
		}

		return $entries;
	}

	private static function buildNestedArrayNodes(int $revision): array
	{
		$nodes = [];
		for ($index = 0; $index < 24; $index++) {
			$children = [];
			for ($childIndex = 0; $childIndex < 4; $childIndex++) {
				$children[] = [
					'label' => 'node-' . $index . '-child-' . $childIndex,
					'enabled' => $childIndex % 2 === 0,
				];
			}

			$nodes[] = [
				'label' => 'node-' . $index,
				'revision' => $revision,
				'children' => $children,
			];
		}

		return $nodes;
	}

	public static function payloadProbe(string $payloadKind, mixed $payload, int $operationIndex): int
	{
		return match ($payloadKind) {
			'constant_array' => self::constantArrayProbe($payload, $operationIndex),
			'route_table_read' => self::routeTableProbe($payload, $operationIndex),
			'large_array' => self::largeArrayProbe($payload, $operationIndex),
			'large_object_graph' => self::largeObjectProbe($payload, $operationIndex),
			'metadata_object_read', 'metadata_object_fetch_mutate' => self::metadataObjectProbe($payload, $operationIndex),
			'multi_key_config_read' => self::multiKeyConfigProbe($payload, $operationIndex),
			'safe_direct_object' => self::safeDirectProbe($payload, $operationIndex),
			'spl_collection_object' => self::splCollectionProbe($payload, $operationIndex),
			'carbon_datetime_object' => self::carbonDateTimeProbe($payload, $operationIndex),
			'serialized_cycle_object', 'cycle_assignment_object' => self::cycleProbe($payload, $operationIndex),
			'reference_assignment_object' => self::referenceProbe($payload, $operationIndex),
			'nested_array_assignment' => self::nestedArrayProbe($payload, $operationIndex),
			default => throw new InvalidArgumentException('Unknown payload kind: ' . $payloadKind),
		};
	}

	private static function constantArrayDigest(mixed $payload): string
	{
		if (!is_array($payload)) {
			throw new RuntimeException('Constant array payload has an unexpected type');
		}

		$route = $payload['routes']['/catalog/books/6'] ?? null;
		if (!is_array($route)) {
			throw new RuntimeException('Constant array payload is incomplete');
		}

		return $route['controller'] . ':' . $route['score'] . ':' . (int) $route['flags'][2] . ':' . $payload['weights'][7];
	}

	private static function constantArrayProbe(mixed $payload, int $operationIndex): int
	{
		if (!is_array($payload)) {
			throw new RuntimeException('Constant array payload has an unexpected type');
		}

		$routeIndex = $operationIndex % 8;
		$route = $payload['routes']['/catalog/books/' . $routeIndex] ?? null;
		if (!is_array($route)) {
			throw new RuntimeException('Constant array payload is incomplete');
		}

		return $route['score'] + $payload['weights'][$routeIndex];
	}

	private static function routeTableDigest(mixed $payload): string
	{
		if (!is_array($payload) || !isset($payload['routes']['route_257'])) {
			throw new RuntimeException('Route table payload has an unexpected type');
		}

		$route = $payload['routes']['route_257'];
		$generator = $payload['generators']['route_17'] ?? null;
		if (!is_array($route) || !is_array($generator)) {
			throw new RuntimeException('Route table payload is incomplete');
		}

		return $payload['name'] . ':' . $route['controller'] . ':' . $route['score'] . ':' . $generator['host'];
	}

	private static function routeTableProbe(mixed $payload, int $operationIndex): int
	{
		if (!is_array($payload) || !isset($payload['routes'])) {
			throw new RuntimeException('Route table payload has an unexpected type');
		}

		$routeIndex = ($operationIndex * 17) % (self::LARGE_ROW_COUNT * 2);
		$route = $payload['routes']['route_' . $routeIndex] ?? null;
		if (!is_array($route)) {
			throw new RuntimeException('Route table payload is incomplete');
		}

		return $route['score'] + strlen($route['controller']) + count($route['methods']);
	}

	private static function largeArrayDigest(mixed $payload): string
	{
		if (!is_array($payload)) {
			throw new RuntimeException('Large array payload has an unexpected type');
		}

		$row = $payload['rows'][96] ?? null;
		if (!is_array($row)) {
			throw new RuntimeException('Large array payload is incomplete');
		}

		return $payload['name'] . ':' . $row['path'] . ':' . $row['title'] . ':' . $row['weights'][3] . ':' . $row['metadata']['tenant'];
	}

	private static function largeArrayProbe(mixed $payload, int $operationIndex): int
	{
		if (!is_array($payload)) {
			throw new RuntimeException('Large array payload has an unexpected type');
		}

		$row = $payload['rows'][($operationIndex * 13) % self::LARGE_ROW_COUNT] ?? null;
		if (!is_array($row)) {
			throw new RuntimeException('Large array payload is incomplete');
		}

		return $row['id'] + $row['weights'][3] + strlen($row['metadata']['tenant']);
	}

	private static function largeObjectDigest(mixed $payload): string
	{
		if (!$payload instanceof BenchmarkLargeObjectPayload) {
			throw new RuntimeException('Large object payload has an unexpected type');
		}

		$row = $payload->rows[96] ?? null;
		if (!is_array($row)) {
			throw new RuntimeException('Large object payload is incomplete');
		}

		return $payload->name . ':' . $payload->child->label . ':' . $payload->child->revision
			. ':' . $row['path'] . ':' . $row['weights'][3] . ':' . $row['metadata']['tenant'];
	}

	private static function largeObjectProbe(mixed $payload, int $operationIndex): int
	{
		if (!$payload instanceof BenchmarkLargeObjectPayload) {
			throw new RuntimeException('Large object payload has an unexpected type');
		}

		$row = $payload->rows[($operationIndex * 19) % self::LARGE_ROW_COUNT] ?? null;
		if (!is_array($row)) {
			throw new RuntimeException('Large object payload is incomplete');
		}

		return $payload->child->revision + $row['weights'][2] + strlen($row['title']);
	}

	private static function metadataObjectDigest(mixed $payload): string
	{
		if (!$payload instanceof BenchmarkMetadataPayload) {
			throw new RuntimeException('Metadata payload has an unexpected type');
		}

		$route = $payload->routes['route_257'] ?? null;
		$service = $payload->services['service.41'] ?? null;
		if (!is_array($route) || !is_array($service)) {
			throw new RuntimeException('Metadata payload is incomplete');
		}

		return $payload->name . ':' . $payload->owner->revision . ':' . $route['score'] . ':' . $service['class'];
	}

	private static function metadataObjectProbe(mixed $payload, int $operationIndex): int
	{
		if (!$payload instanceof BenchmarkMetadataPayload) {
			throw new RuntimeException('Metadata payload has an unexpected type');
		}

		$routeIndex = ($operationIndex * 23) % (self::LARGE_ROW_COUNT * 2);
		$serviceIndex = ($operationIndex * 7) % 96;
		$route = $payload->routes['route_' . $routeIndex] ?? null;
		$service = $payload->services['service.' . $serviceIndex] ?? null;
		if (!is_array($route) || !is_array($service)) {
			throw new RuntimeException('Metadata payload is incomplete');
		}

		return $payload->owner->revision + $route['score'] + count($service['tags']) + strlen($service['class']);
	}

	private static function multiKeyConfigDigest(mixed $payload): string
	{
		if (!is_array($payload) || !isset($payload['entries']['config_17'])) {
			throw new RuntimeException('Multi-key config payload has an unexpected type');
		}

		$entry = $payload['entries']['config_17'];
		return $entry['name'] . ':' . $entry['tenant'] . ':' . (int) $entry['features']['recommendations'] . ':' . $entry['limits']['items'];
	}

	private static function multiKeyConfigProbe(mixed $payload, int $operationIndex): int
	{
		if (is_array($payload) && isset($payload['entries'])) {
			$entry = $payload['entries']['config_' . ($operationIndex % self::MULTI_KEY_CONFIG_COUNT)] ?? null;
		} else {
			$entry = $payload;
		}

		if (!is_array($entry) || !isset($entry['limits'])) {
			throw new RuntimeException('Multi-key config payload is incomplete');
		}

		return $entry['limits']['items'] + $entry['limits']['burst'] + strlen($entry['tenant']);
	}

	private static function safeDirectDigest(mixed $payload): string
	{
		if (!$payload instanceof BenchmarkFastPathPayload) {
			throw new RuntimeException('SafeDirect payload has an unexpected type');
		}

		return $payload->createdAt->format('Y-m-d H:i:s.u e')
			. ':' . $payload->timezone->getName()
			. ':' . $payload->expiresAt->format('Y-m-d H:i:s.u e')
			. ':' . $payload->gracePeriod->format('%a')
			. ':' . $payload->gracePeriod->format('%H:%I:%S');
	}

	private static function safeDirectProbe(mixed $payload, int $operationIndex): int
	{
		if (!$payload instanceof BenchmarkFastPathPayload) {
			throw new RuntimeException('SafeDirect payload has an unexpected type');
		}

		return ((int) $payload->createdAt->format('U'))
			+ ((int) $payload->expiresAt->format('U'))
			+ ((int) $payload->gracePeriod->format('%a'))
			+ ((int) $payload->gracePeriod->format('%H'))
			+ ($operationIndex % 4);
	}

	private static function splCollectionDigest(mixed $payload): string
	{
		if (!$payload instanceof BenchmarkSplCollectionPayload) {
			throw new RuntimeException('SPL collection payload has an unexpected type');
		}

		$fixed = $payload['fixed'] ?? null;
		$map = $payload['map'] ?? null;
		$iterator = $payload['iterator'] ?? null;
		$recursive = $payload['recursive'] ?? null;
		if (!$fixed instanceof SplFixedArray
				|| !$map instanceof ArrayObject
				|| !$iterator instanceof ArrayIterator
				|| !$recursive instanceof RecursiveArrayIterator) {
			throw new RuntimeException('SPL collection payload is incomplete');
		}

		$fixedRow = $fixed[17];
		$mapRow = $map['row_31'];
		$iteratorRow = $iterator['row_43'];
		$branch = $recursive['branch'];
		if (!is_array($fixedRow) || !is_array($mapRow) || !is_array($iteratorRow) || !is_array($branch)) {
			throw new RuntimeException('SPL collection payload rows are incomplete');
		}

		return $payload->name . ':' . $payload->revision
			. ':' . $fixed->getSize()
			. ':' . $fixedRow['label']
			. ':' . $mapRow['score']
			. ':' . $iteratorRow['label']
			. ':' . $branch['leaf_31']['score'];
	}

	private static function splCollectionProbe(mixed $payload, int $operationIndex): int
	{
		if (!$payload instanceof BenchmarkSplCollectionPayload) {
			throw new RuntimeException('SPL collection payload has an unexpected type');
		}

		$fixed = $payload['fixed'] ?? null;
		$map = $payload['map'] ?? null;
		$iterator = $payload['iterator'] ?? null;
		if (!$fixed instanceof SplFixedArray
				|| !$map instanceof ArrayObject
				|| !$iterator instanceof ArrayIterator) {
			throw new RuntimeException('SPL collection payload is incomplete');
		}

		$index = ($operationIndex * 5) % self::SPL_COLLECTION_COUNT;
		$fixedRow = $fixed[$index];
		$mapRow = $map['row_' . (($operationIndex * 7) % self::SPL_COLLECTION_COUNT)];
		$iteratorRow = $iterator['row_' . (($operationIndex * 11) % self::SPL_COLLECTION_COUNT)];
		if (!is_array($fixedRow) || !is_array($mapRow) || !is_array($iteratorRow)) {
			throw new RuntimeException('SPL collection payload rows are incomplete');
		}

		return $fixedRow['score'] + $mapRow['score'] + $iteratorRow['score'] + $payload->revision;
	}

	private static function carbonDateTimeDigest(mixed $payload): string
	{
		if (!$payload instanceof BenchmarkCarbonDateTimePayload) {
			throw new RuntimeException('Carbon DateTime payload has an unexpected type');
		}

		$middle = $payload->timeline[intdiv(count($payload->timeline), 2)] ?? null;
		$last = $payload->timeline[count($payload->timeline) - 1] ?? null;
		if (!is_array($middle) || !is_array($last)) {
			throw new RuntimeException('Carbon DateTime timeline payload is incomplete');
		}

		return $payload->createdAt->format('Y-m-d H:i:s.u e')
			. ':' . $payload->updatedAt->format('Y-m-d H:i:s.u e')
			. ':' . $payload->timezone->getName()
			. ':' . count($payload->timeline)
			. ':' . $middle['created']->format('Y-m-d H:i:s.u e')
			. ':' . $last['updated']->format('Y-m-d H:i:s.u e')
			. ':' . $last['timezone']->getName();
	}

	private static function carbonDateTimeProbe(mixed $payload, int $operationIndex): int
	{
		if (!$payload instanceof BenchmarkCarbonDateTimePayload) {
			throw new RuntimeException('Carbon DateTime payload has an unexpected type');
		}

		$entry = $payload->timeline[$operationIndex % self::CARBON_TIMELINE_COUNT] ?? null;
		if (!is_array($entry)) {
			throw new RuntimeException('Carbon DateTime timeline payload is incomplete');
		}

		return ((int) $payload->createdAt->format('U'))
			+ ((int) $payload->updatedAt->format('U'))
			+ strlen($payload->timezone->getName())
			+ ((int) $entry['created']->format('U'))
			+ ((int) $entry['updated']->format('U'))
			+ strlen($entry['timezone']->getName())
			+ ($operationIndex % 4);
	}

	private static function referenceDigest(mixed $payload): string
	{
		if (!$payload instanceof BenchmarkReferencePayload) {
			throw new RuntimeException('Reference payload has an unexpected type');
		}

		return $payload->name . ':' . $payload->child->label . ':' . $payload->child->revision;
	}

	private static function referenceProbe(mixed $payload, int $operationIndex): int
	{
		if (!$payload instanceof BenchmarkReferencePayload) {
			throw new RuntimeException('Reference payload has an unexpected type');
		}

		return $payload->child->revision + strlen($payload->name) + ($operationIndex % 11);
	}

	private static function cycleDigest(mixed $payload): string
	{
		if (!$payload instanceof BenchmarkCyclePayload
				|| !$payload->peer instanceof BenchmarkCyclePayload
				|| $payload->peer->peer !== $payload) {
			throw new RuntimeException('Cyclic payload is incomplete');
		}

		return $payload->name . ':' . $payload->revision . ':' . $payload->peer->name . ':' . $payload->peer->revision;
	}

	private static function cycleProbe(mixed $payload, int $operationIndex): int
	{
		if (!$payload instanceof BenchmarkCyclePayload || !$payload->peer instanceof BenchmarkCyclePayload) {
			throw new RuntimeException('Cyclic payload is incomplete');
		}

		return $payload->revision + $payload->peer->revision + strlen($payload->peer->name) + ($operationIndex % 13);
	}

	private static function serializedCycleDigest(mixed $payload): string
	{
		if (!$payload instanceof BenchmarkCyclePayload
				|| !$payload->peer instanceof BenchmarkCyclePayload
				|| !$payload->peer->peer instanceof BenchmarkCyclePayload) {
			throw new RuntimeException('Serialized cyclic payload is incomplete');
		}

		return $payload->name . ':' . $payload->revision
			. ':' . $payload->peer->name . ':' . $payload->peer->revision
			. ':' . $payload->peer->peer->name . ':' . $payload->peer->peer->revision;
	}

	private static function nestedArrayDigest(mixed $payload): string
	{
		if (!is_array($payload) || !isset($payload['nodes'][7]) || !is_array($payload['nodes'][7])) {
			throw new RuntimeException('Nested array payload has an unexpected type');
		}

		$node = $payload['nodes'][7];
		return $payload['name'] . ':' . $node['label'] . ':' . $node['revision'] . ':' . (int) $node['children'][2]['enabled'];
	}

	private static function nestedArrayProbe(mixed $payload, int $operationIndex): int
	{
		if (!is_array($payload) || !isset($payload['nodes'])) {
			throw new RuntimeException('Nested array payload has an unexpected type');
		}

		$node = $payload['nodes'][$operationIndex % 24] ?? null;
		if (!is_array($node)) {
			throw new RuntimeException('Nested array payload is incomplete');
		}

		return $node['revision'] + strlen($node['label']) + (int) $node['children'][$operationIndex % 4]['enabled'];
	}

	private function describe(): array
	{
		$backends = [];
		if ($this->isApcuReady()) {
			$backends[] = 'apcu';
		}
		$volatileCacheReady = $this->isVolatileStaticCacheReady();
		$persistentCacheReady = $this->isPersistentStaticCacheReady();
		if ($volatileCacheReady) {
			$backends[] = 'volatile_cache';
		}
		if ($persistentCacheReady) {
			$backends[] = 'persistent_cache';
		}
		if ($volatileCacheReady) {
			$backends[] = 'volatile_static_immediate_class';
			$backends[] = 'volatile_static_immediate_property';
			$backends[] = 'volatile_static_immediate_method';
			$backends[] = 'volatile_static_tracking_class';
			$backends[] = 'volatile_static_tracking_property';
			$backends[] = 'volatile_static_tracking_method';
		}
		if ($persistentCacheReady) {
			$backends[] = 'persistent_static_class';
			$backends[] = 'persistent_static_property';
			$backends[] = 'persistent_static_method';
		}

		$cases = [];
		foreach (self::PAYLOAD_LABELS as $payloadKind => $_label) {
			$cases[$payloadKind] = self::supportedBackendsForPayload($payloadKind, $backends);
		}

		return [
			'ok' => true,
			'php_sapi' => PHP_SAPI,
			'php_version' => PHP_VERSION,
			'architecture' => php_uname('m'),
			'pid' => getmypid(),
			'volatile_cache' => $this->volatileCacheStatus(),
			'persistent_cache' => $this->persistentCacheStatus(),
			'apcu' => $this->apcuStatus(),
			'jit' => $this->jitStatus(),
			'payload_labels' => self::PAYLOAD_LABELS,
			'backend_labels' => self::BACKEND_LABELS,
			'cases' => $cases,
		];
	}

	private function resetState(?string $case = null, ?string $backend = null): array
	{
		if ($case !== null || $backend !== null) {
			if ($case === null || $backend === null) {
				throw new InvalidArgumentException('reset requires both case and backend when either is provided');
			}

			$this->resetScenario($case, $backend);
		} else {
			$this->resetBestEffort();
		}

		return [
			'ok' => true,
			'volatile_cache' => $this->volatileCacheStatus(),
			'persistent_cache' => $this->persistentCacheStatus(),
			'apcu' => $this->apcuStatus(),
		];
	}

	private function resetScenario(string $case, string $backend): void
	{
		$this->assertKnownCaseAndBackend($case, $backend);

		switch ($backend) {
			case 'apcu':
				if ($this->hasApcuApi()) {
					apcu_clear_cache();
				}
				return;

			case 'volatile_cache':
				$this->assertVolatileStaticCacheAvailable();
				OPcache\volatile_clear();
				return;

			case 'persistent_cache':
				if ($this->hasPersistentStaticCacheApi()) {
					OPcache\persistent_clear();
				}
				return;

			case 'volatile_static_immediate_class':
			case 'volatile_static_immediate_property':
			case 'volatile_static_immediate_method':
			case 'volatile_static_tracking_class':
			case 'volatile_static_tracking_property':
			case 'volatile_static_tracking_method':
			case 'persistent_static_class':
			case 'persistent_static_property':
			case 'persistent_static_method':
				if ($this->hasVolatileStaticCacheApi() && $this->isVolatileStaticCacheReady()) {
					OPcache\volatile_clear();
				}
				if ($this->hasPersistentStaticCacheApi() && $this->isPersistentStaticCacheReady()) {
					OPcache\persistent_clear();
				}
				if (function_exists('opcache_reset')) {
					opcache_reset();
				}
				return;
		}
	}

	private function resetBestEffort(): void
	{
		if ($this->hasApcuApi()) {
			apcu_clear_cache();
		}

		if ($this->hasVolatileStaticCacheApi() && $this->isVolatileStaticCacheReady()) {
			OPcache\volatile_clear();
		}

		if ($this->hasPersistentStaticCacheApi() && $this->isPersistentStaticCacheReady()) {
			OPcache\persistent_clear();
		}
	}

	private function prime(string $case, string $backend): array
	{
		$this->assertKnownCaseAndBackend($case, $backend);

		$result = match ($backend) {
			'apcu' => $this->storeApcuPayload($case),
			'volatile_cache' => $this->storeVolatileStaticCachePayload($case),
			'persistent_cache' => $this->storePersistentStaticCachePayload($case),
			default => $this->runScenario($case, $backend, 1),
		};

		return [
			'ok' => true,
			'case' => $case,
			'backend' => $backend,
			'operation_count' => 1,
			'cache_hit' => $result['cache_hit'],
			'cache_hit_count' => $result['cache_hit_count'],
			'build_count' => $result['build_count'],
			'checksum' => $result['checksum'],
		];
	}

	private function measure(string $case, string $backend, int $operations = 1, bool $readOnly = false): array
	{
		$this->assertKnownCaseAndBackend($case, $backend);

		if (function_exists('memory_reset_peak_usage')) {
			memory_reset_peak_usage();
		}

		$memoryBefore = memory_get_usage(true);
		$peakBefore = memory_get_peak_usage(true);
		$start = hrtime(true);
		$result = $this->runScenario($case, $backend, $operations, $readOnly);
		$elapsedMicroseconds = (int) ((hrtime(true) - $start) / 1000);
		$memoryAfter = memory_get_usage(true);
		$peakAfter = memory_get_peak_usage(true);

		return [
			'ok' => true,
			'case' => $case,
			'backend' => $backend,
			'operation_count' => $operations,
			'worker_us' => $elapsedMicroseconds,
			'memory_delta' => $memoryAfter - $memoryBefore,
			'peak_delta' => max(0, $peakAfter - max($memoryBefore, $peakBefore)),
			'cache_hit' => $result['cache_hit'],
			'cache_hit_count' => $result['cache_hit_count'],
			'build_count' => $result['build_count'],
			'checksum' => $result['checksum'],
			'read_score' => $result['read_score'],
		];
	}

	private function measureWrite(string $case, string $backend, int $operations, string $keyMode, string $writeMode, int $workerId, int $keySpace): array
	{
		$this->assertKnownCaseAndBackend($case, $backend);

		if (function_exists('memory_reset_peak_usage')) {
			memory_reset_peak_usage();
		}

		$memoryBefore = memory_get_usage(true);
		$peakBefore = memory_get_peak_usage(true);
		$start = hrtime(true);
		$result = $this->runExplicitWriteScenario($case, $backend, $operations, $keyMode, $writeMode, $workerId, $keySpace);
		$elapsedMicroseconds = (int) ((hrtime(true) - $start) / 1000);
		$memoryAfter = memory_get_usage(true);
		$peakAfter = memory_get_peak_usage(true);

		return [
			'ok' => true,
			'case' => $case,
			'backend' => $backend,
			'key_mode' => $keyMode,
			'write_mode' => $writeMode,
			'worker_id' => $workerId,
			'key_space' => $keySpace,
			'operation_count' => $operations,
			'worker_us' => $elapsedMicroseconds,
			'memory_delta' => $memoryAfter - $memoryBefore,
			'peak_delta' => max(0, $peakAfter - max($memoryBefore, $peakBefore)),
			'store_count' => $result['store_count'],
			'build_count' => $result['build_count'],
			'checksum' => $result['checksum'],
			'write_score' => $result['write_score'],
		];
	}

	private function verify(string $case, string $backend): array
	{
		$this->assertKnownCaseAndBackend($case, $backend);

		try {
			$result = match ($backend) {
				'apcu' => $this->peekExplicitPayload('apcu', $case),
				'volatile_cache' => $this->peekExplicitPayload('volatile', $case),
				'persistent_cache' => $this->peekExplicitPayload('persistent', $case),
				'volatile_static_immediate_class' => BenchmarkVolatileStaticImmediateClassCache::resolve($case),
				'volatile_static_immediate_property' => BenchmarkVolatileStaticImmediatePropertyCache::resolve($case),
				'volatile_static_immediate_method' => BenchmarkVolatileStaticImmediateMethodCache::resolve($case),
				'volatile_static_tracking_class' => BenchmarkVolatileStaticTrackingClassCache::resolve($case),
				'volatile_static_tracking_property' => BenchmarkVolatileStaticTrackingPropertyCache::resolve($case),
				'volatile_static_tracking_method' => BenchmarkVolatileStaticTrackingMethodCache::resolve($case),
				'persistent_static_class' => BenchmarkPersistentStaticClassCache::resolve($case),
				'persistent_static_property' => BenchmarkPersistentStaticPropertyCache::resolve($case),
				'persistent_static_method' => BenchmarkPersistentStaticMethodCache::resolve($case),
			};

			if (is_array($result) && array_key_exists('payload', $result)) {
				if (!$result['cache_hit']) {
					return $this->destroyedRetentionResult($case, $backend, 'Cached attribute payload had to be rebuilt during verification', '', self::expectedDigest($case, $this->backendStoresMutatedPayload($case, $backend)));
				}
				$payload = $result['payload'];
			} else {
				$payload = $result;
			}

			$expectedChecksum = self::expectedDigest($case, $this->backendStoresMutatedPayload($case, $backend));
			$checksum = self::payloadDigest($case, $payload);
			if ($checksum !== $expectedChecksum) {
				return $this->destroyedRetentionResult($case, $backend, 'Payload checksum mismatch', $checksum, $expectedChecksum);
			}

			return $this->retainedRetentionResult($case, $backend, $checksum, $expectedChecksum);
		} catch (Throwable $throwable) {
			return $this->destroyedRetentionResult($case, $backend, $throwable->getMessage(), '', self::expectedDigest($case, $this->backendStoresMutatedPayload($case, $backend)));
		}
	}

	private function verifyWrite(string $case, string $backend, string $keyMode, string $writeMode, int $workerId, int $keySpace): array
	{
		$this->assertKnownCaseAndBackend($case, $backend);

		try {
			$payload = $this->peekWritePayload($case, $backend, $keyMode, $writeMode, $workerId, $keySpace);
			if ($payload === null) {
				return $this->destroyedRetentionResult(
					$case,
					$backend,
					'Write benchmark payload is missing',
					'',
					self::expectedDigest($case, false),
				);
			}

			$expectedChecksum = self::expectedDigest($case, false);
			$checksum = self::payloadDigest($case, $payload);
			if ($checksum !== $expectedChecksum) {
				return $this->destroyedRetentionResult($case, $backend, 'Payload checksum mismatch', $checksum, $expectedChecksum);
			}

			return $this->retainedRetentionResult($case, $backend, $checksum, $expectedChecksum);
		} catch (Throwable $throwable) {
			return $this->destroyedRetentionResult($case, $backend, $throwable->getMessage(), '', self::expectedDigest($case, false));
		}
	}

	private function runScenario(string $case, string $backend, int $operations, bool $readOnly = false): array
	{
		return match ($backend) {
			'apcu' => $this->runApcu($case, $operations, $readOnly),
			'volatile_cache' => $this->runVolatileStaticCache($case, $operations, $readOnly),
			'persistent_cache' => $this->runPersistentStaticCache($case, $operations, $readOnly),
			default => $this->runAttributeBackend($case, $backend, $operations, $readOnly),
		};
	}

	private function storeApcuPayload(string $payloadKind): array
	{
		$this->assertApcuAvailable();

		$payload = self::buildPayload($payloadKind);
		if (self::isMultiKeyPayload($payloadKind)) {
			foreach ($payload['entries'] as $entryName => $entry) {
				if (!apcu_store($this->cacheKey('apcu', $payloadKind, $entryName), $entry)) {
					throw new RuntimeException('apcu_store() failed');
				}
			}
		} else {
			if (!apcu_store($this->cacheKey('apcu', $payloadKind), $payload)) {
				throw new RuntimeException('apcu_store() failed');
			}
		}

		$checksum = self::payloadDigest($payloadKind, $payload);
		$this->assertChecksum($payloadKind, 'apcu', $checksum);

		return $this->cacheResult(1, 0, 1, $checksum, self::payloadProbe($payloadKind, $payload, 0));
	}

	private function storeVolatileStaticCachePayload(string $payloadKind): array
	{
		$this->assertVolatileStaticCacheAvailable();

		$payload = self::buildPayload($payloadKind);
		if (self::isMultiKeyPayload($payloadKind)) {
			$values = [];
			foreach ($payload['entries'] as $entryName => $entry) {
				$values[$this->cacheKey('volatile', $payloadKind, $entryName)] = $entry;
			}
			if (!OPcache\volatile_store_array($values)) {
				throw new RuntimeException('OPcache\\volatile_store_array() failed');
			}
		} else {
			if (!OPcache\volatile_store($this->cacheKey('volatile', $payloadKind), $payload)) {
				throw new RuntimeException('OPcache\\volatile_store() failed');
			}
		}

		$checksum = self::payloadDigest($payloadKind, $payload);
		$this->assertChecksum($payloadKind, 'volatile_cache', $checksum);

		return $this->cacheResult(1, 0, 1, $checksum, self::payloadProbe($payloadKind, $payload, 0));
	}

	private function storePersistentStaticCachePayload(string $payloadKind): array
	{
		$this->assertPersistentStaticCacheAvailable();

		$payload = self::buildPayload($payloadKind);
		if (self::isMultiKeyPayload($payloadKind)) {
			$values = [];
			foreach ($payload['entries'] as $entryName => $entry) {
				$values[$this->cacheKey('persistent', $payloadKind, $entryName)] = $entry;
			}
			OPcache\persistent_store_array($values);
		} else {
			OPcache\persistent_store($this->cacheKey('persistent', $payloadKind), $payload);
		}

		$checksum = self::payloadDigest($payloadKind, $payload);
		$this->assertChecksum($payloadKind, 'persistent_cache', $checksum);

		return $this->cacheResult(1, 0, 1, $checksum, self::payloadProbe($payloadKind, $payload, 0));
	}

	private function runApcu(string $payloadKind, int $operations, bool $readOnly): array
	{
		$this->assertApcuAvailable();
		if (self::isMultiKeyPayload($payloadKind)) {
			return $this->runApcuMultiKey($payloadKind, $operations, $readOnly);
		}

		$key = $this->cacheKey('apcu', $payloadKind);
		$cacheHitCount = 0;
		$buildCount = 0;
		$payload = null;
		$readScore = 0;

		for ($operationIndex = 0; $operationIndex < $operations; $operationIndex++) {
			$success = false;
			$payload = apcu_fetch($key, $success);
			if ($success) {
				$cacheHitCount++;
				$readScore += self::payloadProbe($payloadKind, $payload, $operationIndex);
				self::mutateFetchedPayload($payloadKind, $payload, $operationIndex);
				continue;
			}

			$this->assertReadOnlyHit($payloadKind, 'apcu', $readOnly);

			$payload = self::buildPayload($payloadKind);
			if (!apcu_store($key, $payload)) {
				throw new RuntimeException('apcu_store() failed');
			}
			$buildCount++;
			$readScore += self::payloadProbe($payloadKind, $payload, $operationIndex);
			self::mutateFetchedPayload($payloadKind, $payload, $operationIndex);
			continue;
		}
		$payload = $this->explicitPayloadForChecksum('apcu', $payloadKind, $payload);
		$checksum = self::payloadDigest($payloadKind, $payload);
		$this->assertChecksum($payloadKind, 'apcu', $checksum);

		return $this->cacheResult($operations, $cacheHitCount, $buildCount, $checksum, $readScore);
	}

	private function runVolatileStaticCache(string $payloadKind, int $operations, bool $readOnly): array
	{
		$this->assertVolatileStaticCacheAvailable();
		if (self::isMultiKeyPayload($payloadKind)) {
			return $this->runVolatileStaticCacheMultiKey($payloadKind, $operations, $readOnly);
		}

		$key = $this->cacheKey('volatile', $payloadKind);
		$cacheHitCount = 0;
		$buildCount = 0;
		$payload = null;
		$missing = new stdClass();
		$readScore = 0;

		for ($operationIndex = 0; $operationIndex < $operations; $operationIndex++) {
			$payload = OPcache\volatile_fetch($key, $missing);
			if ($payload !== $missing) {
				$cacheHitCount++;
				$readScore += self::payloadProbe($payloadKind, $payload, $operationIndex);
				self::mutateFetchedPayload($payloadKind, $payload, $operationIndex);
				continue;
			}

			$this->assertReadOnlyHit($payloadKind, 'volatile_cache', $readOnly);

			$payload = self::buildPayload($payloadKind);
			if (!OPcache\volatile_store($key, $payload)) {
				throw new RuntimeException('OPcache\\volatile_store() failed');
			}
			$buildCount++;
			$readScore += self::payloadProbe($payloadKind, $payload, $operationIndex);
			self::mutateFetchedPayload($payloadKind, $payload, $operationIndex);
		}

		$payload = $this->explicitPayloadForChecksum('volatile', $payloadKind, $payload);
		$checksum = self::payloadDigest($payloadKind, $payload);
		$this->assertChecksum($payloadKind, 'volatile_cache', $checksum);

		return $this->cacheResult($operations, $cacheHitCount, $buildCount, $checksum, $readScore);
	}

	private function runPersistentStaticCache(string $payloadKind, int $operations, bool $readOnly): array
	{
		$this->assertPersistentStaticCacheAvailable();
		if (self::isMultiKeyPayload($payloadKind)) {
			return $this->runPersistentStaticCacheMultiKey($payloadKind, $operations, $readOnly);
		}

		$key = $this->cacheKey('persistent', $payloadKind);
		$cacheHitCount = 0;
		$buildCount = 0;
		$payload = null;
		$missing = new stdClass();
		$readScore = 0;

		for ($operationIndex = 0; $operationIndex < $operations; $operationIndex++) {
			$payload = OPcache\persistent_fetch($key, $missing);
			if ($payload !== $missing) {
				$cacheHitCount++;
				$readScore += self::payloadProbe($payloadKind, $payload, $operationIndex);
				self::mutateFetchedPayload($payloadKind, $payload, $operationIndex);
				continue;
			}

			$this->assertReadOnlyHit($payloadKind, 'persistent_cache', $readOnly);

			$payload = self::buildPayload($payloadKind);
			OPcache\persistent_store($key, $payload);
			$buildCount++;
			$readScore += self::payloadProbe($payloadKind, $payload, $operationIndex);
			self::mutateFetchedPayload($payloadKind, $payload, $operationIndex);
		}

		$payload = $this->explicitPayloadForChecksum('persistent', $payloadKind, $payload);
		$checksum = self::payloadDigest($payloadKind, $payload);
		$this->assertChecksum($payloadKind, 'persistent_cache', $checksum);

		return $this->cacheResult($operations, $cacheHitCount, $buildCount, $checksum, $readScore);
	}

	private function runApcuMultiKey(string $payloadKind, int $operations, bool $readOnly): array
	{
		$entryNames = self::multiKeyEntryNames();
		$cacheHitCount = 0;
		$buildCount = 0;
		$readScore = 0;

		for ($operationIndex = 0; $operationIndex < $operations; $operationIndex++) {
			$entryName = $entryNames[$operationIndex % count($entryNames)];
			$success = false;
			$entry = apcu_fetch($this->cacheKey('apcu', $payloadKind, $entryName), $success);
			if ($success) {
				$cacheHitCount++;
				$readScore += self::payloadProbe($payloadKind, $entry, $operationIndex);
				continue;
			}

			$this->assertReadOnlyHit($payloadKind, 'apcu', $readOnly);
			$payload = self::buildPayload($payloadKind);
			foreach ($payload['entries'] as $storeEntryName => $storeEntry) {
				if (!apcu_store($this->cacheKey('apcu', $payloadKind, $storeEntryName), $storeEntry)) {
					throw new RuntimeException('apcu_store() failed');
				}
			}
			$buildCount++;
			$readScore += self::payloadProbe($payloadKind, $payload['entries'][$entryName], $operationIndex);
		}

		$payload = $this->peekExplicitPayload('apcu', $payloadKind);
		$checksum = self::payloadDigest($payloadKind, $payload);
		$this->assertChecksum($payloadKind, 'apcu', $checksum);

		return $this->cacheResult($operations, $cacheHitCount, $buildCount, $checksum, $readScore);
	}

	private function runVolatileStaticCacheMultiKey(string $payloadKind, int $operations, bool $readOnly): array
	{
		$entryNames = self::multiKeyEntryNames();
		$keys = [];
		foreach ($entryNames as $entryName) {
			$keys[$entryName] = $this->cacheKey('volatile', $payloadKind, $entryName);
		}
		$cacheHitCount = 0;
		$buildCount = 0;
		$readScore = 0;
		$missing = null;

		for ($operationIndex = 0; $operationIndex < $operations; $operationIndex++) {
			$entryName = $entryNames[$operationIndex % count($entryNames)];
			$fetched = OPcache\volatile_fetch_array(array_values($keys), $missing);
			$entry = $fetched[$keys[$entryName]] ?? $missing;
			if ($entry !== $missing) {
				$cacheHitCount++;
				$readScore += self::payloadProbe($payloadKind, $entry, $operationIndex);
				continue;
			}

			$this->assertReadOnlyHit($payloadKind, 'volatile_cache', $readOnly);
			$payload = self::buildPayload($payloadKind);
			$values = [];
			foreach ($payload['entries'] as $storeEntryName => $storeEntry) {
				$values[$keys[$storeEntryName]] = $storeEntry;
			}
			if (!OPcache\volatile_store_array($values)) {
				throw new RuntimeException('OPcache\\volatile_store_array() failed');
			}
			$buildCount++;
			$readScore += self::payloadProbe($payloadKind, $payload['entries'][$entryName], $operationIndex);
		}

		$payload = $this->peekExplicitPayload('volatile', $payloadKind);
		$checksum = self::payloadDigest($payloadKind, $payload);
		$this->assertChecksum($payloadKind, 'volatile_cache', $checksum);

		return $this->cacheResult($operations, $cacheHitCount, $buildCount, $checksum, $readScore);
	}

	private function runPersistentStaticCacheMultiKey(string $payloadKind, int $operations, bool $readOnly): array
	{
		$entryNames = self::multiKeyEntryNames();
		$keys = [];
		foreach ($entryNames as $entryName) {
			$keys[$entryName] = $this->cacheKey('persistent', $payloadKind, $entryName);
		}
		$cacheHitCount = 0;
		$buildCount = 0;
		$readScore = 0;
		$missing = null;

		for ($operationIndex = 0; $operationIndex < $operations; $operationIndex++) {
			$entryName = $entryNames[$operationIndex % count($entryNames)];
			$fetched = OPcache\persistent_fetch_array(array_values($keys), $missing);
			$entry = $fetched[$keys[$entryName]] ?? $missing;
			if ($entry !== $missing) {
				$cacheHitCount++;
				$readScore += self::payloadProbe($payloadKind, $entry, $operationIndex);
				continue;
			}

			$this->assertReadOnlyHit($payloadKind, 'persistent_cache', $readOnly);
			$payload = self::buildPayload($payloadKind);
			$values = [];
			foreach ($payload['entries'] as $storeEntryName => $storeEntry) {
				$values[$keys[$storeEntryName]] = $storeEntry;
			}
			OPcache\persistent_store_array($values);
			$buildCount++;
			$readScore += self::payloadProbe($payloadKind, $payload['entries'][$entryName], $operationIndex);
		}

		$payload = $this->peekExplicitPayload('persistent', $payloadKind);
		$checksum = self::payloadDigest($payloadKind, $payload);
		$this->assertChecksum($payloadKind, 'persistent_cache', $checksum);

		return $this->cacheResult($operations, $cacheHitCount, $buildCount, $checksum, $readScore);
	}

	private function runAttributeBackend(string $payloadKind, string $backend, int $operations, bool $readOnly): array
	{
		$cacheHitCount = 0;
		$buildCount = 0;
		$readScore = 0;
		$result = null;

		for ($operationIndex = 0; $operationIndex < $operations; $operationIndex++) {
			$result = $this->resolveAttributeBackend($payloadKind, $backend);
			if (!$result['cache_hit'] && self::isObjectAssignmentPayload($payloadKind)) {
				self::mutateAssignedPayload($payloadKind, $result['payload']);
			}
			$this->assertReadOnlyHit($payloadKind, $backend, $readOnly, $result['cache_hit']);
			$cacheHitCount += (int) $result['cache_hit'];
			$buildCount += $result['build_count'];
			$readScore += self::payloadProbe($payloadKind, $result['payload'], $operationIndex);
		}

		return $this->attributeBenchmarkResult($payloadKind, $backend, $operations, $cacheHitCount, $buildCount, $result, $readScore);
	}

	private function resolveAttributeBackend(string $payloadKind, string $backend): array
	{
		return match ($backend) {
			'volatile_static_immediate_class' => BenchmarkVolatileStaticImmediateClassCache::resolve($payloadKind),
			'volatile_static_immediate_property' => BenchmarkVolatileStaticImmediatePropertyCache::resolve($payloadKind),
			'volatile_static_immediate_method' => BenchmarkVolatileStaticImmediateMethodCache::resolve($payloadKind),
			'volatile_static_tracking_class' => BenchmarkVolatileStaticTrackingClassCache::resolve($payloadKind),
			'volatile_static_tracking_property' => BenchmarkVolatileStaticTrackingPropertyCache::resolve($payloadKind),
			'volatile_static_tracking_method' => BenchmarkVolatileStaticTrackingMethodCache::resolve($payloadKind),
			'persistent_static_class' => BenchmarkPersistentStaticClassCache::resolve($payloadKind),
			'persistent_static_property' => BenchmarkPersistentStaticPropertyCache::resolve($payloadKind),
			'persistent_static_method' => BenchmarkPersistentStaticMethodCache::resolve($payloadKind),
			default => throw new InvalidArgumentException('Unknown attribute backend: ' . $backend),
		};
	}

	private function attributeBenchmarkResult(string $payloadKind, string $backend, int $operations, int $cacheHitCount, int $buildCount, ?array $result, int $readScore = 0): array
	{
		if ($result === null) {
			throw new RuntimeException('Attribute benchmark did not execute any operations');
		}

		$checksum = self::payloadDigest($payloadKind, $result['payload']);
		$expectedMutated = self::isAssignmentPayload($payloadKind) && $buildCount > 0
			? true
			: $this->backendStoresMutatedPayload($payloadKind, $backend);
		$this->assertChecksum($payloadKind, $backend, $checksum, $expectedMutated);

		return $this->cacheResult($operations, $cacheHitCount, $buildCount, $checksum, $readScore);
	}

	private function runExplicitWriteScenario(string $payloadKind, string $backend, int $operations, string $keyMode, string $writeMode, int $workerId, int $keySpace): array
	{
		$namespace = $this->explicitWriteNamespace($payloadKind, $backend);
		$payload = $writeMode === 'store' ? self::buildPayload($payloadKind) : null;
		$operationCount = 0;
		$buildCount = 0;
		$writeScore = 0;

		for ($operationIndex = 0; $operationIndex < $operations; $operationIndex++) {
			$key = $this->writeCacheKey($namespace, $payloadKind, $keyMode, $workerId, $keySpace, $operationIndex);
			if ($writeMode === 'entry_reservation') {
				$result = $this->runEntryReservationWriteOperation($namespace, $key, $payloadKind);
				$buildCount += $result['build_count'];
				$storedPayload = $result['payload'];
			} else {
				if ($payload === null) {
					throw new RuntimeException('Store write benchmark payload was not built');
				}
				switch ($namespace) {
					case 'apcu':
						if (!apcu_store($key, $payload)) {
							throw new RuntimeException('apcu_store() failed');
						}
						break;

					case 'volatile':
						if (!OPcache\volatile_store($key, $payload)) {
							throw new RuntimeException('OPcache\\volatile_store() failed');
						}
						break;

					case 'persistent':
						OPcache\persistent_store($key, $payload);
						break;
				}

				$storedPayload = $payload;
				$buildCount = 1;
			}

			$operationCount++;
			$writeScore += self::payloadProbe($payloadKind, $storedPayload, $operationIndex);
		}

		$storedPayload = $this->peekWritePayload($payloadKind, $backend, $keyMode, $writeMode, $workerId, $keySpace);
		if ($storedPayload === null) {
			throw new RuntimeException('Stored write-benchmark payload is missing');
		}

		$checksum = self::payloadDigest($payloadKind, $storedPayload);
		$this->assertChecksum($payloadKind, $backend, $checksum, false);

		return [
			'store_count' => $operationCount,
			'build_count' => $buildCount,
			'checksum' => $checksum,
			'write_score' => $writeScore,
		];
	}

	private function runEntryReservationWriteOperation(string $namespace, string $key, string $payloadKind): array
	{
		switch ($namespace) {
			case 'apcu':
				if (!function_exists('apcu_entry')) {
					throw new RuntimeException('apcu_entry() is required for entry reservation benchmarking');
				}
				$built = false;
				$storedPayload = apcu_entry($key, static function () use ($payloadKind, &$built): mixed {
					$built = true;

					return self::buildPayload($payloadKind);
				});
				return [
					'payload' => $storedPayload,
					'build_count' => (int) $built,
				];

			case 'volatile':
				if (!OPcache\volatile_exists($key)) {
					while (!OPcache\volatile_lock($key)) {
						usleep(1000);
						if (OPcache\volatile_exists($key)) {
							break 2;
						}
					}
					$payload = self::buildPayload($payloadKind);
					if (!OPcache\volatile_store($key, $payload)) {
						throw new RuntimeException('OPcache\\volatile_store() failed');
					}
					return [
						'payload' => $payload,
						'build_count' => 1,
					];
				}
				break;

			case 'persistent':
				if (!OPcache\persistent_exists($key)) {
					while (!OPcache\persistent_lock($key)) {
						usleep(1000);
						if (OPcache\persistent_exists($key)) {
							break 2;
						}
					}
					$payload = self::buildPayload($payloadKind);
					OPcache\persistent_store($key, $payload);
					return [
						'payload' => $payload,
						'build_count' => 1,
					];
				}
				break;
		}

		$storedPayload = $this->peekExplicitPayloadKey($namespace, $key);
		if ($storedPayload === null) {
			throw new RuntimeException('Entry reservation payload is missing after existing-key check');
		}

		return [
			'payload' => $storedPayload,
			'build_count' => 0,
		];
	}

	private function assertReadOnlyHit(string $payloadKind, string $backend, bool $readOnly, bool $cacheHit = false): void
	{
		if ($readOnly && !$cacheHit) {
			throw new RuntimeException($payloadKind . ' / ' . $backend . ' was not primed before read-only measurement');
		}
	}

	private function cacheResult(int $operations, int $cacheHitCount, int $buildCount, string $checksum, int $readScore = 0): array
	{
		return [
			'cache_hit' => $cacheHitCount === $operations,
			'cache_hit_count' => $cacheHitCount,
			'build_count' => $buildCount,
			'checksum' => $checksum,
			'read_score' => $readScore,
		];
	}

	private function backendStoresMutatedPayload(string $payloadKind, string $backend): bool
	{
		if (!self::isAssignmentPayload($payloadKind)) {
			return false;
		}


		if ($payloadKind === 'nested_array_assignment') {
			return str_starts_with($backend, 'volatile_static_tracking_')
				|| str_starts_with($backend, 'persistent_static_');
		}

		if ($backend === 'volatile_static_immediate_method' || $backend === 'persistent_static_method') {
			return true;
		}

		return str_starts_with($backend, 'volatile_static_tracking_');
	}

	private function assertChecksum(string $payloadKind, string $backend, string $checksum, ?bool $expectedMutated = null): void
	{
		$expectedMutated ??= $this->backendStoresMutatedPayload($payloadKind, $backend);
		$expectedChecksum = self::expectedDigest($payloadKind, $expectedMutated);
		if ($checksum !== $expectedChecksum) {
			throw new RuntimeException(sprintf(
				'%s / %s checksum mismatch: got %s, expected %s',
				$payloadKind,
				$backend,
				$checksum,
				$expectedChecksum,
			));
		}
	}

	private function cacheKey(string $namespace, string $payloadKind, ?string $entryName = null): string
	{
		$key = 'opcache_static_cache_benchmark:' . self::VERSION . ':' . $namespace . ':' . $payloadKind;
		if ($entryName !== null) {
			$key .= ':' . $entryName;
		}

		return $key;
	}

	private function writeCacheKey(string $namespace, string $payloadKind, string $keyMode, int $workerId, int $keySpace, int $operationIndex): string
	{
		$key = 'opcache_static_cache_benchmark:' . self::VERSION . ':' . $namespace . ':' . $payloadKind . ':write:' . $keyMode;
		$slot = $operationIndex % $keySpace;

		if ($keyMode === 'distinct') {
			return $key . ':worker:' . $workerId . ':slot:' . $slot;
		}

		return $key . ':slot:' . $slot;
	}

	private function explicitCacheKeys(string $namespace, string $payloadKind): array
	{
		if (!self::isMultiKeyPayload($payloadKind)) {
			return [$this->cacheKey($namespace, $payloadKind)];
		}

		$keys = [];
		foreach (self::multiKeyEntryNames() as $entryName) {
			$keys[] = $this->cacheKey($namespace, $payloadKind, $entryName);
		}

		return $keys;
	}

	private function assertKnownCaseAndBackend(string $case, string $backend): void
	{
		if (!array_key_exists($case, self::PAYLOAD_LABELS)) {
			throw new InvalidArgumentException('Unknown case: ' . $case);
		}

		if (!array_key_exists($backend, self::BACKEND_LABELS)) {
			throw new InvalidArgumentException('Unknown backend: ' . $backend);
		}

		if (!in_array($backend, self::supportedBackendsForPayload($case, array_keys(self::BACKEND_LABELS)), true)) {
			throw new InvalidArgumentException('Backend ' . $backend . ' does not support case: ' . $case);
		}
	}

	private function explicitPayloadForChecksum(string $namespace, string $payloadKind, mixed $payload): mixed
	{
		if (!self::isFetchMutationPayload($payloadKind)) {
			return $payload;
		}

		$storedPayload = $this->peekExplicitPayload($namespace, $payloadKind);
		if ($storedPayload === null) {
			throw new RuntimeException('Stored fetch-mutation payload is missing');
		}

		return $storedPayload;
	}

	private function explicitWriteNamespace(string $payloadKind, string $backend): string
	{
		if (self::isMultiKeyPayload($payloadKind)) {
			throw new InvalidArgumentException('Write benchmark does not support multi-key payloads: ' . $payloadKind);
		}

		return match ($backend) {
			'apcu' => 'apcu',
			'volatile_cache' => 'volatile',
			'persistent_cache' => 'persistent',
			default => throw new InvalidArgumentException('Write benchmark currently supports explicit cache backends only: ' . $backend),
		};
	}

	private function assertVolatileStaticCacheAvailable(): void
	{
		if (!$this->hasVolatileStaticCacheApi()) {
			throw new RuntimeException('OPcache volatile cache API is not available in this runtime');
		}

		$status = $this->volatileCacheStatus();
		if (!($status['enabled'] ?? false)) {
			throw new RuntimeException('OPcache volatile cache is disabled; set opcache.static_cache.volatile_size_mb > 0');
		}

		if (!($status['available'] ?? false)) {
			$reason = $status['failure_reason'] ?? 'unknown reason';
			throw new RuntimeException('OPcache volatile cache is unavailable: ' . $reason);
		}
	}

	private function assertApcuAvailable(): void
	{
		if (!$this->hasApcuApi()) {
			throw new RuntimeException('APCu is not available in this runtime');
		}

		$status = $this->apcuStatus();
		if (!($status['enabled'] ?? false)) {
			throw new RuntimeException('APCu is disabled; enable apc.enabled=1 and load the extension');
		}

		if (!($status['available'] ?? false)) {
			$reason = $status['failure_reason'] ?? 'unknown reason';
			throw new RuntimeException('APCu is unavailable: ' . $reason);
		}
	}

	private function assertPersistentStaticCacheAvailable(): void
	{
		if (!$this->hasPersistentStaticCacheApi()) {
			throw new RuntimeException('OPcache persistent cache API is not available in this runtime');
		}

		$status = $this->persistentCacheStatus();
		if (!($status['enabled'] ?? false)) {
			throw new RuntimeException('OPcache persistent cache is disabled; set opcache.static_cache.persistent_size_mb > 0');
		}

		if (!($status['available'] ?? false)) {
			$reason = $status['failure_reason'] ?? 'unknown reason';
			throw new RuntimeException('OPcache persistent cache is unavailable: ' . $reason);
		}
	}

	private function volatileCacheStatus(): array
	{
		if (!$this->hasVolatileStaticCacheApi()) {
			return [
				'enabled' => false,
				'available' => false,
				'failure_reason' => 'OPcache volatile cache API is not available in this runtime',
			];
		}

		return self::staticCacheInfoToArray(OPcache\volatile_cache_info());
	}

	private function persistentCacheStatus(): array
	{
		if (!$this->hasPersistentStaticCacheApi()) {
			return [
				'enabled' => false,
				'available' => false,
				'failure_reason' => 'OPcache persistent cache API is not available in this runtime',
			];
		}

		return self::staticCacheInfoToArray(OPcache\persistent_cache_info());
	}

	private static function staticCacheInfoToArray(mixed $status): array
	{
		if (is_array($status)) {
			return $status;
		}

		return [
			'enabled' => (bool) $status->enabled,
			'available' => (bool) $status->available,
			'startup_failed' => (bool) $status->startup_failed,
			'backend_initialized' => (bool) $status->backend_initialized,
			'configured_memory' => (int) $status->configured_memory,
			'shared_memory' => (int) $status->shared_memory,
			'entry_count' => (int) $status->entry_count,
			'segment_count' => (int) $status->segment_count,
			'shared_model' => (string) $status->shared_model,
			'failure_reason' => $status->failure_reason,
		];
	}

	private function jitStatus(): array
	{
		$status = [
			'enabled' => false,
			'kind' => (string) ini_get('opcache.jit'),
			'buffer_size' => (string) ini_get('opcache.jit_buffer_size'),
		];

		if (function_exists('opcache_get_status')) {
			$opcacheStatus = opcache_get_status(false);
			if (is_array($opcacheStatus) && isset($opcacheStatus['jit']) && is_array($opcacheStatus['jit'])) {
				$status = array_merge($status, $opcacheStatus['jit']);
			}
		}

		if (!isset($status['enabled']) || !is_bool($status['enabled'])) {
			$status['enabled'] = $status['kind'] !== ''
				&& $status['kind'] !== '0'
				&& $status['buffer_size'] !== ''
				&& $status['buffer_size'] !== '0';
		}

		return $status;
	}

	private function apcuStatus(): array
	{
		if (!$this->hasApcuApi()) {
			return [
				'enabled' => false,
				'available' => false,
				'failure_reason' => 'APCu extension is not loaded in this runtime',
			];
		}

		$enabled = function_exists('apcu_enabled') ? apcu_enabled() : ini_get('apc.enabled') !== '0';
		if (!$enabled) {
			return [
				'enabled' => false,
				'available' => false,
				'failure_reason' => 'APCu extension is loaded but disabled',
			];
		}

		$cacheInfo = function_exists('apcu_cache_info') ? apcu_cache_info(true) : [];
		$smaInfo = function_exists('apcu_sma_info') ? apcu_sma_info(true) : [];

		return [
			'enabled' => true,
			'available' => true,
			'version' => phpversion('apcu') ?: 'unknown',
			'num_entries' => $cacheInfo['num_entries'] ?? null,
			'mem_size' => $cacheInfo['mem_size'] ?? null,
			'num_seg' => $smaInfo['num_seg'] ?? null,
			'seg_size' => $smaInfo['seg_size'] ?? null,
		];
	}

	private function hasVolatileStaticCacheApi(): bool
	{
		return function_exists('OPcache\\volatile_store')
			&& function_exists('OPcache\\volatile_store_array')
			&& function_exists('OPcache\\volatile_fetch')
			&& function_exists('OPcache\\volatile_fetch_array')
			&& function_exists('OPcache\\volatile_exists')
			&& function_exists('OPcache\\volatile_lock')
			&& function_exists('OPcache\\volatile_unlock')
			&& function_exists('OPcache\\volatile_delete')
			&& function_exists('OPcache\\volatile_delete_array')
			&& function_exists('OPcache\\volatile_clear')
			&& function_exists('OPcache\\volatile_cache_info');
	}

	private function hasPersistentStaticCacheApi(): bool
	{
		return function_exists('OPcache\\persistent_store')
			&& function_exists('OPcache\\persistent_store_array')
			&& function_exists('OPcache\\persistent_fetch')
			&& function_exists('OPcache\\persistent_fetch_array')
			&& function_exists('OPcache\\persistent_exists')
			&& function_exists('OPcache\\persistent_lock')
			&& function_exists('OPcache\\persistent_unlock')
			&& function_exists('OPcache\\persistent_delete')
			&& function_exists('OPcache\\persistent_delete_array')
			&& function_exists('OPcache\\persistent_clear')
			&& function_exists('OPcache\\persistent_atomic_increment')
			&& function_exists('OPcache\\persistent_atomic_decrement')
			&& function_exists('OPcache\\persistent_cache_info');
	}

	private function isVolatileStaticCacheReady(): bool
	{
		$status = $this->volatileCacheStatus();

		return (bool) ($status['enabled'] ?? false) && (bool) ($status['available'] ?? false);
	}

	private function isPersistentStaticCacheReady(): bool
	{
		$status = $this->persistentCacheStatus();

		return (bool) ($status['enabled'] ?? false) && (bool) ($status['available'] ?? false);
	}

	private function hasApcuApi(): bool
	{
		return function_exists('apcu_fetch')
			&& function_exists('apcu_store')
			&& function_exists('apcu_clear_cache');
	}

	private function isApcuReady(): bool
	{
		$status = $this->apcuStatus();

		return (bool) ($status['enabled'] ?? false) && (bool) ($status['available'] ?? false);
	}

	private function peekVolatileStaticCachePayload(string $key): mixed
	{
		$this->assertVolatileStaticCacheAvailable();

		$missing = new stdClass();
		$payload = OPcache\volatile_fetch($key, $missing);

		return $payload !== $missing ? $payload : null;
	}

	private function peekPersistentStaticCachePayload(string $key): mixed
	{
		$this->assertPersistentStaticCacheAvailable();

		$missing = new stdClass();
		$payload = OPcache\persistent_fetch($key, $missing);

		return $payload !== $missing ? $payload : null;
	}

	private function peekApcuPayload(string $key): mixed
	{
		$this->assertApcuAvailable();

		$success = false;
		$payload = apcu_fetch($key, $success);

		return $success ? $payload : null;
	}

	private function peekExplicitPayload(string $namespace, string $payloadKind): mixed
	{
		if (!self::isMultiKeyPayload($payloadKind)) {
			return match ($namespace) {
				'apcu' => $this->peekExplicitPayloadKey('apcu', $this->cacheKey($namespace, $payloadKind)),
				'volatile' => $this->peekExplicitPayloadKey('volatile', $this->cacheKey($namespace, $payloadKind)),
				'persistent' => $this->peekExplicitPayloadKey('persistent', $this->cacheKey($namespace, $payloadKind)),
				default => throw new InvalidArgumentException('Unknown explicit cache namespace: ' . $namespace),
			};
		}

		if ($namespace === 'volatile' || $namespace === 'persistent') {
			$keys = [];
			foreach (self::multiKeyEntryNames() as $entryName) {
				$keys[$entryName] = $this->cacheKey($namespace, $payloadKind, $entryName);
			}

			$missing = null;
			$values = $namespace === 'volatile'
				? OPcache\volatile_fetch_array(array_values($keys), $missing)
				: OPcache\persistent_fetch_array(array_values($keys), $missing);

			$entries = [];
			foreach ($keys as $entryName => $key) {
				$entry = $values[$key] ?? $missing;
				if ($entry === $missing) {
					return null;
				}
				$entries[$entryName] = $entry;
			}

			return ['entries' => $entries];
		}

		$entries = [];
		foreach (self::multiKeyEntryNames() as $entryName) {
			$key = $this->cacheKey($namespace, $payloadKind, $entryName);
			$entry = $this->peekExplicitPayloadKey($namespace, $key);

			if ($entry === null) {
				return null;
			}
			$entries[$entryName] = $entry;
		}

		return ['entries' => $entries];
	}

	private function peekExplicitPayloadKey(string $namespace, string $key): mixed
	{
		return match ($namespace) {
			'apcu' => $this->peekApcuPayload($key),
			'volatile' => $this->peekVolatileStaticCachePayload($key),
			'persistent' => $this->peekPersistentStaticCachePayload($key),
			default => throw new InvalidArgumentException('Unknown explicit cache namespace: ' . $namespace),
		};
	}

	private function peekWritePayload(string $payloadKind, string $backend, string $keyMode, string $writeMode, int $workerId, int $keySpace): mixed
	{
		$namespace = $this->explicitWriteNamespace($payloadKind, $backend);
		$key = $this->writeCacheKey($namespace, $payloadKind, $keyMode, $workerId, $keySpace, 0);

		return $this->peekExplicitPayloadKey($namespace, $key);
	}

	private function retainedRetentionResult(string $case, string $backend, string $checksum, string $expectedChecksum): array
	{
		return [
			'ok' => true,
			'case' => $case,
			'backend' => $backend,
			'cache_retention' => 'retained',
			'checksum' => $checksum,
			'expected_checksum' => $expectedChecksum,
			'reason' => '',
		];
	}

	private function destroyedRetentionResult(string $case, string $backend, string $reason, string $checksum, string $expectedChecksum): array
	{
		return [
			'ok' => true,
			'case' => $case,
			'backend' => $backend,
			'cache_retention' => 'destroyed',
			'checksum' => $checksum,
			'expected_checksum' => $expectedChecksum,
			'reason' => $reason,
		];
	}

	private function respondJson(array $payload, int $statusCode = 200): void
	{
		header('Content-Type: application/json; charset=UTF-8', true, $statusCode);
		echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
	}

	private function respondCaseMatrix(array $cases): void
	{
		header('Content-Type: text/plain; charset=UTF-8');
		echo "case\tbackend\n";
		foreach ($cases as $case => $backends) {
			foreach ($backends as $backend) {
				echo $this->sanitizeTsvField((string) $case), "\t",
					$this->sanitizeTsvField((string) $backend),
					"\n";
			}
		}
	}

	private function respondTsv(array $payload): void
	{
		header('Content-Type: text/plain; charset=UTF-8');
		echo $payload['operation_count'], "\t",
			$payload['worker_us'], "\t",
			$payload['memory_delta'], "\t",
			$payload['peak_delta'], "\t",
			$payload['cache_hit_count'], "\t",
			$payload['build_count'], "\t",
			$this->sanitizeTsvField($payload['checksum']), "\t",
			$payload['read_score'],
			"\n";
	}

	private function respondWriteTsv(array $payload): void
	{
		header('Content-Type: text/plain; charset=UTF-8');
		echo $payload['operation_count'], "\t",
			$payload['worker_us'], "\t",
			$payload['memory_delta'], "\t",
			$payload['peak_delta'], "\t",
			$payload['store_count'], "\t",
			$payload['build_count'], "\t",
			$this->sanitizeTsvField($payload['checksum']), "\t",
			$payload['write_score'],
			"\n";
	}

	private function respondVerifyTsv(array $payload): void
	{
		header('Content-Type: text/plain; charset=UTF-8');
		echo $this->sanitizeTsvField($payload['cache_retention']), "\t",
			$this->sanitizeTsvField($payload['reason'] ?? ''), "\t",
			$this->sanitizeTsvField($payload['checksum'] ?? ''), "\t",
			$this->sanitizeTsvField($payload['expected_checksum'] ?? ''),
			"\n";
	}

	private function sanitizeTsvField(string $value): string
	{
		return str_replace(["\t", "\r", "\n"], ' ', $value);
	}
}
