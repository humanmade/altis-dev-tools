# Cloud Development Environments

Altis has experimental built-in support for [GitHub Codespaces](https://github.com/features/codespaces), a Cloud-based development
environment, that makes it possible to spin up a complete development environment in seconds within your browser, with a full editor
based on Visual Studio Code, without having to install anything on your local machine.

This is useful for many reasons:

- Bootstrap a full development environment in seconds.
- Zero local installation required.
- Standardized and highly configurable environment setup.
- Powerful and scalable development environment.
- Ability to share preview environments with colleagues.

## How to start

1. **Enable Codespaces support for your GitHub organization**

   In order for Codespaces to become available to contributors of a specific repository, the GitHub organization administrator needs
   to enable support for Codespaces for such contributors. Refer to the
   official [Enabling Codespaces for your organization](https://docs.github.com/en/codespaces/managing-codespaces-for-your-organization/enabling-codespaces-for-your-organization)
   and [Managing billing for Codespaces in your organization](https://docs.github.com/en/codespaces/managing-codespaces-for-your-organization/managing-billing-for-codespaces-in-your-organization)
   guides for more information.

2. **Bootstrap the configuration**

   After Codespaces is activated, you'll need to bootstrap the development container configuration
   using `composer dev-tools bootstrap codespaces`. See
   the [documentation on `devcontainers`](https://docs.github.com/en/codespaces/setting-up-your-project-for-codespaces/introduction-to-dev-containers)
   for ways to customize your container further.

3. **Start a new container**

   Once the `devcontainer` configuration is committed, you'll be able to start a new Codespaces environment
   at [https://github.com/codespaces/new](https://github.com/codespaces/new) , or from the "Codespaces" tab when you click on "Code"
   button where you typically get your repository checkout link. Note: we recommend configuring your codespace environment to use a
   machine type of '4-core'. Choose " Configure and create codespace" from the drop down menu on GitHub.

4. **Start development!**

   Once the codespace is started, you'll be able to use the editor-in-browser to manage the project code. You can use the terminal
   to start the integrated local server environment as you would if you installed it locally, using `composer server start`. You can
   also use it to pull and push code in a fully-featured Debian-based environment.

5. **Preview**

   Once the local server is started, you'll be presented with the URL to the preview environment where you can view the project in
   your browser.

6. **Sharing**

   Codespaces preview environments are protected by your own GitHub login session, and are not publicly available by default. But
   you can explicitly choose specific ports to be public through the `Ports` panel within the editor, so you can share it publicly
   with others. Port 80 is typically what you'll need to expose publicly to allow access to your project.

## Notes

GitHub Codespaces support is still experimental, so some hiccups can be expected. Typically, rebuilding the developer container
fixes such hiccups.
