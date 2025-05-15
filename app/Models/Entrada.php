<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entrada extends Model
{
    protected $fillable = [
        'evento_id', 'nombre', 'descripcion', 'stock_inicial', 'stock_actual',
        'max_por_compra', 'precio', 'disponible_desde', 'disponible_hasta', 'tipo',
    ];

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }
}
