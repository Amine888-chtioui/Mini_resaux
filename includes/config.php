<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'ENSIASD');
}
if (!defined('SITE_PLACE')) {
    define('SITE_PLACE', 'Taroudant');
}
if (!defined('SITE_FULL_TITLE')) {
    define('SITE_FULL_TITLE', 'Réseau social — ' . SITE_NAME . ' · ' . SITE_PLACE);
}

if (!defined('CONTACT_PHONE_LABEL')) {
    define('CONTACT_PHONE_LABEL', '+212 XXX XXX XXX');
}
if (!defined('CONTACT_PHONE_TEL')) {
    define('CONTACT_PHONE_TEL', '+212XXXXXXXXX');
}
if (!defined('CONTACT_EMAIL')) {
    define('CONTACT_EMAIL', 'contact@ensiasd.ma');
}
if (!defined('CONTACT_WHATSAPP')) {
    define('CONTACT_WHATSAPP', '212XXXXXXXXX');
}

if (!defined('BASE_URL')) {
    // Configuration pour XAMPP Apache
    define('BASE_URL', '/Mini_resaux');
}

/** @param string $postValue Raw POST redirect value */
function app_redirect_target(string $postValue, string $default): string
{
    $r = trim($postValue);
    if ($r === '' || strpos($r, '//') !== false) {
        return $default;
    }
    if (BASE_URL === '') {
        return (isset($r[0]) && $r[0] === '/') ? $r : $default;
    }
    return strpos($r, BASE_URL) === 0 ? $r : $default;
}
