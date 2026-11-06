-- ============================================================================
-- DocuFlow Cloud — Migration 002: share passwords + share access logs
-- Non-destructive. Adds optional password protection to public share links
-- (bcrypt hash only — never store the plain password) and an audit trail of
-- every public share view / download / failed unlock attempt.
-- Apply with:  mysql docuflow_cloud < database/migrations/002_share_passwords_and_access_logs.sql
-- Fresh installs already include this (database/database.sql).
-- ============================================================================
USE docuflow_cloud;

ALTER TABLE document_shares
  ADD COLUMN password_hash VARCHAR(255) NULL AFTER can_download,
  ADD COLUMN last_accessed_at TIMESTAMP NULL AFTER views;

CREATE TABLE IF NOT EXISTS share_access_logs (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id  INT UNSIGNED NOT NULL,
  share_id   INT UNSIGNED NOT NULL,
  action     ENUM('view','download','unlock_failed') NOT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sal_share (share_id),
  KEY idx_sal_tenant (tenant_id),
  CONSTRAINT fk_sal_share FOREIGN KEY (share_id) REFERENCES document_shares (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
