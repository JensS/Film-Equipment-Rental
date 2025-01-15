<div class="fer-admin-footer">
    <p>
        This plugin is free as in free beer. Feel free to show some love on my 
        <a href="https://www.instagram.com/jenssage.de/" target="_blank">Instagram</a> 
        or buy me a <a href="https://ko-fi.com/jenssage" target="_blank">coffee</a> ❤️
    </p>
</div>

<script>

    var ferAjax = {
        ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('fer_nonce'); ?>',
        currency: '<?php echo get_option('fer_currency', '€'); ?>',
        currencyPosition: '<?php echo get_option('fer_currency_position', 'before'); ?>'
    };
</script>