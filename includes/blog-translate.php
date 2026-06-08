<?php

/**
 * Auto-translate English blog posts into all supported locales (Google Translate API).
 * Web: serve disk cache instantly; queue missing caches after response (no 500/timeouts).
 * CLI / deploy / admin: build caches via scripts/warm-blog-translations.php.
 */

function blog_translate_cache_dir(): string
{
    $dir = dirname(__DIR__) . '/lang/cache/blog-translations';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    return $dir;
}

function blog_is_web_blog_page(): bool
{
    static $result = null;
    if ($result !== null) {
        return $result;
    }
    if (php_sapi_name() === 'cli') {
        $result = false;

        return false;
    }
    if (defined('BISANI_BLOG_PAGE') && BISANI_BLOG_PAGE) {
        $result = true;

        return true;
    }
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
    $result = in_array($script, ['blog', 'blog-details'], true);

    return $result;
}

function blog_google_target_lang(string $locale): string
{
    require_once __DIR__ . '/locale.php';
    if (!locale_is_valid($locale) || $locale === LOCALE_DEFAULT) {
        return LOCALE_DEFAULT;
    }
    $meta = LOCALE_META[$locale] ?? [];

    return (string) ($meta['google_tl'] ?? $locale);
}

function blog_translate_runtime_enabled(): bool
{
    if (getenv('BISANI_BLOG_TRANSLATE_OFF') === '1') {
        return false;
    }
    if (!empty($GLOBALS['_blog_translate_live_job'])) {
        return true;
    }
    if (php_sapi_name() === 'cli') {
        return true;
    }
    if (blog_is_web_blog_page()) {
        return true;
    }

    return false;
}

function blog_translate_is_active(): bool
{
    return blog_translate_runtime_enabled();
}

/**
 * Run translation API calls inside a live-enabled context (CLI, warm jobs, shutdown workers).
 *
 * @template T
 * @param callable(): T $callback
 * @return T
 */
function blog_translate_run_live(callable $callback)
{
    $wasLive = !empty($GLOBALS['_blog_translate_live_job']);
    $GLOBALS['_blog_translate_live_job'] = true;
    try {
        return $callback();
    } finally {
        if (!$wasLive) {
            unset($GLOBALS['_blog_translate_live_job']);
        }
    }
}

function blog_translate_source_hash(array $post, string $depth = 'full'): string
{
    if ($depth === 'full') {
        $parts = [
            $post['title'] ?? '',
            $post['content'] ?? '',
            $post['meta_title'] ?? '',
            $post['meta_desc'] ?? '',
            $post['faq_json'] ?? '',
            $post['created_at'] ?? '',
        ];
    } else {
        // Summary hash must not use faq_json — listing/related queries omit that column.
        $parts = [
            $post['title'] ?? '',
            $post['meta_title'] ?? '',
            $post['meta_desc'] ?? '',
            $post['created_at'] ?? '',
        ];
    }

    return md5(implode("\x1e", $parts));
}

function blog_translate_cache_path(int $blogId, string $localeKey): string
{
    $safe = preg_replace('/[^a-z0-9_-]/i', '', $localeKey);

    return blog_translate_cache_dir() . '/' . $blogId . '-' . $safe . '.json';
}

function blog_translate_cache_get(int $blogId, string $localeKey, string $sourceHash): ?array
{
    static $memory = [];
    static $memoryGen = 0;
    if (($GLOBALS['_blog_cache_mem_bust'] ?? 0) !== $memoryGen) {
        $memory = [];
        $memoryGen = $GLOBALS['_blog_cache_mem_bust'] ?? 0;
    }

    $memKey = $blogId . '|' . $localeKey . '|' . $sourceHash;
    if (array_key_exists($memKey, $memory)) {
        return $memory[$memKey];
    }

    $path = blog_translate_cache_path($blogId, $localeKey);
    if (is_file($path)) {
        $data = json_decode((string) file_get_contents($path), true);
        if (is_array($data)) {
            $fields = is_array($data['fields'] ?? null) ? $data['fields'] : null;
            if ($fields !== null && trim((string) ($fields['title'] ?? '')) !== '') {
                if (($data['source_hash'] ?? '') === $sourceHash) {
                    $memory[$memKey] = $fields;

                    return $fields;
                }
                // Fuzzy hit — partial SELECT rows (no faq_json) use a different hash.
                $fuzzyKey = $blogId . '|' . $localeKey . '|fuzzy';
                if (!array_key_exists($fuzzyKey, $memory)) {
                    $memory[$fuzzyKey] = $fields;
                }
                $memory[$memKey] = $memory[$fuzzyKey];

                return $memory[$fuzzyKey];
            }
        }
    }

    $legacy = blog_translate_cache_get_legacy($blogId, $localeKey);
    if ($legacy !== null) {
        $memory[$memKey] = $legacy;
    }

    return $legacy;
}

