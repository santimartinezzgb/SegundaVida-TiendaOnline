<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

$paginaActiva = 'tienda';
$tituloPagina = 'SegundaVida — Tienda';
require_once __DIR__ . '/includes/navbar.php';

$pdo = getDB();

$productoDetalleId = isset($_GET['producto']) ? (int) $_GET['producto'] : 0;
$productoDetalle = null;
$opinionesProducto = [];

if ($productoDetalleId > 0) {
    $stmtDet = $pdo->prepare(
        'SELECT id, nombre, descripcion, precio, stock, imagen
         FROM productos
         WHERE id = :id AND activo = TRUE
         LIMIT 1'
    );
    $stmtDet->execute([':id' => $productoDetalleId]);
    $productoDetalle = $stmtDet->fetch() ?: null;

    if ($productoDetalle !== null) {
        $pdo->exec(
            'ALTER TABLE producto_opiniones
             ADD COLUMN IF NOT EXISTS respuesta_admin TEXT,
             ADD COLUMN IF NOT EXISTS respuesta_fecha TIMESTAMP'
        );
        $stmtOp = $pdo->prepare(
            'SELECT o.id, o.puntuacion, o.comentario, o.created_at, o.respuesta_admin, o.respuesta_fecha,
                    u.nombre AS autor, u.avatar
             FROM producto_opiniones o
             INNER JOIN usuarios u ON u.id = o.usuario_id
             WHERE o.producto_id = :pid
             ORDER BY o.created_at DESC, o.id DESC'
        );
        $stmtOp->execute([':pid' => $productoDetalleId]);
        $opinionesProducto = $stmtOp->fetchAll();
    }
}

// 1. Catálogo de productos activos (con ordenación segura mediante lista blanca)
$orden = isset($_GET['orden']) && is_string($_GET['orden']) ? $_GET['orden'] : '';
$orderBySql = match ($orden) {
    'precio_asc'  => 'precio ASC, id ASC',
    'precio_desc' => 'precio DESC, id ASC',
    'nombre_asc'  => 'nombre ASC, id ASC',
    'nombre_desc' => 'nombre DESC, id ASC',
    default       => 'id ASC',
};

$productos = $pdo->query(
    "SELECT id, nombre, descripcion, precio, stock, imagen
     FROM productos
     WHERE activo = TRUE
     ORDER BY {$orderBySql}"
)->fetchAll();

// Imágenes adicionales para galería y flechas en tarjetas
$imagenesPorProducto = [];
$stmtImgsAll = $pdo->query(
    'SELECT producto_id, ruta FROM producto_imagenes ORDER BY orden ASC, id ASC'
);
foreach ($stmtImgsAll->fetchAll() as $imgRow) {
    $imagenesPorProducto[(int) $imgRow['producto_id']][] = $imgRow['ruta'];
}

$productosComprados = [];

if ($usuario !== null && $usuario['rol'] === 'comprador') {
    // 2. Productos ya comprados por el usuario (pedidos pendientes o aceptados)
    $stmtComp = $pdo->prepare(
        "SELECT DISTINCT l.producto_id
         FROM pedido_lineas l
         INNER JOIN pedidos p ON p.id = l.pedido_id
         WHERE p.usuario_id = :uid
           AND p.estado IN ('pendiente', 'aceptado')
           AND l.estado_linea = 'activa'"
    );
    $stmtComp->execute([':uid' => $usuario['id']]);
    foreach ($stmtComp->fetchAll() as $row) {
        $productosComprados[(int) $row['producto_id']] = true;
    }
}
?>

