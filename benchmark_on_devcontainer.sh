#!/bin/sh

set -eu

USAGE() {
	cat <<'EOF'
Usage: ./benchmark_on_devcontainer.sh [--php-repo DIR|URL] [--php-ref REF] [--runtime all|fpm|frankenphp] [--runner read|write] [--scenario NAME] [--iterations N] [--warmup N] [--operations N] [--concurrency N] [--key-mode shared|distinct] [--key-space N] [--jit MODE] [--threads N] [--frankenphp-repo DIR|URL] [--frankenphp-ref REF] [--make-jobs N] [--skip-rebuild] [--build-dir DIR] [--output-dir DIR]
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

REQUIRE_COMMAND() {
	COMMAND_NAME=${1}
	if ! command -v "${COMMAND_NAME}" >/dev/null 2>&1; then
		echo "${COMMAND_NAME} is required" >&2
		exit 1
	fi
}

REQUIRE_EXECUTABLE_PATH() {
	PATH_NAME=${1}
	DESCRIPTION=${2}
	if test ! -x "${PATH_NAME}"; then
		echo "${DESCRIPTION} is missing or not executable for --skip-rebuild: ${PATH_NAME}" >&2
		exit 1
	fi
}

REQUIRE_FILE_PATH() {
	PATH_NAME=${1}
	DESCRIPTION=${2}
	if test ! -f "${PATH_NAME}"; then
		echo "${DESCRIPTION} is missing for --skip-rebuild: ${PATH_NAME}" >&2
		exit 1
	fi
}

SCRIPT_DIR=$(CDPATH= cd "$(dirname "${0}")" && pwd)
DEFAULT_SOURCE_ROOT=$(CDPATH= cd "${SCRIPT_DIR}/.." && pwd)
PHP_REPO=${PHP_REPO:-${DEFAULT_SOURCE_ROOT}}
PHP_REF=${PHP_REF:-}
SOURCE_ROOT=
BENCHMARK_RUNTIME=${BENCHMARK_RUNTIME:-all}
RUNNER=${RUNNER:-}
SCENARIO=${SCENARIO:-}
ITERATIONS=${ITERATIONS:-}
WARMUP=${WARMUP:-}
OPERATIONS=${OPERATIONS:-}
CONCURRENCY=${CONCURRENCY:-}
KEY_MODE=${KEY_MODE:-}
KEY_SPACE=${KEY_SPACE:-}
JIT=${JIT:-off}
FRANKENPHP_THREADS=${FRANKENPHP_THREADS:-5}
FRANKENPHP_REPO=${FRANKENPHP_REPO:-https://github.com/php/frankenphp.git}
FRANKENPHP_REF=${FRANKENPHP_REF:-}
MAKE_JOBS=${MAKE_JOBS:-0}
NGINX_BIN=${NGINX_BIN:-/usr/sbin/nginx}
BUILD_PARENT=${OPCACHE_STATIC_CACHE_BENCHMARK_BUILD_DIR:-${TMPDIR:-/tmp}/opcache-static-cache-benchmark-devcontainer}
OUTPUT_DIR=${OUTPUT_DIR:-${SCRIPT_DIR}/results}
SKIP_REBUILD=${SKIP_REBUILD:-0}
NTS_BUILD_DIR=
ZTS_BUILD_DIR=
APCU_NTS_DIR=
APCU_ZTS_DIR=
FRANKENPHP_SRC=
FRANKENPHP_BIN=
PHP_SRC_CLONE=

while test "${#}" -gt 0; do
	case "${1}" in
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
		--runtime)
			REQUIRE_VALUE "${1}" "${#}"
			BENCHMARK_RUNTIME=${2}
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
		--threads)
			REQUIRE_VALUE "${1}" "${#}"
			FRANKENPHP_THREADS=${2}
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
		--make-jobs)
			REQUIRE_VALUE "${1}" "${#}"
			MAKE_JOBS=${2}
			shift 2
			;;
		--skip-rebuild)
			SKIP_REBUILD=1
			shift
			;;
		--build-dir)
			REQUIRE_VALUE "${1}" "${#}"
			BUILD_PARENT=${2}
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

