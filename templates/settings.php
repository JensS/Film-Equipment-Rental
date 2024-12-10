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
                $default_image = get_option('default_image', "");
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

                <?php submit_button(); ?>
            </form>
        </div>
        <?php fer_output_footer(); ?>
    </div>