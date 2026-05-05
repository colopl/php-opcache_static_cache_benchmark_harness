#!/bin/sh

set -eu

umask 0000

BENCHMARK_RUNTIME=${BENCHMARK_RUNTIME:-fpm}
BENCHMARK_ROOT=${BENCHMARK_ROOT:-/usr/src/php-src/opcache_static_cache_benchmark}
FRANKENPHP_BIN=${FRANKENPHP_BIN:-/usr/local/bin/frankenphp}
FRANKENPHP_THREADS=${FRANKENPHP_THREADS:-5}
FRANKENPHP_INI_DIR=${FRANKENPHP_INI_DIR:-${BENCHMARK_ROOT}/runtime/frankenphp-container}
PHP_FPM_BIN=${PHP_FPM_BIN:-/usr/src/php-src/sapi/fpm/php-fpm}
PHP_FPM_CONF=${PHP_FPM_CONF:-${BENCHMARK_ROOT}/php-fpm.conf}
PHP_INI_FILE=${PHP_INI_FILE:-${BENCHMARK_ROOT}/php.ini}
NGINX_BIN=${NGINX_BIN:-/usr/sbin/nginx}
NGINX_CONF=${NGINX_CONF:-${BENCHMARK_ROOT}/nginx.container.conf}
APCU_EXTENSION=${APCU_EXTENSION:-}
APCU_SHM_SIZE=${APCU_SHM_SIZE:-128M}
BENCHMARK_JIT=${BENCHMARK_JIT:-off}
BENCHMARK_JIT_BUFFER_SIZE=${BENCHMARK_JIT_BUFFER_SIZE:-64M}
SERVER_NAME=${SERVER_NAME:-http://127.0.0.1:8080}
FRANKENPHP_PID=
NGINX_PID=
PHP_FPM_PID=

mkdir -p "${BENCHMARK_ROOT}/results"

WRITE_FRANKENPHP_INI() {
	mkdir -p "${FRANKENPHP_INI_DIR}"
	cat > "${FRANKENPHP_INI_DIR}/php.ini" <<EOF
display_errors=1
display_startup_errors=1
log_errors=0
error_reporting=E_ALL
expose_php=0
memory_limit=512M
max_execution_time=0

opcache.enable=1
opcache.enable_cli=0
opcache.validate_timestamps=0
opcache.memory_consumption=128
opcache.max_accelerated_files=20000
opcache.static_cache.volatile_size_mb=128
opcache.static_cache.persistent_size_mb=128
EOF

	if test "${BENCHMARK_JIT}" != off; then
		cat >> "${FRANKENPHP_INI_DIR}/php.ini" <<EOF

opcache.jit_buffer_size=${BENCHMARK_JIT_BUFFER_SIZE}
opcache.jit=${BENCHMARK_JIT}
EOF
	fi

	if test -n "${APCU_EXTENSION}"; then
		if test -f "${APCU_EXTENSION}"; then
			cat >> "${FRANKENPHP_INI_DIR}/php.ini" <<EOF

extension=${APCU_EXTENSION}
apc.enabled=1
apc.shm_size=${APCU_SHM_SIZE}
EOF
		fi
	fi
}

CLEANUP() {
	EXIT_CODE=${?}
	trap - 0 2 15

	if test -n "${FRANKENPHP_PID}"; then
		kill "${FRANKENPHP_PID}" >/dev/null 2>&1 || true
		wait "${FRANKENPHP_PID}" >/dev/null 2>&1 || true
	fi

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

WAIT_FOR_FPM_PROCESS_EXIT() {
	while :; do
		if ! kill -0 "${PHP_FPM_PID}" >/dev/null 2>&1; then
			wait "${PHP_FPM_PID}"
			return "${?}"
		fi
		if ! kill -0 "${NGINX_PID}" >/dev/null 2>&1; then
			wait "${NGINX_PID}"
			return "${?}"
		fi
		sleep 1
	done
}

trap CLEANUP 0 2 15

case "${BENCHMARK_RUNTIME}" in
	fpm)
		if test -n "${APCU_EXTENSION}"; then
			if test -f "${APCU_EXTENSION}"; then
				set -- \
					"${PHP_FPM_BIN}" \
					-n \
					-d "extension=${APCU_EXTENSION}" \
					-d apc.enabled=1 \
					-d "apc.shm_size=${APCU_SHM_SIZE}"
				if test "${BENCHMARK_JIT}" != off; then
					set -- "${@}" \
						-d "opcache.jit_buffer_size=${BENCHMARK_JIT_BUFFER_SIZE}" \
						-d "opcache.jit=${BENCHMARK_JIT}"
				fi
				set -- "${@}" -y "${PHP_FPM_CONF}" -c "${PHP_INI_FILE}"
				"${@}" &
				PHP_FPM_PID=${!}
			else
				set -- "${PHP_FPM_BIN}" -n
				if test "${BENCHMARK_JIT}" != off; then
					set -- "${@}" \
						-d "opcache.jit_buffer_size=${BENCHMARK_JIT_BUFFER_SIZE}" \
						-d "opcache.jit=${BENCHMARK_JIT}"
				fi
				set -- "${@}" -y "${PHP_FPM_CONF}" -c "${PHP_INI_FILE}"
				"${@}" &
				PHP_FPM_PID=${!}
			fi
		else
			set -- "${PHP_FPM_BIN}" -n
			if test "${BENCHMARK_JIT}" != off; then
				set -- "${@}" \
					-d "opcache.jit_buffer_size=${BENCHMARK_JIT_BUFFER_SIZE}" \
					-d "opcache.jit=${BENCHMARK_JIT}"
			fi
			set -- "${@}" -y "${PHP_FPM_CONF}" -c "${PHP_INI_FILE}"
			"${@}" &
			PHP_FPM_PID=${!}
		fi

		"${NGINX_BIN}" -c "${NGINX_CONF}" &
		NGINX_PID=${!}

		WAIT_FOR_FPM_PROCESS_EXIT
		;;
	frankenphp)
		WRITE_FRANKENPHP_INI
		PHPRC="${FRANKENPHP_INI_DIR}" \
		BENCH_THREADS="${FRANKENPHP_THREADS}" \
		OPCACHE_VOLATILE_CACHE_BENCHMARK_ROOT="${BENCHMARK_ROOT}" \
		SERVER_NAME="${SERVER_NAME}" \
		"${FRANKENPHP_BIN}" run --config "${BENCHMARK_ROOT}/Caddyfile" &
		FRANKENPHP_PID=${!}

		wait "${FRANKENPHP_PID}"
		;;
	*)
		echo "Unsupported BENCHMARK_RUNTIME: ${BENCHMARK_RUNTIME}" >&2
		exit 1
		;;
esac
