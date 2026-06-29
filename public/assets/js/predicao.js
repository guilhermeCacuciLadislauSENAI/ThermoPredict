(function () {
    const data = window.thermoPredictionCharts;
    if (!data || typeof Chart === 'undefined') return;

    const colors = {
        navy: '#0a3d62',
        blue: '#1e5f8a',
        softBlue: 'rgba(30, 95, 138, 0.12)',
        green: '#27ae60',
        amber: '#f39c12',
        orange: '#f97316',
        red: '#e74c3c',
        purple: '#8e44ad',
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

    function colorByStatus(status) {
        if (status === 'perda_provavel') return colors.purple;
        if (status === 'critico') return colors.red;
        if (status === 'risco') return colors.orange;
        if (status === 'atencao') return colors.amber;
        return colors.green;
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

    function doughnut(id, labels, values, backgroundColor) {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        new Chart(canvas, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: values, backgroundColor, borderWidth: 0, hoverOffset: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: options().plugins }
        });
    }

    function emptyLine(id) {
        line(id, ['Sem dados'], [{ label: 'Sem dados', data: [0], borderColor: colors.gray, backgroundColor: 'rgba(148, 163, 184, 0.12)', fill: true }], { plugins: { legend: { display: false } } });
    }

    bar('predictionScoreChart', data.score_by_sensor?.labels || [], [{
        label: 'Score',
        data: data.score_by_sensor?.values || [],
        backgroundColor: (data.score_by_sensor?.statuses || []).map(colorByStatus),
        borderRadius: 7,
        maxBarThickness: 44
    }], { plugins: { legend: { display: false } }, scales: { y: { suggestedMax: 100, grid: { color: colors.grid } } } });

    doughnut('predictionStatusChart', data.risk_distribution.labels, data.risk_distribution.values, [colors.green, colors.amber, colors.orange, colors.red, colors.purple]);

    const range = data.temperature_range || {};
    const rangeLabel = document.querySelector('[data-temperature-range-label]');
    if (rangeLabel) rangeLabel.textContent = range.label || '';
    if (hasValues(range.values)) {
        const labels = range.labels || [];
        const datasets = [{
            label: 'Temperatura',
            data: range.values,
            borderColor: colors.blue,
            backgroundColor: colors.softBlue,
            fill: true,
            tension: 0.35,
            pointRadius: 3
        }];
        if (range.projection && range.projection.length) {
            datasets.push({ label: 'Projecao', data: range.projection, borderColor: colors.amber, borderDash: [6, 5], fill: false, tension: 0.2 });
        }
        datasets.push({ label: 'Limite min.', data: labels.map(() => range.limit_min), borderColor: colors.green, borderDash: [4, 4], pointRadius: 0, fill: false });
        datasets.push({ label: 'Limite max.', data: labels.map(() => range.limit_max), borderColor: colors.red, borderDash: [4, 4], pointRadius: 0, fill: false });
        line('temperatureRangeChart', labels, datasets);
    } else {
        emptyLine('temperatureRangeChart');
    }

    bar('outOfRangeChart', data.out_of_range_by_sensor?.labels || [], [{
        label: 'Minutos',
        data: data.out_of_range_by_sensor?.values || [],
        backgroundColor: colors.red,
        borderRadius: 7
    }], { plugins: { legend: { display: false } } });

    bar('occurrencesDayChart', data.occurrences_by_day?.labels || [], [{
        label: 'Ocorrencias',
        data: data.occurrences_by_day?.values || [],
        backgroundColor: colors.amber,
        borderRadius: 7
    }], { plugins: { legend: { display: false } } });

    const projectionLabel = document.querySelector('[data-prediction-projection-label]');
    if (projectionLabel && data.projection?.label) projectionLabel.textContent = data.projection.label;
    if (hasValues(data.projection?.values)) {
        line('predictionProjectionChart', data.projection.labels, [{
            label: 'Projecao',
            data: data.projection.values,
            borderColor: colors.amber,
            backgroundColor: 'rgba(243, 156, 18, 0.14)',
            fill: true,
            tension: 0.25,
            pointRadius: 4
        }]);
    } else {
        emptyLine('predictionProjectionChart');
    }
})();
