<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../components/functions.php'; // <--- PASTIKAN BARIS INI ADA!

session_unset();
session_destroy();
redirect(BASE_URL . '/auth/login.php');
?>