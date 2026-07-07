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
    'smtp_host' => 'smtp.hostinger.com',
    'smtp_port' => '465',
    'smtp_encryption' => 'ssl',
    'smtp_username' => 'noreply@orbtasoft.com',
    'smtp_password' => '#5C#kFHatZm',
    'smtp_from_email' => 'noreply@orbtasoft.com',
    'smtp_from_name' => 'Orbtasoft',
    'notify_email' => '',
    'notify_on_contact' => '0',
    'notify_on_newsletter' => '0',
    'contact_email_subject' => 'New contact message from {{name}}',
    'contact_email_body' => "You received a new contact form submission.\n\nName: {{name}}\nEmail: {{email}}\nMessage:\n{{message}}",
    'newsletter_email_subject' => 'New newsletter subscriber',
    'newsletter_email_body' => "You have a new newsletter subscriber.\n\nEmail: {{email}}",
    'hero_variant' => 'cinematic',
];
$stmt = $pdo->prepare('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (:k, :v)');
foreach ($defaultSettings as $key => $value) {
    $stmt->execute(['k' => $key, 'v' => $value]);
}
echo "Settings seeded (existing keys left untouched).\n";

// ---------- Site strings (every translatable string, editable from the admin Content page) ----------
if (seed_count($pdo, 'site_strings') === 0) {
    $langDe = require __DIR__ . '/../lang/de.php';
    $langEn = require __DIR__ . '/../lang/en.php';
    $langAr = require __DIR__ . '/../lang/ar.php';
    $stmt = $pdo->prepare('INSERT INTO site_strings (str_key, value_de, value_en, value_ar) VALUES (:k, :de, :en, :ar)');
    foreach ($langDe as $key => $value) {
        $stmt->execute(['k' => $key, 'de' => $value, 'en' => $langEn[$key] ?? '', 'ar' => $langAr[$key] ?? '']);
    }
    echo 'Seeded site strings (' . count($langDe) . ").\n";
}

