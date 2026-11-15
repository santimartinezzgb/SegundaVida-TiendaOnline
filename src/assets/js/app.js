'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.querySelector('.sv-navbar');
    const navbarBackdrop = document.querySelector('.sv-navbar-backdrop');
    if (navbar) {
        const actualizarNavbarScroll = () => {
            const scrolled = window.scrollY > 30;
            navbar.classList.toggle('is-scrolled', scrolled);
            if (navbarBackdrop) {
                navbarBackdrop.classList.toggle('is-scrolled', scrolled);
            }
        };
        actualizarNavbarScroll();
        window.addEventListener('scroll', actualizarNavbarScroll, { passive: true });
    }

    // Indicador deslizante fluido bajo los enlaces del menú del header
    const navLinksWrap = document.querySelector('.sv-navbar-links');
    const navIndicator = document.querySelector('.sv-nav-indicator');
    const navLinks = navLinksWrap ? navLinksWrap.querySelectorAll('.nav-link') : [];
    const activeNavLink = navLinksWrap ? navLinksWrap.querySelector('.nav-link.active') : null;

    function moverIndicadorNav(targetLink) {
        if (!navIndicator || !targetLink) return;
        const pad = 8;
        const width = Math.max(0, targetLink.offsetWidth - pad * 2);
        const x = targetLink.offsetLeft + pad;
        navIndicator.style.width = `${width}px`;
        navIndicator.style.transform = `translateX(${x}px)`;
        navIndicator.classList.add('is-visible');
    }

    if (navLinksWrap && navIndicator && navLinks.length > 0) {
        if (activeNavLink) {
            moverIndicadorNav(activeNavLink);
        }

        navLinks.forEach((link) => {
            link.addEventListener('mouseenter', () => moverIndicadorNav(link));
            link.addEventListener('focus', () => moverIndicadorNav(link));
        });

        navLinksWrap.addEventListener('mouseleave', () => {
            if (activeNavLink) {
                moverIndicadorNav(activeNavLink);
            } else {
                navIndicator.classList.remove('is-visible');
            }
        });

        window.addEventListener('resize', () => {
            if (activeNavLink) {
                moverIndicadorNav(activeNavLink);
            }
        });
    }

    // Tema fijo de la aplicación: ThinkPad Classic
    document.documentElement.setAttribute('data-theme', 'thinkpad-classic');

    // Layouts fijos de la aplicación
    const fixedLayouts = {
        header: 'classic',
        hero: 'bento-asimetrico',
        strip: 'rows-list',
        catalog: 'grid-3',
        footer: 'split',
    };
    Object.entries(fixedLayouts).forEach(([target, value]) => {
        document.documentElement.setAttribute(`data-layout-${target}`, value);
    });

    const authModal = document.getElementById('authModal');
    const authTabs = document.getElementById('authTabs');
    const authViewport = document.getElementById('authViewport');
    const tabLogin = document.getElementById('tab-login');
    const tabRegister = document.getElementById('tab-register');
    const paneLogin = document.getElementById('pane-login');
    const paneRegister = document.getElementById('pane-register');
    const formLogin = document.getElementById('formLogin');
    const formRegister = document.getElementById('formRegister');
    const btnLogout = document.getElementById('btnLogout');
    const authAlert = document.getElementById('authAlert');

    function cambiarTabAuth(modo) {
        if (!authTabs || !authViewport || !tabLogin || !tabRegister || !paneLogin || !paneRegister) return;
        const esRegistro = modo === 'register';

        authTabs.classList.toggle('is-register', esRegistro);
        authViewport.classList.toggle('is-register', esRegistro);

        tabLogin.classList.toggle('active', !esRegistro);
        tabLogin.setAttribute('aria-selected', String(!esRegistro));
        tabRegister.classList.toggle('active', esRegistro);
        tabRegister.setAttribute('aria-selected', String(esRegistro));

        paneLogin.classList.toggle('is-active', !esRegistro);
        paneRegister.classList.toggle('is-active', esRegistro);
        paneLogin.inert = esRegistro;
        paneRegister.inert = !esRegistro;

        const paneActivo = esRegistro ? paneRegister : paneLogin;
        authViewport.style.height = `${paneActivo.offsetHeight}px`;

        if (authAlert) {
            authAlert.classList.add('d-none');
        }
    }

    if (tabLogin && tabRegister) {
        tabLogin.addEventListener('click', () => cambiarTabAuth('login'));
        tabRegister.addEventListener('click', () => cambiarTabAuth('register'));
    }

    if (authModal) {
        authModal.addEventListener('shown.bs.modal', () => {
            const modoActual = authTabs?.classList.contains('is-register') ? 'register' : 'login';
            cambiarTabAuth(modoActual);
        });
    }

    function mostrarAlertaAuth(mensaje, tipo = 'danger') {
        if (!authAlert) return;
        authAlert.className = `alert alert-${tipo}`;
        authAlert.textContent = mensaje;
    }

    async function enviarAuth(action, formElement) {
        const datos = new FormData(formElement);
        datos.append('action', action);

        try {
            const resp = await fetch('api/auth_action.php', {
                method: 'POST',
                body: datos,
            });
            const json = await resp.json();

            if (!resp.ok || !json.ok) {
                mostrarAlertaAuth(json.error || 'No se pudo completar la operación.');
                return;
            }

            window.location.reload();
        } catch (err) {
            mostrarAlertaAuth('Error de conexión con el servidor.');
        }
    }

    if (formLogin) {
        formLogin.addEventListener('submit', (e) => {
            e.preventDefault();
            enviarAuth('login', formLogin);
        });
    }

    if (formRegister) {
        formRegister.addEventListener('submit', (e) => {
            e.preventDefault();
            enviarAuth('register', formRegister);
        });
    }

    if (btnLogout) {
        btnLogout.addEventListener('click', async () => {
            const datos = new FormData();
            datos.append('action', 'logout');

            try {
                const resp = await fetch('api/auth_action.php', {
                    method: 'POST',
                    body: datos,
                });
                const json = await resp.json();
                if (json.ok) {
                    window.location.href = 'index.php';
                }
            } catch (err) {
                window.location.reload();
            }
        });
    }

    const formContacto = document.getElementById('formContacto');
    const contactoAlert = document.getElementById('contactoAlert');

    if (formContacto) {
        formContacto.addEventListener('submit', async (e) => {
            e.preventDefault();
            const datos = new FormData(formContacto);

            try {
                const resp = await fetch('api/contacto_action.php', {
                    method: 'POST',
                    body: datos,
                });
                const json = await resp.json();

                if (!resp.ok || !json.ok) {
                    contactoAlert.className = 'alert alert-danger';
                    contactoAlert.textContent = json.error || 'No se pudo enviar el mensaje.';
                    return;
                }

                contactoAlert.className = 'alert alert-success';
                contactoAlert.textContent = json.mensaje;
                formContacto.reset();
            } catch (err) {
                contactoAlert.className = 'alert alert-danger';
                contactoAlert.textContent = 'Error de conexión con el servidor.';
            }
        });
    }

    // =========================================================
    // LÓGICA DE TIENDA (Carrito, Perfil, Pedidos y Admin)
    // =========================================================
    const tiendaAlert = document.getElementById('tiendaAlert');

    function mostrarAlertaTienda(mensaje, tipo = 'danger') {
        if (!tiendaAlert) return;
        tiendaAlert.className = `alert alert-${tipo}`;
        tiendaAlert.textContent = mensaje;
        tiendaAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function enviarApi(url, datos) {
        try {
            const resp = await fetch(url, { method: 'POST', body: datos });
            const json = await resp.json();
            if (!resp.ok || !json.ok) {
                mostrarAlertaTienda(json.error || 'No se pudo completar la operación.');
                return null;
            }
            return json;
        } catch (err) {
            mostrarAlertaTienda('Error de conexión con el servidor.');
            return null;
        }
    }

    // Añadir al carrito
    document.querySelectorAll('.btn-add-cart').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const productoId = btn.dataset.productoId;
            const qtyInput = btn.closest('.d-flex')?.querySelector('.sv-qty-input');
            const cantidad = qtyInput ? qtyInput.value : '1';

            const datos = new FormData();
            datos.append('action', 'add');
            datos.append('producto_id', productoId);
            datos.append('cantidad', cantidad);

            const res = await enviarApi('api/carrito_action.php', datos);
            if (res) window.location.reload();
        });
    });

    // Comprar ahora (desde ficha de producto)
    document.querySelectorAll('.btn-buy-now').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const productoId = btn.dataset.productoId;
            const qtyInput = btn.closest('.d-flex')?.querySelector('.sv-qty-input');
            const cantidad = qtyInput ? qtyInput.value : '1';

            const datos = new FormData();
            datos.append('action', 'buy_now');
            datos.append('producto_id', productoId);
            datos.append('cantidad', cantidad);

            const res = await enviarApi('api/carrito_action.php', datos);
            if (res) {
                window.location.href = 'perfil.php';
            }
        });
    });

    // Publicar opinión de cliente
    const formOpinion = document.getElementById('formOpinion');
    if (formOpinion) {
        formOpinion.addEventListener('submit', async (e) => {
            e.preventDefault();
            const datos = new FormData(formOpinion);
            const res = await enviarApi('api/opiniones_action.php', datos);
            if (res) window.location.reload();
        });
    }

    // Responder a opiniones de clientes (Admin)
    document.querySelectorAll('.sv-admin-reply-form').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const datos = new FormData(form);
            const res = await enviarApi('api/opiniones_action.php', datos);
            if (res) window.location.reload();
        });
    });

    // Flechas de galería en tarjetas de producto
    document.querySelectorAll('.sv-product-media[data-images]').forEach((media) => {
        let images = [];
        try {
            images = JSON.parse(media.dataset.images || '[]');
        } catch (err) {
            images = [];
        }
        if (images.length <= 1) return;

        let idx = 0;
        const imgEl = media.querySelector('img');
        const prevBtn = media.querySelector('.sv-card-arrow--prev');
        const nextBtn = media.querySelector('.sv-card-arrow--next');

        const cambiar = (delta, event) => {
            event.preventDefault();
            event.stopPropagation();
            idx = (idx + delta + images.length) % images.length;
            if (imgEl) imgEl.src = images[idx];
        };

        if (prevBtn) prevBtn.addEventListener('click', (e) => cambiar(-1, e));
        if (nextBtn) nextBtn.addEventListener('click', (e) => cambiar(1, e));
    });

    // Miniaturas en columna estilo Amazon en la vista única de producto
    const imgPrincipal = document.getElementById('detalleImagenPrincipal');
    const galleryThumbs = document.querySelectorAll('.sv-gallery-thumb');
    if (imgPrincipal && galleryThumbs.length > 0) {
        const seleccionarMiniatura = (thumb) => {
            imgPrincipal.src = thumb.dataset.imgSrc;
            galleryThumbs.forEach((t) => t.classList.remove('is-active'));
            thumb.classList.add('is-active');
        };

        galleryThumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => seleccionarMiniatura(thumb));
            thumb.addEventListener('mouseenter', () => seleccionarMiniatura(thumb));
        });
    }

    // Quitar línea del carrito
    document.querySelectorAll('.btn-remove-cart-line').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const datos = new FormData();
            datos.append('action', 'remove_line');
            datos.append('linea_id', btn.dataset.lineaId);

            const res = await enviarApi('api/carrito_action.php', datos);
            if (res) window.location.reload();
        });
    });

    // Confirmar compra
    const btnConfirmarCompra = document.getElementById('btnConfirmarCompra');
    if (btnConfirmarCompra) {
        btnConfirmarCompra.addEventListener('click', async () => {
            const datos = new FormData();
            datos.append('action', 'confirm');

            const res = await enviarApi('api/carrito_action.php', datos);
            if (res) {
                window.location.href = 'perfil.php';
            }
        });
    }

    // Editar perfil y avatar
    const formPerfil = document.getElementById('formPerfil');
    if (formPerfil) {
        formPerfil.addEventListener('submit', async (e) => {
            e.preventDefault();
            const datos = new FormData(formPerfil);
            const res = await enviarApi('api/perfil_action.php', datos);
            if (res) window.location.reload();
        });
    }

    // Cancelar línea de pedido
    document.querySelectorAll('.btn-cancel-line').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('¿Cancelar esta línea y reponer su stock?')) return;
            const datos = new FormData();
            datos.append('action', 'cancel_line');
            datos.append('linea_id', btn.dataset.lineaId);

            const res = await enviarApi('api/pedidos_action.php', datos);
            if (res) window.location.reload();
        });
    });

    // Cancelar pedido completo
    document.querySelectorAll('.btn-cancel-order').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('¿Cancelar el pedido completo y reponer todas sus unidades al stock?')) return;
            const datos = new FormData();
            datos.append('action', 'cancel_order');
            datos.append('pedido_id', btn.dataset.pedidoId);

            const res = await enviarApi('api/pedidos_action.php', datos);
            if (res) window.location.reload();
        });
    });

    // Filtro de pedidos con indicador deslizante (Admin)
    const adminOrderTabsWrap = document.querySelector('.sv-admin-order-tabs');
    const adminOrderTabs = adminOrderTabsWrap ? adminOrderTabsWrap.querySelectorAll('.sv-admin-order-tab') : [];
    const adminTabIndicator = adminOrderTabsWrap ? adminOrderTabsWrap.querySelector('.sv-tab-indicator') : null;
    const adminOrderCards = document.querySelectorAll('.sv-admin-order-card');
    const adminOrdersEmpty = document.getElementById('adminOrdersEmpty');

    function moverIndicadorPedidos(targetTab) {
        if (!adminTabIndicator || !targetTab) return;
        const pad = 8;
        const width = Math.max(0, targetTab.offsetWidth - pad * 2);
        const x = targetTab.offsetLeft + pad;
        adminTabIndicator.style.width = `${width}px`;
        adminTabIndicator.style.transform = `translateX(${x}px)`;
        adminTabIndicator.classList.add('is-visible');
    }

    function filtrarPedidosAdmin(estado) {
        let visibles = 0;
        adminOrderCards.forEach((card) => {
            const coincide = card.dataset.estado === estado;
            card.classList.toggle('d-none', !coincide);
            if (coincide) visibles += 1;
        });
        if (adminOrdersEmpty) {
            adminOrdersEmpty.classList.toggle('d-none', visibles > 0);
        }
    }

    if (adminOrderTabsWrap && adminOrderTabs.length > 0) {
        let tabActiva = adminOrderTabsWrap.querySelector('.sv-admin-order-tab.is-active') || adminOrderTabs[0];
        moverIndicadorPedidos(tabActiva);
        filtrarPedidosAdmin(tabActiva.dataset.estado);

        adminOrderTabs.forEach((tab) => {
            tab.addEventListener('mouseenter', () => moverIndicadorPedidos(tab));
            tab.addEventListener('focus', () => moverIndicadorPedidos(tab));
            tab.addEventListener('click', () => {
                tabActiva = tab;
                adminOrderTabs.forEach((t) => {
                    const activo = t === tab;
                    t.classList.toggle('is-active', activo);
                    t.setAttribute('aria-selected', activo ? 'true' : 'false');
                });
                moverIndicadorPedidos(tab);
                filtrarPedidosAdmin(tab.dataset.estado);
            });
        });

        adminOrderTabsWrap.addEventListener('mouseleave', () => {
            moverIndicadorPedidos(tabActiva);
        });

        window.addEventListener('resize', () => {
            moverIndicadorPedidos(tabActiva);
        });
    }

    // Solicitar reembolso (Comprador)
    document.querySelectorAll('.btn-request-refund').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!window.confirm('¿Seguro que deseas solicitar el reembolso de este pedido?')) {
                return;
            }
            const datos = new FormData();
            datos.append('action', 'request_refund');
            datos.append('pedido_id', btn.dataset.pedidoId);

            const res = await enviarApi('api/pedidos_action.php', datos);
            if (res) window.location.reload();
        });
    });

    // Reembolsar pedido (Admin)
    document.querySelectorAll('.btn-refund-order').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!window.confirm('¿Confirmas el reembolso de este pedido? Se devolverá el importe y se repondrá el stock.')) {
                return;
            }
            const datos = new FormData();
            datos.append('action', 'cancel_order');
            datos.append('pedido_id', btn.dataset.pedidoId);

            const res = await enviarApi('api/pedidos_action.php', datos);
            if (res) window.location.reload();
        });
    });

    // Aceptar pedido (Admin)
    document.querySelectorAll('.btn-accept-order').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const datos = new FormData();
            datos.append('action', 'accept_order');
            datos.append('pedido_id', btn.dataset.pedidoId);

            const res = await enviarApi('api/pedidos_action.php', datos);
            if (res) window.location.reload();
        });
    });

    // Editor de Banner (Admin)
    const formAdminBanner = document.getElementById('formAdminBanner');
    if (formAdminBanner) {
        formAdminBanner.addEventListener('submit', async (e) => {
            e.preventDefault();
            const datos = new FormData(formAdminBanner);
            const res = await enviarApi('api/banner_action.php', datos);
            if (res) {
                mostrarAlertaTienda(res.mensaje || 'Banner actualizado.', 'success');
            }
        });
    }

    // Buscador y ordenación en directo en Inventario actual (Admin)
    const adminInventorySearch = document.getElementById('adminInventorySearch');
    const adminInventorySort = document.getElementById('adminInventorySort');
    const adminInventoryRows = Array.from(document.querySelectorAll('.sv-admin-inventory-row'));
    const adminInventoryEmpty = document.getElementById('adminInventoryEmpty');
    if (adminInventorySearch && adminInventoryRows.length > 0) {
        adminInventorySearch.addEventListener('input', () => {
            const termino = adminInventorySearch.value.trim().toLowerCase();
            let visibles = 0;
            adminInventoryRows.forEach((row) => {
                const nombre = row.dataset.nombre || '';
                const coincide = nombre.includes(termino);
                row.classList.toggle('d-none', !coincide);
                if (coincide) visibles += 1;
            });
            if (adminInventoryEmpty) {
                adminInventoryEmpty.classList.toggle('d-none', visibles > 0);
            }
        });
    }

    if (adminInventorySort && adminInventoryRows.length > 0) {
        const tbody = adminInventoryRows[0].parentElement;
        adminInventorySort.addEventListener('change', () => {
            const criterio = adminInventorySort.value;
            const ordenadas = [...adminInventoryRows].sort((a, b) => {
                if (criterio === 'nombre_asc') {
                    return (a.dataset.nombre || '').localeCompare(b.dataset.nombre || '', 'es');
                }
                if (criterio === 'precio_asc') {
                    return parseFloat(a.dataset.precio) - parseFloat(b.dataset.precio);
                }
                if (criterio === 'precio_desc') {
                    return parseFloat(b.dataset.precio) - parseFloat(a.dataset.precio);
                }
                if (criterio === 'stock_asc') {
                    return parseInt(a.dataset.stock, 10) - parseInt(b.dataset.stock, 10);
                }
                if (criterio === 'stock_desc') {
                    return parseInt(b.dataset.stock, 10) - parseInt(a.dataset.stock, 10);
                }
                return parseInt(a.dataset.id, 10) - parseInt(b.dataset.id, 10);
            });
            ordenadas.forEach((row) => tbody.appendChild(row));
            if (adminInventoryEmpty) {
                tbody.appendChild(adminInventoryEmpty);
            }
        });
    }

    // CRUD Productos (Admin)
    const formAdminProducto = document.getElementById('formAdminProducto');
    const adminProdId = document.getElementById('adminProdId');
    const adminFormTitulo = document.getElementById('adminFormTitulo');
    const btnCancelarEdicion = document.getElementById('btnCancelarEdicion');

    if (formAdminProducto) {
        formAdminProducto.addEventListener('submit', async (e) => {
            e.preventDefault();
            const datos = new FormData(formAdminProducto);
            datos.append('action', adminProdId.value ? 'update' : 'create');

            const res = await enviarApi('api/admin_productos.php', datos);
            if (res) window.location.reload();
        });

        document.querySelectorAll('.btn-edit-prod').forEach((btn) => {
            btn.addEventListener('click', () => {
                adminProdId.value = btn.dataset.id;
                document.getElementById('adminProdNombre').value = btn.dataset.nombre;
                document.getElementById('adminProdDesc').value = btn.dataset.descripcion;
                document.getElementById('adminProdPrecio').value = btn.dataset.precio;
                document.getElementById('adminProdStock').value = btn.dataset.stock;
                adminFormTitulo.textContent = `Editando producto #${btn.dataset.id}`;
                btnCancelarEdicion.classList.remove('d-none');
                formAdminProducto.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        });

        if (btnCancelarEdicion) {
            btnCancelarEdicion.addEventListener('click', () => {
                formAdminProducto.reset();
                adminProdId.value = '';
                adminFormTitulo.textContent = 'Nuevo producto';
                btnCancelarEdicion.classList.add('d-none');
            });
        }

        document.querySelectorAll('.btn-delete-prod').forEach((btn) => {
            btn.addEventListener('click', async () => {
                if (!confirm('¿Eliminar este producto del catálogo?')) return;
                const datos = new FormData();
                datos.append('action', 'delete');
                datos.append('id', btn.dataset.id);

                const res = await enviarApi('api/admin_productos.php', datos);
                if (res) window.location.reload();
            });
        });
    }
});
