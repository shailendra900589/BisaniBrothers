<?php

function testimonials_table_has_locale(PDO $pdo): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    try {
        $has = (bool) $pdo->query("SHOW COLUMNS FROM testimonials LIKE 'locale'")->fetch();
    } catch (PDOException $e) {
        $has = false;
    }
    return $has;
}

function testimonials_from_lang(int $limit = 6): array
{
    locale_init();
    $items = $GLOBALS['_locale_strings']['testimonials'] ?? [];
    if (!is_array($items) || $items === []) {
        return [];
    }

    $out = [];
    foreach (array_slice($items, 0, $limit) as $i => $item) {
        if (!is_array($item) || empty($item['quote'])) {
            continue;
        }
        $out[] = [
            'id'           => 'lang-' . ($i + 1),
            'name'         => $item['name'] ?? 'Client Partner',
            'role_title'   => $item['role_title'] ?? '',
            'company'      => $item['company'] ?? '',
            'quote'        => $item['quote'],
            'service_line' => $item['service_line'] ?? '',
            'rating'       => (int) ($item['rating'] ?? 5),
            'is_active'    => 1,
            'sort_order'   => $i + 1,
        ];
    }
    return $out;
}

function testimonials_fetch_active(PDO $pdo, int $limit = 6): array
{
    locale_init();
    $locale = locale_current();

    if ($locale !== LOCALE_DEFAULT) {
        $localized = testimonials_from_lang($limit);
        if ($localized !== []) {
            return $localized;
        }
    }

    try {
        if (testimonials_table_has_locale($pdo)) {
            $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE is_active = 1 AND locale = ? ORDER BY sort_order ASC, id DESC LIMIT ?');
            $stmt->bindValue(1, $locale, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rows !== []) {
                return $rows;
            }
            if ($locale !== LOCALE_DEFAULT) {
                $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE is_active = 1 AND locale = ? ORDER BY sort_order ASC, id DESC LIMIT ?');
                $stmt->bindValue(1, LOCALE_DEFAULT, PDO::PARAM_STR);
                $stmt->bindValue(2, $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return testimonials_from_lang($limit);
    }
}