// ---------- Services ----------
$services = [
    [
        'slug' => 'web-development', 'icon' => 'code-2', 'image' => '/assets/img/expertise-web.svg',
        'title_de' => 'Webentwicklung', 'title_en' => 'Web Development', 'title_ar' => 'تطوير الويب',
        'desc_de' => 'Schnelle, skalierbare Websites und Web-Apps mit HTML, CSS, JavaScript und PHP.',
        'desc_en' => 'Fast, scalable websites and web apps built with HTML, CSS, JavaScript and PHP.',
        'desc_ar' => 'مواقع وتطبيقات ويب سريعة وقابلة للتوسع بلغات HTML وCSS وJavaScript وPHP.',
        'content_de' => "Wir bauen Websites und Web-Apps direkt auf den Grundlagen des Webs auf: sauberes HTML, durchdachtes CSS und handgeschriebenes JavaScript, ohne unnötige Framework-Schichten dazwischen. Das Ergebnis sind Seiten, die in Millisekunden laden und sich auf jedem Gerät gleich zuverlässig verhalten.\n\nJedes Projekt beginnt mit einem klaren Informationsarchitektur-Plan, gefolgt von einem responsiven Layout, das von Mobiltelefonen bis zu großen Desktops funktioniert. Wir integrieren GSAP für Bewegung, wo sie das Erlebnis verbessert, und PHP für alles, was serverseitige Logik braucht — Kontaktformulare, Nutzerkonten, Inhalte, die sich ohne Codeänderung aktualisieren lassen.",
        'content_en' => "We build websites and web apps directly on the fundamentals of the web: clean HTML, deliberate CSS and hand-written JavaScript, without unnecessary framework layers in between. The result is pages that load in milliseconds and behave consistently on every device.\n\nEvery project starts with a clear information-architecture plan, followed by a responsive layout that works from phones to large desktops. We bring in GSAP for motion where it improves the experience, and PHP for anything that needs server-side logic — contact forms, user accounts, content that updates without a code change.",
        'content_ar' => "نبني المواقع وتطبيقات الويب مباشرة على أساسيات الويب: HTML نظيف، وCSS مدروس، وجافاسكريبت مكتوب يدويًا، دون طبقات أطر عمل غير ضرورية بينهما. النتيجة صفحات تُحمَّل خلال أجزاء من الثانية وتتصرف بثبات على كل جهاز.\n\nيبدأ كل مشروع بخطة واضحة لهيكلة المعلومات، تليها تصميمات متجاوبة تعمل من الهواتف إلى الشاشات الكبيرة. نستخدم GSAP للحركة حيثما تحسّن التجربة، وPHP لكل ما يحتاج منطقًا من جهة الخادم - نماذج التواصل، حسابات المستخدمين، محتوى يتحدث دون تعديل الكود.",
    ],
    [
        'slug' => 'databases-backend', 'icon' => 'database', 'image' => '/assets/img/expertise-backend.svg',
        'title_de' => 'Datenbanken & Backend', 'title_en' => 'Databases & Backend', 'title_ar' => 'قواعد البيانات والأنظمة الخلفية',
        'desc_de' => 'Optimierte MySQL-Schemas und sichere, zuverlässige APIs, die mit Ihrem Unternehmen wachsen.',
        'desc_en' => 'Optimized MySQL schemas and secure, reliable APIs that scale with your business.',
        'desc_ar' => 'تصميم قواعد بيانات MySQL محسّنة وواجهات برمجية آمنة وموثوقة.',
        'content_de' => "Ein Produkt ist nur so zuverlässig wie die Datenbank dahinter. Wir entwerfen MySQL-Schemas mit sauberer Normalisierung, sinnvollen Indizes und Fremdschlüsseln, die Datenintegrität von Anfang an erzwingen, statt sie später zu reparieren.\n\nAuf dieser Grundlage bauen wir PHP-APIs mit vorbereiteten Anweisungen gegen SQL-Injection, ordentlicher Fehlerbehandlung und Sitzungsverwaltung, die auch unter Last stabil bleibt. Wir haben Systeme gebaut, die täglich Millionen Zeilen verarbeiten, ohne dass sich Kunden dessen bewusst sein müssen.",
        'content_en' => "A product is only as reliable as the database behind it. We design MySQL schemas with clean normalization, sensible indexes, and foreign keys that enforce data integrity from day one instead of patching it in later.\n\nOn top of that foundation we build PHP APIs with prepared statements against SQL injection, proper error handling, and session management that stays stable under load. We've built systems that process millions of rows a day without customers ever needing to know it.",
        'content_ar' => "يعتمد أي منتج على موثوقية قاعدة بياناته. نصمم مخططات MySQL بتطبيع نظيف، وفهارس مدروسة، ومفاتيح خارجية تفرض سلامة البيانات منذ اليوم الأول بدلاً من إصلاحها لاحقًا.\n\nفوق هذا الأساس، نبني واجهات برمجية بـ PHP باستخدام الاستعلامات المُجهّزة ضد حقن SQL، ومعالجة أخطاء سليمة، وإدارة جلسات تبقى مستقرة تحت الضغط. بنينا أنظمة تعالج ملايين الصفوف يوميًا دون أن يحتاج العملاء لمعرفة ذلك.",
    ],
    [
        'slug' => 'interactive-3d-experiences', 'icon' => 'box', 'image' => '/assets/img/expertise-3d.svg',
        'title_de' => 'Interaktive 3D-Erlebnisse', 'title_en' => 'Interactive 3D Experiences', 'title_ar' => 'تجارب تفاعلية ثلاثية الأبعاد',
        'desc_de' => 'Three.js-Szenen und GSAP-Animationen verleihen Ihrer Seite echte kinematografische Präsenz.',
        'desc_en' => 'Three.js scenes and GSAP-powered motion that give your site real cinematic presence.',
        'desc_ar' => 'مشاهد Three.js وحركات GSAP تمنح موقعك حضورًا سينمائيًا فعليًا.',
        'content_de' => "Three.js lässt uns Tiefe, Licht und Bewegung direkt im Browser einsetzen, ohne dass Besucher irgendetwas installieren müssen. Wir setzen es gezielt ein: ein rotierender Globus, ein Partikelfeld, eine Szene, die auf Scrollen reagiert.\n\nGepaart mit GSAP und ScrollTrigger entsteht eine Choreografie, bei der jede Bewegung an eine bestimmte Scrollposition gebunden ist. Wir achten dabei immer auf Performance, damit die 3D-Ebene das Erlebnis unterstützt, statt es auf langsamen Geräten zu verlangsamen.",
        'content_en' => "Three.js lets us bring depth, lighting, and motion directly into the browser without asking visitors to install anything. We use it deliberately: a rotating globe, a particle field, a scene that reacts to scroll.\n\nPaired with GSAP and ScrollTrigger, this becomes a choreography where every movement is tied to a specific scroll position. We always keep an eye on performance so the 3D layer supports the experience instead of slowing it down on weaker devices.",
        'content_ar' => "يتيح لنا Three.js إدخال العمق والإضاءة والحركة مباشرة داخل المتصفح دون أن نطلب من الزوار تثبيت أي شيء. نستخدمه بشكل مدروس: كرة أرضية دوارة، حقل جسيمات، مشهد يتفاعل مع التمرير.\n\nبالاقتران مع GSAP وScrollTrigger، تتحول هذه العناصر إلى توليفة حيث ترتبط كل حركة بموضع تمرير محدد. نراقب الأداء دائمًا حتى تدعم طبقة الأبعاد الثلاثية التجربة بدلاً من إبطائها على الأجهزة الأضعف.",
    ],
    [
        'slug' => 'ui-ux-design', 'icon' => 'layout-panel-top', 'image' => '/assets/img/expertise-uiux.svg',
        'title_de' => 'UI/UX-Design', 'title_en' => 'UI/UX Design', 'title_ar' => 'واجهات وتجربة المستخدم',
        'desc_de' => 'Elegante, konversionsorientierte Interfaces mit einer intuitiven Benutzererfahrung.',
        'desc_en' => 'Elegant, conversion-focused interfaces with a smooth, intuitive user experience.',
        'desc_ar' => 'تصميم واجهات أنيقة تركز على التحويل وتجربة استخدام سلسة.',
        'content_de' => "Gutes Design ist unsichtbar, wenn es funktioniert: Besucher finden, was sie suchen, ohne darüber nachzudenken. Wir beginnen mit Nutzerflüssen und Wireframes, bevor wir uns um Farben und Typografie kümmern.\n\nJede Oberfläche, die wir entwerfen, wird mit echten Inhalten und echten Bildschirmgrößen getestet, nicht nur mit perfekten Mockups. Barrierefreiheit und mehrsprachige Unterstützung — einschließlich vollständigem RTL für Arabisch — sind von Anfang an Teil des Prozesses, nicht ein nachträglicher Gedanke.",
        'content_en' => "Good design is invisible when it works: visitors find what they're looking for without having to think about it. We start with user flows and wireframes before we touch colors and typography.\n\nEvery interface we design gets tested with real content and real screen sizes, not just pixel-perfect mockups. Accessibility and multilingual support — including full RTL for Arabic — are part of the process from day one, not an afterthought.",
        'content_ar' => "التصميم الجيد يصبح غير مرئي عندما يعمل بشكل صحيح: يجد الزوار ما يبحثون عنه دون تفكير. نبدأ بمسارات المستخدم والمخططات الأولية قبل الانتقال إلى الألوان والخطوط.\n\nكل واجهة نصممها تُختبر بمحتوى حقيقي وأحجام شاشات حقيقية، لا بنماذج مثالية فقط. إمكانية الوصول والدعم متعدد اللغات - بما في ذلك دعم كامل للاتجاه من اليمين لليسار للعربية - جزء من العملية منذ اليوم الأول، لا فكرة لاحقة.",
    ],
    [
        'slug' => 'business-applications', 'icon' => 'layout-dashboard', 'image' => '/assets/img/expertise-business.svg',
        'title_de' => 'Business-Anwendungen', 'title_en' => 'Business Applications', 'title_ar' => 'تطبيقات الأعمال',
        'desc_de' => 'Individuelle Dashboards und Verwaltungssysteme für Ihren täglichen Betrieb.',
        'desc_en' => 'Custom dashboards and management systems that run your daily operations.',
        'desc_ar' => 'لوحات تحكم وأنظمة إدارة مخصصة تدير عملياتك اليومية.',
        'content_de' => "Viele Unternehmen laufen auf einer Mischung aus Tabellen, E-Mails und veralteter Software. Wir ersetzen das durch ein einziges Dashboard, das zu Ihrem tatsächlichen Arbeitsablauf passt, statt Sie zu zwingen, sich an eine generische Vorlage anzupassen.\n\nOb Vertriebspipeline, Bestandsverwaltung oder interne Berichterstattung — wir entwerfen die Datenbankstruktur um Ihren Prozess herum und bauen ein Interface, das Ihr Team ohne Schulung versteht. Rollenbasierte Berechtigungen sorgen dafür, dass jeder nur sieht, was er sehen soll.",
        'content_en' => "Many businesses run on a mix of spreadsheets, emails, and outdated software. We replace that with a single dashboard that fits your actual workflow instead of forcing you to adapt to a generic template.\n\nWhether it's a sales pipeline, inventory management, or internal reporting, we design the database structure around your process and build an interface your team understands without training. Role-based permissions make sure everyone sees exactly what they should.",
        'content_ar' => "تعمل شركات كثيرة بمزيج من جداول البيانات والبريد الإلكتروني وبرامج قديمة. نستبدل ذلك بلوحة تحكم واحدة تناسب سير عملك الفعلي بدلاً من إجبارك على التكيف مع قالب عام.\n\nسواء كان الأمر يتعلق بمسار مبيعات، أو إدارة مخزون، أو تقارير داخلية، نصمم بنية قاعدة البيانات حول عمليتك ونبني واجهة يفهمها فريقك دون تدريب. تضمن الصلاحيات القائمة على الأدوار أن يرى كل شخص ما يجب أن يراه فقط.",
    ],
    [
        'slug' => 'maintenance-support', 'icon' => 'shield-check', 'image' => '/assets/img/expertise-business.svg',
        'title_de' => 'Wartung & Support', 'title_en' => 'Maintenance & Support', 'title_ar' => 'الصيانة والدعم',
        'desc_de' => 'Monitoring, Sicherheitsupdates und laufender Support lange nach dem Launch.',
        'desc_en' => 'Monitoring, security updates and ongoing support long after launch.',
        'desc_ar' => 'مراقبة، تحديثات أمنية، ودعم فني مستمر بعد الإطلاق.',
        'content_de' => "Der Launch ist nicht das Ende der Arbeit. Wir überwachen Fehlerprotokolle, wenden Sicherheitsupdates zeitnah an und behalten Backups im Blick, damit ein Problem entdeckt wird, bevor Ihre Kunden es merken.\n\nUnsere Support-Vereinbarungen sind unkompliziert: klare Reaktionszeiten, direkte Kommunikation und keine überraschenden Rechnungen. Egal ob es um eine kleine Anpassung oder eine dringende Fehlerbehebung geht, wir behandeln Ihr Produkt weiter wie unser eigenes.",
        'content_en' => "Launch isn't the end of the work. We monitor error logs, apply security updates promptly, and keep an eye on backups so a problem gets caught before your customers notice it.\n\nOur support agreements are straightforward: clear response times, direct communication, and no surprise invoices. Whether it's a small tweak or an urgent bug fix, we keep treating your product like our own.",
        'content_ar' => "الإطلاق ليس نهاية العمل. نراقب سجلات الأخطاء، ونطبّق التحديثات الأمنية بسرعة، ونتابع النسخ الاحتياطية بحيث تُكتشف أي مشكلة قبل أن يلاحظها عملاؤك.\n\nاتفاقيات الدعم لدينا واضحة: أوقات استجابة محددة، وتواصل مباشر، ولا فواتير مفاجئة. سواء كان الأمر تعديلاً بسيطًا أو إصلاح خطأ عاجل، نستمر في التعامل مع منتجك وكأنه منتجنا نحن.",
    ],
];

