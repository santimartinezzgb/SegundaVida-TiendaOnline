# SegundaVida — Tienda Online

Aplicación web de tienda online de informática reacondicionada y de segunda mano desarrollada con **PHP 8**, **PostgreSQL 16**, **Bootstrap 5** y **JavaScript Vanilla**, desplegada mediante **Docker Compose**.

## Requisitos mínimos

- **Docker** y **Docker Compose v2** (`docker compose`) instalados y en ejecución.
- **Git** para clonar el repositorio.
- **Puertos libres:** `8088` (web), `8081` (Adminer) y `5432` (PostgreSQL en localhost), o configurables en `.env`.
- **Espacio en disco:** ~500 MB libres para las imágenes de PHP/Apache, PostgreSQL 16 y Adminer.

## Puesta en marcha

1. Copia el archivo de variables de entorno y ajusta las credenciales si lo necesitas:
   ```bash
   cp .env.example .env
   ```
2. Levanta los contenedores:
   ```bash
   docker compose up -d --build
   ```
3. Accede en el navegador:
   - **Tienda:** `http://localhost:8088` (o el `APP_PORT` definido en `.env`)
   - **Adminer:** `http://localhost:8081` (o el `ADMINER_PORT` definido en `.env`)

## Usuarios de prueba (`sql/schema.sql`)

- **Administrador:** `admin@tienda.local` / `admin123`
- **Comprador demo:** `cliente@tienda.local` / `cliente123`

> Recuerda cambiar estas contraseñas y las credenciales de `.env` antes de desplegar en un entorno público.
