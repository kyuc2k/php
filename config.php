<?php

session_start();

$client_id = "";
$client_secret = "";
$redirect_uri = "http://localhost/google-callback.php";

$conn = new mysqli("localhost","root","Root@1234","google_login");

if ($conn->connect_error) {
    die("DB Error");
}