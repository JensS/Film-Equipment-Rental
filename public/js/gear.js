jQuery(document).ready(function($) {
    // Use event delegation to handle click event
    $(document).on('click', '.fer-show-more', function() {
        var itemId = $(this).data('item');
        var desc = $('#desc-' + itemId);
        desc.slideToggle();
        $(this).text(desc.is(':visible') ? 'Show Less' : 'Show Details');
    });

    $('#fer-search-input').on('input', function() {
        var searchQuery = $(this).val().toLowerCase();
        $('.fer-item').each(function() {
            var itemNameElement = $(this).find('h4');
            if (itemNameElement.length) {
                var itemName = itemNameElement.text().toLowerCase();
                if (itemName.includes(searchQuery)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            }
        });
    });

    $('#fer-download-pdf').click(function() {
        var rentalDays = $('#fer-rental-days').val();
        if (!rentalDays || rentalDays < 1) {
            alert('Please enter a valid number of days.');
            return;
        }

        var items = [];
        $('.fer-item:visible').each(function() {
            var imgSrc = $(this).find('img').attr('src') || '';
            var nameElement = $(this).find('h4');
            var priceElement = $(this).find('.fer-daily-rate');
            if (nameElement.length && priceElement.length) {
                items.push({
                    imgSrc: imgSrc,
                    name: nameElement.text(),
                    price: priceElement.text()
                });
            }
        });

        if (items.length === 0) {
            alert('No items found to generate PDF.');
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', fer_ajax.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-WP-Nonce', fer_ajax.nonce);
        xhr.responseType = 'blob';

        xhr.onload = function() {
            if (this.status === 200) {
                var blob = new Blob([this.response], { type: 'application/pdf' });
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'rental-overview.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } else {
                console.error('Error generating PDF:', this.status);
                alert('Error generating PDF. Please try again.');
            }
        };

        xhr.send(JSON.stringify({
            action: 'fer_generate_pdf',
            data: { items: items, rentalDays: rentalDays }
        }));
    });

    // Lightbox functionality
    var lightbox = $('#fer-lightbox');
    var lightboxImg = $('#fer-lightbox-img');
    var lightboxClose = $('.fer-lightbox-close');

    $(document).on('click', '.fer-lightbox-trigger', function() {
        lightbox.show();
        lightboxImg.attr('src', $(this).attr('src'));
    });

    lightboxClose.click(function() {
        lightbox.hide();
    });

    lightbox.click(function(e) {
        if (e.target === this) {
            lightbox.hide();
        }
    });

});