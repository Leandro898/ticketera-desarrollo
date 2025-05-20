<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - {{ $evento->nombre }}</title>
    <style>
        /* CSS similar al dark mode que ya tienes */
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
            width: 550px;
            max-width: 90%;
        }
        h1 {
            color: #fff;
            margin-bottom: 25px;
            font-size: 1.8em;
            text-align: center;
        }
        .order-summary, .payment-options {
            background-color: #3a3a3a;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        .summary-item strong {
            color: #fff;
        }
        .total-item {
            font-size: 1.5em;
            font-weight: bold;
            border-top: 1px solid #4a4a4a;
            padding-top: 15px;
            margin-top: 15px;
        }
        .payment-option {
            display: flex;
            align-items: center;
            background-color: #4a4a4a;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .payment-option:hover {
            background-color: #5a5a5a;
        }
        .payment-option input[type="radio"] {
            margin-right: 15px;
            transform: scale(1.2);
        }
        .payment-option img {
            width: 30px; /* Tamaño del icono */
            height: auto;
            margin-right: 10px;
        }
        .payment-option .text {
            flex-grow: 1;
        }
        .payment-option .text strong {
            display: block;
            color: #fff;
        }
        .payment-option .text small {
            color: #b0b0b0;
        }
        .terms-checkbox {
            margin-top: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            text-align: left;
        }
        .terms-checkbox input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
            position: relative;
            top: 2px;
        }
        .terms-checkbox label {
            font-size: 0.9em;
            color: #b0b0b0;
        }
        .terms-checkbox a {
            color: #8a2be2;
            text-decoration: none;
        }
        .terms-checkbox a:hover {
            text-decoration: underline;
        }
        .checkout-button {
            background-color: #8a2be2;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }
        .checkout-button:hover {
            background-color: #7a1fd1;
        }
        .conditions-text {
            font-size: 0.85em;
            color: #b0b0b0;
            margin-top: 25px;
            line-height: 1.5;
            text-align: justify;
        }
        .conditions-text a {
            color: #8a2be2;
            text-decoration: none;
        }
        .conditions-text a:hover {
            text-decoration: underline;
        }
        .error-message {
            color: #ff6b6b;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #3a1a1a;
            border: 1px solid #6b1a1a;
            border-radius: 4px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>¿Cómo querés pagar?</h1>

        @if ($errors->any())
            <div class="error-message">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="order-summary">
            @foreach($selectedEntradas as $item)
                <div class="summary-item">
                    <span>{{ $item['cantidad'] }}x {{ $item['nombre'] }}</span>
                    <strong>${{ number_format($item['subtotal'], 0, ',', '.') }}</strong>
                </div>
            @endforeach
            <div class="summary-item total-item">
                <span>Total</span>
                <strong>${{ number_format($totalPagar, 0, ',', '.') }}</strong>
            </div>
        </div>

        <form action="{{ route('comprar.finalizar-compra', $evento->id) }}" method="POST">
            @csrf

            <div class="payment-options">
                <label class="payment-option">
                    <input type="radio" name="metodo_pago" value="mercadopago" required>
                    <img src="https://via.placeholder.com/30/000000/FFFFFF?text=MP" alt="Mercado Pago Logo"> {{-- Reemplaza con el logo real --}}
                    <div class="text">
                        <strong>Mercado Pago</strong>
                        <small>Podrás abonar con tarjeta de débito, crédito o dinero en cuenta.</small>
                    </div>
                </label>

                <label class="payment-option">
                    <input type="radio" name="metodo_pago" value="uala" required>
                    <img src="https://via.placeholder.com/30/000000/FFFFFF?text=UL" alt="Ualá Bis Logo"> {{-- Reemplaza con el logo real --}}
                    <div class="text">
                        <strong>Ualá Bis</strong>
                        <small>Podrás abonar con tarjeta de débito, crédito o dinero en cuenta.</small>
                    </div>
                </label>
                {{-- Agrega más opciones de pago aquí --}}
            </div>

            <div class="terms-checkbox">
                <input type="checkbox" id="acepto_condiciones" name="acepto_condiciones" required>
                <label for="acepto_condiciones">Estoy de acuerdo con las <a href="#">condiciones de compra</a></label>
            </div>

            <button type="submit" class="checkout-button">Continuar compra</button>
        </form>

        <div class="conditions-text">
            <p>Entiendo que estoy comprando directamente a <strong>Pinar Club Producciones</strong>, quien es responsable exclusivo de la entrega, organización y desarrollo del producto o servicio adquirido, incluyendo cualquier reclamo, devolución o inconveniente relacionado. Tikzet es una plataforma que no interviene en la venta de entradas y no participa en la organización ni en la ejecución del evento. Para más información sobre el evento y/o devoluciones/reclamos, ponte en <a href="#">contacto con el organizador</a></p>
        </div>
    </div>
</body>
</html>