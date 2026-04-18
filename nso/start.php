<?php
require '../nso/config.php';
$id=$_GET['id'];

$q=$conn->query("
SELECT * FROM instances
WHERE id=$id
");

$vm=$q->fetch_assoc();

shell_exec("docker start ".$vm['container_name']);

$conn->query("
UPDATE instances
SET status='running'
WHERE id=$id
");

header("Location: ../nso/dashboard.php");