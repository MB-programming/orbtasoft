<?php

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

function icon(string $name): string
{
    $icons = [
        'code-2' => '<path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
        'box' => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/>',
        'layout-panel-top' => '<rect x="3" y="3" width="18" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'layout-dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'shield-check' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
    ];

    $path = $icons[$name] ?? $icons['box'];
    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
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
