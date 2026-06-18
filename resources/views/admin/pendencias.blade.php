<!--- ===================================================== VIEW DE EMPRESAS PENDENTES AGUARDANDO VALIDAÇÃO/APROVAÇÃO  ===================================================== --->
@extends('layouts.index')

@section('title', 'Aprovação de Clientes | THERMO PREDICT')
@section('subtitle', 'Painel Administrativo - Gestão de Acessos')

@section('content')
    <main class="container">
        <div class="form-card" style="max-width: 900px;">
            <div class="titulo-pagina">
                <h2>Cadastros Pendentes</h2>
                <p>Aprove ou gerencie empresas aguardando acesso à plataforma.</p>
            </div>

            @if (session('sucesso'))
                <div class="alerta alerta-baixo">
                    ✔ {{ session('sucesso') }}
                </div>
            @endif

            <table class="tabela-alertas" style="margin-top: 20px;"> <!--- LISTANDO EMPRESAS AGUARDANDO APROVAÇÃO DE ACESSO --->
                <thead>
                    <tr>
                        <th>Empresa / CNPJ</th>
                        <th>Responsável</th>
                        <th>E-mail</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuariosPendentes as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->empresa->razao_social }}</strong><br>
                                <small>{{ $user->empresa->cnpj }}</small>
                            </td>
                            <td>{{ $user->nome }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <form action="{{ route('admin.aprovar', $user->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="status-ok"
                                        style="border: none; cursor: pointer; padding: 8px 15px;">
                                        Aprovar Acesso
                                    </button> <!--- SUBMIT PARA APROVAR ACESSO DA EMPRESA --->
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 30px;">
                                Nenhum cliente aguardando aprovação no momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
@endsection
