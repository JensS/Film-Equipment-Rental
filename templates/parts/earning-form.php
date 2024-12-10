<?php
if (!defined('ABSPATH')) exit;

// Make sure we have the equipment object
if (!isset($equipment) || !$equipment) {
    return;
}
?>
<div class="fer-add-earning-form">
    <form id="fer-earning-form" method="post">
        <?php wp_nonce_field('fer_earning_nonce', 'earning_nonce'); ?>
        <input type="hidden" name="equipment_id" value="<?php echo esc_attr($equipment->id); ?>">
        
        <table class="form-table">
            <tr>
                <th><label for="rental_date">Rental Date</label></th>
                <td>
                    <input type="date" id="rental_date" name="rental_date" required>
                </td>
            </tr>
            <tr>
                <th><label for="rental_days">Number of Days</label></th>
                <td>
                    <input type="number" id="rental_days" name="rental_days" min="1" required>
                </td>
            </tr>
            <tr>
                <th><label for="earnings">Earnings (€)</label></th>
                <td>
                    <input type="number" id="earnings" name="earnings" step="0.01" min="0" required>
                </td>
            </tr>
            <tr>
                <th><label for="notes">Notes</label></th>
                <td>
                    <textarea id="notes" name="notes" rows="4" class="large-text"></textarea>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" class="button button-primary" value="Add Earning Record">
        </p>
    </form>
</div>