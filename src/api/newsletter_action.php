<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));

if ($email === '' || mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['ok' => false, 'error' => 'Introduce un correo electrónico válido.'], 400);
}

$pdo = getDB();
$stmt = $pdo->prepare(
    'INSERT INTO newsletter_suscripciones (email)
     VALUES (:email)
     ON CONFLICT (email) DO NOTHING'
);
$stmt->execute([':email' => $email]);

if ($stmt->rowCount() === 0) {
    jsonResponse(['ok' => true, 'mensaje' => 'Este correo ya estaba suscrito a nuestra newsletter.'], 200);
}

jsonResponse(['ok' => true, 'mensaje' => '¡Suscripción confirmada! Te avisaremos con los próximos lotes.'], 201);
