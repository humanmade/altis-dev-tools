# Altis Travis Build/Testing Configuration

This folder contains the Travis modules used by Altis modules to run continuous integration (CI) build and test jobs to verify the
code's integrity.

The tests are designed to run when there are changes to the `master` and `main` branches, any release branch (e.g. `v19-branch`),
and any branch beginning with `travis`.

The tests run in stages made available by Travis.

Each job is a sequence of phases. The main phases are:

- `install` - install any dependencies required
- `script` - run the build script

Travis CI can run custom commands in the phases:

- `before_install` - before the install phase
- `before_script` - before the script phase
- `after_script` - after the script phase.
- `after_success` - when the build succeeds
- `after_failure` - when the build fails

The Altis module configuration only uses the `before_install`, `before_script`, and `script` phases.

## Setup

We set some global environment variables for Composer and for the Local server stack:

- `COMPOSE_HTTP_TIMEOUT` is set to 360 seconds
- `COMPOSER_NO_INTERACTION` is set to 1
- `ES_MEM_LIMIT` is set to 2 GB to ensure enough memory for Elasticsearch

We set notifications to email on a change to a failure state, but no email on success. We also configure a common composer cache
directory, to speed up subsequent runs of the same build.

## Before Install Phase

If there are no tests defined (it checks the `/tests` directory), the execution will exit early, and no further processing takes
place. We then install `node.js` 12 via `nvm`. Note: I don't believe this is actually needed any more for Altis modules. Finally, we
authenticate with Docker Hub using secrets configured in the repositories.

## Before Script Phase

In this phase, we create a local server installation of Altis. We figure out the relevant branch for this build. Either a PR branch,
a release branch, or we fall back to the `master` branch. We then use `composer create-project` to create a new `altis/skeleton`
project based on the calculated branch.

Next, we install the `altis/test-theme`, and configure that as the default theme so that the server will start up.

We then modify composer to require the current module on the pull request branch, by aliasing that branch as the required branch.

For example, if the current PR branch is `my-pr-branch` and the PR is against `v19-branch`, we alias that using
`composer require module/name:dev-my-pr-branch as 19.0.0` or whatever version composer has calculated in the lock file.

Next, we generate a `salts.php` file with ther output of a call to <https://api.wordpress.org/secret-key/1.1/salt/> as `wp-phpunit`
requires these to be defined now. Then we append `require_once __DIR__ . '/salts.php` to the `.config/load.php` file.

## Script Phase

In this phase, we start Local Server with `composer server start` and run any `codecept` tests defined in the module's `tests`
directory.

Finally, we initialise the `lintdocs` configuration and run the `lintdocs` linting checks against the module.
