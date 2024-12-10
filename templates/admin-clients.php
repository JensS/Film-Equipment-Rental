<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Clients</h1>
    <a href="#" class="page-title-action" id="add-new-client">Add New</a>
    <button id="export-clients" class="button">Export Clients</button>
    <input type="file" id="import-clients" style="display:none;" />
    <button id="import-clients-btn" class="button">Import Clients</button>
    
    <table class="wp-list-table widefat fixed striped" id="clients-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $clients = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}film_clients ORDER BY name");
            foreach ($clients as $client): ?>
                <tr>
                    <td><?php echo esc_html($client->name); ?></td>
                    <td>
                        <a href="#" class="button button-small edit-client" data-id="<?php echo $client->id; ?>" data-name="<?php echo esc_attr($client->name); ?>">Edit</a>
                        <button class="button button-small delete-client" data-id="<?php echo $client->id; ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php fer_output_footer(); ?>
</div>

<div id="client-modal" style="display:none;">
    <form id="client-form">
        <?php wp_nonce_field('fer_nonce', 'fer_nonce'); ?>
        <input type="hidden" name="client_id" id="client_id">
        <table class="form-table">
            <tr>
                <th><label for="client_name">Name</label></th>
                <td>
                    <input type="text" id="client_name" name="name" class="regular-text" required>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button button-primary" value="Save Client">
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#add-new-client').click(function() {
        $('#client_id').val('');
        $('#client_name').val('');
        tb_show('Add New Client', '#TB_inline?inlineId=client-modal');
    });

    $('.edit-client').click(function() {
        $('#client_id').val($(this).data('id'));
        $('#client_name').val($(this).data('name'));
        tb_show('Edit Client', '#TB_inline?inlineId=client-modal');
    });

    $('#client-form').submit(function(e) {
        e.preventDefault();
        var data = $(this).serialize() + '&action=fer_save_client&nonce=<?php echo wp_create_nonce('fer_nonce'); ?>';
        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data);
            }
        });
    });

    $('.delete-client').click(function() {
        if (confirm('Are you sure you want to delete this client?')) {
            var data = {
                action: 'fer_delete_client',
                nonce: '<?php echo wp_create_nonce('fer_nonce'); ?>',
                id: $(this).data('id')
            };
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            });
        }
    });

    $('#export-clients').click(function() {
        const nonce = '<?php echo wp_create_nonce('fer_nonce'); ?>';
        window.location.href = '<?php echo admin_url('admin-ajax.php?action=fer_export_clients&nonce='); ?>' + nonce;
    });

    $('#import-clients-btn').click(function() {
        $('#import-clients').click();
    });

    $('#import-clients').change(function(event) {
        const file = event.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'fer_import_clients');
            formData.append('nonce', '<?php echo wp_create_nonce('fer_nonce'); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.data);
                }
            });
        }
    });
});
</script>
<?php
// Enqueue ThickBox library
add_thickbox();
?>