<?php

function fer_enqueue_scripts($hook) {
    // Debug the current page hook if needed
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Current admin page hook: ' . $hook);
    }
 
    // Base URL for plugin assets
    $plugin_url = plugin_dir_url('', __FILE__);
 
    // Common properties for all AJAX-enabled scripts
    $ajax_data = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fer_nonce')
    );
 
    // Equipment list page (main plugin page)
    if (strpos($hook, 'equipment-rental') !== false) {
        wp_enqueue_script(
            'fer-equipment-form',
            fer_url() . '/js/equipment-form.js',
            array('jquery'),
            FER_VERSION,
            true
        );
        wp_localize_script('fer-equipment-form', 'ferAjax', $ajax_data);
    }
 
    // Add/Edit equipment page
    if (strpos($hook, 'add-equipment') !== false) {
        wp_enqueue_media();
        wp_enqueue_script(
            'fer-equipment-form',
            fer_url() . '/js/equipment-form.js',
            array('jquery', 'media-upload'),
            FER_VERSION,
            true
        );
        wp_localize_script('fer-equipment-form', 'ferAjax', $ajax_data);
    }
 
    // Add rental page
    if (strpos($hook, 'add-rental') !== false) {
        wp_enqueue_script(
            'fer-rental-form',
            fer_url() .'/js/rental-form.js',
            array('jquery'),
            FER_VERSION,
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
            fer_url() . '/js/statistics.js',
            array('jquery', 'chart-js'),
            FER_VERSION,
            true
        );
        wp_localize_script('fer-statistics', 'ferAjax', $ajax_data);
    }
 
    // Settings page
    if (strpos($hook, 'fer-settings') !== false) {
        wp_enqueue_media();
        wp_enqueue_script(
            'fer-settings-form',
            fer_url() . '/js/settings-form.js',
            array('jquery', 'media-upload'),
            FER_VERSION,
            true
        );
        wp_localize_script('fer-settings-form', 'ferAjax', $ajax_data);
    }
 
   
        wp_enqueue_style(
            'fer-admin-styles',
            fer_url() . '/css/style.css'
        );
    
}
add_action('admin_enqueue_scripts', 'fer_enqueue_scripts');
 
 // For the frontend styles (public equipment list)
 function fer_enqueue_public_styles() {
    if(is_singular()){ 
        wp_enqueue_style(
            'fer-public-styles',
            fer_url() . 'public/css/style.css'
        );
        
        wp_enqueue_script('jquery');
        
        // Enqueue the public script
        wp_enqueue_script(
            'fer-public-script',
            fer_url() . 'public/js/gear.js',
            array('jquery'),
            FER_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'fer_enqueue_public_styles');