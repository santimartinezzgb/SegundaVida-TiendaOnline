<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$asunto = trim((string) ($_POST['asunto'] ?? ''));
$mensaje = trim((string) ($_POST['mensaje'] ?? ''));

if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
    jsonResponse(['ok' => false, 'error' => 'El nombre debe tener entre 2 y 100 caracteres.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['ok' => false, 'error' => 'Introduce un correo electrónico válido.'], 400);
}

if (mb_strlen($asunto) < 3 || mb_strlen($asunto) > 150) {
    jsonResponse(['ok' => false, 'error' => 'El asunto debe tener entre 3 y 150 caracteres.'], 400);
}

if (mb_strlen($mensaje) < 10) {
    jsonResponse(['ok' => false, 'error' => 'El mensaje debe tener al menos 10 caracteres.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    'INSERT INTO mensajes_contacto (nombre, email, asunto, mensaje)
     VALUES (:nombre, :email, :asunto, :mensaje)'
);
$stmt->execute([
    ':nombre' => $nombre,
    ':email' => $email,
    ':asunto' => $asunto,
    ':mensaje' => $mensaje,
]);

jsonResponse(['ok' => true, 'mensaje' => '¡Mensaje enviado! Te responderemos lo antes posible.'], 201);
