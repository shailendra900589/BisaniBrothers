<?php

function locale_start_output_buffer(): void
{
    if (ob_get_level() > 0) {
        locale_wrap_existing_buffers();
        return;
    }
    ob_start('locale_filter_output');
}

/**
 * When a page already called ob_start(), attach translation at shutdown.
 */
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
        $html = implode('', array_reverse($chunks));
        echo locale_filter_output($html);
    });
}

function locale_filter_output(string $html): string
{
    if (locale_current() === LOCALE_DEFAULT || $html === '') {
        return $html;
    }

    $map = locale_get_replacement_map();
    if (empty($map)) {
        return $html;
    }

    uksort($map, static fn($a, $b) => strlen($b) <=> strlen($a));
    return str_replace(array_keys($map), array_values($map), $html);
}

function locale_get_replacement_map(): array
{
    locale_init();
    return $GLOBALS['_locale_replacements'] ?? [];
}