case "${BENCHMARK_RUNTIME}" in
	all|fpm|frankenphp)
		;;
	*)
		echo "unsupported runtime: ${BENCHMARK_RUNTIME}" >&2
		exit 1
		;;
esac

if test "${MAKE_JOBS}" = 0; then
	MAKE_JOBS=$(getconf _NPROCESSORS_ONLN 2>/dev/null || echo 1)
fi

CLEANUP() {
	EXIT_CODE=${?}
	trap - 0 2 15

	exit "${EXIT_CODE}"
}

trap CLEANUP 0 2 15

PREPARE_BUILD_ROOT() {
	if test "${SKIP_REBUILD}" -eq 0; then
		rm -rf "${BUILD_PARENT}"
	fi

	NTS_BUILD_DIR=${BUILD_PARENT}/php-nts
	ZTS_BUILD_DIR=${BUILD_PARENT}/php-zts
	APCU_NTS_DIR=${BUILD_PARENT}/apcu-nts
	APCU_ZTS_DIR=${BUILD_PARENT}/apcu-zts
	FRANKENPHP_SRC=${BUILD_PARENT}/frankenphp-src
	FRANKENPHP_BIN=${BUILD_PARENT}/frankenphp
	PHP_SRC_CLONE=${BUILD_PARENT}/php-src-source
	mkdir -p "${NTS_BUILD_DIR}" "${ZTS_BUILD_DIR}"
}

PREPARE_SOURCE_ROOT() {
	if test -d "${PHP_REPO}"; then
		SOURCE_ROOT=$(CDPATH= cd "${PHP_REPO}" && pwd)
		return 0
	fi

	REQUIRE_COMMAND git
	if test -n "${PHP_REF}"; then
		if git clone --depth 1 --branch "${PHP_REF}" "${PHP_REPO}" "${PHP_SRC_CLONE}"; then
			SOURCE_ROOT=${PHP_SRC_CLONE}
			return 0
		fi
		git clone --depth 1 "${PHP_REPO}" "${PHP_SRC_CLONE}"
		(cd "${PHP_SRC_CLONE}" && git fetch --depth 1 origin "${PHP_REF}" && git checkout --detach FETCH_HEAD)
	else
		git clone --depth 1 "${PHP_REPO}" "${PHP_SRC_CLONE}"
	fi
	SOURCE_ROOT=${PHP_SRC_CLONE}
}

ENSURE_NTS_RUNTIME() {
	if test "${SKIP_REBUILD}" -ne 0; then
		REQUIRE_EXECUTABLE_PATH "${NTS_BUILD_DIR}/sapi/fpm/php-fpm" 'NTS php-fpm binary'
		REQUIRE_FILE_PATH "${APCU_NTS_DIR}/apcu.so" 'NTS APCu module'
		return 0
	fi

	BUILD_NTS_RUNTIME
}

ENSURE_ZTS_RUNTIME() {
	if test "${SKIP_REBUILD}" -ne 0; then
		REQUIRE_EXECUTABLE_PATH "${ZTS_BUILD_DIR}/sapi/cli/php" 'ZTS PHP CLI binary'
		REQUIRE_FILE_PATH "${APCU_ZTS_DIR}/apcu.so" 'ZTS APCu module'
		return 0
	fi

	BUILD_ZTS_RUNTIME
}

ENSURE_FRANKENPHP() {
	if test "${SKIP_REBUILD}" -ne 0; then
		REQUIRE_EXECUTABLE_PATH "${FRANKENPHP_BIN}" 'FrankenPHP binary'
		return 0
	fi

	BUILD_FRANKENPHP
}

BUILD_NTS_RUNTIME() {
	REQUIRE_COMMAND make
	REQUIRE_COMMAND curl
	REQUIRE_COMMAND git

	cd "${NTS_BUILD_DIR}"
	"${SOURCE_ROOT}/configure" \
		--disable-all \
		--enable-cli \
		--enable-fpm \
		--enable-pcntl \
		--enable-session
	make -j"${MAKE_JOBS}" sapi/cli/php sapi/fpm/php-fpm scripts/phpize scripts/php-config
	"${SCRIPT_DIR}/scripts/build_apcu.sh" \
		"${NTS_BUILD_DIR}/scripts/phpize" \
		"${NTS_BUILD_DIR}/scripts/php-config" \
		"${APCU_NTS_DIR}" >/dev/null
}

