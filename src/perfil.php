<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';

$usuarioCheck = usuarioActual();
if ($usuarioCheck === null || $usuarioCheck['rol'] !== 'comprador') {
    header('Location: tienda.php');
    exit;
}

$paginaActiva = 'perfil';
$tituloPagina = 'Mi Perfil e Historial — SegundaVida';
require_once __DIR__ . '/includes/navbar.php';

$pdo = getDB();
$pdo->exec('ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS reembolso_solicitado BOOLEAN NOT NULL DEFAULT FALSE');

$stmtHist = $pdo->prepare(
    "SELECT id, estado, total, created_at, reembolso_solicitado
     FROM pedidos
     WHERE usuario_id = :uid AND estado <> 'carrito'
     ORDER BY created_at DESC, id DESC"
);
$stmtHist->execute([':uid' => $usuario['id']]);
$historialPedidos = $stmtHist->fetchAll();

function obtenerLineasPedidoPerfil(PDO $pdo, int $pedidoId): array
{
    $stmt = $pdo->prepare(
        'SELECT l.id, l.producto_id, l.cantidad, l.precio_unitario, l.estado_linea, pr.nombre
         FROM pedido_lineas l
         INNER JOIN productos pr ON pr.id = l.producto_id
         WHERE l.pedido_id = :pid
         ORDER BY l.id ASC'
    );
    $stmt->execute([':pid' => $pedidoId]);
    return $stmt->fetchAll();
}

function badgeEstadoPedidoPerfil(string $estado): string
{
    return match ($estado) {
        'pendiente' => 'bg-warning text-dark',
        'aceptado' => 'bg-success',
        'cancelado' => 'bg-secondary',
        default => 'bg-light text-dark',
    };
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
                        <li class="breadcrumb-item active" aria-current="page">Mi perfil e historial</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold mb-1">Mi perfil e historial de compras</h1>
                <p class="text-muted mb-0">Actualiza tus datos personales y revisa el estado de tus pedidos.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="tienda.php" class="btn btn-outline-dark fw-semibold">
                    <i class="bi bi-bag me-1"></i>Ir a la tienda
                </a>
                <a href="carrito.php" class="btn btn-outline-dark fw-semibold">
                    <i class="bi bi-cart3 me-1"></i>Ver carrito
                </a>
            </div>
        </div>

        <div id="tiendaAlert" class="alert d-none" role="alert"></div>

        <section id="mi-perfil" class="mb-4">
            <div class="row g-4">
                <!-- Perfil editable -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h2 class="h5 fw-bold mb-0">
                                <i class="bi bi-person-badge me-2 text-emerald"></i>Mi Perfil
                            </h2>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <img src="<?= e($usuario['avatar']) ?>" alt="Mi avatar" class="sv-avatar-profile rounded-circle object-fit-cover mb-2">
                                <div class="small text-muted"><?= e($usuario['email']) ?></div>
                            </div>
                            <form id="formPerfil" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="perfilNombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="perfilNombre" name="nombre" required minlength="2" maxlength="100" value="<?= e($usuario['nombre']) ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="perfilAvatar" class="form-label">Cambiar foto de perfil</label>
                                    <input type="file" class="form-control" id="perfilAvatar" name="avatar" accept=".jpg,.jpeg,.png,.webp">
                                </div>
                                <div class="mb-3">
                                    <label for="perfilPassword" class="form-label">Nueva contraseña (opcional)</label>
                                    <input type="password" class="form-control" id="perfilPassword" name="password" minlength="6" placeholder="Dejar vacío para mantener">
                                </div>
                                <button type="submit" class="btn btn-emerald w-100 fw-semibold">
                                    Guardar cambios
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Historial de compras -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <h2 class="h5 fw-bold mb-0">
                                <i class="bi bi-clock-history me-2 text-emerald"></i>Mi historial de compras
                            </h2>
                            <div class="sv-admin-order-tabs" role="tablist" aria-label="Filtrar compras por estado">
                                <button type="button" class="sv-admin-order-tab is-active" data-estado="pendiente" role="tab" aria-selected="true">
                                    Pendientes
                                </button>
                                <button type="button" class="sv-admin-order-tab" data-estado="aceptado" role="tab" aria-selected="false">
                                    Aceptados
                                </button>
                                <button type="button" class="sv-admin-order-tab" data-estado="cancelado" role="tab" aria-selected="false">
                                    Cancelados
                                </button>
                                <span class="sv-tab-indicator" aria-hidden="true"></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($historialPedidos)): ?>
                                <p class="text-muted mb-0">Aún no has realizado ninguna compra.</p>
                            <?php else: ?>
                                <p id="adminOrdersEmpty" class="text-muted mb-0 d-none">No tienes pedidos en este estado.</p>
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($historialPedidos as $ped): ?>
                                        <?php $lineas = obtenerLineasPedidoPerfil($pdo, (int) $ped['id']); ?>
                                        <div class="border rounded-3 p-3 sv-admin-order-card" data-estado="<?= e($ped['estado']) ?>">
                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                                <div>
                                                    <strong>Pedido #<?= (int) $ped['id'] ?></strong>
                                                    <span class="small text-muted ms-2"><?= e(date('d/m/Y H:i', strtotime($ped['created_at']))) ?></span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if ($ped['estado'] === 'aceptado'): ?>
                                                        <?php $reembolsoSolicitado = filter_var($ped['reembolso_solicitado'], FILTER_VALIDATE_BOOLEAN); ?>
                                                        <?php if ($reembolsoSolicitado): ?>
                                                            <span class="badge bg-warning text-dark">Reembolso solicitado</span>
                                                        <?php else: ?>
                                                            <button type="button"
                                                                    class="btn btn-outline-danger btn-sm btn-request-refund"
                                                                    data-pedido-id="<?= (int) $ped['id'] ?>">
                                                                Solicitar reembolso
                                                            </button>
                                                        <?php endif; ?>
                                                        <span class="fw-bold"><?= number_format((float) $ped['total'], 2, ',', '.') ?> €</span>
                                                    <?php else: ?>
                                                        <span class="badge <?= badgeEstadoPedidoPerfil($ped['estado']) ?> text-uppercase">
                                                            <?= e($ped['estado']) ?>
                                                        </span>
                                                        <span class="fw-bold"><?= number_format((float) $ped['total'], 2, ',', '.') ?> €</span>
                                                        <?php if ($ped['estado'] === 'pendiente'): ?>
                                                            <button type="button"
                                                                    class="btn btn-outline-danger btn-sm btn-cancel-order"
                                                                    data-pedido-id="<?= (int) $ped['id'] ?>">
                                                                Cancelar pedido
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <ul class="list-group list-group-flush small">
                                                <?php foreach ($lineas as $l): ?>
                                                    <li class="list-group-item px-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                        <span class="<?= $l['estado_linea'] === 'cancelada' ? 'text-decoration-line-through text-muted' : '' ?>">
                                                            <?= e($l['nombre']) ?> × <?= (int) $l['cantidad'] ?>
                                                            (<?= number_format((float) $l['precio_unitario'], 2, ',', '.') ?> €/ud.)
                                                        </span>
                                                        <?php if ($ped['estado'] === 'pendiente' && $l['estado_linea'] === 'activa'): ?>
                                                            <button type="button"
                                                                    class="btn btn-link text-danger btn-sm p-0 text-decoration-none btn-cancel-line"
                                                                    data-linea-id="<?= (int) $l['id'] ?>">
                                                                Cancelar línea
                                                            </button>
                                                        <?php elseif ($l['estado_linea'] === 'cancelada'): ?>
                                                            <span class="badge bg-light text-muted">Cancelada</span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
