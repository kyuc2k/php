<?php

require '../nso/config.php';

if($_POST){

$u=$_POST['username'];

$p=password_hash(
$_POST['password'],
PASSWORD_BCRYPT
);

$conn->query("
INSERT INTO users(username,password)
VALUES('$u','$p')
");

}
?>

<form method="post">

<input name="username">
<input name="password">

<button>Create User</button>

</form>