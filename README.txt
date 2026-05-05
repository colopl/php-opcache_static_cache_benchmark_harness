OPcache Static Cache Benchmark
============================

This directory contains the HTTP benchmark suite for OPcache Static Cache. Read
scenarios reset the selected state, prime one value, warm it up, measure with
read_only=1, and verify afterwards. A cache miss during a measured read request
is an error, so build/store work is not folded into the timing sample. Write
scenarios reset the selected backend, warm it with store traffic, measure
store/publish batches directly, and verify the final published payload.

The canonical entrypoint is:

  ./benchmark.sh --php-repo https://github.com/colopl/php-src.git --php-ref=opcache_static_cache_impl --runs-on container --target fpm,frankenphp

The final stdout is DokuWiki text that can be pasted into RFC.txt. Its mean
operation cells include the APCu baseline or the relative faster/slower
percentage against APCu for the same workload. Progress and runtime logs are
written to stderr.

Workloads
---------

- constant_array: small deterministic array baseline.
- route_table_read: framework-shaped route table and URL generator reads.
- large_array: larger nested array read-after-prime case.
- large_object_graph: larger userland object graph eligible for shared graph
  storage.
- metadata_object_read: nested metadata objects similar to reflection/container
  metadata.
- metadata_object_fetch_mutate: explicit metadata-object fetches where the
  returned graph is mutated before the next fetch.
- multi_key_config_read: many small config entries read through rotating keys.
- safe_direct_object: DateTimeImmutable/DateTimeZone/DateInterval graph that
  exercises __DirectCacheSafe restore paths.
- spl_collection_object: SplFixedArray/ArrayObject/ArrayIterator graph that
  exercises SPL __DirectCacheSafe restore paths.
- carbon_datetime_object: nesbot/carbon CarbonImmutable/Carbon/CarbonTimeZone
  graph with a 64-entry Carbon timeline array, used to compare DateTime-derived
  objects with custom serializers against the __DirectCacheSafe restore path.
- serialized_cycle_object: cyclic userland object graph that uses the serialization
  fallback.
- reference_assignment_object: object assignment plus later mutation semantics.
- cycle_assignment_object: cyclic assignment plus later mutation semantics.
- nested_array_assignment: nested array assignment plus later mutation
  semantics.

Backends
--------

- apcu: apcu_store() / apcu_fetch().
- volatile_cache: \OPcache\volatile_store() / \OPcache\volatile_fetch().
- persistent_cache: \OPcache\persistent_store() / \OPcache\persistent_fetch().
- volatile_static_immediate_class/property/method:
  #[\OPcache\VolatileStatic(strategy: \OPcache\CacheStrategy::Immediate)].
- volatile_static_tracking_class/property/method:
  #[\OPcache\VolatileStatic(strategy: \OPcache\CacheStrategy::Tracking)].
- persistent_static_class/property/method: #[\OPcache\PersistentStatic].

The complete matrix is 12 workloads by the backends available in the runtime.
When APCu is not loaded, the PHP runner uses the available matrix from
?action=describe&format=matrix; --require-full-matrix requires every advertised
cell to pass.

Running in the devcontainer
---------------------------

The devcontainer wrapper can build NTS php-fpm + nginx and ZTS FrankenPHP:

  ./benchmark.sh \
    --runs-on devcontainer \
    --target fpm,frankenphp \
    --php-repo .. \
    --iterations 60 \
    --warmup 5 \
    --operations 5000

Named scenarios
---------------

The suite now includes named scenarios for the vote-prep benchmark gaps called
out in RFC.txt:

- vote_read_long: longer repeated-read measurements on representative
  read-heavy workloads.
- carbon_datetime_compare: focused DateTimeImmutable direct-restore and Carbon
  DateTime serializer comparison.
- fetch_mutate_object: explicit-cache object fetches where each returned object
  graph is mutated before the next fetch.
- vote_write_throughput: sequential explicit-cache write throughput using a
  bounded distinct-key ring.
- vote_write_contention_shared: concurrent explicit-cache writes where all
  workers publish to the same hot key.
