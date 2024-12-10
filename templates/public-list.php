<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}film_equipment ORDER BY category, name");

// Get settings
$enable_categories = get_option('fer_enable_categories', '1');
$items_per_page = get_option('fer_items_per_page', 12);
$default_image = get_option('fer_default_image', '');
?>
<div class="fer-equipment-list">
    <?php 
    if ($enable_categories === '1') {
        // Display with categories
        foreach (FER_CATEGORIES as $slug => $category): 
            $category_items = array_filter($items, function($item) use ($slug) {
                return $item->category === $slug && $item->status === 'active';
            });
            
            // Skip empty categories
            if (empty($category_items)) continue;
            
            // Limit items per category if set
            if ($items_per_page > 0) {
                $category_items = array_slice($category_items, 0, $items_per_page);
            }
            ?>
            <div class="fer-category-section">
                <h2><?php echo esc_html($category); ?></h2>
                <div class="fer-items-grid">
                    <?php foreach ($category_items as $item): ?>
                        <div class="fer-item" id="item-<?php echo $item->id; ?>">
                            <?php 
                            $image_url = !empty($item->image_url) ? $item->image_url : $default_image;
                            if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>" 
                                     alt="<?php echo esc_attr($item->name); ?>">
                            <?php endif; ?>
                            <h3><?php echo esc_html($item->name); ?></h3>
                            <div class="fer-short-description">
                                <?php echo wp_kses_post(trim($item->short_description ?? '')); ?>
                            </div>
                            <p class="fer-daily-rate"><?php echo fer_format_currency($item->daily_rate); ?> per day</p>
                            <button class="fer-show-more" data-item="<?php echo $item->id; ?>">
                                Show Details
                            </button>
                            <div class="fer-full-description" id="desc-<?php echo $item->id; ?>" style="display: none;">
                                <?php echo wp_kses_post(trim($item->description ?? '')); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach;
    } else {
        // Display without categories
        $active_items = array_filter($items, function($item) {
            return $item->status === 'active';
        });
        
        // Limit total items if set
        if ($items_per_page > 0) {
            $active_items = array_slice($active_items, 0, $items_per_page);
        }
        ?>
        <div class="fer-items-grid">
            <?php foreach ($active_items as $item): ?>
                <!-- Same item HTML as above -->
                <div class="fer-item" id="item-<?php echo $item->id; ?>">
                    <?php 
                    $image_url = !empty($item->image_url) ? $item->image_url : $default_image;
                    if ($image_url): ?>
                        <img src="<?php echo esc_url($image_url); ?>" 
                             alt="<?php echo esc_attr($item->name); ?>">
                    <?php endif; ?>
                    <h3><?php echo esc_html($item->name); ?></h3>
                    <div class="fer-short-description">
                        <?php echo wp_kses_post(trim($item->short_description ?? '')); ?>
                    </div>
                    <p class="fer-daily-rate"><?php echo fer_format_currency($item->daily_rate); ?> per day</p>
                    <button class="fer-show-more" data-item="<?php echo $item->id; ?>">
                        Show Details
                    </button>
                    <div class="fer-full-description" id="desc-<?php echo $item->id; ?>" style="display: none;">
                        <?php echo wp_kses_post(trim($item->description ?? '')); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php } ?>
</div>
