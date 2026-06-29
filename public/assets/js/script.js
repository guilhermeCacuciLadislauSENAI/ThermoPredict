(function () {
    const legacyChart = document.getElementById('graficoTemp');
    if (!legacyChart || typeof Chart === 'undefined') return;

    new Chart(legacyChart, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Temperatura',
                data: [],
                borderColor: '#1e5f8a',
                backgroundColor: 'rgba(30, 95, 138, 0.12)',
                borderWidth: 3,
                tension: 0.35,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: '#e2e8f0' } }
            }
        }
    });
})();
