# Orbtasoft — Software Development Company Website

A cinematic, bilingual (Arabic/English) marketing site for a software
development studio. Built with plain HTML/PHP, vanilla CSS, vanilla
JavaScript, GSAP + ScrollTrigger, and Three.js — no frontend framework,
no build step.

## Stack

- **PHP** (server-rendered pages, no framework)
- **MySQL** (all site content, contact/newsletter submissions, users, admin settings)
- **Vanilla CSS** (custom design system, RTL-aware)
- **Vanilla JavaScript** (nav, scroll reveals, form handling)
- **GSAP + ScrollTrigger** (motion/animation)
- **Three.js** (hero background: particle network + wireframe icosahedron)

GSAP, ScrollTrigger, Three.js and cobe are vendored locally under
`assets/vendor/` (installed via npm and copied in/bundled — no CDN
dependency at runtime, no Node/build step required to run the site).

## Requirements

- PHP 8.0+ with the `pdo_mysql` extension
- MySQL or MariaDB 10+

## Setup

1. Create the database and table:

   ```bash
   mysql -u root -p < sql/schema.sql
   ```

   Then seed it with the site's default content and the admin account
   (safe to re-run — it only inserts into empty tables):

   ```bash
   php sql/seed.php
   ```

2. Configure the database connection. `config/database.php` reads from
   environment variables (falling back to local defaults):

   | Variable  | Default       |
   |-----------|---------------|
   | `DB_HOST` | `127.0.0.1`   |
   | `DB_NAME` | `orbtasoft`   |
   | `DB_USER` | `root`        |
   | `DB_PASS` | *(empty)*     |

   Either export them before starting PHP, or edit the defaults directly
   in `config/database.php`.

3. Run the site with PHP's built-in server from the project root:

   ```bash
   DB_HOST=127.0.0.1 DB_USER=youruser DB_PASS=yourpass php -S localhost:8000
   ```

   Or point your Apache/Nginx document root at the project root — all
   asset and page links are root-relative (`/assets/...`, `/about.php`, etc.).

4. Visit `http://localhost:8000/index.php`.

## Project Structure

```
index.php               Homepage (hero, services, stack, work, CTA)
services.php, portfolio.php, about.php, contact.php, ...   Public pages (root-level)
includes/               header.php, footer.php, functions.php (i18n + helpers)
lang/                   ar.php, en.php — translation strings
config/database.php     PDO MySQL connection
contact-handler.php     AJAX endpoint for the contact form → MySQL
sql/schema.sql          Database schema
assets/css/style.css    Design system
assets/js/main.js       Nav, scroll reveals, mouse-tilt card, contact form
assets/js/three-bg.js   Three.js hero background scene
assets/vendor/          Locally vendored GSAP + Three.js builds
assets/img/             Local SVG placeholder artwork for the portfolio grid
```

## Language switching

The site defaults to German (`de`). English (`en`) and Arabic (`ar`, RTL)
are also available — switch via the language pills inside the popout
menu, or by appending `?lang=de|en|ar` to any URL; the choice is
remembered in a cookie. All UI strings live in `lang/de.php`,
`lang/en.php` and `lang/ar.php`.

## Navigation

The nav is a full-screen popout menu (triggered by the "Menu" button in
the header) with large staggered links, a language switcher, and a
contact CTA — see `includes/header.php`, the `.popout-menu*` rules in
`assets/css/style.css`, and the open/close logic in `assets/js/main.js`.

## Creative work gallery

The portfolio section (`.work-gallery`) is a GSAP ScrollTrigger–pinned
horizontal-scroll gallery on desktop (vertical scroll drives horizontal
card movement, direction-aware for RTL) and falls back to native
swipe-scrolling on mobile.

## Partners logo cloud

`.logo-cloud` on the homepage is a pure-CSS infinite marquee (no JS
animation library) with a progressive-blur fade at each edge. Partner
names are fictional wordmarks (`partners_data()` in
`includes/functions.php`), not real companies. Note it forces
`direction: ltr` on the marquee container — RTL block layout right-aligns
shrink-to-fit boxes, which combined with the `translateX` keyframes would
otherwise push the whole track off-screen.

