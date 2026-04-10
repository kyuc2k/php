<?php

require 'config.php';

$google_login_url = "https://accounts.google.com/o/oauth2/auth?"
    . "client_id=".$client_id
    . "&redirect_uri=".$redirect_uri
    . "&response_type=code"
    . "&scope=email profile"
    . "&access_type=online";

?>

<a href="<?= $google_login_url ?>">
Login with Google
</a>