<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
<h1 class="wp-heading-inline">Equipment List</h1>
    <a href="<?php echo admin_url('admin.php?page=add-equipment'); ?>" class="page-title-action">Add New</a>
    
    <div class="fer-info-box">
        <h3>📋 How to Display Equipment</h3>
        <p>Use the shortcode <code>[equipment_list]</code> on any page or post to display your equipment catalog.</p>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Daily Rate</th>
                <th>Purchase Price</th>
                <th>Purchase Date</th>
                <th>Condition</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}film_equipment ORDER BY category, name");
            foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php if ($item->image_url): ?>
                            <img src="<?php echo esc_url($item->image_url); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover;">
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($item->name); ?></td>
                    <td><?php echo esc_html(FER_CATEGORIES[$item->category]); ?></td>
                    <td><?php echo fer_format_currency($item->daily_rate); ?></td>
                    <td><?php echo fer_format_currency($item->purchase_price); ?></td>
                    <td><?php echo esc_html($item->purchase_date); ?></td>
                    <td><?php echo esc_html($item->condition_status); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=add-equipment&id=' . $item->id); ?>" class="button button-small">Edit</a>
                        <button class="button button-small delete-equipment" data-id="<?php echo $item->id; ?>">Delete</button>
                        
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php fer_output_footer(); ?>
</div>
