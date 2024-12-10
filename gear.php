<?php
/**
 * Plugin Name: Film Equipment Rental
 * Description: Manages film equipment rental inventory with pricing and statistics
 * Version: 1.0
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


// Define default equipment categories
define('FER_DEFAULT_CATEGORIES', array(
    'cameras' => 'Cameras',
    'camera-accessories' => 'Camera Accessories',
    'lenses' => 'Lenses',
    'filtration' => 'Filtration',
    'lighting' => 'Lighting',
    'grip' => 'Grip',
    'miscellaneous' => 'Miscellaneous'
));

// Define default equipment brands
define('FER_DEFAULT_BRANDS', array(
    'Angénieux', 'Apple', 'ARRI', 'Aputure', 'Avenger', 'Bebob', 'Blackmagic', 'Canon', 'Carl Zeiss', 'Cooke', 
    'DJI', 'DZOFilm', 'Focusbug', 'Leica', 'Manfrotto', 'Matthews', 'OConnor', 'Panasonic', 'Peli Case', 
    'Preston', 'RED', 'Røde', 'Sachtler', 'SanDisk', 'Schneider', 'Sennheiser', 'Sigma', 'SmallRig', 
    'smallHD', 'Sony', 'Tentacle', 'Teradek', 'Tiffen', 'Tilta', 'Zoom', 'beyerdynamic'
));

function fer_get_categories() {
    $saved_categories = get_option('fer_categories', array());
    return !empty($saved_categories) ? $saved_categories : FER_DEFAULT_CATEGORIES;
}

function fer_get_brands() {
    $saved_brands = get_option('fer_brands', array());
    if (!empty($saved_brands) && is_string($saved_brands)) {
        $saved_brands = array_map('sanitize_text_field', explode("\n", $saved_brands));
    }
    $brands = !empty($saved_brands) ? $saved_brands : FER_DEFAULT_BRANDS;
    sort($brands);
    return $brands;
}

function fer_activate_plugin() {
    fer_debug_log('Plugin activation started');
    
    // Create tables
    fer_create_tables();
    
    // Add version to options table
    add_option('fer_version', '1.0');
    
    // Initialize categories if they don't exist
    if (!get_option('fer_categories')) {
        update_option('fer_categories', FER_DEFAULT_CATEGORIES);
    }
    
    // Initialize brands if they don't exist
    if (!get_option('fer_brands')) {
        update_option('fer_brands', FER_DEFAULT_BRANDS);
    }
    
    fer_debug_log('Plugin activation completed');
}

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


// Template for public display
function fer_public_display() {
    global $wpdb;
    $equipment_table = $wpdb->prefix . 'film_equipment';
    
    $items = $wpdb->get_results(
        "SELECT * FROM $equipment_table 
         WHERE status = 'active' 
         ORDER BY category, name"
    );
    
    $categories = fer_get_categories();
    
    ob_start();
    ?>
    <div class="fer-equipment-list">
        <?php foreach ($categories as $slug => $category): 
            $category_items = array_filter($items, function($item) use ($slug) {
                return $item->category === $slug;
            });
            
            if (empty($category_items)) continue;
            ?>
            <div class="fer-category-section">
                <h2><?php echo esc_html($category); ?></h2>
                <div class="fer-items-grid">
                    <?php foreach ($category_items as $item): ?>
                        <div class="fer-item">
                            <?php if ($item->image_url): ?>
                                <img src="<?php echo esc_url($item->image_url); ?>" 
                                     alt="<?php echo esc_attr($item->name); ?>">
                            <?php endif; ?>
                            <h3><?php echo esc_html($item->name); ?></h3>
                            <div class="fer-short-description">
                                <?php echo wp_kses_post($item->short_description); ?>
                            </div>
                            <p class="fer-daily-rate"><?php echo fer_format_currency($item->daily_rate); ?> per day</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
function fer_statistics_page() {
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $years = fer_get_available_years(); // We'll create this helper function
    $current_year = $year;
    
    require_once(plugin_dir_path(__FILE__) . 'templates/admin-statistics.php');
}

// Helper function to get all years with rentals
function fer_get_available_years() {
    global $wpdb;
    return $wpdb->get_col("
        SELECT DISTINCT YEAR(rental_date) as year 
        FROM {$wpdb->prefix}rental_sessions 
        ORDER BY year DESC
    ");
}

// Helper function to get statistics
function fer_get_statistics($year = null) {
    global $wpdb;
    
    // Year condition for queries
    $year_condition = $year ? $wpdb->prepare('WHERE YEAR(rs.rental_date) = %d', $year) : '';
    
    // Get overall rental statistics
    $overall_stats = $wpdb->get_row("
        SELECT 
            COUNT(DISTINCT rs.id) as total_rentals,
            SUM(CASE WHEN rs.package_deal = 'yes' THEN rs.package_amount ELSE ee.earnings END) as total_revenue,
            AVG(rs.rental_days) as avg_rental_days,
            SUM(e.daily_rate * rs.rental_days) as potential_revenue,
            COUNT(DISTINCT e.id) as unique_items_rented,
            (SELECT SUM(COALESCE(purchase_price, 0)) FROM {$wpdb->prefix}film_equipment) as total_investment
        FROM {$wpdb->prefix}rental_sessions rs
        LEFT JOIN {$wpdb->prefix}equipment_earnings ee ON rs.id = ee.session_id
        LEFT JOIN {$wpdb->prefix}film_equipment e ON ee.equipment_id = e.id
        $year_condition
    ");

    // Calculate average discount if there's revenue
    if ($overall_stats->potential_revenue > 0) {
        $overall_stats->avg_discount = (($overall_stats->potential_revenue - $overall_stats->total_revenue) 
            / $overall_stats->potential_revenue) * 100;
    } else {
        $overall_stats->avg_discount = 0;
    }

    // Get equipment-specific statistics
    $equipment_stats = $wpdb->get_results("
        SELECT 
            e.*,
            COUNT(DISTINCT rs.id) as rental_count,
            COALESCE(SUM(CASE WHEN rs.package_deal = 'yes' THEN rs.package_amount / ee.equipment_count ELSE ee.earnings END), 0) as total_earnings,
            AVG(rs.rental_days) as avg_rental_days,
            COALESCE(SUM(CASE WHEN rs.package_deal = 'yes' THEN rs.package_amount / ee.equipment_count ELSE ee.earnings END), 0) - e.purchase_price as net_profit,
            CASE 
                WHEN e.purchase_price > 0 
                THEN ((COALESCE(SUM(CASE WHEN rs.package_deal = 'yes' THEN rs.package_amount / ee.equipment_count ELSE ee.earnings END), 0) - e.purchase_price) / e.purchase_price * 100)
                ELSE NULL 
            END as roi_percentage,
            MAX(rs.rental_date) as last_rented
        FROM {$wpdb->prefix}film_equipment e
        LEFT JOIN (
            SELECT 
                ee.equipment_id,
                ee.session_id,
                COUNT(ee.equipment_id) as equipment_count,
                SUM(ee.earnings) as earnings
            FROM {$wpdb->prefix}equipment_earnings ee
            GROUP BY ee.equipment_id, ee.session_id
        ) ee ON e.id = ee.equipment_id
        LEFT JOIN {$wpdb->prefix}rental_sessions rs ON ee.session_id = rs.id
        $year_condition
        GROUP BY e.id
        ORDER BY net_profit DESC
    ");

    // Monthly trend data
    $monthly_trend = $wpdb->get_results("
        SELECT 
            DATE_FORMAT(rs.rental_date, '%Y-%m') as month,
            SUM(CASE WHEN rs.package_deal = 'yes' THEN rs.package_amount ELSE ee.earnings END) as revenue,
            COUNT(DISTINCT rs.id) as rental_count
        FROM {$wpdb->prefix}rental_sessions rs
        LEFT JOIN {$wpdb->prefix}equipment_earnings ee ON rs.id = ee.session_id
        $year_condition
        GROUP BY month
        ORDER BY month ASC
    ");

    // Add top clients statistics
    $top_clients = $wpdb->get_results("
        SELECT 
            c.name,
            COUNT(DISTINCT rs.id) as rental_count,
            SUM(CASE WHEN rs.package_deal = 'yes' THEN rs.package_amount ELSE ee.earnings END) as total_revenue
        FROM {$wpdb->prefix}film_clients c
        LEFT JOIN {$wpdb->prefix}rental_sessions rs ON c.id = rs.client_id
        LEFT JOIN {$wpdb->prefix}equipment_earnings ee ON rs.id = ee.session_id
        $year_condition
        GROUP BY c.id
        ORDER BY total_revenue DESC
        LIMIT 5
    ");

    return array(
        'overall' => $overall_stats,
        'equipment' => $equipment_stats,
        'monthly_trend' => $monthly_trend,
        'top_clients' => $top_clients
    );
}

function fer_add_rental_page() {
    require_once(plugin_dir_path(__FILE__) . 'templates/admin-add-rental.php');
}

function fer_save_rental() {
    check_ajax_referer('fer_rental_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    parse_str($_POST['data'], $form_data);
    
    global $wpdb;
    
    try {
        $wpdb->query('START TRANSACTION');
        
        $session_data = array(
            'rental_date' => sanitize_text_field($form_data['rental_date']),
            'rental_days' => intval($form_data['rental_days']),
            'notes' => sanitize_text_field($form_data['notes']),
            'package_deal' => sanitize_text_field($form_data['package_deal']),
            'package_amount' => isset($form_data['package_amount']) ? floatval($form_data['package_amount']) : null,
            'client_id' => isset($form_data['client_id']) ? intval($form_data['client_id']) : null
        );

        $session_id = !empty($form_data['session_id']) ? intval($form_data['session_id']) : 0;
        
        if ($session_id) {
            // Update existing session
            $wpdb->update(
                $wpdb->prefix . 'rental_sessions',
                $session_data,
                array('id' => $session_id)
            );
            
            // Delete existing earnings to replace with new ones
            $wpdb->delete(
                $wpdb->prefix . 'equipment_earnings',
                array('session_id' => $session_id)
            );
        } else {
            // Insert new session
            $wpdb->insert($wpdb->prefix . 'rental_sessions', $session_data);
            $session_id = $wpdb->insert_id;
        }
        
        // Insert earnings for each item
        foreach ($form_data['equipment'] as $index => $equipment_id) {
            if (empty($equipment_id)) continue;
            
            $earning_data = array(
                'session_id' => $session_id,
                'equipment_id' => intval($equipment_id),
                'earnings' => floatval($form_data['earnings'][$index])
            );
            
            $wpdb->insert($wpdb->prefix . 'equipment_earnings', $earning_data);
        }
        
        $wpdb->query('COMMIT');
        wp_send_json_success(array('session_id' => $session_id));
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_fer_save_rental', 'fer_save_rental');

function fer_rental_history_page() {
    require_once(plugin_dir_path(__FILE__) . 'templates/admin-rental-history.php');
}

function fer_admin_menu() {
    add_menu_page(
        'Equipment Rental',         
        'Rental Manager',           
        'manage_options',
        'equipment-rental',
        'fer_admin_page',
        'dashicons-video-alt'
    );
    
    add_submenu_page(
        'equipment-rental',
        'Gear Overview',       
        'Gear Overview',       
        'manage_options',
        'equipment-rental',
        'fer_admin_page'
    );

    add_submenu_page(
        'equipment-rental',
        'Add Gear',           
        'Add Gear',              
        'manage_options',
        'add-equipment',
        'fer_add_equipment_page'
    );
    
    add_submenu_page(
        'equipment-rental',
        'Rental History',  
        'Rental History', 
        'manage_options',
        'rental-history',
        'fer_rental_history_page'
    );

    add_submenu_page(
        'equipment-rental',
        'Add Rental Income',  
        'Add Rental Income', 
        'manage_options',
        'add-rental',   
        'fer_add_rental_page' 
    );

    add_submenu_page(
        'equipment-rental',
        'Clients',
        'Clients',
        'manage_options',
        'clients',
        'fer_clients_page'
    );
    
    add_submenu_page(
        'equipment-rental',
        'Statistics',
        'Statistics',
        'manage_options',
        'equipment-statistics',
        'fer_statistics_page'
    );

    // Add Settings page
    add_submenu_page(
        'equipment-rental',
        'Settings',
        'Settings',
        'manage_options',
        'fer-settings',
        'fer_settings_page'
    );

}
add_action('admin_menu', 'fer_admin_menu');

// Register shortcode for public display
function fer_equipment_list_shortcode($atts) {
    ob_start();
    include(plugin_dir_path(__FILE__) . 'templates/public-list.php');
    return ob_get_clean();
}
add_shortcode('equipment_list', 'fer_equipment_list_shortcode');

// Admin page callback functions
function fer_admin_page() {
    include(plugin_dir_path(__FILE__) . 'templates/admin-list.php');
}

function fer_add_equipment_page() {
    include(plugin_dir_path(__FILE__) . 'templates/admin-add.php');
}

function fer_add_earning_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Include the earnings template
    include(plugin_dir_path(__FILE__) . 'templates/admin-earnings.php');
}

// AJAX handlers for admin actions
function fer_save_equipment() {
    fer_debug_log('Received equipment save request', $_POST);

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fer_nonce')) {
        fer_debug_log('Nonce verification failed');
        wp_send_json_error('Security check failed');
        return;
    }

    if (!current_user_can('manage_options')) {
        fer_debug_log('Permission check failed for user', get_current_user_id());
        wp_send_json_error('Permission denied');
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'film_equipment';
    
    try {
        $data = array(
            'name' => isset($_POST['name']) ? wp_unslash(sanitize_text_field($_POST['name'])) : '',
            'brand' => isset($_POST['brand']) ? sanitize_text_field($_POST['brand']) : '', // Add this line
            'description' => isset($_POST['description']) ? wp_unslash(wp_kses_post($_POST['description'])) : '',
            'short_description' => isset($_POST['short_description']) ? wp_unslash(wp_kses_post($_POST['short_description'])) : '',
            'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '',
            'daily_rate' => isset($_POST['daily_rate']) ? floatval($_POST['daily_rate']) : 0,
            'purchase_price' => isset($_POST['purchase_price']) ? floatval($_POST['purchase_price']) : 0,
            'purchase_date' => isset($_POST['purchase_date']) ? sanitize_text_field($_POST['purchase_date']) : '',
            'image_url' => isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : ''
        );

        fer_debug_log('Prepared data for insert/update', $data);

        // Check if we're updating an existing item
        $equipment_id = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
        
        if ($equipment_id > 0) {
            // Update existing equipment
            $result = $wpdb->update(
                $table,
                $data,
                array('id' => $equipment_id)
            );
            fer_debug_log('Update result', $result);
        } else {
            // Insert new equipment
            $result = $wpdb->insert($table, $data);
            $equipment_id = $wpdb->insert_id;
            fer_debug_log('Insert result', $result);
        }

        if ($result === false) {
            fer_debug_log('Database error', $wpdb->last_error);
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        } else {
            wp_send_json_success(array(
                'message' => 'Equipment saved successfully',
                'id' => $equipment_id
            ));
        }

    } catch (Exception $e) {
        fer_debug_log('Exception caught', $e->getMessage());
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}
add_action('wp_ajax_fer_save_equipment', 'fer_save_equipment');

function fer_delete_equipment() {
    check_ajax_referer('fer_nonce', 'nonce');
    
    global $wpdb;
    $table = $wpdb->prefix . 'film_equipment';
    
    $id = intval($_POST['id']);
    $wpdb->delete($table, array('id' => $id));
    wp_send_json_success();
}
add_action('wp_ajax_fer_delete_equipment', 'fer_delete_equipment');

function fer_save_rental_session() {
    check_ajax_referer('fer_session_nonce', 'session_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    global $wpdb;
    
    try {
        $wpdb->query('START TRANSACTION');
        
        // Insert session
        $session_data = array(
            'rental_date' => sanitize_text_field($_POST['rental_date']),
            'rental_days' => intval($_POST['rental_days']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );
        
        $wpdb->insert($wpdb->prefix . 'rental_sessions', $session_data);
        $session_id = $wpdb->insert_id;
        
        // Insert earnings
        $equipment_ids = $_POST['equipment'];
        $earnings = $_POST['earnings'];
        
        foreach ($equipment_ids as $index => $equipment_id) {
            if (empty($equipment_id)) continue;
            
            $earning_data = array(
                'session_id' => $session_id,
                'equipment_id' => intval($equipment_id),
                'earnings' => floatval($earnings[$index])
            );
            
            $wpdb->insert($wpdb->prefix . 'equipment_earnings', $earning_data);
        }
        
        $wpdb->query('COMMIT');
        wp_send_json_success(array('session_id' => $session_id));
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}
add_action('wp_ajax_fer_save_rental_session', 'fer_save_rental_session');

function fer_all_earnings_page() {
    global $wpdb;
    $sessions_table = $wpdb->prefix . 'rental_sessions';
    $earnings_table = $wpdb->prefix . 'equipment_earnings';
    $equipment_table = $wpdb->prefix . 'film_equipment';
    
    $sessions = $wpdb->get_results("
        SELECT 
            rs.*,
            GROUP_CONCAT(e.name SEPARATOR ', ') as equipment_names,
            SUM(ee.earnings) as total_earnings,
            SUM(e.daily_rate * rs.rental_days) as standard_total,
            (SUM(e.daily_rate * rs.rental_days) - SUM(ee.earnings)) as total_discount
        FROM $sessions_table rs
        JOIN $earnings_table ee ON rs.id = ee.session_id
        JOIN $equipment_table e ON ee.equipment_id = e.id
        GROUP BY rs.id
        ORDER BY rs.rental_date DESC
    ");
    
    ?>
    <div class="wrap">
        <h1>All Earnings</h1>
        
        <div class="fer-info-box">
            <p>Overview of all rental earnings across all equipment.</p>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Days</th>
                    <th>Equipment</th>
                    <th>Standard Total</th>
                    <th>Actual Earnings</th>
                    <th>Discount</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $session): 
                    $discount_percentage = ($session->standard_total > 0) 
                        ? ($session->total_discount / $session->standard_total * 100) 
                        : 0;
                    ?>
                    <tr>
                        <td><?php echo esc_html($session->rental_date); ?></td>
                        <td><?php echo esc_html($session->rental_days); ?></td>
                        <td><?php echo esc_html($session->equipment_names); ?></td>
                        <td><?php echo fer_format_currency($session->standard_total); ?></td>
                        <td><?php echo fer_format_currency($session->total_earnings); ?></td>
                        <td><?php echo number_format($discount_percentage, 1); ?>%</td>
                        <td><?php echo esc_html($session->notes); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=rental-details&session_id=' . $session->id); ?>" 
                               class="button button-small">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php fer_output_footer(); ?>
    </div>
    <?php
}

function fer_rental_details_page() {
    global $wpdb;
    $session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
    
    if (!$session_id) {
        wp_die('Invalid session ID');
    }
    
    $session = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}rental_sessions WHERE id = %d
    ", $session_id));
    
    if (!$session) {
        wp_die('Session not found');
    }
    
    $items = $wpdb->get_results($wpdb->prepare("
        SELECT 
            e.name,
            e.daily_rate,
            ee.earnings,
            (e.daily_rate * %d) as standard_total,
            ((e.daily_rate * %d) - ee.earnings) as discount_amount
        FROM {$wpdb->prefix}equipment_earnings ee
        JOIN {$wpdb->prefix}film_equipment e ON ee.equipment_id = e.id
        WHERE ee.session_id = %d
    ", $session->rental_days, $session->rental_days, $session_id));
    
    ?>
    <div class="wrap">
        <h1>Rental Session Details</h1>
        
        <div class="fer-session-details">
            <h2>Session Information</h2>
            <table class="form-table">
                <tr>
                    <th>Date</th>
                    <td><?php echo esc_html($session->rental_date); ?></td>
                </tr>
                <tr>
                    <th>Duration</th>
                    <td><?php echo esc_html($session->rental_days); ?> days</td>
                </tr>
                <tr>
                    <th>Notes</th>
                    <td><?php echo esc_html($session->notes); ?></td>
                </tr>
            </table>
            
            <h2>Rented Equipment</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Daily Rate</th>
                        <th>Standard Total</th>
                        <th>Actual Earnings</th>
                        <th>Discount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $discount_percentage = ($item->standard_total > 0) 
                            ? ($item->discount_amount / $item->standard_total * 100) 
                            : 0;
                        ?>
                        <tr>
                            <td><?php echo esc_html($item->name); ?></td>
                            <td><?php echo fer_format_currency($item->daily_rate); ?></td>
                            <td><?php echo fer_format_currency($item->standard_total); ?></td>
                            <td><?php echo fer_format_currency($item->earnings); ?></td>
                            <td><?php echo number_format($discount_percentage, 1); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}


function fer_output_footer() {

    ?>
    <div class="fer-admin-footer">
        <p>
            This plugin is free as in free beer. Feel free to show some love on my 
            <a href="https://www.instagram.com/jenssage.de/" target="_blank">Instagram</a> 
            or buy me a <a href="https://ko-fi.com/jenssage" target="_blank">coffee</a> ❤️
        </p>
    </div>
    <?php
}

function fer_enqueue_scripts($hook) {
    // Debug the current page hook if needed
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Current admin page hook: ' . $hook);
    }
 
    // Base URL for plugin assets
    $plugin_url = plugins_url('', __FILE__);
 
    // Common properties for all AJAX-enabled scripts
    $ajax_data = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fer_nonce')
    );
 
    // Equipment list page (main plugin page)
    if (strpos($hook, 'equipment-rental') !== false) {
        wp_enqueue_script(
            'fer-equipment-form',
            $plugin_url . '/js/equipment-form.js',
            array('jquery'),
            '1.0',
            true
        );
        wp_localize_script('fer-equipment-form', 'ferAjax', $ajax_data);
    }
 
    // Add/Edit equipment page
    if (strpos($hook, 'add-equipment') !== false) {
        wp_enqueue_media();
        wp_enqueue_script(
            'fer-equipment-form',
            $plugin_url . '/js/equipment-form.js',
            array('jquery', 'media-upload'),
            '1.0',
            true
        );
        wp_localize_script('fer-equipment-form', 'ferAjax', $ajax_data);
    }
 
    // Add rental page
    if (strpos($hook, 'add-rental') !== false) {
        wp_enqueue_script(
            'fer-rental-form',
            $plugin_url . '/js/rental-form.js',
            array('jquery'),
            '1.0',
            true
        );
        wp_localize_script('fer-rental-form', 'ferAjax', array_merge($ajax_data, array(
            'currency' => get_option('fer_currency', '€'),
            'currencyPosition' => get_option('fer_currency_position', 'before')
        )));
    }
 
    // Statistics page
    if (strpos($hook, 'equipment-statistics') !== false) {
        wp_enqueue_script(
            'fer-statistics',
            $plugin_url . '/js/statistics.js',
            array('jquery', 'chart-js'),
            '1.0',
            true
        );
        wp_localize_script('fer-statistics', 'ferAjax', $ajax_data);
    }
 
    // Settings page
    if (strpos($hook, 'fer-settings') !== false) {
        wp_enqueue_media();
        wp_enqueue_script(
            'fer-settings-form',
            $plugin_url . '/js/settings-form.js',
            array('jquery', 'media-upload'),
            '1.0',
            true
        );
        wp_localize_script('fer-settings-form', 'ferAjax', $ajax_data);
    }
 
    // Enqueue styles for all plugin pages
    if (strpos($hook, 'equipment-rental') !== false || 
        strpos($hook, 'add-equipment') !== false || 
        strpos($hook, 'add-rental') !== false || 
        strpos($hook, 'rental-history') !== false ||
        strpos($hook, 'equipment-statistics') !== false ||
        strpos($hook, 'fer-settings') !== false) {
        
        wp_enqueue_style(
            'fer-admin-styles',
            $plugin_url . '/css/style.css'
        );
    }
}
add_action('admin_enqueue_scripts', 'fer_enqueue_scripts');
 
 // For the frontend styles (public equipment list)
 function fer_enqueue_public_styles() {
    if(is_singular()){ 
        wp_enqueue_style(
            'fer-public-styles',
            plugins_url('public/css/style.css', __FILE__)
        );
        
        wp_enqueue_script('jquery');
        
        // Enqueue the public script
        wp_enqueue_script(
            'fer-public-script',
            plugins_url('public/js/gear.js', __FILE__),
            array('jquery'),
            '1.0',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'fer_enqueue_public_styles');

function fer_register_settings() {
    register_setting('fer_options', 'fer_currency');
    register_setting('fer_options', 'fer_currency_position');
    register_setting('fer_options', 'fer_date_format');
    register_setting('fer_options', 'fer_items_per_page', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint'
    ));
    register_setting('fer_options', 'fer_enable_categories');

    register_setting('fer_options', 'fer_default_image', 
        array('sanitize_callback' => 'esc_url_raw')
    );
    
    register_setting('fer_options', 'fer_categories', array(
        'type' => 'array',
        'sanitize_callback' => 'fer_sanitize_categories'
    ));

    register_setting('fer_options', 'fer_brands', array(
        'type' => 'array',
        'sanitize_callback' => 'fer_sanitize_brands'
    ));
}

function fer_sanitize_categories($categories) {
    if (!is_array($categories)) {
        return FER_DEFAULT_CATEGORIES;
    }
    
    $sanitized = array();
    foreach ($categories as $slug => $name) {
        $sanitized[sanitize_key($slug)] = sanitize_text_field($name);
    }
    return $sanitized;
}

function fer_sanitize_brands($brands) {
    if (is_string($brands)) {
        $brands = explode("\n", $brands);
    }
    if (!is_array($brands)) {
        return FER_DEFAULT_BRANDS;
    }
    
    $sanitized = array();
    foreach ($brands as $brand) {
        $sanitized[] = sanitize_text_field($brand);
    }
    sort($sanitized);
    return $sanitized;
}

add_action('admin_init', 'fer_register_settings');

function fer_settings_page() {
    include(plugin_dir_path(__FILE__) . 'templates/settings.php');
}

function fer_format_currency($amount) {
    $currency = get_option('fer_currency', '€');
    $position = get_option('fer_currency_position', 'before');
    $formatted = number_format($amount, 2);
    
    return $position === 'before' ? $currency . $formatted : $formatted . $currency;
}

function fer_clients_page() {
    include(plugin_dir_path(__FILE__) . 'templates/admin-clients.php');
}

function fer_save_client() {
    check_ajax_referer('fer_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'film_clients';
    
    try {
        $data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : ''
        );

        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
        
        if ($client_id > 0) {
            // Update existing client
            $result = $wpdb->update(
                $table,
                $data,
                array('id' => $client_id)
            );
        } else {
            // Insert new client
            $result = $wpdb->insert($table, $data);
            $client_id = $wpdb->insert_id;
        }

        if ($result === false) {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        } else {
            wp_send_json_success(array(
                'message' => 'Client saved successfully',
                'id' => $client_id
            ));
        }

    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}
add_action('wp_ajax_fer_save_client', 'fer_save_client');

function fer_delete_client() {
    check_ajax_referer('fer_nonce', 'nonce');
    
    global $wpdb;
    $table = $wpdb->prefix . 'film_clients';
    
    $id = intval($_POST['id']);
    $wpdb->delete($table, array('id' => $id));
    wp_send_json_success();
}
add_action('wp_ajax_fer_delete_client', 'fer_delete_client');

function fer_export_gear() {
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'fer_nonce')) {
        error_log('Nonce verification failed for export gear');
        echo '-1';
        exit;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'film_equipment';
    $items = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="gear.json"');
    echo json_encode($items);
    exit;
}
add_action('wp_ajax_fer_export_gear', 'fer_export_gear');

function fer_import_gear() {
    check_ajax_referer('fer_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }

    if (!isset($_FILES['file'])) {
        wp_send_json_error('No file uploaded');
        return;
    }

    $file = $_FILES['file']['tmp_name'];
    $data = json_decode(file_get_contents($file), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid JSON file');
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'film_equipment';

    foreach ($data as $item) {
        $existing_item = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table WHERE id = %d", $item['id']));
        if ($existing_item) {
            $item['id'] = null; // Remove ID to allow auto-increment
        }
        $wpdb->replace($table, $item);
    }

    wp_send_json_success();
}
add_action('wp_ajax_fer_import_gear', 'fer_import_gear');

function fer_export_rentals() {
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'fer_nonce')) {
        error_log('Nonce verification failed for export rentals');
        echo '-1';
        exit;
    }

    global $wpdb;
    $sessions_table = $wpdb->prefix . 'rental_sessions';
    $earnings_table = $wpdb->prefix . 'equipment_earnings';
    $sessions = $wpdb->get_results("SELECT * FROM $sessions_table", ARRAY_A);
    $earnings = $wpdb->get_results("SELECT * FROM $earnings_table", ARRAY_A);

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="rentals.json"');
    echo json_encode(array('sessions' => $sessions, 'earnings' => $earnings));
    exit;
}
add_action('wp_ajax_fer_export_rentals', 'fer_export_rentals');

function fer_import_rentals() {
    check_ajax_referer('fer_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }

    if (!isset($_FILES['file'])) {
        wp_send_json_error('No file uploaded');
        return;
    }

    $file = $_FILES['file']['tmp_name'];
    $data = json_decode(file_get_contents($file), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid JSON file');
        return;
    }

    global $wpdb;
    $sessions_table = $wpdb->prefix . 'rental_sessions';
    $earnings_table = $wpdb->prefix . 'equipment_earnings';

    foreach ($data['sessions'] as $session) {
        $existing_session = $wpdb->get_row($wpdb->prepare("SELECT id FROM $sessions_table WHERE id = %d", $session['id']));
        if ($existing_session) {
            $session['id'] = null; // Remove ID to allow auto-increment
        }
        $wpdb->replace($sessions_table, $session);
    }

    foreach ($data['earnings'] as $earning) {
        $existing_earning = $wpdb->get_row($wpdb->prepare("SELECT id FROM $earnings_table WHERE id = %d", $earning['id']));
        if ($existing_earning) {
            $earning['id'] = null; // Remove ID to allow auto-increment
        }
        $wpdb->replace($earnings_table, $earning);
    }

    wp_send_json_success();
}
add_action('wp_ajax_fer_import_rentals', 'fer_import_rentals');

function fer_export_clients() {
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'fer_nonce')) {
        error_log('Nonce verification failed for export clients');
        echo '-1';
        exit;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'film_clients';
    $clients = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="clients.json"');
    echo json_encode($clients);
    exit;
}
add_action('wp_ajax_fer_export_clients', 'fer_export_clients');

function fer_import_clients() {
    check_ajax_referer('fer_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }

    if (!isset($_FILES['file'])) {
        wp_send_json_error('No file uploaded');
        return;
    }

    $file = $_FILES['file']['tmp_name'];
    $data = json_decode(file_get_contents($file), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid JSON file');
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'film_clients';

    foreach ($data as $client) {
        $existing_client = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table WHERE id = %d", $client['id']));
        if ($existing_client) {
            $client['id'] = null; // Remove ID to allow auto-increment
        }
        $wpdb->replace($table, $client);
    }

    wp_send_json_success();
}
add_action('wp_ajax_fer_import_clients', 'fer_import_clients');