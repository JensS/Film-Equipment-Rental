<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$equipment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$equipment = $equipment_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}film_equipment WHERE id = %d", $equipment_id)) : null;
$images = $equipment ? $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}film_equipment_images WHERE equipment_id = %d", $equipment_id)) : [];
?>
<div class="wrap">
    <h1>Add Equipment</h1>
    <div class="fer-equipment-form">
        <form id="fer-equipment-form" method="post">
            <?php wp_nonce_field('fer_nonce', 'fer_nonce'); ?>
            <input type="hidden" name="equipment_id" value="<?php echo esc_attr($equipment_id); ?>">
            
            <table class="form-table">

            <tr>
               <th><label for="brand">Brand</label></th>
                    <td>
                        <select id="brand" name="brand">
                            <option value="">Select Brand</option>
                            <?php foreach (fer_get_brands() as $brand): ?>
                                <option value="<?php echo esc_attr($brand); ?>" 
                                    <?php echo $equipment && isset($equipment->brand) && $equipment->brand === $brand ? 'selected' : ''; ?>>
                                    <?php echo esc_html($brand); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label for="name">Name</label></th>
                    <td>
                        <input type="text" id="name" name="name" class="regular-text" 
                               value="<?php echo $equipment ? esc_attr($equipment->name) : ''; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="category">Category</label></th>
                    <td>
                        <select id="category" name="category" required>
                            <?php foreach (fer_get_categories() as $slug => $category): ?>
                                <option value="<?php echo $slug; ?>" 
                                    <?php echo $equipment && $equipment->category === $slug ? 'selected' : ''; ?>>
                                    <?php echo esc_html($category["name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label for="short_description">Short Description</label></th>
                    <td>
                        <textarea id="short_description" name="short_description" rows="2" class="large-text"><?php echo $equipment ? esc_textarea(trim($equipment->short_description ?? '')) : ''; ?></textarea>
                        <p class="description">Brief description for the overview page</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="description">Description</label></th>
                    <td>
                        <textarea id="description" name="description" rows="5" class="large-text"><?php echo $equipment ? esc_textarea($equipment->description) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="daily_rate">Daily Rate (<?php echo get_option('fer_currency', '€'); ?></label></th>
                    <td>
                        <input type="number" id="daily_rate" name="daily_rate" step="0.01" min="0" 
                               value="<?php echo $equipment ? esc_attr($equipment->daily_rate) : ''; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="purchase_price">Purchase Price (€)</label></th>
                    <td>
                        <input type="number" id="purchase_price" name="purchase_price" step="0.01" min="0" 
                               value="<?php echo $equipment ? esc_attr($equipment->purchase_price) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="purchase_date">Purchase Date</label></th>
                    <td>
                        <input type="date" id="purchase_date" name="purchase_date" 
                               value="<?php echo $equipment ? esc_attr($equipment->purchase_date) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="current_value">Current Value (€)</label></th>
                    <td>
                        <input type="number" id="current_value" name="current_value" step="0.01" min="0" 
                               value="<?php echo $equipment ? esc_attr($equipment->current_value) : ''; ?>">
                    </td>
                </tr>

                <tr>
                    <th><label for="serial_number">Serial Number</label></th>
                    <td>
                        <input type="text" id="serial_number" name="serial_number" class="regular-text" 
                            value="<?php echo $equipment ? esc_attr($equipment->serial_number) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="status">Status</label></th>
                    <td>
                        <select id="status" name="status">
                            <option value="active" <?php echo $equipment && $equipment->status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="archived" <?php echo $equipment && $equipment->status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            <option value="sold" <?php echo $equipment && $equipment->status === 'sold' ? 'selected' : ''; ?>>Sold</option>
                            <option value="lost" <?php echo $equipment && $equipment->status === 'lost' ? 'selected' : ''; ?>>Lost</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="images">Images</label></th>
                    <td>
                        <div id="image-container">
                            <?php foreach ($images as $image): ?>
                                <div class="image-entry">
                                    <input type="url" name="images[]" class="large-text" value="<?php echo esc_url($image->url); ?>">
                                    <button type="button" class="button remove-image">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button" id="add-image">Add Image</button>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" class="button button-primary" value="<?php echo $equipment_id ? 'Update Equipment' : 'Add Equipment'; ?>">
            </p>
        </form>
    </div>
    <?php fer_output_footer(); ?>
</div>

<script>
    jQuery(document).ready(function($) {

$('#add-image').click(function() {
    var imageFrame = wp.media({
        title: 'Select Equipment Image',
        multiple: false,
        library: {
            type: 'image'
        }
    });

    imageFrame.on('select', function() {
        var attachment = imageFrame.state().get('selection').first().toJSON();
        $('#image-container').append('<div class="image-entry"><input type="url" name="images[]" class="large-text" value="' + attachment.url + '"><button type="button" class="button remove-image">Remove</button></div>');
    });

    imageFrame.open();
});

$(document).on('click', '.remove-image', function() {
    $(this).closest('.image-entry').remove();
});

// Equipment form submission
$('#fer-equipment-form').submit(function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();
    formData += '&action=fer_save_equipment';
    formData += '&nonce=' + ferAjax.nonce;
    
    $.post(ferAjax.ajaxurl, formData, function(response) {
        if (response.success) {
            window.location.href = 'admin.php?page=equipment-rental&message=saved';
        } else {
            alert('Error saving equipment: ' + (response.data || 'Unknown error'));
        }
    });
});

// Delete equipment
$('.delete-equipment').click(function() {
    if (!confirm('Are you sure you want to delete this item?')) {
        return;
    }
    
    var id = $(this).data('id');
    
    $.post(ferAjax.ajaxurl, {
        action: 'fer_delete_equipment',
        id: id,
        nonce: ferAjax.nonce
    }, function(response) {
        if (response.success) {
            window.location.reload();
        }
    });
});
});
</script>