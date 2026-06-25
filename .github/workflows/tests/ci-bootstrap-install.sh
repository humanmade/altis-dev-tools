#!/usr/bin/env bash
#
# End-to-end proof that installing this package produces the GitHub Actions CI
# workflow. Exercises real composer resolution + the real
# altis/dev-tools-command post-autoload-dump plugin — the only layer that sees
# a too-low constraint, which a mocked-path unit test can't.
#
# Strategy (kept light — no full dev-tools dependency tree):
#   * Resolve THIS package's real altis/dev-tools-command constraint from
#     packagist; assert the installed version is >= 0.9.0.
#   * Stand altis/dev-tools in via a minimal path package carrying THIS repo's
#     real templates/project-ci.yml, giving the plugin a template to copy and a
#     package to read the pin ref from.
#   * composer install, then assert .github/workflows/ci.yml is created and its
#     reusable-workflow ref is pinned (no leftover placeholder).
#
# Usage:
#   ./ci-bootstrap-install.sh

set -euo pipefail

# First altis/dev-tools-command release that scaffolds .github/workflows/ci.yml.
MIN_VERSION="0.9.0"

if ! command -v composer >/dev/null 2>&1; then
    echo "composer is required" >&2
    exit 2
fi

script_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
repo_root=$(cd "$script_dir/../../.." && pwd)
template="$repo_root/templates/project-ci.yml"
composer_json="$repo_root/composer.json"

if [[ ! -f "$template" ]]; then
    echo "templates/project-ci.yml not shipped here; nothing to test."
    exit 0
fi

constraint=$(
    php -r '$j = json_decode(file_get_contents($argv[1]), true); echo $j["require"]["altis/dev-tools-command"] ?? "";' \
        "$composer_json"
)
if [[ -z "$constraint" ]]; then
    echo "FAIL  composer.json does not require altis/dev-tools-command." >&2
    exit 1
fi

tmp_dir=$(mktemp -d)
trap 'rm -rf "$tmp_dir"' EXIT

# Minimal stand-in for altis/dev-tools: forced version + the real template.
stub_dir="$tmp_dir/dev-tools-stub"
mkdir -p "$stub_dir/templates" "$stub_dir/travis"
cp "$template" "$stub_dir/templates/project-ci.yml"
# Inert Travis placeholders so a (bug-state) <=0.8.x command — which copies from
# travis/ — completes gracefully instead of crashing on a missing file; the real
# dev-tools still ships these. That keeps the assertions below, not a composer
# stack trace, the thing that reports the regression.
printf '# stub\n' >"$stub_dir/travis/tests.yml"
printf '# stub\n' >"$stub_dir/travis/project.yml"
cat >"$stub_dir/composer.json" <<'JSON'
{
    "name": "altis/dev-tools",
    "description": "Stand-in for altis/dev-tools used by the CI bootstrap test.",
    "version": "99.0.0",
    "type": "wordpress-muplugin"
}
JSON

# Fixture project that requires both packages, resolving dev-tools-command from
# packagist via THIS repo's real constraint.
project_dir="$tmp_dir/project"
mkdir -p "$project_dir"
cat >"$project_dir/composer.json" <<JSON
{
    "name": "altis/ci-bootstrap-test",
    "description": "Throwaway fixture asserting the GHA CI workflow gets scaffolded.",
    "repositories": [
        { "type": "path", "url": "../dev-tools-stub", "options": { "symlink": false } }
    ],
    "require": {
        "altis/dev-tools": "99.0.0",
        "altis/dev-tools-command": "$constraint"
    },
    "config": {
        "allow-plugins": { "altis/dev-tools-command": true }
    }
}
JSON

echo "Installing fixture (altis/dev-tools-command:$constraint) ..."
( cd "$project_dir" && composer install --no-interaction --no-progress --no-ansi ) >"$tmp_dir/install.log" 2>&1 || {
    echo "FAIL  composer install errored:" >&2
    cat "$tmp_dir/install.log" >&2
    exit 1
}

fail=0

# --- Assertion 1: the resolved command version is GHA-aware. ----------------
installed_version=$(
    php -r '
        $j = json_decode(file_get_contents($argv[1]), true);
        $pkgs = $j["packages"] ?? $j;
        foreach ($pkgs as $p) {
            if (($p["name"] ?? "") === "altis/dev-tools-command") { echo $p["version"]; break; }
        }
    ' "$project_dir/vendor/composer/installed.json"
)
if [[ -z "$installed_version" ]]; then
    echo "FAIL  altis/dev-tools-command was not installed." >&2
    fail=1
elif ! php -r 'exit(version_compare($argv[1], $argv[2], ">=") ? 0 : 1);' "$installed_version" "$MIN_VERSION"; then
    echo "FAIL  Constraint '$constraint' resolved to $installed_version, below the GHA-aware $MIN_VERSION." >&2
    echo "      This is the v27 bug class: the Travis-era command would be installed." >&2
    fail=1
else
    echo "PASS  altis/dev-tools-command resolved to $installed_version (>= $MIN_VERSION)."
fi

# --- Assertion 2: the workflow file is scaffolded and pinned. ---------------
workflow="$project_dir/.github/workflows/ci.yml"
if [[ ! -f "$workflow" ]]; then
    echo "FAIL  composer install did not create .github/workflows/ci.yml." >&2
    fail=1
else
    if ! grep -q 'altis-ci\.yml@' "$workflow"; then
        echo "FAIL  ci.yml does not reference the altis-ci.yml reusable workflow." >&2
        fail=1
    fi
    if grep -q '__ref_replace_me__' "$workflow"; then
        echo "FAIL  ci.yml still contains the __ref_replace_me__ placeholder; the ref was not pinned." >&2
        fail=1
    fi
    [[ $fail -eq 0 ]] && echo "PASS  .github/workflows/ci.yml scaffolded and ref pinned."
fi

if [[ $fail -ne 0 ]]; then
    echo "--- generated ci.yml (if any) ---" >&2
    cat "$workflow" 2>/dev/null >&2 || true
    exit 1
fi

echo
echo "CI bootstrap install test passed."
