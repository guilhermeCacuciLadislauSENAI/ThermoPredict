<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'empresa_id',
        'nome', 
        'email', 
        'password', 
        'perfil',
        'status'
    ];

    // Oculta dados sensíveis quando o Model for convertido para JSON ou Array
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Estabelece a relação: Este usuário PERTENCE a uma Empresa
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
