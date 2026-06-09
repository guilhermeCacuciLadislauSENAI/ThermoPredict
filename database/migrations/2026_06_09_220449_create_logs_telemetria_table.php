<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('logs_telemetria', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sensor_id')->constrained('sensores')->onDelete('cascade');
        $table->decimal('valor_leitura', 8, 2);
        $table->string('nivel_risco');
        $table->timestamp('created_at')->useCurrent()->index(); 
        // Não precisamos da coluna updated_at aqui, dados de IoT não sofrem update.
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs_telemetria');
    }
};
