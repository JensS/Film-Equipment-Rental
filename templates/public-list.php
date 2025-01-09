<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}film_equipment ORDER BY category, name");

// Get settings
$enable_categories = get_option('fer_enable_categories', '1');
$items_per_page = get_option('fer_items_per_page', 12);
$categories = fer_get_categories();
?>
<div class="fer-search-bar">
    <input type="number" id="fer-rental-days" placeholder="Days" min="1" value="1">
    <button id="fer-download-pdf">Rental Overview (pdf)</button>
    <input type="text" id="fer-search-input" placeholder="Search equipment...">
</div>
<div class="fer-equipment-list">
    <?php 
    if ($enable_categories === '1') {
        // Display with categories
        foreach ($categories as $slug => $category): 
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
                <h2><?php echo esc_html($category["name"]); ?></h2>
                <div class="fer-items-grid">
                    <?php foreach ($category_items as $item): ?>
                        <div class="fer-item" id="item-<?php echo $item->id; ?>">
                            <?php 
                            $image_url = !empty($item->image_url) ? $item->image_url : '';
                            if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>" 
                                     alt="<?php echo esc_attr($item->name); ?>" 
                                     class="fer-lightbox-trigger">
                            <?php else: ?>
                                <div class="fer-placeholder"><?php echo $category['icon']; ?></div>
                            <?php endif; ?>
                            <h3><?php echo esc_html((isset($item->brand) ? $item->brand . ' ' : '') . $item->name); ?></h3>
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
                    $image_url = !empty($item->image_url) ? $item->image_url : '';
                    if ($image_url): ?>
                        <img src="<?php echo esc_url($image_url); ?>" 
                             alt="<?php echo esc_attr($item->name); ?>" 
                             class="fer-lightbox-trigger">
                    <?php else: ?>
                        <div class="fer-placeholder"><?php echo $categories[$item->category]['icon']; ?></div>
                    <?php endif; ?>
                    <h3><?php echo esc_html((isset($item->brand) ? $item->brand . ' ' : '') . $item->name); ?></h3>
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
<div id="fer-lightbox" class="fer-lightbox">
    <span class="fer-lightbox-close">&times;</span>
    <div class="fer-lightbox-content">
        <img id="fer-lightbox-img" src="" alt="">
    </div>
</div>
<script>
document.getElementById('fer-download-pdf').addEventListener('click', function() {
    var rentalDays = document.getElementById('fer-rental-days').value;
    if (!rentalDays || rentalDays < 1) {
        alert('Please enter a valid number of days.');
        return;
    }
    var items = [];
    document.querySelectorAll('.fer-item').forEach(function(item) {
        if (item.style.display !== 'none') {
            var imgSrc = item.querySelector('img') ? item.querySelector('img').src : '';
            var name = item.querySelector('h3').textContent;
            var price = item.querySelector('.fer-daily-rate').textContent;
            items.push({ imgSrc: imgSrc, name: name, price: price });
        }
    });
    var data = { items: items, rentalDays: rentalDays };
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fer_generate_pdf', data: data })
    })
    .then(response => response.blob())
    .then(blob => {
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'rental-overview.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
    });
});
</script>
