<?php

ini_set('display_errors', true);
// error_reporting(E_ALL);

$pagetitle = "no pagetitle set";

// --- Detect environment early (needed for cookie flags) ---
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1');

// --- Harden session cookie before session_start ---
// HttpOnly: blocks JS from reading the cookie (mitigates XSS cookie theft)
// SameSite=Lax: blocks the cookie from being sent on cross-site POST requests (CSRF defence layer 1)
// Secure: only send cookie over HTTPS — disabled on localhost so dev still works
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !$isLocal,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

// --- CSRF token: generate once per session ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verify CSRF token submitted as a hidden form field (for normal HTML forms)
function csrf_verify_post(): void
{
    $submitted = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

// Verify CSRF token submitted as a request header (for fetch() / XHR calls)
function csrf_verify_header(): void
{
    $submitted = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted)) {
        http_response_code(403);
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => 'Invalid CSRF token.']));
    }
}

require_once __DIR__ . "/i18n.php";

require_once __DIR__ . "/config.php";

if (! $DB_NAME) {
    die('please create config.php, define $DB_NAME, $DSN, $DB_USER, $DB_PASS there. See config_sample.php');
}

try {
    $dbh = new PDO($DSN, $DB_USER, $DB_PASS);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (Exception $e) {
    die("Problem connecting to database $DB_NAME as $DB_USER: " . $e->getMessage());
}

//       is https set? (either unset, or is set to 'off', by Apache server for example)

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $scheme = 'https';
} else {
    $scheme = 'http';
}

// $host and $isLocal are already set above (before session_start)

if ($isLocal) {
    // php -S serves the project root directly (no XAMPP htdocs subfolder),
    // so there's no URL prefix locally anymore.
    $subpath = '';
} else {
    $subpath = '/~fhs54132/inksync_v3';
}

define('BASE_URL', $scheme . '://' . $host . $subpath);

$role = $_SESSION['role'] ?? 'guest';

// --- Privacy policy / terms-of-use consent gate ---
// Single choke point: every page requires bootstrap.php first, so this is
// the one place that needs to know about it instead of duplicating a check
// across pages/*.php. /consent, /login, /logout and /privacy are exempt so
// the gate page and the policy text itself stay reachable.
require_once __DIR__ . '/consent.php';

$requestUri    = $_SERVER['REQUEST_URI'] ?? '/';
$isExemptRoute = (bool) preg_match('#/(consent|login|logout|privacy)(/|\?|$)#', $requestUri);

if (!$isExemptRoute && isset($_SESSION['USER']) && !user_has_consented()) {
    header('Location: ' . BASE_URL . '/consent');
    exit;
}

// // zwei verschiedene Formatter
// $day_short = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
// $day_long = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
// $day_db = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);

// // Formatierung nach http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details
// $day_short->setPattern('d. LLL');
// $day_long->setPattern('EEEE d. LLLL yyyy');
// $day_db->setPattern('yyyy-LL-dd');
