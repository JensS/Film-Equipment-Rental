<?php
if (!defined('ABSPATH')) exit;

function fer_sanitize_categories($categories) {
    $sanitized_categories = array();

    $allowed_html = array(
        'svg' => array(
            'xmlns' => array(),
            'viewBox' => array(),
            'width' => array(),
            'height' => array(),
            'fill' => array(),
        ),
        'path' => array(
            'd' => array(),
            'fill' => array(),
        ),
        'circle' => array(
            'cx' => array(),
            'cy' => array(),
            'r' => array(),
            'fill' => array(),
        ),
        'rect' => array(
            'x' => array(),
            'y' => array(),
            'width' => array(),
            'height' => array(),
            'fill' => array(),
        ),
        'polygon' => array(
            'points' => array(),
            'fill' => array(),
        ),
        'line' => array(
            'x1' => array(),
            'y1' => array(),
            'x2' => array(),
            'y2' => array(),
            'stroke' => array(),
        ),
    );

    foreach ($categories as $slug => $category) {
        if (is_array($category) && isset($category['name']) && isset($category['icon'])) {
            $sanitized_categories[sanitize_title($slug)] = array(
                'name' => sanitize_text_field($category['name']),
                'icon' => wp_kses($category['icon'], $allowed_html)
            );
        }
    }

    return $sanitized_categories;
}


function fer_sanitize_brands($brands) {
    $brands_array = explode("\n", $brands);
    $sanitized_brands = array_map('sanitize_text_field', $brands_array);
    return implode("\n", $sanitized_brands);
}