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
            const earning = parseFloat($('input[name="earnings[]"]', this).val()) || 0;
            
            if (selectedOption.val()) {
                standardTotal += rate * days;
                actualTotal += earning;
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
        // Pre-fill the earnings field with the standard rate
        const days = parseInt($('#rental_days').val()) || 0;
        const rate = parseFloat($('option:selected', this).data('rate')) || 0;
        $(this).closest('.rental-item').find('input[name="earnings[]"]').val((rate * days).toFixed(2));
        calculateTotals();
    });

    $('#rental-items').on('input', 'input[name="earnings[]"]', calculateTotals);
    $('#rental_days').on('input', function() {
        // Update all earnings fields with new day count
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

    // Form submission handler
    $('#fer-rental-form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fer_save_rental',
                data: $(this).serialize(),
                nonce: $('#rental_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = 'admin.php?page=rental-history&message=added';
                } else {
                    alert('Error saving rental: ' + response.data);
                }
            }
        });
    });

    // Initialize on page load
    updateEquipmentSelects();
    calculateTotals();

    
});