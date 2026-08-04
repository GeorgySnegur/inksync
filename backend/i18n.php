<?php

// i18n.php — minimal translation framework.
//
// How it works:
//   - $_SESSION['lang'] holds the active language for this user ('en' by
//     default). It's only ever changed by an explicit choice: the language
//     switcher in the header, or clicking "Yes" on the auto-detect popup.
//   - should_prompt_german() decides whether to show a one-time popup asking
//     German-speaking visitors (detected via the browser's Accept-Language
//     header) if they'd like to switch.
//   - t('some.key') looks up the string in lang/{lang}.php, falling back to
//     English and then to the raw key if nothing is found, so a missing
//     translation is visible/harmless instead of fatal.
//
// To add another language: copy lang/en.php to lang/{code}.php, translate
// the values, and add {code} to SUPPORTED_LANGS below.

const SUPPORTED_LANGS = ['en', 'de'];
const DEFAULT_LANG     = 'en';

// 1. Pick the active language for this session.
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], SUPPORTED_LANGS, true)) {
    $_SESSION['lang'] = DEFAULT_LANG;
}

// 2. Explicit switch via ?setlang=de (used by the header switcher and the
//    popup's "Yes" button). Once a user has chosen, we stop guessing.
if (isset($_GET['setlang']) && in_array($_GET['setlang'], SUPPORTED_LANGS, true)) {
    $_SESSION['lang']             = $_GET['setlang'];
    $_SESSION['lang_prompt_seen'] = true;
}

// 3. Dismissing the popup without switching also stops it from asking again.
if (isset($_GET['dismiss_lang_prompt'])) {
    $_SESSION['lang_prompt_seen'] = true;
}

// 4. Load the active language file (+ English as a fallback for missing keys).
$GLOBALS['LANG'] = require __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';
$GLOBALS['LANG_FALLBACK'] = $_SESSION['lang'] === DEFAULT_LANG
    ? $GLOBALS['LANG']
    : require __DIR__ . '/../lang/' . DEFAULT_LANG . '.php';

// Translate a key, with optional {placeholder} substitution, e.g.
//   t('storyboard.page_of', ['current' => 2, 'total' => 5])
function t(string $key, array $replacements = []): string
{
    $str = $GLOBALS['LANG'][$key] ?? $GLOBALS['LANG_FALLBACK'][$key] ?? $key;
    foreach ($replacements as $k => $v) {
        $str = str_replace('{' . $k . '}', (string)$v, $str);
    }
    return $str;
}

// Should we show the "Translate to German?" popup on this page load?
// Only when: the browser's primary language is German, the user hasn't
// already made an explicit language choice, and we haven't asked (and been
// dismissed) yet this session.
function should_prompt_german(): bool
{
    if (($_SESSION['lang_prompt_seen'] ?? false) === true) {
        return false;
    }
    if ($_SESSION['lang'] !== DEFAULT_LANG) {
        return false;
    }

    $accept  = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $primary = strtolower(trim(explode(',', $accept)[0] ?? ''));
    return str_starts_with($primary, 'de'); // matches de, de-AT, de-DE, de-CH...
}

// Builds a URL that re-requests the current page with ?setlang={code} set,
// preserving any other query parameters (e.g. project_id, page).
function lang_switch_url(string $code): string
{
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    $parts  = parse_url($uri);
    $query  = [];
    parse_str($parts['query'] ?? '', $query);
    $query['setlang'] = $code;
    return ($parts['path'] ?? '/') . '?' . http_build_query($query);
}

// Builds a URL that re-requests the current page with ?dismiss_lang_prompt=1
// set, preserving any other query parameters.
function dismiss_lang_prompt_url(): string
{
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    $parts  = parse_url($uri);
    $query  = [];
    parse_str($parts['query'] ?? '', $query);
    $query['dismiss_lang_prompt'] = 1;
    return ($parts['path'] ?? '/') . '?' . http_build_query($query);
}
