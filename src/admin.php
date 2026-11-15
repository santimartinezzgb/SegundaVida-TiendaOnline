<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/banner.php';

$usuarioCheck = usuarioActual();
if ($usuarioCheck === null || $usuarioCheck['rol'] !== 'admin') {
    header('Location: tienda.php');
    exit;
}

$paginaActiva = 'admin';
$tituloPagina = 'Panel de Administración — SegundaVida';
require_once __DIR__ . '/includes/navbar.php';

$pdo = getDB();
$pdo->exec('ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS reembolso_solicitado BOOLEAN NOT NULL DEFAULT FALSE');
$banner = obtenerBannerConfig($pdo);

$productos = $pdo->query(
    'SELECT id, nombre, descripcion, precio, stock, imagen
     FROM productos
     WHERE activo = TRUE
     ORDER BY id ASC'
)->fetchAll();

$todosPedidos = $pdo->query(
    "SELECT p.id, p.estado, p.total, p.created_at, p.reembolso_solicitado, u.nombre AS comprador_nombre, u.email AS comprador_email
     FROM pedidos p
     INNER JOIN usuarios u ON u.id = p.usuario_id
     WHERE p.estado <> 'carrito'
     ORDER BY (CASE WHEN p.estado = 'pendiente' THEN 0 ELSE 1 END), p.created_at DESC, p.id DESC"
)->fetchAll();

function obtenerLineasPedidoAdmin(PDO $pdo, int $pedidoId): array
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

