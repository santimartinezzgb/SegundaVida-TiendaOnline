<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

$pdo = getDB();
$pdo->exec(
    'ALTER TABLE producto_opiniones
     ADD COLUMN IF NOT EXISTS respuesta_admin TEXT,
     ADD COLUMN IF NOT EXISTS respuesta_fecha TIMESTAMP'
);

$action = trim((string) ($_POST['action'] ?? 'create'));

if ($action === 'reply_admin') {
    requerirAdminJson();

    $opinionId = (int) ($_POST['opinion_id'] ?? 0);
    $respuesta = trim((string) ($_POST['respuesta'] ?? ''));

    if ($opinionId <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Opinión no válida.'], 400);
    }

    if (mb_strlen($respuesta) < 2 || mb_strlen($respuesta) > 1000) {
        jsonResponse(['ok' => false, 'error' => 'La respuesta debe tener entre 2 y 1000 caracteres.'], 400);
    }

    $stmtUpd = $pdo->prepare(
        'UPDATE producto_opiniones
         SET respuesta_admin = :resp, respuesta_fecha = CURRENT_TIMESTAMP
         WHERE id = :oid'
    );
    $stmtUpd->execute([
        ':resp' => $respuesta,
        ':oid' => $opinionId,
    ]);

    if ($stmtUpd->rowCount() === 0) {
        jsonResponse(['ok' => false, 'error' => 'No se encontró la opinión seleccionada.'], 404);
    }

    jsonResponse(['ok' => true, 'mensaje' => 'Respuesta publicada correctamente.']);
}

$usuario = requerirLoginJson();
if ($usuario['rol'] === 'admin') {
    jsonResponse(['ok' => false, 'error' => 'El administrador responde opiniones en lugar de publicar valoraciones.'], 403);
}

$productoId = (int) ($_POST['producto_id'] ?? 0);
$puntuacion = (int) ($_POST['puntuacion'] ?? 0);
$comentario = trim((string) ($_POST['comentario'] ?? ''));

if ($productoId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Producto no válido.'], 400);
}

if ($puntuacion < 1 || $puntuacion > 5) {
    jsonResponse(['ok' => false, 'error' => 'La puntuación debe estar entre 1 y 5 estrellas.'], 400);
}

if (mb_strlen($comentario) < 5 || mb_strlen($comentario) > 1000) {
    jsonResponse(['ok' => false, 'error' => 'La opinión debe tener entre 5 y 1000 caracteres.'], 400);
}

$stmtProd = $pdo->prepare('SELECT id FROM productos WHERE id = :id AND activo = TRUE');
$stmtProd->execute([':id' => $productoId]);
if (!$stmtProd->fetch()) {
    jsonResponse(['ok' => false, 'error' => 'El producto no existe.'], 404);
}

$stmt = $pdo->prepare(
    'INSERT INTO producto_opiniones (producto_id, usuario_id, puntuacion, comentario)
     VALUES (:producto_id, :usuario_id, :puntuacion, :comentario)'
);
$stmt->execute([
    ':producto_id' => $productoId,
    ':usuario_id' => $usuario['id'],
    ':puntuacion' => $puntuacion,
    ':comentario' => $comentario,
]);

jsonResponse(['ok' => true, 'mensaje' => 'Opinión publicada correctamente.'], 201);
