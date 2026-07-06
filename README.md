# Orbtasoft — Software Development Company Website

A cinematic, bilingual (Arabic/English) marketing site for a software
development studio. Built with plain HTML/PHP, vanilla CSS, vanilla
JavaScript, GSAP + ScrollTrigger, and Three.js — no frontend framework,
no build step.

## Stack

- **PHP** (server-rendered pages, no framework)
- **MySQL** (contact form submissions)
- **Vanilla CSS** (custom design system, RTL-aware)
- **Vanilla JavaScript** (nav, scroll reveals, form handling)
- **GSAP + ScrollTrigger** (motion/animation)
- **Three.js** (hero background: particle network + wireframe icosahedron)

GSAP, ScrollTrigger and Three.js are vendored locally under
`assets/vendor/` (installed via npm and copied in — no CDN dependency at
runtime, no Node/build step required to run the site).

## Requirements

- PHP 8.0+ with the `pdo_mysql` extension
- MySQL or MariaDB 10+

## Setup

1. Create the database and table:

   ```bash
   mysql -u root -p < sql/schema.sql
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
   asset and page links are root-relative (`/assets/...`, `/pages/...`).

4. Visit `http://localhost:8000/index.php`.

## Project Structure

```
index.php               Homepage (hero, services, stack, work, CTA)
pages/                  services.php, portfolio.php, about.php, contact.php
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

## Contact form

Submits via `fetch()` to `contact-handler.php`, which validates input,
rejects bot submissions via a honeypot field, and inserts valid messages
into the `contact_messages` table.
