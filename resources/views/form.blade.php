@extends('layouts.app')

@section('title', 'Novo Chamado | THERMO PREDICT')
@section('subtitle', 'Abertura de Chamados Técnicos')

@section('content')
<main class="container">
    <div class="form-card">
        <div class="titulo-pagina">
            <h2>Abrir Novo Chamado</h2>
            <p>Registre problemas técnicos relacionados aos sensores e equipamentos.</p>
        </div>

        <form action="{{ route('chamados.store') }}" method="POST">
            @csrf
            
            <label for="equipamento">Equipamento</label>
            <select id="equipamento" name="equipamento_id" required>
                <option value="1">Geladeira A</option>
                <option value="2">Geladeira B</option>
                <option value="3">Freezer Laboratório</option>
            </select>

            <label for="prioridade">Prioridade</label>
            <select id="prioridade" name="prioridade" required>
                <option value="baixa">Baixa</option>
                <option value="media">Média</option>
                <option value="alta">Alta</option>
                <option value="critica">Crítica</option>
            </select>

            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" placeholder="Descreva o problema encontrado com os sensores..." required></textarea>

            <button type="submit" class="btn-principal">Abrir Chamado</button>
        </form>
    </div>
</main>
@endsection