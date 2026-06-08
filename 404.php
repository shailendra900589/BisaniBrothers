<?php
http_response_code(404);
$robotsMeta = 'noindex, nofollow';
if (!isset($pageTitle)) {
    $pageTitle = !empty($isArticle404)
        ? "Article Not Found | Bisani Brothers"
        : "Page Not Found | Bisani Brothers";
}
if (!isset($pageDesc)) {
    $pageDesc = !empty($isArticle404)
        ? "The article you are looking for does not exist or may have been moved."
        : "The page you are looking for does not exist.";
}
include 'includes/header.php';
?>

<section class="min-h-[80vh] flex items-center justify-center bg-white relative overflow-hidden">
    
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 font-extrabold text-[20rem] md:text-[30rem] text-gray-50 opacity-[0.03] select-none pointer-events-none z-0">
        404
    </div>

    <div class="max-w-3xl mx-auto px-4 text-center relative z-10">
        
        <div class="w-32 h-32 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-8 animate-bounce shadow-lg shadow-blue-100">
            <i class="fa-solid fa-ghost text-6xl text-[#173978]"></i>
        </div>

        <h1 class="text-6xl md:text-8xl font-extrabold text-[#173978] mb-4">
            404
        </h1>
        
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6">
            <?php echo !empty($isArticle404) ? 'Article Not Found' : 'Oops! Page Not Found'; ?>
        </h2>
        
        <p class="text-lg text-gray-500 mb-10 max-w-lg mx-auto">
            <?php echo !empty($isArticle404)
                ? 'This blog article may have been removed, renamed, or is temporarily unavailable.'
                : 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.'; ?>
        </p>

        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="index" class="px-8 py-3.5 bg-[#173978] text-white font-bold rounded-lg hover:bg-[#2fcaf0] hover:text-[#173978] transition-all shadow-lg hover:shadow-xl hover:-translate-y-1">
                <i class="fa-solid fa-home mr-2"></i> Back to Home
            </a>
            
            <?php if (!empty($isArticle404)): ?>
            <a href="blog" class="px-8 py-3.5 bg-white text-gray-700 border border-gray-200 font-bold rounded-lg hover:border-[#173978] hover:text-[#173978] transition-all">
                <i class="fa-solid fa-newspaper mr-2"></i> Back to Blog
            </a>
            <?php else: ?>
            <a href="contact" class="px-8 py-3.5 bg-white text-gray-700 border border-gray-200 font-bold rounded-lg hover:border-[#173978] hover:text-[#173978] transition-all">
                Contact Support
            </a>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php include 'includes/footer.php'; ?>