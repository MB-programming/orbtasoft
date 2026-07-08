<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }

    $user = null;
    if (!empty($_SESSION['user_id'])) {
        require_once __DIR__ . '/../config/database.php';
        $pdo = get_db();
        if ($pdo) {
            $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = :id');
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $user = $row;
            }
        }
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_valid(string $token): bool
{
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function current_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    $available = ['de', 'en', 'ar'];
    $requested = $_GET['lang'] ?? $_COOKIE['orbta_lang'] ?? 'de';

    if (!in_array($requested, $available, true)) {
        $requested = 'de';
    }

    if (isset($_GET['lang']) && !headers_sent()) {
        setcookie('orbta_lang', $requested, time() + 60 * 60 * 24 * 365, '/');
    }

    $lang = $requested;
    return $lang;
}

function trans(): array
{
    static $strings = null;
    if ($strings === null) {
        $strings = require __DIR__ . '/../lang/' . current_lang() . '.php';
    }
    return $strings;
}

function site_strings_db(): array
{
    static $rows = null;
    if ($rows === null) {
        require_once __DIR__ . '/../config/database.php';
        $pdo = get_db();
        $rows = [];
        if ($pdo) {
            try {
                $rows = $pdo->query('SELECT str_key, value_de, value_en, value_ar FROM site_strings')->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
            } catch (PDOException $e) {
                $rows = [];
            }
        }
    }
    return $rows;
}

function t(string $key): string
{
    $dbStrings = site_strings_db();
    if (isset($dbStrings[$key])) {
        $column = 'value_' . lang_column_suffix();
        if (isset($dbStrings[$key][$column]) && $dbStrings[$key][$column] !== '') {
            return $dbStrings[$key][$column];
        }
    }
    $strings = trans();
    return $strings[$key] ?? $key;
}

function dir_attr(): string
{
    return current_lang() === 'ar' ? 'rtl' : 'ltr';
}

function available_langs(): array
{
    return [
        'de' => 'Deutsch',
        'en' => 'English',
        'ar' => 'العربية',
    ];
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters);
}

function avatar_palette(int $i): string
{
    $palette = [
        'linear-gradient(145deg, #3b82f6, #162c6d)',
        'linear-gradient(145deg, #a855f7, #3b1e6d)',
        'linear-gradient(145deg, #2dd4bf, #0d4d4d)',
        'linear-gradient(145deg, #f472b6, #5a2a4d)',
        'linear-gradient(145deg, #22c55e, #1f5a35)',
        'linear-gradient(145deg, #fbbf24, #5a3a10)',
    ];
    return $palette[$i % count($palette)];
}

function lang_column_suffix(): string
{
    $lang = current_lang();
    return in_array($lang, ['de', 'en', 'ar'], true) ? $lang : 'en';
}

function db_fetch_all(string $sql): array
{
    require_once __DIR__ . '/../config/database.php';
    $pdo = get_db();
    if (!$pdo) {
        return [];
    }
    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('db_fetch_all failed: ' . $e->getMessage());
        return [];
    }
}

function team_data(): array
{
    $lang = lang_column_suffix();
    $rows = db_fetch_all("SELECT id, name, image, role_{$lang} AS role, color FROM team_members ORDER BY sort_order ASC, id ASC");
    return array_map(fn($r) => ['id' => (int) $r['id'], 'name' => $r['name'], 'image' => $r['image'], 'role' => $r['role'], 'color' => $r['color']], $rows);
}

function testimonials_data(): array
{
    $lang = lang_column_suffix();
    $rows = db_fetch_all("SELECT id, name, image, role_{$lang} AS role, quote_{$lang} AS quote, color FROM testimonials ORDER BY sort_order ASC, id ASC");
    return array_map(fn($r) => ['id' => (int) $r['id'], 'name' => $r['name'], 'image' => $r['image'], 'role' => $r['role'], 'quote' => $r['quote'], 'color' => $r['color']], $rows);
}

