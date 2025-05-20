<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Entrada;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Session; // Importar la clase Session

class CompraEntradaController extends Controller
{
    // Método para mostrar la página de selección de entradas (Paso 2)
    public function showSeleccionarEntradas(Evento $evento)
    {
        // Pasamos el evento y sus entradas visibles y con stock
        $entradas = $evento->entradas()->where('visible', true)->where('stock_actual', '>', 0)->get();

        return view('comprar', compact('evento', 'entradas'));
    }

    // Método para procesar la selección de entradas y redirigir a datos del comprador (Paso 2 POST)
    public function storeSeleccionarEntradas(Request $request, Evento $evento)
    {
        $validatedData = $request->validate([
            'cantidades' => 'required|array|min:1', // Aseguramos que al menos una cantidad sea mayor a 0
            'cantidades.*' => 'integer|min:0', // Validamos que las cantidades sean enteros no negativos
        ], [
            'cantidades.min' => 'Debes seleccionar al menos una entrada para continuar.',
        ]);

        $selectedEntradas = [];
        $totalEntradas = 0;

        foreach ($validatedData['cantidades'] as $entradaId => $cantidad) {
            if ($cantidad > 0) {
                $entrada = Entrada::findOrFail($entradaId);

                // Validar que la entrada pertenece al evento
                if ($entrada->evento_id !== $evento->id) {
                    throw ValidationException::withMessages(['cantidades.' . $entradaId => 'La entrada seleccionada no pertenece a este evento.']);
                }

                // Validar stock y max_por_compra
                if ($entrada->stock_actual < $cantidad) {
                    throw ValidationException::withMessages(['cantidades.' . $entradaId => "No hay suficiente stock disponible para '{$entrada->nombre}'. Stock actual: {$entrada->stock_actual}."]);
                }
                if ($entrada->max_por_compra && $cantidad > $entrada->max_por_compra) {
                    throw ValidationException::withMessages(['cantidades.' . $entradaId => "No puedes comprar más de {$entrada->max_por_compra} entradas de '{$entrada->nombre}'."]);
                }

                $selectedEntradas[] = [
                    'entrada_id' => $entrada->id,
                    'nombre' => $entrada->nombre,
                    'precio' => $entrada->precio,
                    'cantidad' => $cantidad,
                    'subtotal' => $entrada->precio * $cantidad,
                ];
                $totalEntradas += $cantidad;
            }
        }

        if (empty($selectedEntradas)) {
            return redirect()->back()->withErrors(['cantidades' => 'Debes seleccionar al menos una entrada para continuar.'])->withInput();
        }

        // Guardar la selección de entradas en la sesión para el siguiente paso
        Session::put('compra_evento_id', $evento->id);
        Session::put('selected_entradas', $selectedEntradas);
        Session::put('total_entradas_cantidad', $totalEntradas);

        // Redirigir al formulario de datos del comprador
        return redirect()->route('comprar.datos-comprador', $evento->id);
    }

    // Los métodos 'show' y 'store' anteriores del controlador ahora se manejarán en los nuevos pasos.
    // Puedes renombrarlos o eliminarlos si ya no son necesarios para este flujo.
    // Ejemplo:
    // public function show(Evento $evento) { /* Ya no es necesario si usas EventoPublicoController@show */ }
    // public function store(Request $request, Evento $evento) { /* Este se convierte en el método final de checkout/pago */ }

    public function index()
    {
        $tickets = Ticket::with('entrada.evento')->latest()->paginate(10);
        return view('tickets.index', compact('tickets'));
    }

    public function showDatosComprador(Evento $evento)
    {
        // Asegurarse de que hay entradas seleccionadas en la sesión
        if (!Session::has('selected_entradas')) {
            return redirect()->route('comprar.seleccionar-entradas', $evento->id)
                             ->withErrors(['error' => 'Por favor, selecciona tus entradas primero.']);
        }
        // Puedes pasar el evento y las entradas seleccionadas a la vista si necesitas mostrarlas
        $selectedEntradas = Session::get('selected_entradas');
        $totalEntradasCantidad = Session::get('total_entradas_cantidad');

        return view('tickets.datos_comprador', compact('evento', 'selectedEntradas', 'totalEntradasCantidad'));
    }

