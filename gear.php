<?php
/**
 * Plugin Name: Film Equipment Rental
 * Description: Manages film equipment rental inventory with pricing and statistics
 * Version: 1.1
 * Author: Jens Sage 
 * Author URI:  https://www.jenssage.com
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Plugin URI: https://github.com/JensS/Film-Equipment-Rental
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, 'fer_activate_plugin');

define('FER_VERSION', "1.1");
define('FER_SLUG', "film-equipment-rental");
define('FER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FER_DEFAULT_BRANDS', array(
    'Angénieux', 'Apple', 'ARRI', 'Aputure', 'Avenger', 'Bebob', 'Blackmagic', 'Canon', 'Carl Zeiss', 'Cooke', 
    'DJI', 'DZOFilm', 'Focusbug', 'Leica', 'Manfrotto', 'Matthews', 'OConnor', 'Panasonic', 'Peli Case', 
    'Preston', "Proaim", 'RED', 'Røde', 'Sachtler', 'SanDisk', 'Schneider', 'Sennheiser', 'Sigma', "Samyang", 'SmallRig', 
    'smallHD', 'Sony', 'Tentacle', 'Teradek', 'Tiffen', 'Tilta', 'Zoom', 'beyerdynamic', 'Panavision', 'OConnor'
));


function fer_url() {
    return plugin_dir_url(__FILE__);
}

// Include necessary files
require_once FER_PLUGIN_DIR . 'includes/default-categories.php';
require_once FER_PLUGIN_DIR . 'includes/functions.php';
require_once FER_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once FER_PLUGIN_DIR . 'includes/admin-menu.php';
require_once FER_PLUGIN_DIR . 'includes/enqueue-scripts.php';
require_once FER_PLUGIN_DIR . 'includes/settings.php';
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once FER_PLUGIN_DIR . 'cli/cli.php';
}

require "vendor/autoload.php";
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/JensS/Film-Equipment-Rental/',
	__FILE__,
	FER_SLUG
);

// @todo Set the branch that contains the stable release. 
$myUpdateChecker->setBranch('stable');
$myUpdateChecker->getVcsApi()->enableReleaseAssets();


// Activation hook
function fer_activate_plugin() {
    fer_debug_log('Plugin activation started');
    
    // Create tables
    fer_create_tables();
    
    // Add version to options table
    add_option('fer_version', '1.1');
    
    // Initialize categories if they don't exist
    if (!get_option('fer_categories')) {
        update_option('fer_categories', FER_DEFAULT_CATEGORIES);
    }
    
    // Initialize brands if they don't exist
    if (!get_option('fer_brands')) {
        update_option('fer_brands', FER_DEFAULT_BRANDS);
    }
    
    // Migrate categories if they exist in old format
    $existing_categories = get_option('fer_categories');
    if ($existing_categories && !is_array($existing_categories)) {
        // Delete old format
        delete_option('fer_categories');
        // Set new format
        update_option('fer_categories', FER_DEFAULT_CATEGORIES);
    }
    
    fer_debug_log('Plugin activation completed');
}

// Create tables
function fer_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');


    // Add clients table to initial creation
    $clients_table = $wpdb->prefix . 'film_clients';
    $sql4 = "CREATE TABLE IF NOT EXISTS $clients_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql4);

    // Create equipment table
    $equipment_table = $wpdb->prefix . 'film_equipment';
    $sql1 = "CREATE TABLE IF NOT EXISTS $equipment_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        brand varchar(100) NOT NULL,
        serial_number varchar(100),
        short_description text,
        description text,
        category varchar(100) NOT NULL,
        daily_rate decimal(10,2) NOT NULL,
        purchase_price decimal(10,2),
        purchase_date date,
        current_value decimal(10,2),
        image_url varchar(255),
        status varchar(20) DEFAULT 'active',
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta($sql1);

    // Create rental sessions table
    $rental_sessions_table = $wpdb->prefix . 'rental_sessions';
    $sql2 = "CREATE TABLE IF NOT EXISTS $rental_sessions_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        rental_date date NOT NULL,
        rental_days int NOT NULL,
        notes text,
        package_deal varchar(3) DEFAULT 'no',  
        package_amount decimal(10,2) DEFAULT NULL, 
        client_id mediumint(9) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        FOREIGN KEY (client_id) REFERENCES {$clients_table}(id)
    ) $charset_collate;";

    dbDelta($sql2);

    // Create earnings table
    $earnings_table = $wpdb->prefix . 'equipment_earnings';
    $sql3 = "CREATE TABLE IF NOT EXISTS $earnings_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        session_id mediumint(9) NOT NULL,
        equipment_id mediumint(9) NOT NULL,
        earnings decimal(10,2) NOT NULL,
        PRIMARY KEY  (id),
        FOREIGN KEY (session_id) REFERENCES {$rental_sessions_table}(id) ON DELETE CASCADE,
        FOREIGN KEY (equipment_id) REFERENCES {$equipment_table}(id) ON DELETE CASCADE
    ) $charset_collate;";

    dbDelta($sql3);

    // Only log if WP_DEBUG is enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $equipment_exists = $wpdb->get_var("SHOW TABLES LIKE '$equipment_table'") === $equipment_table;
        $sessions_exists = $wpdb->get_var("SHOW TABLES LIKE '$rental_sessions_table'") === $rental_sessions_table;
        $earnings_exists = $wpdb->get_var("SHOW TABLES LIKE '$earnings_table'") === $earnings_table;
        
        error_log("Equipment table exists: " . ($equipment_exists ? 'yes' : 'no'));
        error_log("Sessions table exists: " . ($sessions_exists ? 'yes' : 'no'));
        error_log("Earnings table exists: " . ($earnings_exists ? 'yes' : 'no'));
    }
}

/**
 * Initialize the plugin
 * and do stuff like:
 * Migrate from one version to another
 */
function fer_init() {
    if (get_option('fer_version') !== FER_VERSION) {
        
        // Migrate from 1.0 to 1.1
        if (get_option('fer_version') === '1.0') {
            // Add new columns to equipment table
            global $wpdb;
            $wpdb->query($sql);
            
            // Update plugin version
            update_option('fer_version', '1.1');
        }
        
    }
}
add_action( 'init', 'fer_init' );

// Debug log function
function fer_debug_log($message, $data = null) {
    if (WP_DEBUG) {
        if ($data) {
            error_log(sprintf(
                '[Film Equipment Rental] %s: %s',
                $message,
                print_r($data, true)
            ));
        } else {
            error_log(sprintf('[Film Equipment Rental] %s', $message));
        }
    }
}
