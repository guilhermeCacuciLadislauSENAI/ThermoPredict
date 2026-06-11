<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    // Diz explicitamente o nome da tabela no banco
    protected $table = 'empresas';

    // Campos permitidos para cadastro em massa (Mass Assignment)
    protected $fillable = [
        'cnpj', 
        'razao_social'
    ];

    // Estabelece a relação: Uma empresa tem MUITOS usuários
    public function usuarios()
    {
        return $this->hasMany(Usuario::class);
    }

    // Uma empresa tem muitos equipamentos (geladeiras)
    public function equipamentos()
    {
        return $this->hasMany(Equipamento::class);
    }
}