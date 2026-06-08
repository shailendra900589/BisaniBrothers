<?php
/**
 * Meta (Facebook) Pixel — ID stored encrypted; never output in HTML source.
 */
require_once __DIR__ . '/seo-config.php';

function meta_pixel_active(): bool
{
    return defined('SEO_META_PIXEL_ENC') && SEO_META_PIXEL_ENC !== '';
}

function meta_pixel_id(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    if (!meta_pixel_active()) {
        $cached = '';
        return '';
    }
    $raw = @hex2bin(SEO_META_PIXEL_ENC);
    if ($raw === false || $raw === '') {
        $cached = '';
        return '';
    }
    $key = hash('sha256', SEO_INDEXNOW_KEY, true);
    $out = '';
    $len = strlen($raw);
    for ($i = 0; $i < $len; $i++) {
        $out .= chr(ord($raw[$i]) ^ ord($key[$i % strlen($key)]));
    }
    $cached = $out;
    return $out;
}

function meta_pixel_script_url(string $baseUrl): string
{
    return rtrim($baseUrl, '/') . '/bb-analytics.js';
}

function meta_pixel_beacon_url(string $baseUrl): string
{
    return rtrim($baseUrl, '/') . '/bb-beacon.gif';
}

function meta_pixel_emit_script(): string
{
    $id = meta_pixel_id();
    if ($id === '') {
        return '/* */';
    }

    $keySeed = substr(hash('sha256', SEO_INDEXNOW_KEY . '|mp'), 0, 16);
    $payload = '';
    $idLen = strlen($id);
    for ($i = 0; $i < $idLen; $i++) {
        $payload .= chr(ord($id[$i]) ^ ord($keySeed[$i % 16]) ^ (($i * 7 + 13) % 256));
    }
    $b64 = base64_encode($payload);
    $keyJson = json_encode($keySeed, JSON_UNESCAPED_SLASHES);

    return '(function(w,d,k,b64){'
        . "var p=atob(b64),s='',i=0;"
        . 'for(i=0;i<p.length;i++){s+=String.fromCharCode(p.charCodeAt(i)^k.charCodeAt(i%16)^((i*7+13)%256));}'
        . '!function(f,e,v,n,t,s){'
        . 'if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};'
        . "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];"
        . "t=e.createElement(v);t.async=!0;t.src='https://connect.facebook.net/en_US/fbevents.js';"
        . 's=e.getElementsByTagName(v)[0];s.parentNode.insertBefore(t,s);'
        . "}(w,d,'script');"
        . "w.fbq('init',s);w.fbq('track','PageView');"
        . '})(window,document,' . $keyJson . ',' . json_encode($b64, JSON_UNESCAPED_SLASHES) . ');';
}

function meta_pixel_fire_beacon(): void
{
    $id = meta_pixel_id();
    if ($id === '') {
        return;
    }
    $query = http_build_query([
        'id' => $id,
        'ev'  => 'PageView',
        'noscript' => '1',
    ]);
    $url = 'https://www.facebook.com/tr?' . $query;
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 3,
            'header'  => "User-Agent: Mozilla/5.0\r\n",
        ],
    ]);
    @file_get_contents($url, false, $ctx);
}

function meta_pixel_transparent_gif(): string
{
    return base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7') ?: '';
}
