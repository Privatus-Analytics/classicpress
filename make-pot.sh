#!/bin/sh
# Regenerates privatus-analytics/languages/privatus-analytics.pot in Docker (WP-CLI 2.12, PHP 8.3).
set -e
cd "$(dirname "$0")"
docker run --rm --user "$(id -u):$(id -g)" -e HOME=/tmp \
  -v "$PWD/privatus-analytics":/plugin \
  wordpress:cli-2.12-php8.3 wp i18n make-pot /plugin /plugin/languages/privatus-analytics.pot \
  --slug=privatus-analytics --domain=privatus-analytics \
  --headers='{"Report-Msgid-Bugs-To":"https://github.com/Privatus-Analytics/classicpress/issues"}' --allow-root
