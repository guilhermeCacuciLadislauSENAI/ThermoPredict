<!--- ======================================================================== DASHBOARD PRINCIPAL ======================================================================== --->
@extends('layouts.index')

@section('title', 'Dashboard | THERMO PREDICT')
@section('subtitle', 'Monitoramento em Tempo Real - ' . $empresa->razao_social)

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="container">
    <div class="titulo-pagina">
        <h2>Visão Geral da Rede de Frio</h2>
        <p>Acompanhe a telemetria e a saúde dos Coolers Inteligentes da empresa: <strong>{{ $empresa->razao_social }}</strong></p>
        
        @if(auth()->user()->perfil === 'admin')
            <a href="{{ route('admin.clientes') }}" class="btn-secundario" style="margin-top: 15px; display: inline-block; text-decoration: none; padding: 8px 15px;">
                &larr; Voltar para a Lista de Clientes
            </a>
        @endif
    </div>

    <div class="grid-equipamentos" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; margin-top: 20px;">
        @forelse($equipamentos as $cooler)
            <div class="card-equipamento" style="padding: 25px; background: #fff; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.06); border: 1px solid #f1f5f9;">
                
                <div class="cabecalho-card" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f8fafc; padding-bottom: 15px; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 1.25rem; color: #1e293b;">📦 {{ $cooler->nome }}</h3>
                    <span style="background: #dcfce7; color: #166534; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;">
                        {{ $cooler->status }}
                    </span>
                </div>
                
                <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 25px;">
                    <strong>Local Operacional:</strong> {{ $cooler->localizacao ?? 'Não especificado' }}
                </p>
                
                <div class="detalhes-sensores"> <!--- COLETANDO DADOS DO SENSOR --->
                    @forelse($cooler->sensores as $sensor)
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                            <p style="margin: 0 0 15px 0; font-weight: 600; color: #334155; display: flex; justify-content: space-between;">
                                <span>📡 {{ $sensor->tipo }}</span>
                                <span style="font-size: 0.8rem; color: #94a3b8; font-weight: normal; background: #fff; padding: 3px 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                    Min: {{ $sensor->limite_min }} | Max: {{ $sensor->limite_max }}
                                </span>
                            </p>
                            
                            <div style="position: relative; height: 220px; width: 100%;">
                                <canvas id="graficoSensor{{ $sensor->id }}"></canvas>
                            </div>

                            <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    const ctx = document.getElementById('graficoSensor{{ $sensor->id }}').getContext('2d');
                                    const logs = @json($sensor->logs ?? []);

                                    const rotulosTempo = logs.map(log => {
                                        let data = new Date(log.created_at);
                                        return data.getHours() + ':' + (data.getMinutes() < 10 ? '0' : '') + data.getMinutes();
                                    });

                                    const valoresTemperatura = logs.map(log => log.valor_leitura);

                                    new Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            labels: rotulosTempo,
                                            datasets: [{
                                                label: 'Métrica Registrada',
                                                data: valoresTemperatura,
                                                borderColor: '#3b82f6',
                                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                                borderWidth: 3,
                                                fill: true,
                                                tension: 0.4,
                                                pointRadius: 4,
                                                pointBackgroundColor: '#fff',
                                                pointBorderColor: '#3b82f6',
                                                pointBorderWidth: 2
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: { legend: { display: false } },
                                            scales: {
                                                y: {
                                                    suggestedMin: {{ $sensor->limite_min - 2 }},
                                                    suggestedMax: {{ $sensor->limite_max + 2 }},
                                                    grid: { borderDash: [4, 4], color: '#e2e8f0' }
                                                },
                                                x: {
                                                    grid: { display: false }
                                                }
                                            }
                                        }
                                    });
                                });
                            </script>
                        </div>
                    @empty
                        <p style="color: #94a3b8; font-size: 0.9rem; text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                            Aguardando calibração de sensores para este cooler.
                        </p>
                    @endforelse
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <h3 style="color: #1e293b; margin-bottom: 10px;">Infraestrutura Vazia</h3>
                <p style="color: #64748b; font-size: 1.1rem;">Nenhum cooler inteligente foi alocado para esta empresa pelo suporte técnico.</p>
            </div>
        @endforelse
    </div>
</main>
@endsection