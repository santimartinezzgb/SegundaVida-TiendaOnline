<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$usuario = usuarioActual();
$paginaActiva = $paginaActiva ?? '';
$tituloPagina = $tituloPagina ?? 'SegundaVida — Informática de Segunda Mano';

$navCarritoCantidad = 0;
if ($usuario !== null && $usuario['rol'] === 'comprador') {
    $stmtNavCart = getDB()->prepare(
        "SELECT COALESCE(SUM(l.cantidad), 0)
         FROM pedidos p
         JOIN pedido_lineas l ON l.pedido_id = p.id
         WHERE p.usuario_id = :uid AND p.estado = 'carrito' AND l.estado_linea = 'activa'"
    );
    $stmtNavCart->execute([':uid' => (int) $usuario['id']]);
    $navCarritoCantidad = (int) $stmtNavCart->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="thinkpad-classic" data-layout-header="classic" data-layout-hero="bento-asimetrico" data-layout-strip="rows-list" data-layout-catalog="grid-3" data-layout-footer="split">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#181a1d">
    <title><?= e($tituloPagina) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&family=Newsreader:opsz,wght@6..72,600;6..72,700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<div class="sv-navbar-backdrop" aria-hidden="true"></div>

<nav class="navbar navbar-expand-lg navbar-dark sv-navbar sticky-top shadow-sm">
    <div class="container sv-navbar-inner">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
            <span class="sv-brand-icon d-inline-flex align-items-center justify-content-center rounded-circle">
                <i class="bi bi-recycle"></i>
            </span>
            <span>SegundaVida</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Mostrar menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse sv-navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 sv-navbar-links">
                <li class="nav-item">
                    <a class="nav-link <?= $paginaActiva === 'inicio' ? 'active fw-semibold' : '' ?>" href="index.php">
                        Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaActiva === 'tienda' ? 'active fw-semibold' : '' ?>" href="tienda.php">
                        Tienda
                    </a>
                </li>
                <?php if ($usuario !== null && $usuario['rol'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $paginaActiva === 'admin' ? 'active fw-semibold' : '' ?>" href="admin.php">
                            Panel Admin
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $paginaActiva === 'contacto' ? 'active fw-semibold' : '' ?>" href="contacto.php">
                            Contacto
                        </a>
                    </li>
                <?php endif; ?>
                <li class="sv-nav-indicator" aria-hidden="true"></li>
            </ul>

            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <?php if ($usuario === null): ?>
                    <button type="button" class="btn btn-emerald btn-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#authModal">
                        <i class="bi bi-person-circle me-1"></i>Iniciar sesión
                    </button>
                <?php else: ?>
                    <?php if ($usuario['rol'] === 'comprador'): ?>
                        <a href="carrito.php"
                           class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-1 position-relative <?= $paginaActiva === 'carrito' ? 'active' : '' ?>"
                           aria-label="Mi carrito"
                           title="Ver mi carrito">
                            <i class="bi bi-cart3"></i>
                            <?php if ($navCarritoCantidad > 0): ?>
                                <span class="badge bg-emerald-soft ms-1"><?= $navCarritoCantidad ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    <div class="dropdown">
                        <button class="btn btn-link text-decoration-none dropdown-toggle d-flex align-items-center gap-2 text-white p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= e($usuario['avatar']) ?>" alt="Avatar de <?= e($usuario['nombre']) ?>" class="sv-avatar-nav rounded-circle object-fit-cover">
                            <span class="d-none d-sm-inline fw-medium"><?= e($usuario['nombre']) ?></span>
                            <?php if ($usuario['rol'] === 'admin'): ?>
                                <span class="badge bg-warning text-dark">Admin</span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li class="dropdown-header small text-muted">
                                <?= e($usuario['email']) ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($usuario['rol'] === 'admin'): ?>
                            <?php else: ?>
                                <li>
                                    <a class="dropdown-item" href="perfil.php">
                                        <i class="bi bi-person-gear me-2"></i>Mi perfil e historial
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <button type="button" class="dropdown-item text-danger" id="btnLogout">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                                </button>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<?php if ($usuario === null): ?>
<!-- Modal de Inicio de Sesión y Registro -->
<div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header sv-navbar text-white">
                <h5 class="modal-title" id="authModalLabel">
                    <i class="bi bi-recycle me-2 text-emerald"></i>Acceso a SegundaVida
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="nav nav-fill mb-3 sv-auth-tabs" id="authTabs" role="tablist">
                    <span class="sv-auth-tab-indicator" aria-hidden="true"></span>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-login" type="button" role="tab" aria-controls="pane-login" aria-selected="true">
                            Iniciar sesión
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-register" type="button" role="tab" aria-controls="pane-register" aria-selected="false">
                            Crear cuenta
                        </button>
                    </li>
                </ul>

                <div id="authAlert" class="alert d-none" role="alert"></div>

                <div class="sv-auth-viewport" id="authViewport">
                    <div class="sv-auth-track">
                        <!-- Login -->
                        <div class="sv-auth-pane is-active" id="pane-login" role="tabpanel" aria-labelledby="tab-login">
                            <form id="formLogin">
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" id="loginEmail" name="email" required placeholder="tu@email.com">
                                </div>
                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="loginPassword" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-emerald w-100 fw-semibold">Entrar</button>
                            </form>
                        </div>

                        <!-- Registro -->
                        <div class="sv-auth-pane" id="pane-register" role="tabpanel" aria-labelledby="tab-register" inert>
                            <form id="formRegister">
                                <div class="mb-3">
                                    <label for="regNombre" class="form-label">Nombre completo</label>
                                    <input type="text" class="form-control" id="regNombre" name="nombre" required minlength="2" maxlength="100">
                                </div>
                                <div class="mb-3">
                                    <label for="regEmail" class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" id="regEmail" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="regPassword" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="regPassword" name="password" required minlength="6">
                                </div>
                                <button type="submit" class="btn btn-emerald w-100 fw-semibold">Crear cuenta</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
