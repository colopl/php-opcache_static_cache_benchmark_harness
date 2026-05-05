# syntax=docker/dockerfile:1.7

FROM debian:trixie

ARG DEBIAN_FRONTEND=noninteractive
ARG MAKE_JOBS=0
ARG BENCHMARK_RUNTIME=fpm
ARG FRANKENPHP_REPO=https://github.com/php/frankenphp.git
ARG FRANKENPHP_REF=main

RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		autoconf \
		bison \
		build-essential \
		bash \
		ca-certificates \
		composer \
		gcc \
		curl \
		git \
		golang-go \
		libtool \
		libxml2-dev \
		nginx \
		pkg-config \
		re2c \
	&& rm -rf /var/lib/apt/lists/*

COPY . /usr/src/php-src/

WORKDIR /usr/src/php-src

# The benchmark FrankenPHP path builds a custom ZTS SAPI. Disable Zend signals
# unless that SAPI calls zend_signal_startup() before sapi_startup().
RUN ./buildconf --force \
 && if test "$BENCHMARK_RUNTIME" = "frankenphp"; then \
		./configure \
			--disable-all \
			--enable-cli \
			--enable-pcntl \
			--enable-session \
			--enable-embed=static \
			--enable-zend-max-execution-timers \
			--enable-zts \
			--disable-zend-signals; \
	else \
		./configure \
			--disable-all \
			--enable-cli \
			--enable-fpm \
			--enable-pcntl \
			--enable-session; \
	fi \
	&& if test "$MAKE_JOBS" = "0"; then MAKE_JOBS="$(nproc)"; fi \
	&& if test "$BENCHMARK_RUNTIME" = "frankenphp"; then \
		make -j"$MAKE_JOBS" \
			libphp.la \
			sapi/cli/php \
			scripts/phpize \
			scripts/php-config; \
	else \
		make -j"$MAKE_JOBS" \
			sapi/cli/php \
			sapi/fpm/php-fpm \
			scripts/phpize \
			scripts/php-config; \
	fi

WORKDIR /usr/src/php-src/opcache_static_cache_benchmark

RUN sh ./scripts/install_dependencies.sh

RUN ./scripts/build_apcu.sh \
		/usr/src/php-src/scripts/phpize \
		/usr/src/php-src/scripts/php-config \
		/opt/opcache-static-cache-benchmark/apcu

RUN if test "$BENCHMARK_RUNTIME" = "frankenphp"; then \
		PHP_CONFIG=/usr/src/php-src/scripts/php-config; \
		PHP_EMBED_LIB=/usr/src/php-src/.libs/$($PHP_CONFIG --lib-embed); \
		PHP_EXTRA_LIBS=$(printf '%s\n' "$($PHP_CONFIG --libs)" | sed -E 's/(^|[[:space:]])-lphp[^[:space:]]*//g'); \
		PHP_ZTS_CFLAGS='-DZTS -DZEND_ENABLE_STATIC_TSRMLS_CACHE=1 -DZEND_MAX_EXECUTION_TIMERS -DHAVE_CONFIG_H'; \
		if test -d "$FRANKENPHP_REPO"; then \
			cp -R "$FRANKENPHP_REPO" /usr/src/frankenphp; \
			if test -n "$FRANKENPHP_REF"; then git -C /usr/src/frankenphp checkout "$FRANKENPHP_REF"; fi; \
		else \
			if test -n "$FRANKENPHP_REF"; then \
				if ! git clone --depth 1 --branch "$FRANKENPHP_REF" "$FRANKENPHP_REPO" /usr/src/frankenphp; then \
					git clone --depth 1 "$FRANKENPHP_REPO" /usr/src/frankenphp; \
					cd /usr/src/frankenphp; \
					git fetch --depth 1 origin "$FRANKENPHP_REF"; \
					git checkout --detach FETCH_HEAD; \
				fi; \
			else \
				git clone --depth 1 "$FRANKENPHP_REPO" /usr/src/frankenphp; \
			fi; \
		fi; \
		grep -RIl --include='*.go' '#cgo .*LDFLAGS:.*-lphp' /usr/src/frankenphp \
			| xargs -r sed -i 's/ -lphp//g'; \
		if grep -RIn --include='*.go' '#cgo .*LDFLAGS:.*-lphp' /usr/src/frankenphp; then \
			echo 'FrankenPHP still references -lphp in cgo LDFLAGS after compatibility patch' >&2; \
			exit 1; \
		fi; \
		if test ! -f "$PHP_EMBED_LIB"; then \
			echo "PHP embed library not found: $PHP_EMBED_LIB" >&2; \
			exit 1; \
		fi; \
		if printf '%s\n' "$PHP_EXTRA_LIBS" | grep -Eq '(^|[[:space:]])-lphp[^[:space:]]*([[:space:]]|$)'; then \
			echo "php-config --libs still contains libphp after sanitization: $PHP_EXTRA_LIBS" >&2; \
			exit 1; \
		fi; \
		cd /usr/src/frankenphp/caddy/frankenphp; \
		CGO_ENABLED=1 \
		GOFLAGS="${GOFLAGS:-} -tags=nowatcher,nobadger,nobrotli,nomysql,nopgx" \
		CGO_CFLAGS="$($PHP_CONFIG --includes) $PHP_ZTS_CFLAGS -I/usr/src/php-src -I/usr/src/php-src/main -I/usr/src/php-src/TSRM -I/usr/src/php-src/Zend -I/usr/src/php-src/ext -I/usr/src/php-src/ext/date/lib" \
		CGO_LDFLAGS="$PHP_EMBED_LIB $PHP_EXTRA_LIBS -Wl,--export-dynamic" \
		go build -o /usr/local/bin/frankenphp; \
	fi

ENV APCU_EXTENSION=/opt/opcache-static-cache-benchmark/apcu/apcu.so
ENV BENCHMARK_RUNTIME=${BENCHMARK_RUNTIME}
ENV BENCHMARK_ROOT=/usr/src/php-src/opcache_static_cache_benchmark
ENV FRANKENPHP_BIN=/usr/local/bin/frankenphp
ENV FRANKENPHP_THREADS=5
ENV NGINX_BIN=/usr/sbin/nginx
ENV PHP_FPM_BIN=/usr/src/php-src/sapi/fpm/php-fpm

EXPOSE 8080

HEALTHCHECK --interval=5s --timeout=3s --retries=24 CMD curl -fsS http://127.0.0.1:8080/index.php?action=describe >/dev/null || exit 1

ENTRYPOINT ["/usr/bin/env", "sh", "/usr/src/php-src/opcache_static_cache_benchmark/scripts/container-entrypoint.sh"]
