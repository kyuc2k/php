<?php

require "nso/config.php";

$user_id=getUser();

$q=$conn->query("SELECT * FROM users WHERE id=$user_id");
$user=$q->fetch_assoc();

$port=$user['port'];
$display=$user['display_id'];

exec("bash /opt/java-cloud/start.sh $user_id $port $display > /dev/null 2>&1 &");

$conn->query("UPDATE users SET status='running' WHERE id=$user_id");

echo json_encode([
"status"=>"running",
"url"=>"https://".$_SERVER['SERVER_NAME'].":".$port
]);