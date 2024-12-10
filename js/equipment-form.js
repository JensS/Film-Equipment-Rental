jQuery(document).ready(function($) {
    // Media uploader for equipment image
    $('#upload-image').click(function(e) {
        e.preventDefault();
        
        var imageFrame = wp.media({
            title: 'Select Equipment Image',
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        imageFrame.on('select', function() {
            var attachment = imageFrame.state().get('selection').first().toJSON();
            $('#image_url').val(attachment.url);
        });
        
        imageFrame.open();
    });
    
    // Equipment form submission
    $('#fer-equipment-form').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=fer_save_equipment';
        formData += '&nonce=' + ferAjax.nonce;
        
        $.post(ferAjax.ajaxurl, formData, function(response) {
            if (response.success) {
                window.location.href = 'admin.php?page=equipment-rental&message=saved';
            } else {
                alert('Error saving equipment: ' + (response.data || 'Unknown error'));
            }
        });
    });
    
    // Delete equipment
    $('.delete-equipment').click(function() {
        if (!confirm('Are you sure you want to delete this item?')) {
            return;
        }
        
        var id = $(this).data('id');
        
        $.post(ferAjax.ajaxurl, {
            action: 'fer_delete_equipment',
            id: id,
            nonce: ferAjax.nonce
        }, function(response) {
            if (response.success) {
                window.location.reload();
            }
        });
    });
});