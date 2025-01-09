<?php

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
            'current_value' => isset($_POST['current_value']) ? floatval($_POST['current_value']) : 0,
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

use Dompdf\Dompdf;

function fer_generate_pdf() {
    if (!isset($_POST['data'])) {
        wp_send_json_error('No data provided');
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $items = $data['items'];
    $rentalDays = intval($data['rentalDays']);

    ob_start();
    ?>
    <html>
    <head>
        <style>
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h2>Rental Overview</h2>
        <p>Rental Days: <?php echo $rentalDays; ?></p>
        <table>
            <thead>
                <tr>
                    <th>Thumbnail</th>
                    <th>Equipment Name</th>
                    <th>Price per Day</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><img src="<?php echo esc_url($item['imgSrc']); ?>" width="50"></td>
                        <td><?php echo esc_html($item['name']); ?></td>
                        <td><?php echo esc_html($item['price']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('rental-overview.pdf', array('Attachment' => 0));
    exit;
}
add_action('wp_ajax_fer_generate_pdf', 'fer_generate_pdf');
add_action('wp_ajax_nopriv_fer_generate_pdf', 'fer_generate_pdf');