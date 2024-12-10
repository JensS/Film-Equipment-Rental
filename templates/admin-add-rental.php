<?php
if (!defined('ABSPATH')) exit;

$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$rental_session = null;
$rental_items = array();

if ($edit_id) {
    global $wpdb;
    $rental_session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rental_sessions WHERE id = %d",
        $edit_id
    ));
    
    if ($rental_session) {
        $rental_items = $wpdb->get_results($wpdb->prepare(
            "SELECT ee.*, e.name, e.daily_rate 
            FROM {$wpdb->prefix}equipment_earnings ee
            JOIN {$wpdb->prefix}film_equipment e ON ee.equipment_id = e.id
            WHERE ee.session_id = %d",
            $edit_id
        ));
    }
}

// Get all active equipment for the dropdown
global $wpdb;
$items = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}film_equipment 
    WHERE status = 'active' 
    ORDER BY category, name"
);
?>

<div class="wrap">
    <h1><?php echo $edit_id ? 'Edit Rental Income' : 'Add Rental Income'; ?></h1>

    <div class="fer-add-rental-form">
        <form id="fer-rental-form" method="post">
            <?php wp_nonce_field('fer_rental_nonce', 'rental_nonce'); ?>
            <input type="hidden" name="session_id" value="<?php echo $edit_id; ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="rental_date">Rental Date</label></th>
                    <td>
                        <input type="date" id="rental_date" name="rental_date" 
                               value="<?php echo $rental_session ? esc_attr($rental_session->rental_date) : ''; ?>" 
                               required>
                    </td>
                </tr>
                <tr>
                    <th><label for="rental_days">Number of Days</label></th>
                    <td>
                        <input type="number" id="rental_days" name="rental_days" min="1" 
                               value="<?php echo $rental_session ? esc_attr($rental_session->rental_days) : ''; ?>" 
                               required>
                    </td>
                </tr>
                <tr>
                    <th><label>Rented Equipment</label></th>
                    <td>
                        <div id="rental-items">
                            <?php if ($rental_items): ?>
                                <?php foreach ($rental_items as $item): ?>
                                    <div class="rental-item">
                                        <select name="equipment[]" class="equipment-select" required>
                                            <option value="">Select Equipment</option>
                                            <?php foreach (FER_CATEGORIES as $slug => $category): ?>
                                                <optgroup label="<?php echo esc_attr($category); ?>">
                                                    <?php 
                                                    $category_items = array_filter($items, function($equip) use ($slug) {
                                                        return $equip->category === $slug;
                                                    });
                                                    foreach ($category_items as $equip): ?>
                                                        <option value="<?php echo $equip->id; ?>" 
                                                                data-rate="<?php echo $equip->daily_rate; ?>"
                                                                <?php selected($equip->id, $item->equipment_id); ?>>
                                                            <?php echo esc_html($equip->name); ?> 
                                                            (<?php echo fer_format_currency($equip->daily_rate); ?>/day)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" name="earnings[]" step="0.01" min="0" 
                                               value="<?php echo esc_attr($item->earnings); ?>"
                                               placeholder="Earned Amount"
                                               required>
                                        <button type="button" class="button remove-item">×</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="rental-item">
                                    <select name="equipment[]" class="equipment-select" required>
                                        <option value="">Select Equipment</option>
                                        <?php foreach (FER_CATEGORIES as $slug => $category): ?>
                                            <optgroup label="<?php echo esc_attr($category); ?>">
                                                <?php 
                                                $category_items = array_filter($items, function($item) use ($slug) {
                                                    return $item->category === $slug;
                                                });
                                                foreach ($category_items as $item): ?>
                                                    <option value="<?php echo $item->id; ?>" 
                                                            data-rate="<?php echo $item->daily_rate; ?>">
                                                        <?php echo esc_html($item->name); ?> 
                                                        (<?php echo fer_format_currency($item->daily_rate); ?>/day)
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" name="earnings[]" step="0.01" min="0" 
                                           placeholder="Earned Amount" required>
                                    <button type="button" class="button remove-item">×</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="add-item" class="button">+ Add More Equipment</button>
                    </td>
                </tr>
                <tr>
                    <th><label for="notes">Project Name</label></th>
                    <td>
                        <input type="text" id="notes" name="notes" class="regular-text"
                               value="<?php echo $rental_session ? esc_attr($rental_session->notes) : ''; ?>">
                    </td>
                </tr>
            </table>

            <div class="fer-rental-summary">
                <h3>Rental Summary</h3>
                <table>
                    <tr>
                        <th>Standard Total:</th>
                        <td id="standard-total">€0.00</td>
                    </tr>
                    <tr>
                        <th>Actual Income:</th>
                        <td id="actual-total">€0.00</td>
                    </tr>
                    <tr>
                        <th>Discount:</th>
                        <td id="total-discount">0%</td>
                    </tr>
                </table>
            </div>

            <p class="submit">
                <input type="submit" class="button button-primary" 
                       value="<?php echo $edit_id ? 'Update Rental' : 'Save Rental'; ?>">
            </p>
        </form>
    </div>
    <?php fer_output_footer(); ?>
</div>