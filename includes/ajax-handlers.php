<?php

function fer_save_equipment() {

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

    $data = array(
        'name' => isset($_POST['name']) ? wp_unslash(sanitize_text_field($_POST['name'])) : '',
        'brand' => isset($_POST['brand']) ? sanitize_text_field($_POST['brand']) : '', // Add this line
        'description' => isset($_POST['description']) ? wp_unslash(wp_kses_post($_POST['description'])) : '',
        'short_description' => isset($_POST['short_description']) ? wp_unslash(wp_kses_post($_POST['short_description'])) : '',
        'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '',
        'daily_rate' => isset($_POST['daily_rate']) ? floatval($_POST['daily_rate']) : 0,
        'purchase_price' => isset($_POST['purchase_price']) ? floatval($_POST['purchase_price']) : 0,
        'purchase_date' => isset($_POST['purchase_date']) ? sanitize_text_field($_POST['purchase_date']) : '',
        'current_value' => isset($_POST['current_value']) ? floatval($_POST['current_value']) : 0,
    );

    // Check if we're updating an existing item
    $equipment_id = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
    $images = isset($_POST['images']) ? array_map('esc_url_raw', $_POST['images']) : [];

    if ($equipment_id > 0) {
        // Update existing equipment
        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $equipment_id)
        );
        fer_debug_log('Update result', $result);

        // Update images
        $wpdb->delete("{$wpdb->prefix}film_equipment_images", ['equipment_id' => $equipment_id]);
        foreach ($images as $image) {
            $wpdb->insert("{$wpdb->prefix}film_equipment_images", [
                'equipment_id' => $equipment_id,
                'url' => $image
            ]);
        }
    } else {
        // Insert new equipment
        $result = $wpdb->insert($table, $data);
        $equipment_id = $wpdb->insert_id;
        fer_debug_log('Insert result', $result);

        // Insert images
        foreach ($images as $image) {
            $wpdb->insert("{$wpdb->prefix}film_equipment_images", [
                'equipment_id' => $equipment_id,
                'url' => $image
            ]);
        }
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
    if (!isset($_POST['rental_nonce']) || !wp_verify_nonce($_POST['rental_nonce'], 'fer_rental_nonce')) {
        wp_die('Invalid nonce');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    global $wpdb;
    $session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
    $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : null;
    $notes = isset($_POST['notes']) ? sanitize_text_field($_POST['notes']) : '';
    $rental_date = isset($_POST['rental_date']) ? sanitize_text_field($_POST['rental_date']) : '';
    $rental_days = isset($_POST['rental_days']) ? intval($_POST['rental_days']) : 0;

    $data = [
        'client_id' => $client_id,
        'notes' => $notes,
        'rental_date' => $rental_date,
        'rental_days' => $rental_days
    ];

    if ($session_id) {
        $wpdb->update("{$wpdb->prefix}rental_sessions", $data, ['id' => $session_id]);
    } else {
        $wpdb->insert("{$wpdb->prefix}rental_sessions", $data);
        $session_id = $wpdb->insert_id;
    }

    // Save equipment earnings
    $wpdb->delete("{$wpdb->prefix}equipment_earnings", ['session_id' => $session_id]);

    if (isset($_POST['equipment']) && is_array($_POST['equipment'])) {
        foreach ($_POST['equipment'] as $index => $equipment_id) {
            $earnings = isset($_POST['earnings'][$index]) ? floatval($_POST['earnings'][$index]) : 0;

            $wpdb->insert("{$wpdb->prefix}equipment_earnings", [
                'session_id' => $session_id,
                'equipment_id' => intval($equipment_id),
                'earnings' => $earnings
            ]);
        }
    }

    wp_redirect(admin_url('admin.php?page=rental-history'));
}
add_action('admin_post_save_rental', 'fer_save_rental_session');

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

function fer_import_data($data, $table) {
    global $wpdb;
    
    // Sanitize table name to prevent SQL injection
    $table = esc_sql($table);
    
    // Get table columns
    $columns = $wpdb->get_col("DESC {$table}");
    
    foreach ($data as $item) {
        // Convert all values to appropriate format
        foreach ($item as $key => $value) {
            // Skip if column doesn't exist
            if (!in_array($key, $columns)) {
                continue;
            }
            
            // Handle null values
            if ($value === null) {
                $item[$key] = null;
                continue;
            }
            
            // Handle different column types
            switch ($key) {
                case 'rental_date':
                case 'purchase_date':
                case 'created_at':
                    $item[$key] = sanitize_text_field($value);
                    break;
                    
                case 'rental_days':
                case 'id':
                case 'session_id':
                case 'equipment_id':
                case 'client_id':
                    $item[$key] = intval($value);
                    break;
                    
                    
                default:
                    $item[$key] = sanitize_text_field($value);
            }
        }

        // Remove id for insert to avoid conflicts
        $id = isset($item['id']) ? $item['id'] : null;
        unset($item['id']);
        
        // Try to insert first
        $result = $wpdb->insert($table, $item);
        
        // If insert fails, try update
        if ($result === false && $id) {
            $wpdb->update($table, $item, ['id' => $id]);
        }
    }
}

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

    fer_import_data($data, $wpdb->prefix . 'film_equipment');
    wp_send_json_success();
}
add_action('wp_ajax_fer_import_gear', 'fer_import_gear');

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
    $content = file_get_contents($file);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid JSON file: ' . json_last_error_msg());
        return;
    }

    global $wpdb;
    
    try {
        // Start transaction
        $wpdb->query('START TRANSACTION');

        if (!isset($data['sessions']) || !isset($data['earnings'])) {
            throw new Exception('Invalid data structure');
        }

        // Clear existing data
        $wpdb->query("DELETE FROM {$wpdb->prefix}equipment_earnings");
        $wpdb->query("DELETE FROM {$wpdb->prefix}rental_sessions");
        
        // Reset auto-increment
        $wpdb->query("ALTER TABLE {$wpdb->prefix}rental_sessions AUTO_INCREMENT = 1");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}equipment_earnings AUTO_INCREMENT = 1");

        // Get existing client IDs
        $existing_clients = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}film_clients");

        // Import sessions
        foreach ($data['sessions'] as $session) {
            // Check if client_id exists in the clients table, if not, set to null
            if (!empty($session['client_id']) && !in_array($session['client_id'], $existing_clients)) {
                $session['client_id'] = null;
            }

            $wpdb->insert(
                $wpdb->prefix . 'rental_sessions',
                array(
                    'id' => $session['id'],
                    'rental_date' => $session['rental_date'],
                    'rental_days' => intval($session['rental_days']),
                    'notes' => $session['notes'],
                    'package_deal' => $session['package_deal'],
                    'package_amount' => floatval($session['package_amount']),
                    'client_id' => $session['client_id'],
                    'created_at' => isset($session['created_at']) ? $session['created_at'] : current_time('mysql')
                ),
                array('%d', '%s', '%d', '%s', '%s', '%f', '%d', '%s')
            );
            
            if ($wpdb->last_error) {
                throw new Exception('Error importing session: ' . $wpdb->last_error);
            }
        }

        // Get existing equipment IDs
        $existing_equipment = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}film_equipment");

        // Import earnings, skip any with non-existent equipment IDs
        foreach ($data['earnings'] as $earning) {
            if (!in_array($earning['equipment_id'], $existing_equipment)) {
                continue; // Skip this earning if equipment doesn't exist
            }

            $wpdb->insert(
                $wpdb->prefix . 'equipment_earnings',
                array(
                    'id' => $earning['id'],
                    'session_id' => intval($earning['session_id']),
                    'equipment_id' => intval($earning['equipment_id']),
                    'earnings' => floatval($earning['earnings'])
                ),
                array('%d', '%d', '%d', '%f')
            );
            
            if ($wpdb->last_error) {
                throw new Exception('Error importing earning: ' . $wpdb->last_error);
            }
        }

        // Commit transaction
        $wpdb->query('COMMIT');
        
        wp_send_json_success('Import completed successfully');
    } catch (Exception $e) {
        // Rollback on error
        $wpdb->query('ROLLBACK');
        wp_send_json_error('Import failed: ' . $e->getMessage());
    }
}
add_action('wp_ajax_fer_import_rentals', 'fer_import_rentals');

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

    fer_import_data($data, $wpdb->prefix . 'film_clients');
    wp_send_json_success();
}
add_action('wp_ajax_fer_import_clients', 'fer_import_clients');

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

