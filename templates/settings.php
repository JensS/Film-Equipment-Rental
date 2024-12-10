<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
        <h1>Equipment Rental Settings</h1>

        <?php settings_errors(); ?>

        <div class="fer-settings-form">
            <form method="post" action="options.php">
                <?php
                settings_fields('fer_options');
                $currency = get_option('fer_currency', '€');
                $currency_position = get_option('fer_currency_position', 'before');
                $date_format = get_option('fer_date_format', 'Y-m-d');
                $items_per_page = get_option('fer_items_per_page', 12);
                $enable_categories = get_option('fer_enable_categories', '1');
                $default_image = get_option('fer_default_image', "");
                $categories = get_option('fer_categories', FER_DEFAULT_CATEGORIES);
                $brands = fer_get_brands();
                sort($brands);
                ?>
                
                <h2>Currency Settings</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="currency">Currency Symbol</label></th>
                        <td>
                            <input type="text" id="currency" name="fer_currency" 
                                   value="<?php echo esc_attr($currency); ?>" class="regular-text">
                            <p class="description">Enter your currency symbol (e.g., €, $, £)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="currency_position">Currency Position</label></th>
                        <td>
                            <select id="currency_position" name="fer_currency_position">
                                <option value="before" <?php selected($currency_position, 'before'); ?>>Before amount (€99.99)</option>
                                <option value="after" <?php selected($currency_position, 'after'); ?>>After amount (99.99€)</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h2>Display Settings</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="date_format">Date Format</label></th>
                        <td>
                            <select id="date_format" name="fer_date_format">
                                <option value="Y-m-d" <?php selected($date_format, 'Y-m-d'); ?>>2024-01-31</option>
                                <option value="d/m/Y" <?php selected($date_format, 'd/m/Y'); ?>>31/01/2024</option>
                                <option value="m/d/Y" <?php selected($date_format, 'm/d/Y'); ?>>01/31/2024</option>
                                <option value="d.m.Y" <?php selected($date_format, 'd.m.Y'); ?>>31.01.2024</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="items_per_page">Items Per Page</label></th>
                        <td>
                            <input type="number" id="items_per_page" name="fer_items_per_page" 
                                   value="<?php echo esc_attr($items_per_page); ?>" min="1" max="100">
                            <p class="description">Number of items to display per category on the public page</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Categories</th>
                        <td>
                            <label>
                                <input type="checkbox" name="fer_enable_categories" value="1" 
                                       <?php checked($enable_categories, '1'); ?>>
                                Enable category grouping on public page
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="default_image">Default Image URL</label></th>
                        <td>
                            <input type="url" id="default_image" name="fer_default_image" 
                                value="<?php echo esc_url($default_image); ?>" class="regular-text">
                            <button type="button" class="button media-button" id="upload-default-image">Choose Image</button>
                            <p class="description">Default image for equipment without a specific image</p>
                            <div id="default-image-preview">
                                <?php if ($default_image): ?>
                                    <img src="<?php echo esc_url($default_image); ?>" style="max-width: 200px; margin-top: 10px;">
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                </table>

                <h2>Categories</h2>
                <table class="form-table">
                    <tr>
                        <th>Equipment Categories</th>
                        <td>
                            <div id="equipment-categories" class="sortable">
                                <?php
                                foreach ($categories as $slug => $name): ?>
                                    <div class="category-item">
                                        <input type="text" 
                                               name="fer_categories[<?php echo esc_attr($slug); ?>]" 
                                               value="<?php echo esc_attr($name); ?>"
                                               class="regular-text">
                                        <button type="button" class="button remove-category">×</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="button" id="add-category">Add Category</button>
                            <p class="description">Define equipment categories. These will be used for organizing equipment in lists and forms.</p>
                        </td>
                    </tr>
                </table>

                <h2>Brands</h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Brands</th>
                        <td>
                            <textarea name="fer_brands" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(implode("\n", $brands)); ?></textarea>
                            <input type="hidden" name="fer_brands_hidden" id="fer_brands_hidden" value="<?php echo esc_textarea(implode("\n", $brands)); ?>">
                            <p class="description">Enter one brand per line.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php fer_output_footer(); ?>
    </div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#add-category').click(function() {
        var timestamp = new Date().getTime();
        var newCategory = $('<div class="category-item">' +
            '<input type="text" name="fer_categories[new_' + timestamp + ']" value="" class="regular-text">' +
            '<button type="button" class="button remove-category">×</button>' +
            '</div>');
        $('#equipment-categories').append(newCategory);
    });

    $('#equipment-categories').on('click', '.remove-category', function() {
        if ($('.category-item').length > 1) {
            $(this).closest('.category-item').remove();
        } else {
            alert('You must keep at least one category.');
        }
    });

    $('#equipment-categories').sortable({
        placeholder: "ui-state-highlight"
    });

    $('form').submit(function() {
        $('#fer_brands_hidden').val($('textarea[name="fer_brands"]').val());
    });
});
</script>