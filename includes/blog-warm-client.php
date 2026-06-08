<?php
/**
 * Browser-side blog translation warmer (no server terminal needed).
 */
require_once __DIR__ . '/blog-translate.php';

$footerScript = $scriptName ?? basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
if (!in_array($footerScript, ['blog', 'blog-details'], true)) {
    return;
}

$warmJobs = blog_get_warm_jobs();
$needsReload = !empty($blogNeedsTranslationReload);
if ($warmJobs === [] && !$needsReload) {
    return;
}

$warmBase = rtrim($base_url ?? '', '/') . '/bb-blog-warm';
$postId = (isset($post) && is_array($post) && isset($post['id'])) ? (int) $post['id'] : 0;
$currentLocale = locale_current();
?>
<script>
(function () {
    var jobs = <?php echo json_encode(array_values($warmJobs), JSON_UNESCAPED_UNICODE); ?>;
    var warmBase = <?php echo json_encode($warmBase, JSON_UNESCAPED_SLASHES); ?>;
    var needsReload = <?php echo $needsReload ? 'true' : 'false'; ?>;
    var postId = <?php echo (int) $postId; ?>;
    var locale = <?php echo json_encode($currentLocale, JSON_UNESCAPED_UNICODE); ?>;

    function warmUrl(job) {
        return warmBase + '?id=' + encodeURIComponent(job.id)
            + '&locale=' + encodeURIComponent(job.locale)
            + '&depth=' + encodeURIComponent(job.depth || 'full');
    }

    function runJobs(done) {
        var i = 0;
        function next() {
            if (i >= jobs.length) {
                if (typeof done === 'function') done();
                return;
            }
            var job = jobs[i++];
            fetch(warmUrl(job), { credentials: 'same-origin', keepalive: true })
                .catch(function () {})
                .finally(function () { setTimeout(next, 400); });
        }
        next();
    }

    function pollReady(tries) {
        if (!needsReload || postId <= 0 || locale === 'en') return;
        if (tries <= 0) return;
        fetch(warmBase + '?id=' + postId + '&locale=' + encodeURIComponent(locale) + '&depth=full', {
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.cached) {
                    window.location.reload();
                    return;
                }
                setTimeout(function () { pollReady(tries - 1); }, 4000);
            })
            .catch(function () {
                setTimeout(function () { pollReady(tries - 1); }, 5000);
            });
    }

    function start() {
        if (jobs.length) {
            runJobs(function () {
                if (needsReload) pollReady(18);
            });
        } else if (needsReload) {
            pollReady(18);
        }
    }

    if ('requestIdleCallback' in window) {
        requestIdleCallback(start, { timeout: 2500 });
    } else {
        window.addEventListener('load', function () { setTimeout(start, 800); }, { once: true });
    }
})();
</script>
