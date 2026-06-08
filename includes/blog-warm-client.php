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
$needsDetailReload = !empty($blogNeedsTranslationReload);
if ($warmJobs === [] && !$needsDetailReload) {
    return;
}

$warmBase = rtrim($base_url ?? '', '/') . '/bb-blog-warm';
$postId = (isset($post) && is_array($post) && isset($post['id'])) ? (int) $post['id'] : 0;
$currentLocale = locale_current();
$isListingPage = $footerScript === 'blog';
?>
<script>
(function () {
    var jobs = <?php echo json_encode(array_values($warmJobs), JSON_UNESCAPED_UNICODE); ?>;
    var warmBase = <?php echo json_encode($warmBase, JSON_UNESCAPED_SLASHES); ?>;
    var needsDetailReload = <?php echo $needsDetailReload ? 'true' : 'false'; ?>;
    var isListingPage = <?php echo $isListingPage ? 'true' : 'false'; ?>;
    var postId = <?php echo (int) $postId; ?>;
    var locale = <?php echo json_encode($currentLocale, JSON_UNESCAPED_UNICODE); ?>;
    var maxConcurrent = isListingPage ? 4 : 2;

    function warmUrl(job) {
        if (job.type === 'category') {
            return warmBase + '?category=' + encodeURIComponent(job.category)
                + '&locale=' + encodeURIComponent(job.locale);
        }
        return warmBase + '?id=' + encodeURIComponent(job.id)
            + '&locale=' + encodeURIComponent(job.locale)
            + '&depth=' + encodeURIComponent(job.depth || 'full');
    }

    function runJobs(done) {
        if (!jobs.length) {
            if (typeof done === 'function') done();
            return;
        }

        var index = 0;
        var active = 0;
        var finished = 0;

        function pump() {
            while (active < maxConcurrent && index < jobs.length) {
                var job = jobs[index++];
                active++;
                fetch(warmUrl(job), { credentials: 'same-origin', keepalive: true })
                    .catch(function () {})
                    .finally(function () {
                        active--;
                        finished++;
                        if (finished >= jobs.length) {
                            if (typeof done === 'function') done();
                            return;
                        }
                        pump();
                    });
            }
        }

        pump();
    }

    function pollReady(tries) {
        if (!needsDetailReload || postId <= 0 || locale === 'en') return;
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

    function afterJobs() {
        if (jobs.length > 0) {
            window.location.reload();
            return;
        }
        if (needsDetailReload) {
            pollReady(18);
        }
    }

    function start() {
        if (jobs.length) {
            runJobs(afterJobs);
        } else if (needsDetailReload) {
            pollReady(18);
        }
    }

    if ('requestIdleCallback' in window) {
        requestIdleCallback(start, { timeout: 1500 });
    } else {
        window.addEventListener('load', function () { setTimeout(start, 500); }, { once: true });
    }
})();
</script>
