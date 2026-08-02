<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/check_login.php';

if (!isset($_SESSION['USER'])) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

if (user_has_consented()) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_post();
    $decision = $_POST['decision'] ?? '';

    if ($decision === 'accept') {
        record_consent((int) $_SESSION['USER']['user_id']);
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    // Decline (or anything unexpected) -- log out. These are hand-picked
    // test accounts; there is no legitimate path that keeps someone signed
    // in without accepting, so end the session cleanly and explain why on
    // the login page (same teardown as pages/logout.php).
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    session_destroy();
    header('Location: ' . BASE_URL . '/login?declined=1');
    exit;
}

$lang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkSync — <?= $lang === 'de' ? 'Zustimmung erforderlich' : 'Consent required' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/styles.css">
</head>
<body>
<section class="main-section">
    <div class="card" style="max-width: 640px; margin: 3rem auto;">

        <?php if ($lang === 'de'): ?>
            <h2>Bevor du fortfährst</h2>
            <p>InkSync ist ein Studierendenprojekt der FH Salzburg und befindet sich aktuell in einer Testphase mit ausgewählten Testpersonen. Bitte lies unsere <a href="<?= BASE_URL ?>/privacy" target="_blank">Datenschutzerklärung</a> und bestätige Folgendes, um fortzufahren:</p>
            <ul>
                <li>Ich habe die Datenschutzerklärung gelesen und verstanden — insbesondere, dass meine Daten auf einem Server der FH Salzburg verarbeitet und Bilder/Szenenbeschreibungen zur Bildgenerierung an Replicate Inc. (USA) übermittelt werden.</li>
                <li>Ich werde nur Referenzbilder hochladen, an denen ich die nötigen Rechte besitze (z. B. eigene Fotos/Skizzen oder Bilder mit Einverständnis der abgebildeten Person).</li>
                <li>Mir ist bekannt, dass im Rahmen dieser Testphase generierte Panels <strong>nicht für kommerzielle Zwecke</strong> verwendet werden dürfen, sofern nicht ausdrücklich schriftlich anders vereinbart.</li>
                <li>Meine Teilnahme an dieser Testphase ist freiwillig.</li>
            </ul>
        <?php else: ?>
            <h2>Before you continue</h2>
            <p>InkSync is a student project at FH Salzburg, currently in a testing phase with selected test users. Please read our <a href="<?= BASE_URL ?>/privacy" target="_blank">Privacy Policy</a> and confirm the following to continue:</p>
            <ul>
                <li>I have read and understood the Privacy Policy — in particular, that my data is processed on an FH Salzburg server, and that images/scene descriptions are sent to Replicate Inc. (USA) for image generation.</li>
                <li>I will only upload reference images I have the rights to use (e.g. my own photos/sketches, or images where the depicted person has consented).</li>
                <li>I understand that panels generated during this testing phase may <strong>not be used for commercial purposes</strong> unless explicitly agreed otherwise in writing.</li>
                <li>My participation in this testing phase is voluntary.</li>
            </ul>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/consent">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="field">
                <button type="submit" name="decision" value="accept"><?= $lang === 'de' ? 'Zustimmen & fortfahren' : 'Accept & continue' ?></button>
                <button type="submit" name="decision" value="decline"><?= $lang === 'de' ? 'Ablehnen' : 'Decline' ?></button>
            </div>
        </form>
    </div>
</section>
</body>
</html>
