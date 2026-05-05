#!/bin/sh

set -eu

USAGE() {
	cat <<'EOF'
Usage: ./benchmark.sh --runs-on container|devcontainer|local [--target fpm|frankenphp|fpm,frankenphp] [options]

Common options:
  --php-repo DIR|URL          php-src directory or git URL
  --php-ref REF               php-src branch, tag, or revision for git sources
  --frankenphp-repo DIR|URL   FrankenPHP directory or git URL
  --frankenphp-ref REF        FrankenPHP branch, tag, or revision
	--runner read|write         benchmark runner
	--scenario NAME            named benchmark scenario
  --iterations N              measured iterations
  --warmup N                  warmup requests
  --operations N              operations per request
	--concurrency N             write-runner concurrent workers
	--key-mode shared|distinct  write-runner key layout
	--key-space N               write-runner bounded key ring size
	--jit MODE                  JIT mode (off, tracing, function, or numeric)
  --threads N                 FrankenPHP worker threads
  --make-jobs N               build parallelism
  --build-dir DIR             devcontainer build directory
  --skip-rebuild              reuse devcontainer build products

Local runner options:
  --base-url URL              already-running benchmark HTTP endpoint
  --php /path/to/php          PHP CLI used by the local PHP runner
  --output-dir DIR            result output directory
EOF
}

REQUIRE_VALUE() {
	REQUIRE_OPTION=${1}
	REQUIRE_COUNT=${2}
	if test "${REQUIRE_COUNT}" -lt 2; then
		echo "${REQUIRE_OPTION} requires a value" >&2
		exit 1
	fi
}

TARGET_TO_RUNTIME() {
	case "${1}" in
		all|fpm,frankenphp|frankenphp,fpm)
			printf '%s\n' all
			;;
		fpm|frankenphp)
			printf '%s\n' "${1}"
			;;
		*)
			echo "unsupported target: ${1}" >&2
			exit 1
			;;
	esac
}

