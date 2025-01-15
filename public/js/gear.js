jQuery(document).ready(function($) {

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

    function initSlideshow() {
        $('.fer-slideshow').each(function() {
            var $slideshow = $(this);
            var $images = $slideshow.find('img');
            var currentIndex = 0;

            $images.hide().eq(currentIndex).show();

            $slideshow.find('.fer-slideshow-prev').click(function() {
                $images.eq(currentIndex).hide();
                currentIndex = (currentIndex - 1 + $images.length) % $images.length;
                $images.eq(currentIndex).show();
            });

            $slideshow.find('.fer-slideshow-next').click(function() {
                $images.eq(currentIndex).hide();
                currentIndex = (currentIndex + 1) % $images.length;
                $images.eq(currentIndex).show();
            });
        });
    }

    initSlideshow();

    // Lightbox functionality
    $('.fer-show-more').click(function() {
        var itemId = $(this).data('item');
        var $item = $('#item-' + itemId);
        var $images = $item.find('.fer-slideshow img');
        var title = $item.find('h4').text();
        var rate = $item.find('.fer-daily-rate').text();
        var shortDescription = $item.find('.fer-short-description').html();
        var longDescription = $item.find('.fer-full-description').html();

        var $lightbox = $('#fer-lightbox');
        var $slidesContainer = $lightbox.find('.fer-lightbox-slides');
        $slidesContainer.empty();

        $images.each(function() {
            var $img = $(this).clone().show();
            $slidesContainer.append($img);
        });

        $('#fer-lightbox-title').text(title);
        $('#fer-lightbox-rate').text(rate);

        if (shortDescription.trim() === longDescription.trim()) {
            $('#fer-lightbox-description').html(longDescription);
            $('#fer-lightbox-short-description').hide();
        } else {
            $('#fer-lightbox-short-description').html(shortDescription).show();
            $('#fer-lightbox-description').html(longDescription);
        }

        $lightbox.show();
        initLightboxSlideshow();
    });

    $('.fer-lightbox-close').click(function() {
        $('#fer-lightbox').hide();
    });

    $('#fer-lightbox').click(function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

    function initLightboxSlideshow() {
        var $lightbox = $('#fer-lightbox');
        var $slides = $lightbox.find('.fer-lightbox-slides img');
        var currentIndex = 0;

        $slides.hide().eq(currentIndex).show();

        $lightbox.find('.fer-lightbox-prev').click(function() {
            $slides.eq(currentIndex).hide();
            currentIndex = (currentIndex - 1 + $slides.length) % $slides.length;
            $slides.eq(currentIndex).show();
        });

        $lightbox.find('.fer-lightbox-next').click(function() {
            $slides.eq(currentIndex).hide();
            currentIndex = (currentIndex + 1) % $slides.length;
            $slides.eq(currentIndex).show();
        });
    }

    initLightboxSlideshow();

});