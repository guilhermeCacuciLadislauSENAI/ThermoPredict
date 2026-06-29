<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PredicaoController;
use App\Http\Controllers\RelatorioController;

Route::get('/', function () { return view('login'); })->name('login');
Route::get('/login', function () { return redirect()->route('login'); });
Route::get('/register', function () { return redirect()->route('login'); });

Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::middleware(['auth'])->group(function () {

    Route::get('/aguardando-ativacao', function () { return view('aguardando'); })->name('aguardando.ativacao');

    // --- Módulo Cliente (B2B) ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/simulacao', [DashboardController::class, 'simulation'])->name('dashboard.simulation');
    Route::post('/dashboard/simulacao/leituras', [DashboardController::class, 'simulationTick'])->name('dashboard.simulation.tick');
    Route::get('/dashboard/exportar/pdf', [DashboardController::class, 'pdf'])->name('dashboard.pdf');
    Route::get('/dashboard/exportar/csv', [DashboardController::class, 'csv'])->name('dashboard.csv');
    Route::get('/predicao', [PredicaoController::class, 'index'])->name('predicao');
    Route::get('/predicao/pdf', [PredicaoController::class, 'pdf'])->name('predicao.pdf');
    Route::get('/predicao/csv', [PredicaoController::class, 'csv'])->name('predicao.csv');
    
    Route::get('/novo-chamado', function () { return view('form'); })->name('chamados.create');
    Route::post('/novo-chamado', function () { return redirect()->route('suporte'); })->name('chamados.store');
    Route::get('/suporte', function () { return view('suporte'); })->name('suporte');
    Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorios');
    Route::get('/relatorios/pdf', [RelatorioController::class, 'pdf'])->name('relatorios.pdf');
    Route::get('/relatorios/csv', [RelatorioController::class, 'csv'])->name('relatorios.csv');

    // --- Módulo Admin ---
    Route::get('/admin/clientes', [AdminController::class, 'clientes'])->name('admin.clientes');
    Route::get('/admin/pendencias', [AdminController::class, 'pendencias'])->name('admin.pendencias');
    Route::post('/admin/aprovar/{id}', [AdminController::class, 'aprovar'])->name('admin.aprovar');
    Route::get('/admin/empresa/{id}/dashboard', [AdminController::class, 'verGeladeiras'])->name('admin.empresa.dashboard');

    // ⚙️ Gestão de Hardware (Coolers e Sensores)
    Route::get('/admin/empresa/{id}/novo-cooler', [AdminController::class, 'createCooler'])->name('admin.cooler.create');
    Route::post('/admin/empresa/{id}/novo-cooler', [AdminController::class, 'storeCooler'])->name('admin.cooler.store');
    
    Route::get('/admin/cooler/{id}/gerenciar', [AdminController::class, 'gerenciarCooler'])->name('admin.cooler.manage');
    Route::post('/admin/cooler/{id}/sensor', [AdminController::class, 'storeSensor'])->name('admin.sensor.store');
    Route::delete('/admin/sensor/{id}', [AdminController::class, 'destroySensor'])->name('admin.sensor.destroy');

    // --- Sistema ---
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
