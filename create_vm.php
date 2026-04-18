<?php

require 'config.php';

$user = $_SESSION['user'];

$userId = (int)$user['id'];
$time = time();

$port = rand(6001, 7000);
$name = "vm_{$userId}_{$time}";

// ===== PATH GAME RI�NG CHO USER =====
$basePath = "/data/vms/user_" . $userId;
//$gamePath = $basePath . "/THNJ.jar";

// t?o folder n?u chua c�
if (!is_dir($basePath)) {
    mkdir($basePath, 0755, true);
}

/**
 * IMPORTANT:
 * d?m b?o file game.jar d� t?n t?i
 * (upload tru?c d�)
 */
 
// if (!file_exists($gamePath)) {
//    die("Game jar not found for user");
//}

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