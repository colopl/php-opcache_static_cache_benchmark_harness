#!/bin/sh

set -eu

USAGE() {
	cat <<'EOF'
Usage: ./benchmark_on_container.sh [--php-repo DIR|URL[#ref]] [--php-ref REF] [--runtime all|fpm|frankenphp] [--runner read|write] [--scenario NAME] [--iterations N] [--warmup N] [--operations N] [--concurrency N] [--key-mode shared|distinct] [--key-space N] [--jit MODE] [--threads N] [--frankenphp-repo DIR|URL] [--frankenphp-ref REF] [--output-dir DIR] [--keep-running] [--no-build]
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

SCRIPT_DIR=$(CDPATH= cd "$(dirname "${0}")" && pwd)
DEFAULT_PHP_REPO=$(CDPATH= cd "${SCRIPT_DIR}/.." && pwd)
PHP_REPO=
PHP_REPO_SOURCE=
PHP_REF=${PHP_REF:-}
PHP_REPO_URL=
PHP_REPO_REF=
PHP_REPO_KIND=
PHP_BUILD_CONTEXT=
BENCHMARK_ROOT=
DOCKERFILE=
RESULTS_DIR=
OUTPUT_DIR=${OUTPUT_DIR:-}
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
KEEP_RUNNING=0
NO_BUILD=0
MAKE_JOBS=${MAKE_JOBS:-0}
DOCKER=${DOCKER:-docker}
FRANKENPHP_REPO=${FRANKENPHP_REPO:-https://github.com/php/frankenphp.git}
FRANKENPHP_REF=${FRANKENPHP_REF:-}
FRANKENPHP_THREADS=${FRANKENPHP_THREADS:-5}
IMAGE_NAME=${IMAGE_NAME:-}
CONTAINER_NAME=${CONTAINER_NAME:-}
CONTAINER_BASE_URL=http://127.0.0.1:8080/index.php
CONTAINER_RESULTS_DIR=/usr/src/php-src/opcache_static_cache_benchmark/results
CONTAINER_ID=
RESULTS_COPIED=0
TEMP_PHP_REPO=

while test "${#}" -gt 0; do
	case "${1}" in
		--php-repo=*|--php-ref=*|--runtime=*|--iterations=*|--runner=*|--scenario=*|--warmup=*|--operations=*|--concurrency=*|--key-mode=*|--key-space=*|--jit=*|--threads=*|--frankenphp-repo=*|--frankenphp-ref=*|--frankenphp-threads=*|--output-dir=*|--image-name=*|--container-name=*|--make-jobs=*)
			OPTION_NAME=${1%%=*}
			OPTION_VALUE=${1#*=}
			shift
			set -- "${OPTION_NAME}" "${OPTION_VALUE}" "${@}"
			;;
	esac

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
		--threads|--frankenphp-threads)
			REQUIRE_VALUE "${1}" "${#}"
			FRANKENPHP_THREADS=${2}
			shift 2
			;;
		--output-dir)
			REQUIRE_VALUE "${1}" "${#}"
			OUTPUT_DIR=${2}
			shift 2
			;;
		--image-name)
			REQUIRE_VALUE "${1}" "${#}"
			IMAGE_NAME=${2}
			shift 2
			;;
		--container-name)
			REQUIRE_VALUE "${1}" "${#}"
			CONTAINER_NAME=${2}
			shift 2
			;;
		--make-jobs)
			REQUIRE_VALUE "${1}" "${#}"
			MAKE_JOBS=${2}
			shift 2
			;;
		--keep-running)
			KEEP_RUNNING=1
			shift
			;;
		--no-build)
			NO_BUILD=1
			shift
			;;
		-h|--help)
			USAGE
			exit 0
			;;
		*)
			case "${1}" in
				--*)
					echo "Unknown argument: ${1}" >&2
					USAGE >&2
					exit 1
					;;
			esac
			if test -z "${PHP_REPO}"; then
				PHP_REPO=${1}
				shift
			else
				echo "Unknown argument: ${1}" >&2
				USAGE >&2
				exit 1
			fi
			;;
	esac
