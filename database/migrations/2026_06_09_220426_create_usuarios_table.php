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
    Schema::create('usuarios', function (Blueprint $table) {
        $table->id();
        $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
        $table->string('nome');
        $table->string('email')->unique();
        $table->string('password');
        // NOVA COLUNA ABAIXO: Define se é 'cliente' ou 'admin'
        $table->string('perfil')->default('cliente'); 
        $table->string('status')->default('pendente');
        $table->rememberToken();
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
