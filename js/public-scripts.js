jQuery(document).ready(function($) {
    $('.fer-show-more').click(function() {
        var itemId = $(this).data('item');
        var desc = $('#desc-' + itemId);
        desc.slideToggle();
        $(this).text(desc.is(':visible') ? 'Show Less' : 'Show Details');
    });
});