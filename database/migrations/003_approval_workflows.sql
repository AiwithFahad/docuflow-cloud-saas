-- ============================================================================
-- DocuFlow Cloud — Migration 003: configurable approval workflows
-- Non-destructive. Adds reusable workflow templates (optionally scoped to a
-- department or category), template step definitions, a workflow link on
-- approval requests, and the 'changes_requested' decision state.
-- Apply with:  mysql docuflow_cloud < database/migrations/003_approval_workflows.sql
-- Fresh installs already include this (database/database.sql).
-- Run AFTER 004_departments_and_document_metadata.sql (workflows reference
-- the departments table).
-- ============================================================================
USE docuflow_cloud;

CREATE TABLE IF NOT EXISTS approval_workflows (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id     INT UNSIGNED NOT NULL,
  name          VARCHAR(150) NOT NULL,
  description   VARCHAR(500) NULL,
  department_id INT UNSIGNED NULL,
  category_id   INT UNSIGNED NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_by    INT UNSIGNED NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_wf_tenant (tenant_id),
  CONSTRAINT fk_wf_dept FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL,
  CONSTRAINT fk_wf_cat FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS approval_workflow_steps (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  workflow_id INT UNSIGNED NOT NULL,
  step_order  INT NOT NULL DEFAULT 1,
  approver_id INT UNSIGNED NULL,
  role_name   VARCHAR(50) NULL,
  label       VARCHAR(150) NULL,
  PRIMARY KEY (id),
  KEY idx_wfs_workflow (workflow_id),
  CONSTRAINT fk_wfs_wf FOREIGN KEY (workflow_id) REFERENCES approval_workflows (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE approvals
  ADD COLUMN workflow_id INT UNSIGNED NULL AFTER document_id,
  ADD CONSTRAINT fk_appr_wf FOREIGN KEY (workflow_id) REFERENCES approval_workflows (id) ON DELETE SET NULL,
  MODIFY COLUMN status ENUM('pending','approved','rejected','changes_requested','cancelled') NOT NULL DEFAULT 'pending';

ALTER TABLE approval_steps
  MODIFY COLUMN status ENUM('pending','approved','rejected','changes_requested','skipped') NOT NULL DEFAULT 'pending';
