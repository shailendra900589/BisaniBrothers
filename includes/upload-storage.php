<?php
/**
 * Upload storage with IIS/Plesk fallback when httpdocs/uploads is not writable.
 * Physical files may live in vhost tmp; public URLs stay as uploads/... or upload-deliver.php.
 */

function upload_storage_project_root(): string
{
    return dirname(__DIR__);
}

function upload_storage_normalize_db_path(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $path = ltrim(str_replace(['../', './'], '', $path), '/');
    if ($path !== '' && !str_starts_with($path, 'uploads/')) {
        $path = 'uploads/' . ltrim($path, '/');
    }

    return $path;
}

function upload_storage_subpath(string $dbPath): string
{
    $dbPath = upload_storage_normalize_db_path($dbPath);

    return ltrim(substr($dbPath, strlen('uploads/')), '/');
}

/**
 * @return string[]
 */
function upload_storage_vhost_tmp_roots(): array
{
    $httpdocs = upload_storage_project_root();
    $vhost = dirname($httpdocs);
    $roots = [
        $vhost . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'bb-uploads',
        $vhost . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'uploads',
    ];

    $webConfig = $httpdocs . DIRECTORY_SEPARATOR . 'web.config';
    if (is_file($webConfig)) {
        $xml = @file_get_contents($webConfig);
        if (is_string($xml) && preg_match('/tempDirectory="([^"]+)"/i', $xml, $m)) {
            $temp = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $m[1]), DIRECTORY_SEPARATOR);
            $roots[] = $temp . DIRECTORY_SEPARATOR . 'bb-uploads';
            $roots[] = $temp . DIRECTORY_SEPARATOR . 'uploads';
        }
    }

    return array_values(array_unique($roots));
}

/**
 * @return string[]
 */
function upload_storage_candidate_roots(): array
{
    $httpdocs = upload_storage_project_root();

    return array_values(array_unique(array_merge(
        [$httpdocs . DIRECTORY_SEPARATOR . 'uploads'],
        upload_storage_vhost_tmp_roots(),
        [rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'bisanibrothers-uploads']
    )));
}

/**
 * @return string[]
 */
function upload_storage_root_cache_files(): array
{
    $httpdocs = upload_storage_project_root();
    $vhost = dirname($httpdocs);

    return [
        $vhost . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . '.bb-upload-root.txt',
        $httpdocs . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '.upload-root.txt',
    ];
}

function upload_storage_probe_dir_writable(string $dir): bool
{
    if (!is_dir($dir)) {
        return false;
    }

    $probe = $dir . DIRECTORY_SEPARATOR . '.write_probe_' . bin2hex(random_bytes(4));
    $written = @file_put_contents($probe, 'ok', LOCK_EX);
    if ($written === false) {
        return false;
    }

    @unlink($probe);

    return true;
}

function upload_storage_try_fix_permissions(string $dir): void
{
    if (DIRECTORY_SEPARATOR !== '\\' || !is_dir($dir)) {
        return;
    }

    $real = realpath($dir);
    if ($real === false || !function_exists('exec')) {
        return;
    }

    $grants = ['IIS_IUSRS:(OI)(CI)M', 'IUSR:(OI)(CI)M'];
    $pool = (string) ($_SERVER['APP_POOL_ID'] ?? getenv('APP_POOL_ID') ?: '');
    if ($pool !== '') {
        $grants[] = 'IIS AppPool\\' . $pool . ':(OI)(CI)M';
    }

    foreach ($grants as $grant) {
        @exec('icacls ' . escapeshellarg($real) . ' /grant ' . escapeshellarg($grant) . ' 2>&1');
    }
}

function upload_storage_read_cached_root(): ?string
{
    foreach (upload_storage_root_cache_files() as $cacheFile) {
        if (!is_file($cacheFile)) {
            continue;
        }
        $path = trim((string) file_get_contents($cacheFile));
        if ($path !== '' && is_dir($path) && upload_storage_probe_dir_writable($path)) {
            return $path;
        }
    }

    return null;
}

function upload_storage_write_cached_root(string $path): void
{
    foreach (upload_storage_root_cache_files() as $cacheFile) {
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (@file_put_contents($cacheFile, $path) !== false) {
            return;
        }
    }
}

