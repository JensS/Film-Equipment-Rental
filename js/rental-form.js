jQuery(document).ready(function($) {
    function calculateTotals() {
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

        $('#standard-total').text(fer_format_currency(standardTotal));
        $('#actual-total').text(fer_format_currency(actualTotal));
        $('#total-discount').text(discount.toFixed(1) + '%');
    }

    $('#rental-items').on('change', 'select, input', calculateTotals);
    $('#rental_days').on('input', calculateTotals);

    $('#add-item').click(function() {
        const newItem = $('.rental-item:first').clone();
        newItem.find('select').val('');
        newItem.find('input').val('');
        $('#rental-items').append(newItem);
    });

    $('#rental-items').on('click', '.remove-item', function() {
        if ($('.rental-item').length > 1) {
            $(this).closest('.rental-item').remove();
            calculateTotals();
        }
    });

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
});