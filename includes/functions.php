<?php


function fer_equipment_list_shortcode($atts) {
    ob_start();
    include FER_PLUGIN_DIR . 'templates/public-list.php';
    return ob_get_clean();
}
add_shortcode('equipment_list', 'fer_equipment_list_shortcode');

function fer_get_categories() {
    $saved_categories = get_option('fer_categories', array());
    
    // Check if saved_categories is a string (old format) or invalid
    if (!is_array($saved_categories) || empty($saved_categories)) {
        // Delete the invalid option and return defaults
        delete_option('fer_categories');
        return FER_DEFAULT_CATEGORIES;
    }
    
    // Validate structure of each category
    foreach ($saved_categories as $slug => $category) {
        if (!is_array($category) || !isset($category['name']) || !isset($category['icon'])) {
            // If any category is invalid, return defaults
            delete_option('fer_categories');
            return FER_DEFAULT_CATEGORIES;
        }
    }
    
    return $saved_categories;
}

function fer_register_settings() {
    register_setting('fer_options', 'fer_currency');
    register_setting('fer_options', 'fer_currency_position');
    register_setting('fer_options', 'fer_date_format');
    register_setting('fer_options', 'fer_items_per_page');
    register_setting('fer_options', 'fer_enable_categories');
    register_setting('fer_options', 'fer_categories');
    register_setting('fer_options', 'fer_brands');
    register_setting('fer_options', 'fer_enable_pagination');
    register_setting('fer_options', 'fer_enable_grouping');
}
add_action('admin_init', 'fer_register_settings');


function fer_get_brands() {
    $saved_brands = get_option('fer_brands', array());
    if (!empty($saved_brands) && is_string($saved_brands)) {
        $saved_brands = array_map('sanitize_text_field', explode("\n", $saved_brands));
    }
    $brands = !empty($saved_brands) ? $saved_brands : FER_DEFAULT_BRANDS;
    sort($brands);
    return $brands;
}

function fer_format_currency($amount) {
    $currency = get_option('fer_currency', '€');
    $position = get_option('fer_currency_position', 'before');
    if ($amount == null OR !is_numeric($amount)) {
        $amount = 0.00;
    } 
    $formatted = number_format($amount , 2);
    
    return $position === 'before' ? $currency . $formatted : $formatted . $currency;
}