done

PHP_REPO=${PHP_REPO:-${DEFAULT_PHP_REPO}}
PHP_REPO_SOURCE=${PHP_REPO}

RUNTIME_LABEL() {
	case "${1}" in
		fpm)
			printf '%s\n' 'php-fpm + nginx (NTS)'
			;;
		frankenphp)
			printf '%s\n' 'FrankenPHP (ZTS)'
			;;
		*)
			printf '%s\n' "${1}"
			;;
	esac
}

RUN_ALL_RUNTIMES() {
	for CHILD_RUNTIME in frankenphp fpm; do
		CHILD_IMAGE_NAME=${IMAGE_NAME}
		CHILD_CONTAINER_NAME=${CONTAINER_NAME}
		if test -n "${CHILD_IMAGE_NAME}"; then
			CHILD_IMAGE_NAME=${CHILD_IMAGE_NAME}-${CHILD_RUNTIME}
		fi
		if test -n "${CHILD_CONTAINER_NAME}"; then
			CHILD_CONTAINER_NAME=${CHILD_CONTAINER_NAME}-${CHILD_RUNTIME}
		fi

		set -- \
			--php-repo "${PHP_REPO_SOURCE}" \
			--runtime "${CHILD_RUNTIME}" \
			--jit "${JIT}" \
			--frankenphp-repo "${FRANKENPHP_REPO}" \
			--frankenphp-ref "${FRANKENPHP_REF}" \
			--frankenphp-threads "${FRANKENPHP_THREADS}" \
			--output-dir "${OUTPUT_DIR}" \
			--make-jobs "${MAKE_JOBS}"
		if test -n "${PHP_REF}"; then set -- "${@}" --php-ref "${PHP_REF}"; fi
		if test -n "${ITERATIONS}"; then set -- "${@}" --iterations "${ITERATIONS}"; fi
		if test -n "${WARMUP}"; then set -- "${@}" --warmup "${WARMUP}"; fi
		if test -n "${OPERATIONS}"; then set -- "${@}" --operations "${OPERATIONS}"; fi
		if test -n "${RUNNER}"; then set -- "${@}" --runner "${RUNNER}"; fi
		if test -n "${SCENARIO}"; then set -- "${@}" --scenario "${SCENARIO}"; fi
		if test -n "${CONCURRENCY}"; then set -- "${@}" --concurrency "${CONCURRENCY}"; fi
		if test -n "${KEY_MODE}"; then set -- "${@}" --key-mode "${KEY_MODE}"; fi
		if test -n "${KEY_SPACE}"; then set -- "${@}" --key-space "${KEY_SPACE}"; fi

		if test -n "${CHILD_IMAGE_NAME}"; then
			set -- "${@}" --image-name "${CHILD_IMAGE_NAME}"
		fi
		if test -n "${CHILD_CONTAINER_NAME}"; then
			set -- "${@}" --container-name "${CHILD_CONTAINER_NAME}"
		fi
		if test "${KEEP_RUNNING}" -ne 0; then
			set -- "${@}" --keep-running
		fi
		if test "${NO_BUILD}" -ne 0; then
			set -- "${@}" --no-build
		fi

		"${0}" "${@}"
	done
}

if test "${BENCHMARK_RUNTIME}" = all; then
	RUN_ALL_RUNTIMES
	exit 0
fi

IS_GIT_URL() {
	case "${1}" in
		*://*|git@*:*|*.git|*#*)
			return 0
			;;
		*)
			return 1
			;;
	esac
}

