const tables = ['most-profitable-table', 'least-profitable-table'];
tables.forEach(tableId => {
    const table = document.getElementById(tableId);
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
});

// Apply color coding to values
document.querySelectorAll('.fer-value').forEach(element => {
    const value = parseFloat(element.getAttribute('data-value'));
    let color;
    if (value > 0) {
        const greenIntensity = Math.min(200, value * 2);
        color = `rgb(0, ${greenIntensity}, 0)`;
    } else {
        const redIntensity = Math.min(200, Math.abs(value) * 2);
        color = `rgb(${redIntensity}, ${200 - redIntensity}, 0)`;
    }
    element.style.color = color;
});

// Chart.js setup
const ctx = document.getElementById('fer-chart-canvas').getContext('2d');
const chartData = {
    labels: ferAjax.monthlyTrend.map(item => item.month),
    datasets: [
        {
            label: 'Revenue',
            data: ferAjax.monthlyTrend.map(item => item.revenue),
            borderColor: 'green',
            backgroundColor: 'rgba(0, 255, 0, 0.1)',
            fill: true
        },
        {
            label: 'Purchases',
            data: ferAjax.monthlyTrend.map(item => item.purchases),
            borderColor: 'red',
            backgroundColor: 'rgba(255, 0, 0, 0.1)',
            fill: true
        }
    ]
};

const ferChart = new Chart(ctx, {
    type: 'line',
    data: chartData,
    options: {
        responsive: true,
        scales: {
            x: {
                type: 'time',
                time: {
                    unit: 'month'
                }
            },
            y: {
                beginAtZero: true
            }
        }
    }
});