if (seed_count($pdo, 'services') === 0) {
    $stmt = $pdo->prepare('INSERT INTO services (slug, icon, image, title_de, title_en, title_ar, desc_de, desc_en, desc_ar, content_de, content_en, content_ar, sort_order) VALUES (:slug, :icon, :image, :title_de, :title_en, :title_ar, :desc_de, :desc_en, :desc_ar, :content_de, :content_en, :content_ar, :sort_order)');
    foreach ($services as $i => $s) {
        $s['sort_order'] = $i;
        $stmt->execute($s);
    }
    echo "Seeded services.\n";
} else {
    require_once __DIR__ . '/../includes/functions.php';
    $byTitle = [];
    foreach ($services as $s) {
        $byTitle[$s['title_en']] = $s;
    }
    $rows = $pdo->query("SELECT id, title_en FROM services WHERE slug = '' OR slug IS NULL")->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        $stmt = $pdo->prepare('UPDATE services SET slug=:slug, image=:image, content_de=:content_de, content_en=:content_en, content_ar=:content_ar WHERE id=:id');
        foreach ($rows as $row) {
            $match = $byTitle[$row['title_en']] ?? null;
            if ($match) {
                $stmt->execute([
                    'slug' => $match['slug'], 'image' => $match['image'], 'content_de' => $match['content_de'],
                    'content_en' => $match['content_en'], 'content_ar' => $match['content_ar'], 'id' => $row['id'],
                ]);
            } else {
                $pdo->prepare('UPDATE services SET slug = :slug WHERE id = :id')
                    ->execute(['slug' => slugify($row['title_en']) . '-' . $row['id'], 'id' => $row['id']]);
            }
        }
        echo 'Backfilled ' . count($rows) . " service(s).\n";
    }
}

