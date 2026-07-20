#!/usr/bin/env bash
#
# Test harness for the "Detect PHP version" step in module-ci.yml (and the
# equivalent step in altis-ci.yml).
#
# The workflow derives the runner PHP from config.platform.php: an explicit
# php-version input wins; otherwise it reads config.platform.php from the
# project/skeleton composer.json, falling back to a default; then it trims the
# result to major.minor for setup-php. module-ci.yml's fallback is the
# bootstrap-php-version input; altis-ci.yml inlines "8.4" as the fallback.
#
# IMPORTANT: keep the detect_php() function below in sync with those steps —
# there is no shared source file because actions/checkout in the reusable
# workflow checks out the caller repo, not altis-dev-tools.
#
# Usage:
#   ./ci-php-detection.sh         # run the full test matrix
#   ./ci-php-detection.sh --eval  # read OVERRIDE/COMPOSER_JSON/FALLBACK from env, print result
# Examples:
#   COMPOSER_JSON=./composer.json FALLBACK=8.4 ./ci-php-detection.sh --eval
#   OVERRIDE=8.5 ./ci-php-detection.sh --eval

set -euo pipefail

if ! command -v jq >/dev/null 2>&1; then
    echo "jq is required" >&2
    exit 2
fi

# ---------------------------------------------------------------------------
# Logic under test — must match the "Detect PHP version" step in module-ci.yml
# ---------------------------------------------------------------------------
detect_php() {
    local override="$1"
    local composer_json="$2"
    local fallback="$3"

    local php
    if [[ -n "$override" ]]; then
        php="$override"
    else
        php=$(jq -r '.config.platform.php // empty' "$composer_json")
        php="${php:-$fallback}"
    fi
    php=$(echo "$php" | cut -d. -f1,2)

    printf 'php=%s\n' "$php"
}

# ---------------------------------------------------------------------------
# Ad-hoc evaluation mode
# ---------------------------------------------------------------------------
if [[ "${1:-}" == "--eval" ]]; then
    detect_php "${OVERRIDE:-}" "${COMPOSER_JSON:-/dev/null}" "${FALLBACK:-8.4}"
    exit 0
fi

# ---------------------------------------------------------------------------
# Test matrix
# ---------------------------------------------------------------------------
tmp_dir=$(mktemp -d)
trap 'rm -rf "$tmp_dir"' EXIT

write_json() {
    # Reads JSON from stdin, writes to a fresh temp file, echoes the path.
    local path
    path=$(mktemp "$tmp_dir/composer.XXXXXX.json")
    cat >"$path"
    echo "$path"
}

pass=0
fail=0
run_case() {
    local name="$1" override="$2" json="$3" fallback="$4" want="$5"
    local got
    got=$(detect_php "$override" "$json" "$fallback" | sed -n 's/^php=//p')
    if [[ "$got" == "$want" ]]; then
        printf '  PASS  %s\n' "$name"
        pass=$((pass + 1))
    else
        printf '  FAIL  %s\n' "$name"
        printf '          want php: %s\n          got  php: %s\n' "$want" "$got"
        fail=$((fail + 1))
    fi
}

WITH_PHP=$(write_json <<'JSON'
{ "config": { "platform": { "php": "8.4" } } }
JSON
)
run_case "platform.php 8.4"                "" "$WITH_PHP" "8.4" "8.4"

OLD_PHP=$(write_json <<'JSON'
{ "config": { "platform": { "php": "8.3" } } }
JSON
)
run_case "platform.php 8.3"                "" "$OLD_PHP" "8.4" "8.3"

# A patch-level platform pin is trimmed to major.minor for setup-php.
PATCH_PHP=$(write_json <<'JSON'
{ "config": { "platform": { "php": "8.5.0" } } }
JSON
)
run_case "platform.php 8.5.0 trimmed"      "" "$PATCH_PHP" "8.4" "8.5"

# No platform.php at all -> fall back to the bootstrap/default version.
NO_PLATFORM=$(write_json <<'JSON'
{ "config": {} }
JSON
)
run_case "no platform.php, fallback 8.4"   "" "$NO_PLATFORM" "8.4" "8.4"
run_case "no platform.php, fallback 8.2"   "" "$NO_PLATFORM" "8.2" "8.2"

EMPTY=$(write_json <<'JSON'
{}
JSON
)
run_case "empty composer.json, fallback"   "" "$EMPTY" "8.3" "8.3"

# An explicit override wins over config.platform.php (and is trimmed too).
run_case "override wins over platform.php" "8.5" "$OLD_PHP" "8.4" "8.5"
run_case "override patch trimmed"          "8.5.1" "$OLD_PHP" "8.4" "8.5"

printf '\n%d passed, %d failed\n' "$pass" "$fail"
[[ $fail -eq 0 ]]
