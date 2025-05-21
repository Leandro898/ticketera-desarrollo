<?php

namespace App\Http\Controllers;

use MercadoPago\Client\OAuth\OAuthCreateRequest;
use Illuminate\Http\Request;
use MercadoPago\MercadoPagoConfig; // Para la configuración global
use MercadoPago\Client\OAuth\OAuthClient; // Para el flujo OAuth
use MercadoPago\Client\Common\AuthorizationUri; // ¡IMPORTANTE: Añadido para el flujo OAuth!
use MercadoPago\Client\Payment\PaymentClient; // Para consultar pagos (en webhook)
use MercadoPago\Exceptions\MPApiException; // Para capturar excepciones específicas de MP
use App\Models\Ticket;
use App\Models\Entrada;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Session; // No recomendado para webhooks, se mantiene por ahora para depuración

class MercadoPagoController extends Controller
{
    public function __construct()
    {
        // Obtener el access token de tu plataforma
        $accessToken = config('mercadopago.platform_access_token');

        // Líneas de depuración
        \Log::info('DEBUG: Valor de $accessToken antes de pasar a setAccessToken: ' . ($accessToken ?? 'NULL'));

        // Valida que el access token no sea null antes de usarlo
        if (is_null($accessToken)) {
            // Puedes lanzar una excepción o simplemente registrar un error crítico
            Log::critical('Mercado Pago Access Token es NULL. La integración no funcionará.');
            // Opcional: throw new \Exception("Mercado Pago Access Token no configurado.");
        } else {
            // Configuración global de Mercado Pago
            MercadoPagoConfig::setAccessToken($accessToken);
        }

        // Configura el modo Sandbox/Producción.
        MercadoPagoConfig::setRuntimeEnviroment(config('mercadopago.sandbox') ? MercadoPagoConfig::LOCAL : MercadoPagoConfig::SERVER);
    }


