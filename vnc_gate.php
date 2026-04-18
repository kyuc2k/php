<?php
session_start();

require 'config.php';

$user=$_SESSION['user'];

$res = $conn->query("
    SELECT * FROM vm_sessions
    INNER JOIN instances on vm_sessions.user_id = instances.user_id
    WHERE vm_sessions.user_id=".$user['id']."
    AND vm_sessions.expires_at > NOW()
");

if ($res->num_rows == 0) {
    die("Invalid or expired session");
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>VNC</title>
</head>
<body>

<?php while($row=$res->fetch_assoc()){ ?>
    <iframe 
      src="http://103.245.236.153:<?= $row['port'] ?>/vnc.html"
      style="width:100%;height:100vh;border:none;">
    </iframe>
<?php } ?>

</body>
</html>