<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Entrada;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Session;
use MercadoPago\SDK;
use MercadoPago\Item;
use MercadoPago\Payer;
use MercadoPago\Preference;
use MercadoPago\Payment;

class CompraEntradaController extends Controller
{
    public function __construct()
    {
        // Tus credenciales de plataforma
        SDK::setClientId(config('mercadopago.client_id'));
        SDK::setClientSecret(config('mercadopago.client_secret'));
        SDK::setSandboxMode(config('mercadopago.sandbox'));
    }
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
        $entradasSeleccionadas = Session::get('entradas_seleccionadas_' . $evento->id);
        $compradorData = Session::get('comprador_data');

        if (!$entradasSeleccionadas || !$compradorData) {
            return redirect()->route('comprar.seleccionar-entradas', $evento->id)
                             ->with('error', 'Debes seleccionar entradas y proporcionar tus datos primero.');
        }

        $total = 0;
        $items = [];
        foreach ($entradasSeleccionadas as $id => $cantidad) {
            $entrada = Entrada::find($id);
            if ($entrada && $cantidad > 0) {
                $subtotal = $entrada->precio * $cantidad;
                $total += $subtotal;

                $item = new Item();
                $item->id = $entrada->id;
                $item->title = $entrada->nombre;
                $item->quantity = $cantidad;
                $item->unit_price = $entrada->precio;
                $items[] = $item;
            }
        }

        // --- Lógica para Mercado Pago Preference con Split Payments ---
        $preference = new Preference();
        $preference->items = $items;

        $payer = new Payer();
        $payer->name = $compradorData['nombre_completo'];
        $payer->email = $compradorData['email'];
        // Si necesitas más datos del pagador (DNI, teléfono), agrégalos aquí.
        // $payer->identification = array("type" => "DNI", "number" => $compradorData['dni']);
        $preference->payer = $payer;

        // URL a donde Mercado Pago redirigirá después de la compra (éxito, pendiente, falla)
        $preference->back_urls = array(
            "success" => route('compra.exitosa', $evento->id),
            "failure" => route('comprar.checkout', $evento->id) . '?status=failure', // Vuelve al checkout con un mensaje
            "pending" => route('comprar.checkout', $evento->id) . '?status=pending', // Vuelve al checkout con un mensaje
        );
        $preference->auto_return = "approved"; // Para redirigir automáticamente solo en éxito

        // **Configuración de Notificaciones (Webhooks)**
        // Muy importante para que Mercado Pago te notifique el estado del pago
        $preference->notification_url = route('mercadopago.webhook'); // Crear esta ruta y método
        $preference->external_reference = $evento->id . '_' . uniqid(); // Un ID único para tu referencia

        // --- Lógica del Split Payment ---
        // Asume que tu Evento tiene un organizador asociado y este tiene un mp_access_token
        // Ejemplo: $evento->user (si el user es el organizador)
        $organizador = $evento->user; // Ajusta según tu relación (ej. $evento->organizador)

        if (!$organizador || !$organizador->mp_access_token) {
            // Manejar error: el organizador no tiene su cuenta MP conectada
            return redirect()->back()->with('error', 'El organizador no tiene su cuenta de Mercado Pago configurada.');
        }

        // Establece el access_token del receptor (el organizador)
        // MercadoPago\SDK::setAccessToken($organizador->mp_access_token); // NO HACER ESTO AQUI!
        // La preferencia siempre se crea con el access_token de la plataforma.
        // El split payment se especifica dentro de la preferencia.

        // Calcula comisiones
        $comision_plataforma_porcentaje = 0.05; // 5% para tu plataforma
        $monto_para_organizador = $total - ($total * $comision_plataforma_porcentaje);

        // Define el beneficiario (el organizador)
        // La API de Pagos Divididos va dentro de 'payments' o 'marketplace_settings' en el SDK de PHP
        // Para Checkout Pro, usas 'marketplace_settings' con 'installments' y 'payments' para la split
        $preference->marketplace_settings = [
            'installments' => 1, // Número de cuotas, para split payment se recomienda 1.
            'payments' => [
                'receiver_address' => [
                    'zip_code' => 'B7600', // Código postal ficticio del receptor si no lo tienes
                    'street_name' => 'Organizador St',
                    'street_number' => 123,
                ],
                'split_payments' => [
                    [
                        'payer_id' => $organizador->mp_user_id, // El user_id de Mercado Pago del organizador
                        'amount' => round($monto_para_organizador, 2), // Redondea a 2 decimales
                        'fee_bearer' => 'payer', // 'payer' = comprador, 'receiver' = vendedor (organizador)
                                                 // Esto es crítico para determinar quién paga las comisiones de MP.
                                                 // Si es 'payer', las comisiones las descuenta de tu comisión o del monto total.
                                                 // Si es 'receiver', las comisiones de MP se descuentan del monto del organizador.
                    ],
                ],
            ],
        ];

        try {
            $preference->save();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al generar el pago con Mercado Pago: ' . $e->getMessage());
        }

        // Pasa el ID de la preferencia a la vista
        return view('tickets.checkout', compact('evento', 'entradasSeleccionadas', 'compradorData', 'total', 'preference'));
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