PREPARE_GIT_REPO() {
	if test "${PHP_REPO_KIND}" != "git"; then
		return 0
	fi

	if test "${NO_BUILD}" -ne 0; then
		return 0
	fi

	if ! command -v git >/dev/null 2>&1; then
		echo "git is required when --php-repo is a git URL" >&2
		exit 1
	fi

	TEMP_PHP_REPO=$(mktemp -d "${TMPDIR:-/tmp}/opcache-static-cache-php-src.XXXXXX")
	GIT_CLONE_URL=${PHP_REPO_URL}
	GIT_CLONE_REF=${PHP_REPO_REF}

	if test -n "${GIT_CLONE_REF}"; then
		if git clone --depth 1 --branch "${GIT_CLONE_REF}" "${GIT_CLONE_URL}" "${TEMP_PHP_REPO}"; then
			PHP_BUILD_CONTEXT=${TEMP_PHP_REPO}
			DOCKERFILE=${PHP_BUILD_CONTEXT}/opcache_static_cache_benchmark/Dockerfile
			return 0
		fi
		rm -rf "${TEMP_PHP_REPO}"
		TEMP_PHP_REPO=$(mktemp -d "${TMPDIR:-/tmp}/opcache-static-cache-php-src.XXXXXX")
		git clone --depth 1 "${GIT_CLONE_URL}" "${TEMP_PHP_REPO}"
		(CDPATH= cd "${TEMP_PHP_REPO}" && git fetch --depth 1 origin "${GIT_CLONE_REF}" && git checkout --detach FETCH_HEAD)
	else
		git clone --depth 1 "${GIT_CLONE_URL}" "${TEMP_PHP_REPO}"
	fi

	PHP_BUILD_CONTEXT=${TEMP_PHP_REPO}
	DOCKERFILE=${PHP_BUILD_CONTEXT}/opcache_static_cache_benchmark/Dockerfile
}

if test -d "${PHP_REPO_SOURCE}"; then
	PHP_REPO=$(CDPATH= cd "${PHP_REPO_SOURCE}" && pwd)
	PHP_REPO_KIND=local
	PHP_BUILD_CONTEXT=${PHP_REPO}
	BENCHMARK_ROOT=${PHP_REPO}/opcache_static_cache_benchmark
	DOCKERFILE=${BENCHMARK_ROOT}/Dockerfile
	RESULTS_DIR=${OUTPUT_DIR:-${BENCHMARK_ROOT}/results/container-${BENCHMARK_RUNTIME}}
else
	if ! IS_GIT_URL "${PHP_REPO_SOURCE}"; then
		echo "php-src repository must be a local directory or git URL: ${PHP_REPO_SOURCE}" >&2
		exit 1
	fi
	PHP_REPO_KIND=git
	PHP_REPO_URL=${PHP_REPO_SOURCE}
	PHP_REPO_REF=${PHP_REF}
	case "${PHP_REPO_URL}" in
		*#*)
			PHP_REPO_URL_REF=${PHP_REPO_URL##*#}
			PHP_REPO_URL=${PHP_REPO_URL%#*}
			if test -z "${PHP_REPO_REF}"; then
				PHP_REPO_REF=${PHP_REPO_URL_REF}
			fi
			;;
	esac
	if test -z "${PHP_REPO_URL}"; then
		echo "git URL is empty: ${PHP_REPO_SOURCE}" >&2
		exit 1
	fi
	RESULTS_DIR=${OUTPUT_DIR:-${SCRIPT_DIR}/results/container-${BENCHMARK_RUNTIME}}
fi

case "${BENCHMARK_RUNTIME}" in
	fpm|frankenphp)
		;;
	*)
		echo "unsupported runtime: ${BENCHMARK_RUNTIME}" >&2
		exit 1
		;;
esac

if ! command -v "${DOCKER}" >/dev/null 2>&1; then
	echo "docker is required" >&2
	exit 1
fi

if test -z "${IMAGE_NAME}"; then
	IMAGE_NAME=opcache-static-cache-benchmark-${BENCHMARK_RUNTIME}
fi

if test -z "${CONTAINER_NAME}"; then
	CONTAINER_NAME=opcache-static-cache-benchmark-${BENCHMARK_RUNTIME}
fi

PRINT_LOGS() {
	if test -n "${CONTAINER_NAME}"; then
		"${DOCKER}" logs --tail=200 "${CONTAINER_NAME}" || true
	fi
}

