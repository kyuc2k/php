<?php
session_start();

require 'config.php';

$user=$_SESSION['user'];

$res=$conn->query("
SELECT * FROM instances
WHERE user_id=".$user['id']
);

?>

<a href="create_vm.php">Create VPS</a>

<hr>

<?php while($row=$res->fetch_assoc()){ ?>

<div>

Container: <?= $row['container_name'] ?>

Status: <?= $row['status'] ?>

<a href="start.php?id=<?= $row['id'] ?>">Start</a>

<a href="stop.php?id=<?= $row['id'] ?>">Stop</a>

<a href="http://103.245.236.153:<?= $row['port'] ?>/vnc.html" target="_blank">Open VPS</a>

</div>

<?php } ?>