<?php

session_start();

ini_set('display_errors', false);
// error_reporting(E_ALL);

$pagetitle = "no pagetitle set";

require_once __DIR__ . "/config.php";

if ( ! $DB_NAME ) die ('please create config.php, define $DB_NAME, $DSN, $DB_USER, $DB_PASS there. See config_sample.php');

try {
    $dbh = new PDO($DSN, $DB_USER, $DB_PASS);
    $dbh->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

} catch (Exception $e) {
    die ("Problem connecting to database $DB_NAME as $DB_USER: " . $e->getMessage() );
}

//       is https set? (either unset, or is set to 'off', by Apache server for example)

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $scheme = 'https';
} else {
    $scheme = 'http';
}

$host = $_SERVER['HTTP_HOST'];

if (str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1')) {
    $isLocal = true;
} else {
    $isLocal = false;
}

if ($isLocal) {
    $subpath = '/inksync';
} else { 
    $subpath = '/~fhs54132/mmp1'; 
}

define('BASE_URL', $scheme . '://' . $host . $subpath);

$role = $_SESSION['role'] ?? 'guest';

// // zwei verschiedene Formatter
// $day_short = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
// $day_long = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
// $day_db = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);

// // Formatierung nach http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details
// $day_short->setPattern('d. LLL');
// $day_long->setPattern('EEEE d. LLLL yyyy');
// $day_db->setPattern('yyyy-LL-dd');