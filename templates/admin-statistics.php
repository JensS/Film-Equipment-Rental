<?php 
if (!defined('ABSPATH')) exit;

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$stats = fer_get_statistics($year);
?>

<div class="wrap">
    <h1>Equipment Statistics</h1>

    <div class="fer-stats-summary">
        <div class="fer-stat-box">
            <h3>Total Revenue</h3>
            <p><?php echo fer_format_currency($stats['overall']->total_revenue ?? 0); ?></p>
        </div>
        
        <div class="fer-stat-box">
            <h3>Total Rentals</h3>
            <p><?php echo $stats['overall']->total_rentals; ?></p>
        </div>
        
        <div class="fer-stat-box">
            <h3>Average Rental Duration</h3>
            <p><?php echo number_format($stats['overall']->avg_rental_days ?? 0, 1); ?> days</p>
        </div>
        
        <div class="fer-stat-box">
            <h3>Average Discount</h3>
            <p><?php echo number_format($stats['overall']->avg_discount ?? 0, 1); ?>%</p>
        </div>
    </div>

    <div class="fer-equipment-stats">
        <h2>Equipment Performance</h2>
        
        <div class="fer-stats-tables">
            <div class="fer-stats-table">
                <h3>Top 10 Most Profitable Items</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Purchase Price</th>
                            <th>Total Income</th>
                            <th>Net Profit</th>
                            <th>ROI</th>
                            <th>Rentals</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $top_items = array_slice($stats['equipment'], 0, 10);
                        foreach ($top_items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->name); ?></td>
                                <td><?php echo fer_format_currency($item->purchase_price); ?></td>
                                <td><?php echo fer_format_currency($item->total_earnings); ?></td>
                                <td><?php echo fer_format_currency($item->net_profit); ?></td>
                                <td><?php echo number_format($item->roi_percentage, 1); ?>%</td>
                                <td><?php echo $item->rental_count; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php fer_output_footer(); ?>
</div>