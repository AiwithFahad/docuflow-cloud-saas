-- ============================================================================
-- DocuFlow Cloud — Migration 004: departments + document metadata
-- Non-destructive. Adds organizational departments, an optional department
-- owner and expiry date on documents, and free-form custom properties
-- (key/value metadata) per document.
-- Apply with:  mysql docuflow_cloud < database/migrations/004_departments_and_document_metadata.sql
-- Fresh installs already include this (database/database.sql).
-- Run BEFORE 003_approval_workflows.sql (workflows reference departments).
-- ============================================================================
USE docuflow_cloud;

CREATE TABLE IF NOT EXISTS departments (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id  INT UNSIGNED NOT NULL,
  name       VARCHAR(120) NOT NULL,
  color      VARCHAR(9) NOT NULL DEFAULT '#6d28d9',
  head_id    INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_dept_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE documents
  ADD COLUMN department_id INT UNSIGNED NULL AFTER category_id,
  ADD COLUMN expires_at DATE NULL AFTER status,
  ADD CONSTRAINT fk_doc_dept FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS document_properties (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id   INT UNSIGNED NOT NULL,
  document_id INT UNSIGNED NOT NULL,
  prop_key    VARCHAR(100) NOT NULL,
  prop_value  VARCHAR(500) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_prop (document_id, prop_key),
  KEY idx_prop_tenant (tenant_id),
  CONSTRAINT fk_prop_doc FOREIGN KEY (document_id) REFERENCES documents (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
