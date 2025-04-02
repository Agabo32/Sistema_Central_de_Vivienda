// Datos para las tarjetas de métricas principales
const metrics = [
    {
        title: "Beneficiarios Registrados",
        value: "3,009",
        change: "342 este mes",
        icon: "fa-thumbs-up"
    },
    {
        title: "Casas Culminadas",
        value: "342",
        change: "28 este mes",
        icon: "fa-youtube"
    },
    {
        title: "Casas sin culminar",
        value: "35,295",
        change: "12% respecto al mes pasado",
        icon: "fa-globe"
    },
    {
        title: "Actualizaciones",
        value: "27",
        change: "5 nuevas posiciones",
        icon: "fa-google"
    }
];

// Datos de las fuentes de tráfico
const trafficSources = [
    { name: "Búsqueda Orgánica", value: "16,028", percentage: 100, color: "success" },
    { name: "Directo", value: "9,324", percentage: 26, color: "primary" },
    { name: "Búsqueda de Pago", value: "6,177", percentage: 18, color: "info" },
    { name: "Email", value: "1,228", percentage: 100, color: "warning" },
    { name: "Referidos", value: "986", percentage: 100, color: "danger" }
];

// Datos de distribución de rankings
const rankings = [
    { range: "1 a 10", value: "44", percentage: 25, color: "success" },
    { range: "11 a 30", value: "27", percentage: 35, color: "primary" },
    { range: "21 a 50", value: "21", percentage: 25, color: "info" },
    { range: "51 a 100", value: "51", percentage: 15, color: "warning" }
];

// Datos de crecimiento de audiencia
const audienceGrowth = [
    { type: "Orgánico", percentage: 65, color: "success" },
    { type: "De Pago", percentage: 35, color: "primary" },
    { type: "Último", percentage: 45, color: "info" }
];

// Inicializar el dashboard cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Llenar las tarjetas de métricas
    const metricsRow = document.getElementById('metricsRow');
    metrics.forEach(metric => {
        const col = document.createElement('div');
        col.className = 'col-md-6 col-lg-3';
        col.innerHTML = `
            <div class="metric-card">
                <div class="metric-title">${metric.title}</div>
                <div class="metric-value">${metric.value}</div>
                <div class="d-flex align-items-center mt-2">
                    <i class="fas fa-arrow-up text-success me-2"></i>
                    <span class="text-muted small">${metric.change}</span>
                </div>
            </div>
        `;
        metricsRow.appendChild(col);
    });

    // Llenar las fuentes de tráfico
    const trafficContainer = document.getElementById('trafficSources');
    trafficContainer.innerHTML = `
        <h5 class="mb-4">Fuentes de Tráfico del Sitio</h5>
        <div class="metric-value mb-3">35,295</div>
        ${trafficSources.map(source => `
            <div class="traffic-source">
                <span>${source.name}</span>
                <div class="d-flex justify-content-between">
                    <div class="progress w-75">
                        <div class="progress-bar bg-${source.color}" role="progressbar" style="width: ${source.percentage}%" 
                            aria-valuenow="${source.percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <span>${source.value}</span>
                </div>
            </div>
        `).join('')}
    `;

    // Llenar la distribución de rankings
    const rankingsContainer = document.getElementById('rankingsDistribution');
    rankingsContainer.innerHTML = `
        <h5 class="mb-4">Distribución de Posiciones en Google</h5>
        <div class="metric-value mb-3">27,296</div>
        ${rankings.map(rank => `
            <div class="traffic-source">
                <span>${rank.range}</span>
                <div class="d-flex justify-content-between">
                    <div class="progress w-75">
                        <div class="progress-bar bg-${rank.color}" role="progressbar" style="width: ${rank.percentage}%" 
                            aria-valuenow="${rank.percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <span>${rank.value}</span>
                </div>
            </div>
        `).join('')}
        <div class="mt-4">
            <h6>Flujo de Confianza</h6>
            <div class="metric-value">25%</div>
        </div>
    `;

    // Llenar el crecimiento de audiencia
    const audienceContainer = document.getElementById('audienceGrowth');
    audienceContainer.innerHTML = `
        <h5 class="mb-4">Crecimiento de Audiencia</h5>
        <ul class="audience-growth">
            ${audienceGrowth.map(item => `
                <li>
                    <span class="me-2">${item.type}</span>
                    <div class="progress">
                        <div class="progress-bar bg-${item.color}" role="progressbar" style="width: ${item.percentage}%" 
                            aria-valuenow="${item.percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </li>
            `).join('')}
        </ul>
    `;

    // Configurar gráfico de conversiones de AdWords
    const adWordsCtx = document.createElement('canvas');
    document.getElementById('adWordsChart').appendChild(adWordsCtx);
    new Chart(adWordsCtx, {
        type: 'bar',
        data: {
            labels: ['Ene', 'Feb', 'Mar'],
            datasets: [{
                label: 'Conversiones',
                data: [353, 210, 280],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(153, 102, 255, 0.6)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Configurar gráfico circular de auditoría del sitio
    const auditCtx = document.getElementById('auditChart');
    new Chart(auditCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [72, 28],
                backgroundColor: [
                    '#FF0800FF',
                    '#e9ecef'
                ],
                borderWidth: 0
            }]
        },
        options: {
            cutout: '80%',  // Grosor del anillo
            responsive: false,
            plugins: {
                legend: {
                    display: false  // Ocultar leyenda
                },
                tooltip: {
                    enabled: false  // Desactivar tooltips
                }
            }
        }
    });
});