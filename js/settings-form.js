jQuery(document).ready(function($) {
    $('#upload-default-image').click(function(e) {
        e.preventDefault();
        
        var imageFrame = wp.media({
            title: 'Select Default Equipment Image',
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        imageFrame.on('select', function() {
            var attachment = imageFrame.state().get('selection').first().toJSON();
            $('#default_image').val(attachment.url);
            $('#default-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; margin-top: 10px;">');
        });
        
        imageFrame.open();
    });
});