#!/usr/bin/env bash
#
# Test harness for the "Compute base branch" step in module-ci.yml.
#
# The actual logic in the workflow runs on a GitHub-hosted runner, but the
# branch-resolution rules are simple enough to mirror locally so we can
# exercise them without round-tripping through CI.
#
# IMPORTANT: keep the resolve_branches() function below in sync with the
# corresponding step in ../module-ci.yml — there is no shared source file
# because actions/checkout in the reusable workflow checks out the caller
# repo, not altis-dev-tools.
#
# Usage:
#   ./ci-branch-resolution.sh            # run the full test matrix
#   ./ci-branch-resolution.sh --eval     # read HEAD_REF/BASE_REF/REF_NAME from env
#                                     # and print the resolved branches
# Examples:
#   HEAD_REF=fix/foo BASE_REF=v25-branch ./ci-branch-resolution.sh --eval
#   REF_NAME=master ./ci-branch-resolution.sh --eval

set -euo pipefail

# ---------------------------------------------------------------------------
# Logic under test — must match the "Compute base branch" step in module-ci.yml
# ---------------------------------------------------------------------------
resolve_branches() {
    local head_ref="${1:-}"
    local base_ref="${2:-}"
    local ref_name="${3:-}"

    # github.head_ref || github.ref_name
    local branch_name="${head_ref:-$ref_name}"
    # github.base_ref || github.ref_name
    local target_ref="${base_ref:-$ref_name}"

    local base_branch
    if [[ "$target_ref" =~ ^(v[0-9]{2}-branch)$ ]]; then
        base_branch="${BASH_REMATCH[1]}"
    else
        base_branch="master"
    fi

    printf 'branch-name=%s\nbase-branch=%s\n' "$branch_name" "$base_branch"
}

# ---------------------------------------------------------------------------
# Ad-hoc evaluation mode
# ---------------------------------------------------------------------------
if [[ "${1:-}" == "--eval" ]]; then
    resolve_branches "${HEAD_REF:-}" "${BASE_REF:-}" "${REF_NAME:-}"
    exit 0
fi

# ---------------------------------------------------------------------------
# Test matrix: name | HEAD_REF | BASE_REF | REF_NAME | want branch | want base
# Empty HEAD_REF/BASE_REF mimics a push event; populated mimics a pull_request.
# ---------------------------------------------------------------------------
cases=(
    "push to master                     |              |             | master      | master      | master"
    "push to v23-branch                 |              |             | v23-branch  | v23-branch  | v23-branch"
    "push to v26-branch                 |              |             | v26-branch  | v26-branch  | v26-branch"
    "PR feat -> master                  | feat/foo     | master      |             | feat/foo    | master"
    "PR feat -> v23-branch              | feat/foo     | v23-branch  |             | feat/foo    | v23-branch"
    "PR feat -> v26-branch              | bug/123      | v26-branch  |             | bug/123     | v26-branch"
    "PR v25-branch -> master            | v25-branch   | master      |             | v25-branch  | master"
    "PR with slashy name -> v24-branch  | fix/some/dir | v24-branch  |             | fix/some/dir| v24-branch"
    "non-matching target name           | feat/foo     | dev         |             | feat/foo    | master"
)

pass=0
fail=0
for c in "${cases[@]}"; do
    IFS='|' read -r name head base ref want_branch want_base <<<"$c"
    # Strip surrounding whitespace from each field.
    name=$(echo "$name" | xargs)
    head=$(echo "$head" | xargs)
    base=$(echo "$base" | xargs)
    ref=$(echo "$ref"  | xargs)
    want_branch=$(echo "$want_branch" | xargs)
    want_base=$(echo "$want_base" | xargs)

    out=$(resolve_branches "$head" "$base" "$ref")
    got_branch=$(printf '%s\n' "$out" | sed -n 's/^branch-name=//p')
    got_base=$(printf '%s\n' "$out" | sed -n 's/^base-branch=//p')

    if [[ "$got_branch" == "$want_branch" && "$got_base" == "$want_base" ]]; then
        printf '  PASS  %s\n' "$name"
        pass=$((pass + 1))
    else
        printf '  FAIL  %s\n' "$name"
        printf '          inputs:  head=%q base=%q ref=%q\n' "$head" "$base" "$ref"
        printf '          wanted:  branch=%q  base=%q\n' "$want_branch" "$want_base"
        printf '          got:     branch=%q  base=%q\n' "$got_branch" "$got_base"
        fail=$((fail + 1))
    fi
done

printf '\n%d passed, %d failed\n' "$pass" "$fail"
[[ $fail -eq 0 ]]