/** @return array<string, mixed>|null */
function blog_translate_cache_get_legacy(int $blogId, string $localeKey): ?array
{
    if (!preg_match('/^([a-z]{2})-(summary|full)$/', $localeKey, $m)) {
        return null;
    }

    $legacyPath = blog_translate_cache_dir() . '/' . $blogId . '-' . $m[1] . '.json';
    if (!is_file($legacyPath)) {
        return null;
    }

    $data = json_decode((string) file_get_contents($legacyPath), true);
    if (!is_array($data) || !is_array($data['fields'] ?? null)) {
        return null;
    }

    $fields = $data['fields'];
    if ($m[2] === 'summary') {
        $summary = array_intersect_key($fields, array_flip(['title', 'meta_title', 'meta_desc', '_listing_excerpt']));
        return $summary !== [] ? $summary : null;
    }

    return $fields;
}

function blog_translate_cache_set(int $blogId, string $localeKey, string $sourceHash, array $fields): void
{
    $payload = [
        'source_hash' => $sourceHash,
        'locale'      => $localeKey,
        'updated_at'  => date('c'),
        'fields'      => $fields,
    ];

    @file_put_contents(
        blog_translate_cache_path($blogId, $localeKey),
        json_encode($payload, JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );

    // Bust in-request cache so the write is visible immediately.
    $GLOBALS['_blog_cache_mem_bust'] = ($GLOBALS['_blog_cache_mem_bust'] ?? 0) + 1;
}

function blog_translate_cache_clear(?int $blogId = null): void
{
    $dir = blog_translate_cache_dir();
    if ($blogId === null) {
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            @unlink($file);
        }

        return;
    }

    foreach (glob($dir . '/' . $blogId . '-*.json') ?: [] as $file) {
        @unlink($file);
    }
}

function blog_http_get(string $url): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; BisaniBrothers/1.0)',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING       => '',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (PHP_VERSION_ID >= 80000) {
            curl_close($ch);
        } else {
            @curl_close($ch);
        }
        if (is_string($body) && $body !== '' && $code >= 200 && $code < 300) {
            return $body;
        }
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 20,
            'header'  => "User-Agent: Mozilla/5.0 (compatible; BisaniBrothers/1.0)\r\n",
        ],
        'ssl' => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);

    return is_string($body) && $body !== '' ? $body : null;
}

function blog_translate_parse_response(?string $json, string $original): string
{
    if ($json === null || $json === '') {
        return $original;
    }

    $data = json_decode($json, true);
    if (!isset($data[0]) || !is_array($data[0])) {
        return $original;
    }

    $parts = [];
    foreach ($data[0] as $chunk) {
        if (isset($chunk[0]) && is_string($chunk[0])) {
            $parts[] = $chunk[0];
        }
    }

    $translated = $parts !== [] ? implode('', $parts) : '';

    return ($translated !== '' && $translated !== $original) ? $translated : $original;
}

/**
 * Translate many strings in parallel (much faster than sequential calls).
 *
 * @param array<int, string> $texts
 * @return array<int, string>
 */
