jQuery(document).ready(function($) {
    $('.fer-show-more').click(function() {
        var itemId = $(this).data('item');
        var desc = $('#desc-' + itemId);
        desc.slideToggle();
        $(this).text(desc.is(':visible') ? 'Show Less' : 'Show Details');
    });
});

document.getElementById('fer-search-input').addEventListener('input', function() {
    var searchQuery = this.value.toLowerCase();
    var items = document.querySelectorAll('.fer-item');
    items.forEach(function(item) {
        var itemName = item.querySelector('h3').textContent.toLowerCase();
        if (itemName.includes(searchQuery)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

// Lightbox functionality
var lightbox = document.getElementById('fer-lightbox');
var lightboxImg = document.getElementById('fer-lightbox-img');
var lightboxClose = document.querySelector('.fer-lightbox-close');

document.querySelectorAll('.fer-lightbox-trigger').forEach(function(img) {
    img.addEventListener('click', function() {
        lightbox.style.display = 'block';
        lightboxImg.src = this.src;
    });
});

lightboxClose.addEventListener('click', function() {
    lightbox.style.display = 'none';
});

lightbox.addEventListener('click', function(e) {
    if (e.target === lightbox) {
        lightbox.style.display = 'none';
    }
});