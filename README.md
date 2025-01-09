# Film Equipment Rental Plugin

This is a WordPress plugin that has two main functionalities:
1. Organize your gear (storing items including their serial number, purchase date, price, etc.)
2. Track your rental income on this gear and see ROI

## CLI Tool

This plugin includes a CLI tool for debugging and managing the plugin's data. The CLI tool provides the following commands:

### Reset Plugin Tables

You can reset all plugin tables using the `fer_reset` command. This will drop all existing tables and recreate them.

```sh
wp fer_reset
```

You will be prompted to confirm the reset action. This is useful for debugging and starting with a clean slate.

### Import Example Data

You can import example data into the plugin tables using the `fer_example_data` command. This will import data from predefined SQL files.

```sh
wp fer_example_data
```

This command is useful for debugging and testing the plugin with sample data.

### Usage

1. Ensure that WP-CLI is installed and configured on your WordPress site.
2. Run the desired command from the command line.

**Note:** Debug mode must be enabled to use these commands. You can enable debug mode by adding the following line to your `wp-config.php` file:

```php
define('WP_DEBUG', true);
```