- vote_write_contention_distinct: concurrent explicit-cache writes where each
  worker publishes to its own bounded key ring.
- vote_entry_reservation_contention: concurrent single-builder entry
  reservation using apcu_entry() or OPcache *_lock($key) plus store on one
  shared hot key.

Examples:

  ./benchmark.sh --runs-on devcontainer --target fpm,frankenphp --runner read --scenario vote_read_long
  ./benchmark.sh --runs-on devcontainer --target fpm,frankenphp --runner read --scenario carbon_datetime_compare
  ./benchmark.sh --runs-on devcontainer --target fpm,frankenphp --runner read --scenario fetch_mutate_object
  ./benchmark.sh --runs-on devcontainer --target fpm,frankenphp --runner write --scenario vote_write_throughput
  ./benchmark.sh --runs-on devcontainer --target fpm,frankenphp --runner write --scenario vote_write_contention_shared
  ./benchmark.sh --runs-on devcontainer --target fpm,frankenphp --runner write --scenario vote_entry_reservation_contention
  ./benchmark.sh --runs-on devcontainer --target fpm,frankenphp --runner read --scenario vote_read_long --jit tracing

Useful options:

  ./benchmark.sh --runs-on devcontainer --target fpm
  ./benchmark.sh --runs-on devcontainer --target frankenphp
  ./benchmark.sh --runs-on devcontainer --target fpm,frankenphp --runner write --scenario vote_write_contention_distinct
  ./benchmark.sh --runs-on devcontainer --target fpm,frankenphp --runner write --scenario vote_write_contention_shared --jit tracing

APCu is built by scripts/build_apcu.sh. By default it clones the APCu master
branch; set APCU_VERSION or APCU_REPO to override that source.
  ./benchmark.sh --runs-on devcontainer --target frankenphp --threads 5
  ./benchmark.sh --runs-on devcontainer --skip-rebuild --build-dir /tmp/opcache-static-cache-benchmark-devcontainer

CLI-only checks
---------------

The CLI helper records the one-shot startup overhead table and the Zend VM/JIT
baseline table used by RFC.txt:

  ./scripts/benchmark_cli.sh \
    --runner-php /path/to/current-nts/sapi/cli/php \
    --current-nts-php /path/to/current-nts/sapi/cli/php \
    --current-zts-php /path/to/current-zts/sapi/cli/php \
    --base-nts-php /path/to/base-nts/sapi/cli/php \
    --base-zts-php /path/to/base-zts/sapi/cli/php \
    --source-root /path/to/current/php-src \
    --output-dir results/current-cli

Use --mode startup to emit only the startup table, or --mode zend to emit only
the Zend/bench.php comparison. The base PHP binaries are required only for
--mode zend and --mode all.

--php-repo accepts a local php-src directory or a git URL. When a git URL is
used, --php-ref selects a branch, tag, or fetchable revision. --frankenphp-repo
and --frankenphp-ref provide the same controls for FrankenPHP; local directories
are supported.

Running in a container
----------------------

The container wrapper builds the selected runtime in Docker and copies results
out of the container:

  ./benchmark.sh \
    --runs-on container \
    --target fpm,frankenphp \
    --php-repo .. \
    --iterations 60 \
    --warmup 5 \
    --operations 5000

For a remote source:

  ./benchmark.sh \
    --runs-on container \
    --target frankenphp \
    --php-repo https://github.com/php/php-src.git \
    --php-ref master \
    --frankenphp-repo https://github.com/php/frankenphp.git

Running against an existing HTTP runtime
----------------------------------------

If an HTTP runtime is already serving public/index.php, use local mode:

  ./benchmark.sh \
    --runs-on local \
    --target fpm \
    --php ../sapi/cli/php \
    --base-url http://127.0.0.1:8080/index.php \
    --iterations 10 \
    --warmup 2 \
    --operations 1000

The target label in local mode is informational; it does not start a runtime.

Direct wrappers
---------------

