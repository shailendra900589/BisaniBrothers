<?php
/**
 * Bootstrap page-content locale files from lang packs (nav/services) + hi template.
 * Usage: php scripts/bootstrap-page-translations.php
 */
$root = dirname(__DIR__);
$en = require $root . '/lang/page-content/en.php';
$locales = require $root . '/lang/locale-config.php';
$hiPack = require $root . '/lang/packs/pages-content.php';
$hiOverlay = $hiPack['hi'] ?? [];

$serviceHeroMap = [
    'sales-growth' => ['sales', 'sales_desc' => 'hero_desc'],
    'survey-market-research' => ['survey'],
    'staffing-solutions' => ['staffing'],
    'btl-atl' => ['btl'],
    'lending-collection' => ['lending'],
    'ev-infrastructure' => ['ev'],
];

$commonFromPack = static function (array $pack): array {
    $nav = $pack['nav'] ?? [];
    $footer = $pack['footer'] ?? [];
    $common = $pack['common'] ?? [];
    return [
        'know_more' => $common['know_more'] ?? $common['learn_more'] ?? 'Know More',
        'success_title' => $footer['toast_success'] ?? $pack['index']['success_title'] ?? 'Message Sent!',
        'success_desc' => $pack['index']['success_desc'] ?? 'We have received your enquiry and will contact you shortly.',
        'form_name' => $footer['form_name'] ?? 'Full Name',
        'form_email' => $footer['email_ph'] ?? 'Email',
        'form_submit' => $footer['subscribe'] ?? 'Submit',
    ];
};

function write_page_content(string $path, array $data): void
{
    $export = var_export($data, true);
    file_put_contents($path, "<?php\n/** Auto-generated page translations */\nreturn {$export};\n");
}

foreach (array_keys($locales) as $code) {
    if ($code === 'en') {
        continue;
    }

    $packFile = $root . "/lang/packs/{$code}.php";
    if (!is_file($packFile)) {
        echo "Skip {$code}: no pack\n";
        continue;
    }

    $pack = require $packFile;
    $out = ['_common' => array_merge($en['_common'], $commonFromPack($pack))];

    if ($code === 'hi' && isset($hiOverlay)) {
        $out = array_replace_recursive($out, $hiOverlay);
        write_page_content("{$root}/lang/page-content/{$code}.php", array_replace_recursive($en, $out));
        echo "Wrote hi (full pack merge)\n";
        continue;
    }

    // Page heroes from services names in pack
    $services = $pack['services'] ?? [];
    foreach ($serviceHeroMap as $slug => $map) {
        $svcKey = $map[0];
        if (!isset($services[$svcKey])) {
            continue;
        }
        $page = [];
        $name = $services[$svcKey];
        $parts = preg_split('/\s+/u', $name, 2);
        $page['hero_h1_a'] = $parts[0] . (isset($parts[1]) ? '.' : '');
        $page['hero_h1_b'] = $parts[1] ?? '';
        $page['intro_highlight'] = $name;
        $out[$slug] = $page;
    }

    // Static pages from pages meta in pack
    $pages = $pack['pages'] ?? [];
    if (isset($pages['about'])) {
        $out['about']['hero_btn'] = $pack['nav']['about'] ?? 'About';
    }
    if (isset($pages['contact'])) {
        $out['contact']['hero_btn'] = $pack['nav']['contact'] ?? 'Contact';
    }
    if (isset($pages['careers'])) {
        $out['careers']['hero_btn'] = $pack['nav']['careers'] ?? 'Careers';
    }
    if (isset($pages['case-studies'])) {
        $out['case-studies']['hero_title'] = $pack['nav']['case_studies'] ?? 'Case Studies';
    }
    if (isset($pages['industries'])) {
        $out['industries']['hero_title'] = $pack['nav']['industries'] ?? 'Industries';
    }
    if (isset($pages['blog'])) {
        $out['blog']['hero_title'] = $pack['nav']['blog'] ?? 'Blog';
    }

    write_page_content("{$root}/lang/page-content/{$code}.php", array_replace_recursive($en, $out));
    echo "Wrote lang/page-content/{$code}.php\n";
}

echo "Done.\n";
