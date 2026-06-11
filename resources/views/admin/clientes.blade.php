@extends('layouts.app')

@section('title', 'Clientes | THERMO PREDICT')
@section('subtitle', 'Painel Administrativo - Visão Geral de Empresas')

@section('content')
<main class="container">
    <div class="form-card" style="max-width: 1000px;">
        <div class="titulo-pagina">
            <h2>Empresas Monitoradas</h2>
            <p>Gerencie os equipamentos e visualize o status em tempo real.</p>
        </div>

        @if(session('sucesso'))
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-top: 15px; font-weight: bold;">
                ✔ {{ session('sucesso') }}
            </div>
        @endif

        <table class="tabela-alertas" style="margin-top: 20px; width: 100%;">
            <thead>
                <tr>
                    <th style="text-align: left;">Empresa / CNPJ</th>
                    <th style="text-align: right;">Ações de Gestão</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($empresas as $empresa)
                    <tr>
                        <td>
                            <strong>{{ $empresa->razao_social }}</strong><br>
                            <small>{{ $empresa->cnpj ?? 'CNPJ Não Informado' }}</small>
                        </td>
                        <td style="text-align: right;">
                            
                            <a href="{{ route('admin.cooler.create', $empresa->id) }}" class="btn-primario" style="padding: 6px 12px; text-decoration: none; font-size: 0.9rem;">
                                + Novo Cooler
                            </a>
                            
                            <a href="{{ route('admin.empresa.dashboard', $empresa->id) }}" class="btn-secundario" style="padding: 6px 12px; text-decoration: none; font-size: 0.9rem; margin-left: 5px;">
                                📈 Gráficos
                            </a>
                            
                            @foreach($empresa->equipamentos as $equipamento)
                                <a href="{{ route('admin.cooler.manage', $equipamento->id) }}" class="btn-secundario" style="padding: 6px 12px; text-decoration: none; font-size: 0.9rem; margin-left: 5px; background-color: #333; color: white;">
                                    ⚙️ Configurar
                                </a>
                            @endforeach

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center; padding: 30px;">
                            Nenhum cliente ativo no momento.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</main>
@endsection