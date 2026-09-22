<?php
require_once dirname(__DIR__) . '/security_headers.php';
require_once dirname(__DIR__) . '/includes/security_utils.php';

date_default_timezone_set('Europe/Rome');


// Caricamento segreti: prima getenv, poi config/secrets.php se esiste
$secrets = [];
if (file_exists(__DIR__ . '/secrets.php')) {
    $secrets = include __DIR__ . '/secrets.php';
    if (!is_array($secrets)) $secrets = [];
}

function app_secret($key, $default = null) {
    // getenv ha priorità, poi secrets.php, poi default
    $env = getenv($key);
    if ($env !== false && $env !== '') return $env;
    global $secrets;
    if (isset($secrets[$key]) && $secrets[$key] !== '') return $secrets[$key];
    return $default;
}

$appEnv = app_secret('APP_ENV', 'production');
$isDebugEnv = ($appEnv === 'local' || $appEnv === 'development');

error_reporting(E_ALL);
ini_set('display_errors', $isDebugEnv ? '1' : '0');
ini_set('log_errors', '1');

app_start_secure_session();

// DB config: in localhost/CLI possiamo usare default; online richiediamo secrets/env
$isLocal = (
    (($_SERVER['SERVER_NAME'] ?? '') === 'localhost') ||
    (($_SERVER['SERVER_NAME'] ?? '') === '127.0.0.1') ||
    (($_SERVER['SERVER_ADDR'] ?? '') === '127.0.0.1') ||
    (($_SERVER['SERVER_ADDR'] ?? '') === '::1') ||
    (php_sapi_name() === 'cli')
);

if ($isLocal) {
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = app_secret('DB_NAME', 'if0_41207556_moki');
} else {
    $host = app_secret('DB_HOST');
    $user = app_secret('DB_USER');
    $password = app_secret('DB_PASS');
    $database = app_secret('DB_NAME');
}

// Se online e manca config DB, fallisci in modo controllato
if (!$isLocal && (!$host || !$user || !$password || !$database)) {
    error_log('Database config missing: set DB_HOST/DB_USER/DB_PASS/DB_NAME via env or config/secrets.php');
    http_response_code(500);
    exit('Servizio temporaneamente non disponibile.');
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $user, $password, $database);

// Se in locale fallisce la connessione con il db di default, proviamo con 'clubmoki'
if ($conn->connect_error && $isLocal && $database !== 'clubmoki') {
    $database = 'clubmoki';
    $conn = @new mysqli($host, $user, $password, $database);
}

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    if (php_sapi_name() === 'cli') {
        exit("Database connection failed: " . $conn->connect_error . "\n");
    }
    http_response_code(500);
    exit('Servizio temporaneamente non disponibile.');
}

$conn->set_charset('utf8mb4');

try {
    $conn->query("SET time_zone = '+00:00'");
} catch (Throwable $e) {
}
?>