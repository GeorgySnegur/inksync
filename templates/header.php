<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/check_login.php';

$role = $_SESSION['role'] ?? 'guest';
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkSync — Storyboard Generator</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/styles.css">
    <!-- CSRF token for JS fetch() calls — read via document.querySelector('meta[name="csrf-token"]').content -->
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
</head>
<body>

<?php if (should_prompt_german()) : ?>
<div class="lang-popup" id="lang-popup">
    <span><?= htmlspecialchars(t('lang.popup_question')) ?></span>
    <a class="lang-popup-btn lang-popup-yes" href="<?= htmlspecialchars(lang_switch_url('de')) ?>"><?= htmlspecialchars(t('lang.popup_yes')) ?></a>
    <a class="lang-popup-btn lang-popup-no" href="<?= htmlspecialchars(dismiss_lang_prompt_url()) ?>"><?= htmlspecialchars(t('lang.popup_no')) ?></a>
</div>
<?php endif; ?>

<div class="lang-switcher-float">
    <a href="<?= htmlspecialchars(lang_switch_url('en')) ?>" class="<?= $_SESSION['lang'] === 'en' ? 'lang-active' : '' ?>">EN</a>
    <span>/</span>
    <a href="<?= htmlspecialchars(lang_switch_url('de')) ?>" class="<?= $_SESSION['lang'] === 'de' ? 'lang-active' : '' ?>">DE</a>
</div>

<header>
    <ul>
        <?php if ($role === 'admin') : ?>
            <li><a href="<?= BASE_URL ?>/admin"><?= htmlspecialchars(t('nav.admin_panel')) ?></a></li>
        <?php endif; ?>

        <?php if ($role !== 'guest' && $role !== null) : ?>
            <li><a href="<?= BASE_URL ?>/projects"><?= htmlspecialchars(t('nav.projects')) ?> (<?= htmlspecialchars($_SESSION['USER']['username']) ?>)</a></li>
        <?php endif; ?>

        <div>
            <li>
                <a href="<?= BASE_URL ?>/"><h1>InkSync</h1></a>
                <p><?= htmlspecialchars(t('nav.tagline')) ?></p>
            </li>
        </div>

        <?php if ($role !== 'guest' && $role !== null) : ?>
            <li><a href="<?= BASE_URL ?>/logout"><?= htmlspecialchars(t('nav.logout')) ?> (<?= htmlspecialchars($_SESSION['USER']['username']) ?>)</a></li>
        <?php else : ?>
            <li><a href="<?= BASE_URL ?>/login"><?= htmlspecialchars(t('nav.login')) ?></a></li>
        <?php endif; ?>
    </ul>
</header>
