<?php

require '../nso/config.php';

if($_POST){

$u=$_POST['username'];
$p=$_POST['password'];

$q=$conn->query("
SELECT * FROM users
WHERE username='$u'
");

$user=$q->fetch_assoc();

if(password_verify($p,$user['password'])){

$_SESSION['user']=$user;

header("Location: ../nso/dashboard.php");

}

}
?>

<form method="post">

<input name="username">

<input type="password" name="password">

<button>Login</button>

</form>