# Webpack Asset Loader

This feature makes it easy to detect and load assets generated by Webpack, including those served from local `webpack-dev-server` instances.

## Usage

The Asset Loader library is designed to work in conjunction with a Webpack configuration (such as those created with the presets in [@humanmade/webpack-helpers](https://github.com/humanmade/webpack-helpers)) which generate an asset manifest file. This manifest associates asset bundle names with either URIs pointing to asset bundles on a running DevServer instance, or else local file paths on disk.

### `Asset_Loader\register_asset()` and `Asset_Loader\enqueue_asset()`

`Asset_Loader` provides a set of functions for reading this manifest file and registering a specific resource within it to load within your website. The primary public interface provided by this plugin are the functions `Asset_Loader\register_asset()` and `Asset_Loader\enqueue_asset()`.

To register a manifest asset call one of these functions inside actions like `wp_enqueue_scripts` or `enqueue_block_editor_assets`, in the same manner you would have called the standard WordPress `wp_register_script` or `wp_enqueue_style` functions.

Both functions accept the following parameters:

- **`string $manifest_path`**: The path on disk to the generated manifest file.
- **`string $target_asset`**: The asset output name defined in the Webpack configuration.
- **`array $options`**: An array of options to control how the asset is registered and loaded.
   - `bool 'in-footer'`: Whether to load the asset in the footer or of the page or the `<head>`. Default to `true`.
   - `array 'dependencies`: An array of script or style handles this asset is dependent upon.
   - `string 'handle'`: An optional custom handle to use for referring to the asset. Defaults to the value of `$target_asset`.


To register an asset to be manually enqueued later, use `Asset_Loader\register_asset()` instead of `Asset_Loader\enqueue_asset()`.

The following example shows how to enqueue a JS and a CSS file for the Block Editor:

```php
<?php
namespace My_Theme\Scripts;

use Asset_Loader;

add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_block_editor_assets' );

/**
 * Enqueue the JS and CSS for blocks in the editor.
 *
 * @return void
 */
function enqueue_block_editor_assets() {
  Asset_Loader\enqueue_asset(
    // In a plugin, this would be `plugin_dir_path( __FILE__ )` or similar.
    get_stylesheet_directory() . '/build/asset-manifest.json',
    // The handle of a resource within the manifest. For static file fallbacks,
    // this should also match the filename on disk of a build production asset.
    'blocks.js',
    [
      'handle' => 'my-theme-blocks',
      'dependencies' => [ 'wp-data', 'wp-element', 'wp-editor' ],
    ]
  );

  Asset_Loader\enqueue_asset(
    // In a plugin, this would be `plugin_dir_path( __FILE__ )` or similar.
    get_stylesheet_directory() . '/build/asset-manifest.json',
    // Enqueue CSS for the editor.
    'blocks.css',
    [
      'handle' => 'my-theme-blocks-style',
      'dependencies' => [ 'wp-components' ],
    ]
  );
}
```