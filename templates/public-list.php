<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}film_equipment ORDER BY category, name");

// Get settings
$enable_categories = get_option('fer_enable_categories', '1');
$enable_pagination = get_option('fer_enable_pagination', '0');
$items_per_page = get_option('fer_items_per_page', 12);
$enable_grouping = get_option('fer_enable_grouping', '0');
$categories = fer_get_categories();

// Filter active items
$active_items = array_filter($items, function($item) {
    return $item->status === 'active';
});

// Group items by title and daily price if grouping is enabled
if ($enable_grouping === '1') {
    $grouped_items = [];
    foreach ($active_items as $item) {
        // Create a unique key using name and daily rate, ensuring proper type casting
        $key = strtolower(trim($item->name)) . '|' . number_format((float)$item->daily_rate, 2);
        
        if (!isset($grouped_items[$key])) {
            $grouped_items[$key] = [
                'item' => $item,
                'count' => 1
            ];
        } else {
            $grouped_items[$key]['count']++;
        }
    }
    
    // Convert to numeric array for consistency with the rest of the code
    $grouped_items = array_values($grouped_items);
} else {
    $grouped_items = array_map(function($item) {
        return [
            'item' => $item,
            'count' => 1
        ];
    }, $active_items);
}

// Pagination logic
if ($enable_pagination === '1') {
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $total_items = count($grouped_items);
    $total_pages = ceil($total_items / $items_per_page);
    $grouped_items = array_slice($grouped_items, ($paged - 1) * $items_per_page, $items_per_page);
}

?>
<div class="fer-search-bar">
    <!--
    <input type="number" id="fer-rental-days" placeholder="Days" min="1" value="1">
    <button id="fer-download-pdf">Rental Overview (pdf)</button>
-->
    <input type="text" id="fer-search-input" placeholder="Search equipment...">
</div>
<div class="fer-equipment-list">
    <?php 
    if ($enable_categories === '1') {
        // Group items by category
        $items_by_category = [];
        foreach ($grouped_items as $group) {
            $item = $group['item'];
            $items_by_category[$item->category][] = $group;
        }
        
        // Display categories
        foreach ($categories as $slug => $category):
            if (empty($items_by_category[$slug])) continue;
            
            $category_items = $items_by_category[$slug];
            if ($items_per_page > 0) {
                $category_items = array_slice($category_items, 0, $items_per_page);
            }
            ?>
            <div class="fer-category-section">
                <h2><?php echo esc_html($category["name"]); ?></h2>
                <div class="fer-items-grid">
                    <?php 
                    foreach ($category_items as $group):
                        $item = $group['item'];
                        $count = $group['count'];
                        ?>
                        <div class="fer-item">
                            <?php if ($count > 1): ?>
                                <div class="fer-item-count"><?php echo $count; ?> available</div>
                            <?php endif; ?>
                            <?php fer_render_equipment_item($item, $categories); ?>
                        </div>
                        <?php
                    endforeach;
                    ?>
                </div>
            </div>
        <?php 
        endforeach;
    } else {
        // Display without categories
        if ($items_per_page > 0) {
            $grouped_items = array_slice($grouped_items, 0, $items_per_page);
        }
        ?>
        <div class="fer-items-grid">
            <?php 
            foreach ($grouped_items as $group):
                $item = $group['item'];
                $count = $group['count'];
                ?>
                <div class="fer-item">
                    <?php if ($count > 1): ?>
                        <div class="fer-item-count"><?php echo $count; ?> available</div>
                    <?php endif; ?>
                    <?php fer_render_equipment_item($item, $categories); ?>
                </div>
                <?php
            endforeach;
            ?>
        </div>
    <?php } ?>
</div>
<?php if ($enable_pagination === '1' && $total_pages > 1): ?>
    <div class="fer-pagination">
        <?php
        echo paginate_links(array(
            'total' => $total_pages,
            'current' => $paged,
            'format' => '?paged=%#%',
            'prev_text' => __('&laquo; Previous'),
            'next_text' => __('Next &raquo;'),
        ));
        ?>
    </div>
<?php endif; ?>
<div id="fer-lightbox" class="fer-lightbox">
    <span class="fer-lightbox-close">&times;</span>
    <div class="fer-lightbox-content">
        <img id="fer-lightbox-img" src="" alt="">
    </div>
</div>