SCRIPT_DIR=$(CDPATH= cd "$(dirname "${0}")" && pwd)
DEFAULT_PHP_REPO=$(CDPATH= cd "${SCRIPT_DIR}/.." && pwd)
RUNS_ON=devcontainer
TARGET=fpm,frankenphp
PHP_REPO=${PHP_REPO:-${DEFAULT_PHP_REPO}}
PHP_REF=${PHP_REF:-}
FRANKENPHP_REPO=${FRANKENPHP_REPO:-https://github.com/php/frankenphp.git}
FRANKENPHP_REF=${FRANKENPHP_REF:-}
ITERATIONS=${ITERATIONS:-}
WARMUP=${WARMUP:-}
OPERATIONS=${OPERATIONS:-}
RUNNER=${RUNNER:-}
SCENARIO=${SCENARIO:-}
CONCURRENCY=${CONCURRENCY:-}
KEY_MODE=${KEY_MODE:-}
KEY_SPACE=${KEY_SPACE:-}
JIT=${JIT:-off}
THREADS=${FRANKENPHP_THREADS:-5}
MAKE_JOBS=${MAKE_JOBS:-0}
BUILD_DIR=${OPCACHE_STATIC_CACHE_BENCHMARK_BUILD_DIR:-}
SKIP_REBUILD=0
BASE_URL=${BASE_URL:-http://127.0.0.1:8080/index.php}
PHP_BIN=${PHP_BIN:-php}
OUTPUT_DIR=${OUTPUT_DIR:-${SCRIPT_DIR}/results}

while test "${#}" -gt 0; do
	case "${1}" in
		--runs-on)
			REQUIRE_VALUE "${1}" "${#}"
			RUNS_ON=${2}
			shift 2
			;;
		--target)
			REQUIRE_VALUE "${1}" "${#}"
			TARGET=${2}
			shift 2
			;;
		--php-repo)
			REQUIRE_VALUE "${1}" "${#}"
			PHP_REPO=${2}
			shift 2
			;;
		--php-ref)
			REQUIRE_VALUE "${1}" "${#}"
			PHP_REF=${2}
			shift 2
			;;
		--frankenphp-repo)
			REQUIRE_VALUE "${1}" "${#}"
			FRANKENPHP_REPO=${2}
			shift 2
			;;
		--frankenphp-ref)
			REQUIRE_VALUE "${1}" "${#}"
			FRANKENPHP_REF=${2}
			shift 2
			;;
		--iterations)
			REQUIRE_VALUE "${1}" "${#}"
			ITERATIONS=${2}
			shift 2
			;;
		--warmup)
			REQUIRE_VALUE "${1}" "${#}"
			WARMUP=${2}
			shift 2
			;;
		--operations)
			REQUIRE_VALUE "${1}" "${#}"
			OPERATIONS=${2}
			shift 2
			;;
		--runner)
			REQUIRE_VALUE "${1}" "${#}"
			RUNNER=${2}
			shift 2
			;;
		--scenario)
			REQUIRE_VALUE "${1}" "${#}"
			SCENARIO=${2}
			shift 2
			;;
		--concurrency)
			REQUIRE_VALUE "${1}" "${#}"
			CONCURRENCY=${2}
			shift 2
			;;
		--key-mode)
			REQUIRE_VALUE "${1}" "${#}"
			KEY_MODE=${2}
			shift 2
			;;
		--key-space)
			REQUIRE_VALUE "${1}" "${#}"
			KEY_SPACE=${2}
			shift 2
			;;
		--jit)
			REQUIRE_VALUE "${1}" "${#}"
			JIT=${2}
			shift 2
			;;
		--threads)
			REQUIRE_VALUE "${1}" "${#}"
			THREADS=${2}
			shift 2
			;;
		--make-jobs)
			REQUIRE_VALUE "${1}" "${#}"
			MAKE_JOBS=${2}
			shift 2
			;;
		--build-dir)
			REQUIRE_VALUE "${1}" "${#}"
			BUILD_DIR=${2}
			shift 2
			;;
		--skip-rebuild)
			SKIP_REBUILD=1
			shift
			;;
		--base-url)
			REQUIRE_VALUE "${1}" "${#}"
			BASE_URL=${2}
			shift 2
			;;
		--php)
			REQUIRE_VALUE "${1}" "${#}"
			PHP_BIN=${2}
			shift 2
			;;
		--output-dir)
			REQUIRE_VALUE "${1}" "${#}"
			OUTPUT_DIR=${2}
			shift 2
			;;
		-h|--help)
			USAGE
			exit 0
			;;
		*)
			echo "Unknown argument: ${1}" >&2
			USAGE >&2
			exit 1
			;;
	esac
done

RUNTIME=$(TARGET_TO_RUNTIME "${TARGET}")

