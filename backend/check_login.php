<?php

    function validate_password($password) {
        // Define the allowed password pattern
        // ^               : Start of string
        // [A-Za-z\d@$!%*?&]{8,} : Password must be at least 8 characters long and only contain allowed characters
        // $               : End of string
        $pattern = '/^[A-Za-z\d@$!%*?&]{8,64}$/';

        // define common_passwords (all lowercase)
        // lookup set of common passwords
        // O(1) lookup instead of O(n)!!!
        // all words are lowercase
        $common_passwords = array_flip(
        array_map('strtolower', file(__DIR__ . '/security/10k_common_passwords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
        );

        if (preg_match($pattern, $password)) {
            if (!(isset($common_passwords[strtolower($password)]))) {
                echo "Regestration Sucessful!";
                return true;
            } else {
                echo "Registration Unsuccessful - common password";
                return false;
            }
        } else {
            echo "Registration Unsuccessful - must be 8 - 64 characters long";
            return false;
        }
    }


    function check_login(string $username, string $password) {
        global $dbh;
        $stmt = $dbh->prepare("SELECT password, role, user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $output = $stmt->fetch();
        $hash = $output->password;
        $role = $output->role;
        $user_id = $output->user_id;

        if (password_verify($password, $hash)) {
            echo "login successful! ";
            return [true, $role, $user_id];
        } else {
            return false;
        }
    }

?>
