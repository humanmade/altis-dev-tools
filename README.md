<h1 align="center"><img src="https://make.hmn.md/altis/Altis-logo.svg" width="89" alt="Altis" /> Developer Tools</h1>

<p align="center">Developer tooling for <strong><a href="https://altis-dxp.com/">Altis</a></strong>.</p>

<p align="center"><a href="https://packagist.org/packages/altis/dev-tools"><img alt="Packagist Version" src="https://img.shields.io/packagist/v/altis/dev-tools.svg"></a></p>

## Dependencies

* [Composer](https://getcomposer.org/download/)

## Installation with Altis

Altis Dev Tools is included by default in an Altis project, so you don't need to install anything else.

## Installation without Altis

Altis Dev Tools can be installed as a dependency within a Composer-based WordPress project.

First, ensure you have WordPress Packagist as a repository somewhere in your project's top-level `composer.json`:

```json
"repositories": [
    {
        "type": "composer",
        "url": "https://wpackagist.org"
    }
],
```

Then, add the dependency:

`composer require --dev altis/dev-tools`

## Getting Started

[For full documentation click here](./docs).
