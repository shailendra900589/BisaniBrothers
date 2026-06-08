<?php
require dirname(__DIR__) . '/db.php';

$slug = 'top-finance-company-near-me-for-fast-loan-approval';
$tags = 'FinTech, Personal Loan, Fast Loan Approval, Lucknow, Business Loan';
$faq = json_encode([
    ['question' => 'How fast can I get loan approval near me?', 'answer' => 'Many NBFCs and digital lenders offer approval within 24–48 hours when documents are complete and eligibility criteria are met. Bisani Brothers helps compare options for faster processing.'],
    ['question' => 'What documents are required for a personal loan?', 'answer' => 'Typically you need identity proof (Aadhaar/PAN), address proof, salary slips or bank statements, and passport-size photographs. Requirements may vary by lender.'],
    ['question' => 'Which finance company is best for quick loans in Lucknow?', 'answer' => 'Compare interest rates, processing fees, and approval timelines across banks, NBFCs, and housing finance companies. Bisani Brothers assists borrowers in finding suitable lenders based on profile and need.'],
], JSON_UNESCAPED_UNICODE);

$stmt = $pdo->prepare('UPDATE blogs SET tags = ?, faq_json = ? WHERE slug = ?');
$stmt->execute([$tags, $faq, $slug]);
echo 'Updated rows: ' . $stmt->rowCount() . PHP_EOL;
