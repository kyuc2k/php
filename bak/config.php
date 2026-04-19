<?php
$conn = new mysqli(
    getenv('DB_HOST') ?: '',
    getenv('DB_USER') ?: '',
    getenv('DB_PASSWORD') ?: '',
    getenv('DB_NAME_NSO') ?: ''
);