<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$usuarioCheck = usuarioActual();
if ($usuarioCheck === null || $usuarioCheck['rol'] !== 'comprador') {
    header('Location: tienda.php');
    exit;
}

$paginaActiva = 'carrito';
$tituloPagina = 'Mi Carrito — SegundaVida';
require_once __DIR__ . '/includes/navbar.php';

$pdo = getDB();
$stmtCart = $pdo->prepare(
    "SELECT l.id AS linea_id, l.producto_id, l.cantidad, l.precio_unitario,
            pr.nombre, pr.stock AS stock_disponible, pr.activo AS producto_activo, p.total
     FROM pedidos p
     INNER JOIN pedido_lineas l ON l.pedido_id = p.id
     INNER JOIN productos pr ON pr.id = l.producto_id
     WHERE p.usuario_id = :uid AND p.estado = 'carrito' AND l.estado_linea = 'activa'
     ORDER BY l.id ASC"
);
$stmtCart->execute([':uid' => $usuario['id']]);
$carritoLineas = $stmtCart->fetchAll();
$carritoTotal = !empty($carritoLineas) ? (float) $carritoLineas[0]['total'] : 0.0;

$productosNoDisponibles = [];
foreach ($carritoLineas as $linea) {
    $activo = filter_var($linea['producto_activo'], FILTER_VALIDATE_BOOLEAN);
    $stockDisp = (int) $linea['stock_disponible'];
    if (!$activo || $stockDisp < (int) $linea['cantidad']) {
        $productosNoDisponibles[] = $linea['nombre'];
    }
}
?>

<main class="flex-grow-1 py-4 py-lg-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <nav aria-label="Migas de pan" class="mb-2">
                    <ol class="breadcrumb small mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="tienda.php" class="text-decoration-none">Tienda</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Carrito</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold mb-1">Tu carrito actual</h1>
                <p class="text-muted mb-0">El stock queda reservado mientras permanezca en tu carrito.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="tienda.php" class="btn btn-outline-dark fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i>Seguir comprando
                </a>
                <a href="perfil.php" class="btn btn-outline-dark fw-semibold">
                    <i class="bi bi-person-gear me-1"></i>Mi perfil e historial
                </a>
            </div>
        </div>

        <div id="tiendaAlert" class="alert d-none" role="alert"></div>

        <?php if (!empty($productosNoDisponibles)): ?>
            <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <div>
                    <strong>Aviso de disponibilidad:</strong>
                    <?= count($productosNoDisponibles) === 1 ? 'El siguiente producto ya no se encuentra disponible' : 'Los siguientes productos ya no se encuentran disponibles' ?>
                    con el stock solicitado (otro cliente pudo adquirirlo antes):
                    <strong><?= e(implode(', ', $productosNoDisponibles)) ?></strong>.
                    Quítalo<?= count($productosNoDisponibles) === 1 ? '' : 's' ?> del carrito para poder confirmar tu compra.
                </div>
            </div>
        <?php endif; ?>

        <section id="mi-carrito" class="mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 fw-bold mb-0">
                        <i class="bi bi-cart3 me-2 text-emerald"></i>Productos reservados
                    </h2>
                    <span class="small text-muted">
                        <?= count($carritoLineas) ?> <?= count($carritoLineas) === 1 ? 'producto' : 'productos' ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($carritoLineas)): ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-3">Tu carrito está vacío. Añade algún equipo del catálogo para reservarlo.</p>
                            <a href="tienda.php" class="btn btn-emerald fw-semibold px-4">
                                <i class="bi bi-bag me-1"></i>Ir a la tienda
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-3">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-end">Precio ud.</th>
                                        <th class="text-end">Subtotal</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($carritoLineas as $linea): ?>
                                        <?php
                                        $subtotal = (int) $linea['cantidad'] * (float) $linea['precio_unitario'];
                                        $lineaDisponible = filter_var($linea['producto_activo'], FILTER_VALIDATE_BOOLEAN)
                                            && (int) $linea['stock_disponible'] >= (int) $linea['cantidad'];
                                        ?>
                                        <tr class="<?= $lineaDisponible ? '' : 'table-warning' ?>">
                                            <td class="fw-medium">
                                                <a href="tienda.php?producto=<?= (int) $linea['producto_id'] ?>" class="text-decoration-none">
                                                    <?= e($linea['nombre']) ?>
                                                </a>
                                                <?php if (!$lineaDisponible): ?>
                                                    <span class="badge bg-danger ms-2">Ya no disponible</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?= (int) $linea['cantidad'] ?></td>
                                            <td class="text-end"><?= number_format((float) $linea['precio_unitario'], 2, ',', '.') ?> €</td>
                                            <td class="text-end fw-semibold"><?= number_format($subtotal, 2, ',', '.') ?> €</td>
                                            <td class="text-end">
                                                <button type="button"
                                                        class="btn btn-outline-danger btn-sm btn-remove-cart-line"
                                                        data-linea-id="<?= (int) $linea['linea_id'] ?>">
                                                    <i class="bi bi-trash me-1"></i>Quitar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-2 border-top">
                            <div class="fs-5 fw-bold">
                                Total: <span class="sv-price"><?= number_format($carritoTotal, 2, ',', '.') ?> €</span>
                            </div>
                            <button type="button"
                                    id="btnConfirmarCompra"
                                    class="btn btn-emerald fw-semibold px-4"
                                    <?= !empty($productosNoDisponibles) ? 'disabled' : '' ?>>
                                <i class="bi bi-check2-circle me-1"></i>Confirmar compra
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
