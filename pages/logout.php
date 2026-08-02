<?php
require_once __DIR__ . '/../backend/bootstrap.php';

$_SESSION = [];

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

session_destroy();

// Use the clean-URL route (/login), not the raw pages/login.php path --
// the raw path bypasses .htaccess rewriting and can 404 depending on
// server config / direct-access rules.
header("Location: " . BASE_URL . "/login");
exit;