<?php
    session_start();
    ini_set('display_errors', true);

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



    // // zwei verschiedene Formatter
    // $day_short = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
    // $day_long = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
    // $day_db = new IntlDateFormatter('de_DE', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);

    // // Formatierung nach http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details
    // $day_short->setPattern('d. LLL');
    // $day_long->setPattern('EEEE d. LLLL yyyy');
    // $day_db->setPattern('yyyy-LL-dd');

    // lookup set of common passwords
    // O(1) lookup instead of O(n)!!!
    // all words are lowercase
    $common_passwords = array_flip(
    array_map('strtolower', file(__DIR__ . '/security/10k_common_passwords.txt'))
    );

    // $common_passwords = file(
    //     __DIR__ . '/security/10k_common_passwords.txt',
    //     FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    // );

    function validate_password($password) {
        // Define the password pattern
        // ^               : Start of string
        // (?=.*[A-Z])     : At least one uppercase letter
        // (?=.*[a-z])     : At least one lowercase letter
        // (?=.*\d)        : At least one digit
        // (?=.*[@$!%*?&]) : At least one special character
        // [A-Za-z\d@$!%*?&]{8,} : Password must be at least 8 characters long and only contain allowed characters
        // $               : End of string
        $pattern = '/^[A-Za-z\d@$!%*?&]{8,64}$/';

        if (preg_match($pattern, $password)) {
            if (!isset($common_passwords[strtolower($password)])) {
                return true;
                echo " password: " . $password . " is not a common password! ";
            } else {
                echo "weak password! ";
                return false;
            }
        } else {
            echo " password must be 8 - 64 character long! ";
            return false;
        }
    }


    // function check_login($username, $password) {
    //     $stmt = $dbh->prepare("");

    //     else {
    //         return false;
    //     }
    // }

?>
