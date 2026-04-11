<?php

session_start();

function loadEnvFile(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2) + [1 => '']);
        $value = trim($value, "\"'");

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

loadEnvFile(__DIR__ . '/.env');

$client_id = getenv('CLIENT_ID') ?: '';
$client_secret = getenv('CLIENT_SECRET') ?: '';
$redirect_uri = getenv('REDIRECT_URI') ?: '';

$conn = new mysqli(
    getenv('DB_HOST') ?: '',
    getenv('DB_USER') ?: '',
    getenv('DB_PASSWORD') ?: '',
    getenv('DB_NAME') ?: ''
);

if ($conn->connect_error) {
    die("DB Error");
}