    public function storeDatosComprador(Request $request, Evento $evento)
    {
        $validatedData = $request->validate([
            'nombre_completo' => 'required|string|max:255', // Cambiado a nombre_completo
            'dni' => 'required|string|max:20', // Si no tienes columna 'dni' en 'users', quita 'unique:users' aquí
            'email' => 'required|email|max:255',
        ]);

        // Guardar los datos del comprador en la sesión
        Session::put('comprador_data', [
            'nombre_completo' => $validatedData['nombre_completo'], // Almacenar como nombre_completo
            'dni' => $validatedData['dni'],
            'email' => $validatedData['email'],
        ]);

        // Redirigir a la página de checkout/pago
        return redirect()->route('comprar.checkout', $evento->id);
    }

    public function showCheckout(Evento $evento)
    {
        // Asegurarse de que haya entradas seleccionadas y datos del comprador en la sesión
        if (!Session::has('selected_entradas') || !Session::has('comprador_data')) {
            return redirect()->route('evento.show', $evento->id) // O a un paso anterior
                             ->withErrors(['error' => 'Información de compra incompleta.']);
        }

        $selectedEntradas = Session::get('selected_entradas');
        $compradorData = Session::get('comprador_data');

        $totalPagar = array_sum(array_column($selectedEntradas, 'subtotal'));

        return view('tickets.checkout', compact('evento', 'selectedEntradas', 'compradorData', 'totalPagar'));
    }

    public function finalizarCompra(Request $request, Evento $evento)
    {
        // Validar que el usuario acepta las condiciones de compra
        $request->validate([
            'acepto_condiciones' => 'required|accepted',
            'metodo_pago' => 'required|string|in:mercadopago,uala', // Agrega tus métodos de pago
        ], [
            'acepto_condiciones.required' => 'Debes aceptar las condiciones de compra.',
            'acepto_condiciones.accepted' => 'Debes aceptar las condiciones de compra.',
            'metodo_pago.required' => 'Debes seleccionar un método de pago.',
            'metodo_pago.in' => 'El método de pago seleccionado no es válido.',
        ]);

        // Recuperar datos de la sesión
        $selectedEntradas = Session::get('selected_entradas');
        $compradorData = Session::get('comprador_data');

        if (! $selectedEntradas || ! $compradorData || Session::get('compra_evento_id') != $evento->id) {
            return redirect()->route('evento.show', $evento->id)
                             ->withErrors(['error' => 'Información de compra perdida. Por favor, reinicia el proceso.']);
        }

        DB::beginTransaction();
        try {
            $totalEntradasCompradas = 0;
            $ticketsCreados = [];

            foreach ($selectedEntradas as $item) {
                $entrada = Entrada::lockForUpdate()->findOrFail($item['entrada_id']); // Bloquea la entrada para asegurar stock

                if ($entrada->stock_actual < $item['cantidad']) {
                    throw ValidationException::withMessages(['stock' => "No hay suficiente stock disponible para '{$entrada->nombre}'. Stock actual: {$entrada->stock_actual}."]);
                }

                for ($i = 0; $i < $item['cantidad']; $i++) {
                    $ticket = Ticket::create([
                        'entrada_id' => $entrada->id,
                        'nombre' => $compradorData['nombre'],
                        'email' => $compradorData['email'],
                        'estado' => 'vendida', // O 'pendiente_pago' si se integra una pasarela real
                        'codigo_qr' => uniqid('QR-'),
                    ]);
                    $ticketsCreados[] = $ticket;
                    $totalEntradasCompradas++;
                }
                $entrada->decrement('stock_actual', $item['cantidad']);
            }

            // Aquí integrarías la lógica de la pasarela de pago real.
            // Por ahora, simulamos que el pago es exitoso.

            // Limpiar la sesión de datos de compra
            Session::forget(['compra_evento_id', 'selected_entradas', 'comprador_data', 'total_entradas_cantidad']);

            DB::commit();

            // Redirigir a una página de éxito final
            return redirect()->route('compra.exitosa', $evento->id)->with('success', '¡Tu compra se ha completado con éxito!');

        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->validator->getMessageBag())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error en el checkout final: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Hubo un error al finalizar la compra. Por favor, inténtalo de nuevo.']);
        }
    }

    public function compraExitosa(Evento $evento)
    {
        // Puedes pasar datos para mostrar en la página de éxito, como el ID de la compra o un resumen.
        // Asegúrate de que el mensaje de éxito viene de la sesión.
        return view('tickets.compra_exitosa', compact('evento'));
    }
}