<?php

require_once __DIR__ . '/mail-helpers.php';
require_once __DIR__ . '/seo.php';

function marketing_unsubscribe_secret(): string
{
    return hash('sha256', 'bb_marketing_' . (getenv('BISANI_MARKETING_KEY') ?: 'bisanibrothers2026'));
}

function marketing_unsubscribe_token(string $email): string
{
    return hash_hmac('sha256', strtolower(trim($email)), marketing_unsubscribe_secret());
}

function marketing_unsubscribe_url(string $email): string
{
    $base = seo_site_url_rtrim();
    $e = rtrim(strtr(base64_encode(strtolower(trim($email))), '+/', '-_'), '=');
    $t = marketing_unsubscribe_token($email);
    return $base . '/newsletter-unsubscribe.php?e=' . urlencode($e) . '&t=' . urlencode($t);
}

function marketing_verify_unsubscribe(string $encodedEmail, string $token): ?string
{
    $email = base64_decode(strtr($encodedEmail, '-_', '+/') . str_repeat('=', (4 - strlen($encodedEmail) % 4) % 4));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    if (!hash_equals(marketing_unsubscribe_token($email), $token)) {
        return null;
    }
    return strtolower(trim($email));
}

function marketing_templates(): array
{
    return [
        'business_insights' => [
            'label'   => 'Business Insights Newsletter',
            'subject' => 'Latest Business Insights from Bisani Brothers',
            'html'    => <<<'HTML'
<h1 style="margin:0 0 16px;font-size:26px;color:#173978;">{{HEADLINE}}</h1>
<p style="margin:0 0 20px;font-size:16px;line-height:1.6;color:#475569;">{{PREHEADER}}</p>
<a href="{{IMAGE_LINK}}" style="display:block;margin:0 0 24px;text-decoration:none;">
  <img src="{{IMAGE_URL}}" alt="Featured" width="560" style="width:100%;max-width:560px;height:auto;border-radius:12px;display:block;">
</a>
<div style="font-size:16px;line-height:1.7;color:#334155;">{{BODY}}</div>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px auto 0;"><tr><td style="border-radius:8px;background:#173978;">
  <a href="{{CTA_URL}}" style="display:inline-block;padding:14px 32px;color:#ffffff;font-weight:bold;text-decoration:none;font-size:16px;">{{CTA_TEXT}}</a>
</td></tr></table>
HTML,
        ],
        'special_offer' => [
            'label'   => 'Special Offer / Promotion',
            'subject' => 'Exclusive Offer for You — Bisani Brothers',
            'html'    => <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#173978 0%,#2fcaf0 100%);border-radius:12px;margin-bottom:24px;">
  <tr><td style="padding:28px 24px;text-align:center;">
    <p style="margin:0 0 8px;font-size:13px;color:#e0f2fe;text-transform:uppercase;letter-spacing:2px;font-weight:bold;">Limited Time Offer</p>
    <h1 style="margin:0 0 12px;font-size:32px;color:#ffffff;">{{HEADLINE}}</h1>
    <p style="margin:0;font-size:18px;color:#ffffff;font-weight:bold;background:rgba(0,0,0,0.2);display:inline-block;padding:8px 20px;border-radius:999px;">Code: {{OFFER_CODE}}</p>
  </td></tr>
</table>
<a href="{{IMAGE_LINK}}"><img src="{{IMAGE_URL}}" alt="Offer" width="560" style="width:100%;max-width:560px;height:auto;border-radius:12px;margin-bottom:20px;display:block;"></a>
<div style="font-size:16px;line-height:1.7;color:#334155;">{{BODY}}</div>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px auto 0;"><tr><td style="border-radius:8px;background:#2fcaf0;">
  <a href="{{CTA_URL}}" style="display:inline-block;padding:14px 36px;color:#173978;font-weight:bold;text-decoration:none;font-size:16px;">{{CTA_TEXT}}</a>
</td></tr></table>
HTML,
        ],
        'service_spotlight' => [
            'label'   => 'Service Spotlight',
            'subject' => 'Discover Our {{HEADLINE}} Solutions',
            'html'    => <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
  <td width="240" valign="top" style="padding-right:20px;">
    <a href="{{IMAGE_LINK}}"><img src="{{IMAGE_URL}}" alt="Service" width="240" style="width:100%;max-width:240px;border-radius:12px;display:block;"></a>
  </td>
  <td valign="top">
    <h2 style="margin:0 0 12px;font-size:22px;color:#173978;">{{HEADLINE}}</h2>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#64748b;">{{PREHEADER}}</p>
    <div style="font-size:15px;line-height:1.7;color:#334155;">{{BODY}}</div>
    <p style="margin:20px 0 0;"><a href="{{CTA_URL}}" style="color:#173978;font-weight:bold;text-decoration:underline;">{{CTA_TEXT}} →</a></p>
  </td>
</tr></table>
HTML,
        ],
        'announcement' => [
            'label'   => 'Announcement / Event',
            'subject' => 'Important Announcement from Bisani Brothers',
            'html'    => <<<'HTML'
<p style="margin:0 0 8px;font-size:12px;color:#2fcaf0;text-transform:uppercase;letter-spacing:2px;font-weight:bold;">Announcement</p>
<h1 style="margin:0 0 16px;font-size:28px;color:#173978;text-align:center;">{{HEADLINE}}</h1>
<p style="margin:0 0 24px;font-size:16px;color:#64748b;text-align:center;">{{PREHEADER}}</p>
<a href="{{IMAGE_LINK}}" style="display:block;text-align:center;margin-bottom:24px;">
  <img src="{{IMAGE_URL}}" alt="Announcement" width="520" style="width:100%;max-width:520px;height:auto;border-radius:12px;">
</a>
<div style="font-size:16px;line-height:1.7;color:#334155;">{{BODY}}</div>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px auto 0;"><tr><td style="border-radius:8px;border:2px solid #173978;">
  <a href="{{CTA_URL}}" style="display:inline-block;padding:12px 28px;color:#173978;font-weight:bold;text-decoration:none;">{{CTA_TEXT}}</a>
</td></tr></table>
HTML,
        ],
        'case_study' => [
            'label'   => 'Success Story / Case Study',
            'subject' => 'How We Helped Clients Scale — Bisani Brothers',
            'html'    => <<<'HTML'
<h1 style="margin:0 0 8px;font-size:24px;color:#173978;">{{HEADLINE}}</h1>
<p style="margin:0 0 20px;font-size:14px;color:#94a3b8;font-style:italic;">{{PREHEADER}}</p>
<a href="{{IMAGE_LINK}}"><img src="{{IMAGE_URL}}" alt="Case study" width="560" style="width:100%;max-width:560px;border-radius:12px;margin-bottom:20px;display:block;"></a>
<div style="font-size:16px;line-height:1.7;color:#334155;border-left:4px solid #2fcaf0;padding-left:16px;">{{BODY}}</div>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0 0;"><tr><td style="background:#f8fafc;border-radius:8px;padding:16px 20px;">
  <p style="margin:0;font-size:14px;color:#64748b;">Ready for similar results?</p>
  <a href="{{CTA_URL}}" style="font-size:16px;color:#173978;font-weight:bold;text-decoration:none;">{{CTA_TEXT}}</a>
</td></tr></table>
HTML,
        ],
        'manual' => [
            'label'   => 'Blank / Manual Design',
            'subject' => 'Message from Bisani Brothers',
            'html'    => <<<'HTML'
<div style="font-size:16px;line-height:1.7;color:#334155;">{{BODY}}</div>
HTML,
        ],
    ];
}

