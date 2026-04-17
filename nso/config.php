<?php

session_start();

$conn = new mysqli(
    getenv('DB_HOST') ?: '',
    getenv('DB_USER') ?: '',
    getenv('DB_PASSWORD') ?: '',
    getenv('DB_NAME_NSO') ?: ''
);

if($conn->connect_error){
die("DB Error");
}

function getUser(){
return $_SESSION['user_id'] ?? 0;
}

?>