case "${RUNS_ON}" in
	devcontainer)
		set -- \
			--php-repo "${PHP_REPO}" \
			--runtime "${RUNTIME}" \
			--jit "${JIT}" \
			--threads "${THREADS}" \
			--frankenphp-repo "${FRANKENPHP_REPO}" \
			--output-dir "${OUTPUT_DIR}" \
			--make-jobs "${MAKE_JOBS}"
		if test -n "${ITERATIONS}"; then set -- "${@}" --iterations "${ITERATIONS}"; fi
		if test -n "${WARMUP}"; then set -- "${@}" --warmup "${WARMUP}"; fi
		if test -n "${OPERATIONS}"; then set -- "${@}" --operations "${OPERATIONS}"; fi
		if test -n "${RUNNER}"; then set -- "${@}" --runner "${RUNNER}"; fi
		if test -n "${SCENARIO}"; then set -- "${@}" --scenario "${SCENARIO}"; fi
		if test -n "${CONCURRENCY}"; then set -- "${@}" --concurrency "${CONCURRENCY}"; fi
		if test -n "${KEY_MODE}"; then set -- "${@}" --key-mode "${KEY_MODE}"; fi
		if test -n "${KEY_SPACE}"; then set -- "${@}" --key-space "${KEY_SPACE}"; fi
		if test -n "${PHP_REF}"; then set -- "${@}" --php-ref "${PHP_REF}"; fi
		if test -n "${FRANKENPHP_REF}"; then set -- "${@}" --frankenphp-ref "${FRANKENPHP_REF}"; fi
		if test -n "${BUILD_DIR}"; then set -- "${@}" --build-dir "${BUILD_DIR}"; fi
		if test "${SKIP_REBUILD}" -ne 0; then set -- "${@}" --skip-rebuild; fi
		exec "${SCRIPT_DIR}/benchmark_on_devcontainer.sh" "${@}"
		;;
	container)
		PHP_REPO_ARG=${PHP_REPO}
		if test -n "${PHP_REF}"; then
			case "${PHP_REPO_ARG}" in
				*#*) ;;
				*) PHP_REPO_ARG=${PHP_REPO_ARG}#${PHP_REF} ;;
			esac
		fi
		set -- \
			--php-repo "${PHP_REPO_ARG}" \
			--runtime "${RUNTIME}" \
			--jit "${JIT}" \
			--frankenphp-repo "${FRANKENPHP_REPO}" \
			--frankenphp-threads "${THREADS}" \
			--output-dir "${OUTPUT_DIR}" \
			--make-jobs "${MAKE_JOBS}"
		if test -n "${ITERATIONS}"; then set -- "${@}" --iterations "${ITERATIONS}"; fi
		if test -n "${WARMUP}"; then set -- "${@}" --warmup "${WARMUP}"; fi
		if test -n "${OPERATIONS}"; then set -- "${@}" --operations "${OPERATIONS}"; fi
		if test -n "${RUNNER}"; then set -- "${@}" --runner "${RUNNER}"; fi
		if test -n "${SCENARIO}"; then set -- "${@}" --scenario "${SCENARIO}"; fi
		if test -n "${CONCURRENCY}"; then set -- "${@}" --concurrency "${CONCURRENCY}"; fi
		if test -n "${KEY_MODE}"; then set -- "${@}" --key-mode "${KEY_MODE}"; fi
		if test -n "${KEY_SPACE}"; then set -- "${@}" --key-space "${KEY_SPACE}"; fi
		if test -n "${FRANKENPHP_REF}"; then set -- "${@}" --frankenphp-ref "${FRANKENPHP_REF}"; fi
		exec "${SCRIPT_DIR}/benchmark_on_container.sh" "${@}"
		;;
	local)
		set -- \
			--php "${PHP_BIN}" \
			--base-url "${BASE_URL}" \
			--runtime-label "local ${TARGET}" \
			--output-dir "${OUTPUT_DIR}"
		if test -n "${ITERATIONS}"; then set -- "${@}" --iterations "${ITERATIONS}"; fi
		if test -n "${WARMUP}"; then set -- "${@}" --warmup "${WARMUP}"; fi
		if test -n "${OPERATIONS}"; then set -- "${@}" --operations "${OPERATIONS}"; fi
		if test -n "${RUNNER}"; then set -- "${@}" --runner "${RUNNER}"; fi
		if test -n "${SCENARIO}"; then set -- "${@}" --scenario "${SCENARIO}"; fi
		if test "${RUNNER}" = write; then
			if test -n "${CONCURRENCY}"; then set -- "${@}" --concurrency "${CONCURRENCY}"; fi
			if test -n "${KEY_MODE}"; then set -- "${@}" --key-mode "${KEY_MODE}"; fi
			if test -n "${KEY_SPACE}"; then set -- "${@}" --key-space "${KEY_SPACE}"; fi
		fi
		if test "${RUNNER}" = read || { test -z "${RUNNER}" && test -z "${SCENARIO}"; }; then
			set -- "${@}" --require-full-matrix
		fi
		exec "${SCRIPT_DIR}/scripts/benchmark.sh" "${@}"
		;;
	*)
		echo "unsupported --runs-on value: ${RUNS_ON}" >&2
		exit 1
		;;
esac
