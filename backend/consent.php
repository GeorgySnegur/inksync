<?php
// backend/consent.php — first-login privacy policy / terms-of-use consent gate.
//
// Bump CONSENT_VERSION whenever the policy text in pages/privacy.php changes
// in a way that matters (new processor, new data category, etc.) -- this
// re-prompts everyone, including users who already accepted an older
// version. Keep this string in sync with the "Stand / Last updated" line at
// the top of pages/privacy.php.
const CONSENT_VERSION = '2026-06-23';

// Has the currently logged-in user accepted the current policy version?
// Guests aren't gated here (login.php/the page itself decides what guests
// can see); only used once $_SESSION['USER'] exists.
// Result is cached in the session so most page loads don't touch the DB --
// record_consent() below keeps that cache in sync.
function user_has_consented(): bool {
    if (!isset($_SESSION['USER'])) {
        return true;
    }

    if (($_SESSION['USER']['consent_version'] ?? null) === CONSENT_VERSION) {
        return true;
    }

    global $dbh;
    $stmt = $dbh->prepare("SELECT consent_version FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['USER']['user_id']]);
    $version = $stmt->fetchColumn();

    if ($version === CONSENT_VERSION) {
        $_SESSION['USER']['consent_version'] = $version;
        return true;
    }

    return false;
}

// Records acceptance of the current policy version for the given user --
// DB is the source of truth, session is just a fast-path cache.
function record_consent(int $user_id): void {
    global $dbh;
    $stmt = $dbh->prepare("UPDATE users SET consent_accepted_at = NOW(), consent_version = ? WHERE user_id = ?");
    $stmt->execute([CONSENT_VERSION, $user_id]);
    $_SESSION['USER']['consent_version'] = CONSENT_VERSION;
}
