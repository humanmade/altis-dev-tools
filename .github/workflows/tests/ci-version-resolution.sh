#!/usr/bin/env bash
#
# Test harness for the composer alias-version logic in module-ci.yml's
# "Install test theme and inject package under test" step.
#
# The workflow does:
#   composer require -W "$ALTIS_PACKAGE:dev-${BRANCH_NAME} as ${ALIAS_VERSION}"
# where ALIAS_VERSION is derived from composer.lock so the dev branch satisfies
# whatever version constraint is already on the package.
#
# IMPORTANT: keep the resolve_alias_version() function below in sync with the
# corresponding step in ../module-ci.yml — there is no shared source file
# because actions/checkout in the reusable workflow checks out the caller
# repo, not altis-dev-tools.
#
# Usage:
#   ./ci-version-resolution.sh         # run the full test matrix
#   ./ci-version-resolution.sh --eval  # read LOCK_FILE and PKG from env, print result
# Examples:
#   LOCK_FILE=./composer.lock PKG=altis/cms ./ci-version-resolution.sh --eval

set -euo pipefail

if ! command -v jq >/dev/null 2>&1; then
    echo "jq is required" >&2
    exit 2
fi

# ---------------------------------------------------------------------------
# Logic under test — must match the step in module-ci.yml
# ---------------------------------------------------------------------------
resolve_alias_version() {
    local lock_file="$1"
    local pkg="$2"

    local installed_version
    installed_version=$(
        jq -r '(.packages[], .["packages-dev"][]) | select(.name==$pkg) | .version' \
            --arg pkg "$pkg" "$lock_file" \
        | head -1
    )

    local alias_version
    if [[ -z "$installed_version" || "$installed_version" =~ ^dev- ]]; then
        alias_version="9999.9.9"
    else
        alias_version="${installed_version}9"
    fi

    printf 'installed-version=%s\nalias-version=%s\n' "$installed_version" "$alias_version"
}

# ---------------------------------------------------------------------------
# Ad-hoc evaluation mode
# ---------------------------------------------------------------------------
if [[ "${1:-}" == "--eval" ]]; then
    if [[ -z "${LOCK_FILE:-}" || -z "${PKG:-}" ]]; then
        echo "Usage: LOCK_FILE=path/to/composer.lock PKG=altis/cms $0 --eval" >&2
        exit 2
    fi
    resolve_alias_version "$LOCK_FILE" "$PKG"
    exit 0
fi

# ---------------------------------------------------------------------------
# Test matrix
# ---------------------------------------------------------------------------
tmp_dir=$(mktemp -d)
trap 'rm -rf "$tmp_dir"' EXIT

write_lock() {
    # Reads JSON from stdin, writes to a fresh temp file, echoes the path.
    local path
    path=$(mktemp "$tmp_dir/lock.XXXXXX.json")
    cat >"$path"
    echo "$path"
}

pass=0
fail=0
run_case() {
    local name="$1" lock="$2" pkg="$3" want_alias="$4"
    local got_alias
    got_alias=$(resolve_alias_version "$lock" "$pkg" | sed -n 's/^alias-version=//p')
    if [[ "$got_alias" == "$want_alias" ]]; then
        printf '  PASS  %s\n' "$name"
        pass=$((pass + 1))
    else
        printf '  FAIL  %s\n' "$name"
        printf '          want alias: %s\n          got  alias: %s\n' "$want_alias" "$got_alias"
        fail=$((fail + 1))
    fi
}

# Case 1: concrete X.Y.Z version in .packages -> X.Y.Z9
LOCK=$(write_lock <<'JSON'
{
  "packages":     [ {"name":"altis/cms","version":"1.2.3"} ],
  "packages-dev": []
}
JSON
)
run_case "concrete version in packages"           "$LOCK" "altis/cms"           "1.2.39"

# Case 2: concrete X.Y.Z version in .packages-dev -> X.Y.Z9
# This is the bug fix (altis/local-server lives in skeleton's require-dev).
LOCK=$(write_lock <<'JSON'
{
  "packages":     [ {"name":"altis/cms","version":"1.2.3"} ],
  "packages-dev": [ {"name":"altis/local-server","version":"4.5.6"} ]
}
JSON
)
run_case "concrete version in packages-dev"       "$LOCK" "altis/local-server" "4.5.69"

# Case 3: dev-* installed version -> 9999.9.9
LOCK=$(write_lock <<'JSON'
{
  "packages":     [ {"name":"altis/cms","version":"dev-master"} ],
  "packages-dev": []
}
JSON
)
run_case "dev-master installed"                   "$LOCK" "altis/cms"          "9999.9.9"

LOCK=$(write_lock <<'JSON'
{
  "packages":     [ {"name":"altis/cms","version":"dev-feature-branch"} ],
  "packages-dev": []
}
JSON
)
run_case "dev-feature-branch installed"           "$LOCK" "altis/cms"          "9999.9.9"

# Case 4: package not present in either array -> 9999.9.9
LOCK=$(write_lock <<'JSON'
{
  "packages":     [ {"name":"altis/cms","version":"1.2.3"} ],
  "packages-dev": []
}
JSON
)
run_case "package missing entirely"               "$LOCK" "altis/seo"          "9999.9.9"

# Case 5: empty lock arrays -> 9999.9.9
LOCK=$(write_lock <<'JSON'
{ "packages": [], "packages-dev": [] }
JSON
)
run_case "empty lock"                             "$LOCK" "altis/cms"          "9999.9.9"

# Case 6: package in BOTH arrays -> head -1 picks the first hit (.packages first)
LOCK=$(write_lock <<'JSON'
{
  "packages":     [ {"name":"altis/cms","version":"1.0.0"} ],
  "packages-dev": [ {"name":"altis/cms","version":"2.0.0"} ]
}
JSON
)
run_case "duplicate name, packages wins"          "$LOCK" "altis/cms"          "1.0.09"

# Case 7: prerelease suffix is preserved, with `9` appended
LOCK=$(write_lock <<'JSON'
{
  "packages":     [ {"name":"altis/cms","version":"1.2.3-RC1"} ],
  "packages-dev": []
}
JSON
)
run_case "prerelease suffix preserved"            "$LOCK" "altis/cms"          "1.2.3-RC19"

# Case 8: only matching package among many entries -> picks correct one
LOCK=$(write_lock <<'JSON'
{
  "packages": [
    {"name":"johnpbloch/wordpress","version":"6.4.0"},
    {"name":"altis/cms","version":"7.8.9"},
    {"name":"altis/seo","version":"3.0.0"}
  ],
  "packages-dev": []
}
JSON
)
run_case "selects correct package among many"     "$LOCK" "altis/cms"          "7.8.99"

# Case 9: package only in packages-dev, package-with-same-prefix in packages -> exact match wins
LOCK=$(write_lock <<'JSON'
{
  "packages":     [ {"name":"altis/cms","version":"1.0.0"} ],
  "packages-dev": [ {"name":"altis/cms-dev-tools","version":"2.0.0"} ]
}
JSON
)
run_case "exact name match (no substring)"        "$LOCK" "altis/cms-dev-tools" "2.0.09"

printf '\n%d passed, %d failed\n' "$pass" "$fail"
[[ $fail -eq 0 ]]
