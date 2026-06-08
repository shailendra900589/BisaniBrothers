<?php

/**
 * Auto-translate English blog posts into the active locale (disk cache only on web).
 */

function blog_translate_cache_dir(): string
{
    $dir = dirname(__DIR__) . '/lang/cache/blog-translations';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

function blog_translate_runtime_enabled(): bool
{
    if (getenv('BISANI_BLOG_TRANSLATE_LIVE') === '1') {
        return true;
    }

    return php_sapi_name() === 'cli';
}

function blog_translate_source_hash(array $post): string
{
    $parts = [
        $post['title'] ?? '',
        $post['content'] ?? '',
        $post['meta_title'] ?? '',
        $post['meta_desc'] ?? '',
        $post['faq_json'] ?? '',
        $post['updated_at'] ?? $post['created_at'] ?? '',
    ];

    return md5(implode("\x1e", $parts));
}

function blog_translate_cache_path(int $blogId, string $localeKey): string
{
    $safe = preg_replace('/[^a-z0-9_-]/i', '', $localeKey);

    return blog_translate_cache_dir() . '/' . $blogId . '-' . $safe . '.json';
}

function blog_translate_cache_get(int $blogId, string $localeKey, string $sourceHash): ?array
{
    $path = blog_translate_cache_path($blogId, $localeKey);
    if (!is_file($path)) {
        return null;
    }

    $data = json_decode((string) file_get_contents($path), true);
    if (!is_array($data) || ($data['source_hash'] ?? '') !== $sourceHash) {
        return null;
    }

    return is_array($data['fields'] ?? null) ? $data['fields'] : null;
}

function blog_translate_cache_set(int $blogId, string $localeKey, string $sourceHash, array $fields): void
{
    $payload = [
        'source_hash' => $sourceHash,
        'locale'      => $localeKey,
        'updated_at'  => date('c'),
        'fields'      => $fields,
    ];

    file_put_contents(
        blog_translate_cache_path($blogId, $localeKey),
        json_encode($payload, JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
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

function blog_translate_google(string $text, string $targetLang): string
{
    $text = trim($text);
    if ($text === '' || mb_strlen($text) < 2) {
        return $text;
    }
    if (preg_match('#^https?://#i', $text)) {
        return $text;
    }

    $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl='
        . rawurlencode($targetLang)
        . '&dt=t&q=' . rawurlencode($text);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 20,
            'header'  => "User-Agent: Mozilla/5.0\r\n",
        ],
    ]);

    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) {
        return $text;
    }

    $data = json_decode($json, true);
    if (!isset($data[0]) || !is_array($data[0])) {
        return $text;
    }

    $parts = [];
    foreach ($data[0] as $chunk) {
        if (isset($chunk[0]) && is_string($chunk[0])) {
            $parts[] = $chunk[0];
        }
    }

    return $parts !== [] ? implode('', $parts) : $text;
}

