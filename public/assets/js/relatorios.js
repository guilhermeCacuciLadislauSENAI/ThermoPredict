/* =========================
   BASE DE DADOS MOCKADA
========================= */
const dados = [
    { horario: "08:15", equipamento: "Geladeira 01", sensor: "Temperatura", temperatura: 4.2, porta: "Fechada", status: "Normal" },
    { horario: "08:20", equipamento: "Geladeira 02", sensor: "Porta", temperatura: 8.1, porta: "Aberta", status: "Alerta" },
    { horario: "08:35", equipamento: "Freezer Central", sensor: "Energia", temperatura: 12.5, porta: "Aberta", status: "Crítico" },
    { horario: "09:10", equipamento: "Geladeira 01", sensor: "Backup", temperatura: 3.8, porta: "Fechada", status: "Normal" },
    { horario: "09:45", equipamento: "Freezer Central", sensor: "Temperatura", temperatura: 10.4, porta: "Aberta", status: "Crítico" }
];

/* =========================
   ELEMENTOS HTML
========================= */
const tabelaHistorico = document.getElementById("tabelaHistorico");
const filtroEquipamento = document.getElementById("filtroEquipamento");
const filtroStatus = document.getElementById("filtroStatus");
const filtroSensor = document.getElementById("filtroSensor");
const filtroPeriodo = document.getElementById("filtroPeriodo");
const filtroPorta = document.getElementById("filtroPorta");

/* =========================
   RENDERIZAÇÃO DA TABELA
========================= */
function renderizarTabela(lista) {
    tabelaHistorico.innerHTML = "";

    if (lista.length === 0) {
        tabelaHistorico.innerHTML = `
            <tr>
                <td colspan="6">Nenhum resultado encontrado.</td>
            </tr>
        `;
        return;
    }

    lista.forEach(item => {
        let classeStatus = "status-ok";
        if (item.status === "Alerta") classeStatus = "status-alerta";
        if (item.status === "Crítico") classeStatus = "status-critico";

        tabelaHistorico.innerHTML += `
            <tr>
                <td>${item.horario}</td>
                <td>${item.equipamento}</td>
                <td>${item.sensor}</td>
                <td>${item.temperatura}°C</td>
                <td>${item.porta}</td>
                <td><span class="${classeStatus}">${item.status}</span></td>
            </tr>
        `;
    });
}

/* =========================
   CARDS DINÂMICOS
========================= */
function atualizarCards(lista) {
    document.getElementById("totalAlertas").innerText = lista.length;
    document.getElementById("totalCriticos").innerText = lista.filter(d => d.status === "Crítico").length;

    const media = lista.reduce((a, b) => a + b.temperatura, 0) / (lista.length || 1);
    document.getElementById("mediaTemp").innerText = media.toFixed(1) + "°C";

    const portasAbertas = lista.filter(d => d.porta === "Aberta").length;
    document.getElementById("tempoPorta").innerText = (portasAbertas * 3) + " min";
}

/* =========================
   GRÁFICOS
========================= */
const graficoTemperatura = new Chart(document.getElementById("graficoTemperatura"), {
    type: "bar",
    data: {
        labels: [],
        datasets: [{ label: "Temperatura °C", data: [], borderWidth: 1 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

function atualizarGraficoTemperatura(lista) {
    const equipamentos = [];
    const temperaturas = [];

    lista.forEach(item => {
        equipamentos.push(item.equipamento);
        temperaturas.push(item.temperatura);
    });

    graficoTemperatura.data.labels = equipamentos;
    graficoTemperatura.data.datasets[0].data = temperaturas;
    graficoTemperatura.update();
}

const graficoStatus = new Chart(document.getElementById("graficoStatus"), {
    type: "doughnut",
    data: {
        labels: ["Normal", "Alerta", "Crítico"],
        datasets: [{ data: [0, 0, 0] }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

function atualizarGraficoStatus(lista) {
    const normais = lista.filter(d => d.status === "Normal").length;
    const alertas = lista.filter(d => d.status === "Alerta").length;
    const criticos = lista.filter(d => d.status === "Crítico").length;

    graficoStatus.data.datasets[0].data = [normais, alertas, criticos];
    graficoStatus.update();
}

/* =========================
   FILTROS E EVENTOS
========================= */
function aplicarFiltros() {
    const equipamento = filtroEquipamento.value;
    const status = filtroStatus.value;
    const sensor = filtroSensor.value;
    const periodo = filtroPeriodo.value;
    const porta = filtroPorta.value;

    const filtrado = dados.filter(item => {
        const filtroEquip = equipamento === "todos" || item.equipamento === equipamento;
        const filtroStat = status === "todos" || item.status === status;
        const filtroSens = sensor === "todos" || item.sensor === sensor;
        const filtroPortaStatus = porta === "todos" || item.porta === porta;

        let filtroPeriodoHorario = true;
        const hora = parseInt(item.horario.split(":")[0]);

        if (periodo === "manha") filtroPeriodoHorario = hora < 12;
        if (periodo === "tarde") filtroPeriodoHorario = hora >= 12 && hora < 18;
        if (periodo === "noite") filtroPeriodoHorario = hora >= 18;

        return filtroEquip && filtroStat && filtroSens && filtroPortaStatus && filtroPeriodoHorario;
    });

    renderizarTabela(filtrado);
    atualizarCards(filtrado);
    atualizarGraficoTemperatura(filtrado);
    atualizarGraficoStatus(filtrado);
}

filtroEquipamento.addEventListener("change", aplicarFiltros);
filtroStatus.addEventListener("change", aplicarFiltros);
filtroSensor.addEventListener("change", aplicarFiltros);
filtroPeriodo.addEventListener("change", aplicarFiltros);
filtroPorta.addEventListener("change", aplicarFiltros);

/* =========================
   INICIALIZAÇÃO
========================= */
renderizarTabela(dados);
atualizarCards(dados);
atualizarGraficoTemperatura(dados);
atualizarGraficoStatus(dados);