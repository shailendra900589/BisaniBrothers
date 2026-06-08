<?php

function bb_asset_version(string $relativePath): string
{
    $path = dirname(__DIR__) . '/' . ltrim($relativePath, '/');
    return file_exists($path) ? (string) filemtime($path) : '1';
}

function bb_stylesheet(string $file): string
{
    return 'assets/css/' . ltrim($file, '/') . '?v=' . bb_asset_version('assets/css/' . ltrim($file, '/'));
}

function bb_admin_stylesheet(): string
{
    return '../assets/css/admin.css?v=' . bb_asset_version('assets/css/admin.css');
}

function bb_admin_script(string $file): string
{
    return '../assets/' . ltrim($file, '/') . '?v=' . bb_asset_version('assets/' . ltrim($file, '/'));
}

/**
 * Load local CKEditor 4 for admin pages (suppresses insecure-version banner).
 */
function bb_ckeditor_admin_scripts(string $editorJs = '', array $extra = []): void
{
    ?>
    <script>window.CKEDITOR_BASEPATH = "../assets/ckeditor/";</script>
    <script src="<?php echo bb_admin_script('ckeditor/ckeditor.js'); ?>"></script>
    <script>if (window.CKEDITOR) { CKEDITOR.config.versionCheck = false; }</script>
    <?php
    foreach ($extra as $key => $value) {
        if (!is_string($key)) {
            continue;
        }
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
        echo '<script>window.' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . ' = ' . $json . ';</script>' . "\n";
    }
    if ($editorJs !== '') {
        echo '<script src="' . htmlspecialchars(bb_admin_script($editorJs), ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
    }
}
