CREATE DATABASE IF NOT EXISTS orbtasoft CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE orbtasoft;

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(160) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============ Admin dashboard ============

CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Every translatable string on the site — editable from the admin dashboard.
CREATE TABLE IF NOT EXISTS site_strings (
    str_key VARCHAR(100) PRIMARY KEY,
    value_de TEXT NOT NULL,
    value_en TEXT NOT NULL,
    value_ar TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Per-entity SEO overrides: static pages, portfolio projects, services, blog posts.
-- Global SEO defaults (title suffix, analytics IDs, org schema, verification codes) live in `settings`.
CREATE TABLE IF NOT EXISTS seo_meta (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(20) NOT NULL,
    entity_key VARCHAR(160) NOT NULL,
    seo_title_de VARCHAR(255) NOT NULL DEFAULT '',
    seo_title_en VARCHAR(255) NOT NULL DEFAULT '',
    seo_title_ar VARCHAR(255) NOT NULL DEFAULT '',
    seo_description_de VARCHAR(320) NOT NULL DEFAULT '',
    seo_description_en VARCHAR(320) NOT NULL DEFAULT '',
    seo_description_ar VARCHAR(320) NOT NULL DEFAULT '',
    og_image VARCHAR(255) NOT NULL DEFAULT '',
    canonical_url VARCHAR(255) NOT NULL DEFAULT '',
    noindex TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY entity_unique (entity_type, entity_key)
) ENGINE=InnoDB;

-- ============ Admin-managed content ============

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL DEFAULT '',
    icon VARCHAR(60) NOT NULL DEFAULT 'box',
    image VARCHAR(255) NOT NULL DEFAULT '',
    title_de VARCHAR(160) NOT NULL,
    title_en VARCHAR(160) NOT NULL,
    title_ar VARCHAR(160) NOT NULL,
    desc_de TEXT NOT NULL,
    desc_en TEXT NOT NULL,
    desc_ar TEXT NOT NULL,
    content_de TEXT NOT NULL DEFAULT '',
    content_en TEXT NOT NULL DEFAULT '',
    content_ar TEXT NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS portfolio_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    image VARCHAR(255) NOT NULL,
    title VARCHAR(160) NOT NULL,
    client VARCHAR(160) NOT NULL DEFAULT '',
    year VARCHAR(20) NOT NULL DEFAULT '',
    project_url VARCHAR(255) NOT NULL DEFAULT '',
    category VARCHAR(20) NOT NULL DEFAULT 'web',
    technologies VARCHAR(255) NOT NULL DEFAULT '',
    duration VARCHAR(60) NOT NULL DEFAULT '',
    metric_1_label VARCHAR(60) NOT NULL DEFAULT '',
    metric_1_value VARCHAR(60) NOT NULL DEFAULT '',
    metric_2_label VARCHAR(60) NOT NULL DEFAULT '',
    metric_2_value VARCHAR(60) NOT NULL DEFAULT '',
    metric_3_label VARCHAR(60) NOT NULL DEFAULT '',
    metric_3_value VARCHAR(60) NOT NULL DEFAULT '',
    tag_de VARCHAR(160) NOT NULL,
    tag_en VARCHAR(160) NOT NULL,
    tag_ar VARCHAR(160) NOT NULL,
    description_de TEXT NOT NULL DEFAULT '',
    description_en TEXT NOT NULL DEFAULT '',
    description_ar TEXT NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS portfolio_gallery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    portfolio_id INT UNSIGNED NOT NULL,
    image VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_gallery_portfolio FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Upgrading an existing database created before these columns existed:
ALTER TABLE services ADD COLUMN IF NOT EXISTS slug VARCHAR(160) NOT NULL DEFAULT '' AFTER id;
ALTER TABLE services ADD COLUMN IF NOT EXISTS image VARCHAR(255) NOT NULL DEFAULT '' AFTER icon;
ALTER TABLE services ADD COLUMN IF NOT EXISTS content_de TEXT NOT NULL DEFAULT '' AFTER desc_ar;
ALTER TABLE services ADD COLUMN IF NOT EXISTS content_en TEXT NOT NULL DEFAULT '' AFTER content_de;
ALTER TABLE services ADD COLUMN IF NOT EXISTS content_ar TEXT NOT NULL DEFAULT '' AFTER content_en;

ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS slug VARCHAR(160) NOT NULL DEFAULT '' AFTER id;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS client VARCHAR(160) NOT NULL DEFAULT '' AFTER title;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS year VARCHAR(20) NOT NULL DEFAULT '' AFTER client;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS project_url VARCHAR(255) NOT NULL DEFAULT '' AFTER year;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS category VARCHAR(20) NOT NULL DEFAULT 'web' AFTER project_url;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS technologies VARCHAR(255) NOT NULL DEFAULT '' AFTER category;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS duration VARCHAR(60) NOT NULL DEFAULT '' AFTER technologies;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS metric_1_label VARCHAR(60) NOT NULL DEFAULT '' AFTER duration;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS metric_1_value VARCHAR(60) NOT NULL DEFAULT '' AFTER metric_1_label;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS metric_2_label VARCHAR(60) NOT NULL DEFAULT '' AFTER metric_1_value;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS metric_2_value VARCHAR(60) NOT NULL DEFAULT '' AFTER metric_2_label;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS metric_3_label VARCHAR(60) NOT NULL DEFAULT '' AFTER metric_2_value;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS metric_3_value VARCHAR(60) NOT NULL DEFAULT '' AFTER metric_3_label;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS description_de TEXT NOT NULL DEFAULT '' AFTER tag_ar;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS description_en TEXT NOT NULL DEFAULT '' AFTER description_de;
ALTER TABLE portfolio_items ADD COLUMN IF NOT EXISTS description_ar TEXT NOT NULL DEFAULT '' AFTER description_en;

CREATE TABLE IF NOT EXISTS blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    cover_image VARCHAR(255) NOT NULL,
    author VARCHAR(120) NOT NULL DEFAULT 'Orbtasoft Team',
    title_de VARCHAR(200) NOT NULL,
    title_en VARCHAR(200) NOT NULL,
    title_ar VARCHAR(200) NOT NULL,
    excerpt_de VARCHAR(400) NOT NULL,
    excerpt_en VARCHAR(400) NOT NULL,
    excerpt_ar VARCHAR(400) NOT NULL,
    content_de TEXT NOT NULL,
    content_en TEXT NOT NULL,
    content_ar TEXT NOT NULL,
    published_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS team_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    role_de VARCHAR(160) NOT NULL,
    role_en VARCHAR(160) NOT NULL,
    role_ar VARCHAR(160) NOT NULL,
    color VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;
ALTER TABLE team_members ADD COLUMN IF NOT EXISTS image VARCHAR(255) NOT NULL DEFAULT '' AFTER name;

CREATE TABLE IF NOT EXISTS testimonials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    role_de VARCHAR(160) NOT NULL,
    role_en VARCHAR(160) NOT NULL,
    role_ar VARCHAR(160) NOT NULL,
    quote_de TEXT NOT NULL,
    quote_en TEXT NOT NULL,
    quote_ar TEXT NOT NULL,
    color VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;
ALTER TABLE testimonials ADD COLUMN IF NOT EXISTS image VARCHAR(255) NOT NULL DEFAULT '' AFTER name;

CREATE TABLE IF NOT EXISTS partners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    weight INT NOT NULL DEFAULT 700,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;
ALTER TABLE partners ADD COLUMN IF NOT EXISTS logo VARCHAR(255) NOT NULL DEFAULT '' AFTER name;

CREATE TABLE IF NOT EXISTS tech_stack (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ============ Client portal ============

CREATE TABLE IF NOT EXISTS client_projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    status ENUM('planning','in_progress','review','completed','on_hold') NOT NULL DEFAULT 'planning',
    progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_client_projects_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_milestones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    CONSTRAINT fk_milestones_project FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS client_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    sender ENUM('client','admin') NOT NULL,
    admin_id INT UNSIGNED NULL,
    body TEXT NOT NULL,
    attachment_path VARCHAR(255) NOT NULL DEFAULT '',
    attachment_type VARCHAR(20) NOT NULL DEFAULT '',
    attachment_name VARCHAR(255) NOT NULL DEFAULT '',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE client_messages ADD COLUMN IF NOT EXISTS attachment_path VARCHAR(255) NOT NULL DEFAULT '' AFTER body;
ALTER TABLE client_messages ADD COLUMN IF NOT EXISTS attachment_type VARCHAR(20) NOT NULL DEFAULT '' AFTER attachment_path;
ALTER TABLE client_messages ADD COLUMN IF NOT EXISTS attachment_name VARCHAR(255) NOT NULL DEFAULT '' AFTER attachment_type;

CREATE TABLE IF NOT EXISTS client_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(40) NOT NULL DEFAULT 'general',
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL DEFAULT '',
    link VARCHAR(255) NOT NULL DEFAULT '',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('credit','debit') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    note VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS discounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    amount DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    expires_at DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED NULL,
    invoice_number VARCHAR(40) NOT NULL UNIQUE,
    status ENUM('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
    discount_id INT UNSIGNED NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    currency VARCHAR(6) NOT NULL DEFAULT 'USD',
    due_date DATE NULL,
    paid_at DATETIME NULL,
    notes TEXT NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoices_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_invoices_project FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE SET NULL,
    CONSTRAINT fk_invoices_discount FOREIGN KEY (discount_id) REFERENCES discounts(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB;
