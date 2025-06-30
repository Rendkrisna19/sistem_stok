<?php
session_start();
// Redirect langsung ke halaman login
header("Location: auth/login.php");
exit();
?>