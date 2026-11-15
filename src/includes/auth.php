<?php

declare(strict_types=1);

/**
 * Inicia la sesión PHP de forma segura si aún no está activa.
 */
function iniciarSesionSiNoExiste(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/**
 * Devuelve los datos del usuario autenticado o null si es visitante.
 */
function usuarioActual(): ?array
{
    iniciarSesionSiNoExiste();

    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['usuario_id'],
        'nombre' => (string) ($_SESSION['nombre'] ?? ''),
        'email' => (string) ($_SESSION['email'] ?? ''),
        'rol' => (string) ($_SESSION['rol'] ?? 'comprador'),
        'avatar' => (string) ($_SESSION['avatar'] ?? 'assets/img/default-avatar.svg'),
    ];
}

function estaLogueado(): bool
{
    return usuarioActual() !== null;
}

function esAdmin(): bool
{
    $usuario = usuarioActual();
    return $usuario !== null && $usuario['rol'] === 'admin';
}

function esComprador(): bool
{
    $usuario = usuarioActual();
    return $usuario !== null && $usuario['rol'] === 'comprador';
}

/**
 * Escapa texto para salida HTML segura frente a XSS.
 */
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Envía una respuesta JSON y detiene la ejecución.
 */
function jsonResponse(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Exige sesión iniciada para endpoints API; devuelve 401 si no hay sesión.
 */
function requerirLoginJson(): array
{
    $usuario = usuarioActual();
    if ($usuario === null) {
        jsonResponse(['ok' => false, 'error' => 'Debes iniciar sesión para realizar esta acción.'], 401);
    }
    return $usuario;
}

/**
 * Exige rol 'admin' para endpoints API; devuelve 401 o 403 si no corresponde.
 */
function requerirAdminJson(): array
{
    $usuario = requerirLoginJson();
    if ($usuario['rol'] !== 'admin') {
        jsonResponse(['ok' => false, 'error' => 'Acceso restringido a administradores.'], 403);
    }
    return $usuario;
}

/**
 * Exige rol 'comprador' para endpoints API; devuelve 401 o 403 si no corresponde.
 */
function requerirCompradorJson(): array
{
    $usuario = requerirLoginJson();
    if ($usuario['rol'] !== 'comprador') {
        jsonResponse(['ok' => false, 'error' => 'Acción disponible solo para compradores.'], 403);
    }
    return $usuario;
}
