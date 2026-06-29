<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogTelemetria extends Model
{
    protected $table = 'logs_telemetria';
    
    // Desativa o updated_at, pois logs de IoT não são atualizados
    public const UPDATED_AT = null; 

    protected $fillable = [
        'sensor_id',
        'valor_leitura',
        'temperatura_externa',
        'umidade_externa',
        'tampa_aberta',
        'nivel_risco',
        'created_at',
    ];

    protected $casts = [
        'valor_leitura' => 'float',
        'temperatura_externa' => 'float',
        'umidade_externa' => 'float',
        'tampa_aberta' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }
}
