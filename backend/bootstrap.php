
<?php

session_start();

ini_set('display_errors', false);

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

// define('BASE_URL', isset($_SERVER['HTTPS']) ? 'https://' . $_SERVER['HTTP_HOST'] . '/~fhs54132/mmp1' : 'http://' . $_SERVER['HTTP_HOST'] . '/~fhs54132/mmp1');

// $constructed_url = '/';

// define('BASE_URL', $constructed_url);

$role = $_SESSION['role'] ?? 'guest';

// // zwei verschiedene Formatter
// $day_short = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
// $day_long = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
// $day_db = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);

// // Formatierung nach http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details
// $day_short->setPattern('d. LLL');
// $day_long->setPattern('EEEE d. LLLL yyyy');
// $day_db->setPattern('yyyy-LL-dd');