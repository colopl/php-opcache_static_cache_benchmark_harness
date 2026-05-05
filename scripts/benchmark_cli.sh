#!/bin/sh

set -eu

USAGE() {
	cat <<'EOF'
Usage: ./scripts/benchmark_cli.sh --current-nts-php PHP --current-zts-php PHP [--base-nts-php PHP --base-zts-php PHP] [--source-root DIR] [--mode all|startup|zend] [--runs N] [--output-dir DIR] [--runner-php PHP]
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
RUNNER_PHP=${RUNNER_PHP:-php}
CURRENT_NTS_PHP=${CURRENT_NTS_PHP:-}
CURRENT_ZTS_PHP=${CURRENT_ZTS_PHP:-}
BASE_NTS_PHP=${BASE_NTS_PHP:-}
BASE_ZTS_PHP=${BASE_ZTS_PHP:-}
SOURCE_ROOT=${SOURCE_ROOT:-$(CDPATH= cd "${ROOT}/.." && pwd)}
MODE=${MODE:-all}
RUNS=${RUNS:-10}
OUTPUT_DIR=${OUTPUT_DIR:-${ROOT}/results}

while test "${#}" -gt 0; do
	case "${1}" in
		--runner-php)
			REQUIRE_VALUE "${1}" "${#}"
			RUNNER_PHP=${2}
			shift 2
			;;
		--current-nts-php)
			REQUIRE_VALUE "${1}" "${#}"
			CURRENT_NTS_PHP=${2}
			shift 2
			;;
		--current-zts-php)
			REQUIRE_VALUE "${1}" "${#}"
			CURRENT_ZTS_PHP=${2}
			shift 2
			;;
		--base-nts-php)
			REQUIRE_VALUE "${1}" "${#}"
			BASE_NTS_PHP=${2}
			shift 2
			;;
		--base-zts-php)
			REQUIRE_VALUE "${1}" "${#}"
			BASE_ZTS_PHP=${2}
			shift 2
			;;
		--source-root)
			REQUIRE_VALUE "${1}" "${#}"
			SOURCE_ROOT=${2}
			shift 2
			;;
		--mode)
			REQUIRE_VALUE "${1}" "${#}"
			MODE=${2}
			shift 2
			;;
		--runs)
			REQUIRE_VALUE "${1}" "${#}"
			RUNS=${2}
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

set -- \
	"${ROOT}/scripts/BenchmarkCliRunner.php" \
	--current-nts-php "${CURRENT_NTS_PHP}" \
	--current-zts-php "${CURRENT_ZTS_PHP}" \
	--source-root "${SOURCE_ROOT}" \
	--mode "${MODE}" \
	--runs "${RUNS}" \
	--output-dir "${OUTPUT_DIR}"

if test -n "${BASE_NTS_PHP}"; then
	set -- "${@}" --base-nts-php "${BASE_NTS_PHP}"
fi
if test -n "${BASE_ZTS_PHP}"; then
	set -- "${@}" --base-zts-php "${BASE_ZTS_PHP}"
fi

exec "${RUNNER_PHP}" "${@}"
