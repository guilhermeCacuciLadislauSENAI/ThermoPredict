(function () {
    const data = window.thermoDashboardCharts;
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

    function baseOptions(extra) {
        return Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, boxWidth: 8, color: '#526b85', font: { size: 11 } }
                },
                tooltip: {
                    backgroundColor: '#082f4f',
                    padding: 10
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#8496b5' } },
                y: { grid: { color: colors.grid }, ticks: { color: '#8496b5' } }
            }
        }, extra || {});
    }

    function makeLine(id, labels, datasets, options) {
        const canvas = document.getElementById(id);
        if (!canvas) return;

        new Chart(canvas, {
            type: 'line',
            data: { labels: labels || [], datasets },
            options: baseOptions(options)
        });
    }

    function makeBar(id, labels, datasets, options) {
        const canvas = document.getElementById(id);
        if (!canvas) return;

        new Chart(canvas, {
            type: 'bar',
            data: { labels: labels || [], datasets },
            options: baseOptions(options)
        });
    }

    function makeDoughnut(id, labels, values) {
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
                plugins: baseOptions().plugins
            }
        });
    }

    function renderEmptyLine(id) {
        makeLine(id, ['Sem dados'], [{
            label: 'Sem dados',
            data: [0],
            borderColor: colors.gray,
            backgroundColor: 'rgba(148, 163, 184, 0.12)',
            fill: true
        }], { plugins: { legend: { display: false } } });
    }

    function renderMainCharts() {
        if (hasValues(data.daily_average?.values)) {
            makeLine('dailyAverageChart', data.daily_average.labels, [{
                label: 'Temperatura media',
                data: data.daily_average.values,
                borderColor: colors.blue,
                backgroundColor: colors.softBlue,
                fill: true,
                tension: 0.35,
                pointRadius: 3
            }]);
        } else {
            renderEmptyLine('dailyAverageChart');
        }

        makeDoughnut('riskDistributionChart', data.risk_distribution.labels, data.risk_distribution.values);

        makeBar('equipmentAverageChart', data.equipment_average.labels, [{
            label: 'Media',
            data: data.equipment_average.values,
            backgroundColor: colors.blue,
            borderRadius: 7,
            maxBarThickness: 44
        }], {
            plugins: { legend: { display: false } }
        });

        const projectionLabel = document.querySelector('[data-projection-label]');
        if (projectionLabel && data.projection?.label) {
            projectionLabel.textContent = data.projection.label;
        }

        if (hasValues(data.projection?.values)) {
            makeLine('projectionChart', data.projection.labels, [{
                label: 'Projecao',
                data: data.projection.values,
                borderColor: colors.amber,
                backgroundColor: 'rgba(243, 156, 18, 0.14)',
                fill: true,
                tension: 0.25,
                pointRadius: 4
            }]);
        } else {
            renderEmptyLine('projectionChart');
        }
    }

    function renderSensorCharts() {
        Object.entries(data.sensor_series || {}).forEach(([sensorId, series]) => {
            const canvas = document.querySelector(`[data-sensor-chart="${sensorId}"]`);
            if (!canvas) return;

            const labels = series.labels || [];
            const values = series.values || [];
            const datasets = [{
                label: 'Leitura',
                data: values,
                borderColor: colors.blue,
                backgroundColor: colors.softBlue,
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointRadius: 3
            }];

            if (series.projection && series.projection.length) {
                datasets.push({
                    label: 'Projecao',
                    data: series.projection,
                    borderColor: colors.amber,
                    borderDash: [6, 5],
                    borderWidth: 2,
                    fill: false,
                    tension: 0.2,
                    pointRadius: 3
                });
            }

            if (series.limit_min !== null) {
                datasets.push({
                    label: 'Limite min.',
                    data: labels.map(() => series.limit_min),
                    borderColor: colors.green,
                    borderDash: [4, 4],
                    borderWidth: 1.5,
                    pointRadius: 0,
                    fill: false
                });
            }

            if (series.limit_max !== null) {
                datasets.push({
                    label: 'Limite max.',
                    data: labels.map(() => series.limit_max),
                    borderColor: colors.red,
                    borderDash: [4, 4],
                    borderWidth: 1.5,
                    pointRadius: 0,
                    fill: false
                });
            }

            makeLine(canvas.id, labels.length ? labels : ['Sem dados'], datasets, {
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 8, color: '#8496b5' } },
                    y: { grid: { color: colors.grid }, ticks: { color: '#8496b5' } }
                }
            });
        });
    }

    renderMainCharts();
    renderSensorCharts();
})();
