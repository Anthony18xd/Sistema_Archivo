<?php
/**
 * ARCHIVO: includes/helpers.php
 * FUNCIONES AUXILIARES DEL SISTEMA
 */

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function sanitizeArray(array $data): array {
    $clean = [];
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $clean[$key] = sanitize($value);
        } else {
            $clean[$key] = $value;
        }
    }
    return $clean;
}

function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function getPost(string $key, $default = ''): string {
    return isset($_POST[$key]) ? sanitize($_POST[$key]) : $default;
}

function getPostInt(string $key, int $default = 0): int {
    $value = getPost($key);
    return $value !== '' ? (int) $value : $default;
}

function getQuery(string $key, $default = ''): string {
    return isset($_GET[$key]) ? sanitize($_GET[$key]) : $default;
}

function setSession(string $key, $value): void {
    $_SESSION[$key] = $value;
}

function getSession(string $key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

function removeSession(string $key): void {
    unset($_SESSION[$key]);
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRF() . '">';
}

function verifyCSRF(): bool {
    if (!isPost()) return true;
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function dateFormat(string $date, string $format = 'd/m/Y'): string {
    return date($format, strtotime($date));
}

function dateTimeFormat(string $datetime, string $format = 'd/m/Y H:i'): string {
    return date($format, strtotime($datetime));
}

function timeFormat(string $time, string $format = 'H:i'): string {
    return date($format, strtotime($time));
}

function getFileUrl(string $path): string {
    return SITE_URL . '/' . ltrim($path, '/');
}

function isActivePage(string $page): string {
    $currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
    return ($currentPage === $page) ? 'active' : '';
}

function getIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function getUserAgent(): string {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function formatNumber(int $number): string {
    return number_format($number, 0, ',', '.');
}
