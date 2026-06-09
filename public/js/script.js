/* =========================
   ELEMENTOS HTML E CARDS
========================= */
const tempValor = document.getElementById("tempValor");
const riscoValor = document.getElementById("riscoValor");
const portaValor = document.getElementById("portaValor");
const statusValor = document.getElementById("statusValor");
const alertaBox = document.getElementById("alertaBox");
const predicaoTexto = document.getElementById("predicaoTexto");
const listaAlertas = document.getElementById("listaAlertas");

const filtroEquipamento = document.getElementById("filtroEquipamento");
const filtroSensor = document.getElementById("filtroSensor");
const filtroHistoricoStatus = document.getElementById("filtroHistoricoStatus");
const filtroHistoricoPorta = document.getElementById("filtroHistoricoPorta");
const filtroHistoricoTemp = document.getElementById("filtroHistoricoTemp");

const cardTemp = document.querySelector(".temp");
const cardRisco = document.querySelector(".risco");
const cardPorta = document.querySelector(".porta");
const cardStatus = document.querySelector(".status");

/* =========================
   DADOS E GRÁFICO
========================= */
const temperaturas = [];
const horarios = [];
let historicoCompleto = [];

const ctx = document.getElementById("graficoTemp");
const graficoTemp = new Chart(ctx, {
    type: "line",
    data: {
        labels: horarios,
        datasets: [{ label: "Temperatura °C", data: temperaturas, borderWidth: 3, tension: 0.3, fill: true }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, animation: { duration: 400 },
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: false, suggestedMin: 0, suggestedMax: 12 } }
    }
});

/* =========================
   ESTADOS DE CONEXÃO
========================= */
function mostrarConectando() {
    alertaBox.className = "alerta alerta-conectando";
    alertaBox.innerHTML = "🔄 Fazendo conexão com o servidor...";
}

function sistemaOnline(temp, porta) {
    [cardTemp, cardRisco, cardPorta, cardStatus].forEach(c => c.classList.remove("neutro", "offline"));
    statusValor.innerHTML = "Online";
    tempValor.innerHTML = `${temp.toFixed(1)} °C`;
    portaValor.innerHTML = porta;
    alertaBox.className = "alerta alerta-baixo";
    alertaBox.innerHTML = "✔ Sistema operando normalmente.";
}

function sistemaOffline() {
    [cardTemp, cardRisco, cardPorta, cardStatus].forEach(c => c.classList.add("offline"));
    tempValor.innerHTML = "--";
    riscoValor.innerHTML = "--";
    portaValor.innerHTML = "--";
    statusValor.innerHTML = "Offline";
    alertaBox.className = "alerta alerta-alto";
    alertaBox.innerHTML = "❌ Sistema offline.";
    predicaoTexto.innerHTML = `
        <div class="bloco-ia ia-critico">
            <h4>Sistema</h4><p>Não foi possível realizar a leitura dos sensores.</p>
        </div>
    `;
}

/* =========================
   HISTÓRICO E IA
========================= */
function renderizarHistorico() {
    listaAlertas.innerHTML = "";
    let dadosFiltrados = [...historicoCompleto];

    if (filtroHistoricoStatus.value !== "todos") {
        dadosFiltrados = dadosFiltrados.filter(item => item.status === filtroHistoricoStatus.value);
    }
    if (filtroHistoricoPorta.value !== "todos") {
        dadosFiltrados = dadosFiltrados.filter(item => item.porta === filtroHistoricoPorta.value);
    }
    if (filtroHistoricoTemp.value !== "todos") {
        dadosFiltrados = dadosFiltrados.filter(item => {
            if (filtroHistoricoTemp.value === "baixa") return item.temperatura < 2;
            if (filtroHistoricoTemp.value === "ideal") return (item.temperatura >= 2 && item.temperatura <= 7);
            if (filtroHistoricoTemp.value === "alta") return item.temperatura > 7;
        });
    }

    dadosFiltrados.forEach(dado => {
        let classeStatus = "status-ok";
        if (dado.status === "ALERTA") classeStatus = "status-alerta";
        if (dado.status === "CRÍTICO") classeStatus = "status-critico";

        const linha = document.createElement("tr");
        linha.innerHTML = `
            <td>${dado.horario}</td>
            <td>${dado.equipamento}</td>
            <td>${dado.sensor}</td>
            <td>${dado.temperatura} °C</td>
            <td>${dado.porta}</td>
            <td class="${classeStatus}">${dado.status}</td>
        `;
        listaAlertas.appendChild(linha);
    });
}

