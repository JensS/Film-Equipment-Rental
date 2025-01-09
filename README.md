# Film Equipment Rental Plugin

![Film Equipment Rental Plugin Logo](./logo.svg)
<img src="./logo.svg">

This is a WordPress plugin that has two main functionalities:
1. Organize your gear (storing items including their serial number, purchase date, price, etc.)
2. Track your rental income on this gear and see ROI

## Frontend

The frontend of the plugin allows users to view and search for available equipment. It includes the following features:

- **Search Bar**: Users can search for equipment by name and filter by rental days.
- **Equipment List**: Displays a list of available equipment, organized by categories if enabled.
- **Lightbox**: Clicking on an equipment image opens a lightbox with a larger view of the image.
- **Rental Overview PDF**: Users can generate a PDF overview of the rental equipment.

### Shortcode

To display the equipment list on any page or post, use the following shortcode:

```sh
[equipment_list]
```

## Backend

The backend of the plugin provides an admin interface for managing equipment, clients, and rental sessions. It includes the following features:

- **Equipment Management**: Add, edit, and delete equipment items.
- **Client Management**: Add, edit, and delete clients.
- **Rental Sessions**: Track rental sessions and earnings for each piece of equipment.
- **Statistics**: View detailed statistics on equipment performance, revenue, and ROI.
- **Import/Export**: Import and export equipment, clients, and rental data in JSON format.

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

## About the Creator

This plugin was created by Jens Sage, a fellow filmmaker who understands the struggle of Gear Acquisition Syndrome (GAS) all too well. Jens built this tool to help manage and track his collection of film equipment. You can find more about Jens at [www.jenssage.com](https://www.jenssage.com) or follow him on Instagram at [instagram.com/jenssage.de](https://instagram.com/jenssage.de).


