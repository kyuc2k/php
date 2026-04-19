<?php
session_start();

require "config.php";

$user_id=getUser();

$q=$conn->query("SELECT status FROM users WHERE id=$user_id");
$user=$q->fetch_assoc();

echo json_encode($user);