The lower-level runtime wrappers are still available:

  ./scripts/benchmark_fpm.sh \
    --php-fpm /path/to/php-src-build/sapi/fpm/php-fpm \
    --php-cli /path/to/php-src-build/sapi/cli/php \
    --apcu-so /path/to/apcu.so \
    --runner write \
    --scenario vote_write_contention_shared

  ./scripts/benchmark_frankenphp.sh \
    --frankenphp /path/to/frankenphp-zts \
    --php-cli /path/to/php-zts/sapi/cli/php \
    --apcu-so /path/to/apcu.so \
    --threads 5 \
    --runner read \
    --scenario vote_read_long \
    --jit tracing

Output files
------------

Each run writes files under the selected output directory:

- raw-<timestamp>.tsv: per-request samples.
- summary-<timestamp>.tsv: per workload/backend aggregate rows.
- apcu-comparison-<timestamp>.tsv: candidate rows compared with APCu for the
  same workload.
- strategy-comparison-<timestamp>.tsv: Immediate/Tracking/PersistentStatic rows
  grouped by class/property/method target.
- metadata-<timestamp>.json: runtime settings and matrix metadata.
- report-<timestamp>.dokuwiki.txt: the same DokuWiki report printed to stdout.
- write-raw-<timestamp>.tsv: per-iteration write samples.
- write-summary-<timestamp>.tsv: write scenario aggregate rows.
- write-apcu-comparison-<timestamp>.tsv: write rows compared with APCu for the
  same workload.
- write-metadata-<timestamp>.json: write scenario metadata, including
  architecture and JIT state.
- write-report-<timestamp>.dokuwiki.txt: DokuWiki report for write scenarios.

Interpreting results
--------------------

Write scenarios benchmark the explicit cache APIs only. They measure the
store/publish path directly and use bounded shared or distinct key layouts to
surface write throughput and lock contention without folding the attribute
restore fast path into the store-side timing.

Metadata JSON now records the runtime architecture (php_uname('m')) and JIT
status from opcache_get_status(), so the same named scenario can be compared
across architectures and with JIT disabled or enabled.

Property/method attributes and repeated explicit fetches both exercise the main
intended read path: once the value is restored into request-local storage, the
inner loop reads a local slot instead of decoding the shared payload each time.
Explicit OPcache\volatile_fetch() and OPcache\persistent_fetch() still cross the
key/value API and lock path, but they reuse a request-local materialized zval
slot while the cache epoch still matches when the value is supported by the
request-local clone path. For object-free values this remains a direct slot-copy
path. For ordinary object-bearing values, the slot is treated as a request-local
prototype and every fetch returns an independent object graph through an
internal clone path that does not invoke userland __clone. Mutating a returned
object graph does not affect previous or later fetches. DateTime,
DateTimeImmutable, DateTimeZone, DateInterval, SPL collections, and supported
subclasses such as Carbon rematerialize from the stored value on each explicit
fetch instead of joining the prototype clone path. The fetch_mutate_object
scenario measures the clone-after-mutation path for ordinary object graphs: it
is slower than an object-free slot copy, but avoids repeated full
materialization from the stored value when the object graph is prototype
cloneable.

Class-level attributes store a class blob. They are useful when a whole class of
static state should be restored together, but can be much more expensive when a
workload repeatedly needs only one value from the class blob. The
strategy-comparison TSV makes this visible.

CacheStrategy::Immediate stores an assignment-time snapshot in the volatile
cache. Later mutations of the same object or nested array are not followed
unless the static root is assigned again.

CacheStrategy::Tracking publishes changed restored/assigned roots at request
shutdown. It is the strategy for code that wants later reachable mutations to
survive, but it carries mutation-tracking and shutdown-publish overhead.

PersistentStatic stores in the persistent cache and has stricter failure semantics. It
is appropriate for non-volatile state where losing a write is an application
error. Root assignments and later array mutations of the assigned/restored value
are written to persistent cache SHM immediately and fail fatally if they cannot be stored.
Scalar/object property mutations are not followed unless the static root is
assigned again; mutating an array identity inside the restored graph is still an
array mutation and is published immediately. It does not use the volatile-cache
backend.
