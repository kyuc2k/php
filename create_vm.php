<?php
session_start();

require 'config.php';

$user = $_SESSION['user'];

$userId = (int)$user['id'];
$time = time();

$port = rand(6001, 7000);
$name = "vm_{$userId}_{$time}";

// ===== PATH GAME RI�NG CHO USER =====
$basePath = "/data/vms/user_" . $userId;

if (!is_dir($basePath)) {
    mkdir($basePath, 0755, true);
}

$userPath = "user_" . $userId;

// ===== DOCKER COMMAND =====
$cmd = "docker run -d --name $name -p $port:6001 -v /data/vms/$userPath:/app/game --memory=512m --cpus=1 micro-saas 2>&1";

// ch?y docker
$output = shell_exec($cmd);


// ki?m tra l?i
if ($output && !str_contains($output, 'error') && !str_contains($output, 'Error')) {

    $stmt = $conn->prepare("
        INSERT INTO instances (user_id, container_name, port, status)
        VALUES (?, ?, ?, 'running')
    ");

    $stmt->bind_param("isi", $userId, $name, $port);
    $stmt->execute();

    $token = bin2hex(random_bytes(16));
    $expire = date("Y-m-d H:i:s", time() + 3600); // 1h

    $conn->query("
        INSERT INTO vm_sessions (user_id, vm_name, token, expires_at)
        VALUES ({$user['id']}, '$name', '$token', '$expire')
    ");

    header("Location: dashboard.php");
    exit;

} else {

    echo "<pre>";
    echo "CMD:\n$cmd\n\n";
    echo "OUTPUT:\n";
    var_dump($output);
    echo "</pre>";

    echo "Docker create failed";
}