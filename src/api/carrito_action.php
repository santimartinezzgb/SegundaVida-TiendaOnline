<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

$usuario = requerirCompradorJson();
$usuarioId = $usuario['id'];
$action = trim((string) ($_POST['action'] ?? ''));

switch ($action) {
    case 'add':
        anadirAlCarrito($usuarioId);
        break;
    case 'remove_line':
        quitarLineaCarrito($usuarioId);
        break;
    case 'confirm':
        confirmarCarrito($usuarioId);
        break;
    case 'buy_now':
        comprarDirectamente($usuarioId);
        break;
    default:
        jsonResponse(['ok' => false, 'error' => 'Acción de carrito no válida.'], 400);
}

function anadirAlCarrito(int $usuarioId): never
{
    $productoId = (int) ($_POST['producto_id'] ?? 0);
    $cantidad = (int) ($_POST['cantidad'] ?? 1);

    if ($productoId <= 0 || $cantidad <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Producto o cantidad no válidos.'], 400);
    }

    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        // Comprobar disponibilidad sin reservar stock todavía
        $stmtProd = $pdo->prepare(
            'SELECT id, stock, precio
             FROM productos
             WHERE id = :id AND activo = TRUE'
        );
        $stmtProd->execute([':id' => $productoId]);
        $producto = $stmtProd->fetch();

        if (!$producto || (int) $producto['stock'] < $cantidad) {
            $pdo->rollBack();
            jsonResponse(['ok' => false, 'error' => 'No hay stock suficiente para este producto.'], 409);
        }

        // Obtener o crear el pedido en estado 'carrito' del comprador
        $stmtCarrito = $pdo->prepare(
            "SELECT id FROM pedidos WHERE usuario_id = :uid AND estado = 'carrito' FOR UPDATE"
        );
        $stmtCarrito->execute([':uid' => $usuarioId]);
        $carrito = $stmtCarrito->fetch();

        if ($carrito) {
            $pedidoId = (int) $carrito['id'];
        } else {
            $stmtNuevo = $pdo->prepare(
                "INSERT INTO pedidos (usuario_id, estado, total) VALUES (:uid, 'carrito', 0) RETURNING id"
            );
            $stmtNuevo->execute([':uid' => $usuarioId]);
            $pedidoId = (int) $stmtNuevo->fetchColumn();
        }

        // Insertar o incrementar la línea de producto validando stock total en carrito
        $stmtLinea = $pdo->prepare(
            "SELECT id, cantidad FROM pedido_lineas
             WHERE pedido_id = :pid AND producto_id = :prod_id AND estado_linea = 'activa'
             FOR UPDATE"
        );
        $stmtLinea->execute([
            ':pid' => $pedidoId,
            ':prod_id' => $productoId,
        ]);
        $linea = $stmtLinea->fetch();

        $cantidadFinal = ($linea ? (int) $linea['cantidad'] : 0) + $cantidad;
        if ($cantidadFinal > (int) $producto['stock']) {
            $pdo->rollBack();
            jsonResponse(['ok' => false, 'error' => 'No hay stock suficiente para añadir más unidades de este producto.'], 409);
        }

        if ($linea) {
            $stmtUpdLinea = $pdo->prepare(
                'UPDATE pedido_lineas
                 SET cantidad = cantidad + :qty, precio_unitario = :precio
                 WHERE id = :lid'
            );
            $stmtUpdLinea->execute([
                ':qty' => $cantidad,
                ':precio' => $producto['precio'],
                ':lid' => $linea['id'],
            ]);
        } else {
            $stmtInsLinea = $pdo->prepare(
                "INSERT INTO pedido_lineas (pedido_id, producto_id, cantidad, precio_unitario, estado_linea)
                 VALUES (:pid, :prod_id, :qty, :precio, 'activa')"
            );
            $stmtInsLinea->execute([
                ':pid' => $pedidoId,
                ':prod_id' => $productoId,
                ':qty' => $cantidad,
                ':precio' => $producto['precio'],
            ]);
        }

        recalcularTotalPedido($pdo, $pedidoId);
        $pdo->commit();

        jsonResponse([
            'ok' => true,
            'mensaje' => 'Producto añadido al carrito.',
            'stock_restante' => (int) $producto['stock'],
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['ok' => false, 'error' => 'Error al añadir el producto al carrito.'], 500);
    }
}

function quitarLineaCarrito(int $usuarioId): never
{
    $lineaId = (int) ($_POST['linea_id'] ?? 0);
    if ($lineaId <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Línea de carrito no válida.'], 400);
    }

    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "SELECT l.id, l.pedido_id, l.producto_id, l.cantidad
             FROM pedido_lineas l
             INNER JOIN pedidos p ON p.id = l.pedido_id
             WHERE l.id = :lid AND p.usuario_id = :uid AND p.estado = 'carrito' AND l.estado_linea = 'activa'
             FOR UPDATE"
        );
        $stmt->execute([
            ':lid' => $lineaId,
            ':uid' => $usuarioId,
        ]);
        $linea = $stmt->fetch();

        if (!$linea) {
            $pdo->rollBack();
            jsonResponse(['ok' => false, 'error' => 'No se encontró la línea en tu carrito.'], 404);
        }

        $pdo->prepare('DELETE FROM pedido_lineas WHERE id = :lid')->execute([':lid' => $linea['id']]);

        recalcularTotalPedido($pdo, (int) $linea['pedido_id']);
        $pdo->commit();

        jsonResponse(['ok' => true, 'mensaje' => 'Producto eliminado del carrito.']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['ok' => false, 'error' => 'Error al quitar la línea del carrito.'], 500);
    }
}