COPY_RESULTS() {
	if test "${RESULTS_COPIED}" -ne 0; then
		return 0
	fi

	if test -z "${CONTAINER_NAME}"; then
		return 0
	fi

	mkdir -p "${RESULTS_DIR}"
	"${DOCKER}" cp "${CONTAINER_NAME}:${CONTAINER_RESULTS_DIR}/." "${RESULTS_DIR}" >/dev/null 2>&1 || true
	RESULTS_COPIED=1
}

CLEANUP() {
	EXIT_CODE=${?}
	trap - 0 2 15

	COPY_RESULTS

	if test "${KEEP_RUNNING}" -eq 0; then
		"${DOCKER}" rm -f "${CONTAINER_NAME}" >/dev/null 2>&1 || true
	else
		echo "Container left running: ${CONTAINER_NAME}"
		echo "Stop it with: ${DOCKER} rm -f ${CONTAINER_NAME}"
	fi

	if test -n "${TEMP_PHP_REPO}"; then
		rm -rf "${TEMP_PHP_REPO}"
	fi

	exit "${EXIT_CODE}"
}

WAIT_FOR_HEALTH() {
	HEALTH_CONTAINER_ID=${1}
	HEALTH_ATTEMPT=1

	while test "${HEALTH_ATTEMPT}" -le 120; do
		HEALTH_STATUS=$("${DOCKER}" inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "${HEALTH_CONTAINER_ID}" 2>/dev/null || true)
		case "${HEALTH_STATUS}" in
			healthy)
				return 0
				;;
			unhealthy|exited|dead)
				return 1
				;;
		esac
		sleep 1
		HEALTH_ATTEMPT=$(( ${HEALTH_ATTEMPT} + 1 ))
	done

	return 1
}

trap CLEANUP 0 2 15

mkdir -p "${RESULTS_DIR}"

if test "${NO_BUILD}" -eq 0; then
	PREPARE_GIT_REPO
	if test ! -f "${DOCKERFILE}"; then
		echo "Dockerfile not found: ${DOCKERFILE}" >&2
		exit 1
	fi
	"${DOCKER}" build \
		--build-arg "BENCHMARK_RUNTIME=${BENCHMARK_RUNTIME}" \
		--build-arg "FRANKENPHP_REPO=${FRANKENPHP_REPO}" \
		--build-arg "FRANKENPHP_REF=${FRANKENPHP_REF}" \
		--build-arg "MAKE_JOBS=${MAKE_JOBS}" \
		-t "${IMAGE_NAME}" \
		-f "${DOCKERFILE}" \
		"${PHP_BUILD_CONTEXT}"
fi

"${DOCKER}" rm -f "${CONTAINER_NAME}" >/dev/null 2>&1 || true
CONTAINER_ID=$("${DOCKER}" run \
	-d \
	--name "${CONTAINER_NAME}" \
	-e "BENCHMARK_RUNTIME=${BENCHMARK_RUNTIME}" \
	-e "BENCHMARK_SCENARIO=${SCENARIO}" \
	-e "BENCHMARK_JIT=${JIT}" \
	-e "FRANKENPHP_THREADS=${FRANKENPHP_THREADS}" \
	"${IMAGE_NAME}")

if test -z "${CONTAINER_ID}"; then
	echo "Failed to start benchmark container" >&2
	PRINT_LOGS
	exit 1
fi

if ! WAIT_FOR_HEALTH "${CONTAINER_ID}"; then
	echo "Benchmark container did not become healthy" >&2
	PRINT_LOGS
	exit 1
fi

echo "Benchmark runtime: ${CONTAINER_BASE_URL}?action=describe" >&2
"${DOCKER}" exec "${CONTAINER_NAME}" curl -fsS "${CONTAINER_BASE_URL}?action=describe" >&2
echo >&2

set -- \
	--php /usr/src/php-src/sapi/cli/php \
	--base-url "${CONTAINER_BASE_URL}" \
	--runtime-label "$(RUNTIME_LABEL "${BENCHMARK_RUNTIME}")"
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

if ! "${DOCKER}" exec "${CONTAINER_NAME}" ./scripts/benchmark.sh "${@}"; then
	PRINT_LOGS
	exit 1
fi

COPY_RESULTS

echo
echo "Results copied under: ${RESULTS_DIR}"
