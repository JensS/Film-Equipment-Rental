<?php 
if (!defined('ABSPATH')) exit;

$year = isset($_GET['year']) ? $_GET['year'] : 'all';
$stats = fer_get_statistics($year === 'all' ? null : intval($year));
$years = fer_get_available_years();
$timespan_label = $year === 'all' ? 'All Time' : intval($year);
?>
<div class="wrap">
    <h1>Equipment Statistics</h1>

    <form method="get" action="">
        <input type="hidden" name="page" value="equipment-statistics">
        <label for="year">Select Year:</label>
        <select name="year" id="year" onchange="this.form.submit()">
            <option value="all" <?php selected($year, 'all'); ?>>All Time</option>
            <?php foreach ($years as $available_year): ?>
                <option value="<?php echo esc_attr($available_year); ?>" <?php selected($year, $available_year); ?>>
                    <?php echo esc_html($available_year); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="fer-stats-summary">
        <div class="fer-stat-box">
            <h3>Total Revenue (<?php echo esc_html($timespan_label); ?>)</h3>
            <p><?php echo fer_format_currency($stats['overall']->total_revenue ?? 0); ?></p>
        </div>
        
        <div class="fer-stat-box">
            <h3>Total Rentals (<?php echo esc_html($timespan_label); ?>)</h3>
            <p><?php echo $stats['overall']->total_rentals ?? 0; ?></p>
        </div>
        
        <div class="fer-stat-box">
            <h3>Average Rental Duration (<?php echo esc_html($timespan_label); ?>)</h3>
            <p><?php echo number_format($stats['overall']->avg_rental_days ?? 0, 1); ?> days</p>
        </div>
        
        <div class="fer-stat-box">
            <h3>Average Discount (<?php echo esc_html($timespan_label); ?>)</h3>
            <p><?php echo number_format($stats['overall']->avg_discount ?? 0, 1); ?>%</p>
        </div>

        <div class="fer-stat-box">
            <h3>Total Purchase Value</h3>
            <p><?php echo fer_format_currency($stats['overall']->total_investment ?? 0); ?></p>
        </div>
    </div>

    <div class="fer-equipment-stats">
        <h2>Equipment Performance (<?php echo esc_html($timespan_label); ?>)</h2>
        
        <div class="fer-stats-tables">
            <div class="fer-stats-table">
                <h3>Top 10 Most Profitable Items (<?php echo esc_html($timespan_label); ?>)</h3>
                <table class="wp-list-table widefat fixed striped" id="most-profitable-table">
                    <thead>
                        <tr>
                            <th data-sort="name">Equipment</th>
                            <th data-sort="purchase_price" style="text-align:right">Purchase Price</th>
                            <th data-sort="total_earnings" style="text-align:right">Total Income</th>
                            <th data-sort="net_profit" style="text-align:right">Net Profit</th>
                            <th data-sort="roi_percentage" style="text-align:right">ROI</th>
                            <th data-sort="rental_count" style="text-align:right">Rentals</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Filter equipment before slicing top 10 lists
                        $filtered_equipment = array_filter($stats['equipment'], function($item) {
                            return $item->purchase_price !== null && (float) $item->purchase_price > 0;
                        });

                        // Sort by ROI descending for top items
                        usort($filtered_equipment, function($a, $b) {
                            return $b->roi_percentage <=> $a->roi_percentage;
                        });
                        $top_items = array_slice($filtered_equipment, 0, 10);

                        foreach ($top_items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->name); ?></td>
                                <td class="number" style="text-align:right;"><?php echo fer_format_currency($item->purchase_price); ?></td>
                                <td class="fer-value number" style="text-align:right;"  data-value="<?php echo $item->total_earnings; ?>"><?php echo fer_format_currency($item->total_earnings); ?></td>
                                <td class="fer-valu number" style="text-align:right;" data-value="<?php echo $item->net_profit; ?>"><?php echo fer_format_currency($item->net_profit); ?></td>
                                <td class="fer-value number" style="text-align:right;" data-value="<?php echo $item->roi_percentage; ?>"><?php echo number_format($item->roi_percentage ?? 0, 1); ?>%</td>
                                <td class="number" style="text-align:right;"><?php echo $item->rental_count; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="fer-stats-table">
                <h3>Top 10 Least Profitable Items (<?php echo esc_html($timespan_label); ?>)</h3>
                <table class="wp-list-table widefat fixed striped" id="least-profitable-table">
                    <thead>
                        <tr>
                            <th data-sort="name">Equipment</th>
                            <th data-sort="purchase_price" style="text-align:right">Purchase Price</th>
                            <th data-sort="total_earnings" style="text-align:right">Total Income</th>
                            <th data-sort="net_profit" style="text-align:right">Net Profit</th>
                            <th data-sort="roi_percentage" style="text-align:right">ROI</th>
                            <th data-sort="rental_count" style="text-align:right">Rentals</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Sort by ROI ascending for least profitable items
                        $filtered_least_prof = $filtered_equipment;
                        usort($filtered_least_prof, function($a, $b) {
                            return $a->roi_percentage <=> $b->roi_percentage;
                        });
                        $least_profitable_items = array_slice($filtered_least_prof, 0, 10);

                        foreach ($least_profitable_items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->name); ?></td>
                                <td class="number" style="text-align:right;"><?php echo fer_format_currency($item->purchase_price); ?></td>
                                <td class="fer-value number" style="text-align:right;" data-value="<?php echo $item->total_earnings; ?>"><?php echo fer_format_currency($item->total_earnings); ?></td>
                                <td class="fer-value number" style="text-align:right;" data-value="<?php echo $item->net_profit; ?>"><?php echo fer_format_currency($item->net_profit); ?></td>
                                <td class="fer-value number" style="text-align:right;" data-value="<?php echo $item->roi_percentage; ?>"><?php echo number_format($item->roi_percentage ?? 0, 1); ?>%</td>
                                <td class="number" style="text-align:right;"><?php echo $item->rental_count; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="fer-stats-table">
                <h3>Top 5 Clients by Revenue (<?php echo esc_html($timespan_label); ?>)</h3>
                <table class="wp-list-table widefat fixed striped" id="top-clients-table">
                    <thead>
                        <tr>
                            <th data-sort="name">Client</th>
                            <th data-sort="rental_count" style="text-align:right">Number of Rentals</th>
                            <th data-sort="total_revenue" style="text-align:right">Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['top_clients'] as $client): ?>
                            <tr>
                                <td><?php echo esc_html($client->name); ?></td>
                                <td class="number" style="text-align:right;"><?php echo $client->rental_count; ?></td>
                                <td class="fer-value number" style="text-align:right;" data-value="<?php echo $client->total_revenue; ?>"><?php echo fer_format_currency($client->total_revenue); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="fer-chart">
        <h2>Revenue and Purchase Expenses Over Time (<?php echo esc_html($timespan_label); ?>)</h2>
        <canvas id="fer-chart-canvas"></canvas>
    </div>

    <?php fer_output_footer(); ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pass the monthly trend data and purchase data to the JavaScript file
    const monthlyTrend = <?php echo json_encode($stats['monthly_trend']); ?>;
    const purchaseData = <?php echo json_encode(array_map(function($item) {
        return ['date' => $item->purchase_date, 'price' => $item->purchase_price];
    }, $stats['equipment'])); ?>;
    window.ferAjax = {
        monthlyTrend: monthlyTrend,
        purchaseData: purchaseData
    };
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectedYear = '<?php echo esc_js($year); ?>';
    let monthlyTrend = ferAjax.monthlyTrend;
    let purchaseData = ferAjax.purchaseData;

    if (selectedYear !== 'all') {
        monthlyTrend = monthlyTrend.filter(item => item.month.startsWith(selectedYear));
        purchaseData = purchaseData.filter(
            item => item.date && item.date.substring(0, 4) === selectedYear
        );
    }

    const ctx = document.getElementById('fer-chart-canvas').getContext('2d');
    const chartData = {
        labels: monthlyTrend.map(item => item.month),
        datasets: [
            {
                label: 'Rental Income',
                data: monthlyTrend.map(item => item.revenue),
                backgroundColor: 'rgba(0, 255, 0, 0.5)',
                borderColor: 'green',
                borderWidth: 2,
                type: 'bar'
            },
            {
                label: 'Purchase Expense',
                data: purchaseData.map(item => ({ x: item.date, y: item.price })),
                backgroundColor: 'rgba(255, 0, 0, 0.5)',
                borderColor: 'red',
                borderWidth: 2,
                type: 'bar'
            }
        ]
    };

    const ferChart = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'month',
                        displayFormats: {
                            month: 'MMM yyyy'
                        }
                    },
                    ticks: {
                        maxRotation: 0,
                        minRotation: 0
                    }
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>