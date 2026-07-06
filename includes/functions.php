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

function t(string $key): string
{
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

function team_data(): array
{
    $names = ['Youssef Adel', 'Lina Hartmann', 'Marco Lindqvist', 'Sara El-Amin', 'Tom Richter', 'Maya Okafor'];
    $roleKeys = ['team_role_1', 'team_role_2', 'team_role_3', 'team_role_4', 'team_role_5', 'team_role_6'];
    $team = [];
    foreach ($names as $i => $name) {
        $team[] = ['name' => $name, 'role' => t($roleKeys[$i]), 'color' => avatar_palette($i)];
    }
    return $team;
}

function testimonials_data(): array
{
    return [
        ['quote' => t('testimonial_1_quote'), 'name' => t('testimonial_1_name'), 'role' => t('testimonial_1_role'), 'color' => avatar_palette(0)],
        ['quote' => t('testimonial_2_quote'), 'name' => t('testimonial_2_name'), 'role' => t('testimonial_2_role'), 'color' => avatar_palette(2)],
        ['quote' => t('testimonial_3_quote'), 'name' => t('testimonial_3_name'), 'role' => t('testimonial_3_role'), 'color' => avatar_palette(4)],
    ];
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
    ];

    $path = $icons[$name] ?? $icons['box'];
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

function partners_data(): array
{
    // Fictional client/partner wordmarks (rendered as styled text, not real company logos).
    return [
        ['name' => 'Nova Analytics',  'weight' => 800],
        ['name' => 'Vertex Commerce', 'weight' => 700],
        ['name' => 'Lumen Booking',   'weight' => 600],
        ['name' => 'Atlas CRM',       'weight' => 800],
        ['name' => 'Solstice Labs',   'weight' => 700],
        ['name' => 'Nexora',          'weight' => 900],
        ['name' => 'Brightfield',     'weight' => 600],
        ['name' => 'Cobalt Systems',  'weight' => 700],
    ];
}

function services_data(): array
{
    return [
        ['icon' => 'code-2',        'title' => t('service_1_title'), 'desc' => t('service_1_desc')],
        ['icon' => 'database',      'title' => t('service_2_title'), 'desc' => t('service_2_desc')],
        ['icon' => 'box',           'title' => t('service_3_title'), 'desc' => t('service_3_desc')],
        ['icon' => 'layout-panel-top', 'title' => t('service_4_title'), 'desc' => t('service_4_desc')],
        ['icon' => 'layout-dashboard', 'title' => t('service_5_title'), 'desc' => t('service_5_desc')],
        ['icon' => 'shield-check',  'title' => t('service_6_title'), 'desc' => t('service_6_desc')],
    ];
}

function expertise_data(): array
{
    return [
        ['title' => t('service_1_title'), 'image' => '/assets/img/expertise-web.svg'],
        ['title' => t('service_2_title'), 'image' => '/assets/img/expertise-backend.svg'],
        ['title' => t('service_3_title'), 'image' => '/assets/img/expertise-3d.svg'],
        ['title' => t('service_4_title'), 'image' => '/assets/img/expertise-uiux.svg'],
        ['title' => t('service_5_title'), 'image' => '/assets/img/expertise-business.svg'],
    ];
}

function stack_data(): array
{
    return ['HTML5', 'CSS3', 'JavaScript', 'PHP', 'MySQL', 'Three.js', 'GSAP', 'Framer'];
}

function portfolio_data(): array
{
    return [
        [
            'title'   => 'Nova Analytics',
            'tag'     => 'SaaS Dashboard',
            'image'   => '/assets/img/project-nova.svg',
        ],
        [
            'title'   => 'Vertex Commerce',
            'tag'     => 'E-Commerce Platform',
            'image'   => '/assets/img/project-vertex.svg',
        ],
        [
            'title'   => 'Lumen Booking',
            'tag'     => 'Booking System',
            'image'   => '/assets/img/project-lumen.svg',
        ],
        [
            'title'   => 'Atlas CRM',
            'tag'     => 'Business App',
            'image'   => '/assets/img/project-atlas.svg',
        ],
    ];
}
