<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/banner.php';

$paginaActiva = 'inicio';
$tituloPagina = 'SegundaVida — Informática Reacondicionada y de Segunda Mano';
require_once __DIR__ . '/includes/navbar.php';

$pdo = getDB();
$banner = obtenerBannerConfig($pdo);
$bannerActivo = filter_var($banner['activo'], FILTER_VALIDATE_BOOLEAN);
$stmt = $pdo->query(
    'SELECT id, nombre, descripcion, precio, stock, imagen
     FROM productos
     WHERE activo = TRUE AND stock > 0
     ORDER BY id ASC
     LIMIT 4'
);
$destacados = $stmt->fetchAll();

// Imágenes extra para flechas en tarjetas de portada
$imagenesPorProducto = [];
if (!empty($destacados)) {
    $ids = array_map(static fn(array $p): int => (int) $p['id'], $destacados);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtImg = $pdo->prepare(
        "SELECT producto_id, ruta FROM producto_imagenes WHERE producto_id IN ($placeholders) ORDER BY orden ASC, id ASC"
    );
    $stmtImg->execute($ids);
    foreach ($stmtImg->fetchAll() as $imgRow) {
        $imagenesPorProducto[(int) $imgRow['producto_id']][] = $imgRow['ruta'];
    }
}
?>

