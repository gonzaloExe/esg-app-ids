<?php
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'esg');
define('DB_USER', 'esg_user');
define('DB_PASS', 'campos480');

define('APP_NAME', 'ESG - Entorno Seguro y Gestión');
define('APP_VERSION', '2.1');
define('AGENT_PORT', 17890);

date_default_timezone_set('America/Argentina/Buenos_Aires');
ini_set('session.cookie_httponly','1');
ini_set('session.use_only_cookies','1');
if (session_status() === PHP_SESSION_NONE) session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]
    );
    return $pdo;
}
function clean($v, int $max=255): string {
    return mb_substr(trim((string)$v), 0, $max);
}
function now(): string { return date('Y-m-d H:i:s'); }
function jsonResponse(bool $ok, $data=null, string $message='', int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>$ok,'data'=>$data,'message'=>$message], JSON_UNESCAPED_UNICODE);
    exit;
}