## Expertise hover slider

`#hoverSlider` shows a service name list next to a stacked image panel;
hovering/focusing/tapping an item clip-path-reveals its image and rolls
the label text. Character-by-character text splitting only runs for
Latin-script labels — Arabic is left as plain text, since splitting
cursive script into per-character spans breaks Arabic letter-joining.

## Newsletter (orbit signup)

A lightweight email-capture section with spinning orbit-ring decoration
and a canvas confetti burst on success, storing emails in
`newsletter_subscribers` via `newsletter-handler.php`.

## Team marquee

`.team-marquee` on the About page is a pure-CSS infinite marquee of
fictional team members (`team_data()` in `includes/functions.php`),
shown as initials avatars — not real photos, for the same reason the
partner logos are fictional wordmarks. Grayscale-by-default, full color
on hover.

## Circular testimonials

`#circularTestimonials` on the homepage is a 3D-offset testimonial
carousel (active/left/right avatar positions, autoplay, keyboard and
button navigation). Quote text is split and animated **word-by-word**
(not character-by-character) specifically so it stays safe for Arabic —
splitting cursive script into individual character spans breaks letter
joining (see the expertise hover slider, which hit this exact bug).

## 404 page

`404.php` uses [cobe](https://github.com/shuding/cobe) — a
canvas-based globe library with a framework-agnostic vanilla JS API, so
it works here without any React wrapper. It's vendored as a single
bundled script (`assets/vendor/cobe.min.js`, built with esbuild from the
npm package since cobe only ships an ESM build with a bare-specifier
dependency on `phenomenon`). Apache serves this page for real 404s via
the `.htaccess` `ErrorDocument` directive.

## Login / Register

Real authentication, not just UI: `users` table with `password_hash`/
`password_verify`, PHP sessions, and CSRF tokens on both forms.
`auth-login.php`, `auth-register.php` and `auth-logout.php` handle the
POSTs; the header reflects logged-in state (name + logout in the popout
menu) via `current_user()` in `includes/functions.php`.

## Contact form

Submits via `fetch()` to `contact-handler.php`, which validates input,
rejects bot submissions via a honeypot field, and inserts valid messages
into the `contact_messages` table.

## Admin dashboard

A full content-management backend lives under `/admin` (its own
session-based auth, separate from the public login/register system).

- **Login**: `/admin/login.php` — default account seeded by
  `sql/seed.php` is username `minaboules`.
- **Content CRUD**: Services, Portfolio, Team, Testimonials, Partners and
  Tech Stack each have a full create/edit/delete admin page
  (`admin/services.php`, `admin/portfolio.php`, etc.). This content used
  to be hardcoded PHP arrays in `includes/functions.php` — it's now read
  from MySQL, so editing it in the dashboard changes the live site
  immediately. Translatable entities store one column per language
  (`title_de`/`title_en`/`title_ar`, etc.) rather than reusing the static
  UI-string system in `lang/*.php`.
- **Submissions**: `admin/messages.php` and `admin/newsletter.php` list
  (and let you delete) contact form and newsletter submissions, with a
  CSV export for subscribers.
- **Settings** (`admin/settings.php`): SMTP host/port/encryption/
  credentials, a toggle for "notify me on new contact/newsletter
  submissions", the notification recipient address, and editable email
  subject/body templates (with `{{name}}`/`{{email}}`/`{{message}}`
  placeholders) — plus a "send test email" button.
- **Mailer** (`includes/mailer.php`): a from-scratch SMTP client over raw
  PHP sockets (EHLO, STARTTLS, AUTH LOGIN, MAIL FROM/RCPT TO/DATA) — no
  PHPMailer/Composer dependency, consistent with the rest of the vanilla
  stack. `contact-handler.php` and `newsletter-handler.php` call it
  after a successful DB insert; a mail failure is logged but never
  affects the user-facing success response, since the submission is
  already saved either way.
