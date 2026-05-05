#!/bin/sh

set -eu

USAGE() {
	cat <<'EOF'
Usage: ./scripts/benchmark.sh [--php /path/to/php] [--base-url URL] [--runner read|write] [--scenario NAME] [--iterations N] [--warmup N] [--operations N] [--concurrency N] [--key-mode shared|distinct] [--key-space N] [--runtime-label LABEL] [--output-dir DIR] [--cases a,b] [--backends a,b] [--require-full-matrix]

Legacy positional form is still accepted:
  ./scripts/benchmark.sh URL ITERATIONS WARMUP OPERATIONS
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

ROOT=$(CDPATH= cd "$(dirname "${0}")/.." && pwd)
PHP_BIN=${PHP_BIN:-php}
BASE_URL=${BASE_URL:-http://127.0.0.1:8080/index.php}
ITERATIONS=${ITERATIONS:-}
WARMUP=${WARMUP:-}
OPERATIONS=${OPERATIONS:-}
RUNNER=${RUNNER:-}
SCENARIO=${SCENARIO:-}
CONCURRENCY=${CONCURRENCY:-}
KEY_MODE=${KEY_MODE:-}
KEY_SPACE=${KEY_SPACE:-}
RUNTIME_LABEL=${BENCHMARK_RUNTIME_LABEL:-PHP runtime}
OUTPUT_DIR=${OUTPUT_DIR:-${ROOT}/results}
CASES=${CASES:-all}
BACKENDS=${BACKENDS:-all}
REQUIRE_FULL_MATRIX_VALUE=${REQUIRE_FULL_MATRIX:-0}

if test "${#}" -gt 0; then
	case "${1}" in
		--*) ;;
		*)
			BASE_URL=${1}
			if test "${#}" -gt 1; then ITERATIONS=${2}; fi
			if test "${#}" -gt 2; then WARMUP=${3}; fi
			if test "${#}" -gt 3; then OPERATIONS=${4}; fi
			set --
			;;
	esac
fi

while test "${#}" -gt 0; do
	case "${1}" in
		--php)
			REQUIRE_VALUE "${1}" "${#}"
			PHP_BIN=${2}
			shift 2
			;;
		--base-url)
			REQUIRE_VALUE "${1}" "${#}"
			BASE_URL=${2}
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
		--runtime-label)
			REQUIRE_VALUE "${1}" "${#}"
			RUNTIME_LABEL=${2}
			shift 2
			;;
		--output-dir)
			REQUIRE_VALUE "${1}" "${#}"
			OUTPUT_DIR=${2}
			shift 2
			;;
		--cases)
			REQUIRE_VALUE "${1}" "${#}"
			CASES=${2}
			shift 2
			;;
		--backends)
			REQUIRE_VALUE "${1}" "${#}"
			BACKENDS=${2}
			shift 2
			;;
		--require-full-matrix)
			REQUIRE_FULL_MATRIX_VALUE=1
			shift
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

set -- \
	"${ROOT}/scripts/BenchmarkLauncher.php" \
	--base-url "${BASE_URL}" \
	--runtime-label "${RUNTIME_LABEL}" \
	--output-dir "${OUTPUT_DIR}" \
	--cases "${CASES}" \
	--backends "${BACKENDS}"

if test -n "${ITERATIONS}"; then
	set -- "${@}" --iterations "${ITERATIONS}"
fi

if test -n "${WARMUP}"; then
	set -- "${@}" --warmup "${WARMUP}"
fi

if test -n "${OPERATIONS}"; then
	set -- "${@}" --operations "${OPERATIONS}"
fi

if test -n "${RUNNER}"; then
	set -- "${@}" --runner "${RUNNER}"
fi

if test -n "${SCENARIO}"; then
	set -- "${@}" --scenario "${SCENARIO}"
fi

if test "${RUNNER}" = write; then
	if test -n "${CONCURRENCY}"; then set -- "${@}" --concurrency "${CONCURRENCY}"; fi
	if test -n "${KEY_MODE}"; then set -- "${@}" --key-mode "${KEY_MODE}"; fi
	if test -n "${KEY_SPACE}"; then set -- "${@}" --key-space "${KEY_SPACE}"; fi
fi

if test "${REQUIRE_FULL_MATRIX_VALUE}" -ne 0; then
	set -- "${@}" --require-full-matrix
fi

exec "${PHP_BIN}" "${@}"
