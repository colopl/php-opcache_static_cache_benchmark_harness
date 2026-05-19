#!/bin/sh

set -eu

USAGE() {
	cat <<'EOF'
Usage: ./scripts/benchmark_frankenphp.sh --frankenphp /path/to/frankenphp --apcu-so /path/to/apcu.so --php-cli /path/to/php [--base-url http://127.0.0.1:8080/index.php] [--threads N] [--runner read|write] [--scenario NAME] [--iterations N] [--warmup N] [--operations N] [--concurrency N] [--key-mode shared|distinct] [--key-space N] [--jit MODE] [--output-dir DIR]
EOF
}

REQUIRE_VALUE() {
	if test "${2}" -lt 2; then
		echo "${1} requires a value" >&2
		exit 1
	fi
}

ROOT=$(CDPATH= cd "$(dirname "${0}")/.." && pwd)
FRANKENPHP_BIN=
PHP_CLI_BIN=
APCU_SO=
BASE_URL=${BASE_URL:-http://127.0.0.1:8080/index.php}
THREADS=${THREADS:-5}
ITERATIONS=${ITERATIONS:-}
WARMUP=${WARMUP:-}
OPERATIONS=${OPERATIONS:-}
RUNNER=${RUNNER:-}
SCENARIO=${SCENARIO:-}
CONCURRENCY=${CONCURRENCY:-}
KEY_MODE=${KEY_MODE:-}
KEY_SPACE=${KEY_SPACE:-}
JIT=${JIT:-off}
JIT_BUFFER_SIZE=${JIT_BUFFER_SIZE:-64M}
OUTPUT_DIR=${OUTPUT_DIR:-${ROOT}/results}
APC_SHM_SIZE=${APC_SHM_SIZE:-128M}
RUNTIME_INI_DIR=${ROOT}/runtime/frankenphp-full-matrix
FRANKENPHP_PID=

while test "${#}" -gt 0; do
	case "${1}" in
		--frankenphp)
			REQUIRE_VALUE "${1}" "${#}"
			FRANKENPHP_BIN=${2}
			shift 2
			;;
		--php-cli)
			REQUIRE_VALUE "${1}" "${#}"
			PHP_CLI_BIN=${2}
			shift 2
			;;
		--apcu-so)
			REQUIRE_VALUE "${1}" "${#}"
			APCU_SO=${2}
			shift 2
			;;
		--base-url)
			REQUIRE_VALUE "${1}" "${#}"
			BASE_URL=${2}
			shift 2
			;;
		--threads)
			REQUIRE_VALUE "${1}" "${#}"
			THREADS=${2}
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

if test -z "${FRANKENPHP_BIN}"; then
	USAGE >&2
	exit 1
fi
if test -z "${APCU_SO}"; then
	USAGE >&2
	exit 1
fi
if test -z "${PHP_CLI_BIN}"; then
	USAGE >&2
	exit 1
fi
if test ! -x "${FRANKENPHP_BIN}"; then
	echo "FrankenPHP binary is not executable: ${FRANKENPHP_BIN}" >&2
	exit 1
fi
if test ! -x "${PHP_CLI_BIN}"; then
	echo "PHP CLI binary is not executable: ${PHP_CLI_BIN}" >&2
	exit 1
fi
if test ! -f "${APCU_SO}"; then
	echo "APCu shared module not found: ${APCU_SO}" >&2
	exit 1
fi

CLEANUP() {
	EXIT_CODE=${?}
	trap - 0 2 15

	if test -n "${FRANKENPHP_PID}"; then
		kill "${FRANKENPHP_PID}" >/dev/null 2>&1 || true
		wait "${FRANKENPHP_PID}" >/dev/null 2>&1 || true
	fi

	exit "${EXIT_CODE}"
}

WAIT_FOR_RUNTIME() {
	ATTEMPT=1
	while test "${ATTEMPT}" -le 60; do
		if curl -fsS "${BASE_URL}?action=describe" >/dev/null 2>&1; then
			return 0
		fi
		sleep 1
		ATTEMPT=$(( ${ATTEMPT} + 1 ))
	done

	return 1
}

