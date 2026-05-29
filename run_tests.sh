#!/usr/bin/env bash

set -euo pipefail

PHPUNIT_ARGS=(tests --testdox --colors=always --display-deprecations --display-phpunit-deprecations --fail-on-deprecation --fail-on-phpunit-deprecation)

if php -m | grep -Eq '^(xdebug|pcov)$'; then
  if php -m | grep -Eq '^xdebug$'; then
    export XDEBUG_MODE=coverage
  fi
  PHPUNIT_ARGS+=(--coverage-text --coverage-html coverage)
fi

./vendor/bin/phpunit "${PHPUNIT_ARGS[@]}"
