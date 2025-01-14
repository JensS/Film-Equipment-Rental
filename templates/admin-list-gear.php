<?php
if (!defined('ABSPATH')) exit;

$current_page = isset($_GET['page']) ? $_GET['page'] : '';
$sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'name';
$sort_order = isset($_GET['sort_order']) && $_GET['sort_order'] === 'desc' ? 'DESC' : 'ASC';

// Define sortable columns with more granular sorting options
$sortable_columns = [
    'image' => 'image_url',
    'brand' => 'brand',
    'name' => 'name',
    'category' => 'category',
    'short_description' => 'short_description',
    'daily_rate' => 'daily_rate',
    'purchase_date' => 'purchase_date',
    'purchase_price' => 'purchase_price',
    'current_value' => 'current_value'
];

$sort_column = isset($sortable_columns[$sort_by]) ? $sortable_columns[$sort_by] : 'name';

global $wpdb;
$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}film_equipment ORDER BY $sort_column $sort_order");
$categories = fer_get_categories();
?>
<div class="wrap">
<h1 class="wp-heading-inline">Equipment Overview</h1>
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
                <th><a href="<?php echo add_query_arg(['page' => $current_page, 'sort_by' => 'image', 'sort_order' => ($sort_by === 'image' && $sort_order === 'ASC' ? 'desc' : 'asc')]); ?>">Image</a></th>
                <th style="width:20%">
                    <a href="<?php echo add_query_arg(['page' => $current_page, 'sort_by' => 'brand', 'sort_order' => ($sort_by === 'brand' && $sort_order === 'ASC' ? 'desc' : 'asc')]); ?>">Brand</a> <br/>
                    <a href="<?php echo add_query_arg(['page' => $current_page, 'sort_by' => 'name', 'sort_order' => ($sort_by === 'name' && $sort_order === 'ASC' ? 'desc' : 'asc')]); ?>">Name</a>
                </th>
                <th style="width:20%">
                    <a href="<?php echo add_query_arg(['page' => $current_page, 'sort_by' => 'category', 'sort_order' => ($sort_by === 'category' && $sort_order === 'ASC' ? 'desc' : 'asc')]); ?>">Category</a> <br/>
                    <a href="<?php echo add_query_arg(['page' => $current_page, 'sort_by' => 'short_description', 'sort_order' => ($sort_by === 'short_description' && $sort_order === 'ASC' ? 'desc' : 'asc')]); ?>">Description</a>
                </th>
                <th style="text-align:right;"><a href="<?php echo add_query_arg(['page' => $current_page, 'sort_by' => 'daily_rate', 'sort_order' => ($sort_by === 'daily_rate' && $sort_order === 'ASC' ? 'desc' : 'asc')]); ?>">Daily Rate</a></th>
                <th style="text-align:right;"><a href="<?php echo add_query_arg(['page' => $current_page, 'sort_by' => 'purchase_date', 'sort_order' => ($sort_by === 'purchase_date' && $sort_order === 'ASC' ? 'desc' : 'asc')]); ?>">Purchase Date</a></th>
                <th style="text-align:right;">
                    <a href="<?php echo add_query_arg(['page' => $current_page, 'sort_by' => 'purchase_price', 'sort_order' => ($sort_by === 'purchase_price' && $sort_order === 'ASC' ? 'desc' : 'asc')]); ?>">Purchase Price</a> <br/>
                    <a href="<?php echo add_query_arg(['page' => $current_page, 'sort_by' => 'current_value', 'sort_order' => ($sort_by === 'current_value' && $sort_order === 'ASC' ? 'desc' : 'asc')]); ?>">Current Value</a>
                </th>
                <th style="width:7%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr data-id="<?php echo $item->id; ?>">
                    <td rowspan="2">
                        <?php if ($item->image_url): ?>
                            <img src="<?php echo esc_url($item->image_url); ?>" alt="" style="max-height: 60px; object-fit: cover;">
                        <?php else: ?>
                            <div class="fer-placeholder"><?php echo $categories[$item->category]['icon']; ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="brand"><?php echo esc_html(isset($item->brand) ? $item->brand : ''); ?></div>
                        <div class="editable" data-field="name" style="font-weight:500"><?php echo esc_html($item->name); ?></div>
                    </td>
                    <td>
                        <div class="category"><?php echo esc_html($categories[$item->category]["name"]); ?></div>
                        
                    </td>
                    <td rowspan="2" class="number editable" data-field="daily_rate" style="text-align:right"><?php echo fer_format_currency($item->daily_rate); ?></td>
                    <td rowspan="2" class="date" style="text-align:right"><?php echo esc_html(fer_format_date($item->purchase_date)); ?></td>
                    <td>
                        <div class="number editable" data-field="purchase_price"><?php echo fer_format_currency($item->purchase_price); ?></div>
                    </td>
                    <td rowspan="2" class="actions">
                        <a href="<?php echo admin_url('admin.php?page=add-equipment&id=' . $item->id); ?>" class="button button-small" title="Edit">✏️</a>
                        <button class="button button-small delete-equipment" data-id="<?php echo $item->id; ?>" title="Delete">🗑️</button>
                        <button class="button button-small duplicate-equipment" data-id="<?php echo $item->id; ?>" title="Duplicate">📄</button>
                    </td>
                </tr>
                <tr data-id="<?php echo $item->id; ?>">
                    <td class="editable" data-field="serial_number" style="text-align:left">
                        SN: 
                        <?php echo esc_html(isset($item->serial_number) ? $item->serial_number : "–"); ?>
                    </td>
                    <td>
                    <div class="editable small" data-field="short_description"><?php echo esc_html(!empty($item->short_description) ? $item->short_description : "–"); ?></div>
                    </td>
                    <td class="editable number" data-field="current_value" style="text-align:right"><?php echo fer_format_currency($item->current_value); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php fer_output_footer(); ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

    document.querySelectorAll('.duplicate-equipment').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nonce = '<?php echo wp_create_nonce('fer_nonce'); ?>';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'fer_duplicate_equipment',
                    nonce: nonce,
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Inline editing
    jQuery('.editable').dblclick(function() {
        const $this = jQuery(this);
        let originalValue = $this.text().trim();
        const field = $this.data('field');
        const id = $this.closest('tr').data('id');
        let inputType = 'text';

        // Remove "SN: " from serial number value
        if (field === 'serial_number') {
            originalValue = originalValue.replace('SN: ', '').trim();
            if (originalValue == '–') {
                originalValue = '';
            }
        }

        // Handle different field types
        if (field === 'daily_rate' || field === 'purchase_price' || field === 'current_value') {
            inputType = 'number';
            originalValue = originalValue.replace(/[^\d.-]/g, '');
        } else if (field === 'short_description') {
            originalValue = originalValue === '–' ? '' : originalValue;
        }

        const $input = jQuery(`<input type="${inputType}" class="inline-edit" value="${originalValue}" />`);
        $this.html($input);
        $input.focus();

        $input.blur(function() {
            let newValue = $input.val().trim();
            if (newValue !== originalValue) {
                jQuery.ajax({
                    url: ferAjax.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'fer_update_equipment_field',
                        nonce: ferAjax.nonce,
                        id: id,
                        field: field,
                        value: newValue
                    },
                    success: function(response) {
                        if (response.success) {
                            if (field === 'daily_rate' || field === 'purchase_price' || field === 'current_value') {
                                $this.text(fer_format_currency(newValue));
                            } else if (field === 'serial_number') {
                                $this.text('SN: ' + (newValue || '–'));
                            } else if (field === 'short_description') {
                                $this.text(newValue || '–');
                            } else {
                                $this.text(newValue);
                            }
                        } else {
                            alert(response.data);
                            $this.text(originalValue);
                        }
                    },
                    error: function() {
                        alert('Error updating field');
                        $this.text(originalValue);
                    }
                });
            } else {
                if (field === 'serial_number') {
                    $this.text('SN: ' + (originalValue || '–'));
                } else if (field === 'short_description') {
                    $this.text(originalValue || '–');
                } else {
                    $this.text(originalValue);
                }
            }
        });

        $input.keypress(function(e) {
            if (e.which === 13) {
                $input.blur();
            }
        });
    });

    function fer_format_currency(amount) {
        const currency = '<?php echo get_option('fer_currency', '€'); ?>';
        const position = '<?php echo get_option('fer_currency_position', 'before'); ?>';
        const formatted = parseFloat(amount).toFixed(2);

        return position === 'before' ? currency + formatted : formatted + currency;
    }
});
</script>