function blog_translate_texts_parallel(array $texts, string $targetLang, int $maxConcurrent = 14): array
{
    require_once __DIR__ . '/locale.php';
    if ($targetLang === LOCALE_DEFAULT || !blog_translate_is_active()) {
        return $texts;
    }

    $googleLang = blog_google_target_lang($targetLang);
    if ($googleLang === '' || $googleLang === LOCALE_DEFAULT) {
        return $texts;
    }

    static $memory = [];
    $results = array_fill(0, count($texts), '');
    $pending = [];

    foreach ($texts as $i => $text) {
        $text = trim((string) $text);
        if ($text === '' || mb_strlen($text) < 2 || preg_match('#^https?://#i', $text)) {
            $results[$i] = $text;
            continue;
        }

        $memKey = $targetLang . '|' . md5($text);
        if (isset($memory[$memKey])) {
            $results[$i] = $memory[$memKey];
            continue;
        }

        if (mb_strlen($text) > 4500) {
            $results[$i] = blog_translate_text($text, $targetLang);
            $memory[$memKey] = $results[$i];
            continue;
        }

        $pending[$i] = $text;
    }

    if ($pending === [] || !function_exists('curl_multi_init')) {
        foreach ($pending as $i => $text) {
            $results[$i] = blog_translate_google($text, $targetLang);
        }

        return $results;
    }

    foreach (array_chunk($pending, $maxConcurrent, true) as $chunk) {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($chunk as $i => $text) {
            $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl='
                . rawurlencode($googleLang)
                . '&dt=t&q=' . rawurlencode($text);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT        => 12,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; BisaniBrothers/1.0)',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_ENCODING       => '',
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$i] = ['ch' => $ch, 'text' => $text];
        }

        $running = null;
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running > 0) {
                curl_multi_select($mh, 0.4);
            }
        } while ($running > 0 && $status === CURLM_OK);

        foreach ($handles as $i => $meta) {
            $json = curl_multi_getcontent($meta['ch']);
            $translated = blog_translate_parse_response(is_string($json) ? $json : null, $meta['text']);
            $results[$i] = $translated;
            $memory[$targetLang . '|' . md5($meta['text'])] = $translated;
            curl_multi_remove_handle($mh, $meta['ch']);
            if (PHP_VERSION_ID >= 80000) {
                curl_close($meta['ch']);
            } else {
                @curl_close($meta['ch']);
            }
        }

        curl_multi_close($mh);
    }

    return $results;
}

/**
 * Free Google Translate (no API key) — all blog content is translated through this API.
 * @see https://translate.googleapis.com/translate_a/single
 */
function blog_translate_google(string $text, string $targetLang): string
{
    $text = trim($text);
    if ($text === '' || mb_strlen($text) < 2) {
        return $text;
    }
    if (preg_match('#^https?://#i', $text)) {
        return $text;
    }

    require_once __DIR__ . '/locale.php';
    $googleLang = blog_google_target_lang($targetLang);
    if ($googleLang === '' || $googleLang === LOCALE_DEFAULT) {
        return $text;
    }

    $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl='
        . rawurlencode($googleLang)
        . '&dt=t&q=' . rawurlencode($text);

    for ($attempt = 0; $attempt < 2; $attempt++) {
        $json = blog_http_get($url);
        if ($json === null) {
            usleep(80000);
            continue;
        }

        $translated = blog_translate_parse_response($json, $text);
        if ($translated !== $text) {
            return $translated;
        }
    }

    return $text;
}

function blog_translate_text(string $text, string $targetLang): string
{
    require_once __DIR__ . '/locale.php';
    if ($targetLang === LOCALE_DEFAULT || trim($text) === '') {
        return $text;
    }

    if (!blog_translate_is_active()) {
        return $text;
    }

    $results = blog_translate_texts_parallel([$text], $targetLang);

    return $results[0] ?? $text;
}

