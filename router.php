<?php

// Dev-only router for PHP's built-in server (`php -S`).
// php -S doesn't read .htaccess, so this mirrors the clean-URL rules from
// the root .htaccess by hand. Not used by Apache/the uni server -- there,
// .htaccess handles this instead. Keep the two in sync if routes change.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Let real files/directories pass through untouched, same as the
// .htaccess "-f [OR] -d" passthrough rule.
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

$routes = [
    '/login'    => '/pages/login.php',
    '/projects' => '/pages/projects.php',
    '/admin'    => '/pages/admin_panel.php',
    '/logout'   => '/pages/logout.php',
    '/consent'  => '/pages/consent.php',
    '/privacy'  => '/pages/privacy.php',
];

$path = rtrim($uri, '/');
if ($path === '') {
    $path = '/';
}

if (isset($routes[$path])) {
    require __DIR__ . $routes[$path];
    return true;
}

require __DIR__ . '/index.php';
