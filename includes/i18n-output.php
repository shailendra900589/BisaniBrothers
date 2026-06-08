<?php

function locale_start_output_buffer(): void
{
    if (ob_get_level() > 0) {
        locale_wrap_existing_buffers();
        return;
    }
    ob_start('locale_filter_output');
}

function locale_wrap_existing_buffers(): void
{
    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;

    register_shutdown_function(static function (): void {
        if (locale_current() === LOCALE_DEFAULT) {
            while (ob_get_level() > 0) {
                echo ob_get_clean();
            }
            return;
        }

        $chunks = [];
        while (ob_get_level() > 0) {
            $chunks[] = ob_get_clean();
        }
        echo locale_filter_output(implode('', array_reverse($chunks)));
    });
}

function locale_filter_output(string $html): string
{
    if (locale_current() === LOCALE_DEFAULT || $html === '') {
        return $html;
    }

    $map = locale_get_replacement_map();
    if ($map === []) {
        return $html;
    }

    $protected = [];
    $html = preg_replace_callback(
        '/<(script|style|noscript|textarea|pre|code)\b[^>]*>.*?<\/\1>/is',
        static function (array $matches) use (&$protected): string {
            $token = '%%BBI18N' . count($protected) . '%%';
            $protected[$token] = $matches[0];

            return $token;
        },
        $html
    ) ?? $html;

    uksort($map, static fn($a, $b) => strlen($b) <=> strlen($a));
    $html = str_replace(array_keys($map), array_values($map), $html);

    return $protected !== [] ? strtr($html, $protected) : $html;
}

function locale_get_replacement_map(): array
{
    locale_init();

    return $GLOBALS['_locale_replacements'] ?? [];
}
