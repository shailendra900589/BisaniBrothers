<?php

function page_slug(?string $script = null): string
{
    $script = $script ?? basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
    return $script === '' ? 'index' : $script;
}

function page_t(string $key, ?string $fallback = null): string
{
    locale_init();
    $slug = page_slug();
    $pages = $GLOBALS['_locale_strings']['page'] ?? [];

    if (isset($pages[$slug][$key]) && is_string($pages[$slug][$key]) && $pages[$slug][$key] !== '') {
        return $pages[$slug][$key];
    }
    if (isset($pages['_common'][$key]) && is_string($pages['_common'][$key]) && $pages['_common'][$key] !== '') {
        return $pages['_common'][$key];
    }

    return $fallback ?? $key;
}

function page_te(string $key, ?string $fallback = null): string
{
    return htmlspecialchars(page_t($key, $fallback), ENT_QUOTES, 'UTF-8');
}
