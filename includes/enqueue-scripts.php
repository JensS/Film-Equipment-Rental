<?php

function fer_enqueue_scripts($hook) {
    // Debug the current page hook if needed
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Current admin page hook: ' . $hook);
    }
 
    // Base URL for plugin assets
    $plugin_url = plugin_dir_url('', __FILE__);
 
    
 
    wp_enqueue_script('jquery');
    wp_enqueue_media();
   
    wp_enqueue_style(
        'fer-admin-styles',
        fer_url() . '/admin.css'
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