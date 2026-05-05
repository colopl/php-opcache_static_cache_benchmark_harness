#!/bin/sh

set -eu

USAGE() {
	cat <<'EOF'
Usage: ./scripts/benchmark_fpm.sh --php-fpm /path/to/php-fpm --apcu-so /path/to/apcu.so [--php-cli /path/to/php] [--nginx-bin /usr/sbin/nginx] [--base-url http://127.0.0.1:8080/index.php] [--runner read|write] [--scenario NAME] [--iterations N] [--warmup N] [--operations N] [--concurrency N] [--key-mode shared|distinct] [--key-space N] [--jit MODE] [--output-dir DIR]
EOF
}

REQUIRE_VALUE() {
	if test "${2}" -lt 2; then
		echo "${1} requires a value" >&2
		exit 1
	fi
}

ROOT=$(CDPATH= cd "$(dirname "${0}")/.." && pwd)
PHP_FPM_BIN=
PHP_CLI_BIN=
APCU_SO=
NGINX_BIN=${NGINX_BIN:-/usr/sbin/nginx}
BASE_URL=${BASE_URL:-http://127.0.0.1:8080/index.php}
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
NGINX_PID=
PHP_FPM_PID=

while test "${#}" -gt 0; do
	case "${1}" in
		--php-fpm)
			REQUIRE_VALUE "${1}" "${#}"
			PHP_FPM_BIN=${2}
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
		--nginx-bin)
			REQUIRE_VALUE "${1}" "${#}"
			NGINX_BIN=${2}
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

if test -z "${PHP_FPM_BIN}"; then
	USAGE >&2
	exit 1
fi
if test -z "${APCU_SO}"; then
	USAGE >&2
	exit 1
fi
if test ! -x "${PHP_FPM_BIN}"; then
	echo "php-fpm binary is not executable: ${PHP_FPM_BIN}" >&2
	exit 1
fi
if test -z "${PHP_CLI_BIN}"; then
	PHP_SAPI_DIR=$(dirname "$(dirname "${PHP_FPM_BIN}")")
	PHP_CLI_BIN=${PHP_SAPI_DIR}/cli/php
fi
if test ! -x "${PHP_CLI_BIN}"; then
	echo "PHP CLI binary is not executable: ${PHP_CLI_BIN}" >&2
	exit 1
fi
if test ! -f "${APCU_SO}"; then
	echo "APCu shared module not found: ${APCU_SO}" >&2
	exit 1
fi
if test ! -x "${NGINX_BIN}"; then
	echo "nginx binary is not executable: ${NGINX_BIN}" >&2
	exit 1
fi

CLEANUP() {
	EXIT_CODE=${?}
	trap - 0 2 15

	if test -n "${NGINX_PID}"; then
		kill "${NGINX_PID}" >/dev/null 2>&1 || true
		wait "${NGINX_PID}" >/dev/null 2>&1 || true
	fi

	if test -n "${PHP_FPM_PID}"; then
		kill "${PHP_FPM_PID}" >/dev/null 2>&1 || true
		wait "${PHP_FPM_PID}" >/dev/null 2>&1 || true
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
	ASSERT_PORT_FREE 9000 "php-fpm"
}

trap CLEANUP 0 2 15

cd "${ROOT}"
ASSERT_RUNTIME_PORTS_FREE

set -- \
	"${PHP_FPM_BIN}" \
	-n \
	-d "extension=${APCU_SO}" \
	-d apc.enabled=1 \
	-d "apc.shm_size=${APC_SHM_SIZE}"

if test "${JIT}" != off; then
	set -- "${@}" \
		-d "opcache.jit_buffer_size=${JIT_BUFFER_SIZE}" \
		-d "opcache.jit=${JIT}"
fi

set -- "${@}" \
	-y "${ROOT}/php-fpm.conf" \
	-c "${ROOT}/php.ini"

"${@}" &
PHP_FPM_PID=${!}

"${NGINX_BIN}" -p "${ROOT}" -c "${ROOT}/nginx.conf" &
NGINX_PID=${!}

if ! WAIT_FOR_RUNTIME; then
	echo "php-fpm benchmark runtime did not become ready: ${BASE_URL}?action=describe" >&2
	exit 1
fi

RUNTIME_LABEL='php-fpm + nginx (NTS)'
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