// ---------- Portfolio ----------
$portfolio = [
        [
            'slug' => 'nova-analytics', 'image' => '/assets/img/project-nova.svg', 'title' => 'Nova Analytics',
            'client' => 'Nova Analytics Inc.', 'year' => '2025', 'project_url' => 'https://nova-analytics.example.com',
            'tag_de' => 'SaaS-Dashboard', 'tag_en' => 'SaaS Dashboard', 'tag_ar' => 'لوحة تحكم SaaS',
            'description_de' => 'Nova Analytics brauchte ein Echtzeit-Dashboard, das täglich Millionen Ereignisse visualisieren kann, ohne das Betriebsteam zu überfordern. Wir haben das gesamte Frontend um ein leichtgewichtiges Komponentensystem herum neu aufgebaut, es mit einem optimierten MySQL-Schema für Zeitreihenabfragen kombiniert und GSAP-gesteuerte Mikrointeraktionen eingebaut, damit sich jede Diagrammaktualisierung bewusst und nicht abrupt anfühlt. Ergebnis: Die Ladezeiten sanken um 60 %, und das Support-Team meldet deutlich weniger Anfragen der Art „Ist das Dashboard kaputt?".',
            'description_en' => 'Nova Analytics needed a real-time dashboard that could visualize millions of events per day without overwhelming their operations team. We rebuilt their entire front-end around a lightweight component system, paired it with an optimized MySQL schema for time-series queries, and layered in GSAP-driven micro-interactions so every chart update feels intentional rather than jarring. The result: page load times dropped by 60%, and the support team reports far fewer "is the dashboard broken?" tickets.',
            'description_ar' => 'احتاجت Nova Analytics إلى لوحة تحكم فورية قادرة على تصور ملايين الأحداث يوميًا دون إرهاق فريق العمليات. أعدنا بناء الواجهة الأمامية بالكامل حول نظام مكونات خفيف، ودمجناها مع مخطط MySQL محسّن لاستعلامات السلاسل الزمنية، وأضفنا تفاعلات دقيقة مدعومة بـ GSAP بحيث يبدو كل تحديث للرسم البياني مقصودًا لا مفاجئًا. النتيجة: انخفضت أوقات التحميل بنسبة 60%، وأصبح فريق الدعم يستقبل عددًا أقل بكثير من تذاكر "هل لوحة التحكم معطلة؟".',
        ],
        [
            'slug' => 'vertex-commerce', 'image' => '/assets/img/project-vertex.svg', 'title' => 'Vertex Commerce',
            'client' => 'Vertex Commerce', 'year' => '2024', 'project_url' => 'https://vertex-commerce.example.com',
            'tag_de' => 'E-Commerce-Plattform', 'tag_en' => 'E-Commerce Platform', 'tag_ar' => 'منصة تجارة إلكترونية',
            'description_de' => 'Vertex Commerce kam mit einem Checkout-Prozess zu uns, der fast ein Drittel der Kunden zwischen Warenkorb und Bestätigung verlor. Wir haben die gesamte Kaufreise um einen Einzelseiten-Checkout herum neu gestaltet, Inline-Validierung statt vollständiger Fehler-Neuladungen hinzugefügt und eine individuelle PHP/MySQL-Bestellpipeline gebaut, die Verkaufsspitzen ohne Ausfälle bewältigt. Die Konversionsrate verbesserte sich bereits im ersten Monat, und die Plattform bewältigt heute problemlos den größten Verkaufstag des Jahres.',
            'description_en' => 'Vertex Commerce came to us with a checkout flow that was losing nearly a third of customers between cart and confirmation. We redesigned the entire purchase journey around a single-page checkout, added inline validation instead of full-page error reloads, and built a custom PHP/MySQL order pipeline that could handle flash-sale traffic spikes without falling over. Conversion improved within the first month, and the platform now comfortably handles their biggest sales day of the year.',
            'description_ar' => 'جاءت Vertex Commerce إلينا بعملية دفع كانت تفقد ما يقارب ثلث العملاء بين سلة الشراء والتأكيد. أعدنا تصميم رحلة الشراء بأكملها حول صفحة دفع واحدة، وأضفنا التحقق الفوري بدل إعادة تحميل الصفحة عند الخطأ، وبنينا خط معالجة طلبات مخصص بـ PHP وMySQL يتحمل ذروة الحركة في أيام العروض دون تعطل. تحسّن معدل التحويل خلال الشهر الأول، والمنصة الآن تتعامل بسهولة مع أكبر يوم مبيعات في السنة.',
        ],
        [
            'slug' => 'lumen-booking', 'image' => '/assets/img/project-lumen.svg', 'title' => 'Lumen Booking',
            'client' => 'Lumen Booking', 'year' => '2024', 'project_url' => 'https://lumen-booking.example.com',
            'tag_de' => 'Buchungssystem', 'tag_en' => 'Booking System', 'tag_ar' => 'نظام حجز',
            'description_de' => 'Lumen Booking verwaltet die Terminplanung für Dutzende kleiner Kliniken, und das alte System vergab denselben Termin doppelt, wenn zwei Personen zur gleichen Sekunde buchten. Wir haben den Buchungskern mit korrektem Datenbank-Locking neu aufgebaut, eine Kalenderoberfläche mit Live-Verfügbarkeit hinzugefügt und automatische E-Mail-Erinnerungen eingerichtet, wodurch die Nichterscheinen-Quote im ersten Quartal nach dem Launch spürbar sank.',
            'description_en' => 'Lumen Booking runs appointment scheduling for dozens of small clinics, and their old system double-booked slots whenever two people booked at the same second. We rebuilt the booking core with proper database-level locking, added a calendar UI with live availability, and wired up automatic email reminders so no-show rates dropped noticeably in the first quarter after launch.',
            'description_ar' => 'تدير Lumen Booking جدولة المواعيد لعشرات العيادات الصغيرة، وكان نظامها القديم يحجز نفس الموعد مرتين عندما يقوم شخصان بالحجز في نفس الثانية. أعدنا بناء نواة الحجز باستخدام قفل صحيح على مستوى قاعدة البيانات، وأضفنا واجهة تقويم بتوفر فوري، وربطنا تذكيرات بريد إلكتروني تلقائية، ما أدى إلى انخفاض ملحوظ في معدل الغياب عن المواعيد خلال الربع الأول بعد الإطلاق.',
        ],
        [
            'slug' => 'atlas-crm', 'image' => '/assets/img/project-atlas.svg', 'title' => 'Atlas CRM',
            'client' => 'Atlas CRM', 'year' => '2023', 'project_url' => 'https://atlas-crm.example.com',
            'tag_de' => 'Business-App', 'tag_en' => 'Business App', 'tag_ar' => 'تطبيق أعمال',
            'description_de' => 'Das Vertriebsteam von Atlas CRM verfolgte Leads zuvor über vier verschiedene Tabellen, bevor wir übernommen haben. Wir haben ein einheitliches Dashboard mit Pipeline-Phasen, Aktivitätsverläufen und rollenbasierten Berechtigungen gebaut, alles auf derselben PHP/MySQL-Basis wie der Rest unseres Stacks. Die Vertriebsleiter haben jetzt eine einzige verlässliche Quelle, statt Updates über Slack hinterherzujagen.',
            'description_en' => "Atlas CRM's sales team was tracking leads across four different spreadsheets before we stepped in. We built them a unified dashboard with pipeline stages, activity timelines, and role-based permissions, all on the same PHP/MySQL foundation the rest of our stack uses. Their sales managers now get a single source of truth instead of chasing updates over Slack.",
            'description_ar' => 'كان فريق مبيعات Atlas CRM يتابع العملاء المحتملين عبر أربعة جداول بيانات مختلفة قبل أن نتدخل. بنينا لهم لوحة تحكم موحدة بمراحل واضحة لمسار المبيعات، وسجل زمني للأنشطة، وصلاحيات حسب الدور، كل ذلك على نفس أساس PHP وMySQL المستخدم في باقي مجموعتنا التقنية. أصبح مديرو المبيعات الآن يمتلكون مصدرًا واحدًا موثوقًا بدل ملاحقة التحديثات عبر Slack.',
        ],
];

