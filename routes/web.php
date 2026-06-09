<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return view('login');
})->name('login');

Route::get('/login', function () { return redirect()->route('login'); });
Route::get('/register', function () { return redirect()->route('login'); });

Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::middleware(['auth'])->group(function () {

    Route::get('/aguardando-ativacao', function () {
        return view('aguardando'); 
    })->name('aguardando.ativacao');

    // --- Módulo Cliente ---
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/novo-chamado', function () {
        return view('form');
    })->name('chamados.create');
    
    Route::post('/novo-chamado', function () {
        return redirect()->route('suporte');
    })->name('chamados.store');

    Route::get('/suporte', function () {
        return view('suporte');
    })->name('suporte');

    Route::get('/relatorios', function () {
        return view('relatorios');
    })->name('relatorios');

    // --- Módulo Admin ---
    Route::get('/admin/clientes', [AdminController::class, 'clientes'])->name('admin.clientes');
    Route::get('/admin/pendencias', [AdminController::class, 'pendencias'])->name('admin.pendencias');
    Route::post('/admin/aprovar/{id}', [AdminController::class, 'aprovar'])->name('admin.aprovar');

    // --- Sair ---
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});