use Dompdf\Dompdf;

function fer_generate_pdf() {
    // Get the raw input
    $json_str = file_get_contents('php://input');
    $json_data = json_decode($json_str, true);

    if (!isset($json_data['action']) || $json_data['action'] !== 'fer_generate_pdf') {
        wp_send_json_error('Invalid action');
        return;
    }

    // Verify nonce from headers
    $nonce = isset($_SERVER['HTTP_X_WP_NONCE']) ? $_SERVER['HTTP_X_WP_NONCE'] : '';
    if (!wp_verify_nonce($nonce, 'fer_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    if (!isset($json_data['data'])) {
        wp_send_json_error('No data provided');
        return;
    }

    $items = $json_data['data']['items'];
    $rentalDays = intval($json_data['data']['rentalDays']);

    ob_start();
    ?>
    <html>
    <head>
        <style>
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            img { max-width: 100px; height: auto; }
        </style>
    </head>
    <body>
        <h2>Rental Overview</h2>
        <p>Rental Days: <?php echo $rentalDays; ?></p>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Equipment Name</th>
                    <th>Daily Rate</th>
                    <th>Total (<?php echo $rentalDays; ?> days)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                foreach ($items as $item): 
                    $daily_rate = floatval(preg_replace('/[^0-9.]/', '', $item['price']));
                    $item_total = $daily_rate * $rentalDays;
                    $total += $item_total;
                ?>
                    <tr>
                        <td><?php if (!empty($item['imgSrc'])): ?><img src="<?php echo esc_url($item['imgSrc']); ?>"><?php endif; ?></td>
                        <td><?php echo esc_html($item['name']); ?></td>
                        <td><?php echo esc_html($item['price']); ?></td>
                        <td><?php echo number_format($item_total, 2); ?> €</td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                    <td><strong><?php echo number_format($total, 2); ?> €</strong></td>
                </tr>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    require_once(plugin_dir_path(__FILE__) . '../vendor/autoload.php');
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Return PDF as binary data
    header('Content-Type: application/pdf');
    echo $dompdf->output();
    wp_die();
}
add_action('wp_ajax_fer_generate_pdf', 'fer_generate_pdf');
add_action('wp_ajax_nopriv_fer_generate_pdf', 'fer_generate_pdf');

function fer_duplicate_equipment() {
    check_ajax_referer('fer_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }

    if (!isset($_POST['id'])) {
        wp_send_json_error('No equipment ID provided');
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'film_equipment';
    $id = intval($_POST['id']);

    // Get the original equipment data
    $original = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);

    if (!$original) {
        wp_send_json_error('Equipment not found');
        return;
    }

    // Remove the ID to allow auto-increment
    unset($original['id']);

    // Insert the duplicated equipment
    $result = $wpdb->insert($table, $original);
    if ($result === false) {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    } else {
        wp_send_json_success(array(
            'message' => 'Equipment duplicated successfully',
            'id' => $wpdb->insert_id
        ));
    }
}
add_action('wp_ajax_fer_duplicate_equipment', 'fer_duplicate_equipment');

function fer_update_equipment_field() {
    check_ajax_referer('fer_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }

    if (!isset($_POST['id'], $_POST['field'], $_POST['value'])) {
        wp_send_json_error('Invalid request');
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'film_equipment';
    $id = intval($_POST['id']);
    $field = sanitize_text_field($_POST['field']);
    $value = sanitize_text_field($_POST['value']);

    $allowed_fields = ['name', 'daily_rate', 'purchase_price', 'short_description', 'current_value', 'serial_number'];
    if (!in_array($field, $allowed_fields)) {
        wp_send_json_error('Invalid field');
        return;
    }

    if ($field === 'daily_rate' || $field === 'purchase_price' || $field === 'current_value') {
        $value = floatval($value);
    }

    $data = [$field => $value];
    $result = $wpdb->update($table, $data, ['id' => $id]);

    if ($result === false) {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    } else {
        wp_send_json_success('Field updated successfully');
    }
}
add_action('wp_ajax_fer_update_equipment_field', 'fer_update_equipment_field');

function fer_delete_rental() {
    check_ajax_referer('fer_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }

    global $wpdb;
    $rental_table = $wpdb->prefix . 'rental_sessions';
    $earnings_table = $wpdb->prefix . 'equipment_earnings';
    
    $id = intval($_POST['id']);
    
    try {
        $wpdb->query('START TRANSACTION');
        
        // Delete associated earnings first (due to foreign key constraint)
        $wpdb->delete($earnings_table, array('session_id' => $id));
        
        // Then delete the rental session
        $result = $wpdb->delete($rental_table, array('id' => $id));
        
        if ($result === false) {
            throw new Exception($wpdb->last_error);
        }
        
        $wpdb->query('COMMIT');
        wp_send_json_success();
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error('Error deleting rental: ' . $e->getMessage());
    }
}
add_action('wp_ajax_fer_delete_rental', 'fer_delete_rental');


