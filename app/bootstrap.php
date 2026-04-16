<?php
/**
 * Application Bootstrap
 * Loads environment, establishes DB connection, starts session, sets up autoloading.
 */

// Prevent double-loading
if (defined('APP_BOOTSTRAPPED')) return;
define('APP_BOOTSTRAPPED', true);

// Base path
define('BASE_PATH', dirname(__DIR__));

// ── Environment loader ──────────────────────────────────────────
function loadEnvFile(string $path): void
{
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) >= 2) {
            $name  = trim($parts[0]);
            $value = trim($parts[1], "\"'");
            putenv("$name=$value");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnvFile(BASE_PATH . '/.env');

// ── Google OAuth config ─────────────────────────────────────────
$client_id     = getenv('CLIENT_ID') ?: '';
$client_secret = getenv('CLIENT_SECRET') ?: '';
$redirect_uri  = getenv('REDIRECT_URI') ?: '';

// ── Database connection ─────────────────────────────────────────
$conn = new mysqli(
    getenv('DB_HOST') ?: '',
    getenv('DB_USER') ?: '',
    getenv('DB_PASSWORD') ?: '',
    getenv('DB_NAME') ?: ''
);

if ($conn->connect_error) {
    die("DB Error: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// ── Session ─────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Simple class autoloader ─────────────────────────────────────
spl_autoload_register(function (string $class): void {
    // Map namespace-style class names to file paths
    // e.g. "Models\User" → app/Models/User.php
    $directories = [
        BASE_PATH . '/app/Models/',
        BASE_PATH . '/app/Controllers/',
        BASE_PATH . '/app/Helpers/',
    ];
    foreach ($directories as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ── Helper: render view ─────────────────────────────────────────
function view(string $name, array $data = []): void
{
    extract($data);
    $viewFile = BASE_PATH . '/app/Views/' . $name . '.php';
    if (!file_exists($viewFile)) {
        http_response_code(500);
        die("View not found: $name");
    }
    require $viewFile;
}
