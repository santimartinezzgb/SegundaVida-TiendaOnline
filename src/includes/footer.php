<footer class="sv-footer text-light py-4 mt-auto">
    <div class="container">
        <div class="row align-items-center gy-3 sv-footer-row">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 fw-bold sv-footer-brand">
                    <i class="bi bi-recycle text-emerald"></i>
                    <span>SegundaVida</span>
                </div>
                <p class="small text-secondary mb-0 mt-1 sv-footer-desc">
                    Dando una segunda vida a portátiles y accesorios informáticos reacondicionados.
                </p>
            </div>
            <div class="col-md-6 text-md-end small sv-footer-links">
                <a href="index.php" class="text-secondary text-decoration-none me-3">Inicio</a>
                <a href="tienda.php" class="text-secondary text-decoration-none me-3">Tienda</a>
                <?php if (isset($usuario) && $usuario !== null && $usuario['rol'] === 'admin'): ?>
                    <a href="admin.php" class="text-secondary text-decoration-none">Panel Admin</a>
                <?php else: ?>
                    <a href="compromiso.php" class="text-secondary text-decoration-none me-3">Compromiso</a>
                    <a href="contacto.php" class="text-secondary text-decoration-none">Contacto</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="sv-footer-bottom text-center mt-4 pt-3">
            <p class="small text-secondary mb-3">&copy; 2026 SegundaVida</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <button type="button" class="sv-store-badge" aria-label="Descargar en Google Play">
                    <i class="bi bi-google-play sv-store-badge-icon" aria-hidden="true"></i>
                    <span class="sv-store-badge-text">
                        <span class="sv-store-badge-sub">DESCARGAR EN</span>
                        <span class="sv-store-badge-title">Google Play</span>
                    </span>
                </button>
                <button type="button" class="sv-store-badge" aria-label="Consíguelo en el App Store">
                    <i class="bi bi-apple sv-store-badge-icon" aria-hidden="true"></i>
                    <span class="sv-store-badge-text">
                        <span class="sv-store-badge-sub">Consíguelo en el</span>
                        <span class="sv-store-badge-title">App Store</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
