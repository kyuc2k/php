<?php

require "nso/config.php";

if($_POST){

$username=$_POST['username'];
$password=password_hash($_POST['password'],PASSWORD_DEFAULT);

$port=6000+rand(1,500);
$display=rand(1,500);

$conn->query("INSERT INTO users(username,password,port,display_id)
VALUES('$username','$password','$port','$display')");

echo "Register success <a href=nso/login.php>Login</a>";
}

?>

<form method="post">

<input name="username" placeholder="Username">
<input name="password" type="password">

<button>Register</button>

</form>