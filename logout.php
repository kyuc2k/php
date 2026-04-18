<?php
session_start();
session_destroy();
header("Location: nso/login.php");