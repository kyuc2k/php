<?php

session_start();
require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
}

$user = $_SESSION['user'];

?>

<h2><?= $user['name'] ?></h2>

<img src="<?= $user['picture'] ?>">

<p><?= $user['email'] ?></p>

<a href="upload.php">Upload PDF</a> | <a href="logout.php">Logout</a>