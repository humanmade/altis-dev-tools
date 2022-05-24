# Cloud Development Environments

Altis has experimental built-in support for [GitHub Codespaces](https://github.com/features/codespaces), a Cloud-based development environment, that makes it possible to spin up a complete development environment in seconds within your browser, with a full editor based on Visual Studio Code and a fully-featured environment for your project, without having to install anything on your local machine.

This is useful for many reasons:
    - Few seconds to bootstrap a full development environment.
    - Zero local installation required.
    - Standardized and highly configurable environment setup.
    - Powerful development environment, scalable as needed.
    - Possibility of sharing preview environments with colleagues as needed.

## How to start

<!-- a note about pricing and associated logistics here? -->

    - Ask Altis support to activate Codespaces feature for your project members.
    - Bootstrap the development container configuration using `composer dev-tools bootstrap codespaces`
    - Create a new Codespaces environment at https://github.com/codespaces/new

Once the codespace is ready, you'll be able to use the editor to manage the project code, and the terminal to start the integrated local server environment as you would if you installed it locally, using `composer server start`, and to pull and push code in a fully-featured Debian-based environment.

Once the local server is started, you'll be presented with the URL to the preview environment where you can view the project in your browser. Noting that this preview environment is protected by your own GitHub login session, and is not publicly available by default, although you can explicitly choose specific ports to be public through the `Ports` panel within the editor.

## Notes

GitHub Codespaces support is still experimental, so some hiccups can be expected. Typically rebuilding the dev container fixes such hiccups. If you feel like there is a problem within the development environment that is not related to any custom configuration within the devcontainer.json setup, you can always refer to Altis support for guidance.