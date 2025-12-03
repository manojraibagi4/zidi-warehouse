<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for a new language selection via GET and update the session.
// This is the user-driven change.
if (isset($_GET['lang'])) {
    $newLang = $_GET['lang'];
    // Validate the language code to prevent file path manipulation.
    if (in_array($newLang, ['en', 'de'])) {
        $_SESSION['lang'] = $newLang;
        // Redirect to the current page to remove the 'lang' parameter from the URL.
        // This prevents the language from being reset on page refresh.
        $queryString = http_build_query(array_diff_key($_GET, ['lang' => '']));
        header('Location: ' . $_SERVER['PHP_SELF'] . ($queryString ? '?' . $queryString : ''));
        exit();
    }
}

// Get the current language code.
// Prioritize the session value, then the database default (from app.php),
// and finally fall back to 'en'.
// The default language from app.php will be set in the session via header.php.
$config = require __DIR__ . '/../src/config/app.php';
$lang_code = $_SESSION['lang'] ?? $config['default_language'] ?? 'en';

$langFile = __DIR__ . '/../src/lang/' . $lang_code . '.php';

if (!file_exists($langFile)) {
    // Fallback to English if the selected language file does not exist.
    $langFile = __DIR__ . '/../src/lang/en.php';
}

$langData = include $langFile;

/**
 * Returns the translated string for a given key.
 *
 * @param string $key The language key to look up.
 * @return string The translated string or the key itself if not found.
 */
function lang($key)
{
    global $langData;
    return $langData[$key] ?? $key;
}