<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

$usuario = requerirLoginJson();
$action = trim((string) ($_POST['action'] ?? ''));

switch ($action) {
    case 'cancel_line':
        cancelarLinea($usuario);
        break;
    case 'cancel_order':
        cancelarPedido($usuario);
        break;
    case 'accept_order':
        aceptarPedido($usuario);
        break;
    case 'request_refund':
        solicitarReembolso($usuario);
        break;
    default:
        jsonResponse(['ok' => false, 'error' => 'Acción de pedido no válida.'], 400);
}

function cancelarLinea(array $usuario): never
{
    $lineaId = (int) ($_POST['linea_id'] ?? 0);
    if ($lineaId <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Línea de pedido no válida.'], 400);
    }

    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        if ($usuario['rol'] === 'admin') {
            $stmt = $pdo->prepare(
                "SELECT l.id, l.pedido_id, l.producto_id, l.cantidad
                 FROM pedido_lineas l
                 INNER JOIN pedidos p ON p.id = l.pedido_id
                 WHERE l.id = :lid AND p.estado IN ('pendiente', 'aceptado') AND l.estado_linea = 'activa'
                 FOR UPDATE"
            );
            $stmt->execute([':lid' => $lineaId]);
        } else {
            // El comprador solo puede cancelar líneas activas de sus propios pedidos pendientes
            $stmt = $pdo->prepare(
                "SELECT l.id, l.pedido_id, l.producto_id, l.cantidad
                 FROM pedido_lineas l
                 INNER JOIN pedidos p ON p.id = l.pedido_id
                 WHERE l.id = :lid AND p.usuario_id = :uid AND p.estado = 'pendiente' AND l.estado_linea = 'activa'
                 FOR UPDATE"
            );
            $stmt->execute([
                ':lid' => $lineaId,
                ':uid' => $usuario['id'],
            ]);
        }

        $linea = $stmt->fetch();
        if (!$linea) {
            $pdo->rollBack();
            jsonResponse(['ok' => false, 'error' => 'No se pudo cancelar la línea seleccionada.'], 404);
        }

        $pdo->prepare("UPDATE pedido_lineas SET estado_linea = 'cancelada' WHERE id = :lid")
            ->execute([':lid' => $linea['id']]);

        // Reposición exacta de unidades al stock
        $pdo->prepare('UPDATE productos SET stock = stock + :qty WHERE id = :prod_id')
            ->execute([
                ':qty' => $linea['cantidad'],
                ':prod_id' => $linea['producto_id'],
            ]);

        $pedidoId = (int) $linea['pedido_id'];
        actualizarEstadoYTotalTrasCancelacion($pdo, $pedidoId);

        $pdo->commit();
        jsonResponse(['ok' => true, 'mensaje' => 'Línea cancelada y stock repuesto correctamente.']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['ok' => false, 'error' => 'Error al cancelar la línea del pedido.'], 500);
    }
}

function cancelarPedido(array $usuario): never
{
    $pedidoId = (int) ($_POST['pedido_id'] ?? 0);
    if ($pedidoId <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Pedido no válido.'], 400);
    }

    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        if ($usuario['rol'] === 'admin') {
            $stmt = $pdo->prepare(
                "SELECT id FROM pedidos WHERE id = :pid AND estado IN ('pendiente', 'aceptado') FOR UPDATE"
            );
            $stmt->execute([':pid' => $pedidoId]);
        } else {
            // El comprador solo puede cancelar sus propios pedidos pendientes
            $stmt = $pdo->prepare(
                "SELECT id FROM pedidos WHERE id = :pid AND usuario_id = :uid AND estado = 'pendiente' FOR UPDATE"
            );
            $stmt->execute([
                ':pid' => $pedidoId,
                ':uid' => $usuario['id'],
            ]);
        }

        $pedido = $stmt->fetch();
        if (!$pedido) {
            $pdo->rollBack();
            jsonResponse(['ok' => false, 'error' => 'No se pudo cancelar el pedido.'], 404);
        }

        $stmtLineas = $pdo->prepare(
            "SELECT id, producto_id, cantidad
             FROM pedido_lineas
             WHERE pedido_id = :pid AND estado_linea = 'activa'
             FOR UPDATE"
        );
        $stmtLineas->execute([':pid' => $pedidoId]);
        $lineas = $stmtLineas->fetchAll();

        $stmtReponer = $pdo->prepare('UPDATE productos SET stock = stock + :qty WHERE id = :prod_id');
        foreach ($lineas as $linea) {
            $stmtReponer->execute([
                ':qty' => $linea['cantidad'],
                ':prod_id' => $linea['producto_id'],
            ]);
        }

        $pdo->prepare("UPDATE pedido_lineas SET estado_linea = 'cancelada' WHERE pedido_id = :pid AND estado_linea = 'activa'")
            ->execute([':pid' => $pedidoId]);

        $pdo->prepare("UPDATE pedidos SET estado = 'cancelado', total = 0 WHERE id = :pid")
            ->execute([':pid' => $pedidoId]);

        $pdo->commit();
        jsonResponse(['ok' => true, 'mensaje' => 'Pedido cancelado y unidades repuestas al stock.']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['ok' => false, 'error' => 'Error al cancelar el pedido.'], 500);
    }
}

