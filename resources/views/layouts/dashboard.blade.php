@extends('layouts.app')

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
            <select id="filtroEquipamento">
                <option value="todos">Todos</option>
                <option value="Geladeira A">Geladeira A</option>
                <option value="Geladeira B">Geladeira B</option>
                <option value="Freezer Laboratório">Freezer Laboratório</option>
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
    <script src="{{ asset('js/script.js') }}"></script>
@endpush