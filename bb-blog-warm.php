<?php
/**
 * Background blog translation warmer (separate HTTP request — does not block page loads).
 * Called via fetch() from blog pages when a locale cache is missing.
 */
@ini_set('display_errors', '0');
@set_time_limit(300);

require __DIR__ . '/db.php';
require_once __DIR__ . '/includes/blog-helpers.php';
require_once __DIR__ . '/includes/blog-translate.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function blog_warm_api_json(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$postId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$slug = isset($_GET['slug']) ? trim(urldecode((string) $_GET['slug'])) : '';
if ($slug !== '' && preg_match('/\s/', $slug)) {
    $slug = blog_normalize_slug($slug);
}
$locale = isset($_GET['locale']) ? strtolower(trim((string) $_GET['locale'])) : '';
$depth = isset($_GET['depth']) && $_GET['depth'] === 'summary' ? 'summary' : 'full';

require_once __DIR__ . '/includes/locale.php';
if (!locale_is_valid($locale) || $locale === LOCALE_DEFAULT) {
    blog_warm_api_json(['ok' => false, 'error' => 'invalid_locale'], 400);
}

if ($postId <= 0 && $slug !== '') {
    $stmt = $pdo->prepare('SELECT id FROM blogs WHERE slug = ? AND is_published = 1 LIMIT 1');
    $stmt->execute([$slug]);
    $postId = (int) ($stmt->fetchColumn() ?: 0);
}

if ($postId <= 0) {
    blog_warm_api_json(['ok' => false, 'error' => 'post_not_found'], 404);
}

$stmt = $pdo->prepare('SELECT * FROM blogs WHERE id = ? AND is_published = 1 LIMIT 1');
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    blog_warm_api_json(['ok' => false, 'error' => 'post_not_found'], 404);
}

$cacheKey = $depth === 'full' ? 'full' : 'summary';
$localeKey = $locale . '-' . $cacheKey;
$sourceHash = blog_translate_source_hash($post, $depth) . '|' . $cacheKey;

if (blog_translate_cache_get($postId, $localeKey, $sourceHash) !== null) {
    blog_warm_api_json(['ok' => true, 'cached' => true, 'id' => $postId, 'locale' => $locale, 'depth' => $depth]);
}

$ok = blog_warm_post_translation($post, $locale, $depth);
blog_warm_api_json([
    'ok'      => $ok,
    'cached'  => $ok,
    'built'   => $ok,
    'id'      => $postId,
    'locale'  => $locale,
    'depth'   => $depth,
]);
