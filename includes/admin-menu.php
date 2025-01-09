<?php

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

function fer_admin_page() {
    include FER_PLUGIN_DIR . 'templates/admin-list-gear.php';
}

function fer_add_equipment_page() {
    include FER_PLUGIN_DIR . 'templates/admin-add-gear.php';
}

function fer_add_rental_page() {
    include FER_PLUGIN_DIR . 'templates/admin-add-rental.php';
}

function fer_rental_history_page() {
    include FER_PLUGIN_DIR . 'templates/admin-rental-history.php';
}

function fer_statistics_page() {
    include FER_PLUGIN_DIR . 'templates/admin-statistics.php';
}

function fer_clients_page() {
    include FER_PLUGIN_DIR . 'templates/admin-clients.php';
}

function fer_settings_page() {
    include FER_PLUGIN_DIR . 'templates/admin-settings.php';
}

function fer_all_earnings_page() {
    include FER_PLUGIN_DIR . 'templates/admin-earnings.php';
}

function fer_rental_details_page() {
    include FER_PLUGIN_DIR . 'templates/admin-rental-details.php';
}

function fer_output_footer() {
    include FER_PLUGIN_DIR . 'templates/admin-footer.php';
}