/** Fast HTML translation — extract text nodes, translate in parallel, reinsert. */
function blog_translate_html(string $html, string $targetLang): string
{
    require_once __DIR__ . '/locale.php';
    if ($targetLang === LOCALE_DEFAULT || trim($html) === '' || !blog_translate_is_active()) {
        return $html;
    }

    if (!preg_match('/<[^>]+>/', $html)) {
        return blog_translate_text($html, $targetLang);
    }

    $origPlain = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));

    $tokens = [];
    $tokenMap = [];
    $markers = [];
    $counter = 0;

    $replaced = preg_replace_callback(
        '/>([^<]+)</u',
        static function (array $m) use (&$tokens, &$tokenMap, &$markers, &$counter): string {
            $raw = $m[1];
            $plain = trim(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($plain === '' || mb_strlen($plain) < 2 || preg_match('#^https?://#i', $plain)) {
                return $m[0];
            }

            $hash = md5($plain);
            if (!isset($tokenMap[$hash])) {
                $tokenMap[$hash] = count($tokens);
                $tokens[] = $plain;
            }

            $marker = '[[BBX' . ($counter++) . ']]';
            $markers[$marker] = $hash;

            return '>' . $marker . '<';
        },
        $html
    );

    if ($tokens === [] || !is_string($replaced)) {
        return is_string($replaced) ? $replaced : $html;
    }

    $translatedList = blog_translate_texts_parallel($tokens, $targetLang);
    $translatedByHash = [];
    foreach ($tokens as $idx => $plain) {
        $translatedByHash[md5($plain)] = $translatedList[$idx] ?? $plain;
    }

    foreach ($markers as $marker => $hash) {
        $translated = $translatedByHash[$hash] ?? '';
        if ($translated !== '') {
            $replaced = str_replace($marker, $translated, $replaced);
        }
    }

    $newPlain = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($replaced, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
    if ($origPlain !== '' && $newPlain === $origPlain && preg_match('/[a-z]/i', $origPlain)) {
        return blog_translate_text($origPlain, $targetLang);
    }

    return $replaced;
}

function blog_build_localized_fields(array $post, string $locale, string $depth): array
{
    $title = (string) ($post['title'] ?? '');
    $metaTitle = (string) ($post['meta_title'] ?? '');
    $metaDesc = (string) ($post['meta_desc'] ?? '');

    [$tTitle, $tMetaTitle, $tMetaDesc] = blog_translate_texts_parallel([$title, $metaTitle, $metaDesc], $locale);

    $fields = [
        'title'      => $tTitle,
        'meta_title' => $tMetaTitle,
        'meta_desc'  => $tMetaDesc,
    ];

    if ($depth === 'full') {
        $fields = array_merge($fields, blog_build_content_fields($post, $locale));
    } elseif (trim($fields['meta_desc']) === '') {
        require_once __DIR__ . '/blog-helpers.php';
        $snippet = blog_excerpt($post['content'] ?? '', 280);
        if ($snippet !== '') {
            $fields['_listing_excerpt'] = blog_translate_text($snippet, $locale);
        }
    }

    return $fields;
}

/** @return array<string, mixed> */
function blog_build_content_fields(array $post, string $locale): array
{
    $fields = [];
    $content = (string) ($post['content'] ?? '');
    if ($content !== '') {
        $fields['content'] = blog_translate_html($content, $locale);
    }

    if (!empty($post['faq_json'])) {
        require_once __DIR__ . '/blog-helpers.php';
        $faq = blog_parse_faq($post['faq_json']);
        $faqTexts = [];
        foreach ($faq as $item) {
            $faqTexts[] = (string) ($item['question'] ?? '');
            $faqTexts[] = (string) ($item['answer'] ?? '');
        }
        $faqTranslated = blog_translate_texts_parallel($faqTexts, $locale);
        foreach ($faq as $idx => &$item) {
            $item['question'] = $faqTranslated[$idx * 2] ?? $item['question'];
            $item['answer'] = $faqTranslated[$idx * 2 + 1] ?? $item['answer'];
        }
        unset($item);
        $fields['faq_json'] = json_encode($faq, JSON_UNESCAPED_UNICODE);
    }

    return $fields;
}

/**
 * Warm listing summaries in 3 parallel waves (titles, meta titles, meta descs).
 *
 * @param array<int, array<string, mixed>> $posts
 */
function blog_warm_summaries_parallel(array $posts, string $locale): int
{
    require_once __DIR__ . '/locale.php';
    if ($locale === LOCALE_DEFAULT || $posts === []) {
        return 0;
    }

    $warmed = 0;
    $titles = [];
    $metaTitles = [];
    $metaDescs = [];
    $meta = [];

    foreach ($posts as $idx => $post) {
        $blogId = (int) ($post['id'] ?? 0);
        if ($blogId <= 0) {
            continue;
        }
        $sourceHash = blog_translate_source_hash($post, 'summary') . '|summary';
        if (blog_translate_cache_get($blogId, $locale . '-summary', $sourceHash) !== null) {
            continue;
        }
        $meta[$idx] = ['id' => $blogId, 'hash' => $sourceHash, 'post' => $post];
        $titles[$idx] = (string) ($post['title'] ?? '');
        $metaTitles[$idx] = (string) ($post['meta_title'] ?? '');
        $metaDescs[$idx] = (string) ($post['meta_desc'] ?? '');
    }

    if ($meta === []) {
        return 0;
    }

    $tTitles = blog_translate_texts_parallel(array_values($titles), $locale);
    $tMetaTitles = blog_translate_texts_parallel(array_values($metaTitles), $locale);
    $tMetaDescs = blog_translate_texts_parallel(array_values($metaDescs), $locale);
    $keys = array_keys($meta);

    foreach ($keys as $pos => $idx) {
        $info = $meta[$idx];
        $fields = [
            'title'      => $tTitles[$pos] ?? $titles[$idx],
            'meta_title' => $tMetaTitles[$pos] ?? $metaTitles[$idx],
            'meta_desc'  => $tMetaDescs[$pos] ?? $metaDescs[$idx],
        ];
        blog_translate_cache_set($info['id'], $locale . '-summary', $info['hash'], $fields);
        $warmed++;
    }

    return $warmed;
}

/** Queue warm jobs for browser fetch (IIS has no fastcgi_finish_request — never warm in shutdown). */
function blog_queue_warm_job(int $postId, string $locale, string $depth = 'full'): void
{
    if ($postId <= 0 || php_sapi_name() === 'cli') {
        return;
    }
    require_once __DIR__ . '/locale.php';
    if (!locale_is_valid($locale) || $locale === LOCALE_DEFAULT) {
        return;
    }
    if (!isset($GLOBALS['_blog_warm_jobs']) || !is_array($GLOBALS['_blog_warm_jobs'])) {
        $GLOBALS['_blog_warm_jobs'] = [];
    }
    $GLOBALS['_blog_warm_jobs']["{$postId}|{$locale}|{$depth}"] = [
        'type'   => 'post',
        'id'     => $postId,
        'locale' => $locale,
        'depth'  => $depth,
    ];
}

function blog_queue_category_warm(string $category, string $locale): void
{
    if (php_sapi_name() === 'cli') {
        return;
    }
    require_once __DIR__ . '/locale.php';
    $category = trim($category);
    if ($category === '' || !locale_is_valid($locale) || $locale === LOCALE_DEFAULT) {
        return;
    }
    if (!isset($GLOBALS['_blog_warm_jobs']) || !is_array($GLOBALS['_blog_warm_jobs'])) {
        $GLOBALS['_blog_warm_jobs'] = [];
    }
    $key = 'cat|' . $locale . '|' . strtolower($category);
    $GLOBALS['_blog_warm_jobs'][$key] = [
        'type'     => 'category',
        'category' => $category,
        'locale'   => $locale,
    ];
}

/** @return array<int, array{id: int, locale: string, depth: string}> */
function blog_get_warm_jobs(): array
{
    return array_values($GLOBALS['_blog_warm_jobs'] ?? []);
}

function blog_warm_post_translation(array $post, string $locale, string $depth = 'full'): bool
{
    require_once __DIR__ . '/locale.php';
    if ($locale === LOCALE_DEFAULT) {
        return true;
    }

    $blogId = (int) ($post['id'] ?? 0);
    if ($blogId <= 0) {
        return false;
    }

    $cacheKey = $depth === 'full' ? 'full' : 'summary';
    $localeKey = $locale . '-' . $cacheKey;
    $sourceHash = blog_translate_source_hash($post, $depth) . '|' . $cacheKey;
    if (blog_translate_cache_get($blogId, $localeKey, $sourceHash) !== null) {
        return true;
    }

    try {
        blog_translate_run_live(static function () use ($post, $locale, $depth, $blogId, $localeKey, $sourceHash): void {
            if (function_exists('set_time_limit')) {
                @set_time_limit($depth === 'full' ? 300 : 120);
            }
            $fields = blog_build_localized_fields($post, $locale, $depth);
            blog_translate_cache_set($blogId, $localeKey, $sourceHash, $fields);
        });

        return true;
    } catch (Throwable $e) {
        error_log('Blog warm failed for #' . $blogId . ' [' . $locale . '/' . $depth . ']: ' . $e->getMessage());

        return false;
    }
}

/**
 * Warm summary + full caches for one post across locales (CLI / admin / deploy).
 */
function blog_warm_post_locales(array $post, ?array $locales = null): int
{
    require_once __DIR__ . '/locale.php';
    $locales = $locales ?? locale_non_default_codes();
    $warmed = 0;

    foreach ($locales as $locale) {
        if (blog_warm_post_translation($post, $locale, 'summary')) {
            $warmed++;
        }
        if (blog_warm_post_translation($post, $locale, 'full')) {
            $warmed++;
        }
    }

    return $warmed;
}

function blog_localize_post(array $post, ?string $locale = null, string $depth = 'full', bool $allowLiveTranslate = true): array
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? locale_current();
    $postLocale = $post['locale'] ?? LOCALE_DEFAULT;

    if ($locale === LOCALE_DEFAULT || $postLocale === $locale) {
        return $post;
    }

    $blogId = (int) ($post['id'] ?? 0);
    if ($blogId <= 0) {
        return $post;
    }

    $cacheKey = $depth === 'full' ? 'full' : 'summary';
    $localeKey = $locale . '-' . $cacheKey;
    $sourceHash = blog_translate_source_hash($post, $depth) . '|' . $cacheKey;
    $cached = blog_translate_cache_get($blogId, $localeKey, $sourceHash);
    if ($cached !== null) {
        return array_merge($post, $cached, ['_auto_translated' => true]);
    }

    $summaryCached = null;
    if ($depth === 'full') {
        $summaryCached = blog_translate_cache_get(
            $blogId,
            $locale . '-summary',
            blog_translate_source_hash($post, 'summary') . '|summary'
        );
    }

    if ($allowLiveTranslate && blog_translate_runtime_enabled()) {
        if (blog_warm_post_translation($post, $locale, $depth)) {
            $cached = blog_translate_cache_get($blogId, $localeKey, $sourceHash);
            if ($cached !== null) {
                return array_merge($post, $cached, ['_auto_translated' => true]);
            }
        }

        if ($depth === 'full' && !empty($post['content'])) {
            $base = $summaryCached ?? [
                'title'      => (string) ($post['title'] ?? ''),
                'meta_title' => (string) ($post['meta_title'] ?? ''),
                'meta_desc'  => (string) ($post['meta_desc'] ?? ''),
            ];
            if ($summaryCached === null) {
                [$base['title'], $base['meta_title'], $base['meta_desc']] = blog_translate_texts_parallel(
                    [$base['title'], $base['meta_title'], $base['meta_desc']],
                    $locale
                );
            }
            $fields = array_merge($base, blog_build_content_fields($post, $locale));
            blog_translate_cache_set($blogId, $localeKey, $sourceHash, $fields);

            return array_merge($post, $fields, ['_auto_translated' => true]);
        }
    } elseif (!blog_is_web_blog_page()) {
        blog_queue_warm_job($blogId, $locale, $depth);
    }

    if ($summaryCached !== null && $depth === 'summary') {
        return array_merge($post, $summaryCached, ['_auto_translated' => true]);
    }

    return $post;
}

