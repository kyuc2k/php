<?php

require "nso/config.php";

if($_POST){

$username=$_POST['username'];
$password=$_POST['password'];

$q=$conn->query("SELECT * FROM users WHERE username='$username'");
$user=$q->fetch_assoc();

if(password_verify($password,$user['password'])){

$_SESSION['user_id']=$user['id'];

header("Location: dashboard.php");

}

}

?>

<form method="post">

<input name="username">
<input name="password" type="password">

<button>Login</button>

</form>