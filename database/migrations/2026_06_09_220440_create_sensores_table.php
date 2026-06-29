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
    Schema::create('sensores', function (Blueprint $table) {
        $table->id();
        $table->foreignId('equipamento_id')->constrained('equipamentos')->onDelete('cascade');
        $table->string('tipo');
        $table->decimal('limite_min', 5, 2)->nullable();
        $table->decimal('limite_max', 5, 2)->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensores');
    }
};