<main class="flex-grow-1">
    <?php if ($usuario !== null && $usuario['rol'] === 'admin'): ?>
    <div class="border-bottom py-2 bg-dark text-white">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2 small">
            <span><i class="bi bi-eye me-2 text-emerald"></i>Visualización de vista cliente del Inicio</span>
            <a href="admin.php" class="text-white text-decoration-underline">Volver al Panel Admin</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($bannerActivo): ?>
    <?php
    $usarEstiloFondo = ($banner['color_fondo'] !== '#181a1d' || $banner['color_texto'] !== '#f8fafc');
    $usarEstiloAcento = ($banner['color_acento'] !== '#e62325');
    ?>
    <!-- Banner promocional personalizable desde Panel Admin -->
    <section class="sv-bf-banner sv-bf-banner--hero py-5"
             <?= $usarEstiloFondo ? 'style="background-color: ' . e($banner['color_fondo']) . '; color: ' . e($banner['color_texto']) . ';"' : '' ?>>
        <div class="container py-4 py-lg-5">
            <div class="row align-items-center gy-4">
                <div class="col-lg-8">
                    <?php if (trim((string) $banner['etiqueta']) !== ''): ?>
                        <span class="sv-bf-tag mb-3" <?= $usarEstiloAcento ? 'style="background-color: ' . e($banner['color_acento']) . '; border-color: ' . e($banner['color_acento']) . ';"' : '' ?>>
                            <?= e($banner['etiqueta']) ?>
                        </span>
                    <?php endif; ?>
                    <h1 class="display-4 fw-bold mb-3">
                        <?= e($banner['titulo']) ?>
                    </h1>
                    <?php if (trim((string) $banner['subtitulo']) !== ''): ?>
                        <p class="lead mb-0 opacity-75">
                            <?= e($banner['subtitulo']) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php if (trim((string) $banner['texto_boton']) !== ''): ?>
                    <div class="col-lg-4 d-flex flex-column align-items-lg-end justify-content-center gap-3">
                        <a href="<?= e($banner['url_boton'] ?: 'tienda.php') ?>"
                           class="btn btn-emerald btn-lg px-4 fw-semibold"
                           <?= $usarEstiloAcento ? 'style="background-color: ' . e($banner['color_acento']) . '; border-color: ' . e($banner['color_acento']) . '; color: #ffffff;"' : '' ?>>
                            <?= e($banner['texto_boton']) ?><i class="bi bi-arrow-up-right ms-1"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Productos destacados -->
    <section class="py-5">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
                <div>
                    <span class="small text-uppercase fw-semibold text-emerald">Catálogo activo</span>
                    <h2 class="h3 fw-bold mb-0">Unidades recién salidas del banco de pruebas</h2>
                </div>
                <a href="tienda.php" class="btn btn-emerald btn-sm fw-semibold px-3">
                    Abrir catálogo completo 
                </a>
            </div>

            <div class="sv-catalog-grid">
                <?php foreach ($destacados as $prod): ?>
                    <?php
                    $prodId = (int) $prod['id'];
                    $imgs = array_values(array_unique(array_merge([$prod['imagen']], $imagenesPorProducto[$prodId] ?? [])));
                    ?>
                    <article class="sv-product-card h-100 d-flex flex-column">
                        <a href="tienda.php?producto=<?= $prodId ?>"
                           class="sv-product-media d-block text-decoration-none"
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
                        </a>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <h3 class="h6 fw-bold flex-grow-1 mb-3">
                                <a href="tienda.php?producto=<?= (int) $prod['id'] ?>" class="text-decoration-none text-reset">
                                    <?= e($prod['nombre']) ?>
                                </a>
                            </h3>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="sv-price fw-bold fs-5">
                                    <?= number_format((float) $prod['precio'], 2, ',', '.') ?> €
                                </span>
                                <?php if ($usuario === null): ?>
                                    <button type="button"
                                            class="btn btn-emerald btn-sm px-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#authModal"
                                            aria-label="Añadir al carrito"
                                            title="Inicia sesión para añadir al carrito">
                                        <i class="bi bi-cart-plus fs-6"></i>
                                    </button>
                                <?php elseif ($usuario['rol'] === 'comprador'): ?>
                                    <button type="button"
                                            class="btn btn-emerald btn-sm px-3 btn-add-cart"
                                            data-producto-id="<?= (int) $prod['id'] ?>"
                                            aria-label="Añadir al carrito"
                                            title="Añadir al carrito">
                                        <i class="bi bi-cart-plus fs-6"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Bloque Newsletter -->
    <section class="sv-hero py-5">
        <div class="container py-2">
            <div class="row align-items-center justify-content-between gy-4">
                <div class="col-lg-6">
                    <span class="small text-uppercase fw-semibold text-emerald d-block mb-2">
                        Newsletter · Avisos de stock
                    </span>
                    <h2 class="h2 fw-bold mb-2">
                        Entérate antes que nadie de los nuevos lotes reacondicionados
                    </h2>
                    <p class="mb-0 opacity-75">
                        Recibe un correo semanal con las entradas de portátiles, tablets y accesorios revisados antes de que se agoten. Sin spam.
                    </p>
                </div>

                <div class="col-lg-5">
                    <form id="newsletterForm" class="d-flex flex-column flex-sm-row gap-2" novalidate>
                        <label for="newsletterEmail" class="visually-hidden">Correo electrónico</label>
                        <input
                            type="email"
                            id="newsletterEmail"
                            name="email"
                            class="form-control form-control-lg"
                            placeholder="tu@correo.com"
                            required
                        >
                        <button type="submit" class="btn btn-emerald btn-lg fw-semibold px-4 flex-shrink-0">
                            Suscribirme
                        </button>
                    </form>
                    <div id="newsletterFeedback" class="small mt-2 d-none" role="status" aria-live="polite"></div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.getElementById('newsletterForm')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = this;
            const input = document.getElementById('newsletterEmail');
            const feedback = document.getElementById('newsletterFeedback');
            const submitBtn = form.querySelector('button[type="submit"]');
            if (!input || !feedback) return;

            feedback.classList.remove('d-none', 'text-emerald', 'text-warning');
            if (!input.checkValidity()) {
                feedback.textContent = 'Introduce un correo electrónico válido.';
                feedback.classList.add('text-warning');
                input.focus();
                return;
            }

            if (submitBtn) submitBtn.disabled = true;
            try {
                const res = await fetch('api/newsletter_action.php', {
                    method: 'POST',
                    body: new FormData(form),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    feedback.textContent = data.error || 'No se pudo completar la suscripción.';
                    feedback.classList.add('text-warning');
                } else {
                    feedback.textContent = data.mensaje;
                    feedback.classList.add('text-emerald');
                    input.value = '';
                }
            } catch (err) {
                feedback.textContent = 'Error de conexión al guardar la suscripción.';
                feedback.classList.add('text-warning');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    </script>

    <!-- Banda editorial de compromiso SegundaVida -->
    <section class="sv-editorial-strip py-4">
        <div class="container">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                <h2 class="h5 fw-bold mb-0">Con el compromiso de SegundaVida: reacondicionados de calidad.</h2>
                <a href="compromiso.php" class="btn btn-dark btn-sm px-3 py-2 fw-bold text-nowrap align-self-start align-self-md-auto">
                    Nuestro compromiso <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="sv-commitment-grid">
                <div class="sv-commitment-card">
                    <div class="sv-shield-badge" aria-hidden="true">
                        <i class="bi bi-award"></i>
                    </div>
                    <h3 class="h6 fw-bold mb-0">2 años de garantía</h3>
                </div>
                <div class="sv-commitment-card">
                    <div class="sv-shield-badge" aria-hidden="true">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <h3 class="h6 fw-bold mb-0">Hasta 100 puntos de control</h3>
                </div>
                <div class="sv-commitment-card">
                    <div class="sv-shield-badge" aria-hidden="true">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <h3 class="h6 fw-bold mb-0">30 días de prueba</h3>
                </div>
                <div class="sv-commitment-card">
                    <div class="sv-shield-badge" aria-hidden="true">
                        <i class="bi bi-star"></i>
                    </div>
                    <h3 class="h6 fw-bold mb-0">Reacondicionado por expertos</h3>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
