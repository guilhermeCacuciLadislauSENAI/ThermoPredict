<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    protected $table = 'sensores';
    protected $fillable = ['equipamento_id', 'tipo', 'limite_min', 'limite_max'];

    public function equipamento()
    {
        return $this->belongsTo(Equipamento::class);
    }

    // NOVA RELAÇÃO: Um sensor tem muitos logs de telemetria
    public function logs()
    {
        return $this->hasMany(LogTelemetria::class);
    }
}