function icon(string $name): string
{
    $icons = [
        'code-2' => '<path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
        'box' => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/>',
        'layout-panel-top' => '<rect x="3" y="3" width="18" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'layout-dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'shield-check' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'user-star' => '<path d="M16.051 12.616a1 1 0 0 1 1.909.024l.737 1.452a1 1 0 0 0 .737.535l1.634.256a1 1 0 0 1 .588 1.806l-1.172 1.168a1 1 0 0 0-.282.866l.259 1.613a1 1 0 0 1-1.541 1.134l-1.465-.75a1 1 0 0 0-.912 0l-1.465.75a1 1 0 0 1-1.539-1.133l.258-1.613a1 1 0 0 0-.282-.866l-1.156-1.153a1 1 0 0 1 .572-1.822l1.633-.256a1 1 0 0 0 .737-.535z"/><path d="M8 15H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/>',
        'arrow-left' => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'eye' => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/>',
        'mail' => '<path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/>',
        'phone' => '<path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.735 1.567l-.465.336a1 1 0 0 0-.303 1.212 12.04 12.04 0 0 0 5.335 5.453z"/>',
        'map-pin' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        'rocket' => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>',
        'check-circle' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'brand-mark' => '<ellipse cx="12" cy="12" rx="9" ry="4" fill="none" transform="rotate(-30 12 12)"/><circle cx="12" cy="12" r="2.6" fill="currentColor" stroke="none"/><circle cx="20" cy="7.2" r="1.6" fill="currentColor" stroke="none"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'star' => '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>',
        'briefcase' => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect x="2" y="6" width="20" height="14" rx="2"/>',
        'send' => '<path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/><path d="m21.854 2.147-10.94 10.939"/>',
        'settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'trash' => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'pencil' => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>',
        'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'grip' => '<circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/>',
        'file-text' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'paperclip' => '<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
        'mic' => '<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/>',
        'smile' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/>',
        'gauge' => '<path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'shield-alert' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'bar-chart-3' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7 16h8"/><path d="M7 11h12"/><path d="M7 6h3"/>',
        'lock' => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    ];

    $path = $icons[$name] ?? $icons['box'];
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

function partners_data(): array
{
    // Fictional client/partner logos + names (logo marks are placeholder illustrations, not real company logos).
    $rows = db_fetch_all('SELECT id, name, logo, weight FROM partners ORDER BY sort_order ASC, id ASC');
    return array_map(fn($r) => ['id' => (int) $r['id'], 'name' => $r['name'], 'logo' => $r['logo'], 'weight' => (int) $r['weight']], $rows);
}

function services_data(): array
{
    $lang = lang_column_suffix();
    $rows = db_fetch_all("SELECT id, slug, icon, title_{$lang} AS title, desc_{$lang} AS `desc` FROM services ORDER BY sort_order ASC, id ASC");
    return array_map(fn($r) => ['id' => (int) $r['id'], 'slug' => $r['slug'], 'icon' => $r['icon'], 'title' => $r['title'], 'desc' => $r['desc']], $rows);
}

function service_by_slug(string $slug): ?array
{
    $lang = lang_column_suffix();
    $sql = "SELECT id, slug, icon, image, title_{$lang} AS title, desc_{$lang} AS description, content_{$lang} AS content
            FROM services WHERE slug = " . quote_for_lookup($slug) . " LIMIT 1";
    $rows = db_fetch_all($sql);
    return $rows[0] ?? null;
}

function service_neighbors(int $id): array
{
    $rows = db_fetch_all('SELECT id, slug FROM services ORDER BY sort_order ASC, id ASC');
    $index = null;
    foreach ($rows as $i => $r) {
        if ((int) $r['id'] === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null || !$rows) {
        return ['prev' => null, 'next' => null];
    }
    $count = count($rows);
    return [
        'prev' => $rows[($index - 1 + $count) % $count]['slug'],
        'next' => $rows[($index + 1) % $count]['slug'],
    ];
}

function expertise_data(): array
{
    $imageMap = [
        'code-2' => '/assets/img/expertise-web.svg',
        'database' => '/assets/img/expertise-backend.svg',
        'box' => '/assets/img/expertise-3d.svg',
        'layout-panel-top' => '/assets/img/expertise-uiux.svg',
        'layout-dashboard' => '/assets/img/expertise-business.svg',
        'shield-check' => '/assets/img/expertise-business.svg',
    ];
    $services = array_slice(services_data(), 0, 5);
    return array_map(fn($s) => [
        'title' => $s['title'],
        'image' => $imageMap[$s['icon']] ?? '/assets/img/expertise-web.svg',
    ], $services);
}

function stack_data(): array
{
    $rows = db_fetch_all('SELECT name FROM tech_stack ORDER BY sort_order ASC, id ASC');
    return array_map(fn($r) => $r['name'], $rows);
}

function portfolio_data(): array
{
    $lang = lang_column_suffix();
    $rows = db_fetch_all("SELECT id, slug, image, title, client, year, category, tag_{$lang} AS tag FROM portfolio_items ORDER BY sort_order ASC, id ASC");
    return array_map(fn($r) => [
        'id' => (int) $r['id'],
        'slug' => $r['slug'],
        'title' => $r['title'],
        'tag' => $r['tag'],
        'image' => $r['image'],
        'client' => $r['client'],
        'year' => $r['year'],
        'category' => $r['category'],
    ], $rows);
}

function portfolio_item_by_slug(string $slug): ?array
{
    $lang = lang_column_suffix();
    $sql = "SELECT id, slug, image, title, client, year, project_url, category, technologies, duration,
                   metric_1_label, metric_1_value, metric_2_label, metric_2_value, metric_3_label, metric_3_value,
                   tag_{$lang} AS tag, description_{$lang} AS description
            FROM portfolio_items WHERE slug = " . quote_for_lookup($slug) . " LIMIT 1";
    $rows = db_fetch_all($sql);
    $item = $rows[0] ?? null;
    if ($item) {
        $item['gallery'] = db_fetch_all('SELECT image FROM portfolio_gallery WHERE portfolio_id = ' . (int) $item['id'] . ' ORDER BY sort_order ASC, id ASC');
    }
    return $item;
}

function portfolio_categories(): array
{
    return [
        'web' => t('portfolio_category_web'),
        'uiux' => t('portfolio_category_uiux'),
        'mobile' => t('portfolio_category_mobile'),
        'business' => t('portfolio_category_business'),
    ];
}

function portfolio_neighbors(int $id): array
{
    $rows = db_fetch_all('SELECT id, slug FROM portfolio_items ORDER BY sort_order ASC, id ASC');
    $index = null;
    foreach ($rows as $i => $r) {
        if ((int) $r['id'] === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null || !$rows) {
        return ['prev' => null, 'next' => null];
    }
    $count = count($rows);
    return [
        'prev' => $rows[($index - 1 + $count) % $count]['slug'],
        'next' => $rows[($index + 1) % $count]['slug'],
    ];
}

function blog_posts_data(): array
{
    $lang = lang_column_suffix();
    $sql = "SELECT id, slug, cover_image, author, title_{$lang} AS title, excerpt_{$lang} AS excerpt, published_at
            FROM blog_posts ORDER BY published_at DESC, id DESC";
    return db_fetch_all($sql);
}

function blog_post_by_slug(string $slug): ?array
{
    $lang = lang_column_suffix();
    $sql = "SELECT id, slug, cover_image, author, title_{$lang} AS title, excerpt_{$lang} AS excerpt, content_{$lang} AS content, published_at
            FROM blog_posts WHERE slug = " . quote_for_lookup($slug) . " LIMIT 1";
    $rows = db_fetch_all($sql);
    return $rows[0] ?? null;
}

function blog_neighbors(int $id): array
{
    $rows = db_fetch_all('SELECT id, slug FROM blog_posts ORDER BY published_at DESC, id DESC');
    $index = null;
    foreach ($rows as $i => $r) {
        if ((int) $r['id'] === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null || !$rows) {
        return ['prev' => null, 'next' => null];
    }
    $count = count($rows);
    return [
        'prev' => $rows[($index - 1 + $count) % $count]['slug'],
        'next' => $rows[($index + 1) % $count]['slug'],
    ];
}

function format_date(string $datetime): string
{
    $locales = ['de' => 'de_DE', 'en' => 'en_US', 'ar' => 'ar_EG'];
    $locale = $locales[current_lang()] ?? 'en_US';
    $formatter = new IntlDateFormatter($locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
    $ts = strtotime($datetime);
    return $ts ? $formatter->format($ts) : $datetime;
}

function reading_minutes(string $content): int
{
    $words = str_word_count(strip_tags($content));
    if ($words === 0) {
        $words = (int) round(mb_strlen($content) / 6);
    }
    return max(1, (int) ceil($words / 200));
}

function site_setting(string $key, string $default = ''): string
{
    require_once __DIR__ . '/mailer.php';
    static $settings = null;
    if ($settings === null) {
        $settings = get_settings();
    }
    return $settings[$key] ?? $default;
}

function quote_for_lookup(string $value): string
{
    require_once __DIR__ . '/../config/database.php';
    $pdo = get_db();
    return $pdo ? $pdo->quote($value) : "''";
}
