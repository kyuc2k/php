<?php
session_start();

if (!isset($_SESSION['vnc_token'])) {
    header("Location: login.php");
    exit;
}

$token = $_SESSION['vnc_token'];
?>
<!DOCTYPE html>
<html>
<head>
  <title>VNC</title>
</head>
<body>

<iframe 
  src="/vnc.html?token=<?php echo $token; ?>"
  style="width:100%;height:100vh;border:none;">
</iframe>

</body>
</html>