<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index() 
    {
        $empresa = Auth::user()->empresa;
        
        // Puxa as geladeiras, os sensores, e os logs de cada sensor ordenados pela data
        $equipamentos = $empresa->equipamentos()->with(['sensores.logs' => function($query) {
            $query->orderBy('created_at', 'asc');
        }])->get();

        return view('dashboard', compact('empresa', 'equipamentos'));
    }
}