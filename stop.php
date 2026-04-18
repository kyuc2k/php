<?php
session_start();
require 'config.php';
$id=$_GET['id'];

$q=$conn->query("
SELECT * FROM instances
WHERE id=$id
");

$vm=$q->fetch_assoc();

shell_exec("docker stop ".$vm['container_name']);

$conn->query("
UPDATE instances
SET status='stopped'
WHERE id=$id
");

header("Location: dashboard.php");