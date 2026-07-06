<?php
/**
 * One-time seed: populates the admin-manageable content tables with the
 * site's current default content, and creates the initial admin user.
 * Run once after importing schema.sql: php sql/seed.php
 */

require __DIR__ . '/../config/database.php';

$pdo = get_db();
if (!$pdo) {
    fwrite(STDERR, "Could not connect to the database. Check config/database.php / env vars.\n");
    exit(1);
}

function seed_count(PDO $pdo, string $table): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

$palette = [
    'linear-gradient(145deg, #3b82f6, #162c6d)',
    'linear-gradient(145deg, #a855f7, #3b1e6d)',
    'linear-gradient(145deg, #2dd4bf, #0d4d4d)',
    'linear-gradient(145deg, #f472b6, #5a2a4d)',
    'linear-gradient(145deg, #22c55e, #1f5a35)',
    'linear-gradient(145deg, #fbbf24, #5a3a10)',
];

// ---------- Admin user ----------
if (seed_count($pdo, 'admin_users') === 0) {
    $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (:u, :p)');
    $stmt->execute(['u' => 'minaboules', 'p' => password_hash('mina2002306', PASSWORD_DEFAULT)]);
    echo "Created admin user: minaboules\n";
}

// ---------- Settings ----------
$defaultSettings = [
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_encryption' => 'tls',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_from_email' => 'hello@orbtasoft.dev',
    'smtp_from_name' => 'Orbtasoft',
    'notify_email' => '',
    'notify_on_contact' => '0',
    'notify_on_newsletter' => '0',
    'contact_email_subject' => 'New contact message from {{name}}',
    'contact_email_body' => "You received a new contact form submission.\n\nName: {{name}}\nEmail: {{email}}\nMessage:\n{{message}}",
    'newsletter_email_subject' => 'New newsletter subscriber',
    'newsletter_email_body' => "You have a new newsletter subscriber.\n\nEmail: {{email}}",
];
$stmt = $pdo->prepare('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (:k, :v)');
foreach ($defaultSettings as $key => $value) {
    $stmt->execute(['k' => $key, 'v' => $value]);
}
echo "Settings seeded (existing keys left untouched).\n";

// ---------- Services ----------
if (seed_count($pdo, 'services') === 0) {
    $services = [
        ['code-2', 'Webentwicklung', 'Web Development', 'تطوير الويب',
            'Schnelle, skalierbare Websites und Web-Apps mit HTML, CSS, JavaScript und PHP.',
            'Fast, scalable websites and web apps built with HTML, CSS, JavaScript and PHP.',
            'مواقع وتطبيقات ويب سريعة وقابلة للتوسع بلغات HTML وCSS وJavaScript وPHP.'],
        ['database', 'Datenbanken & Backend', 'Databases & Backend', 'قواعد البيانات والأنظمة الخلفية',
            'Optimierte MySQL-Schemas und sichere, zuverlässige APIs, die mit Ihrem Unternehmen wachsen.',
            'Optimized MySQL schemas and secure, reliable APIs that scale with your business.',
            'تصميم قواعد بيانات MySQL محسّنة وواجهات برمجية آمنة وموثوقة.'],
        ['box', 'Interaktive 3D-Erlebnisse', 'Interactive 3D Experiences', 'تجارب تفاعلية ثلاثية الأبعاد',
            'Three.js-Szenen und GSAP-Animationen verleihen Ihrer Seite echte kinematografische Präsenz.',
            'Three.js scenes and GSAP-powered motion that give your site real cinematic presence.',
            'مشاهد Three.js وحركات GSAP تمنح موقعك حضورًا سينمائيًا فعليًا.'],
        ['layout-panel-top', 'UI/UX-Design', 'UI/UX Design', 'واجهات وتجربة المستخدم',
            'Elegante, konversionsorientierte Interfaces mit einer intuitiven Benutzererfahrung.',
            'Elegant, conversion-focused interfaces with a smooth, intuitive user experience.',
            'تصميم واجهات أنيقة تركز على التحويل وتجربة استخدام سلسة.'],
        ['layout-dashboard', 'Business-Anwendungen', 'Business Applications', 'تطبيقات الأعمال',
            'Individuelle Dashboards und Verwaltungssysteme für Ihren täglichen Betrieb.',
            'Custom dashboards and management systems that run your daily operations.',
            'لوحات تحكم وأنظمة إدارة مخصصة تدير عملياتك اليومية.'],
        ['shield-check', 'Wartung & Support', 'Maintenance & Support', 'الصيانة والدعم',
            'Monitoring, Sicherheitsupdates und laufender Support lange nach dem Launch.',
            'Monitoring, security updates and ongoing support long after launch.',
            'مراقبة، تحديثات أمنية، ودعم فني مستمر بعد الإطلاق.'],
    ];
    $stmt = $pdo->prepare('INSERT INTO services (icon, title_de, title_en, title_ar, desc_de, desc_en, desc_ar, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($services as $i => $s) {
        $stmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $i]);
    }
    echo "Seeded services.\n";
}

