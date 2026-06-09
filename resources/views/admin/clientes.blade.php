@extends('layouts.app')

@section('title', 'Clientes | THERMO PREDICT')
@section('subtitle', 'Painel Administrativo - Visão Geral de Empresas')

@section('content')
<main class="container">
    <div class="form-card" style="max-width: 900px;">
        <div class="titulo-pagina">
            <h2>Empresas Monitoradas</h2>
            <p>Selecione um cliente para visualizar o status em tempo real da sua rede de frio.</p>
        </div>

        <table class="tabela-alertas" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>Empresa / CNPJ</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($empresas as $empresa)
                    <tr>
                        <td>
                            <strong>{{ $empresa->razao_social }}</strong><br>
                            <small>{{ $empresa->cnpj ?? 'CNPJ Não Informado' }}</small>
                        </td>
                        <td>
                            <a href="#" class="btn-secundario" style="width: auto; height: auto; padding: 8px 15px; text-decoration: none; font-size: 0.9rem;">
                                📊 Ver Geladeiras
                            </a>
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