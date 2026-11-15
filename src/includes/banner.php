<?php

declare(strict_types=1);

/**
 * Asegura la existencia de la tabla banner_config y devuelve la configuración activa.
 */
function obtenerBannerConfig(PDO $pdo): array
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS banner_config (
            id INTEGER PRIMARY KEY DEFAULT 1,
            activo BOOLEAN NOT NULL DEFAULT TRUE,
            etiqueta VARCHAR(120) NOT NULL DEFAULT 'BLACK FRIDAY · STOCK LIMITADO',
            titulo VARCHAR(200) NOT NULL DEFAULT 'Hasta 40% de descuento en portátiles y accesorios',
            subtitulo TEXT NOT NULL DEFAULT 'Equipos ThinkPad, Dell Latitude, MacBook y accesorios profesionales reacondicionados con rebaja directa hasta agotar existencias en almacén.',
            texto_boton VARCHAR(80) NOT NULL DEFAULT 'Ver ofertas',
            url_boton VARCHAR(200) NOT NULL DEFAULT 'tienda.php',
            color_fondo VARCHAR(20) NOT NULL DEFAULT '#181a1d',
            color_texto VARCHAR(20) NOT NULL DEFAULT '#f8fafc',
            color_acento VARCHAR(20) NOT NULL DEFAULT '#e62325',
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $pdo->exec(
        "INSERT INTO banner_config (id) VALUES (1) ON CONFLICT (id) DO NOTHING"
    );

    $row = $pdo->query('SELECT * FROM banner_config WHERE id = 1')->fetch();
    return $row ?: [
        'id' => 1,
        'activo' => true,
        'etiqueta' => 'BLACK FRIDAY · STOCK LIMITADO',
        'titulo' => 'Hasta 40% de descuento en portátiles y accesorios',
        'subtitulo' => 'Equipos ThinkPad, Dell Latitude, MacBook y accesorios profesionales reacondicionados con rebaja directa hasta agotar existencias en almacén.',
        'texto_boton' => 'Ver ofertas',
        'url_boton' => 'tienda.php',
        'color_fondo' => '#181a1d',
        'color_texto' => '#f8fafc',
        'color_acento' => '#e62325',
    ];
}
