<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}film_equipment WHERE status = 'active' ORDER BY category, name");
?>
<div class="wrap">
    <h1>Add Rental Session</h1>

    <div class="fer-rental-form">
        <form id="fer-rental-session-form" method="post">
            <?php wp_nonce_field('fer_session_nonce', 'session_nonce'); ?>
            
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
                    <th><label>Rented Equipment</label></th>
                    <td>
                        <div id="rental-items">
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
                                       placeholder="Earnings" required class="earning-input">
                                <button type="button" class="remove-item button">Remove</button>
                            </div>
                        </div>
                        <button type="button" id="add-item" class="button">Add Another Item</button>
                    </td>
                </tr>
                <tr>
                    <th><label for="notes">Notes</label></th>
                    <td>
                        <textarea id="notes" name="notes" rows="4" class="large-text"></textarea>
                    </td>
                </tr>
            </table>

            <div class="fer-rental-summary">
                <h3>Rental Summary</h3>
                <p>Total Standard Rate: <span id="standard-total">€0.00</span></p>
                <p>Actual Total: <span id="actual-total">€0.00</span></p>
                <p>Total Discount: <span id="total-discount">0%</span></p>
            </div>
            
            <p class="submit">
                <input type="submit" class="button button-primary" value="Save Rental Session">
            </p>
        </form>
    </div>
    <?php fer_output_footer(); ?>
</div>


<script>
jQuery(document).ready(function($) {
    function updateSummary() {
        let standardTotal = 0;
        let actualTotal = 0;
        const days = parseInt($('#rental_days').val()) || 0;
        
        $('.rental-item').each(function() {
            const rate = parseFloat($('select option:selected', this).data('rate')) || 0;
            const earning = parseFloat($('input[name="earnings[]"]', this).val()) || 0;
            
            standardTotal += rate * days;
            actualTotal += earning;
        });
        
        const discount = standardTotal > 0 ? 
            ((standardTotal - actualTotal) / standardTotal * 100) : 0;
        
        $('#standard-total').text('€' + standardTotal.toFixed(2));
        $('#actual-total').text('€' + actualTotal.toFixed(2));
        $('#total-discount').text(discount.toFixed(1) + '%');
    }
    
    $('#rental-items').on('change', 'select, input', updateSummary);
    $('#rental_days').on('change', updateSummary);
    
    $('#add-item').click(function() {
        const newItem = $('.rental-item:first').clone();
        newItem.find('select').val('');
        newItem.find('input').val('');
        $('#rental-items').append(newItem);
    });
    
    $('#rental-items').on('click', '.remove-item', function() {
        if ($('.rental-item').length > 1) {
            $(this).closest('.rental-item').remove();
            updateSummary();
        }
    });
});
</script>