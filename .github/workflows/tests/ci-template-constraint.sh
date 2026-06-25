#!/usr/bin/env bash
#
# Fails if this package ships templates/project-ci.yml but its
# altis/dev-tools-command constraint resolves below 0.9.0 — the first release
# that consumes the template. Below that, the template is never installed and
# no .github/workflows/ci.yml is produced.
#
# Cheap, no-network half of the pair; ci-bootstrap-install.sh proves it end to
# end.
#
# Usage:
#   ./ci-template-constraint.sh

set -euo pipefail

# First altis/dev-tools-command release that scaffolds .github/workflows/ci.yml.
MIN_VERSION="0.9.0"

script_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
repo_root=$(cd "$script_dir/../../.." && pwd)
template="$repo_root/templates/project-ci.yml"
composer_json="$repo_root/composer.json"

# No template means an older line (<= v26) that intentionally predates the
# GitHub Actions bootstrap. Nothing to enforce.
if [[ ! -f "$template" ]]; then
    echo "templates/project-ci.yml not shipped here; nothing to enforce."
    exit 0
fi

constraint=$(
    php -r '$j = json_decode(file_get_contents($argv[1]), true); echo $j["require"]["altis/dev-tools-command"] ?? "";' \
        "$composer_json"
)

if [[ -z "$constraint" ]]; then
    echo "FAIL  templates/project-ci.yml is shipped but composer.json does not require altis/dev-tools-command." >&2
    exit 1
fi

# This guard only reasons about single-range constraints (^x, ~x, >=x, x). A
# compound OR (e.g. '^0.8.2 || ^0.9.0') needs a human to confirm intent rather
# than a naive floor read, so refuse instead of guessing.
if [[ "$constraint" == *"||"* ]]; then
    echo "FAIL  Compound constraint '$constraint' can't be checked by floor comparison." >&2
    echo "      Update this test to reason about the OR ranges deliberately." >&2
    exit 1
fi

# Lowest version the constraint admits = the first x.y.z token in it.
floor=$(printf '%s' "$constraint" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
if [[ -z "$floor" ]]; then
    echo "FAIL  Could not parse a floor version out of constraint '$constraint'." >&2
    exit 1
fi

# floor >= MIN_VERSION?  (sort -V ascending; smaller lands first.)
lowest=$(printf '%s\n%s\n' "$MIN_VERSION" "$floor" | sort -V | head -1)
if [[ "$lowest" != "$MIN_VERSION" ]]; then
    echo "FAIL  altis/dev-tools-command constraint '$constraint' (floor $floor) excludes the" >&2
    echo "      GHA-aware $MIN_VERSION+, so templates/project-ci.yml will never be installed." >&2
    exit 1
fi

echo "PASS  templates/project-ci.yml is shipped and altis/dev-tools-command '$constraint' admits >= $MIN_VERSION."
