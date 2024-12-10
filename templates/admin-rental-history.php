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
        (SUM(e.daily_rate * rs.rental_days) - SUM(ee.earnings)) as total_discount
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
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Days</th>
                    <th>Equipment</th>
                    <th>Standard Total</th>
                    <th>Actual Income</th>
                    <th>Discount</th>
                    <th>Notes</th>
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
                        <td><?php echo fer_format_currency($session->total_earnings); ?></td>
                        <td><?php echo number_format($discount_percentage, 1); ?>%</td>
                        <td><?php echo esc_html($session->notes); ?></td>
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