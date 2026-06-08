<?php

function growth_partner_parse_message(string $message): array
{
    $out = [
        'city'       => '',
        'occupation' => '',
        'experience' => '',
        'work_type'  => '',
        'team_size'  => '',
        'regions'    => '',
        'raw'        => trim($message),
    ];

    foreach (preg_split('/\r\n|\r|\n/', $message) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (str_starts_with($line, 'City: ')) {
            $out['city'] = trim(substr($line, 6));
        } elseif (str_starts_with($line, 'Occupation: ')) {
            $out['occupation'] = trim(substr($line, 12));
        } elseif (str_starts_with($line, 'Experience: ')) {
            $out['experience'] = trim(substr($line, 12));
        } elseif (str_starts_with($line, 'Work preference: ')) {
            $out['work_type'] = trim(substr($line, 17));
        } elseif (str_starts_with($line, 'Team size: ')) {
            $out['team_size'] = trim(substr($line, 11));
        } elseif (str_starts_with($line, 'Regions: ')) {
            $out['regions'] = trim(substr($line, 9));
        }
    }

    return $out;
}

function growth_partner_type_badge(string $type): string
{
    $type = strtolower(trim($type));
    if ($type === 'agency') {
        return 'bg-purple-50 text-purple-700 border-purple-100';
    }
    return 'bg-teal-50 text-teal-700 border-teal-100';
}

function growth_partner_type_label(string $type): string
{
    $type = strtolower(trim($type));
    return match ($type) {
        'agency'     => 'Agency',
        'individual' => 'Individual',
        default      => ucfirst($type ?: 'Individual'),
    };
}

function growth_partner_summary(array $parsed): string
{
    $parts = array_filter([
        $parsed['occupation'] ?? '',
        $parsed['experience'] ?? '',
        $parsed['work_type'] ?? '',
    ]);

    if ($parts !== []) {
        return implode(' · ', $parts);
    }

    $raw = trim($parsed['raw'] ?? '');
    if ($raw === '') {
        return '-';
    }

    return mb_strlen($raw) > 80 ? mb_substr($raw, 0, 80) . '…' : $raw;
}
