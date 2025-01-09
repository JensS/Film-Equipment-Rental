<?php

function fer_equipment_list_shortcode($atts) {
    ob_start();
    include FER_PLUGIN_DIR . 'templates/public-list.php';
    return ob_get_clean();
}
add_shortcode('equipment_list', 'fer_equipment_list_shortcode');