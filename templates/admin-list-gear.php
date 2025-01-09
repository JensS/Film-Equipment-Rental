<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
<h1 class="wp-heading-inline">Equipment List</h1>
    <a href="<?php echo admin_url('admin.php?page=add-equipment'); ?>" class="page-title-action" style="top:0">Add New</a>
    <button id="export-gear" class="button">Export Gear</button>
    <input type="file" id="import-gear" style="display:none;" />
    <button id="import-gear-btn" class="button">Import Gear</button>
    
    <div class="fer-info-box">
        <h3>📋 How to Display Equipment</h3>
        <p>Use the shortcode <code>[equipment_list]</code> on any page or post to display your equipment catalog.</p>
    </div>

    <table class="wp-list-table widefat fixed striped" id="equipment-table">
        <thead>
            <tr>
                <th data-sort="image">Image</th>
                <th data-sort="brand">Brand</th>
                <th data-sort="name">Name</th>
                <th data-sort="category">Category</th>
                <th data-sort="daily_rate">Daily Rate</th>
                <th data-sort="purchase_price">Purchase Price</th>
                <th data-sort="purchase_date">Purchase Date</th>
                <th data-sort="current_value">Current Value</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}film_equipment ORDER BY category, name");
            $categories = fer_get_categories();
            foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php if ($item->image_url): ?>
                            <img src="<?php echo esc_url($item->image_url); ?>" alt="" style="max-height: 60px; object-fit: cover;">
                        <?php else: ?>
                            <div class="fer-placeholder"><?php echo $categories[$item->category]['icon']; ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html(isset($item->brand) ? $item->brand : ''); ?></td>
                    <td><?php echo esc_html($item->name); ?></td>
                    <td><?php echo esc_html($categories[$item->category]["name"]); ?></td>
                    <td><?php echo fer_format_currency($item->daily_rate); ?></td>
                    <td><?php echo fer_format_currency($item->purchase_price); ?></td>
                    <td><?php echo esc_html($item->purchase_date); ?></td>
                    <td><?php echo fer_format_currency($item->current_value); ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('equipment-table');
    const headers = table.querySelectorAll('th[data-sort]');
    let sortDirection = 1;

    headers.forEach(header => {
        header.addEventListener('click', () => {
            const sortKey = header.getAttribute('data-sort');
            const rows = Array.from(table.querySelectorAll('tbody tr'));

            rows.sort((a, b) => {
                const aValue = a.querySelector(`td:nth-child(${header.cellIndex + 1})`).innerText.trim();
                const bValue = b.querySelector(`td:nth-child(${header.cellIndex + 1})`).innerText.trim();

                if (!isNaN(aValue) && !isNaN(bValue)) {
                    return sortDirection * (parseFloat(aValue) - parseFloat(bValue));
                }

                return sortDirection * aValue.localeCompare(bValue);
            });

            sortDirection *= -1;

            rows.forEach(row => table.querySelector('tbody').appendChild(row));
        });
    });

    document.getElementById('export-gear').addEventListener('click', function() {
        const nonce = '<?php echo wp_create_nonce('fer_nonce'); ?>';
        window.location.href = '<?php echo admin_url('admin-ajax.php?action=fer_export_gear&nonce='); ?>' + nonce;
    });

    document.getElementById('import-gear-btn').addEventListener('click', function() {
        document.getElementById('import-gear').click();
    });

    document.getElementById('import-gear').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'fer_import_gear');
            formData.append('nonce', '<?php echo wp_create_nonce('fer_nonce'); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data);
                }
            });
        }
    });
});
</script>
