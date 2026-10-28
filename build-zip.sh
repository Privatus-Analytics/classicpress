#!/bin/sh
# Builds the ClassicPress directory zip: build/privatus-analytics-v<version>.zip
# with a privatus-analytics/ folder inside (no version number in the folder name).
# Usage: ./build-zip.sh [version]   (defaults to the Version header)
set -e
cd "$(dirname "$0")"
main=privatus-analytics/privatus-analytics.php
header_version=$(sed -n 's/^ \* Version: *//p' "$main")
version=${1:-$header_version}
version=${version#v}
[ "$version" = "$header_version" ] || { echo "tag v$version does not match the Version header $header_version" >&2; exit 1; }
grep -q "define( 'PRIVATUS_ANALYTICS_VERSION', '$version' );" "$main" || { echo "PRIVATUS_ANALYTICS_VERSION is not $version" >&2; exit 1; }
grep -Eq '^ \* Requires CP: +[0-9]+\.[0-9]+$' "$main" || { echo "Requires CP header missing or not in X.Y form" >&2; exit 1; }
grep -Eq '^ \* Requires PHP: +[0-9]+\.[0-9]+$' "$main" || { echo "Requires PHP header missing" >&2; exit 1; }
if grep -rqi "^[ *]*Update URI" privatus-analytics/*.php; then echo "Update URI header found" >&2; exit 1; fi
out="build/privatus-analytics-v$version.zip"
mkdir -p build
rm -f "$out"
cp README.md privatus-analytics/README.md
trap 'rm -f privatus-analytics/README.md' EXIT
zip -rq "$out" privatus-analytics -x '*.DS_Store' '*/.git/*'
unzip -l "$out" | tail -1
echo "$out"
