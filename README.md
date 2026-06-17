# Repositorio Documental
Sistema de gestión y publicación de documentos institucionales.
Arquitectura limpia · PHP 8+ · MySQL · Sin frameworks ni dependencias externas

---

## Características

- Repositorio público con búsqueda, filtros por categoría, dependencia y año
- Panel de administración con control de acceso por roles
- CRUD completo de documentos, categorías, dependencias y usuarios
- Carga de archivos con drag & drop (PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX)
- Edición de documentos con reemplazo opcional del archivo
- Log de actividad del sistema
- BASE_URL dinámica — funciona en cualquier subdirectorio sin configuración extra
- Sin dependencias externas ni frameworks

---

## Estructura del proyecto

```
repositorio/
├── index.php               ← Redirige automáticamente a public/
├── config.php              ← Configuración global (BD, rutas, constantes)
├── database.sql            ← Script SQL completo
├── .htaccess               ← Seguridad raíz
│
├── src/                    ← Lógica de negocio (no accesible desde web)
│   ├── bootstrap.php       ← Autoloader + sesión + cabeceras de seguridad
│   ├── Database.php        ← Conexión PDO Singleton
│   ├── Auth.php            ← Autenticación + roles + permisos
│   ├── Logger.php          ← Log de actividad en BD
│   ├── Helpers.php         ← Funciones utilitarias globales
│   ├── Document.php        ← Modelo de documentos
│   └── Models.php          ← Modelos: User, Category, Dependency
│
├── templates/              ← Layouts reutilizables
│   ├── layout_public_top.php
│   ├── layout_public_bottom.php
│   ├── layout_admin_top.php
│   ├── layout_admin_bottom.php
│   └── error_403.php
│
├── uploads/                ← Archivos subidos
│   └── .htaccess           ← Bloquea ejecución PHP en uploads
│
└── public/                 ← Único directorio expuesto al navegador
    ├── index.php           ← Repositorio público
    ├── .htaccess
    ├── assets/
    │   ├── css/
    │   │   ├── public.css
    │   │   └── admin.css
    │   ├── js/
    │   │   ├── public.js
    │   │   └── admin.js
    │   └── img/
    │       └── logo.png    ← Logo de la organización
    └── admin/
        ├── index.php       ← Login (acceder a /admin/ abre el login directo)
        ├── logout.php
        ├── panel.php       ← Dashboard con estadísticas y actividad reciente
        ├── documentos.php  ← Subir, editar y eliminar documentos
        ├── categorias.php  ← CRUD de categorías con colores personalizables
        ├── dependencias.php← CRUD de dependencias/áreas
        ├── usuarios.php    ← CRUD de usuarios con gestión de roles
        └── log.php         ← Log de actividad del sistema
```

---

## Roles y permisos

| Permiso                  | superadmin | editor | viewer |
|--------------------------|:----------:|:------:|:------:|
| Ver documentos           | ✅         | ✅     | ✅     |
| Subir documentos         | ✅         | ✅     | ❌     |
| Editar documentos        | ✅         | ✅     | ❌     |
| Eliminar documentos      | ✅         | ❌     | ❌     |
| Ver/crear usuarios       | ✅         | ❌     | ❌     |
| Editar usuarios          | ✅         | ❌     | ❌     |
| Cambiar propia contraseña| ✅         | ✅     | ✅     |
| CRUD categorías          | ✅         | ❌     | ❌     |
| CRUD dependencias        | ✅         | ❌     | ❌     |
| Ver log de actividad     | ✅         | ❌     | ❌     |

---

## Instalación

### 1. Base de datos
En phpMyAdmin → pestaña SQL → pegar y ejecutar `database.sql`.

### 2. Configurar `config.php`
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'repositorio_docs');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');

define('INST_NOMBRE',       'Nombre de la organización');
define('INST_NOMBRE_CORTO', 'SIGLA');
define('INST_EMAIL',        'contacto@organizacion.com');

// Entorno: 'development' para ver errores, 'production' para ocultalos
define('APP_ENV', 'production');
```

### 3. Logo
Coloca el logo en `public/assets/img/logo.png`.
Se recomienda PNG con fondo transparente, mínimo 120px de alto.

### 4. Subir al servidor
Sube todo el proyecto al servidor via FTP o cPanel.

**Opción A — Document Root apunta a `public/`** *(recomendado)*
El servidor sirve directamente desde `public/`. No requiere configuración adicional.

**Opción B — Proyecto en un subdirectorio**
Si el proyecto queda en `/public_html/repositorio/`, el `index.php` de la raíz
redirige automáticamente a `public/`. La `BASE_URL` se calcula sola, funciona
en local y en producción sin cambiar nada.

### 5. Permisos (cPanel)
- Carpeta `uploads/` → **755**
- Archivos PHP → **644**

### 6. Primer acceso
- Repositorio público: `https://tudominio.com/`
- Panel admin: `https://tudominio.com/admin/`
- Usuario inicial: `overlord`
- Contraseña inicial: `12345678` cambiarla desde el panel en el primer ingreso

---

## Gestión de usuarios

Desde `admin/ → Usuarios` se puede:

- **Crear** nuevos usuarios con rol asignado
- **Editar** nombre, usuario (login), email, rol y estado activo/inactivo
- **Cambiar contraseña** — si es el propio usuario, solicita la contraseña actual;
  si es un superadmin editando otro usuario, no la solicita
- **Eliminar** usuarios (con protección para no eliminar el último superadmin)

---

## Categorías y dependencias

Ambas son administrables desde el panel sin tocar la base de datos:

- **Categorías** — nombre, colores de texto y fondo personalizables, orden y estado activo/inactivo
- **Dependencias** — nombre completo, sigla y estado activo/inactivo
- No se puede eliminar una categoría o dependencia que tenga documentos asociados

---

## Seguridad implementada

- CSRF tokens en todos los formularios POST
- Protección SQL Injection — PDO con prepared statements
- XSS — escape de toda salida con `htmlspecialchars`
- Cabeceras HTTP de seguridad (CSP, X-Frame-Options, Referrer-Policy)
- Sesión con regeneración periódica de ID
- Cookies HttpOnly + SameSite=Strict
- Archivos de configuración bloqueados desde web (.htaccess)
- Carpeta `uploads/` sin listado ni ejecución de scripts
- Delay en login fallido (anti fuerza bruta)
- Log de actividad con IP y usuario
- Validación de tipo de archivo en servidor (no solo en cliente)
- Nombres de archivo aleatorios al subir (no predecibles)
- Control de roles granular por permiso
- Protección para no degradar/eliminar el último superadmin

---

## Requisitos del servidor

- PHP 8.0 o superior
- MySQL 5.7+ / MariaDB 10.3+
- Apache con mod_rewrite habilitado
- Extensión PDO_MySQL habilitada