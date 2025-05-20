<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos del Comprador - {{ $evento->nombre }}</title>
    <style>
        /* ... (CSS existente) ... */
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(241, 237, 237); /* Fondo oscuro */
            color: #e0e0e0; /* Texto claro */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background-color:rgb(222, 220, 220); /* Contenedor oscuro */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(237, 233, 233, 0.5);
            width: 500px;
            max-width: 90%;
            text-align: center;
        }
        h1 {
            color: #fff;
            margin-bottom: 25px;
            font-size: 1.8em;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #b0b0b0;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="email"] {
            width: calc(100% - 20px);
            padding: 12px 10px;
            background-color:rgb(213, 207, 207);
            border: 1px solidrgb(206, 202, 202);
            border-radius: 4px;
            color: #e0e0e0;
            font-size: 1em;
            box-sizing: border-box;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus {
            outline: none;
            border-color: #8a2be2; /* Borde de enfoque */
            box-shadow: 0 0 0 0.2rem rgba(138, 43, 226, 0.25);
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-row .col {
            flex: 1;
        }
        .info-text {
            color: #b0b0b0;
            font-size: 0.9em;
            margin-top: 15px;
            margin-bottom: 30px;
        }
        .continue-button {
            background-color: #8a2be2; /* Color morado */
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }
        .continue-button:hover {
            background-color: #7a1fd1;
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
        <h1>Datos del comprador</h1>

        @if ($errors->any())
            <div class="error-message">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('comprar.store-datos-comprador', $evento->id) }}" method="POST">
            @csrf

            <div class="form-group"> {{-- Ya no es un form-row con cols --}}
                <label for="nombre_completo">Nombre y Apellido *</label>
                <input type="text" id="nombre_completo" name="nombre_completo" placeholder="Introduce tu nombre y apellido aquí" value="{{ old('nombre_completo') }}" required>
            </div>

            <div class="form-group"> {{-- Este campo ahora es independiente --}}
                <label for="dni">DNI *</label>
                <input type="text" id="dni" name="dni" placeholder="Introduce tu DNI aquí" value="{{ old('dni') }}" required>
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico *</label>
                <input type="email" id="email" name="email" placeholder="Introduce tu correo aquí" value="{{ old('email') }}" required>
            </div>

            <p class="info-text">Al finalizar, te enviaremos los datos de tu compra por Correo</p>

            <button type="submit" class="continue-button">Continuar Pedido</button>
        </form>
    </div>
</body>
</html>