function marketing_apply_placeholders(string $html, array $vars): string
{
    $defaults = [
        'HEADLINE'   => 'Your Headline Here',
        'PREHEADER'  => 'Add a short intro line for your subscribers.',
        'BODY'       => '<p>Write your message here. You can add <strong>bold text</strong>, lists, and links.</p>',
        'IMAGE_URL'  => seo_site_url_rtrim() . '/assets/images/logos.png',
        'IMAGE_LINK' => seo_site_url_rtrim() . '/',
        'CTA_TEXT'   => 'Learn More',
        'CTA_URL'    => seo_site_url_rtrim() . '/contact',
        'OFFER_CODE' => 'BBPL2026',
    ];
    $vars = array_merge($defaults, $vars);
    foreach ($vars as $key => $val) {
        $html = str_replace('{{' . strtoupper($key) . '}}', (string) $val, $html);
    }
    return $html;
}

function marketing_wrap_email(string $bodyHtml, string $email, string $previewText = ''): string
{
    $unsub = marketing_unsubscribe_url($email);
    $site = seo_site_url_rtrim();
    $year = date('Y');
    $preview = htmlspecialchars($previewText ?: 'Updates from Bisani Brothers', ENT_QUOTES, 'UTF-8');

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Bisani Brothers</title></head><body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">'
        . '<div style="display:none;max-height:0;overflow:hidden;">' . $preview . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(23,57,120,0.08);">'
        . '<tr><td style="background:#173978;padding:24px 28px;text-align:center;">'
        . '<a href="' . htmlspecialchars($site, ENT_QUOTES) . '" style="text-decoration:none;">'
        . '<img src="' . htmlspecialchars($site . '/assets/images/logos.png', ENT_QUOTES) . '" alt="Bisani Brothers" height="48" style="height:48px;width:auto;"></a>'
        . '</td></tr>'
        . '<tr><td style="padding:32px 28px;">' . $bodyHtml . '</td></tr>'
        . '<tr><td style="background:#f8fafc;padding:24px 28px;text-align:center;border-top:1px solid #e2e8f0;">'
        . '<p style="margin:0 0 8px;font-size:13px;color:#64748b;">Bisani Brothers Pvt. Ltd. · Lucknow, India</p>'
        . '<p style="margin:0 0 12px;font-size:13px;color:#64748b;">'
        . '<a href="' . htmlspecialchars($site . '/contact', ENT_QUOTES) . '" style="color:#173978;">Contact Us</a>'
        . ' · <a href="' . htmlspecialchars($site, ENT_QUOTES) . '" style="color:#173978;">Website</a></p>'
        . '<p style="margin:0;font-size:11px;color:#94a3b8;">You received this because you subscribed on bisanibrother.com.<br>'
        . '<a href="' . htmlspecialchars($unsub, ENT_QUOTES) . '" style="color:#94a3b8;">Unsubscribe</a></p>'
        . '<p style="margin:12px 0 0;font-size:11px;color:#cbd5e1;">&copy; ' . $year . ' Bisani Brothers. All rights reserved.</p>'
        . '</td></tr></table></td></tr></table></body></html>';
}

