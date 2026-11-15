-- Extensión para generar hashes bcrypt compatibles con PHP password_verify()
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- 1. Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL DEFAULT 'comprador' CHECK (rol IN ('admin', 'comprador')),
    avatar VARCHAR(255) NOT NULL DEFAULT 'assets/img/default-avatar.svg',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabla de productos
CREATE TABLE IF NOT EXISTS productos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    precio NUMERIC(10, 2) NOT NULL CHECK (precio >= 0),
    stock INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
    imagen VARCHAR(255) NOT NULL DEFAULT 'assets/img/default-product.svg',
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabla de pedidos (incluye el carrito persistente en estado 'carrito')
CREATE TABLE IF NOT EXISTS pedidos (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    estado VARCHAR(20) NOT NULL DEFAULT 'carrito' CHECK (estado IN ('carrito', 'pendiente', 'aceptado', 'cancelado')),
    total NUMERIC(10, 2) NOT NULL DEFAULT 0 CHECK (total >= 0),
    reembolso_solicitado BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Garantiza un único carrito activo por usuario
CREATE UNIQUE INDEX IF NOT EXISTS idx_pedidos_unico_carrito
    ON pedidos (usuario_id)
    WHERE estado = 'carrito';

-- 4. Líneas de pedido
CREATE TABLE IF NOT EXISTS pedido_lineas (
    id SERIAL PRIMARY KEY,
    pedido_id INTEGER NOT NULL REFERENCES pedidos(id) ON DELETE CASCADE,
    producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE RESTRICT,
    cantidad INTEGER NOT NULL CHECK (cantidad > 0),
    precio_unitario NUMERIC(10, 2) NOT NULL CHECK (precio_unitario >= 0),
    estado_linea VARCHAR(20) NOT NULL DEFAULT 'activa' CHECK (estado_linea IN ('activa', 'cancelada'))
);

-- 5. Mensajes del formulario de contacto
CREATE TABLE IF NOT EXISTS mensajes_contacto (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    asunto VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 6. Opiniones de clientes por producto
CREATE TABLE IF NOT EXISTS producto_opiniones (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
    usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    puntuacion INTEGER NOT NULL CHECK (puntuacion BETWEEN 1 AND 5),
    comentario TEXT NOT NULL,
    respuesta_admin TEXT,
    respuesta_fecha TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 7. Imágenes adicionales por producto (galería)
CREATE TABLE IF NOT EXISTS producto_imagenes (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
    ruta VARCHAR(255) NOT NULL,
    orden INTEGER NOT NULL DEFAULT 1
);

-- 8. Configuración del banner promocional de portada
CREATE TABLE IF NOT EXISTS banner_config (
    id INTEGER PRIMARY KEY DEFAULT 1,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    etiqueta VARCHAR(120) NOT NULL DEFAULT 'BLACK FRIDAY · STOCK LIMITADO',
    titulo VARCHAR(200) NOT NULL DEFAULT 'Hasta 40% de descuento en portátiles y accesorios',
    subtitulo TEXT NOT NULL DEFAULT 'Equipos ThinkPad, Dell Latitude, MacBook y accesorios profesionales reacondicionados con rebaja directa hasta agotar existencias en almacén.',
    texto_boton VARCHAR(80) NOT NULL DEFAULT 'Ver ofertas',
    url_boton VARCHAR(200) NOT NULL DEFAULT 'tienda.php',
    color_fondo VARCHAR(20) NOT NULL DEFAULT '#181a1d',
    color_texto VARCHAR(20) NOT NULL DEFAULT '#f8fafc',
    color_acento VARCHAR(20) NOT NULL DEFAULT '#e62325',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO banner_config (id) VALUES (1) ON CONFLICT (id) DO NOTHING;

-- 6. Suscripciones a la newsletter
CREATE TABLE IF NOT EXISTS newsletter_suscripciones (
    id SERIAL PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================================
-- DATOS SEMILLA (Usuarios y catálogo inicial de informática reciclada)
-- =====================================================================

INSERT INTO usuarios (nombre, email, password_hash, rol)
VALUES
    ('Administrador', 'admin@tienda.local', crypt('admin123', gen_salt('bf', 10)), 'admin'),
    ('Cliente Demo', 'cliente@tienda.local', crypt('cliente123', gen_salt('bf', 10)), 'comprador')
ON CONFLICT (email) DO NOTHING;

INSERT INTO productos (nombre, descripcion, precio, stock, imagen)
VALUES
    (
        'Apple MacBook Pro 14" Space Grey',
        'Portátil reacondicionado con chip Apple M1 Pro, rendimiento excepcional y pantalla Retina de alta resolución.',
        1099.00,
        6,
        'uploads/productos/prod-78.webp'
    ),
    (
        'Asus Zenbook Pro Dual Screen Laptop',
        'Portátil reacondicionado de alto rendimiento con doble pantalla, ideal para productividad avanzada y profesionales creativos.',
        949.00,
        4,
        'uploads/productos/prod-79.webp'
    ),
    (
        'Huawei Matebook X Pro',
        'Portátil ultradelgado reacondicionado con pantalla táctil de alta resolución, pensado para trabajar en movilidad.',
        699.00,
        8,
        'uploads/productos/prod-80.webp'
    ),
    (
        'Lenovo Yoga 920',
        'Portátil convertible 2 en 1 reacondicionado con bisagra flexible de 360 grados para usarlo como ordenador o tablet.',
        449.00,
        5,
        'uploads/productos/prod-81.webp'
    ),
    (
        'Dell XPS 13 9300 Laptop',
        'Ultrabook compacto reacondicionado con pantalla InfinityEdge prácticamente sin bordes y gran autonomía.',
        649.00,
        7,
        'uploads/productos/prod-82.webp'
    ),
    (
        'iPad Mini 2021 Starlight',
        'Tablet compacta reacondicionada de Apple con pantalla Retina, chip serie A y diseño ligero.',
        289.00,
        9,
        'uploads/productos/prod-159.webp'
    ),
    (
        'Samsung Galaxy Tab S8 Plus Grey',
        'Tablet Android reacondicionada de alto rendimiento con gran pantalla AMOLED, procesador rápido y soporte para S Pen.',
        339.00,
        6,
        'uploads/productos/prod-160.webp'
    ),
    (
        'Samsung Galaxy Tab White',
        'Tablet Android reacondicionada y versátil con pantalla vibrante y batería revisada para trabajo y ocio.',
        179.00,
        10,
        'uploads/productos/prod-161.webp'
    ),
    (
        'Amazon Echo Plus',
        'Altavoz inteligente reacondicionado con control por voz Alexa integrado, sonido de alta calidad y hub para hogar conectado.',
        49.00,
        12,
        'uploads/productos/prod-99.webp'
    ),
    (
        'Apple AirPods',
        'Auriculares inalámbricos reacondicionados con emparejamiento instantáneo, audio nítido e integración con Siri.',
        69.00,
        15,
        'uploads/productos/prod-100.webp'
    ),
    (
        'Apple AirPods Max Silver',
        'Auriculares circumaurales reacondicionados de alta fidelidad con ecualización adaptativa y cancelación activa de ruido.',
        319.00,
        4,
        'uploads/productos/prod-101.webp'
    ),
    (
        'Apple AirPower Wireless Charger',
        'Base de carga inalámbrica reacondicionada diseñada para recargar varios dispositivos compatibles de forma simultánea.',
        39.00,
        0,
        'uploads/productos/prod-102.webp'
    ),
    (
        'Apple HomePod Mini Cosmic Grey',
        'Altavoz inteligente compacto reacondicionado con audio envolvente e integración total con el ecosistema Apple.',
        59.00,
        11,
        'uploads/productos/prod-103.webp'
    ),
    (
        'Apple iPhone Charger',
        'Cargador rápido de pared revisado y de alta eficiencia para mantener tus dispositivos siempre con batería.',
        9.90,
        20,
        'uploads/productos/prod-104.webp'
    ),
    (
        'Apple MagSafe Battery Pack',
        'Batería externa magnética portátil reacondicionada compatible con MagSafe para ampliar la autonomía sin cables.',
        49.00,
        3,
        'uploads/productos/prod-105.webp'
    ),
    (
        'Apple Watch Series 4 Gold',
        'Reloj inteligente reacondicionado con monitorización de ritmo cardíaco, seguimiento deportivo y pantalla Retina.',
        149.00,
        5,
        'uploads/productos/prod-106.webp'
    ),
    (
        'Beats Flex Wireless Earphones',
        'Auriculares inalámbricos reacondicionados cómodos con extremos magnéticos y hasta 12 horas de autonomía.',
        24.90,
        14,
        'uploads/productos/prod-107.webp'
    );

INSERT INTO producto_opiniones (producto_id, usuario_id, puntuacion, comentario)
SELECT p.id, u.id, 5, 'Equipo muy bien reacondicionado, silencioso y con la batería al 90%. Listo para trabajar desde el primer día.'
FROM productos p, usuarios u
WHERE p.id = 1 AND u.email = 'cliente@tienda.local'
AND NOT EXISTS (SELECT 1 FROM producto_opiniones WHERE producto_id = 1 AND usuario_id = u.id);

INSERT INTO producto_opiniones (producto_id, usuario_id, puntuacion, comentario)
SELECT p.id, u.id, 4, 'Pantalla en muy buen estado y rendimiento fluido para uso diario.'
FROM productos p, usuarios u
WHERE p.id = 2 AND u.email = 'cliente@tienda.local'
AND NOT EXISTS (SELECT 1 FROM producto_opiniones WHERE producto_id = 2 AND usuario_id = u.id);

-- Imágenes extra descargadas para galería
INSERT INTO producto_imagenes (producto_id, ruta, orden)
SELECT p.id, REPLACE(p.imagen, '.webp', '-2.webp'), 2
FROM productos p
WHERE p.imagen IN (
    'uploads/productos/prod-78.webp',
    'uploads/productos/prod-79.webp',
    'uploads/productos/prod-80.webp',
    'uploads/productos/prod-81.webp',
    'uploads/productos/prod-82.webp',
    'uploads/productos/prod-100.webp',
    'uploads/productos/prod-104.webp',
    'uploads/productos/prod-105.webp',
    'uploads/productos/prod-106.webp'
)
AND NOT EXISTS (
    SELECT 1 FROM producto_imagenes pi WHERE pi.producto_id = p.id AND pi.orden = 2
);

INSERT INTO producto_imagenes (producto_id, ruta, orden)
SELECT p.id, REPLACE(p.imagen, '.webp', '-3.webp'), 3
FROM productos p
WHERE p.imagen IN (
    'uploads/productos/prod-78.webp',
    'uploads/productos/prod-79.webp',
    'uploads/productos/prod-80.webp',
    'uploads/productos/prod-81.webp',
    'uploads/productos/prod-82.webp',
    'uploads/productos/prod-100.webp',
    'uploads/productos/prod-106.webp'
)
AND NOT EXISTS (
    SELECT 1 FROM producto_imagenes pi WHERE pi.producto_id = p.id AND pi.orden = 3
);
