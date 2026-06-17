<?php
// ============================================================
// src/Helpers.php — Funciones utilitarias globales
// ============================================================

/** Escapar HTML */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Redirigir — prefija BASE_URL automáticamente en rutas absolutas */
function redirect(string $url): never
{
    if (str_starts_with($url, '/')) {
        $url = BASE_URL . $url;
    }
    header('Location: ' . $url);
    exit;
}

/** Formatear tamaño en KB a legible */
function formatSize(int $kb): string
{
    if ($kb < 1024) return $kb . ' KB';
    return round($kb / 1024, 1) . ' MB';
}

/** Tipo de archivo → etiqueta + colores */
function tipoInfo(string $ext): array
{
    return match(strtolower($ext)) {
        'pdf'        => ['label' => 'PDF', 'color' => '#993C1D', 'bg' => '#FAECE7'],
        'doc','docx' => ['label' => 'DOC', 'color' => '#185FA5', 'bg' => '#E6F1FB'],
        'ppt','pptx' => ['label' => 'PPT', 'color' => '#854F0B', 'bg' => '#FAEEDA'],
        'xls','xlsx' => ['label' => 'XLS', 'color' => '#3B6D11', 'bg' => '#EAF3DE'],
        default      => ['label' => strtoupper($ext), 'color' => '#5F5E5A', 'bg' => '#F1EFE8'],
    };
}

/** Normalizar extensión a tipo base */
function normalizarTipo(string $ext): string
{
    return match(strtolower($ext)) {
        'docx' => 'doc',
        'pptx' => 'ppt',
        'xlsx' => 'xls',
        default => strtolower($ext),
    };
}

/** Construir URL con parámetros GET modificados */
function buildUrl(array $extra = [], array $remove = []): string
{
    $params = $_GET;
    foreach ($remove as $k) unset($params[$k]);
    foreach ($extra as $k => $v) $params[$k] = $v;
    unset($params['p']); // reset página al cambiar filtros
    $qs = http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== '0' && $v !== 0));
    return '?' . $qs;
}

/** Sanitizar nombre de archivo para guardarlo */
function sanitizarNombreArchivo(string $titulo, string $ext): string
{
    $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $titulo);
    $base = strtolower($base);
    $base = preg_replace('/[^a-z0-9]+/', '-', $base);
    $base = trim($base, '-');
    $base = substr($base, 0, 60);
    return $base . '-' . date('Ymd') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
}

/** Mensaje flash (guardar en sesión para mostrar tras redirect) */
function flashSet(string $tipo, string $msg): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'msg' => $msg];
}

function flashGet(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** CSRF token */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function csrfVerify(): bool
{
    $token = $_POST['csrf_token']  ?? $_GET['csrf_token'] ?? '';
    return hash_equals(csrfToken(), $token);
}

/** Validar CSRF o abortar */
function csrfCheck(): void
{
    if (!csrfVerify()) {
        http_response_code(403);
        die('Token de seguridad inválido. Vuelve atrás e intenta de nuevo.');
    }
}

/** Paginación: calcular offset y total de páginas */
function paginar(int $total, int $porPagina, int $pagina): array
{
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina       = max(1, min($pagina, $totalPaginas));
    $offset       = ($pagina - 1) * $porPagina;
    return ['pagina' => $pagina, 'totalPaginas' => $totalPaginas, 'offset' => $offset];
}

/** Etiqueta de rol legible */
function labelRol(string $rol): string
{
    return match($rol) {
        'superadmin' => 'Superadministrador',
        'editor'     => 'Editor',
        'viewer'     => 'Visor',
        default      => ucfirst($rol),
    };
}
