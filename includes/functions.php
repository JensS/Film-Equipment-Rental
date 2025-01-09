<?php

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
