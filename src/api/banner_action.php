<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/banner.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

requerirAdminJson();

$etiqueta = trim((string) ($_POST['etiqueta'] ?? ''));
$titulo = trim((string) ($_POST['titulo'] ?? ''));
$subtitulo = trim((string) ($_POST['subtitulo'] ?? ''));
$textoBoton = trim((string) ($_POST['texto_boton'] ?? ''));
$urlBoton = trim((string) ($_POST['url_boton'] ?? 'tienda.php'));
$colorFondo = trim((string) ($_POST['color_fondo'] ?? '#181a1d'));
$colorTexto = trim((string) ($_POST['color_texto'] ?? '#f8fafc'));
$colorAcento = trim((string) ($_POST['color_acento'] ?? '#e62325'));
$activo = isset($_POST['activo']) && $_POST['activo'] === '1';

if (mb_strlen($titulo) < 3 || mb_strlen($titulo) > 200) {
    jsonResponse(['ok' => false, 'error' => 'El título debe tener entre 3 y 200 caracteres.'], 400);
}

if (mb_strlen($etiqueta) > 120 || mb_strlen($textoBoton) > 80 || mb_strlen($urlBoton) > 200) {
    jsonResponse(['ok' => false, 'error' => 'Alguno de los campos supera la longitud máxima.'], 400);
}

if ($urlBoton === '' || preg_match('/^\s*javascript:/i', $urlBoton)) {
    $urlBoton = 'tienda.php';
}

$validarHex = static function (string $hex, string $fallback): string {
    return preg_match('/^#[0-9a-fA-F]{6}$/', $hex) ? $hex : $fallback;
};

$colorFondo = $validarHex($colorFondo, '#181a1d');
$colorTexto = $validarHex($colorTexto, '#f8fafc');
$colorAcento = $validarHex($colorAcento, '#e62325');

$pdo = getDB();
obtenerBannerConfig($pdo);

$stmt = $pdo->prepare(
    'UPDATE banner_config
     SET activo = :activo,
         etiqueta = :etiqueta,
         titulo = :titulo,
         subtitulo = :subtitulo,
         texto_boton = :texto_boton,
         url_boton = :url_boton,
         color_fondo = :color_fondo,
         color_texto = :color_texto,
         color_acento = :color_acento,
         updated_at = CURRENT_TIMESTAMP
     WHERE id = 1'
);

$stmt->execute([
    ':activo' => $activo ? 'true' : 'false',
    ':etiqueta' => $etiqueta,
    ':titulo' => $titulo,
    ':subtitulo' => $subtitulo,
    ':texto_boton' => $textoBoton,
    ':url_boton' => $urlBoton,
    ':color_fondo' => $colorFondo,
    ':color_texto' => $colorTexto,
    ':color_acento' => $colorAcento,
]);

jsonResponse(['ok' => true, 'mensaje' => 'Banner promocional actualizado correctamente.']);
