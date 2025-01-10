<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
        <h1>Equipment Rental Settings</h1>

        <?php settings_errors(); ?>

        <div class="fer-equipment-form">
            <form method="post" action="options.php">
                <?php
                settings_fields('fer_options');
                $currency = get_option('fer_currency', '€');
                $currency_position = get_option('fer_currency_position', 'before');
                $date_format = get_option('fer_date_format', 'Y-m-d');
                $items_per_page = get_option('fer_items_per_page', 12);
                $enable_categories = get_option('fer_enable_categories', '1');
                $categories = get_option('fer_categories', FER_DEFAULT_CATEGORIES);
                $brands = fer_get_brands();
                sort($brands);
                $enable_pagination = get_option('fer_enable_pagination', '0');
                $enable_grouping = get_option('fer_enable_grouping', '0');

                // Ensure categories is an array
                if (!is_array($categories)) {
                    $categories = FER_DEFAULT_CATEGORIES;
                }
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
                        <th>Pagination</th>
                        <td>
                            <label>
                                <input type="checkbox" name="fer_enable_pagination" value="1" 
                                       <?php checked($enable_pagination, '1'); ?>>
                                Enable pagination on public page
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="items_per_page">Items Per Page</label></th>
                        <td>
                            <input type="number" id="items_per_page" name="fer_items_per_page" 
                                   value="<?php echo esc_attr($items_per_page); ?>" min="1" max="100">
                            <p class="description">Number of items to display per page on the public page</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Group Similar Items</th>
                        <td>
                            <label>
                                <input type="checkbox" name="fer_enable_grouping" value="1" 
                                       <?php checked($enable_grouping, '1'); ?>>
                                Group items with the same title and daily price
                            </label>
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
                                foreach ($categories as $slug => $category): ?>
                                    <div class="category-item">
                                        <input type="text" 
                                               name="fer_categories[<?php echo esc_attr($slug); ?>][name]" 
                                               value="<?php echo esc_attr($category['name']); ?>"
                                               class="regular-text">
                                        <textarea name="fer_categories[<?php echo esc_attr($slug); ?>][icon]" 
                                                  class="large-text code" rows="3"><?php echo esc_textarea($category['icon']); ?></textarea>
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
            '<input type="text" name="fer_categories[new_' + timestamp + '][name]" value="" class="regular-text">' +
            '<textarea name="fer_categories[new_' + timestamp + '][icon]" class="large-text code" rows="3"></textarea>' +
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