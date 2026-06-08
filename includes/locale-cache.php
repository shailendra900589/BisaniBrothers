<?php

function locale_cache_dir(): string
{
    return dirname(__DIR__) . '/lang/cache';
}

function locale_cache_file_mtime(array $files): int
{
    $mtime = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            $mtime = max($mtime, (int) filemtime($file));
        }
    }

    return $mtime;
}

/**
 * @template T
 * @param callable(): T $builder
 * @return T
 */
function locale_cache_remember(string $key, array $sourceFiles, callable $builder)
{
    $cacheFile = locale_cache_dir() . '/' . $key . '.php';
    $sourceMtime = locale_cache_file_mtime($sourceFiles);

    if (is_file($cacheFile) && filemtime($cacheFile) >= $sourceMtime) {
        $cached = require $cacheFile;
        if (is_array($cached)) {
            return $cached;
        }
    }

    $data = $builder();
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    file_put_contents(
        $cacheFile,
        '<?php return ' . var_export($data, true) . ';' . PHP_EOL,
        LOCK_EX
    );

    return $data;
}

function locale_replacement_source_files(string $code): array
{
    $root = dirname(__DIR__) . '/lang';

    return [
        "{$root}/en.php",
        "{$root}/{$code}.php",
        "{$root}/page-content/en.php",
        "{$root}/page-content/{$code}.php",
        "{$root}/overlays/{$code}.php",
        __FILE__,
    ];
}
