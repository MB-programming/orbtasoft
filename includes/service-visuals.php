<?php
/**
 * Per-service creative content: a unique animated "hero visual" component per
 * service slug, plus feature bullets and a technology list. Feature/tech copy
 * is fixed structural content (like the tool pages), so it lives here rather
 * than in the database or lang files — only the shared section headings and
 * the couple of short in-graphic labels go through t().
 */

function service_visual_data(): array
{
    $lang = current_lang();

    $data = [
        'web-development' => [
            'visual' => 'code',
            'tech' => ['HTML5', 'CSS3', 'JavaScript', 'PHP', 'MySQL'],
            'features' => [
                'de' => ['Responsive Design-Systeme', 'Schnelle Ladezeiten (Core Web Vitals)', 'SEO-freundliches Markup', 'Progressive Enhancement'],
                'en' => ['Responsive design systems', 'Fast load times (Core Web Vitals)', 'SEO-friendly markup', 'Progressive enhancement'],
                'ar' => ['أنظمة تصميم متجاوبة', 'سرعة تحميل عالية (Core Web Vitals)', 'كود متوافق مع SEO', 'تحسين تدريجي (Progressive Enhancement)'],
            ],
        ],
        'databases-backend' => [
            'visual' => 'schema',
            'tech' => ['MySQL', 'PHP', 'PDO', 'REST APIs'],
            'features' => [
                'de' => ['Normalisiertes Schema-Design', 'Sichere REST-APIs', 'Query-Performance-Tuning', 'Automatisierte Backups'],
                'en' => ['Normalized schema design', 'Secure REST APIs', 'Query performance tuning', 'Automated backups'],
                'ar' => ['تصميم مخطط بيانات منظم (Normalized)', 'واجهات REST API آمنة', 'ضبط أداء الاستعلامات', 'نسخ احتياطي تلقائي'],
            ],
        ],
        'interactive-3d-experiences' => [
            'visual' => '3d',
            'tech' => ['Three.js', 'WebGL', 'GSAP', 'Canvas'],
            'features' => [
                'de' => ['WebGL-Szenen mit Three.js', 'Scroll-gesteuerte Animation mit GSAP', 'Optimiert für 60 fps', 'Grazile Fallbacks für ältere Geräte'],
                'en' => ['WebGL scenes built with Three.js', 'Scroll-driven motion with GSAP', 'Optimized for 60fps', 'Graceful fallbacks on low-end devices'],
                'ar' => ['مشاهد WebGL بـ Three.js', 'حركة مرتبطة بالتمرير عبر GSAP', 'محسّن لـ 60 إطار/ثانية', 'بدائل سلسة للأجهزة الأضعف'],
            ],
        ],
        'ui-ux-design' => [
            'visual' => 'design',
            'tech' => ['Figma', 'Design Tokens', 'Component Libraries', 'WCAG'],
            'features' => [
                'de' => ['User Research & Wireframes', 'High-Fidelity-Prototypen', 'Design-Systeme & Komponenten', 'Barrierefrei nach WCAG'],
                'en' => ['User research & wireframing', 'High-fidelity prototypes', 'Design systems & component libraries', 'Accessibility-first (WCAG)'],
                'ar' => ['أبحاث المستخدمين ووايرفريمز', 'نماذج أولية عالية الدقة', 'أنظمة تصميم ومكتبات مكونات', 'إمكانية وصول حسب معايير WCAG'],
            ],
        ],
        'business-applications' => [
            'visual' => 'dashboard',
            'tech' => ['PHP', 'MySQL', 'Data Visualization', 'REST APIs'],
            'features' => [
                'de' => ['Individuelle Admin-Dashboards', 'Rollenbasierte Zugriffskontrolle', 'Reporting & Analysen', 'Workflow-Automatisierung'],
                'en' => ['Custom admin dashboards', 'Role-based access control', 'Reporting & analytics', 'Workflow automation'],
                'ar' => ['لوحات تحكم إدارية مخصصة', 'صلاحيات وصول حسب الدور', 'تقارير وتحليلات', 'أتمتة سير العمل'],
            ],
        ],
        'maintenance-support' => [
            'visual' => 'support',
            'tech' => ['Monitoring', 'Backups', 'Patch Management', 'SLA'],
            'features' => [
                'de' => ['24/7-Verfügbarkeitsüberwachung', 'Sicherheitsupdates', 'Regelmäßige Backups', 'Priority-Support-SLA'],
                'en' => ['24/7 uptime monitoring', 'Security patching', 'Regular backups', 'Priority support SLA'],
                'ar' => ['مراقبة تشغيل على مدار الساعة', 'تحديثات أمان دورية', 'نسخ احتياطي منتظم', 'اتفاقية دعم بأولوية عالية (SLA)'],
            ],
        ],
    ];

    foreach ($data as $slug => &$entry) {
        $entry['features'] = $entry['features'][$lang] ?? $entry['features']['en'];
    }

    return $data;
}