BUILD_ZTS_RUNTIME() {
	REQUIRE_COMMAND make
	REQUIRE_COMMAND curl
	REQUIRE_COMMAND git
	REQUIRE_COMMAND go

	cd "${ZTS_BUILD_DIR}"
	"${SOURCE_ROOT}/configure" \
		--disable-all \
		--enable-cli \
		--enable-pcntl \
		--enable-session \
		--enable-embed=static \
		--enable-zend-max-execution-timers \
		--enable-zts \
		--disable-zend-signals
	make -j"${MAKE_JOBS}" libphp.la sapi/cli/php scripts/phpize scripts/php-config
	"${SCRIPT_DIR}/scripts/build_apcu.sh" \
		"${ZTS_BUILD_DIR}/scripts/phpize" \
		"${ZTS_BUILD_DIR}/scripts/php-config" \
		"${APCU_ZTS_DIR}" >/dev/null
}

BUILD_FRANKENPHP() {
	PHP_CONFIG=${ZTS_BUILD_DIR}/scripts/php-config
	PHP_EMBED_LIB=${ZTS_BUILD_DIR}/.libs/$(${PHP_CONFIG} --lib-embed)
	PHP_EXTRA_LIBS=$(printf '%s\n' "$(${PHP_CONFIG} --libs)" | sed -E 's/(^|[[:space:]])-lphp[^[:space:]]*//g')
	PHP_ZTS_CFLAGS='-DZTS -DZEND_ENABLE_STATIC_TSRMLS_CACHE=1 -DZEND_MAX_EXECUTION_TIMERS -DHAVE_CONFIG_H'

	if test -d "${FRANKENPHP_REPO}"; then
		cp -R "${FRANKENPHP_REPO}" "${FRANKENPHP_SRC}"
		if test -n "${FRANKENPHP_REF}"; then
			git -C "${FRANKENPHP_SRC}" checkout "${FRANKENPHP_REF}"
		fi
	else
		if test -n "${FRANKENPHP_REF}"; then
			if ! git clone --depth 1 --branch "${FRANKENPHP_REF}" "${FRANKENPHP_REPO}" "${FRANKENPHP_SRC}"; then
				git clone --depth 1 "${FRANKENPHP_REPO}" "${FRANKENPHP_SRC}"
				(cd "${FRANKENPHP_SRC}" && git fetch --depth 1 origin "${FRANKENPHP_REF}" && git checkout --detach FETCH_HEAD)
			fi
		else
			git clone --depth 1 "${FRANKENPHP_REPO}" "${FRANKENPHP_SRC}"
		fi
	fi
	grep -RIl --include='*.go' '#cgo .*LDFLAGS:.*-lphp' "${FRANKENPHP_SRC}" \
		| xargs -r sed -i 's/ -lphp//g'
	if grep -RIn --include='*.go' '#cgo .*LDFLAGS:.*-lphp' "${FRANKENPHP_SRC}"; then
		echo 'FrankenPHP still references -lphp in cgo LDFLAGS after compatibility patch' >&2
		exit 1
	fi
	if test ! -f "${PHP_EMBED_LIB}"; then
		echo "PHP embed library not found: ${PHP_EMBED_LIB}" >&2
		exit 1
	fi
	if printf '%s\n' "${PHP_EXTRA_LIBS}" | grep -Eq '(^|[[:space:]])-lphp[^[:space:]]*([[:space:]]|$)'; then
		echo "php-config --libs still contains libphp after sanitization: ${PHP_EXTRA_LIBS}" >&2
		exit 1
	fi

	cd "${FRANKENPHP_SRC}/caddy/frankenphp"
	CGO_ENABLED=1 \
	GOFLAGS="${GOFLAGS:-} -tags=nowatcher,nobadger,nobrotli,nomysql,nopgx" \
	CGO_CFLAGS="$("${PHP_CONFIG}" --includes) ${PHP_ZTS_CFLAGS} -I${SOURCE_ROOT} -I${SOURCE_ROOT}/main -I${SOURCE_ROOT}/TSRM -I${SOURCE_ROOT}/Zend -I${SOURCE_ROOT}/ext -I${SOURCE_ROOT}/ext/date/lib -I${ZTS_BUILD_DIR} -I${ZTS_BUILD_DIR}/main -I${ZTS_BUILD_DIR}/Zend" \
	CGO_LDFLAGS="${PHP_EMBED_LIB} ${PHP_EXTRA_LIBS} -Wl,--export-dynamic" \
	go build -o "${FRANKENPHP_BIN}"
}