function gerarPredicao(dado) {
    let html = `
        <div class="bloco-ia ia-ok">
            <h4>Sensor</h4>
            <p>Sensor ativo: <strong>${dado.sensor}</strong>. Equipamento monitorado: <strong>${dado.equipamento}</strong>.</p>
        </div>
    `;

    if (dado.temperatura >= 2 && dado.temperatura <= 7) {
        riscoValor.innerHTML = "Baixo";
        html += `<div class="bloco-ia ia-ok"><h4>Temperatura Estável</h4><p>Equipamento operando dentro da faixa ideal.</p></div>`;
    } else if (dado.temperatura > 7) {
        riscoValor.innerHTML = "Alto";
        html += `<div class="bloco-ia ia-critico"><h4>Temperatura Crítica</h4><p>Temperatura acima do ideal. Existe risco de perda da eficácia das vacinas.</p></div>`;
    } else {
        riscoValor.innerHTML = "Médio";
        html += `<div class="bloco-ia ia-alerta"><h4>Temperatura Baixa</h4><p>Existe risco de congelamento.</p></div>`;
    }

    if (dado.porta === "Aberta") {
        html += `<div class="bloco-ia ia-alerta"><h4>Porta Aberta</h4><p>Porta aberta detectada. Pode ocorrer aumento rápido da temperatura interna.</p></div>`;
    }
    if (dado.status === "CRÍTICO") {
        html += `<div class="bloco-ia ia-critico"><h4>Falha Crítica</h4><p>Intervenção imediata recomendada.</p></div>`;
    }

    predicaoTexto.innerHTML = html;
}

/* =========================
   BUSCAR DADOS API
========================= */
async function buscarDados() {
    try {
        mostrarConectando();
        const resposta = await fetch("http://localhost:3000/sensores");
        const dados = await resposta.json();
        let dadosFiltrados = dados;

        if (filtroEquipamento.value !== "todos") dadosFiltrados = dadosFiltrados.filter(item => item.equipamento === filtroEquipamento.value);
        if (filtroSensor.value !== "todos") dadosFiltrados = dadosFiltrados.filter(item => item.sensor === filtroSensor.value);

        if (dadosFiltrados.length === 0) {
            sistemaOffline();
            return;
        }

        const dado = dadosFiltrados[0];
        sistemaOnline(dado.temperatura, dado.porta);
        gerarPredicao(dado);

        historicoCompleto.unshift(dado);
        if (historicoCompleto.length > 50) historicoCompleto.pop();

        renderizarHistorico();

        temperaturas.push(dado.temperatura);
        horarios.push(dado.horario);
        if (temperaturas.length > 20) {
            temperaturas.shift();
            horarios.shift();
        }
        graficoTemp.update();

    } catch (erro) {
        sistemaOffline();
        console.error(erro);
    }
}

/* =========================
   EVENTOS E EXECUÇÃO
========================= */
filtroEquipamento.addEventListener("change", buscarDados);
filtroSensor.addEventListener("change", buscarDados);
filtroHistoricoStatus.addEventListener("change", renderizarHistorico);
filtroHistoricoPorta.addEventListener("change", renderizarHistorico);
filtroHistoricoTemp.addEventListener("change", renderizarHistorico);

buscarDados();
setInterval(buscarDados, 5000);