function blog_translate_text(string $text, string $targetLang): string
{
    require_once __DIR__ . '/locale.php';
    if ($targetLang === LOCALE_DEFAULT || trim($text) === '') {
        return $text;
    }

    if (!blog_translate_runtime_enabled()) {
        return $text;
    }

    static $memory = [];
    $key = md5($targetLang . '|' . $text);
    if (isset($memory[$key])) {
        return $memory[$key];
    }

    if (mb_strlen($text) > 4500) {
        $chunks = preg_split('/(\n\n+|<\/p>\s*|<\/h[23]>\s*|<\/li>\s*)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        $out = '';
        $buffer = '';
        foreach ($chunks as $chunk) {
            $buffer .= $chunk;
            if (mb_strlen(strip_tags($buffer)) >= 400 || preg_match('/<\/p>|<\/h[23]>|<\/li>/i', $chunk)) {
                $plain = trim(strip_tags(html_entity_decode($buffer, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                if ($plain !== '') {
                    $out .= blog_translate_google($buffer, $targetLang);
                } else {
                    $out .= $buffer;
                }
                $buffer = '';
            }
        }
        if ($buffer !== '') {
            $plain = trim(strip_tags(html_entity_decode($buffer, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $out .= $plain !== '' ? blog_translate_google($buffer, $targetLang) : $buffer;
        }
        $memory[$key] = $out;

        return $out;
    }

    $memory[$key] = blog_translate_google($text, $targetLang);

    return $memory[$key];
}

function blog_translate_inline_html(string $html, string $targetLang): string
{
    if (!preg_match('/<[^>]+>/', $html)) {
        return blog_translate_text($html, $targetLang);
    }

    $parts = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];
    $out = '';
    foreach ($parts as $part) {
        if ($part === '' || preg_match('/^<[^>]+>$/', $part)) {
            $out .= $part;
            continue;
        }
        $plain = trim(html_entity_decode(strip_tags($part), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $out .= $plain !== '' ? blog_translate_text($plain, $targetLang) : $part;
    }

    return $out;
}

function blog_translate_html(string $html, string $targetLang): string
{
    require_once __DIR__ . '/locale.php';
    if ($targetLang === LOCALE_DEFAULT || trim($html) === '' || !blog_translate_runtime_enabled()) {
        return $html;
    }

    $pattern = '/<(p|h2|h3|h4|h5|li|td|th|blockquote|figcaption|span)(\s[^>]*)?>(.*?)<\/\1>/is';
    $translated = preg_replace_callback($pattern, function (array $m) use ($targetLang): string {
        $tag = $m[1];
        $attrs = $m[2] ?? '';
        $inner = $m[3];
        $plain = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($plain === '') {
            return $m[0];
        }
        if (preg_match('/<(p|h2|h3|h4|h5|ul|ol|table|div|li|span)/i', $inner)) {
            $inner = blog_translate_html($inner, $targetLang);
        } else {
            $inner = blog_translate_inline_html($inner, $targetLang);
        }

        return '<' . $tag . $attrs . '>' . $inner . '</' . $tag . '>';
    }, $html);

    if (!is_string($translated)) {
        return $html;
    }

    $origPlain = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
    $newPlain = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
    if ($origPlain !== '' && $newPlain !== '' && $origPlain === $newPlain) {
        return blog_translate_text($html, $targetLang);
    }

    return $translated;
}

function blog_localize_post(array $post, ?string $locale = null, string $depth = 'full'): array
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
    $sourceHash = blog_translate_source_hash($post) . '|' . $cacheKey;
    $cached = blog_translate_cache_get($blogId, $localeKey, $sourceHash);
    if ($cached !== null) {
        return array_merge($post, $cached, ['_auto_translated' => true]);
    }

    if (!blog_translate_runtime_enabled()) {
        return $post;
    }

    $fields = [
        'title'      => blog_translate_text((string) ($post['title'] ?? ''), $locale),
        'meta_title' => blog_translate_text((string) ($post['meta_title'] ?? ''), $locale),
        'meta_desc'  => blog_translate_text((string) ($post['meta_desc'] ?? ''), $locale),
    ];

    if ($depth === 'full') {
        $fields['content'] = blog_translate_html((string) ($post['content'] ?? ''), $locale);

        if (!empty($post['faq_json'])) {
            require_once __DIR__ . '/blog-helpers.php';
            $faq = blog_parse_faq($post['faq_json']);
            foreach ($faq as &$item) {
                $item['question'] = blog_translate_text($item['question'], $locale);
                $item['answer'] = blog_translate_text($item['answer'], $locale);
            }
            unset($item);
            $fields['faq_json'] = json_encode($faq, JSON_UNESCAPED_UNICODE);
        }
    } elseif (trim($fields['meta_desc']) === '') {
        require_once __DIR__ . '/blog-helpers.php';
        $snippet = blog_excerpt($post['content'] ?? '', 280);
        if ($snippet !== '') {
            $fields['_listing_excerpt'] = blog_translate_text($snippet, $locale);
        }
    }

    blog_translate_cache_set($blogId, $localeKey, $sourceHash, $fields);

    return array_merge($post, $fields, ['_auto_translated' => true]);
}

function blog_localize_posts(array $posts, ?string $locale = null, string $depth = 'full'): array
{
    $out = [];
    foreach ($posts as $post) {
        $out[] = blog_localize_post($post, $locale, $depth);
    }

    return $out;
}
