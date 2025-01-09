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
        SUM(e.daily_rate * rs.rental_days) as standard_total,
        (SUM(e.daily_rate * rs.rental_days) - SUM(ee.earnings)) as total_discount,
        rs.package_deal
    FROM $sessions_table rs
    JOIN $earnings_table ee ON rs.id = ee.session_id
    JOIN $equipment_table e ON ee.equipment_id = e.id
    GROUP BY rs.id
    ORDER BY rs.rental_date DESC
");
?>

<div class="wrap">
    <h1>Rental History</h1>
    
    <div class="fer-rental-history">
        <button id="export-rentals" class="button">Export Rentals</button>
        <input type="file" id="import-rentals" style="display:none;" />
        <button id="import-rentals-btn" class="button">Import Rentals</button>
        <table class="wp-list-table widefat fixed striped" id="rental-history-table">
            <thead>
                <tr>
                    <th data-sort="rental_date">Date</th>
                    <th data-sort="rental_days">Days</th>
                    <th data-sort="equipment_names">Equipment</th>
                    <th data-sort="standard_total">Standard Total</th>
                    <th data-sort="total_earnings">Actual Income</th>
                    <th data-sort="total_discount">Discount</th>
                    <th data-sort="notes">Notes</th>
                    <th data-sort="package_deal">Package Deal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $session): 
                    $discount_percentage = ($session->standard_total > 0) 
                        ? ($session->total_discount / $session->standard_total * 100) 
                        : 0;
                    ?>
                    <tr>
                        <td><?php echo esc_html($session->rental_date); ?></td>
                        <td><?php echo esc_html($session->rental_days); ?></td>
                        <td><?php echo esc_html($session->equipment_names); ?></td>
                        <td><?php echo fer_format_currency($session->standard_total); ?></td>
                        <td><?php echo fer_format_currency($session->total_earnings); ?></td>
                        <td><?php echo number_format($discount_percentage, 1); ?>%</td>
                        <td><?php echo esc_html($session->notes); ?></td>
                        <td><?php echo $session->package_deal ? 'Yes' : 'No'; ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=add-rental&edit=' . $session->id); ?>" 
                            class="button button-small">Edit</a>
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
        document.getElementById('import-rentals').click();
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
});
</script>