function badgeEstadoPedidoAdmin(string $estado): string
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
                <h1 class="h2 fw-bold mb-1">
                    <i class="bi bi-shield-lock me-2 text-emerald"></i>Panel de Administración
                </h1>
                <p class="text-muted mb-0">Personaliza promociones de portada, gestiona el inventario y controla los pedidos.</p>
            </div>
            <a href="tienda.php" class="btn btn-outline-dark fw-semibold">
                <i class="bi bi-eye me-1"></i>Ver catálogo público
            </a>
        </div>

        <div id="tiendaAlert" class="alert d-none" role="alert"></div>

        <!-- 1. Pedidos de clientes -->
        <section class="mb-5" aria-label="Gestión de compras de clientes">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <h2 class="h5 fw-bold mb-0">Gestión de compras de clientes</h2>
                    <div class="sv-admin-order-tabs" role="tablist" aria-label="Filtrar pedidos por estado">
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
                    <?php if (empty($todosPedidos)): ?>
                        <p class="text-muted mb-0">No hay pedidos registrados todavía.</p>
                    <?php else: ?>
                        <p id="adminOrdersEmpty" class="text-muted mb-0 d-none">No hay pedidos en este estado.</p>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($todosPedidos as $ped): ?>
                                <?php $lineas = obtenerLineasPedidoAdmin($pdo, (int) $ped['id']); ?>
                                <div class="border rounded-3 p-3 sv-admin-order-card" data-estado="<?= e($ped['estado']) ?>">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div>
                                            <strong>Pedido #<?= (int) $ped['id'] ?></strong>
                                            <span class="ms-2 text-muted small">
                                                <?= e($ped['comprador_nombre']) ?> (<?= e($ped['comprador_email']) ?>)
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ($ped['estado'] === 'aceptado'): ?>
                                                <?php if (filter_var($ped['reembolso_solicitado'], FILTER_VALIDATE_BOOLEAN)): ?>
                                                    <button type="button"
                                                            class="btn btn-outline-danger btn-sm btn-refund-order"
                                                            data-pedido-id="<?= (int) $ped['id'] ?>">
                                                        Reembolsar
                                                    </button>
                                                <?php endif; ?>
                                                <span class="fw-bold"><?= number_format((float) $ped['total'], 2, ',', '.') ?> €</span>
                                            <?php else: ?>
                                                <span class="badge <?= badgeEstadoPedidoAdmin($ped['estado']) ?> text-uppercase">
                                                    <?= e($ped['estado']) ?>
                                                </span>
                                                <span class="fw-bold"><?= number_format((float) $ped['total'], 2, ',', '.') ?> €</span>
                                                <?php if ($ped['estado'] === 'pendiente'): ?>
                                                    <button type="button"
                                                            class="btn btn-success btn-sm btn-accept-order"
                                                            data-pedido-id="<?= (int) $ped['id'] ?>">
                                                        Aceptar
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-outline-danger btn-sm btn-cancel-order"
                                                            data-pedido-id="<?= (int) $ped['id'] ?>">
                                                        Cancelar y reponer
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <ul class="list-group list-group-flush small">
                                        <?php foreach ($lineas as $l): ?>
                                            <li class="list-group-item px-0 d-flex justify-content-between">
                                                <span class="<?= $l['estado_linea'] === 'cancelada' ? 'text-decoration-line-through text-muted' : '' ?>">
                                                    <?= e($l['nombre']) ?> × <?= (int) $l['cantidad'] ?>
                                                </span>
                                                <span><?= number_format((float) $l['precio_unitario'], 2, ',', '.') ?> €/ud.</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 2. CRUD Productos -->
        <section id="panel-admin" class="mb-5">
            <div class="row g-4">
                <!-- CRUD Productos -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h2 class="h5 fw-bold mb-0" id="adminFormTitulo">Nuevo producto</h2>
                            <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btnCancelarEdicion">
                                Limpiar
                            </button>
                        </div>
                        <div class="card-body">
                            <form id="formAdminProducto" enctype="multipart/form-data">
                                <input type="hidden" id="adminProdId" name="id" value="">
                                <div class="mb-3">
                                    <label for="adminProdNombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="adminProdNombre" name="nombre" required minlength="2" maxlength="150">
                                </div>
                                <div class="mb-3">
                                    <label for="adminProdDesc" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="adminProdDesc" name="descripcion" rows="3" required minlength="5"></textarea>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label for="adminProdPrecio" class="form-label">Precio (€)</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="adminProdPrecio" name="precio" required>
                                    </div>
                                    <div class="col-6">
                                        <label for="adminProdStock" class="form-label">Stock</label>
                                        <input type="number" min="0" class="form-control" id="adminProdStock" name="stock" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="adminProdImagen" class="form-label">Imagen (opcional)</label>
                                    <input type="file" class="form-control" id="adminProdImagen" name="imagen" accept=".jpg,.jpeg,.png,.webp">
                                </div>
                                <button type="submit" class="btn btn-emerald w-100 fw-semibold">
                                    Guardar producto
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tabla Productos -->
                <div class="col-lg-7">
                    <div class="sv-admin-inventory-wrap">
                        <div class="card border-0 shadow-sm rounded-4 sv-admin-inventory-card">
                            <div class="card-header bg-white border-bottom py-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                                <h2 class="h5 fw-bold mb-0 text-nowrap">Inventario</h2>
                                <input type="search"
                                       id="adminInventorySearch"
                                       class="form-control form-control-sm sv-admin-inventory-search"
                                       placeholder="Buscar por nombre..."
                                       aria-label="Buscar producto en inventario">
                                <select id="adminInventorySort"
                                        class="form-select form-select-sm sv-admin-inventory-sort"
                                        aria-label="Ordenar inventario">
                                    <option value="">Ordenar...</option>
                                    <option value="nombre_asc">A - Z</option>
                                    <option value="precio_asc">Precio: menor a mayor</option>
                                    <option value="precio_desc">Precio: mayor a menor</option>
                                    <option value="stock_asc">Stock: menor a mayor</option>
                                    <option value="stock_desc">Stock: mayor a menor</option>
                                </select>
                            </div>
                            <div class="card-body table-responsive sv-admin-inventory-body">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th class="text-end">Precio</th>
                                            <th class="text-center">Stock</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos as $prod): ?>
                                            <tr class="sv-admin-inventory-row"
                                                data-id="<?= (int) $prod['id'] ?>"
                                                data-nombre="<?= e(mb_strtolower($prod['nombre'])) ?>"
                                                data-precio="<?= (float) $prod['precio'] ?>"
                                                data-stock="<?= (int) $prod['stock'] ?>">
                                                <td class="fw-medium"><?= e($prod['nombre']) ?></td>
                                                <td class="text-end"><?= number_format((float) $prod['precio'], 2, ',', '.') ?> €</td>
                                                <td class="text-center">
                                                    <span class="fw-bold <?= (int) $prod['stock'] > 3 ? 'text-dark' : 'text-danger' ?>">
                                                        <?= (int) $prod['stock'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <button type="button"
                                                            class="btn btn-link border-0 bg-transparent p-1 text-secondary btn-sm btn-edit-prod"
                                                            data-id="<?= (int) $prod['id'] ?>"
                                                            data-nombre="<?= e($prod['nombre']) ?>"
                                                            data-descripcion="<?= e($prod['descripcion']) ?>"
                                                            data-precio="<?= e((string) $prod['precio']) ?>"
                                                            data-stock="<?= (int) $prod['stock'] ?>"
                                                            aria-label="Editar producto"
                                                            title="Editar">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-link border-0 bg-transparent p-1 text-danger btn-sm btn-delete-prod"
                                                            data-id="<?= (int) $prod['id'] ?>"
                                                            aria-label="Eliminar producto"
                                                            title="Eliminar">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr id="adminInventoryEmpty" class="d-none">
                                            <td colspan="4" class="text-center text-muted py-3">No se encontraron productos con ese nombre.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Editor de Banner / Promoción de Portada -->
        <section class="mb-4" aria-label="Personalizar banner de portada">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="h5 fw-bold mb-0">
                        <i class="bi bi-megaphone me-2 text-emerald"></i>Banner promocional (Inicio)
                    </h2>
                    <a href="index.php" class="small text-decoration-none fw-semibold">Ver en Inicio <i class="bi bi-arrow-up-right"></i></a>
                </div>
                <div class="card-body">
                    <form id="formAdminBanner">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="bannerEtiqueta" class="form-label">Etiqueta superior</label>
                                <input type="text" class="form-control" id="bannerEtiqueta" name="etiqueta" maxlength="120" value="<?= e($banner['etiqueta']) ?>" placeholder="Ej. BLACK FRIDAY · STOCK LIMITADO">
                            </div>
                            <div class="col-md-6">
                                <label for="bannerTitulo" class="form-label">Título principal</label>
                                <input type="text" class="form-control" id="bannerTitulo" name="titulo" required minlength="3" maxlength="200" value="<?= e($banner['titulo']) ?>">
                            </div>
                            <div class="col-12">
                                <label for="bannerSubtitulo" class="form-label">Descripción / Subtítulo</label>
                                <textarea class="form-control" id="bannerSubtitulo" name="subtitulo" rows="2"><?= e($banner['subtitulo']) ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="bannerTextoBoton" class="form-label">Texto del botón</label>
                                <input type="text" class="form-control" id="bannerTextoBoton" name="texto_boton" maxlength="80" value="<?= e($banner['texto_boton']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="bannerUrlBoton" class="form-label">Enlace del botón</label>
                                <?php
                                $opcionesUrlBanner = [
                                    'tienda.php' => 'Tienda',
                                    'contacto.php' => 'Contacto',
                                ];
                                ?>
                                <select class="form-select" id="bannerUrlBoton" name="url_boton">
                                    <?php foreach ($opcionesUrlBanner as $urlVal => $urlLabel): ?>
                                        <option value="<?= e($urlVal) ?>" <?= $banner['url_boton'] === $urlVal ? 'selected' : '' ?>>
                                            <?= e($urlLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="bannerActivo" name="activo" value="1" <?= filter_var($banner['activo'], FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="bannerActivo">Mostrar</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 d-flex align-items-end justify-content-md-end">
                                <button type="submit" class="btn btn-emerald fw-semibold px-4">
                                    <i class="bi bi-save me-1"></i>Guardar banner
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
