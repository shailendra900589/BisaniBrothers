<?php
/**
 * One-click blog translation warm — runs in browser (no SSH/terminal needed).
 */
require_once __DIR__ . '/includes/init.php';
admin_require_roles(['admin']);

require '../db.php';
require_once '../includes/seo.php';
require_once '../includes/assets.php';
require_once '../includes/locale.php';
require_once '../includes/blog-helpers.php';

$stmt = $pdo->query('SELECT id, title, slug FROM blogs WHERE is_published = 1 ORDER BY id ASC');
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$locales = locale_non_default_codes();
$warmBase = rtrim(seo_site_url_rtrim(), '/') . '/bb-blog-warm';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warm Blog Translations | Admin</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(bb_stylesheet('tailwind.css')); ?>">
</head>
<body class="bg-slate-100 min-h-screen p-6">
    <div class="max-w-3xl mx-auto bg-white rounded-xl shadow p-6">
        <h1 class="text-2xl font-bold text-[#173978] mb-2">Warm blog translations</h1>
        <p class="text-slate-600 text-sm mb-4">Builds Google Translate caches for all languages. Keep this tab open until finished. No terminal required.</p>
        <p class="text-sm mb-4"><strong><?php echo count($posts); ?></strong> posts × <strong><?php echo count($locales); ?></strong> locales</p>
        <button type="button" id="start-warm" class="px-5 py-2.5 bg-[#173978] text-white font-bold rounded-lg hover:bg-[#2fcaf0] hover:text-[#173978] transition">Start warming</button>
        <pre id="log" class="mt-4 p-4 bg-slate-900 text-green-300 text-xs rounded-lg max-h-96 overflow-auto whitespace-pre-wrap"></pre>
        <p class="mt-4"><a href="blogs.php" class="text-[#173978] font-semibold">← Back to blogs</a></p>
    </div>
    <script>
    (function () {
        var posts = <?php echo json_encode($posts, JSON_UNESCAPED_UNICODE); ?>;
        var locales = <?php echo json_encode($locales, JSON_UNESCAPED_UNICODE); ?>;
        var warmBase = <?php echo json_encode($warmBase, JSON_UNESCAPED_SLASHES); ?>;
        var logEl = document.getElementById('log');
        var btn = document.getElementById('start-warm');
        var running = false;

        function log(msg) {
            logEl.textContent += msg + '\n';
            logEl.scrollTop = logEl.scrollHeight;
        }

        function warmOne(id, locale, depth) {
            var url = warmBase + '?id=' + id + '&locale=' + encodeURIComponent(locale) + '&depth=' + depth;
            return fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
        }

        function run() {
            if (running) return;
            running = true;
            btn.disabled = true;
            btn.textContent = 'Running…';
            var chain = Promise.resolve();
            posts.forEach(function (post) {
                locales.forEach(function (locale) {
                    ['summary', 'full'].forEach(function (depth) {
                        chain = chain.then(function () {
                            log('Warming #' + post.id + ' ' + locale + ' ' + depth + '…');
                            return warmOne(post.id, locale, depth).then(function (data) {
                                log(data && data.cached ? '  ✓ cached' : (data && data.ok ? '  ✓ built' : '  ✗ failed'));
                            }).catch(function () {
                                log('  ✗ error');
                            });
                        });
                    });
                });
            });
            chain.then(function () {
                log('\nDone! Blog language switching should work now.');
                btn.textContent = 'Completed';
            });
        }

        btn.addEventListener('click', run);
    })();
    </script>
</body>
</html>