if (seed_count($pdo, 'portfolio_items') === 0) {
    $stmt = $pdo->prepare('INSERT INTO portfolio_items (slug, image, title, client, year, project_url, tag_de, tag_en, tag_ar, description_de, description_en, description_ar, sort_order) VALUES (:slug, :image, :title, :client, :year, :project_url, :tag_de, :tag_en, :tag_ar, :description_de, :description_en, :description_ar, :sort_order)');
    foreach ($portfolio as $i => $p) {
        $p['sort_order'] = $i;
        $stmt->execute($p);
    }
    echo "Seeded portfolio items.\n";
} else {
    // Backfill detail fields (slug/client/year/url/description) for rows created before these columns existed.
    require_once __DIR__ . '/../includes/functions.php';
    $byTitle = [];
    foreach ($portfolio as $p) {
        $byTitle[$p['title']] = $p;
    }
    $rows = $pdo->query("SELECT id, title FROM portfolio_items WHERE client = '' OR client IS NULL")->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        $stmt = $pdo->prepare('UPDATE portfolio_items SET slug = :slug, client = :client, year = :year, project_url = :project_url, description_de = :description_de, description_en = :description_en, description_ar = :description_ar WHERE id = :id');
        foreach ($rows as $row) {
            $match = $byTitle[$row['title']] ?? null;
            if ($match) {
                $stmt->execute([
                    'slug' => $match['slug'], 'client' => $match['client'], 'year' => $match['year'],
                    'project_url' => $match['project_url'], 'description_de' => $match['description_de'],
                    'description_en' => $match['description_en'], 'description_ar' => $match['description_ar'],
                    'id' => $row['id'],
                ]);
            } else {
                $pdo->prepare('UPDATE portfolio_items SET slug = :slug WHERE id = :id')
                    ->execute(['slug' => slugify($row['title']) . '-' . $row['id'], 'id' => $row['id']]);
            }
        }
        echo 'Backfilled ' . count($rows) . " portfolio item(s).\n";
    }
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