function confirmarCarrito(int $usuarioId): never
{
    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "SELECT id FROM pedidos WHERE usuario_id = :uid AND estado = 'carrito' FOR UPDATE"
        );
        $stmt->execute([':uid' => $usuarioId]);
        $carrito = $stmt->fetch();

        if (!$carrito) {
            $pdo->rollBack();
            jsonResponse(['ok' => false, 'error' => 'Tu carrito está vacío.'], 400);
        }

        $pedidoId = (int) $carrito['id'];

        $stmtLineas = $pdo->prepare(
            "SELECT l.id, l.producto_id, l.cantidad, pr.nombre
             FROM pedido_lineas l
             INNER JOIN productos pr ON pr.id = l.producto_id
             WHERE l.pedido_id = :pid AND l.estado_linea = 'activa'
             ORDER BY l.id ASC"
        );
        $stmtLineas->execute([':pid' => $pedidoId]);
        $lineas = $stmtLineas->fetchAll();

        if (empty($lineas)) {
            $pdo->rollBack();
            jsonResponse(['ok' => false, 'error' => 'Tu carrito no tiene productos.'], 400);
        }

        // Descuento atómico al confirmar compra: si otro usuario lo compró antes, abortar
        $stmtDescontar = $pdo->prepare(
            'UPDATE productos
             SET stock = stock - :qty
             WHERE id = :prod_id AND activo = TRUE AND stock >= :qty
             RETURNING stock, precio'
        );

        foreach ($lineas as $linea) {
            $stmtDescontar->execute([
                ':qty' => (int) $linea['cantidad'],
                ':prod_id' => (int) $linea['producto_id'],
            ]);
            $prodActualizado = $stmtDescontar->fetch();

            if (!$prodActualizado) {
                $pdo->rollBack();
                jsonResponse([
                    'ok' => false,
                    'error' => 'El producto "' . $linea['nombre'] . '" ya no se encuentra disponible con stock suficiente. Quítalo del carrito para continuar.',
                ], 409);
            }
        }

        recalcularTotalPedido($pdo, $pedidoId);

        $pdo->prepare(
            "UPDATE pedidos SET estado = 'pendiente', created_at = CURRENT_TIMESTAMP WHERE id = :pid"
        )->execute([':pid' => $pedidoId]);

        $pdo->commit();
        jsonResponse(['ok' => true, 'mensaje' => 'Compra confirmada. Pendiente de aceptación por el administrador.']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['ok' => false, 'error' => 'Error al confirmar la compra.'], 500);
    }
}

function recalcularTotalPedido(PDO $pdo, int $pedidoId): void
{
    $stmt = $pdo->prepare(
        "UPDATE pedidos
         SET total = COALESCE((
             SELECT SUM(cantidad * precio_unitario)
             FROM pedido_lineas
             WHERE pedido_id = :pid AND estado_linea = 'activa'
         ), 0)
         WHERE id = :pid"
    );
    $stmt->execute([':pid' => $pedidoId]);
}

function comprarDirectamente(int $usuarioId): never
{
    $productoId = (int) ($_POST['producto_id'] ?? 0);
    $cantidad = (int) ($_POST['cantidad'] ?? 1);

    if ($productoId <= 0 || $cantidad <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Producto o cantidad no válidos.'], 400);
    }

    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        $stmtProd = $pdo->prepare(
            'UPDATE productos
             SET stock = stock - :qty
             WHERE id = :id AND activo = TRUE AND stock >= :qty
             RETURNING stock, precio'
        );
        $stmtProd->execute([
            ':qty' => $cantidad,
            ':id' => $productoId,
        ]);
        $producto = $stmtProd->fetch();

        if (!$producto) {
            $pdo->rollBack();
            jsonResponse(['ok' => false, 'error' => 'No hay stock suficiente para este producto.'], 409);
        }

        $total = round($cantidad * (float) $producto['precio'], 2);

        $stmtPed = $pdo->prepare(
            "INSERT INTO pedidos (usuario_id, estado, total)
             VALUES (:uid, 'pendiente', :total)
             RETURNING id"
        );
        $stmtPed->execute([
            ':uid' => $usuarioId,
            ':total' => $total,
        ]);
        $pedidoId = (int) $stmtPed->fetchColumn();

        $stmtLinea = $pdo->prepare(
            "INSERT INTO pedido_lineas (pedido_id, producto_id, cantidad, precio_unitario, estado_linea)
             VALUES (:pid, :prod_id, :qty, :precio, 'activa')"
        );
        $stmtLinea->execute([
            ':pid' => $pedidoId,
            ':prod_id' => $productoId,
            ':qty' => $cantidad,
            ':precio' => $producto['precio'],
        ]);

        $pdo->commit();
        jsonResponse([
            'ok' => true,
            'mensaje' => 'Compra directa realizada. Tu pedido queda pendiente de aceptación.',
            'pedido_id' => $pedidoId,
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['ok' => false, 'error' => 'Error al procesar la compra directa.'], 500);
    }
}
