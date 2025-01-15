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

// Get all clients for dropdown
$clients = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}film_clients ORDER BY name");
?>

<div class="wrap">
    <h1><?php echo $edit_id ? 'Edit Rental Income' : 'Add Rental Income'; ?></h1>

    <div class="fer-add-rental-form">
        <form id="fer-rental-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('fer_rental_nonce', 'rental_nonce'); ?>
            <input type="hidden" name="action" value="save_rental">
            <input type="hidden" name="session_id" value="<?php echo esc_attr($edit_id); ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="client_id">Client</label></th>
                    <td>
                        <select name="client_id" id="client_id">
                            <option value="">Select Client (Optional)</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client->id; ?>" 
                                        <?php selected($rental_session && $rental_session->client_id == $client->id); ?>>
                                    <?php echo esc_html($client->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="notes">Project Name</label></th>
                    <td>
                        <input type="text" id="notes" name="notes" class="regular-text"
                               value="<?php echo $rental_session ? esc_attr(stripslashes($rental_session->notes)) : ''; ?>">
                    </td>
                </tr>
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
                                    <?php include 'admin-add-rental-list-gear.php'; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php include 'admin-add-rental-list-gear.php'; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="add-item" class="button">+ Add More Equipment</button>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="discount-percentage">Apply Discount Percentage:</label>
                        <span style="font-weight: 400" class="small">(replaces rates for every rented item!)</span>
                    </th>
                    <td>
                        <input type="number" id="discount-percentage" step="0.01" min="0" max="100" placeholder="Discount %" style="width: 120px;">
                        <button type="button" id="apply-discount" class="button">Apply Discount</button>
        
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
                       value="<?php echo $edit_id ? 'Update Rental' : 'Add Rental'; ?>">
            </p>
        </form>
    </div>
    <?php fer_output_footer(); ?>
</div>


<script>
    // Define currency formatting function in global scope
function fer_format_currency(amount) {
    const formatted = amount.toFixed(2);
    return ferAjax.currencyPosition === 'before' 
        ? ferAjax.currency + formatted 
        : formatted + ferAjax.currency;
}

jQuery(document).ready(function($) {
    // Keep track of selected equipment
    let selectedEquipment = new Set();

    function calculateTotals() {
        let standardTotal = 0;
        let actualTotal = 0;
        const days = parseInt($('#rental_days').val()) || 0;

        $('.rental-item').each(function() {
            const selectedOption = $('select option:selected', this);
            const rate = parseFloat(selectedOption.data('rate')) || 0;
            const actualIncome = parseFloat($('input[name="earnings[]"]', this).val()) || 0;
            
            if (selectedOption.val()) {
                standardTotal += rate * days;
                actualTotal += actualIncome;
            }
        });

        const discount = standardTotal > 0 ? 
            ((standardTotal - actualTotal) / standardTotal * 100) : 0;

        $('#standard-total').text(fer_format_currency(standardTotal));
        $('#actual-total').text(fer_format_currency(actualTotal));
        $('#total-discount').text(discount.toFixed(1) + '%');
    }

    function updateEquipmentSelects() {
        // Clear the set of selected equipment
        selectedEquipment.clear();

        // Gather all currently selected equipment IDs
        $('.equipment-select').each(function() {
            const value = $(this).val();
            if (value) {
                selectedEquipment.add(value);
            }
        });

        // Update all selects to disable selected options
        $('.equipment-select').each(function() {
            const currentSelect = $(this);
            const currentValue = currentSelect.val();

            currentSelect.find('option').each(function() {
                const option = $(this);
                const optionValue = option.val();

                if (optionValue && selectedEquipment.has(optionValue) && optionValue !== currentValue) {
                    option.prop('disabled', true);
                } else {
                    option.prop('disabled', false);
                }
            });
        });
    }

    // Event handlers
    $('#rental-items').on('change', 'select', function() {
        updateEquipmentSelects();
        // Pre-fill the actual income field with the standard rate
        const days = parseInt($('#rental_days').val()) || 0;
        const rate = parseFloat($('option:selected', this).data('rate')) || 0;
        $(this).closest('.rental-item').find('input[name="earnings[]"]').val((rate * days).toFixed(2));
        calculateTotals();
    });

    $('#rental-items').on('input', 'input[name="earnings[]"]', calculateTotals);
    $('#rental_days').on('input', function() {
        // Update all actual income fields with new day count
        const days = parseInt($(this).val()) || 0;
        $('.rental-item').each(function() {
            const rate = parseFloat($('select option:selected', this).data('rate')) || 0;
            $('input[name="earnings[]"]', this).val((rate * days).toFixed(2));
        });
        calculateTotals();
    });

    $('#add-item').click(function() {
        const newItem = $('.rental-item:first').clone();
        newItem.find('select').val('');
        newItem.find('input').val('');
        $('#rental-items').append(newItem);
        updateEquipmentSelects();
    });

    $('#rental-items').on('click', '.remove-item', function() {
        if ($('.rental-item').length > 1) {
            $(this).closest('.rental-item').remove();
            updateEquipmentSelects();
            calculateTotals();
        }
    });

    $('#apply-discount').click(function() {
        const discountPercentage = parseFloat($('#discount-percentage').val()) || 0;
        if (discountPercentage > 0 && discountPercentage <= 100) {
            $('.rental-item').each(function() {
                const rate = parseFloat($('select option:selected', this).data('rate')) || 0;
                const discountedIncome = rate * (1 - (discountPercentage / 100));
                const days = parseInt($('#rental_days').val()) || 0;
                $('input[name="earnings[]"]', this).val((discountedIncome * days).toFixed(2));
            });
            calculateTotals();
        }
    });

    // Initialize
    updateEquipmentSelects();
    calculateTotals();
});
</script>