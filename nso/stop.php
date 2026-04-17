<?php

require "nso/config.php";

$user_id=getUser();

$q=$conn->query("SELECT * FROM users WHERE id=$user_id");
$user=$q->fetch_assoc();

$display=$user['display_id'];

exec("bash /opt/java-cloud/stop.sh $display");

$conn->query("UPDATE users SET status='stopped' WHERE id=$user_id");

echo json_encode([
"status"=>"stopped"
]);