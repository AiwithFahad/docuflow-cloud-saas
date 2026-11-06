-- =====================================================================
-- DocuFlow Cloud — Document Management SaaS
-- Complete normalized MySQL 8 schema + demo seed data
-- Multi-tenant: every business table carries tenant_id and is isolated.
-- Import:  mysql -u root -p < database.sql
-- =====================================================================
CREATE DATABASE IF NOT EXISTS docuflow_cloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE docuflow_cloud;
SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ---------------------------------------------------------------------
-- Platform / tenancy
-- ---------------------------------------------------------------------
CREATE TABLE tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL,
    logo VARCHAR(255) NULL,
    brand_color VARCHAR(9) NOT NULL DEFAULT '#1e3a5f',
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenants_status (status)
) ENGINE=InnoDB;

CREATE TABLE tenant_settings (
    tenant_id INT UNSIGNED PRIMARY KEY,
    default_approval_required TINYINT(1) NOT NULL DEFAULT 0,
    share_link_days INT NOT NULL DEFAULT 7,
    allow_public_shares TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_ts_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'member',
    status ENUM('pending','active','disabled') NOT NULL DEFAULT 'pending',
    email_verified_at TIMESTAMP NULL,
    email_verification_token VARCHAR(64) NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_tenant (tenant_id),
    INDEX idx_users_role (role),
    CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_email_time (email, attempted_at)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    token CHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pr_email (email)
) ENGINE=InnoDB;

CREATE TABLE invitations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'member',
    token CHAR(48) NOT NULL UNIQUE,
    status ENUM('pending','accepted','expired') NOT NULL DEFAULT 'pending',
    invited_by INT UNSIGNED NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inv_tenant (tenant_id),
    CONSTRAINT fk_invit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_inv_by FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Subscriptions & billing
-- ---------------------------------------------------------------------
CREATE TABLE subscription_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(60) NOT NULL UNIQUE,
    price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_yearly DECIMAL(10,2) NOT NULL DEFAULT 0,
    storage_mb INT NOT NULL DEFAULT 100,       -- -1 = unlimited
    max_users INT NOT NULL DEFAULT 3,          -- -1 = unlimited
    max_file_mb INT NOT NULL DEFAULT 10,       -- -1 = unlimited
    max_teams INT NOT NULL DEFAULT 1,          -- -1 = unlimited
    version_history INT NOT NULL DEFAULT 3,
    features VARCHAR(500) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    billing_cycle ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    status ENUM('trialing','active','past_due','cancelled','expired') NOT NULL DEFAULT 'trialing',
    trial_ends_at TIMESTAMP NULL,
    current_period_start TIMESTAMP NULL,
    current_period_end TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sub_tenant (tenant_id),
    INDEX idx_sub_status (status),
    CONSTRAINT fk_sub_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
) ENGINE=InnoDB;

