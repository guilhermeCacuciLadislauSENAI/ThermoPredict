<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipamento extends Model
{
    protected $table = 'equipamentos';
    protected $fillable = ['empresa_id', 'nome', 'localizacao', 'status'];

    // Um equipamento (cooler) tem muitos sensores
    public function sensores()
    {
        return $this->hasMany(Sensor::class);
    }

    // ➕ O CÓDIGO QUE FALTAVA: Um equipamento pertence a uma empresa
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}