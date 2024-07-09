# Linting Your Documentation

Altis support checking your documentation files for formatting and syntax errors. It can also run a style and spelling check on your
documentation.

The formatting and syntax checks will highlight any issues that may affect how your documentation will appear in the WordPress
dashboard under the Documentation tab.

## Commands

The commands to run are as follows:

```shell
composer dev-tools lintdocs files --lint-path=path-to-module files|markdown|style|all
```

Where:

- `lint-path` is the path to the module to test relative to the root of your project,
- `files` will check the required file (`README.md`) exists in your docs folder and that you do not have a markdown file with the
  same name as it's containing folder,
- `markdown` will check the markdown syntax and formatting of each of your files, and
- `style` will run a style and spell check on your files.

If you specify `all` it will run all the above checks.

The command expects the documentation files to be in sub-folders of the module path you specify. It will check folders
called `docs`, `user-docs`, and `other-docs`. If any do not exist, it will handle that.

## Configuration

To initialise the configuration you need to run the command

```shell
composer dev-tools bootstrap lintdocs
```

This will place a files called `.markdownlint.jsconc` and '.vale.ini', and a folder called `styles` in the root directory of your
project.

## Markdown Lint

The markdown command runs `markdownlint` on all your markdown files. Documentation about the tool can
be [found on GitHub](https://github.com/DavidAnson/markdownlint)

The configuration file for the tool is `.markdownlint.yml`. You can find the details of the rules in
the [online help](https://github.com/DavidAnson/markdownlint/blob/main/doc/Rules.md). The file is commented and you can adjust it to
suite your own preferences. Altis recommends you leave most of the rules as default.

## Vale Style checker

The [Vale style checker](https://vale.sh/) is an open-source tool that allows you to enforce editorial style guides. It comes with
several off-the shelf style guides and you can write your own. Altis uses the `proselint` style and adds an Altis specific spell
check dictionary.

The styles folder contains the style rules for the Vale style checker. The configuration file `vale.ini` specifies which styles set
to use.

You will find documentation on the structure of the Styles and how to write your own, in
the [online documentation](https://vale.sh/docs/).

## Files checker

The `files` check does not have any configuration but will check the two main requirements for Altis' built-in documentation.

1. There should be a `README.md` file in each folder and sub-folder.
2. There should not be a file with the same base name as its parent folder.
