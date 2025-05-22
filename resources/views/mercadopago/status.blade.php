<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Conexión Mercado Pago</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h2>Estado de tu Conexión con Mercado Pago</h2>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                @auth
                    <h3>Datos de tu cuenta:</h3>
                    <p><strong>ID de Usuario:</strong> {{ Auth::user()->id }}</p>
                    <p><strong>Email:</strong> {{ Auth::user()->email }}</p>

                    <hr>

                    <h3>Datos de Mercado Pago (guardados):</h3>
                    @if (Auth::user()->mp_access_token)
                        <p class="text-success">
                            <i class="fas fa-check-circle"></i> **¡Conexión Exitosa!**
                        </p>
                        <p><strong>Access Token:</strong> <code>{{ substr(Auth::user()->mp_access_token, 0, 30) }}...</code> (solo los primeros caracteres)</p>
                        <p><strong>Refresh Token:</strong> <code>{{ substr(Auth::user()->mp_refresh_token, 0, 30) }}...</code> (solo los primeros caracteres)</p>
                        <p><strong>Public Key:</strong> <code>{{ Auth::user()->mp_public_key }}</code></p>
                        <p><strong>MP User ID:</strong> <code>{{ Auth::user()->mp_user_id }}</code></p>
                        <p><strong>Expira en:</strong> <code>{{ Auth::user()->mp_expires_in }}</code></p>
                        <a href="#" class="btn btn-danger mt-3">Desconectar Mercado Pago</a>
                    @else
                        <p class="text-warning">
                            <i class="fas fa-exclamation-triangle"></i> **No hay datos de conexión de Mercado Pago guardados para esta cuenta.**
                        </p>
                        <a href="{{ route('mercadopago.connect') }}" class="btn btn-primary mt-3">Conectar con Mercado Pago</a>
                    @endif
                @else
                    <p class="text-info">Debes iniciar sesión para ver el estado de tu conexión con Mercado Pago.</p>
                    <a href="{{ route('login') }}" class="btn btn-info">Iniciar Sesión</a>
                @endauth
            </div>
            <div class="card-footer text-center">
                <a href="/" class="btn btn-secondary">Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>