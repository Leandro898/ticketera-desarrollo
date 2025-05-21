<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - {{ $evento->nombre }}</title>
    <style>
        /* ... (CSS existente) ... */
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a1a;
            color: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #2a2a2a;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
            width: 600px;
            max-width: 90%;
            text-align: center;
        }
        h1 {
            color: #fff;
            margin-bottom: 25px;
            font-size: 1.8em;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        .summary-total {
            font-size: 1.4em;
            font-weight: bold;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #444;
        }
        .payment-methods {
            text-align: left;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .payment-method-item {
            background-color: #3a3a3a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
        }
        .payment-method-item:hover {
            background-color: #4a4a4a;
        }
        .payment-method-item input[type="radio"] {
            margin-right: 15px;
            transform: scale(1.2);
            accent-color: #8a2be2;
        }
        .payment-method-item label {
            flex-grow: 1;
            font-size: 1.1em;
            display: flex;
            align-items: center;
        }
        .payment-method-item img {
            height: 24px;
            margin-right: 10px;
        }
        .terms-checkbox {
            margin-top: 25px;
            text-align: left;
            font-size: 0.95em;
        }
        .terms-checkbox input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.1);
            accent-color: #8a2be2;
        }
        .continue-button {
            background-color: #8a2be2;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
            margin-top: 25px;
        }
        .continue-button:hover {
            background-color: #7a1fd1;
        }
        .conditions-box {
            background-color: #2a2a2a;
            border-top: 1px solid #444;
            padding-top: 20px;
            margin-top: 30px;
            text-align: left;
            font-size: 0.9em;
            color: #b0b0b0;
        }
        .conditions-box p {
            margin-bottom: 10px;
        }
        .conditions-box a {
            color: #8a2be2;
            text-decoration: none;
        }
        .conditions-box a:hover {
            text-decoration: underline;
        }
    </style>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
</head>
<body>
    <div class="container">
        <h1>Resumen de Compra</h1>

        @foreach ($entradasSeleccionadas as $id => $cantidad)
            @php
                $entrada = \App\Models\Entrada::find($id);
            @endphp
            @if ($entrada)
                <div class="summary-item">
                    <span>{{ $cantidad }}x {{ $entrada->nombre }}</span>
                    <span>${{ number_format($entrada->precio * $cantidad, 0, ',', '.') }}</span>
                </div>
            @endif
        @endforeach

        <div class="summary-total">
            <span>Total</span>
            <span>${{ number_format($total, 0, ',', '.') }}</span>
        </div>

        <div class="payment-methods">
            <h2>¿Cómo querés pagar?</h2>
            <div class="payment-method-item">
                <input type="radio" id="mercadopago" name="payment_method" value="mercadopago" checked>
                <label for="mercadopago">
                    <img src="https://img.icons8.com/color/48/000000/mercadopago.png" alt="Mercado Pago Logo">
                    MercadoPago
                    <br>
                    <small>Podrás abonar con tarjeta de débito, crédito o dinero en cuenta.</small>
                </label>
            </div>
            </div>

        <div class="terms-checkbox">
            <input type="checkbox" id="terms_conditions" name="terms_conditions" required>
            <label for="terms_conditions">Estoy de acuerdo con las <a href="#">condiciones de compra</a></label>
        </div>

        @if (isset($preference) && $preference->id)
            <div class="cho-container" style="margin-top: 25px;"></div>
            <script>
                // Inicializa el SDK de Mercado Pago con tu Public Key
                const mp = new MercadoPago("{{ config('mercadopago.client_id') }}", { // Usar CLIENT_ID como public key
                    locale: 'es-AR'
                });

                // Crea el checkout con la preferencia generada en el backend
                mp.checkout({
                    preference: {
                        id: '{{ $preference->id }}'
                    },
                    render: {
                        container: '.cho-container', // Donde se renderizará el botón de pago
                        label: 'Continuar compra', // Texto del botón
                    }
                });
            </script>
        @else
            <p style="color: #ff6b6b; margin-top: 20px;">Error al generar el botón de pago. Por favor, inténtalo de nuevo.</p>
            <a href="{{ route('comprar.checkout', $evento->id) }}" class="continue-button">Volver a intentar</a>
        @endif


        <div class="conditions-box">
            <p>Condiciones generales de compra</p>
            <p>Entiendo que estoy comprando directamente a **Pinar Club Producciones**, quien es responsable exclusivo de la entrega, organización y desarrollo del producto o servicio adquirido, incluyendo cualquier reclamo, devolución o inconveniente relacionado. Tikzet es una plataforma que no interviene en la venta de entradas y no participa en la organización ni en la ejecución del evento. Para más información sobre el evento y/o devoluciones/reclamos, ponte en <a href="#">contacto con el organizador</a></p>
        </div>
    </div>
</body>
</html>