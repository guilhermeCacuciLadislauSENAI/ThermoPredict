<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogTelemetria extends Model
{
    protected $table = 'logs_telemetria';
    
    // Desativa o updated_at, pois logs de IoT não são atualizados
    public const UPDATED_AT = null; 

    protected $fillable = ['sensor_id', 'valor_leitura', 'nivel_risco', 'created_at'];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }
}