<main class="flex-grow-1 py-4">
    <div class="container">
        <div id="tiendaAlert" class="alert d-none" role="alert"></div>

        <?php if ($productoDetalleId > 0): ?>
            <?php if ($productoDetalle === null): ?>
                <div class="card border-0 p-4 text-center">
                    <h1 class="h4 fw-bold mb-2">Producto no encontrado</h1>
                    <p class="text-muted mb-3">El artículo solicitado no existe o ya no está disponible en el catálogo.</p>
                    <div>
                        <a href="tienda.php" class="btn btn-emerald">Volver al catálogo</a>
                    </div>
                </div>
            <?php else: ?>
                <?php
                $detId = (int) $productoDetalle['id'];
                $detStock = (int) $productoDetalle['stock'];
                $detComprado = isset($productosComprados[$detId]);
                $detImagenes = array_values(array_unique(array_merge([$productoDetalle['imagen']], $imagenesPorProducto[$detId] ?? [])));
                $totalOpiniones = count($opinionesProducto);
                $mediaPuntuacion = $totalOpiniones > 0
                    ? array_sum(array_column($opinionesProducto, 'puntuacion')) / $totalOpiniones
                    : 0;
                ?>
                <nav aria-label="Migas de pan" class="mb-3">
                    <ol class="breadcrumb small mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="tienda.php" class="text-decoration-none">Tienda</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= e($productoDetalle['nombre']) ?></li>
                    </ol>
                </nav>

                <section class="card border-0 p-3 p-lg-4 mb-5">
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-6">
                            <div class="sv-gallery-layout">
                                <?php if (count($detImagenes) > 1): ?>
                                    <div class="sv-gallery-thumbs" aria-label="Galería de imágenes">
                                        <?php foreach ($detImagenes as $idx => $rutaImg): ?>
                                            <button type="button"
                                                    class="sv-gallery-thumb <?= $idx === 0 ? 'is-active' : '' ?>"
                                                    data-img-src="<?= e($rutaImg) ?>"
                                                    aria-label="Ver imagen <?= $idx + 1 ?>">
                                                <img src="<?= e($rutaImg) ?>" alt="<?= e($productoDetalle['nombre']) ?> vista <?= $idx + 1 ?>">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="sv-gallery-main position-relative">
                                    <img id="detalleImagenPrincipal" src="<?= e($detImagenes[0]) ?>" alt="<?= e($productoDetalle['nombre']) ?>" class="sv-gallery-main-img">
                                    <?php if ($detComprado): ?>
                                        <span class="badge sv-badge-comprado position-absolute top-0 end-0 m-2 px-3 py-2">
                                            <i class="bi bi-check-circle-fill me-1"></i>Ya comprado
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 d-flex flex-column gap-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <h1 class="h2 fw-bold mb-0"><?= e($productoDetalle['nombre']) ?></h1>
                                <?php if ($detStock <= 0): ?>
                                    <span class="badge bg-danger">Sin stock</span>
                                <?php elseif ($detStock <= 2): ?>
                                    <span class="badge sv-badge-low-stock">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Últimas unidades (<?= $detStock ?>)
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if ($totalOpiniones > 0): ?>
                                <div class="small text-muted">
                                    <span class="fw-bold text-warning">★ <?= number_format($mediaPuntuacion, 1, ',', '.') ?></span>
                                    <span>(<?= $totalOpiniones ?> <?= $totalOpiniones === 1 ? 'opinión' : 'opiniones' ?>)</span>
                                </div>
                            <?php endif; ?>

                            <div class="sv-price fw-bold display-6">
                                <?= number_format((float) $productoDetalle['precio'], 2, ',', '.') ?> €
                            </div>

                            <div class="border-top border-bottom py-3">
                                <h2 class="h6 fw-bold mb-2">Descripción y especificaciones</h2>
                                <p class="mb-0 text-muted"><?= nl2br(e($productoDetalle['descripcion'])) ?></p>
                            </div>

                            <?php if ($usuario === null): ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-emerald px-4" data-bs-toggle="modal" data-bs-target="#authModal" <?= $detStock <= 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-cart-plus me-2"></i>Añadir al carrito
                                    </button>
                                    <button type="button" class="btn btn-outline-dark px-4" data-bs-toggle="modal" data-bs-target="#authModal" <?= $detStock <= 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-lightning-charge me-1"></i>Comprar ahora
                                    </button>
                                </div>
                            <?php elseif ($usuario['rol'] === 'comprador'): ?>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <label for="detalleCantidad" class="small fw-semibold mb-0">Cantidad:</label>
                                        <input type="number"
                                               id="detalleCantidad"
                                               class="form-control form-control-sm text-center sv-qty-input"
                                               style="width: 4.5rem;"
                                               value="1"
                                               min="1"
                                               max="<?= max(1, $detStock) ?>"
                                               <?= $detStock <= 0 ? 'disabled' : '' ?>>
                                    </div>
                                    <button type="button"
                                            class="btn btn-outline-dark px-4 btn-add-cart"
                                            data-producto-id="<?= $detId ?>"
                                            <?= $detStock <= 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-cart-plus me-2"></i>Añadir al carrito
                                    </button>
                                    <button type="button"
                                            class="btn btn-emerald px-4 btn-buy-now"
                                            data-producto-id="<?= $detId ?>"
                                            <?= $detStock <= 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-lightning-charge me-1"></i>Comprar ahora
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary mb-0 small">
                                    Editar este producto desde el <a href="admin.php">panel de administración</a>.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Opiniones de clientes -->
                <?php $esAdmin = ($usuario !== null && $usuario['rol'] === 'admin'); ?>
                <section class="mb-5">
                    <div class="row g-4">
                        <div class="<?= $esAdmin ? 'col-12' : 'col-lg-7' ?>">
                            <div class="card border-0 p-4 h-100">
                                <h2 class="h5 fw-bold mb-3">
                                    Opiniones de clientes (<?= $totalOpiniones ?>)
                                </h2>
                                <?php if (empty($opinionesProducto)): ?>
                                    <p class="text-muted mb-0">Todavía no hay opiniones para este producto.<?= $esAdmin ? '' : ' ¡Sé el primero en opinar!' ?></p>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-3">
                                        <?php foreach ($opinionesProducto as $op): ?>
                                            <div class="border-bottom pb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="<?= e($op['avatar']) ?>" alt="<?= e($op['autor']) ?>" class="sv-avatar-nav rounded-circle object-fit-cover">
                                                        <strong class="small"><?= e($op['autor']) ?></strong>
                                                    </div>
                                                    <span class="small text-muted">
                                                        <?= e(date('d/m/Y', strtotime($op['created_at']))) ?>
                                                    </span>
                                                </div>
                                                <div class="text-warning small mb-1">
                                                    <?= str_repeat('★', (int) $op['puntuacion']) ?><?= str_repeat('☆', 5 - (int) $op['puntuacion']) ?>
                                                </div>
                                                <p class="small mb-0"><?= nl2br(e($op['comentario'])) ?></p>

                                                <?php if (!empty($op['respuesta_admin'])): ?>
                                                    <div class="mt-2 ms-3 ps-3 border-start border-2 border-danger">
                                                        <div class="small fw-bold mb-1">
                                                            <i class="bi bi-shield-check me-1 text-emerald"></i>Respuesta de SegundaVida
                                                            <?php if (!empty($op['respuesta_fecha'])): ?>
                                                                <span class="fw-normal text-muted ms-1">
                                                                    · <?= e(date('d/m/Y', strtotime($op['respuesta_fecha']))) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="small text-muted mb-0"><?= nl2br(e($op['respuesta_admin'])) ?></p>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($esAdmin): ?>
                                                    <form class="sv-admin-reply-form mt-3">
                                                        <input type="hidden" name="action" value="reply_admin">
                                                        <input type="hidden" name="opinion_id" value="<?= (int) $op['id'] ?>">
                                                        <div class="d-flex flex-column flex-sm-row gap-2">
                                                            <textarea name="respuesta"
                                                                      class="form-control form-control-sm"
                                                                      rows="2"
                                                                      required
                                                                      minlength="2"
                                                                      maxlength="1000"
                                                                      placeholder="Responder a esta opinión..."></textarea>
                                                            <button type="submit" class="btn btn-emerald btn-sm text-nowrap align-self-sm-end px-3">
                                                                <?= !empty($op['respuesta_admin']) ? 'Actualizar respuesta' : 'Responder' ?>
                                                            </button>
                                                        </div>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!$esAdmin): ?>
                        <div class="col-lg-5">
                            <div class="card border-0 p-4 h-100">
                                <h2 class="h5 fw-bold mb-3">Deja tu opinión</h2>
                                <?php if ($usuario === null): ?>
                                    <p class="small text-muted mb-3">Inicia sesión para valorar este producto y compartir tu experiencia.</p>
                                    <button type="button" class="btn btn-emerald btn-sm" data-bs-toggle="modal" data-bs-target="#authModal">
                                        Iniciar sesión para opinar
                                    </button>
                                <?php else: ?>
                                    <form id="formOpinion">
                                        <input type="hidden" name="producto_id" value="<?= $detId ?>">
                                        <div class="mb-3">
                                            <label for="opinionPuntuacion" class="form-label">Valoración</label>
                                            <select class="form-select" id="opinionPuntuacion" name="puntuacion" required>
                                                <option value="5">★★★★★ (5/5 - Excelente)</option>
                                                <option value="4">★★★★☆ (4/5 - Muy bueno)</option>
                                                <option value="3">★★★☆☆ (3/5 - Correcto)</option>
                                                <option value="2">★★☆☆☆ (2/5 - Mejorable)</option>
                                                <option value="1">★☆☆☆☆ (1/5 - Malo)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="opinionComentario" class="form-label">Tu comentario</label>
                                            <textarea class="form-control" id="opinionComentario" name="comentario" rows="3" required minlength="5" maxlength="1000" placeholder="Cuéntanos qué tal el estado y rendimiento del equipo..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-emerald w-100">
                                            Publicar opinión
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php else: ?>
        <!-- Cabecera de Tienda -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h1 class="h2 fw-bold mb-1">Catálogo SegundaVida</h1>
                <p class="text-muted mb-0">Portátiles y accesorios informáticos de segunda mano revisados.</p>
            </div>

            <form method="get" action="tienda.php" class="d-flex align-items-center gap-2">
                <label for="ordenCatalogo" class="small text-muted text-nowrap mb-0">
                    <i class="bi bi-funnel me-1"></i>Ordenar:
                </label>
                <select id="ordenCatalogo"
                        name="orden"
                        class="form-select form-select-sm w-auto"
                        aria-label="Ordenar productos"
                        onchange="this.form.submit()">
                    <option value="" <?= $orden === '' ? 'selected' : '' ?>>Por defecto</option>
                    <option value="precio_asc" <?= $orden === 'precio_asc' ? 'selected' : '' ?>>Precio: menor a mayor</option>
                    <option value="precio_desc" <?= $orden === 'precio_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
                    <option value="nombre_asc" <?= $orden === 'nombre_asc' ? 'selected' : '' ?>>Alfabético: A - Z</option>
                    <option value="nombre_desc" <?= $orden === 'nombre_desc' ? 'selected' : '' ?>>Alfabético: Z - A</option>
                </select>
            </form>
        </div>

        <!-- Grid de Productos -->
        <section aria-label="Catálogo de productos" class="mb-5">
            <div class="sv-catalog-grid">
                <?php foreach ($productos as $prod): ?>
                    <?php
                    $prodId = (int) $prod['id'];
                    $stock = (int) $prod['stock'];
                    $yaComprado = isset($productosComprados[$prodId]);
                    if ($stock <= 0) {
                        continue;
                    }
                    $imgs = array_values(array_unique(array_merge([$prod['imagen']], $imagenesPorProducto[$prodId] ?? [])));
                    ?>
                    <article class="sv-product-card h-100 d-flex flex-column <?= $yaComprado ? 'sv-card-comprado' : '' ?>">
                        <a href="tienda.php?producto=<?= $prodId ?>"
                           class="position-relative sv-product-media d-block text-decoration-none"
                           data-images="<?= e(json_encode($imgs, JSON_UNESCAPED_SLASHES)) ?>">
                            <img src="<?= e($imgs[0]) ?>" alt="<?= e($prod['nombre']) ?>" class="sv-product-img">
                            <?php if (count($imgs) > 1): ?>
                                <button type="button" class="sv-card-arrow sv-card-arrow--prev" aria-label="Imagen anterior">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <button type="button" class="sv-card-arrow sv-card-arrow--next" aria-label="Imagen siguiente">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            <?php endif; ?>
                            <?php if ($yaComprado): ?>
                                <span class="badge sv-badge-comprado position-absolute top-0 end-0 m-2 px-3 py-2 shadow-sm">
                                    <i class="bi bi-check-circle-fill me-1"></i>Ya comprado
                                </span>
                            <?php endif; ?>
                        </a>

                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-grow-1 mb-3">
                                <h2 class="h6 fw-bold mb-0">
                                    <a href="tienda.php?producto=<?= $prodId ?>" class="text-decoration-none text-reset">
                                        <?= e($prod['nombre']) ?>
                                    </a>
                                </h2>
                                <?php if ($stock <= 2): ?>
                                    <span class="badge sv-badge-low-stock flex-shrink-0">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Últimas unidades
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top gap-2">
                                <span class="sv-price fw-bold fs-5">
                                    <?= number_format((float) $prod['precio'], 2, ',', '.') ?> €
                                </span>

                                <?php if ($usuario === null): ?>
                                    <button type="button"
                                            class="btn btn-emerald btn-sm px-3"
                                            aria-label="Añadir al carrito"
                                            title="<?= $stock <= 0 ? 'Agotado' : 'Inicia sesión para añadir al carrito' ?>"
                                            <?= $stock <= 0 ? 'disabled' : 'data-bs-toggle="modal" data-bs-target="#authModal"' ?>>
                                        <i class="bi bi-cart-plus fs-6"></i>
                                    </button>
                                <?php elseif ($usuario['rol'] === 'comprador'): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="number"
                                               class="form-control form-control-sm text-center sv-qty-input"
                                               style="width: 4.2rem;"
                                               value="1"
                                               min="1"
                                               max="<?= max(1, $stock) ?>"
                                               <?= $stock <= 0 ? 'disabled' : '' ?>
                                               aria-label="Cantidad">
                                        <button type="button"
                                                class="btn btn-emerald btn-sm px-3 btn-add-cart"
                                                data-producto-id="<?= $prodId ?>"
                                                aria-label="Añadir al carrito"
                                                title="<?= $stock <= 0 ? 'Agotado' : 'Añadir al carrito' ?>"
                                                <?= $stock <= 0 ? 'disabled' : '' ?>>
                                            <i class="bi bi-cart-plus fs-6"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
