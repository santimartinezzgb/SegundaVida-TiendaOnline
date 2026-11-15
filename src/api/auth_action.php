<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

iniciarSesionSiNoExiste();

$action = trim((string) ($_POST['action'] ?? ''));

switch ($action) {
    case 'login':
        manejarLogin();
        break;
    case 'register':
        manejarRegistro();
        break;
    case 'logout':
        manejarLogout();
        break;
    default:
        jsonResponse(['ok' => false, 'error' => 'Acción no válida.'], 400);
}

function manejarLogin(): never
{
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        jsonResponse(['ok' => false, 'error' => 'Debes completar el correo y la contraseña.'], 400);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, nombre, email, password_hash, rol, avatar FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        jsonResponse(['ok' => false, 'error' => 'Correo o contraseña incorrectos.'], 401);
    }

    guardarUsuarioEnSesion($usuario);
    jsonResponse(['ok' => true, 'mensaje' => 'Sesión iniciada correctamente.']);
}

function manejarRegistro(): never
{
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
        jsonResponse(['ok' => false, 'error' => 'El nombre debe tener entre 2 y 100 caracteres.'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['ok' => false, 'error' => 'El correo electrónico no es válido.'], 400);
    }

    if (strlen($password) < 6) {
        jsonResponse(['ok' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.'], 400);
    }

    $pdo = getDB();

    $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $check->execute([':email' => $email]);
    if ($check->fetch()) {
        jsonResponse(['ok' => false, 'error' => 'Ya existe una cuenta registrada con ese correo.'], 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // El registro público siempre asigna el rol 'comprador'
    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, email, password_hash, rol)
         VALUES (:nombre, :email, :hash, :rol)
         RETURNING id, nombre, email, rol, avatar'
    );
    $stmt->execute([
        ':nombre' => $nombre,
        ':email' => $email,
        ':hash' => $hash,
        ':rol' => 'comprador',
    ]);
    $nuevoUsuario = $stmt->fetch();

    guardarUsuarioEnSesion($nuevoUsuario);
    jsonResponse(['ok' => true, 'mensaje' => 'Cuenta creada correctamente.'], 201);
}

function manejarLogout(): never
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    jsonResponse(['ok' => true, 'mensaje' => 'Sesión cerrada.']);
}

function guardarUsuarioEnSesion(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['rol'] = $usuario['rol'];
    $_SESSION['avatar'] = $usuario['avatar'];
}