function solicitarReembolso(array $usuario): never
{
    if ($usuario['rol'] !== 'comprador') {
        jsonResponse(['ok' => false, 'error' => 'Solo el comprador puede solicitar reembolsos.'], 403);
    }

    $pedidoId = (int) ($_POST['pedido_id'] ?? 0);
    if ($pedidoId <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Pedido no válido.'], 400);
    }

    $pdo = getDB();
    $pdo->exec('ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS reembolso_solicitado BOOLEAN NOT NULL DEFAULT FALSE');

    $stmt = $pdo->prepare(
        "UPDATE pedidos
         SET reembolso_solicitado = TRUE
         WHERE id = :pid AND usuario_id = :uid AND estado = 'aceptado' AND reembolso_solicitado = FALSE"
    );
    $stmt->execute([
        ':pid' => $pedidoId,
        ':uid' => $usuario['id'],
    ]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['ok' => false, 'error' => 'No se pudo solicitar el reembolso para este pedido.'], 404);
    }

    jsonResponse(['ok' => true, 'mensaje' => 'Solicitud de reembolso enviada correctamente.']);
}

function aceptarPedido(array $usuario): never
{
    if ($usuario['rol'] !== 'admin') {
        jsonResponse(['ok' => false, 'error' => 'Solo el administrador puede aceptar pedidos.'], 403);
    }

    $pedidoId = (int) ($_POST['pedido_id'] ?? 0);
    if ($pedidoId <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Pedido no válido.'], 400);
    }

    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "UPDATE pedidos
             SET estado = 'aceptado'
             WHERE id = :pid AND estado = 'pendiente'
             RETURNING id"
        );
        $stmt->execute([':pid' => $pedidoId]);

        if (!$stmt->fetch()) {
            $pdo->rollBack();
            jsonResponse(['ok' => false, 'error' => 'El pedido no existe o ya no está pendiente.'], 404);
        }

        $pdo->commit();
        jsonResponse(['ok' => true, 'mensaje' => 'Pedido aceptado correctamente.']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['ok' => false, 'error' => 'Error al aceptar el pedido.'], 500);
    }
}

function actualizarEstadoYTotalTrasCancelacion(PDO $pdo, int $pedidoId): void
{
    $stmtRestantes = $pdo->prepare(
        "SELECT COUNT(*) AS activas, COALESCE(SUM(cantidad * precio_unitario), 0) AS total
         FROM pedido_lineas
         WHERE pedido_id = :pid AND estado_linea = 'activa'"
    );
    $stmtRestantes->execute([':pid' => $pedidoId]);
    $res = $stmtRestantes->fetch();

    if ((int) $res['activas'] === 0) {
        $pdo->prepare("UPDATE pedidos SET estado = 'cancelado', total = 0 WHERE id = :pid")
            ->execute([':pid' => $pedidoId]);
    } else {
        $pdo->prepare('UPDATE pedidos SET total = :total WHERE id = :pid')
            ->execute([
                ':total' => $res['total'],
                ':pid' => $pedidoId,
            ]);
    }
}
