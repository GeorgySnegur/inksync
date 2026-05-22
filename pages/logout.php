<?php
require_once __DIR__ . '/../backend/bootstrap.php';

$_SESSION = [];

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

session_destroy();

header("Location: " . BASE_URL . "/pages/login.php");
exit;