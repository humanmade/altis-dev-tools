# Developer Tools

![](./assets/banner-dev-tools.png)

Altis includes developer tools to allow you to debug and optimize your code. They provide you with an overview of how your page was rendered, as well as details on the various components.


The Altis developer tools are built on top of the [Query Monitor](https://querymonitor.com/) plugin.


## Getting started

The developer tools are active by default for all environment types except `production`. While logged in, a summary of the request's details will appear in the toolbar at the top of the page. Click this summary to open the developer tools.

![Screenshot of the developer tools toolbar item](./assets/toolbar.png)

This summary will automatically change color if errors or warnings are detected on your page.

The developer tools will be displayed as a panel at the bottom of your page.

![Screenshot of the developer tools panel](./assets/qm-overview.png)


### Activating for other users

By default, only administrators have access to the developer tools. You can temporarily test with other users or while logged out by setting an activation cookie instead.

To activate this cookie:

1. Open the developer tools while logged in as an administrator
2. Click on the gear icon in the top right to access the settings screen
3. Click the "Set authentication cookie" button to enable developer tools for your browser
4. Switch to another account or log out

![Screenshot of the developer tools panel](./assets/qm-settings-auth.png)

You can also add the `view_query_monitor` capability to users to permanently grant them the ability to use the developer tools.


### Activating in other environments

You can enable this on other environments by setting the `altis.modules.dev-tools.enabled` configuration option to true. We recommend using [environment-specific configuration](docs://getting-started/configuration.md#environment-specific-configuration) to only enable it on environments where necessary, as it has a small performance cost.

By default, this module is enabled on all environments except `production`.

For example, to disable on staging:

```json
{
	"extra": {
		"altis": {
			"environments": {
				"staging": {
					"modules": {
						"dev-tools": {
							"enabled": false
						}
					}
				}
			}
		}
	}
}
```


### Editor Stack Traces

Stack traces within the developer tools panel can be made into clickable links that open in your editor. Database queries will also be clickable so you can jump directly to the function that made them.

To activate clickable stack traces:

1. Open the developer tools panel
2. Click on the gear icon in the top right to access the settings screen
3. Select your editor from the drop down
4. Click the "Set editor cookie" button
5. Refresh the page

![Screenshot of the developer tools panel](./assets/qm-settings-editor.png)
