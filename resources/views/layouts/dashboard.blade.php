<!--- ===================================================================== DASHBOARD POR EQUIPAMENTO ===================================================================== --->
@extends('layouts.index')

@section('title', 'Dashboard | THERMO PREDICT')
@section('header_subtitle', 'Monitoramento Inteligente da Cadeia Fria de Vacinas')

@push('scripts-head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<main class="container">
    <section class="filtros-box">
        <div class="filtro-item">
            <label>Equipamento</label>
            <select id="filtroEquipamento"> <!--- FILTRO POR EQUIPAMENTO --->
                <option value="todos">Todos</option>
                <option value="Cooler A">Cooler A</option>
                <option value="Cooler B">Cooler B</option>
                <option value="Cooler C">Cooler C</option>
            </select>
        </div>
        <div class="filtro-item">
            <label>Sensor</label>
            <select id="filtroSensor">
                <option value="todos">Todos</option>
                <option value="Sensor Interno">Sensor Interno</option>
                <option value="Sensor Porta">Sensor Porta</option>
                <option value="Sensor Backup">Sensor Backup</option>
            </select>
        </div>
    </section>

</main>
@endsection

@push('scripts')
    <script src="/assets/js/script.js"></script> <!--- Link do JavaScript --->
@endpush