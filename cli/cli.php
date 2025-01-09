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

function import_sql_file($file_path) {
    global $wpdb;

    $sql = file_get_contents($file_path);
    $queries = explode(';', $sql);

    foreach ($queries as $query) {
        if (trim($query)) {
            $wpdb->query($query);
        }
    }

    WP_CLI::success("Data from $file_path has been imported.");
}

WP_CLI::add_command('fer_reset', function() {
    WP_CLI::confirm("Do you want to reset all film gear clients, equipment, rental sessions, and earnings?");
    reset_plugin_tables();
});

WP_CLI::add_command('fer_example_data', function() {
    import_sql_file(plugin_dir_path(__FILE__) . '../sql_dumps/wp_film_equipment.sql');
    import_sql_file(plugin_dir_path(__FILE__) . '../sql_dumps/wp_earnings.sql');
    WP_CLI::success('Example data have been imported.');
});