BASE_URL_PORT() {
	URL_WITHOUT_SCHEME=${BASE_URL#*://}
	HOST_PORT=${URL_WITHOUT_SCHEME%%/*}
	PORT=${HOST_PORT##*:}

	if test "${PORT}" = "${HOST_PORT}"; then
		case "${BASE_URL}" in
			http://*) PORT=80 ;;
			https://*) PORT=443 ;;
			*) PORT= ;;
		esac
	fi

	echo "${PORT}"
}

PORT_IN_USE() {
	if command -v ss >/dev/null 2>&1; then
		ss -ltn 2>/dev/null | awk -v port=":${1}" '
			$1 == "LISTEN" && index($4, port) { found = 1 }
			END { exit(found ? 0 : 1) }
		'
		return "${?}"
	fi

	return 1
}

ASSERT_PORT_FREE() {
	PORT=${1}
	LABEL=${2}

	if test -n "${PORT}" && PORT_IN_USE "${PORT}"; then
		echo "${LABEL} port is already in use: ${PORT}" >&2
		exit 1
	fi
}

ASSERT_RUNTIME_PORTS_FREE() {
	if curl --max-time 1 -fsS "${BASE_URL}?action=describe" >/dev/null 2>&1; then
		echo "benchmark base URL already serves a runtime: ${BASE_URL}" >&2
		exit 1
	fi

	ASSERT_PORT_FREE "$(BASE_URL_PORT)" "benchmark HTTP"
	ASSERT_PORT_FREE 2019 "FrankenPHP admin"
}

WRITE_RUNTIME_INI() {
	mkdir -p "${RUNTIME_INI_DIR}"
	cat > "${RUNTIME_INI_DIR}/php.ini" <<EOF
display_errors=1
display_startup_errors=1
log_errors=0
error_reporting=E_ALL
expose_php=0
memory_limit=512M
max_execution_time=0

extension=${APCU_SO}
apc.enabled=1
apc.shm_size=${APC_SHM_SIZE}

opcache.enable=1
opcache.enable_cli=0
opcache.validate_timestamps=0
opcache.memory_consumption=128
opcache.max_accelerated_files=20000
opcache.static_cache.volatile_size_mb=128
opcache.static_cache.pinned_size_mb=128
EOF

	if test "${JIT}" != off; then
		cat >> "${RUNTIME_INI_DIR}/php.ini" <<EOF

opcache.jit_buffer_size=${JIT_BUFFER_SIZE}
opcache.jit=${JIT}
EOF
	fi
}

trap CLEANUP 0 2 15

cd "${ROOT}"
ASSERT_RUNTIME_PORTS_FREE
WRITE_RUNTIME_INI

PHPRC="${RUNTIME_INI_DIR}" \
BENCH_THREADS="${THREADS}" \
OPCACHE_VOLATILE_CACHE_BENCHMARK_ROOT="${ROOT}" \
SERVER_NAME="${BASE_URL%/index.php}" \
"${FRANKENPHP_BIN}" run --config "${ROOT}/Caddyfile" &
FRANKENPHP_PID=${!}

if ! WAIT_FOR_RUNTIME; then
	echo "FrankenPHP benchmark runtime did not become ready: ${BASE_URL}?action=describe" >&2
	exit 1
fi

RUNTIME_LABEL='FrankenPHP (ZTS)'
if test "${JIT}" != off; then
	RUNTIME_LABEL="${RUNTIME_LABEL} [JIT ${JIT}]"
fi

set -- \
	--php "${PHP_CLI_BIN}" \
	--base-url "${BASE_URL}" \
	--runtime-label "${RUNTIME_LABEL}" \
	--output-dir "${OUTPUT_DIR}"

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

if test "${RUNNER}" = read || { test -z "${RUNNER}" && test -z "${SCENARIO}"; }; then
	set -- "${@}" --require-full-matrix
fi

"${ROOT}/scripts/benchmark.sh" "${@}"
