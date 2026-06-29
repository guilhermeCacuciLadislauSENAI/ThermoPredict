(function () {
    const data = window.thermoReportCharts;
    if (!data || typeof Chart === 'undefined') return;

    const colors = {
        navy: '#0a3d62',
        blue: '#1e5f8a',
        softBlue: 'rgba(30, 95, 138, 0.12)',
        green: '#27ae60',
        amber: '#f39c12',
        red: '#e74c3c',
        gray: '#94a3b8',
        grid: '#e2e8f0'
    };

    function hasValues(values) {
        return Array.isArray(values) && values.some(value => value !== null && Number(value) !== 0);
    }

    function options(extra) {
        return Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, boxWidth: 8, color: '#526b85', font: { size: 11 } }
                },
                tooltip: { backgroundColor: '#082f4f', padding: 10 }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#8496b5' } },
                y: { grid: { color: colors.grid }, ticks: { color: '#8496b5' } }
            }
        }, extra || {});
    }

    function line(id, labels, datasets, extra) {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        new Chart(canvas, { type: 'line', data: { labels, datasets }, options: options(extra) });
    }

    function bar(id, labels, datasets, extra) {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        new Chart(canvas, { type: 'bar', data: { labels, datasets }, options: options(extra) });
    }

    function doughnut(id, labels, values) {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: [colors.green, colors.amber, colors.red],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: options().plugins
            }
        });
    }

    function emptyLine(id) {
        line(id, ['Sem dados'], [{
            label: 'Sem dados',
            data: [0],
            borderColor: colors.gray,
            backgroundColor: 'rgba(148, 163, 184, 0.12)',
            fill: true
        }], { plugins: { legend: { display: false } } });
    }

    if (hasValues(data.daily_average?.values)) {
        line('reportDailyAverageChart', data.daily_average.labels, [{
            label: 'Temperatura media',
            data: data.daily_average.values,
            borderColor: colors.blue,
            backgroundColor: colors.softBlue,
            fill: true,
            tension: 0.35,
            pointRadius: 3
        }]);
    } else {
        emptyLine('reportDailyAverageChart');
    }

    doughnut('reportRiskDistributionChart', data.risk_distribution.labels, data.risk_distribution.values);

    bar('reportEquipmentAverageChart', data.equipment_average.labels, [{
        label: 'Media',
        data: data.equipment_average.values,
        backgroundColor: colors.blue,
        borderRadius: 7,
        maxBarThickness: 44
    }], { plugins: { legend: { display: false } } });

    const projectionLabel = document.querySelector('[data-report-projection-label]');
    if (projectionLabel && data.projection?.label) {
        projectionLabel.textContent = data.projection.label;
    }

    if (hasValues(data.projection?.values)) {
        line('reportProjectionChart', data.projection.labels, [{
            label: 'Projecao',
            data: data.projection.values,
            borderColor: colors.amber,
            backgroundColor: 'rgba(243, 156, 18, 0.14)',
            fill: true,
            tension: 0.25,
            pointRadius: 4
        }]);
    } else {
        emptyLine('reportProjectionChart');
    }
})();
