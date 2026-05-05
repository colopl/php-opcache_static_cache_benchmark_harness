#!/bin/sh

set -eu

SCRIPT_DIR=$(CDPATH= cd "$(dirname "${0}")/.." && pwd)
COMPOSER_PHAR=${COMPOSER_PHAR:-${TMPDIR:-/tmp}/opcache-static-cache-benchmark-composer.phar}

REQUIRE_COMMAND() {
	COMMAND_NAME=${1}
	if ! command -v "${COMMAND_NAME}" >/dev/null 2>&1; then
		echo "${COMMAND_NAME} is required" >&2
		exit 1
	fi
}

INSTALL_WITH_COMPOSER() {
	cd "${SCRIPT_DIR}"
	"${@}" install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
}

if test -f "${SCRIPT_DIR}/vendor/autoload.php"; then
	exit 0
fi

if test -n "${COMPOSER_BIN:-}"; then
	INSTALL_WITH_COMPOSER "${COMPOSER_BIN}"
	exit 0
fi

if command -v composer >/dev/null 2>&1; then
	INSTALL_WITH_COMPOSER composer
	exit 0
fi

REQUIRE_COMMAND php
REQUIRE_COMMAND curl

if test ! -f "${COMPOSER_PHAR}"; then
	curl -fsSL https://getcomposer.org/download/latest-stable/composer.phar -o "${COMPOSER_PHAR}"
fi

INSTALL_WITH_COMPOSER php "${COMPOSER_PHAR}"
