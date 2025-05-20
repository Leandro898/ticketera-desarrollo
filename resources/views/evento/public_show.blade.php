<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $evento->nombre }} - Comprar Entradas</title>
    <style>
        /* Aquí va el CSS de tu primera imagen (dark mode) */
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a1a; /* Fondo oscuro */
            color: #e0e0e0; /* Texto claro */
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        .event-container {
            background-color: #2a2a2a; /* Contenedor oscuro */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-wrap: wrap; /* Permite que los elementos se envuelvan en pantallas pequeñas */
            max-width: 900px;
            width: 100%;
            overflow: hidden;
        }
        .event-image {
            flex: 1; /* Ocupa espacio flexible */
            min-width: 300px; /* Ancho mínimo para la imagen */
            max-width: 40%; /* Máximo 40% del ancho del contenedor */
            overflow: hidden;
        }
        .event-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        .event-details {
            flex: 2; /* Ocupa el doble de espacio flexible */
            min-width: 350px; /* Ancho mínimo para los detalles */
            padding: 30px;
            box-sizing: border-box;
        }
        .event-details h1 {
            color: #fff;
            margin-top: 0;
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        .event-details p {
            margin-bottom: 8px;
            font-size: 1em;
            color: #b0b0b0;
        }
        .event-details .organizer, .event-details .location, .event-details .date-time, .event-details .genre {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .event-details .icon {
            margin-right: 10px;
            color: #8a8a8a; /* Color de los íconos */
        }
        .price-section {
            background-color: #3a3a3a;
            padding: 20px;
            border-radius: 6px;
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .price-section .price-text {
            font-size: 1.5em;
            font-weight: bold;
            color: #fff;
        }
        .price-section .price-subtext {
            font-size: 0.9em;
            color: #b0b0b0;
        }
        .buy-button {
            background-color: #8a2be2; /* Color morado */
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .buy-button:hover {
            background-color: #7a1fd1;
        }
        .about-event {
            margin-top: 30px;
        }
        .about-event h2 {
            color: #fff;
            font-size: 1.6em;
            margin-bottom: 15px;
        }
        .about-event p {
            color: #b0b0b0;
            line-height: 1.6;
        }
        /* Media Queries para responsividad */
        @media (max-width: 768px) {
            .event-container {
                flex-direction: column;
                max-width: 100%;
                border-radius: 0; /* Eliminar bordes redondeados en móviles si es necesario */
            }
            .event-image {
                max-width: 100%;
                min-width: auto;
            }
            .event-details {
                padding: 20px;
                min-width: auto;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="event-container">
        <div class="event-image">
            <img src="{{ $evento->banner_url ?? asset('images/default_banner.jpg') }}" alt="{{ $evento->nombre }}">
        </div>
        <div class="event-details">
            <p class="date-time">{{ \Carbon\Carbon::parse($evento->fecha_inicio)->isoFormat('dddd, DD [de] MMMM HH:mm') }} - {{ \Carbon\Carbon::parse($evento->fecha_fin)->isoFormat('dddd, DD [de] MMMM HH:mm') }}</p>
            <h1>{{ $evento->nombre }}</h1>
            <p class="organizer"><i class="fas fa-building icon"></i> {{ $evento->organizador }}</p>
            <p class="location"><i class="fas fa-map-marker-alt icon"></i> {{ $evento->direccion }}</p>
            <p class="genre"><i class="fas fa-music icon"></i> {{ $evento->genero }}</p>

            <div class="price-section">
                <div class="price-info">
                    <div class="price-text">Desde ${{ number_format($evento->entradas()->min('precio') ?? 0, 0, ',', '.') }}</div>
                    <div class="price-subtext">Precio final. Sin sorpresas.</div>
                </div>
                <a href="{{ route('comprar.seleccionar-entradas', $evento->id) }}" class="buy-button">COMPRAR</a>
            </div>

            <div class="about-event">
                <h2>Sobre este evento</h2>
                <p>{{ $evento->descripcion }}</p>
            </div>
        </div>
    </div>
</body>
</html>