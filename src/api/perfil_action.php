<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

$usuario = requerirLoginJson();

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
    jsonResponse(['ok' => false, 'error' => 'El nombre debe tener entre 2 y 100 caracteres.'], 400);
}

if ($password !== '' && strlen($password) < 6) {
    jsonResponse(['ok' => false, 'error' => 'La nueva contraseña debe tener al menos 6 caracteres.'], 400);
}

$nuevoAvatar = $usuario['avatar'];

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['avatar'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['ok' => false, 'error' => 'Error al subir la imagen de perfil.'], 400);
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        jsonResponse(['ok' => false, 'error' => 'La imagen no puede superar los 2 MB.'], 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensionesPermitidas = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensionesPermitidas[$mime])) {
        jsonResponse(['ok' => false, 'error' => 'Formato no permitido. Usa JPG, PNG o WebP.'], 400);
    }

    $dirDestino = __DIR__ . '/../uploads/avatars';
    if (!is_dir($dirDestino)) {
        mkdir($dirDestino, 0775, true);
    }

    $nombreArchivo = uniqid('avatar_', true) . '.' . $extensionesPermitidas[$mime];
    $rutaFisica = $dirDestino . '/' . $nombreArchivo;

    if (!move_uploaded_file($file['tmp_name'], $rutaFisica)) {
        jsonResponse(['ok' => false, 'error' => 'No se pudo guardar la imagen en el servidor.'], 500);
    }

    $nuevoAvatar = 'uploads/avatars/' . $nombreArchivo;
}

$pdo = getDB();

if ($password !== '') {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'UPDATE usuarios SET nombre = :nombre, avatar = :avatar, password_hash = :hash WHERE id = :id'
    );
    $stmt->execute([
        ':nombre' => $nombre,
        ':avatar' => $nuevoAvatar,
        ':hash' => $hash,
        ':id' => $usuario['id'],
    ]);
} else {
    $stmt = $pdo->prepare(
        'UPDATE usuarios SET nombre = :nombre, avatar = :avatar WHERE id = :id'
    );
    $stmt->execute([
        ':nombre' => $nombre,
        ':avatar' => $nuevoAvatar,
        ':id' => $usuario['id'],
    ]);
}

$_SESSION['nombre'] = $nombre;
$_SESSION['avatar'] = $nuevoAvatar;

jsonResponse([
    'ok' => true,
    'mensaje' => 'Perfil actualizado correctamente.',
    'avatar' => $nuevoAvatar,
]);
