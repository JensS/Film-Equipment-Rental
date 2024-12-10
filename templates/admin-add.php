<?php
if (!defined('ABSPATH')) exit;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$item = null;

if ($id) {
    global $wpdb;
    $item = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}film_equipment WHERE id = %d",
        $id
    ));
}
?>
<div class="wrap">
    <h1><?php echo $id ? 'Edit Equipment' : 'Add New Equipment'; ?></h1>
    
    <div class="fer-equipment-form">
        <form id="fer-equipment-form" method="post">
            <?php wp_nonce_field('fer_nonce', 'fer_nonce'); ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="name">Name</label></th>
                    <td>
                        <input type="text" id="name" name="name" class="regular-text" 
                               value="<?php echo $item ? esc_attr($item->name) : ''; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="category">Category</label></th>
                    <td>
                        <select id="category" name="category" required>
                            <?php foreach (FER_CATEGORIES as $slug => $name): ?>
                                <option value="<?php echo $slug; ?>" 
                                    <?php echo $item && $item->category === $slug ? 'selected' : ''; ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label for="short_description">Short Description</label></th>
                    <td>
                        <textarea id="short_description" name="short_description" rows="2" class="large-text"><?php echo $item ? esc_textarea(trim($item->short_description ?? '')) : ''; ?></textarea>
                        <p class="description">Brief description for the overview page</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="description">Description</label></th>
                    <td>
                        <textarea id="description" name="description" rows="5" class="large-text"><?php echo $item ? esc_textarea($item->description) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="daily_rate">Daily Rate (<?php echo get_option('fer_currency', '€'); ?></label></th>
                    <td>
                        <input type="number" id="daily_rate" name="daily_rate" step="0.01" min="0" 
                               value="<?php echo $item ? esc_attr($item->daily_rate) : ''; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="purchase_price">Purchase Price (€)</label></th>
                    <td>
                        <input type="number" id="purchase_price" name="purchase_price" step="0.01" min="0" 
                               value="<?php echo $item ? esc_attr($item->purchase_price) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="purchase_date">Purchase Date</label></th>
                    <td>
                        <input type="date" id="purchase_date" name="purchase_date" 
                               value="<?php echo $item ? esc_attr($item->purchase_date) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="condition_status">Condition</label></th>
                    <td>
                        <select id="condition_status" name="condition_status">
                            <option value="New" <?php echo $item && $item->condition_status === 'New' ? 'selected' : ''; ?>>New</option>
                            <option value="Excellent" <?php echo $item && $item->condition_status === 'Excellent' ? 'selected' : ''; ?>>Excellent</option>
                            <option value="Good" <?php echo $item && $item->condition_status === 'Good' ? 'selected' : ''; ?>>Good</option>
                            <option value="Fair" <?php echo $item && $item->condition_status === 'Fair' ? 'selected' : ''; ?>>Fair</option>
                            <option value="Poor" <?php echo $item && $item->condition_status === 'Poor' ? 'selected' : ''; ?>>Poor</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="image_url">Image URL</label></th>
                    <td>
                        <input type="url" id="image_url" name="image_url" class="large-text" 
                               value="<?php echo $item ? esc_url($item->image_url) : ''; ?>">
                        <button type="button" class="button media-button" id="upload-image">Upload Image</button>
                    </td>
                </tr>

                <tr>
                    <th><label for="serial_number">Serial Number</label></th>
                    <td>
                        <input type="text" id="serial_number" name="serial_number" class="regular-text" 
                            value="<?php echo $item ? esc_attr($item->serial_number) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="status">Status</label></th>
                    <td>
                        <select id="status" name="status">
                            <option value="active" <?php echo $item && $item->status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="archived" <?php echo $item && $item->status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            <option value="sold" <?php echo $item && $item->status === 'sold' ? 'selected' : ''; ?>>Sold</option>
                            <option value="lost" <?php echo $item && $item->status === 'lost' ? 'selected' : ''; ?>>Lost</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" class="button button-primary" value="<?php echo $id ? 'Update Equipment' : 'Add Equipment'; ?>">
            </p>
        </form>
    </div>
    <?php fer_output_footer(); ?>
</div>