function marketing_parse_email_list(string $raw): array
{
    $found = [];
    if (preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $raw, $matches)) {
        foreach ($matches[0] as $email) {
            $email = strtolower(trim($email));
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $found[$email] = $email;
            }
        }
    }
    return array_values($found);
}

function marketing_parse_csv_emails(string $csvContent): array
{
    $emails = [];
    $lines = preg_split('/\r\n|\r|\n/', $csvContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $row = str_getcsv($line);
        foreach ($row as $cell) {
            foreach (marketing_parse_email_list((string) $cell) as $email) {
                $emails[$email] = $email;
            }
        }
    }
    return array_values($emails);
}

function marketing_emails_to_recipients(array $emails): array
{
    $out = [];
    foreach ($emails as $email) {
        $out[] = ['id' => 0, 'email' => $email];
    }
    return $out;
}

function marketing_subscribe_bulk(PDO $pdo, array $emails): int
{
    $added = 0;
    foreach ($emails as $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO newsletter_subscribers (email, status) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE status = ?'
            );
            $stmt->execute([strtolower($email), 'active', 'active']);
            $added++;
        } catch (PDOException $e) {
            error_log('marketing_subscribe_bulk: ' . $e->getMessage());
        }
    }
    return $added;
}

function marketing_recipient_mode_label(string $mode): string
{
    return match ($mode) {
        'selected' => 'selected subscribers',
        'manual'   => 'manual list',
        'import'   => 'imported list',
        default    => 'all subscribers',
    };
}