function render_service_visual(string $slug): string
{
    switch ($slug) {
        case 'web-development':
            return '<div class="service-visual sv-code" data-visual="code">
                <div class="sv-code__bar"><span class="sv-code__dot sv-code__dot--r"></span><span class="sv-code__dot sv-code__dot--y"></span><span class="sv-code__dot sv-code__dot--g"></span><span class="sv-code__filename">build.php</span></div>
                <div class="sv-code__body">
                    <div class="sv-code__line"><span class="sv-code__num">1</span><span class="sv-code__kw">function</span> <span class="sv-code__fn">launchProject</span>() {</div>
                    <div class="sv-code__line"><span class="sv-code__num">2</span>&nbsp;&nbsp;<span class="sv-code__kw">return</span> [</div>
                    <div class="sv-code__line"><span class="sv-code__num">3</span>&nbsp;&nbsp;&nbsp;&nbsp;<span class="sv-code__str">\'fast\'</span> => <span class="sv-code__bool">true</span>,</div>
                    <div class="sv-code__line"><span class="sv-code__num">4</span>&nbsp;&nbsp;&nbsp;&nbsp;<span class="sv-code__str">\'scalable\'</span> => <span class="sv-code__bool">true</span>,</div>
                    <div class="sv-code__line"><span class="sv-code__num">5</span>&nbsp;&nbsp;&nbsp;&nbsp;<span class="sv-code__str">\'beautiful\'</span> => <span class="sv-code__bool">true</span>,</div>
                    <div class="sv-code__line"><span class="sv-code__num">6</span>&nbsp;&nbsp;];</div>
                    <div class="sv-code__line"><span class="sv-code__num">7</span>}<span class="sv-code__cursor"></span></div>
                </div>
            </div>';

        case 'databases-backend':
            return '<div class="service-visual sv-schema" data-visual="schema">
                <svg viewBox="0 0 400 240" class="sv-schema__svg" preserveAspectRatio="xMidYMid meet">
                    <path class="sv-schema__link" d="M100,50 C160,50 160,120 200,120" />
                    <path class="sv-schema__link" d="M300,50 C240,50 240,120 200,120" />
                    <path class="sv-schema__link" d="M200,120 C200,150 200,150 200,180" />
                </svg>
                <div class="sv-schema__node" style="top:16px; left:6%;">Users</div>
                <div class="sv-schema__node" style="top:16px; right:6%;">Orders</div>
                <div class="sv-schema__node sv-schema__node--main" style="top:96px; left:50%;">MySQL</div>
                <div class="sv-schema__node" style="top:176px; left:50%;">API</div>
            </div>';

        case 'interactive-3d-experiences':
            return '<div class="service-visual sv-3d" data-visual="3d">
                <div id="serviceThreeCanvas" class="sv-3d__canvas"></div>
            </div>';

        case 'ui-ux-design':
            return '<div class="service-visual sv-design" data-visual="design">
                <div class="sv-design__panel">
                    <div class="sv-design__layers">
                        <span class="sv-design__layer is-active">' . e(t('sv_design_layer_hero')) . '</span>
                        <span class="sv-design__layer">' . e(t('sv_design_layer_nav')) . '</span>
                        <span class="sv-design__layer">' . e(t('sv_design_layer_footer')) . '</span>
                    </div>
                    <div class="sv-design__canvas">
                        <span class="sv-design__block sv-design__block--1"></span>
                        <span class="sv-design__block sv-design__block--2"></span>
                        <span class="sv-design__block sv-design__block--3"></span>
                        <span class="sv-design__cursor"></span>
                    </div>
                    <div class="sv-design__swatches">
                        <span style="background:#3b82f6;"></span>
                        <span style="background:#60a5fa;"></span>
                        <span style="background:#f4f6fb;"></span>
                        <span style="background:#0a101d;"></span>
                    </div>
                </div>
            </div>';

        case 'business-applications':
            return '<div class="service-visual sv-dashboard" data-visual="dashboard">
                <div class="sv-dash__kpis">
                    <div class="sv-dash__kpi"><span class="sv-dash__kpi-value" data-count="128">0</span><span class="sv-dash__kpi-label">' . e(t('sv_dash_kpi_users')) . '</span></div>
                    <div class="sv-dash__kpi"><span class="sv-dash__kpi-value" data-count="99">0</span><span class="sv-dash__kpi-label">' . e(t('sv_dash_kpi_uptime')) . '</span></div>
                    <div class="sv-dash__kpi"><span class="sv-dash__kpi-value" data-count="47">0</span><span class="sv-dash__kpi-label">' . e(t('sv_dash_kpi_reports')) . '</span></div>
                </div>
                <div class="sv-dash__chart">
                    <span class="sv-dash__bar" style="--h:40%"></span>
                    <span class="sv-dash__bar" style="--h:65%"></span>
                    <span class="sv-dash__bar" style="--h:50%"></span>
                    <span class="sv-dash__bar" style="--h:80%"></span>
                    <span class="sv-dash__bar" style="--h:60%"></span>
                    <span class="sv-dash__bar" style="--h:95%"></span>
                </div>
            </div>';

        case 'maintenance-support':
            return '<div class="service-visual sv-support" data-visual="support">
                <div class="sv-support__status"><span class="sv-support__pulse"></span> ' . e(t('sv_support_status')) . '</div>
                <svg viewBox="0 0 400 100" class="sv-support__wave" preserveAspectRatio="none">
                    <polyline class="sv-support__line" points="0,50 130,50 150,15 170,85 190,50 400,50" />
                </svg>
                <div class="sv-support__stats">
                    <div><strong>99.98%</strong><span>' . e(t('sv_support_uptime')) . '</span></div>
                    <div><strong>&lt;15min</strong><span>' . e(t('sv_support_response')) . '</span></div>
                    <div><strong>24/7</strong><span>' . e(t('sv_support_monitoring')) . '</span></div>
                </div>
            </div>';

        default:
            return '';
    }
}