function fer_get_available_years() {
    global $wpdb;
    return $wpdb->get_col("
        SELECT DISTINCT YEAR(rental_date) as year 
        FROM {$wpdb->prefix}rental_sessions 
        ORDER BY year DESC
    ");
}

function fer_format_date($date_string) {
    if (empty($date_string) || $date_string === '0000-00-00') {
        return '–';
    }
    
    // Convert the date string to a timestamp
    $timestamp = strtotime($date_string);
    if (!$timestamp) {
        return '–';
    }
    
    // Use WordPress's date_i18n function which respects both the date format setting
    // and the site's locale setting
    return date_i18n(get_option('date_format'), $timestamp);
}

function fer_get_statistics($year = null) {
    global $wpdb;
    $year_condition = $year
        ? $wpdb->prepare('WHERE YEAR(rental_date) = %d', $year)
        : '';

    // Query #1: overall stats without large JOIN
    $overall_stats = $wpdb->get_row("
        SELECT
            COUNT(id) AS total_rentals,
            SUM(
                CASE WHEN package_deal = 'yes'
                     THEN package_amount
                     ELSE COALESCE((
                         SELECT SUM(earnings)
                         FROM {$wpdb->prefix}equipment_earnings
                         WHERE session_id = rs.id
                     ), 0)
                END
            ) AS total_revenue,
            AVG(rental_days) AS avg_rental_days
        FROM {$wpdb->prefix}rental_sessions rs
        $year_condition
    ");

    // Subquery #2: potential revenue
    $potential_revenue = $wpdb->get_var("
        SELECT SUM(e.daily_rate * rs.rental_days)
        FROM {$wpdb->prefix}rental_sessions rs
        JOIN {$wpdb->prefix}equipment_earnings ee ON rs.id = ee.session_id
        JOIN {$wpdb->prefix}film_equipment e ON e.id = ee.equipment_id
        $year_condition
    ");

    // Subquery #3: unique items rented
    $unique_items = $wpdb->get_var("
        SELECT COUNT(DISTINCT e.id)
        FROM {$wpdb->prefix}rental_sessions rs
        JOIN {$wpdb->prefix}equipment_earnings ee ON rs.id = ee.session_id
        JOIN {$wpdb->prefix}film_equipment e ON e.id = ee.equipment_id
        $year_condition
    ");

    // Subquery #4: total investment
    $total_investment = $wpdb->get_var("
        SELECT SUM(COALESCE(purchase_price, 0))
        FROM {$wpdb->prefix}film_equipment
    ");

    // Assign
    $overall_stats->potential_revenue = (float) $potential_revenue;
    $overall_stats->unique_items_rented = (int) $unique_items;
    $overall_stats->total_investment = (float) $total_investment;

    // Correct discount
    if ($overall_stats->potential_revenue > 0) {
        $overall_stats->avg_discount = (
            ($overall_stats->potential_revenue - $overall_stats->total_revenue)
            / $overall_stats->potential_revenue
        ) * 100;
    } else {
        $overall_stats->avg_discount = 0;
    }

    // Fix equipment earnings and ROI display (use CASE for package deals, then sum earnings)
    $equipment_stats = $wpdb->get_results("
        SELECT 
            e.*,
            COUNT(DISTINCT rs.id) as rental_count,
            COALESCE(SUM(ee.earnings), 0) as total_earnings,
            AVG(rs.rental_days) as avg_rental_days,
            COALESCE(SUM(ee.earnings), 0) - e.purchase_price as net_profit,
            ROUND(
                CASE WHEN e.purchase_price > 0 THEN
                    ((COALESCE(SUM(ee.earnings), 0) - e.purchase_price) / e.purchase_price * 100)
                ELSE 0 END
            , 1) as roi_percentage,
            MAX(rs.rental_date) as last_rented
        FROM {$wpdb->prefix}film_equipment e
        LEFT JOIN {$wpdb->prefix}equipment_earnings ee ON e.id = ee.equipment_id
        LEFT JOIN {$wpdb->prefix}rental_sessions rs ON ee.session_id = rs.id
        $year_condition
        GROUP BY e.id
        ORDER BY net_profit DESC
    ");

    // Monthly trend data with fixed revenue calculation
    $monthly_trend = $wpdb->get_results("
        SELECT 
            DATE_FORMAT(rs.rental_date, '%Y-%m') as month,
            SUM(
                CASE WHEN rs.package_deal = 'yes'
                     THEN rs.package_amount
                     ELSE COALESCE((
                         SELECT SUM(ee.earnings)
                         FROM {$wpdb->prefix}equipment_earnings ee
                         WHERE ee.session_id = rs.id
                     ), 0)
                END
            ) as revenue,
            COUNT(DISTINCT rs.id) as rental_count
        FROM {$wpdb->prefix}rental_sessions rs
        $year_condition
        GROUP BY month
        ORDER BY month ASC
    ");

    // Add top clients statistics
    $top_clients = $wpdb->get_results("
        SELECT 
            c.name,
            COUNT(DISTINCT rs.id) as rental_count,
            SUM(
                CASE WHEN rs.package_deal = 'yes'
                     THEN rs.package_amount
                     ELSE COALESCE((
                         SELECT SUM(ee.earnings)
                         FROM {$wpdb->prefix}equipment_earnings ee
                         WHERE ee.session_id = rs.id
                     ), 0)
                END
            ) as total_revenue
        FROM {$wpdb->prefix}film_clients c
        LEFT JOIN {$wpdb->prefix}rental_sessions rs ON c.id = rs.client_id
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

function fer_format_structured_description($text) {
    // Remove extra whitespace and normalize line endings
    $text = trim(preg_replace('/\s+/', ' ', $text));
    
    // Pattern 1: "Key Value" pairs separated by spaces
    if (preg_match_all('/([A-Za-z\s]+(?:\([^)]+\))?)[\s:]+([\d.]+[″″"\'°\w\s\/]+)(?=\s[A-Z]|$)/', $text, $matches)) {
        $output = '<table class="fer-specs-table">';
        for ($i = 0; $i < count($matches[1]); $i++) {
            $key = trim($matches[1][$i]);
            $value = trim($matches[2][$i]);
            $output .= sprintf(
                '<tr><td class="fer-spec-label">%s</td><td class="fer-spec-value">%s</td></tr>',
                esc_html($key),
                esc_html($value)
            );
        }
        $output .= '</table>';
        return $output;
    }
    
    // Pattern 2: "Key: Value" format with line breaks
    if (preg_match_all('/([^:\n]+):\s*([^\n]+)(?:\n|$)/', $text, $matches)) {
        $output = '<table class="fer-specs-table">';
        for ($i = 0; $i < count($matches[1]); $i++) {
            $key = trim($matches[1][$i]);
            $value = trim($matches[2][$i]);
            $output .= sprintf(
                '<tr><td class="fer-spec-label">%s</td><td class="fer-spec-value">%s</td></tr>',
                esc_html($key),
                esc_html($value)
            );
        }
        $output .= '</table>';
        return $output;
    }
    
    // If no pattern matches, return the original text with paragraphs
    return '<p>' . nl2br(esc_html($text)) . '</p>';
}

function fer_render_equipment_item($item, $categories) {
    ?>
    <div class="fer-item" id="item-<?php echo $item->id; ?>">
        <?php 
        $image_url = !empty($item->image_url) ? $item->image_url : '';
        if ($image_url): ?>
            <img src="<?php echo esc_url($image_url); ?>" 
                 alt="<?php echo esc_attr($item->name); ?>" 
                 class="fer-lightbox-trigger">
        <?php else: ?>
            <div class="fer-placeholder"><?php echo $categories[$item->category]['icon']; ?></div>
        <?php endif; ?>
        <h4><?php echo esc_html((isset($item->brand) ? $item->brand . ' ' : '') . $item->name); ?></h4>
        <div class="fer-short-description">
            <?php 
            $short_desc = trim($item->short_description ?? '');
            $long_desc = trim($item->description ?? '');
            
            if (!empty($short_desc)) {
                echo wp_kses_post($short_desc);
            } elseif (!empty($long_desc)) {
                echo wp_kses_post(wp_trim_words($long_desc, 20, '...'));
            }
            ?>
        </div>
        <p class="fer-daily-rate"><?php echo fer_format_currency($item->daily_rate); ?> per day</p>
        <button class="fer-show-more" data-item="<?php echo $item->id; ?>">
            Show Details
        </button>
        <div class="fer-full-description" id="desc-<?php echo $item->id; ?>" style="display: none;">
            <?php 
            if (!empty($long_desc)) {
                echo fer_format_structured_description($long_desc);
            } elseif (!empty($short_desc)) {
                echo fer_format_structured_description(wp_kses_post($short_desc));
            }
            ?>
        </div>
    </div>
    <?php
}

function fer_handle_import_gear() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    if (!check_ajax_referer('fer_nonce', 'nonce', false)) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    if (!isset($_FILES['file'])) {
        wp_send_json_error('No file uploaded');
        return;
    }

    $file_content = file_get_contents($_FILES['file']['tmp_name']);
    $items = json_decode($file_content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Invalid JSON file');
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'film_equipment';
    $imported = 0;

    foreach ($items as $item) {
        // Remove ID to avoid conflicts
        unset($item['id']);
        
        // Ensure all required fields are present
        $required_fields = ['name', 'category', 'daily_rate'];
        foreach ($required_fields as $field) {
            if (!isset($item[$field])) {
                continue 2; // Skip this item if missing required fields
            }
        }

        // Insert the record
        $result = $wpdb->insert(
            $table_name,
            $item,
            [
                '%s', // name
                '%s', // brand
                '%s', // serial_number
                '%s', // short_description
                '%s', // description
                '%s', // category
                '%f', // daily_rate
                '%f', // purchase_price
                '%s', // purchase_date
                '%f', // current_value
                '%s', // image_url
                '%s'  // status
            ]
        );

        if ($result) {
            $imported++;
        }
    }

    wp_send_json_success("Successfully imported $imported items");
}
add_action('wp_ajax_fer_import_gear', 'fer_handle_import_gear');