function marketing_get_recipients(PDO $pdo, string $mode, array $stored = []): array
{
    if ($mode === 'manual' || $mode === 'import') {
        $emails = [];
        foreach ($stored as $item) {
            if (is_string($item) && filter_var($item, FILTER_VALIDATE_EMAIL)) {
                $emails[strtolower($item)] = strtolower($item);
            }
        }
        return marketing_emails_to_recipients(array_values($emails));
    }

    if ($mode === 'selected' && $stored !== []) {
        $ids = array_values(array_filter(array_map('intval', $stored)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, email FROM newsletter_subscribers WHERE status='active' AND id IN ($placeholders) ORDER BY subscribed_at DESC");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $pdo->query("SELECT id, email FROM newsletter_subscribers WHERE status='active' ORDER BY subscribed_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}

function marketing_resolve_recipients(PDO $pdo, string $mode, array $selectedIds, string $manualRaw, ?string $importRaw, ?string $csvPath): array
{
    if ($mode === 'manual') {
        return marketing_emails_to_recipients(marketing_parse_email_list($manualRaw));
    }

    if ($mode === 'import') {
        $emails = marketing_parse_email_list($importRaw ?? '');
        if ($csvPath && is_readable($csvPath)) {
            $emails = array_values(array_unique(array_merge(
                $emails,
                marketing_parse_csv_emails((string) file_get_contents($csvPath))
            )));
        } elseif ($importRaw !== null && $importRaw !== '') {
            $emails = array_values(array_unique(array_merge(
                $emails,
                marketing_parse_csv_emails($importRaw)
            )));
        }
        return marketing_emails_to_recipients($emails);
    }

    return marketing_get_recipients($pdo, $mode, $selectedIds);
}

function marketing_create_campaign(PDO $pdo, array $data): int
{
    $sql = 'INSERT INTO marketing_campaigns (title, subject, body_html, template_key, recipient_mode, recipient_emails, total_recipients, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['title'],
        $data['subject'],
        $data['body_html'],
        $data['template_key'] ?? 'manual',
        $data['recipient_mode'],
        json_encode($data['recipient_emails'] ?? []),
        (int) ($data['total_recipients'] ?? 0),
        'sending',
        $data['created_by'] ?? 'admin',
    ]);
    return (int) $pdo->lastInsertId();
}

function marketing_update_campaign_counts(PDO $pdo, int $campaignId, int $sent, int $failed, string $status): void
{
    $stmt = $pdo->prepare('UPDATE marketing_campaigns SET sent_count=?, failed_count=?, status=?, sent_at=IF(?=\'sent\', NOW(), sent_at) WHERE id=?');
    $stmt->execute([$sent, $failed, $status, $status, $campaignId]);
}

function marketing_log_recipient(PDO $pdo, int $campaignId, string $email, string $status): void
{
    try {
        $stmt = $pdo->prepare('INSERT INTO marketing_campaign_logs (campaign_id, email, status, sent_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$campaignId, $email, $status]);
    } catch (PDOException $e) {
        error_log('marketing_log_recipient: ' . $e->getMessage());
    }
}

function marketing_send_to_recipient(string $subject, string $bodyHtml, string $email): bool
{
    $wrapped = marketing_wrap_email($bodyHtml, $email);
    $unsub = marketing_unsubscribe_url($email);
    return mail_send($email, $subject, $wrapped, null, ['List-Unsubscribe' => '<' . $unsub . '>']);
}

function marketing_fetch_campaigns(PDO $pdo, int $limit = 50): array
{
    return $pdo->query("SELECT * FROM marketing_campaigns ORDER BY created_at DESC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
}
