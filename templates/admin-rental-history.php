<?php 
if (!defined('ABSPATH')) exit;

global $wpdb;
$sessions_table = $wpdb->prefix . 'rental_sessions';
$earnings_table = $wpdb->prefix . 'equipment_earnings';
$equipment_table = $wpdb->prefix . 'film_equipment';

$sessions = $wpdb->get_results("
    SELECT 
        rs.*,
        GROUP_CONCAT(e.name SEPARATOR ', ') as equipment_names,
        SUM(ee.earnings) as total_earnings,
        SUM(e.daily_rate * rs.rental_days) as standard_total
    FROM $sessions_table rs
    JOIN $earnings_table ee ON rs.id = ee.session_id
    JOIN $equipment_table e ON ee.equipment_id = e.id
    GROUP BY rs.id
    ORDER BY rs.rental_date DESC
");

?>

<div class="wrap">
    <h1 class="wp-heading-inline">Rental History</h1>
    <a href="<?php echo admin_url('admin.php?page=add-rental'); ?>" class="page-title-action" style="top:0">Add Rental Income</a>
    <button id="export-rentals" class="button">Export Rentals</button>
    <input type="file" id="import-rentals" style="display:none;" />
    <button id="import-rentals-btn" class="button">Import Rentals</button>
    
    <div class="fer-rental-history">
        <table class="wp-list-table widefat fixed striped" id="rental-history-table">
            <thead>
                <tr>
                    <th data-sort="rental_date" style="text-align:right">Date</th>
                    <th data-sort="rental_days" style="width:4%;text-align:right">Days</th>
                    <th data-sort="equipment_names" style="width:15%">Equipment</th>
                    <th data-sort="standard_total" style="text-align:right">Standard Total</th>
                    <th data-sort="total_actual_income" style="text-align:right">Actual Income</th>
                    <th data-sort="total_discount" style="text-align:right">Discount</th>
                    <th data-sort="notes" style="">Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $session): 
                    $discount_percentage = ($session->standard_total > 0) 
                        ? (($session->standard_total - $session->total_earnings) / $session->standard_total * 100) 
                        : 0;
                    ?>
                    <tr>
                        <td class="date" style="text-align:right"><?php echo esc_html(fer_format_date($session->rental_date)); ?></td>
                        <td class="number" style="text-align:right"><?php echo esc_html($session->rental_days); ?></td>
                        <td class="small"><?php echo esc_html($session->equipment_names); ?></td>
                        <td class="number" style="text-align:right"><?php echo fer_format_currency($session->standard_total); ?></td>
                        <td class="number" style="text-align:right"><?php echo fer_format_currency($session->total_earnings); ?></td>
                        <td class="number" style="text-align:right"><?php echo number_format($discount_percentage, 1); ?>%</td>
                        <td><?php echo esc_html(stripslashes($session->notes)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=add-rental&edit=' . $session->id); ?>" 
                               class="button button-small" title="Edit">✏️</a>
                            <button class="button button-small delete-rental" 
                                    data-id="<?php echo $session->id; ?>" 
                                    title="Delete">🗑️</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php fer_output_footer(); ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('rental-history-table');
    const headers = table.querySelectorAll('th[data-sort]');
    let sortDirection = 1;

    headers.forEach(header => {
        header.addEventListener('click', () => {
            const sortKey = header.getAttribute('data-sort');
            const rows = Array.from(table.querySelectorAll('tbody tr'));

            rows.sort((a, b) => {
                const aValue = a.querySelector(`td:nth-child(${header.cellIndex + 1})`).innerText.trim();
                const bValue = b.querySelector(`td:nth-child(${header.cellIndex + 1})`).innerText.trim();

                if (!isNaN(aValue) && !isNaN(bValue)) {
                    return sortDirection * (parseFloat(aValue) - parseFloat(bValue));
                }

                return sortDirection * aValue.localeCompare(bValue);
            });

            sortDirection *= -1;

            rows.forEach(row => table.querySelector('tbody').appendChild(row));
        });
    });

    document.getElementById('export-rentals').addEventListener('click', function() {
        const nonce = '<?php echo wp_create_nonce('fer_nonce'); ?>';
        window.location.href = '<?php echo admin_url('admin-ajax.php?action=fer_export_rentals&nonce='); ?>' + nonce;
    });

    document.getElementById('import-rentals-btn').addEventListener('click', function() {
        if (confirm('Warning: Importing rental history will replace all existing rental entries. Are you sure you want to continue?')) {
            document.getElementById('import-rentals').click();
        }
    });

    document.getElementById('import-rentals').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'fer_import_rentals');
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

    // Add delete rental functionality
    document.querySelectorAll('.delete-rental').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm('Are you sure you want to delete this rental entry? This cannot be undone.')) {
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'fer_delete_rental',
                        nonce: '<?php echo wp_create_nonce('fer_nonce'); ?>',
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('tr').remove();
                    } else {
                        alert('Error deleting rental: ' + data.data);
                    }
                });
            }
        });
    });
});
</script>