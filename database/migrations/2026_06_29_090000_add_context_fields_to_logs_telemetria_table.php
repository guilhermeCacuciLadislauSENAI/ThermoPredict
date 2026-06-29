<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logs_telemetria', function (Blueprint $table) {
            $table->decimal('temperatura_externa', 8, 2)->nullable()->after('valor_leitura');
            $table->decimal('umidade_externa', 5, 2)->nullable()->after('temperatura_externa');
            $table->boolean('tampa_aberta')->default(false)->after('umidade_externa');
        });
    }

    public function down(): void
    {
        Schema::table('logs_telemetria', function (Blueprint $table) {
            $table->dropColumn(['temperatura_externa', 'umidade_externa', 'tampa_aberta']);
        });
    }
};