RUN_FPM_BENCHMARK() {
	if test ! -x "${NGINX_BIN}"; then
		echo "nginx binary is not executable: ${NGINX_BIN}; set NGINX_BIN=/path/to/nginx" >&2
		exit 1
	fi

	ENSURE_NTS_RUNTIME
	cd "${SCRIPT_DIR}"
	set -- \
		--php-fpm "${NTS_BUILD_DIR}/sapi/fpm/php-fpm" \
		--php-cli "${NTS_BUILD_DIR}/sapi/cli/php" \
		--apcu-so "${APCU_NTS_DIR}/apcu.so" \
		--nginx-bin "${NGINX_BIN}" \
		--output-dir "${OUTPUT_DIR}" \
		--jit "${JIT}"
	if test -n "${ITERATIONS}"; then set -- "${@}" --iterations "${ITERATIONS}"; fi
	if test -n "${WARMUP}"; then set -- "${@}" --warmup "${WARMUP}"; fi
	if test -n "${OPERATIONS}"; then set -- "${@}" --operations "${OPERATIONS}"; fi
	if test -n "${RUNNER}"; then set -- "${@}" --runner "${RUNNER}"; fi
	if test -n "${SCENARIO}"; then set -- "${@}" --scenario "${SCENARIO}"; fi
	if test -n "${CONCURRENCY}"; then set -- "${@}" --concurrency "${CONCURRENCY}"; fi
	if test -n "${KEY_MODE}"; then set -- "${@}" --key-mode "${KEY_MODE}"; fi
	if test -n "${KEY_SPACE}"; then set -- "${@}" --key-space "${KEY_SPACE}"; fi
	"${SCRIPT_DIR}/scripts/benchmark_fpm.sh" "${@}"
}

RUN_FRANKENPHP_BENCHMARK() {
	ENSURE_ZTS_RUNTIME
	ENSURE_FRANKENPHP
	cd "${SCRIPT_DIR}"
	set -- \
		--frankenphp "${FRANKENPHP_BIN}" \
		--php-cli "${ZTS_BUILD_DIR}/sapi/cli/php" \
		--apcu-so "${APCU_ZTS_DIR}/apcu.so" \
		--threads "${FRANKENPHP_THREADS}" \
		--output-dir "${OUTPUT_DIR}" \
		--jit "${JIT}"
	if test -n "${ITERATIONS}"; then set -- "${@}" --iterations "${ITERATIONS}"; fi
	if test -n "${WARMUP}"; then set -- "${@}" --warmup "${WARMUP}"; fi
	if test -n "${OPERATIONS}"; then set -- "${@}" --operations "${OPERATIONS}"; fi
	if test -n "${RUNNER}"; then set -- "${@}" --runner "${RUNNER}"; fi
	if test -n "${SCENARIO}"; then set -- "${@}" --scenario "${SCENARIO}"; fi
	if test -n "${CONCURRENCY}"; then set -- "${@}" --concurrency "${CONCURRENCY}"; fi
	if test -n "${KEY_MODE}"; then set -- "${@}" --key-mode "${KEY_MODE}"; fi
	if test -n "${KEY_SPACE}"; then set -- "${@}" --key-space "${KEY_SPACE}"; fi
	"${SCRIPT_DIR}/scripts/benchmark_frankenphp.sh" "${@}"
}

PREPARE_BUILD_ROOT
PREPARE_SOURCE_ROOT
sh "${SCRIPT_DIR}/scripts/install_dependencies.sh"

case "${BENCHMARK_RUNTIME}" in
	all)
		RUN_FRANKENPHP_BENCHMARK
		RUN_FPM_BENCHMARK
		;;
	frankenphp)
		RUN_FRANKENPHP_BENCHMARK
		;;
	fpm)
		RUN_FPM_BENCHMARK
		;;
esac
