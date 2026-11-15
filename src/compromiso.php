<?php

declare(strict_types=1);

$paginaActiva = 'compromiso';
$tituloPagina = 'Nuestro Compromiso — SegundaVida';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-grow-1">
    <!-- Cabecera de Nuestro Compromiso -->
    <section class="sv-editorial-strip py-5">
        <div class="container">
            <div class="row align-items-center gy-4">
                <div class="col-lg-8">
                    <span class="badge bg-dark text-uppercase mb-3 px-3 py-2">Calidad verificada</span>
                    <h1 class="display-6 fw-bold mb-3">
                        Nuestro compromiso: tecnología reacondicionada con garantías reales.
                    </h1>
                    <p class="lead text-muted mb-0">
                        En SegundaVida creemos en alargar la vida útil de cada portátil, monitor y accesorio
                        informático sin renunciar al rendimiento, la transparencia ni tu tranquilidad.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="tienda.php" class="btn btn-dark px-4 py-2 fw-bold">
                        Explorar catálogo <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Desglose de los 4 pilares -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6">
                    <article class="sv-commitment-card h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="sv-shield-badge" aria-hidden="true">
                                <i class="bi bi-award"></i>
                            </div>
                            <h2 class="h5 fw-bold mb-0">2 años de garantía</h2>
                        </div>
                        <p class="text-muted mb-0">
                            Todos nuestros equipos reacondicionados incluyen 2 años de cobertura frente a fallos
                            de hardware. Si surge cualquier incidencia técnica, nuestro equipo la revisa, repara
                            o sustituye sin coste adicional.
                        </p>
                    </article>
                </div>

                <div class="col-md-6">
                    <article class="sv-commitment-card h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="sv-shield-badge" aria-hidden="true">
                                <i class="bi bi-list-check"></i>
                            </div>
                            <h2 class="h5 fw-bold mb-0">Hasta 100 puntos de control</h2>
                        </div>
                        <p class="text-muted mb-0">
                            Cada dispositivo supera una batería de pruebas técnicas antes de salir a la venta:
                            estrés de CPU y RAM, lectura SMART de unidades SSD NVMe, salud real de batería,
                            puertos, pantalla, teclado y renovación de pasta térmica.
                        </p>
                    </article>
                </div>

                <div class="col-md-6">
                    <article class="sv-commitment-card h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="sv-shield-badge" aria-hidden="true">
                                <i class="bi bi-arrow-left-right"></i>
                            </div>
                            <h2 class="h5 fw-bold mb-0">30 días de prueba</h2>
                        </div>
                        <p class="text-muted mb-0">
                            Queremos que trabajes y estudies con total seguridad. Dispones de 30 días desde la
                            recepción para probar tu equipo en tu día a día y solicitar la devolución si no se
                            adapta a lo que necesitas.
                        </p>
                    </article>
                </div>

                <div class="col-md-6">
                    <article class="sv-commitment-card h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="sv-shield-badge" aria-hidden="true">
                                <i class="bi bi-star"></i>
                            </div>
                            <h2 class="h5 fw-bold mb-0">Reacondicionado por expertos</h2>
                        </div>
                        <p class="text-muted mb-0">
                            Nuestros técnicos especializados limpian, verifican y restauran cada equipo siguiendo
                            estándares profesionales, reduciendo residuos electrónicos y ofreciendo una alternativa
                            sostenible frente a la compra de hardware nuevo.
                        </p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- Cierre / CTA -->
    <section class="sv-editorial-strip py-5">
        <div class="container">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h4 fw-bold mb-1">¿Tienes dudas sobre algún equipo o su estado?</h2>
                    <p class="text-muted mb-0">
                        Consulta nuestro inventario actualizado en tiempo real o escríbenos directamente.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="tienda.php" class="btn btn-dark fw-bold px-3 py-2">Ir a la tienda</a>
                    <a href="contacto.php" class="btn btn-outline-dark fw-bold px-3 py-2">Contactar</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