function upload_storage_prepare_dir(string $dir): bool
{
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0775, true) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }
        upload_storage_try_fix_permissions($dir);
    }

    if (DIRECTORY_SEPARATOR !== '\\') {
        @chmod($dir, 0775);
        clearstatcache(true, $dir);
    }

    if (!upload_storage_probe_dir_writable($dir)) {
        upload_storage_try_fix_permissions($dir);
        clearstatcache(true, $dir);
    }

    $indexFile = $dir . DIRECTORY_SEPARATOR . 'index.html';
    if (!is_file($indexFile)) {
        @file_put_contents($indexFile, "<!DOCTYPE html><title></title>\n");
    }

    return upload_storage_probe_dir_writable($dir);
}

function upload_storage_active_root(bool $refresh = false): ?string
{
    static $cached = null;
    if (!$refresh && $cached !== null) {
        return $cached;
    }

    if (!$refresh) {
        $fromFile = upload_storage_read_cached_root();
        if ($fromFile !== null) {
            $cached = $fromFile;

            return $cached;
        }
    }

    foreach (upload_storage_candidate_roots() as $root) {
        if (!upload_storage_prepare_dir($root)) {
            continue;
        }
        upload_storage_write_cached_root($root);
        $cached = $root;

        return $cached;
    }

    $cached = null;

    return null;
}

/**
 * @return array{ok: bool, path: string, error: string, root: string}
 */
function upload_storage_ensure_dir(string $subDir = ''): array
{
    $root = upload_storage_active_root();
    if ($root === null) {
        $tried = implode(', ', upload_storage_candidate_roots());

        return [
            'ok'    => false,
            'path'  => upload_storage_project_root() . DIRECTORY_SEPARATOR . 'uploads',
            'error' => "No writable upload storage found. Tried: {$tried}. Grant Modify on uploads to IIS_IUSRS in Plesk, or ensure vhost tmp is writable.",
            'root'  => '',
        ];
    }

    $subDir = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $subDir), DIRECTORY_SEPARATOR);
    $dir = $subDir === '' ? $root : $root . DIRECTORY_SEPARATOR . $subDir;

    if (!upload_storage_prepare_dir($dir)) {
        upload_storage_active_root(true);

        return [
            'ok'    => false,
            'path'  => $dir,
            'error' => "Cannot write to upload folder: {$dir}",
            'root'  => $root,
        ];
    }

    return ['ok' => true, 'path' => $dir, 'error' => '', 'root' => $root];
}

function upload_storage_is_primary_root(string $root): bool
{
    $primary = upload_storage_project_root() . DIRECTORY_SEPARATOR . 'uploads';

    return realpath($root) === realpath($primary);
}

function upload_storage_resolve_file(string $dbPath): ?string
{
    $dbPath = upload_storage_normalize_db_path($dbPath);
    if ($dbPath === 'uploads/' || $dbPath === '') {
        return null;
    }

    $sub = upload_storage_subpath($dbPath);
    $httpdocs = upload_storage_project_root();
    $primary = $httpdocs . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dbPath);
    if (is_file($primary)) {
        return $primary;
    }

    foreach (upload_storage_candidate_roots() as $root) {
        $candidate = $root . ($sub === '' ? '' : DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sub));
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function upload_storage_public_url(string $dbPath): string
{
    $dbPath = upload_storage_normalize_db_path($dbPath);
    if ($dbPath === '' || $dbPath === 'uploads/') {
        return '';
    }

    $httpdocs = upload_storage_project_root();
    $primary = $httpdocs . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dbPath);
    if (is_file($primary)) {
        return $dbPath;
    }

    if (upload_storage_resolve_file($dbPath) !== null) {
        $sub = upload_storage_subpath($dbPath);

        return 'upload-deliver.php?f=' . rawurlencode($sub);
    }

    return $dbPath;
}

function upload_storage_mirror_to_primary(string $physicalPath, string $dbPath): void
{
    $dbPath = upload_storage_normalize_db_path($dbPath);
    $primary = upload_storage_project_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dbPath);
    if (is_file($primary)) {
        return;
    }

    $primaryDir = dirname($primary);
    if (!is_dir($primaryDir)) {
        @mkdir($primaryDir, 0775, true);
        upload_storage_try_fix_permissions($primaryDir);
    }

    if (is_dir($primaryDir) && upload_storage_probe_dir_writable($primaryDir)) {
        @copy($physicalPath, $primary);
    }
}