// ---------- Blog posts ----------
if (seed_count($pdo, 'blog_posts') === 0) {
    $posts = [
        [
            'slug' => 'cinematic-web-experiences',
            'cover_image' => '/assets/img/blog-cinematic.svg',
            'author' => 'Lina Hartmann',
            'title_de' => 'Wie wir kinoreife Web-Erlebnisse gestalten',
            'title_en' => 'How We Design Cinematic Web Experiences',
            'title_ar' => 'كيف نصمم تجارب ويب سينمائية',
            'excerpt_de' => 'Ein Blick hinter die Kulissen unserer Motion-Design-Philosophie und wie GSAP und Three.js zusammenspielen.',
            'excerpt_en' => 'A behind-the-scenes look at our motion design philosophy and how GSAP and Three.js work together.',
            'excerpt_ar' => 'نظرة خلف الكواليس على فلسفتنا في تصميم الحركة وكيف يعمل GSAP وThree.js معًا.',
            'content_de' => "Wenn Besucher unsere Websites zum ersten Mal öffnen, wollen wir, dass es sich wie der Beginn eines Films anfühlt: ein klarer Ton, eine bewusste Kamera, ein Gefühl von Absicht in jeder Bewegung. Das ist kein Zufall, sondern das Ergebnis eines Designprozesses, der Bewegung von Anfang an mitdenkt, statt sie nachträglich hinzuzufügen.\n\nWir beginnen jedes Projekt mit einem Storyboard, nicht mit einem Mockup. Bevor eine einzige Zeile CSS geschrieben wird, skizzieren wir, wie sich der Nutzer durch die Seite bewegt: Was tritt zuerst ins Bild, was folgt der Maus, was reagiert auf das Scrollen. Diese Choreografie wird später mit GSAP und ScrollTrigger umgesetzt, die uns erlauben, Animationen präzise an die Scrollposition zu koppeln, statt sie einfach beim Laden abzuspielen.\n\nThree.js kommt ins Spiel, wenn flache Bilder nicht ausreichen. Ein rotierender Globus, ein Partikelfeld, eine 3D-Szene, die auf die Kamera reagiert – all das gibt einer Seite Tiefe, ohne dass der Nutzer eine App installieren oder auf einen Ladebildschirm warten muss. Der Trick ist, es sparsam einzusetzen: 3D soll das Erzählen unterstützen, nicht das Hauptereignis sein.\n\nAm Ende ist kinoreifes Webdesign vor allem eine Frage des Timings. Eine Animation, die eine Zehntelsekunde zu früh oder zu spät kommt, fühlt sich falsch an, selbst wenn der Nutzer nicht genau sagen kann, warum. Wir verbringen genauso viel Zeit mit dem Feintuning von Easing-Kurven wie mit dem eigentlichen Layout – und genau das macht am Ende den Unterschied.",
            'content_en' => "When visitors open one of our websites for the first time, we want it to feel like the opening shot of a film: a clear tone, a deliberate camera, a sense of intention behind every movement. That's not an accident — it's the result of a design process that treats motion as a first-class citizen instead of an afterthought bolted on at the end.\n\nWe start every project with a storyboard, not a mockup. Before a single line of CSS gets written, we sketch how a visitor will move through the page: what enters first, what follows the cursor, what reacts to scroll. That choreography later gets implemented with GSAP and ScrollTrigger, which let us tie animations precisely to scroll position instead of just firing them on page load.\n\nThree.js comes into play when flat imagery isn't enough. A rotating globe, a particle field, a 3D scene that reacts to the camera — these add depth to a page without asking the visitor to install an app or sit through a loading screen. The trick is restraint: 3D should support the story, not become the main event.\n\nIn the end, cinematic web design mostly comes down to timing. An animation that fires a tenth of a second too early or too late feels wrong, even if the visitor can't quite articulate why. We spend as much time tuning easing curves as we do on the actual layout — and that's exactly what makes the difference.",
            'content_ar' => "عندما يفتح الزوار أحد مواقعنا لأول مرة، نريد أن يشعروا وكأنهم أمام أول مشهد من فيلم: نبرة واضحة، كاميرا مدروسة، وإحساس بالقصد وراء كل حركة. هذا ليس مصادفة، بل نتيجة عملية تصميم تتعامل مع الحركة كعنصر أساسي منذ البداية، وليس إضافة لاحقة.\n\nنبدأ كل مشروع بلوحة قصة (storyboard) لا بنموذج ثابت. قبل كتابة أي سطر CSS، نرسم كيف سيتحرك الزائر عبر الصفحة: ما الذي يظهر أولاً، وما الذي يتبع المؤشر، وما الذي يتفاعل مع التمرير. تُنفَّذ هذه التوليفة لاحقًا باستخدام GSAP وScrollTrigger، مما يتيح لنا ربط الحركات بدقة بموضع التمرير بدلاً من تشغيلها فقط عند تحميل الصفحة.\n\nيأتي دور Three.js عندما لا تكفي الصور المسطحة. كرة أرضية دوارة، حقل من الجسيمات، مشهد ثلاثي الأبعاد يتفاعل مع الكاميرا - كل هذا يضيف عمقًا للصفحة دون أن يُطلب من الزائر تثبيت تطبيق أو انتظار شاشة تحميل. السر هو الاعتدال: يجب أن يخدم العنصر الثلاثي الأبعاد القصة، لا أن يصبح هو النجم.\n\nفي النهاية، يعتمد التصميم السينمائي للويب في الغالب على التوقيت. الحركة التي تبدأ أبكر أو أتأخر من اللازم بجزء من الثانية تبدو خاطئة، حتى لو لم يستطع الزائر تحديد السبب بدقة. نقضي وقتًا في ضبط منحنيات التسارع (easing) بقدر ما نقضيه في التخطيط الفعلي للصفحة - وهذا بالضبط ما يصنع الفرق.",
            'published_at' => '2026-05-12 09:00:00',
        ],
        [
            'slug' => 'lessons-shipping-80-projects',
            'cover_image' => '/assets/img/blog-lessons.svg',
            'author' => 'Youssef Adel',
            'title_de' => '5 Lektionen aus über 80 ausgelieferten Projekten',
            'title_en' => '5 Lessons From Shipping 80+ Projects',
            'title_ar' => '5 دروس من تسليم أكثر من 80 مشروعًا',
            'excerpt_de' => 'Was uns Dutzende Projekte über Scope, Kommunikation und Infrastruktur beigebracht haben.',
            'excerpt_en' => 'What dozens of projects have taught us about scope, communication, and infrastructure.',
            'excerpt_ar' => 'ما تعلمناه من عشرات المشاريع حول النطاق والتواصل والبنية التحتية.',
            'content_de' => "Über die Jahre haben wir mehr als 80 Projekte für Kunden in unterschiedlichsten Branchen ausgeliefert. Manche liefen reibungslos, andere haben uns schmerzhafte Lektionen erteilt. Hier sind fünf davon, die wir immer wieder anwenden.\n\n1. Scope Creep beginnt harmlos. Eine kleine Änderungsanfrage hier, ein „kann man das nicht auch noch schnell...\" dort – und plötzlich ist der Zeitplan explodiert. Wir haben gelernt, jede Änderung schriftlich festzuhalten und ihre Auswirkung auf Zeit und Kosten sofort zu benennen, statt sie stillschweigend zu absorbieren.\n\n2. Schlechte Nachrichten früh überbringen. Ein Kunde, der in Woche zwei erfährt, dass ein Meilenstein wackelt, kann planen. Ein Kunde, der es in der letzten Woche erfährt, ist zu Recht wütend. Wir haben uns eine Regel gesetzt: Probleme werden innerhalb von 24 Stunden nach Entdeckung kommuniziert.\n\n3. In langweilige Infrastruktur investieren. Automatisierte Backups, Staging-Umgebungen, Fehlerprotokolle – nichts davon ist aufregend, aber all das hat uns wiederholt vor Katastrophen bewahrt, die andernfalls das Vertrauen eines Kunden für immer gekostet hätten.\n\n4. Design und Engineering müssen sich im selben Raum unterhalten, nicht nacheinander. Projekte, bei denen Designer und Entwickler von Tag eins an zusammenarbeiten, brauchen deutlich weniger Nacharbeit als solche, bei denen ein fertiges Design einfach „übergeben\" wird.\n\n5. Kleiner und häufiger ausliefern schlägt groß und selten. Ein Kunde, der alle zwei Wochen etwas Funktionierendes sieht, vertraut dem Prozess. Ein Kunde, der drei Monate lang nichts sieht, beginnt zu zweifeln – egal wie gut die Arbeit im Hintergrund läuft.",
            'content_en' => "Over the years we've shipped more than 80 projects for clients across a wide range of industries. Some went smoothly, others taught us painful lessons. Here are five we keep applying on every new engagement.\n\n1. Scope creep starts small. One tiny change request here, one \"can you also just quickly...\" there — and suddenly the timeline has blown up. We've learned to write down every change request and immediately state its impact on time and cost, instead of quietly absorbing it.\n\n2. Deliver bad news early. A client who learns in week two that a milestone is at risk can plan around it. A client who learns in the final week is rightly furious. We hold ourselves to a rule: problems get communicated within 24 hours of being discovered.\n\n3. Invest in boring infrastructure. Automated backups, staging environments, error logging — none of it is exciting, but all of it has repeatedly saved us from disasters that would otherwise have cost a client's trust permanently.\n\n4. Design and engineering need to talk in the same room, not in sequence. Projects where designers and developers collaborate from day one need far less rework than ones where a finished design simply gets \"handed off.\"\n\n5. Shipping smaller and more often beats shipping big and rarely. A client who sees something working every two weeks trusts the process. A client who sees nothing for three months starts to doubt it, no matter how well things are actually going behind the scenes.",
            'content_ar' => "على مدى السنوات، سلّمنا أكثر من 80 مشروعًا لعملاء في مجالات متنوعة. بعضها سار بسلاسة، وبعضها علّمنا دروسًا مؤلمة. إليك خمسة دروس ما زلنا نطبقها في كل مشروع جديد.\n\n1. توسّع النطاق (Scope Creep) يبدأ صغيرًا. طلب تعديل بسيط هنا، و\"هل يمكن أيضًا وبسرعة...\" هناك - وفجأة ينفجر الجدول الزمني. تعلّمنا أن نوثّق كل طلب تعديل ونوضح أثره على الوقت والتكلفة فورًا، بدلاً من استيعابه بصمت.\n\n2. أوصل الأخبار السيئة مبكرًا. العميل الذي يعرف في الأسبوع الثاني أن معلمًا رئيسيًا في خطر يمكنه التخطيط لذلك. أما العميل الذي يعرف في الأسبوع الأخير فغضبه مبرر تمامًا. وضعنا لأنفسنا قاعدة: تُبلَّغ المشاكل خلال 24 ساعة من اكتشافها.\n\n3. استثمر في البنية التحتية \"المملة\". النسخ الاحتياطي التلقائي، بيئات الاختبار، تسجيل الأخطاء - لا شيء من هذا مثير، لكن كل ذلك أنقذنا مرارًا من كوارث كانت ستكلف ثقة العميل إلى الأبد.\n\n4. يجب أن يتحدث التصميم والهندسة في نفس الغرفة، لا بالتسلسل. المشاريع التي يتعاون فيها المصممون والمطورون منذ اليوم الأول تحتاج إعادة عمل أقل بكثير من تلك التي يُسلَّم فيها تصميم جاهز ببساطة.\n\n5. التسليم بأجزاء أصغر وأكثر تكرارًا أفضل من التسليم الكبير النادر. العميل الذي يرى شيئًا يعمل كل أسبوعين يثق بالعملية. أما العميل الذي لا يرى شيئًا لمدة ثلاثة أشهر فيبدأ بالشك، بغض النظر عن مدى جودة سير العمل خلف الكواليس.",
            'published_at' => '2026-04-02 09:00:00',
        ],
        [
            'slug' => 'why-php-mysql-modern-web-apps',
            'cover_image' => '/assets/img/blog-stack.svg',
            'author' => 'Marco Lindqvist',
            'title_de' => 'Warum wir 2026 immer noch auf PHP und MySQL setzen',
            'title_en' => 'Why We Still Choose PHP and MySQL in 2026',
            'title_ar' => 'لماذا لا نزال نختار PHP وMySQL في 2026',
            'excerpt_de' => 'Kein Hype, nur ein Stack, der überall läuft, sich bewährt hat und sich gut mit modernem Frontend verträgt.',
            'excerpt_en' => 'No hype, just a stack that runs everywhere, has proven itself, and pairs well with a modern frontend.',
            'excerpt_ar' => 'لا ضجيج، فقط تقنية تعمل في كل مكان وأثبتت جدارتها وتتناسب جيدًا مع واجهة أمامية حديثة.',
            'content_de' => "Es ist leicht, sich von jedem neuen JavaScript-Framework mitreißen zu lassen, das diese Woche erscheint. Aber wenn ein Kunde uns bittet, etwas zu bauen, das in fünf Jahren noch von einem beliebigen Entwickler gewartet werden kann, landen wir immer wieder bei PHP und MySQL.\n\nPHP läuft praktisch überall. Jeder Hoster, jeder Cloud-Anbieter, jedes Billig-Shared-Hosting unterstützt es out of the box. Das mag unglamourös klingen, ist aber für einen Kunden mit begrenztem Budget entscheidend: keine Container-Orchestrierung, kein Serverless-Vendor-Lock-in, nur ein Ordner, den man per FTP hochladen kann, wenn es sein muss.\n\nMySQL (und seine Forks wie MariaDB) sind seit über 25 Jahren im Härtetest. Die Fehlerfälle sind bekannt, die Dokumentation ist ausgereift, und praktisch jeder Backend-Entwickler kann eine Datenbank lesen, die jemand anderes entworfen hat. Das ist keine Innovationsbremse, sondern ein Fundament, auf dem man ruhig schlafen kann.\n\nWas viele überrascht: Nichts hindert uns daran, dieses „langweilige\" Backend mit einem hochmodernen Frontend zu kombinieren. GSAP, Three.js und handgeschriebenes modernes JavaScript laufen wunderbar über einer PHP-API. Der Kunde bekommt ein Erlebnis, das sich 2026 anfühlt, unterstützt von einem Stack, der auch 2036 noch problemlos gewartet werden kann.",
            'content_en' => "It's easy to get swept up in whatever new JavaScript framework launched this week. But when a client asks us to build something that any competent developer can still maintain in five years, we keep landing back on PHP and MySQL.\n\nPHP runs practically everywhere. Every host, every cloud provider, every cheap shared-hosting plan supports it out of the box. That might sound unglamorous, but it matters enormously to a client on a limited budget: no container orchestration, no serverless vendor lock-in, just a folder you can upload over FTP if you really have to.\n\nMySQL (and its forks like MariaDB) have been battle-tested for over 25 years. The failure modes are well understood, the documentation is mature, and practically any backend developer can read a database someone else designed. That's not a lack of innovation — it's a foundation you can sleep soundly on.\n\nWhat surprises a lot of people: nothing stops us from pairing this \"boring\" backend with a genuinely modern frontend. GSAP, Three.js, and hand-written modern JavaScript run beautifully on top of a PHP API. The client gets an experience that feels like 2026, backed by a stack that will still be maintainable without drama in 2036.",
            'content_ar' => "من السهل الانجراف وراء أي إطار عمل جافاسكريبت جديد يظهر هذا الأسبوع. لكن عندما يطلب منا عميل بناء شيء يستطيع أي مطور كفء صيانته بعد خمس سنوات، نعود دائمًا إلى PHP وMySQL.\n\nيعمل PHP عمليًا في كل مكان. كل مزود استضافة، وكل مزود سحابي، وكل خطة استضافة مشتركة رخيصة تدعمه مباشرة. قد يبدو هذا غير مثير، لكنه بالغ الأهمية لعميل بميزانية محدودة: لا حاجة لتنسيق الحاويات، ولا ارتباط بمزود serverless معين، فقط مجلد يمكنك رفعه عبر FTP إذا اضطررت لذلك حقًا.\n\nأثبت MySQL (وفروعه مثل MariaDB) جدارته عبر اختبار ميداني لأكثر من 25 عامًا. حالات الفشل معروفة جيدًا، والتوثيق ناضج، وأي مطور خلفية تقريبًا يستطيع قراءة قاعدة بيانات صممها شخص آخر. هذا ليس نقصًا في الابتكار، بل أساس يمكنك أن تنام مطمئنًا عليه.\n\nما يفاجئ الكثيرين: لا شيء يمنعنا من دمج هذه الخلفية \"المملة\" مع واجهة أمامية حديثة بحق. يعمل GSAP وThree.js وجافاسكريبت حديث مكتوب يدويًا بشكل رائع فوق واجهة برمجية PHP. يحصل العميل على تجربة تشعر وكأنها من عام 2026، مدعومة بتقنية ستظل قابلة للصيانة دون دراما في عام 2036.",
            'published_at' => '2026-02-18 09:00:00',
        ],
        [
            'slug' => 'building-multilingual-rtl-websites',
            'cover_image' => '/assets/img/blog-i18n.svg',
            'author' => 'Sara El-Amin',
            'title_de' => 'Barrierefreie, mehrsprachige Websites bauen',
            'title_en' => 'Building Accessible, Multilingual Websites',
            'title_ar' => 'بناء مواقع متعددة اللغات ويسهل الوصول إليها',
            'excerpt_de' => 'Was uns der Support von Deutsch, Englisch und Arabisch auf derselben Codebasis über RTL wirklich beigebracht hat.',
            'excerpt_en' => 'What supporting German, English, and Arabic on one codebase actually taught us about RTL.',
            'excerpt_ar' => 'ما تعلمناه فعليًا عن دعم الاتجاه من اليمين لليسار عند دعم الألمانية والإنجليزية والعربية على قاعدة كود واحدة.',
            'content_de' => "Mehrsprachigkeit klingt nach einer reinen Übersetzungsaufgabe, bis man Arabisch hinzufügt und merkt, dass sich die halbe Seite in die falsche Richtung dreht. Hier sind ein paar Dinge, die uns auf die harte Tour beigebracht wurden.\n\nCSS-Logical-Properties reichen allein nicht aus. `margin-inline-start` statt `margin-left` zu verwenden, löst viele Probleme automatisch, aber nicht alle. Layouts, die auf Pixel-genauem `translateX` für Animationen basieren, brechen in RTL, weil die Bewegungsrichtung selbst gespiegelt werden muss – das übernimmt keine Logical Property.\n\nFlexbox- und Grid-Reihenfolge kann Animationen sabotieren, die auf fester Reihenfolge beruhen. Unser Logo-Marquee zum Beispiel verschwand komplett vom Bildschirm in RTL, weil sich schrumpfende Block-Boxen in RTL standardmäßig rechts ausrichten, kombiniert mit einer Animation, die von einer Links-nach-rechts-Bewegung ausging. Die Lösung war, den Marquee-Container explizit auf `direction: ltr` zu setzen und die visuelle Spiegelung dem übergeordneten Container zu überlassen.\n\nArabisch ist eine Schreibschrift – Buchstaben verbinden sich je nach Nachbarn. Eine Technik, die wir für gestaffelte Texteffekte auf Englisch verwendet hatten (Text in einzelne Buchstaben-Spans aufteilen), zerstörte diese Verbindungen komplett und produzierte unleserlichen Text. Die Lösung war, arabischen Text per Regex zu erkennen und auf Wortebene statt auf Buchstabenebene zu animieren.",
            'content_en' => "Multilingual support sounds like a pure translation task until you add Arabic and half the page starts spinning in the wrong direction. Here are a few things that taught us hard lessons.\n\nCSS logical properties aren't enough on their own. Swapping `margin-left` for `margin-inline-start` fixes a lot automatically, but not everything. Layouts built on pixel-precise `translateX` for animation break under RTL because the direction of motion itself needs to flip — no logical property does that for you.\n\nFlexbox and grid ordering can sabotage animations that assume a fixed order. Our logo marquee, for instance, disappeared entirely off-screen under RTL, because shrink-to-fit block boxes right-align by default in RTL, combined with an animation that assumed left-to-right motion. The fix was forcing the marquee container to `direction: ltr` explicitly and letting the parent container handle the visual mirroring.\n\nArabic is a cursive script — letters connect differently depending on their neighbors. A technique we'd used for staggered text effects in English (splitting text into individual letter spans) completely broke those connections and produced garbled, unreadable text. The fix was detecting Arabic text with a regex and animating at the word level instead of the letter level.",
            'content_ar' => "يبدو دعم تعدد اللغات مهمة ترجمة بسيطة إلى أن تضيف العربية فيبدأ نصف الصفحة بالدوران في الاتجاه الخاطئ. إليك بعض الأمور التي علّمتنا دروسًا قاسية.\n\nخصائص CSS المنطقية (logical properties) وحدها لا تكفي. استبدال `margin-left` بـ `margin-inline-start` يحل الكثير تلقائيًا، لكن ليس كل شيء. التخطيطات المبنية على `translateX` بدقة البكسل للحركة تنكسر في اتجاه اليمين لليسار لأن اتجاه الحركة نفسه يجب أن ينعكس - ولا توجد خاصية منطقية تقوم بذلك نيابة عنك.\n\nترتيب Flexbox وGrid يمكن أن يخرّب الحركات التي تفترض ترتيبًا ثابتًا. شريط شعارات العملاء المتحرك لدينا، على سبيل المثال، اختفى تمامًا خارج الشاشة في اتجاه اليمين لليسار، لأن الصناديق التي تتقلص لتناسب محتواها تُحاذى لليمين افتراضيًا في هذا الاتجاه، مع حركة كانت تفترض حركة من اليسار لليمين. كان الحل هو فرض `direction: ltr` على حاوية الشريط المتحرك صراحةً وترك المرآة البصرية للحاوية الأم.\n\nاللغة العربية خط متصل - الحروف تتصل بشكل مختلف حسب جيرانها. تقنية استخدمناها لتأثيرات النص المتدرج بالإنجليزية (تقسيم النص إلى عناصر منفصلة لكل حرف) دمّرت تلك الاتصالات تمامًا وأنتجت نصًا مشوهًا يتعذر قراءته. كان الحل هو اكتشاف النص العربي عبر تعبير نمطي (regex) وتحريكه على مستوى الكلمة بدلاً من مستوى الحرف.",
            'published_at' => '2026-06-20 09:00:00',
        ],
    ];
    $stmt = $pdo->prepare('INSERT INTO blog_posts (slug, cover_image, author, title_de, title_en, title_ar, excerpt_de, excerpt_en, excerpt_ar, content_de, content_en, content_ar, published_at) VALUES (:slug, :cover_image, :author, :title_de, :title_en, :title_ar, :excerpt_de, :excerpt_en, :excerpt_ar, :content_de, :content_en, :content_ar, :published_at)');
    foreach ($posts as $p) {
        $stmt->execute($p);
    }
    echo "Seeded blog posts.\n";
}

echo "Done.\n";
