<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$usuarioCheck = usuarioActual();
if ($usuarioCheck !== null && $usuarioCheck['rol'] === 'admin') {
    header('Location: admin.php');
    exit;
}

$paginaActiva = 'contacto';
$tituloPagina = 'SegundaVida — Contacto';
require_once __DIR__ . '/includes/navbar.php';

$nombrePrefill = $usuario['nombre'] ?? '';
$emailPrefill = $usuario['email'] ?? '';
?>

<main class="flex-grow-1 py-5">
    <div class="container">
        <div class="row justify-content-center g-4">
            <div class="col-lg-5">
                <span class="badge bg-emerald-soft px-3 py-2 rounded-pill fw-semibold mb-3">
                    <i class="bi bi-headset me-1"></i>Atención al cliente
                </span>
                <h1 class="h2 fw-bold mb-3">Contacta con SegundaVida</h1>
                <p class="text-muted mb-4">
                    ¿Tienes dudas sobre el estado de un portátil reacondicionado, compatibilidad de un accesorio o sobre tu pedido? Escríbenos y te ayudamos.
                </p>

                <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
                    <div class="card-body d-flex align-items-start gap-3">
                        <div class="sv-feature-icon d-inline-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <h2 class="h6 fw-bold mb-1">Taller de reacondicionado</h2>
                            <p class="small text-muted mb-0">Lunes a Viernes, 9:30 – 19:00</p>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-3">
                    <div class="card-body d-flex align-items-start gap-3">
                        <div class="sv-feature-icon d-inline-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h2 class="h6 fw-bold mb-1">Equipos con garantía</h2>
                            <p class="small text-muted mb-0">Soporte directo sobre hardware de segunda mano testado.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h2 class="h4 fw-bold mb-3">Envíanos un mensaje</h2>

                    <div id="contactoAlert" class="alert d-none" role="alert"></div>

                    <form id="formContacto">
                        <div class="mb-3">
                            <label for="contactoNombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="contactoNombre" name="nombre" required minlength="2" maxlength="100" value="<?= e($nombrePrefill) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="contactoEmail" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="contactoEmail" name="email" required value="<?= e($emailPrefill) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="contactoAsunto" class="form-label">Asunto</label>
                            <input type="text" class="form-control" id="contactoAsunto" name="asunto" required minlength="3" maxlength="150" placeholder="Ej. Consulta sobre ThinkPad T480">
                        </div>
                        <div class="mb-3">
                            <label for="contactoMensaje" class="form-label">Mensaje</label>
                            <textarea class="form-control" id="contactoMensaje" name="mensaje" rows="4" required minlength="10" placeholder="Cuéntanos en qué podemos ayudarte..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-emerald w-100 fw-semibold">
                            <i class="bi bi-send me-2"></i>Enviar consulta
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