CREATE TABLE subscription_usage (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    metric VARCHAR(60) NOT NULL,
    used BIGINT NOT NULL DEFAULT 0,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usage_tenant (tenant_id),
    CONSTRAINT fk_usage_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    value DECIMAL(10,2) NOT NULL,
    max_uses INT NULL,
    uses INT NOT NULL DEFAULT 0,
    expires_at TIMESTAMP NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    subscription_id INT UNSIGNED NULL,
    number VARCHAR(30) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('unpaid','paid','void') NOT NULL DEFAULT 'unpaid',
    meta_plan_id INT UNSIGNED NULL,
    meta_cycle VARCHAR(10) NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inv_tenant (tenant_id),
    CONSTRAINT fk_inv_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_inv_sub FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    method VARCHAR(40) NOT NULL DEFAULT 'manual',
    -- A payment may ONLY become 'paid' after verified confirmation.
    status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    reference VARCHAR(80) NOT NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pay_tenant (tenant_id),
    INDEX idx_pay_status (status),
    CONSTRAINT fk_pay_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_pay_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Platform operations
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    body VARCHAR(500) NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user (user_id, is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE support_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    admin_reply TEXT NULL,
    status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    description VARCHAR(500) NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_act_tenant (tenant_id, id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    entity VARCHAR(60) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    action VARCHAR(60) NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_tenant (tenant_id, id)
) ENGINE=InnoDB;

CREATE TABLE announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    body VARCHAR(500) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE platform_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value VARCHAR(500) NULL
) ENGINE=InnoDB;

CREATE TABLE newsletter_subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Teams
-- ---------------------------------------------------------------------
CREATE TABLE teams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_teams_tenant (tenant_id),
    CONSTRAINT fk_teams_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE team_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    team_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'member',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_team_user (team_id, user_id),
    CONSTRAINT fk_tm_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_tm_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_tm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Document management
-- ---------------------------------------------------------------------
CREATE TABLE folders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    description VARCHAR(255) NULL,
    color VARCHAR(9) NOT NULL DEFAULT '#f5b93f',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_folders_tenant_parent (tenant_id, parent_id),
    CONSTRAINT fk_folders_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_folders_parent FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE CASCADE,
    CONSTRAINT fk_folders_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    color VARCHAR(9) NOT NULL DEFAULT '#1e3a5f',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cat_tenant_name (tenant_id, name),
    CONSTRAINT fk_cat_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    color VARCHAR(9) NOT NULL DEFAULT '#6d28d9',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tag_tenant_name (tenant_id, name),
    CONSTRAINT fk_tag_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    color VARCHAR(9) NOT NULL DEFAULT '#6d28d9',
    head_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dept_tenant (tenant_id),
    CONSTRAINT fk_dept_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    folder_id INT UNSIGNED NULL,
    category_id INT UNSIGNED NULL,
    department_id INT UNSIGNED NULL,
    owner_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    stored_name VARCHAR(80) NOT NULL,          -- randomized; file lives in storage/private
    original_name VARCHAR(255) NOT NULL,
    extension VARCHAR(12) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    expires_at DATE NULL,                       -- optional document expiry / review date
    download_count INT UNSIGNED NOT NULL DEFAULT 0,
    deleted_at TIMESTAMP NULL,                  -- soft delete (trash)
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_docs_tenant_folder (tenant_id, folder_id),
    INDEX idx_docs_tenant_cat (tenant_id, category_id),
    INDEX idx_docs_name (name),
    FULLTEXT INDEX ft_docs (name, description),
    CONSTRAINT fk_docs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_docs_folder FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL,
    CONSTRAINT fk_docs_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_docs_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_docs_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    document_id INT UNSIGNED NOT NULL,
    version_number INT NOT NULL,
    stored_name VARCHAR(80) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_by INT UNSIGNED NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_doc_version (document_id, version_number),
    CONSTRAINT fk_ver_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_ver_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_ver_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_tags (
    document_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (document_id, tag_id),
    CONSTRAINT fk_dt_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_dt_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    document_id INT UNSIGNED NOT NULL,
    subject_type ENUM('user','team') NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    can_view TINYINT(1) NOT NULL DEFAULT 1,
    can_download TINYINT(1) NOT NULL DEFAULT 0,
    can_edit TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_perm (document_id, subject_type, subject_id),
    CONSTRAINT fk_perm_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_perm_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_shares (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    document_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    token CHAR(48) NOT NULL UNIQUE,             -- 192-bit random token
    can_download TINYINT(1) NOT NULL DEFAULT 0,
    password_hash VARCHAR(255) NULL,            -- optional bcrypt password gate
    expires_at TIMESTAMP NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    last_accessed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_share_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_share_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_share_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE share_access_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    share_id INT UNSIGNED NOT NULL,
    action ENUM('view','download','unlock_failed') NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sal_share (share_id),
    INDEX idx_sal_tenant (tenant_id),
    CONSTRAINT fk_sal_share FOREIGN KEY (share_id) REFERENCES document_shares(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE approval_workflows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(500) NULL,
    department_id INT UNSIGNED NULL,
    category_id INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_wf_tenant (tenant_id),
    CONSTRAINT fk_wf_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_wf_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_wf_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE approval_workflow_steps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT UNSIGNED NOT NULL,
    step_order INT NOT NULL DEFAULT 1,
    approver_id INT UNSIGNED NULL,
    role_name VARCHAR(50) NULL,
    label VARCHAR(150) NULL,
    INDEX idx_wfs_workflow (workflow_id),
    CONSTRAINT fk_wfs_wf FOREIGN KEY (workflow_id) REFERENCES approval_workflows(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE approvals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    document_id INT UNSIGNED NOT NULL,
    workflow_id INT UNSIGNED NULL,
    requested_by INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    status ENUM('pending','approved','rejected','changes_requested','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_appr_tenant (tenant_id),
    CONSTRAINT fk_appr_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_appr_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_appr_wf FOREIGN KEY (workflow_id) REFERENCES approval_workflows(id) ON DELETE SET NULL,
    CONSTRAINT fk_appr_user FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE approval_steps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    approval_id INT UNSIGNED NOT NULL,
    step_order INT NOT NULL DEFAULT 1,
    approver_id INT UNSIGNED NOT NULL,
    status ENUM('pending','approved','rejected','changes_requested','skipped') NOT NULL DEFAULT 'pending',
    comment VARCHAR(500) NULL,
    decided_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_step_approver (approver_id, status),
    CONSTRAINT fk_step_approval FOREIGN KEY (approval_id) REFERENCES approvals(id) ON DELETE CASCADE,
    CONSTRAINT fk_step_user FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    document_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_comment_doc (document_id),
    CONSTRAINT fk_comment_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    document_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favorite (user_id, document_id),
    INDEX idx_fav_tenant (tenant_id),
    CONSTRAINT fk_fav_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_properties (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    document_id INT UNSIGNED NOT NULL,
    prop_key VARCHAR(100) NOT NULL,
    prop_value VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_prop (document_id, prop_key),
    INDEX idx_prop_tenant (tenant_id),
    CONSTRAINT fk_prop_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- =====================================================================
INSERT INTO roles (id, slug, name) VALUES
(1,'super_admin','Super Admin'),(2,'company_admin','Company Admin'),(3,'manager','Manager'),
(4,'member','Member'),(5,'viewer','Viewer');

INSERT INTO permissions (id, slug, name) VALUES
(1,'documents.upload','Upload documents'),(2,'documents.edit','Edit documents'),
(3,'documents.delete','Delete documents'),(4,'documents.share','Share documents'),
(5,'folders.manage','Manage folders'),(6,'teams.manage','Manage teams'),
(7,'users.manage','Manage users'),(8,'settings.manage','Manage settings'),
(9,'approvals.decide','Decide approvals'),(10,'reports.view','View reports');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE slug IN ('documents.upload','documents.edit','documents.delete','documents.share','folders.manage','teams.manage','approvals.decide','reports.view');
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE slug IN ('documents.upload','documents.edit','documents.share','approvals.decide');
-- company_admin (2) and super_admin (1) bypass permission rows in code; viewers have none.

INSERT INTO subscription_plans (id, name, slug, price_monthly, price_yearly, storage_mb, max_users, max_file_mb, max_teams, version_history, features) VALUES
(1,'Free',         'free',          0.00,    0.00,    100,   3,   10,  1,  3,  'File manager,Sharing links,Community support'),
(2,'Starter',      'starter',      12.00,  120.00,  10240,  10,   25,  5, 10,  'Version history,Approvals,Email support'),
(3,'Professional', 'professional', 29.00,  290.00, 102400,  25,  100, 15, 25,  'Teams & permissions,Audit logs,Reports & CSV export,Priority support'),
(4,'Business',     'business',     79.00,  790.00, 512000, 100,  250, 50, 50,  'Advanced analytics,Custom branding,API access,Phone support'),
(5,'Enterprise',   'enterprise',  199.00, 1990.00,    -1,   -1,   -1, -1, 100, 'Unlimited everything,SSO,SLA 99.9%,Dedicated success manager');

INSERT INTO coupons (code, type, value, max_uses, is_active) VALUES ('WELCOME10', 'percent', 10.00, NULL, 1);

INSERT INTO platform_settings (setting_key, setting_value) VALUES
('site_name','DocuFlow Cloud'),('support_email','support@docuflow.example'),
('trial_days','14'),('maintenance_mode','0');

INSERT INTO announcements (title, body, is_active) VALUES
('New: Approval workflows are live', 'Route any document for approval and keep the full decision trail.', 1);

-- Demo tenant + users. All demo passwords are "password" (bcrypt hash below).
INSERT INTO tenants (id, name, slug, email, brand_color, status) VALUES
(1, 'Demo Company Inc', 'demo-company', 'admin@demo.com', '#1e3a5f', 'active');
INSERT INTO tenant_settings (tenant_id, default_approval_required, share_link_days, allow_public_shares)
VALUES (1, 0, 7, 1);

INSERT INTO users (id, tenant_id, name, email, password_hash, role, status, email_verified_at) VALUES
(1, NULL, 'Platform Owner', 'super@docuflow.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active', NOW()),
(2, 1, 'Ayesha Khan', 'admin@demo.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company_admin', 'active', NOW()),
(3, 1, 'Bilal Ahmed', 'manager@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'active', NOW()),
(4, 1, 'Sara Malik',  'member@demo.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'active', NOW()),
(5, 1, 'Omar Farooq', 'viewer@demo.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer', 'active', NOW());

-- Demo subscription: Professional, active, monthly (with a verified paid invoice).
INSERT INTO subscriptions (id, tenant_id, plan_id, billing_cycle, status, current_period_start, current_period_end)
VALUES (1, 1, 3, 'monthly', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH));
INSERT INTO invoices (id, tenant_id, subscription_id, number, description, amount, discount, total, status, paid_at)
VALUES (1, 1, 1, 'INV-2026-00001', 'DocuFlow Cloud — Professional plan (monthly)', 29.00, 0.00, 29.00, 'paid', NOW());
INSERT INTO payments (tenant_id, invoice_id, amount, method, status, reference, paid_at)
VALUES (1, 1, 29.00, 'card', 'paid', 'MAN-DEMO-0001', NOW());
INSERT INTO subscription_usage (tenant_id, metric, used) VALUES (1, 'documents', 3);

INSERT INTO teams (id, tenant_id, name, description) VALUES
(1, 1, 'Finance', 'Invoices, budgets and supplier contracts'),
(2, 1, 'Operations', 'SOPs and day-to-day operations documents');
INSERT INTO team_members (tenant_id, team_id, user_id, role) VALUES
(1, 1, 3, 'lead'), (1, 1, 4, 'member'), (1, 2, 4, 'member');

INSERT INTO folders (id, tenant_id, parent_id, name, description, color, created_by) VALUES
(1, 1, NULL, 'Finance', 'Finance department documents', '#1e3a5f', 2),
(2, 1, 1,    'Invoices 2026', 'Supplier and client invoices', '#6d28d9', 2),
(3, 1, NULL, 'HR', 'People and policies', '#0e7490', 2),
(4, 1, NULL, 'Legal', 'Contracts and agreements', '#b45309', 2);

INSERT INTO categories (id, tenant_id, name, color) VALUES
(1, 1, 'Contracts', '#b45309'), (2, 1, 'Invoices', '#1e3a5f'),
(3, 1, 'Policies', '#0e7490'), (4, 1, 'Reports', '#6d28d9');

INSERT INTO tags (id, tenant_id, name, color) VALUES
(1, 1, 'urgent', '#dc2626'), (2, 1, 'client', '#1e3a5f'),
(3, 1, '2026', '#6d28d9'), (4, 1, 'signed', '#15803d');

-- Sample documents (metadata + matching placeholder files installed by the
-- installer into storage/private: seed_contract.txt, seed_policy.txt, seed_report.txt)
INSERT INTO documents (id, tenant_id, folder_id, category_id, owner_id, name, description, stored_name, original_name, extension, mime_type, size_bytes, download_count) VALUES
(1, 1, 4, 1, 2, 'Supplier Contract 2026', 'Master services agreement with Helios Legal — signed copy.', 'seed_contract.txt', 'Supplier-Contract-2026.txt', 'txt', 'text/plain', 230, 5),
(2, 1, 3, 3, 2, 'Remote Work Policy', 'Company-wide remote work policy, effective 2026.', 'seed_policy.txt', 'Remote-Work-Policy.txt', 'txt', 'text/plain', 221, 12),
(3, 1, NULL, 4, 3, 'Q3 Sales Report', 'Quarterly sales summary prepared by the manager.', 'seed_report.txt', 'Q3-Sales-Report.txt', 'txt', 'text/plain', 193, 3);

INSERT INTO document_versions (tenant_id, document_id, version_number, stored_name, size_bytes, uploaded_by, note) VALUES
(1, 1, 1, 'seed_contract.txt', 230, 2, 'Initial upload'),
(1, 2, 1, 'seed_policy.txt', 221, 2, 'Initial upload'),
(1, 3, 1, 'seed_report.txt', 193, 3, 'Initial upload');

INSERT INTO document_tags (document_id, tag_id) VALUES (1, 4), (1, 3), (2, 3), (3, 1);
INSERT INTO document_permissions (tenant_id, document_id, subject_type, subject_id, can_view, can_download, can_edit)
VALUES (1, 1, 'team', 1, 1, 1, 0), (1, 3, 'user', 4, 1, 1, 0);
INSERT INTO comments (tenant_id, document_id, user_id, body) VALUES
(1, 1, 3, 'Legal confirmed the signature page is included. Ready for archive.'),
(1, 3, 2, 'Great quarter — please share with the Finance team.');
INSERT INTO notifications (tenant_id, user_id, title, body, link) VALUES
(1, 2, 'Welcome to DocuFlow Cloud', 'Your Professional trial workspace is ready. Invite your team to get started.', 'app/users');
INSERT INTO support_tickets (tenant_id, user_id, name, email, subject, message, status, priority) VALUES
(1, 2, 'Ayesha Khan', 'admin@demo.com', 'How do I raise our storage limit?', 'We are close to the Professional storage quota. What are our options?', 'open', 'normal');
INSERT INTO activity_logs (tenant_id, user_id, action, description) VALUES
(1, 2, 'tenant.registered', 'Demo Company Inc workspace created'),
(1, 2, 'document.uploaded', 'Uploaded Supplier-Contract-2026.txt');

-- Upgrade seeds: departments, document metadata, favourites, a password-
-- protected share link, an approval workflow and a pending approval.
INSERT INTO departments (id, tenant_id, name, color, head_id) VALUES
(1, 1, 'Finance', '#1e3a5f', 3), (2, 1, 'HR', '#0e7490', 2),
(3, 1, 'Legal', '#b45309', 2), (4, 1, 'Operations', '#6d28d9', 3);
UPDATE documents SET department_id = 3 WHERE id = 1;
UPDATE documents SET department_id = 2, expires_at = DATE_ADD(CURDATE(), INTERVAL 90 DAY) WHERE id = 2;
UPDATE documents SET department_id = 1 WHERE id = 3;

INSERT INTO document_favorites (tenant_id, user_id, document_id) VALUES
(1, 4, 1), (1, 3, 3), (1, 2, 1);

INSERT INTO document_properties (tenant_id, document_id, prop_key, prop_value) VALUES
(1, 1, 'Client', 'Helios Legal'), (1, 1, 'Retention', '7 years'),
(1, 2, 'Review cycle', 'Annual'), (1, 3, 'Quarter', 'Q3 2026');

-- Sample public share link for the Remote Work Policy.
-- Password for this demo link: "password" (same bcrypt hash as the demo users).
INSERT INTO document_shares (id, tenant_id, document_id, created_by, token, can_download, password_hash, expires_at, is_active, views) VALUES
(1, 1, 2, 2, 'demotoken000000000000000000000000000000000000001', 1, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', DATE_ADD(NOW(), INTERVAL 30 DAY), 1, 2);
INSERT INTO share_access_logs (tenant_id, share_id, action, ip_address) VALUES
(1, 1, 'view', '127.0.0.1'), (1, 1, 'download', '127.0.0.1');

-- Two-stage review workflow + one pending request using it.
INSERT INTO approval_workflows (id, tenant_id, name, description, department_id, category_id, is_active, created_by) VALUES
(1, 1, 'Standard document review', 'Manager review, then company admin sign-off.', NULL, NULL, 1, 2);
INSERT INTO approval_workflow_steps (workflow_id, step_order, approver_id, label) VALUES
(1, 1, 3, 'Manager review'), (1, 2, 2, 'Admin sign-off');
INSERT INTO approvals (id, tenant_id, document_id, workflow_id, requested_by, title, status) VALUES
(1, 1, 1, 1, 4, 'Please approve the Supplier Contract 2026', 'pending');
INSERT INTO approval_steps (approval_id, step_order, approver_id, status) VALUES
(1, 1, 3, 'pending'), (1, 2, 2, 'pending');

-- End of DocuFlow Cloud schema + seeds.
