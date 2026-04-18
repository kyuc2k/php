<?php
session_start();
require 'config.php';

$user=$_SESSION['user'];

$res=$conn->query("
SELECT * FROM instances
WHERE user_id=".$user['id']
);


if ($res->num_rows == 0) {
    die("Invalid or expired session");
}

// redirect sang noVNC
header("Location: vnc_gate.php");
exit;