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
    Schema::create('chamados', function (Blueprint $table) {
        $table->id();
        $table->foreignId('equipamento_id')->constrained('equipamentos')->onDelete('cascade');
        $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
        $table->string('prioridade');
        $table->text('descricao');
        $table->string('status')->default('Aberto');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chamados');
    }
};
