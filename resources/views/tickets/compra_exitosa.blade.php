<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra Exitosa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a1a;
            color: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            text-align: center;
        }
        .success-card {
            background-color: #2a2a2a;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
            width: 500px;
            max-width: 90%;
        }
        .success-card h1 {
            color: #4CAF50; /* Verde de éxito */
            margin-bottom: 20px;
        }
        .success-card p {
            font-size: 1.1em;
            margin-bottom: 25px;
        }
        .success-card a {
            background-color: #8a2be2;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .success-card a:hover {
            background-color: #7a1fd1;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <h1>¡Compra Exitosa!</h1>
        <p>{{ session('success', 'Tu compra se ha realizado correctamente.') }}</p>
        <p>Revisa tu correo electrónico para los detalles de tus tickets.</p>
        <a href="{{ route('evento.show', $evento->id) }}">Volver al Evento</a>
    </div>
</body>
</html>