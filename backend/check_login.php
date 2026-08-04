<?php

function validate_password($password)
{
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
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

    // Brute-force lockout: 5 consecutive failed attempts locks the
    // username out for 15 minutes. Keyed by username (not user_id) so a
    // nonexistent username still gets tracked/locked the same as a real
    // one -- otherwise an attacker could tell which usernames are valid
    // just by noticing which ones never lock out.
    const LOGIN_MAX_ATTEMPTS  = 5;
    const LOGIN_LOCKOUT_MINS  = 15;

    // Returns minutes remaining if locked, or null if not locked.
    // Also clears an expired lockout so the account gets a clean slate.
function login_lockout_minutes_left(string $username): ?int
{
    global $dbh;
    $stmt = $dbh->prepare("SELECT locked_until FROM login_attempts WHERE username = ?");
    $stmt->execute([$username]);
    $row = $stmt->fetch();

    if (!$row || $row->locked_until === null) {
        return null;
    }

    $seconds_left = strtotime($row->locked_until) - time();
    if ($seconds_left <= 0) {
        // Lockout expired -- reset so the next attempt starts fresh.
        $dbh->prepare("UPDATE login_attempts SET failed_count = 0, locked_until = NULL WHERE username = ?")
            ->execute([$username]);
        return null;
    }

    return (int) ceil($seconds_left / 60);
}

function record_failed_login(string $username): void
{
    global $dbh;
    $stmt = $dbh->prepare(
        "INSERT INTO login_attempts (username, failed_count, locked_until)
             VALUES (?, 1, NULL)
             ON CONFLICT (username) DO UPDATE SET
                 failed_count = login_attempts.failed_count + 1,
                 locked_until = CASE
                     WHEN login_attempts.failed_count + 1 >= " . LOGIN_MAX_ATTEMPTS . "
                     THEN NOW() + INTERVAL '" . LOGIN_LOCKOUT_MINS . " minutes'
                     ELSE login_attempts.locked_until
                 END"
    );
    $stmt->execute([$username]);
}

function record_successful_login(string $username): void
{
    global $dbh;
    $dbh->prepare("DELETE FROM login_attempts WHERE username = ?")->execute([$username]);
}

    // Returns:
    //   [true, $role, $user_id]  on success
    //   ['locked', $minutesLeft] if the account is currently locked out
    //   false                    on wrong username/password
function check_login(string $username, string $password)
{
    global $dbh;

    $locked_minutes = login_lockout_minutes_left($username);
    if ($locked_minutes !== null) {
        return ['locked', $locked_minutes];
    }

    $stmt = $dbh->prepare("SELECT password, role, user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $output = $stmt->fetch();

    // No such user, or wrong password -- treat identically so we don't
    // leak which usernames exist via timing/response differences.
    if (!$output || !password_verify($password, $output->password)) {
        record_failed_login($username);
        return false;
    }

    record_successful_login($username);
    return [true, $output->role, $output->user_id];
}