/** Serve cached translation only — never block the HTTP request. */
function blog_localize_post_cached_only(array $post, ?string $locale = null, string $depth = 'summary'): array
{
    return blog_localize_post($post, $locale, $depth, false);
}

function blog_localize_posts(array $posts, ?string $locale = null, string $depth = 'full', bool $allowLiveTranslate = true): array
{
    $out = [];
    foreach ($posts as $post) {
        $out[] = blog_localize_post($post, $locale, $depth, $allowLiveTranslate);
    }

    return $out;
}

function blog_localize_posts_cached_only(array $posts, ?string $locale = null, string $depth = 'summary'): array
{
    return blog_localize_posts($posts, $locale, $depth, false);
}

/** Live Google Translate on blog pages (cache-first, then free API, then warm fallback). */
function blog_localize_post_for_web(array $post, ?string $locale = null, string $depth = 'summary'): array
{
    $rows = blog_localize_posts_for_web([$post], $locale, $depth);

    return $rows[0] ?? $post;
}

function blog_localize_posts_for_web(array $posts, ?string $locale = null, string $depth = 'summary'): array
{
    require_once __DIR__ . '/locale.php';
    $locale = $locale ?? locale_current();
    if ($locale === LOCALE_DEFAULT || $posts === []) {
        return $posts;
    }

    $out = [];
    $toWarm = [];

    foreach ($posts as $i => $post) {
        $cached = blog_localize_post_cached_only($post, $locale, $depth);
        $cacheComplete = !empty($cached['_auto_translated'])
            && ($depth === 'summary' || !empty($cached['content']));
        if ($cacheComplete) {
            $out[$i] = $cached;
        } else {
            $toWarm[$i] = $post;
            $out[$i] = $post;
        }
    }

    if ($toWarm !== [] && blog_translate_runtime_enabled()) {
        if ($depth === 'summary') {
            blog_warm_summaries_parallel(array_values($toWarm), $locale);
            foreach ($toWarm as $i => $post) {
                $cached = blog_localize_post_cached_only($post, $locale, $depth);
                $out[$i] = !empty($cached['_auto_translated']) ? $cached : $post;
            }
        } else {
            foreach ($toWarm as $i => $post) {
                $out[$i] = blog_localize_post($post, $locale, $depth, true);
            }
        }
    }

    ksort($out);

    return array_values($out);
}