    /**
     * Inicia el proceso de conexión OAuth para un vendedor/organizador.
     * Redirige al usuario a la página de autorización de Mercado Pago.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function connect()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Debes iniciar sesión para conectar tu cuenta de Mercado Pago.');
        }

        $clientId = config('mercadopago.client_id');
        $redirectUri = route('mercadopago.callback');

        // Scopes reducidos al mínimo para la prueba
        $scopes = [
            'offline_access'
        ];

        $scopesString = implode('%20', $scopes);

        $authUrl = "https://auth.mercadopago.com/authorization?client_id={$clientId}&response_type=code&platform_id=mp&redirect_uri={$redirectUri}&scope={$scopesString}";

        try {
            return redirect()->away($authUrl);
        } catch (\Exception $e) {
            Log::error('Error al generar URL de autorización de Mercado Pago: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'No se pudo iniciar el proceso de conexión con Mercado Pago.');
        }
    }

    /**
     * Maneja el callback de Mercado Pago después de la autorización OAuth.
     * Intercambia el código de autorización por un access_token.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state'); // Si usas el parámetro state para seguridad CSRF

        if (!$code) {
            $error = $request->input('error');
            $errorDescription = $request->input('error_description');
            Log::error('Mercado Pago OAuth Callback: No se recibió el código de autorización. Error: ' . $error . ' - Descripción: ' . $errorDescription);
            return redirect()->route('dashboard')->with('error', 'No se pudo conectar la cuenta de Mercado Pago. Motivo: ' . ($errorDescription ?: 'Autorización denegada.'));
        }

        $redirectUri = route('mercadopago.callback');

        try {
            $oAuthClient = new OAuthClient();

            // *** CAMBIO CRÍTICO AQUÍ: Usar un objeto OAuthCreateRequest ***
            $requestOAuth = new OAuthCreateRequest([
                "code"         => $code,
                "redirect_uri" => $redirectUri,
            ]);

            $response = $oAuthClient->create($requestOAuth);

            // *** DEBUGGING: Muestra la respuesta de Mercado Pago ***
            Log::info('Mercado Pago OAuth Response:', (array) $response);
            // dd($response); // Descomenta esta línea para ver la respuesta completa en el navegador

            // Acceder a las propiedades de la respuesta como objeto
            if ($response && isset($response->access_token)) {
                $user = Auth::user();

                // *** DEBUGGING: Muestra la respuesta de Mercado Pago ***
                Log::info('Mercado Pago OAuth Response:', (array) $response);
                // dd($response); // Descomenta esta línea para ver la respuesta completa en el navegador

                $user->mp_access_token = $response->access_token;
                $user->mp_refresh_token = $response->refresh_token ?? null;
                $user->mp_public_key = $response->public_key ?? null;
                $user->mp_user_id = $response->user_id;
                $user->mp_expires_in = now()->addSeconds($response->expires_in);
                $user->save();

                Log::info('Mercado Pago OAuth Callback: Cuenta conectada exitosamente para user_id: ' . $user->id . ' (MP User ID: ' . $user->mp_user_id . ')');
                return redirect()->route('dashboard')->with('success', 'Cuenta de Mercado Pago conectada exitosamente.');
            } else {
                Log::error('Mercado Pago OAuth Callback: Error al obtener el token de Mercado Pago. Respuesta: ' . json_encode($response));
                return redirect()->route('dashboard')->with('error', 'Error al obtener el token de Mercado Pago. Detalles: ' . json_encode($response));
            }
        } catch (MPApiException $e) {
            Log::error('Mercado Pago OAuth Callback API Error: Status ' . $e->getApiResponse()->getStatusCode() . ' - Content: ' . json_encode($e->getApiResponse()->getContent()));
            return redirect()->route('dashboard')->with('error', 'Error de API al conectar con Mercado Pago: ' . $e->getApiResponse()->getStatusCode());
        } catch (\Exception $e) {
            Log::error('Mercado Pago OAuth Callback: Error de conexión con Mercado Pago: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Error de conexión con Mercado Pago: ' . $e->getMessage());
        }
    }

    /**
     * Maneja las notificaciones de webhook de Mercado Pago.
     * Procesa los estados de pago y actualiza la base de datos.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        Log::info('Mercado Pago Webhook Received:', $request->all());

        // **IMPORTANTE EN PRODUCCIÓN:** Implementar la validación de la firma del webhook.
        // Esto es crucial para asegurar que la notificación proviene de Mercado Pago.
        // https://www.mercadopago.com.ar/developers/es/guides/online-payments/webhooks/security

        $data = $request->all();
        $topic = $data['topic'] ?? $data['type']; // 'topic' para IPN, 'type' para notificaciones estándar (deprecated)
        $id = $data['id'] ?? null; // ID del recurso (ej., ID del pago, ID de la preferencia)

        // Si es una notificación de prueba manual desde el Devsite, puede que no tenga 'id'
        if (!$id && ($topic === 'test' || $topic === 'payment')) {
             Log::info("Webhook: Notificación de prueba o sin ID de recurso. Topic: " . $topic);
             return response()->json(['status' => 'ok', 'message' => 'Test notification or no resource ID'], 200);
        }

        if ($topic === 'payment') {
            $paymentId = $id;

            try {
                // *** CRÍTICO para un Marketplace:
                // Si el pago es de un organizador, necesitas el Access Token de ESE organizador
                // para consultar el pago. El Access Token global de tu plataforma NO FUNCIONARÁ.
                // Debes guardar el external_reference en tu tabla de "pedidos" y ahí
                // guardar el mp_user_id del organizador.
                // Luego, recuperas el access_token de ESE mp_user_id para instanciar PaymentClient.

                // Por ahora, usamos el Access Token global de tu plataforma (config('mercadopago.platform_access_token'))
                // Si tus pagos NO van a tu cuenta de plataforma, esto fallará en producción.
                // Asumiendo que el paymentId te permite obtener la external_reference para encontrar al vendedor
                $paymentClient = new PaymentClient();
                $payment = $paymentClient->get($paymentId);

                if ($payment) {
                    Log::info("Webhook Payment ID {$paymentId} Status: " . $payment->status);
                    Log::info("Webhook Payment External Reference: " . ($payment->external_reference ?? 'N/A'));

                    // Evitar reprocesar pagos duplicados
                    // Esto asume que 'payment_id_mp' es un campo único en tu tabla 'tickets' o 'pedidos'.
                    $existingTicket = Ticket::where('payment_id_mp', $paymentId)->first();
                    if ($existingTicket) {
                        Log::info("Webhook: Payment ID {$paymentId} ya ha sido procesado. Ignorando duplicado.");
                        return response()->json(['status' => 'ok', 'message' => 'Payment already processed'], 200);
                    }

                    if ($payment->status === 'approved') {
                        $externalReference = $payment->external_reference;
                        // Asegúrate de que tu external_reference sea única y te permita
                        // recuperar los datos del pedido (compradorData, entradasSeleccionadas, eventoId)
                        // DE TU BASE DE DATOS, NO DE LA SESIÓN.
                        $parts = explode('_', $externalReference);
                        $eventoId = $parts[0] ?? null; // Asume que el evento ID es la primera parte

                        // --- CAMBIO CLAVE PARA PRODUCCIÓN ---
                        // Aquí deberías buscar el pedido en tu base de datos usando $externalReference
                        // y obtener todos los datos necesarios para generar los tickets.
                        // Ejemplo:
                        // $order = Order::where('external_reference', $externalReference)->first();
                        // if (!$order) {
                        //     Log::error("Webhook: Pedido no encontrado para external_reference: " . $externalReference);
                        //     return response()->json(['status' => 'error', 'message' => 'Order not found'], 400);
                        // }
                        // $compradorData = ['nombre_completo' => $order->buyer_name, 'email' => $order->buyer_email];
                        // $entradasSeleccionadas = $order->items_data; // Asumiendo que tienes esto serializado

                        // Por ahora, para que tu código no falle, mantenemos la sesión, pero es un FIX TEMPORAL
                        $compradorData = Session::get('comprador_data');
                        $entradasSeleccionadas = Session::get('entradas_seleccionadas_' . $eventoId);

                        if (!$compradorData || !$entradasSeleccionadas) {
                            Log::error("Webhook: Datos de compra no encontrados (sesión) para external_reference: " . $externalReference . ". Esto fallará en producción.");
                            // Si esto pasa, el webhook no puede procesar el pedido correctamente.
                            // Deberías implementar un sistema de reintentos o manual.
                            return response()->json(['status' => 'error', 'message' => 'Order data not found (session issue)'], 400);
                        }

                        DB::beginTransaction();
                        try {
                            $ticketsCreados = [];
                            foreach ($entradasSeleccionadas as $entradaId => $cantidad) {
                                $entrada = Entrada::lockForUpdate()->find($entradaId);

                                if (!$entrada || $entrada->stock_actual < $cantidad) {
                                    throw new \Exception("Stock insuficiente o entrada no encontrada para ID: " . $entradaId . ". Cantidad solicitada: " . $cantidad . ", Stock actual: " . $entrada->stock_actual);
                                }

                                for ($i = 0; $i < $cantidad; $i++) {
                                    $ticket = Ticket::create([
                                        'entrada_id' => $entrada->id,
                                        'nombre' => $compradorData['nombre_completo'],
                                        'email' => $compradorData['email'],
                                        'estado' => 'vendida',
                                        'codigo_qr' => uniqid('QR-'), // Considera generar un QR real o un código más robusto
                                        'payment_id_mp' => $paymentId,
                                        'payment_status_mp' => $payment->status,
                                        'external_reference_mp' => $externalReference,
                                    ]);
                                    $ticketsCreados[] = $ticket;
                                }
                                $entrada->decrement('stock_actual', $cantidad);
                            }

                            DB::commit();
                            Log::info("Webhook: Tickets creados y stock actualizado exitosamente para payment ID: " . $paymentId);

                        } catch (\Exception $e) {
                            DB::rollBack();
                            Log::error("Webhook: Error procesando transacción para payment ID {$paymentId}: " . $e->getMessage());
                            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                        }

                    } else if ($payment->status === 'pending') {
                        Log::info("Webhook: Payment ID {$paymentId} is PENDING. External Reference: " . ($payment->external_reference ?? 'N/A'));
                        // Aquí podrías marcar el pedido como pendiente en tu DB
                    } else {
                        Log::info("Webhook: Payment ID {$paymentId} is in status: " . $payment->status . ". External Reference: " . ($payment->external_reference ?? 'N/A'));
                        // Aquí podrías marcar el pedido como rechazado/cancelado en tu DB
                    }
                } else {
                    Log::warning("Webhook: Payment ID {$paymentId} not found in Mercado Pago API.");
                    return response()->json(['status' => 'error', 'message' => 'Payment not found in MP'], 404);
                }
            } catch (MPApiException $e) {
                Log::error('Webhook API Error: Status ' . $e->getApiResponse()->getStatusCode() . ' - Content: ' . json_encode($e->getApiResponse()->getContent()));
                return response()->json(['status' => 'error', 'message' => 'API Error: ' . $e->getApiResponse()->getStatusCode()], 500);
            } catch (\Exception $e) {
                Log::error("Webhook: Error general al procesar webhook de Mercado Pago para ID {$paymentId}: " . $e->getMessage());
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        } else {
            Log::info("Webhook: Ignorando topic: " . $topic);
        }

        return response()->json(['status' => 'ok'], 200);
    }
}