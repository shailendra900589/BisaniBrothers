<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/marketing-helpers.php';

$message = '';
$success = false;
$email = marketing_verify_unsubscribe(
    (string) ($_GET['e'] ?? ''),
    (string) ($_GET['t'] ?? '')
);

if ($email && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status='unsubscribed' WHERE email=?");
        $stmt->execute([$email]);
        $success = true;
        $message = 'You have been unsubscribed successfully.';
    } catch (PDOException $e) {
        $message = 'Could not process unsubscribe. Please contact contact@bisanibrother.com';
    }
} elseif (!$email) {
    $message = 'Invalid or expired unsubscribe link.';
}

$pageTitle = 'Newsletter Unsubscribe | Bisani Brothers';
include __DIR__ . '/includes/header.php';
?>
<section class="py-24 px-4">
    <div class="max-w-lg mx-auto bg-white rounded-2xl shadow-xl border p-8 text-center">
        <div class="w-16 h-16 bg-[#173978]/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-envelope-open text-2xl text-[#173978]"></i>
        </div>
        <h1 class="text-2xl font-bold text-[#173978] mb-3">Newsletter Preferences</h1>
        <?php if ($success): ?>
            <p class="text-green-700 mb-4"><?php echo htmlspecialchars($message); ?></p>
            <a href="<?php echo seo_site_url_rtrim(); ?>/" class="inline-block bg-[#173978] text-white px-6 py-3 rounded-lg font-bold">Back to Website</a>
        <?php elseif ($email): ?>
            <p class="text-slate-600 mb-6">Unsubscribe <strong><?php echo htmlspecialchars($email); ?></strong> from Bisani Brothers marketing emails?</p>
            <form method="post" class="space-y-3">
                <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-lg">Yes, Unsubscribe Me</button>
                <a href="<?php echo seo_site_url_rtrim(); ?>/" class="block text-sm text-slate-500 hover:text-[#173978]">No, keep me subscribed</a>
            </form>
        <?php else: ?>
            <p class="text-red-600 mb-4"><?php echo htmlspecialchars($message); ?></p>
            <a href="<?php echo seo_site_url_rtrim(); ?>/contact" class="text-[#173978] font-bold underline">Contact us for help</a>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