// ---------- Portfolio ----------
if (seed_count($pdo, 'portfolio_items') === 0) {
    $portfolio = [
        ['/assets/img/project-nova.svg', 'Nova Analytics', 'SaaS-Dashboard', 'SaaS Dashboard', 'لوحة تحكم SaaS'],
        ['/assets/img/project-vertex.svg', 'Vertex Commerce', 'E-Commerce-Plattform', 'E-Commerce Platform', 'منصة تجارة إلكترونية'],
        ['/assets/img/project-lumen.svg', 'Lumen Booking', 'Buchungssystem', 'Booking System', 'نظام حجز'],
        ['/assets/img/project-atlas.svg', 'Atlas CRM', 'Business-App', 'Business App', 'تطبيق أعمال'],
    ];
    $stmt = $pdo->prepare('INSERT INTO portfolio_items (image, title, tag_de, tag_en, tag_ar, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($portfolio as $i => $p) {
        $stmt->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $i]);
    }
    echo "Seeded portfolio items.\n";
}

// ---------- Team ----------
if (seed_count($pdo, 'team_members') === 0) {
    $team = [
        ['Youssef Adel', 'CEO & Gründer', 'CEO & Founder', 'الرئيس التنفيذي والمؤسس'],
        ['Lina Hartmann', 'Head of Design', 'Head of Design', 'رئيس قسم التصميم'],
        ['Marco Lindqvist', 'Tech Lead', 'Tech Lead', 'قائد تقني'],
        ['Sara El-Amin', 'Content Director', 'Content Director', 'مدير المحتوى'],
        ['Tom Richter', 'Backend-Entwickler', 'Backend Engineer', 'مهندس أنظمة خلفية'],
        ['Maya Okafor', 'QA & Research Lead', 'QA & Research Lead', 'قائد الجودة والأبحاث'],
    ];
    $stmt = $pdo->prepare('INSERT INTO team_members (name, role_de, role_en, role_ar, color, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($team as $i => $m) {
        $stmt->execute([$m[0], $m[1], $m[2], $m[3], $palette[$i % count($palette)], $i]);
    }
    echo "Seeded team members.\n";
}

// ---------- Testimonials ----------
if (seed_count($pdo, 'testimonials') === 0) {
    $testimonials = [
        ['Rania Kassab', 'COO, Vertex Commerce', 'COO, Vertex Commerce', 'مدير العمليات، Vertex Commerce',
            'Orbtasoft hat unser Dashboard komplett neu gebaut, die Codequalität war herausragend. Die Kommunikation war jederzeit klar.',
            'Orbtasoft rebuilt our dashboard from scratch and the quality of the code was outstanding. Communication was clear at every step.',
            'أعادت أوربتاسوفت بناء لوحة التحكم الخاصة بنا من الصفر، وكانت جودة الكود استثنائية. التواصل كان واضحًا في كل خطوة.',
            $palette[0]],
        ['Jonas Weber', 'Gründer, Solstice Labs', 'Founder, Solstice Labs', 'المؤسس، Solstice Labs',
            'Das kinematografische Design hat jede Erwartung übertroffen. Unsere Conversion-Rate stieg innerhalb weniger Wochen nach dem Launch.',
            'The cinematic design they delivered exceeded every expectation. Our conversion rate jumped within weeks of launch.',
            'التصميم السينمائي الذي قدموه فاق كل التوقعات. ارتفع معدل التحويل لدينا خلال أسابيع من الإطلاق.',
            $palette[2]],
        ['Amara Chukwu', 'Product Lead, Atlas CRM', 'Product Lead, Atlas CRM', 'قائدة المنتج، Atlas CRM',
            'Ein seltenes Team, das in Engineering und Design gleichermaßen stark ist. Pünktlich geliefert, und das Produkt funktioniert einfach.',
            'A rare team that\'s equally strong on engineering and design. They shipped on time and the product just works.',
            'فريق نادر قوي في الهندسة والتصميم معًا. سلّموا في الموعد والمنتج يعمل ببساطة كما ينبغي.',
            $palette[4]],
    ];
    $stmt = $pdo->prepare('INSERT INTO testimonials (name, role_de, role_en, role_ar, quote_de, quote_en, quote_ar, color, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($testimonials as $i => $t) {
        $stmt->execute([$t[0], $t[1], $t[2], $t[3], $t[4], $t[5], $t[6], $t[7], $i]);
    }
    echo "Seeded testimonials.\n";
}

// ---------- Partners ----------
if (seed_count($pdo, 'partners') === 0) {
    $partners = [
        ['Nova Analytics', 800], ['Vertex Commerce', 700], ['Lumen Booking', 600], ['Atlas CRM', 800],
        ['Solstice Labs', 700], ['Nexora', 900], ['Brightfield', 600], ['Cobalt Systems', 700],
    ];
    $stmt = $pdo->prepare('INSERT INTO partners (name, weight, sort_order) VALUES (?, ?, ?)');
    foreach ($partners as $i => $p) {
        $stmt->execute([$p[0], $p[1], $i]);
    }
    echo "Seeded partners.\n";
}

// ---------- Tech stack ----------
if (seed_count($pdo, 'tech_stack') === 0) {
    $stack = ['HTML5', 'CSS3', 'JavaScript', 'PHP', 'MySQL', 'Three.js', 'GSAP', 'Framer'];
    $stmt = $pdo->prepare('INSERT INTO tech_stack (name, sort_order) VALUES (?, ?)');
    foreach ($stack as $i => $name) {
        $stmt->execute([$name, $i]);
    }
    echo "Seeded tech stack.\n";
}

echo "Done.\n";
