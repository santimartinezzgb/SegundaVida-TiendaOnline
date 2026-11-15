<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

requerirAdminJson();
$action = trim((string) ($_POST['action'] ?? ''));

switch ($action) {
    case 'create':
        crearProducto();
        break;
    case 'update':
        actualizarProducto();
        break;
    case 'delete':
        eliminarProducto();
        break;
    default:
        jsonResponse(['ok' => false, 'error' => 'Acción de administración no válida.'], 400);
}

function crearProducto(): never
{
    [$nombre, $descripcion, $precio, $stock] = validarDatosProducto();
    $imagen = procesarImagenProducto('assets/img/default-product.svg');

    $pdo = getDB();
    $stmt = $pdo->prepare(
        'INSERT INTO productos (nombre, descripcion, precio, stock, imagen)
         VALUES (:nombre, :descripcion, :precio, :stock, :imagen)
         RETURNING id'
    );
    $stmt->execute([
        ':nombre' => $nombre,
        ':descripcion' => $descripcion,
        ':precio' => $precio,
        ':stock' => $stock,
        ':imagen' => $imagen,
    ]);

    jsonResponse([
        'ok' => true,
        'mensaje' => 'Producto creado correctamente.',
        'id' => (int) $stmt->fetchColumn(),
    ], 201);
}

function actualizarProducto(): never
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['ok' => false, 'error' => 'ID de producto no válido.'], 400);
    }

    [$nombre, $descripcion, $precio, $stock] = validarDatosProducto();

    $pdo = getDB();
    $stmtActual = $pdo->prepare('SELECT imagen FROM productos WHERE id = :id');
    $stmtActual->execute([':id' => $id]);
    $actual = $stmtActual->fetch();

    if (!$actual) {
        jsonResponse(['ok' => false, 'error' => 'El producto no existe.'], 404);
    }

    $imagen = procesarImagenProducto($actual['imagen']);

    $stmt = $pdo->prepare(
        'UPDATE productos
         SET nombre = :nombre, descripcion = :descripcion, precio = :precio, stock = :stock, imagen = :imagen
         WHERE id = :id'
    );
    $stmt->execute([
        ':nombre' => $nombre,
        ':descripcion' => $descripcion,
        ':precio' => $precio,
        ':stock' => $stock,
        ':imagen' => $imagen,
        ':id' => $id,
    ]);

    jsonResponse(['ok' => true, 'mensaje' => 'Producto actualizado correctamente.']);
}

function eliminarProducto(): never
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['ok' => false, 'error' => 'ID de producto no válido.'], 400);
    }

    $pdo = getDB();

    $stmtRef = $pdo->prepare('SELECT COUNT(*) FROM pedido_lineas WHERE producto_id = :id');
    $stmtRef->execute([':id' => $id]);

    if ((int) $stmtRef->fetchColumn() > 0) {
        // Conservamos el producto desactivado para no romper el historial de pedidos existentes
        $pdo->prepare('UPDATE productos SET activo = FALSE WHERE id = :id')->execute([':id' => $id]);
        jsonResponse(['ok' => true, 'mensaje' => 'Producto ocultado del catálogo (conservado en historial de pedidos).']);
    }

    $stmtDel = $pdo->prepare('DELETE FROM productos WHERE id = :id');
    $stmtDel->execute([':id' => $id]);

    if ($stmtDel->rowCount() === 0) {
        jsonResponse(['ok' => false, 'error' => 'El producto no existe.'], 404);
    }

    jsonResponse(['ok' => true, 'mensaje' => 'Producto eliminado correctamente.']);
}

function validarDatosProducto(): array
{
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
    $precioRaw = str_replace(',', '.', trim((string) ($_POST['precio'] ?? '')));
    $stockRaw = trim((string) ($_POST['stock'] ?? ''));

    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 150) {
        jsonResponse(['ok' => false, 'error' => 'El nombre debe tener entre 2 y 150 caracteres.'], 400);
    }

    if (mb_strlen($descripcion) < 5) {
        jsonResponse(['ok' => false, 'error' => 'La descripción debe tener al menos 5 caracteres.'], 400);
    }

    if (!is_numeric($precioRaw) || (float) $precioRaw < 0) {
        jsonResponse(['ok' => false, 'error' => 'El precio debe ser un número mayor o igual a 0.'], 400);
    }

    if (!ctype_digit($stockRaw) || (int) $stockRaw < 0) {
        jsonResponse(['ok' => false, 'error' => 'El stock debe ser un entero mayor o igual a 0.'], 400);
    }

    return [$nombre, $descripcion, round((float) $precioRaw, 2), (int) $stockRaw];
}

function procesarImagenProducto(string $imagenPorDefecto): string
{
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
        return $imagenPorDefecto;
    }

    $file = $_FILES['imagen'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['ok' => false, 'error' => 'Error al subir la imagen del producto.'], 400);
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        jsonResponse(['ok' => false, 'error' => 'La imagen del producto no puede superar los 2 MB.'], 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensionesPermitidas = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensionesPermitidas[$mime])) {
        jsonResponse(['ok' => false, 'error' => 'Formato de imagen no permitido. Usa JPG, PNG o WebP.'], 400);
    }

    $dirDestino = __DIR__ . '/../uploads/productos';
    if (!is_dir($dirDestino)) {
        mkdir($dirDestino, 0775, true);
    }

    $nombreArchivo = uniqid('prod_', true) . '.' . $extensionesPermitidas[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dirDestino . '/' . $nombreArchivo)) {
        jsonResponse(['ok' => false, 'error' => 'No se pudo guardar la imagen del producto.'], 500);
    }

    return 'uploads/productos/' . $nombreArchivo;
}
