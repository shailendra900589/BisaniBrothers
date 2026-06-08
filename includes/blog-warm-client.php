<?php
/**
 * Blog pages use server-side Google Translate — no client reload (prevents page blink).
 */
if (defined('BISANI_BLOG_PAGE') && BISANI_BLOG_PAGE) {
    return;
}

require_once __DIR__ . '/blog-translate.php';

$footerScript = $scriptName ?? basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
if (!in_array($footerScript, ['blog', 'blog-details'], true)) {
    return;
}

$warmJobs = blog_get_warm_jobs();
if ($warmJobs === []) {
    return;
}

$warmBase = rtrim($base_url ?? '', '/') . '/bb-blog-warm';
?>
<script>
(function () {
    var jobs = <?php echo json_encode(array_values($warmJobs), JSON_UNESCAPED_UNICODE); ?>;
    var warmBase = <?php echo json_encode($warmBase, JSON_UNESCAPED_SLASHES); ?>;

    function warmUrl(job) {
        if (job.type === 'category') {
            return warmBase + '?category=' + encodeURIComponent(job.category)
                + '&locale=' + encodeURIComponent(job.locale);
        }
        return warmBase + '?id=' + encodeURIComponent(job.id)
            + '&locale=' + encodeURIComponent(job.locale)
            + '&depth=' + encodeURIComponent(job.depth || 'full');
    }

    function start() {
        jobs.forEach(function (job) {
            fetch(warmUrl(job), { credentials: 'same-origin', keepalive: true }).catch(function () {});
        });
    }

    if ('requestIdleCallback' in window) {
        requestIdleCallback(start, { timeout: 3000 });
    } else {
        window.addEventListener('load', function () { setTimeout(start, 1000); }, { once: true });
    }
})();
</script>
