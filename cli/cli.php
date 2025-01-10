<?php
if (!defined('ABSPATH')) exit;

if (!defined('WP_CLI') || !WP_CLI) {
    exit('This script can only be run via WP-CLI.');
}

if (!defined('WP_DEBUG') || !WP_DEBUG) {
    WP_CLI::error('Debug mode is not enabled.');
}

global $wpdb;

function reset_plugin_tables() {
    global $wpdb;

    // Disable foreign key checks
    $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');

    $tables = [
        $wpdb->prefix . 'film_clients',
        $wpdb->prefix . 'film_equipment',
        $wpdb->prefix . 'rental_sessions',
        $wpdb->prefix . 'equipment_earnings'
    ];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }

    // Re-enable foreign key checks
    $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');

    fer_create_tables();
    WP_CLI::success('Plugin tables have been reset.');
}

function import_json_file($file_path, $table) {
    $data = json_decode(file_get_contents($file_path), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        WP_CLI::error('Invalid JSON file');
    }

    fer_import_data($data, $table);
    WP_CLI::success("Data from $file_path has been imported.");
}

WP_CLI::add_command('fer_reset', function() {
    WP_CLI::confirm("Do you want to reset all film gear clients, equipment, rental sessions, and earnings?");
    reset_plugin_tables();
});

WP_CLI::add_command('fer_import_gear', function($args, $assoc_args) {
    $file_path = $args[0] ?? null;
    if (!$file_path || !file_exists($file_path)) {
        WP_CLI::error('Please provide a valid JSON file path.');
    }
    import_json_file($file_path, $wpdb->prefix . 'film_equipment');
});

WP_CLI::add_command('fer_import_rentals', function($args, $assoc_args) {
    $file_path = $args[0] ?? null;
    if (!$file_path || !file_exists($file_path)) {
        WP_CLI::error('Please provide a valid JSON file path.');
    }
    $data = json_decode(file_get_contents($file_path), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        WP_CLI::error('Invalid JSON file');
    }

    fer_import_data($data['sessions'], $wpdb->prefix . 'rental_sessions');
    fer_import_data($data['earnings'], $wpdb->prefix . 'equipment_earnings');
    WP_CLI::success("Rental data from $file_path has been imported.");
});

WP_CLI::add_command('fer_import_clients', function($args, $assoc_args) {
    $file_path = $args[0] ?? null;
    if (!$file_path || !file_exists($file_path)) {
        WP_CLI::error('Please provide a valid JSON file path.');
    }
    import_json_file($file_path, $wpdb->prefix . 'film_clients');
});
