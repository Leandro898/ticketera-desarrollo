<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\Request;

class EventoPublicoController extends Controller
{
    public function show(Evento $evento)
    {
        // Esto mostrará la página de detalles del evento con el banner y el botón "Comprar"
        return view('evento.public_show', compact('evento'));
    }
}