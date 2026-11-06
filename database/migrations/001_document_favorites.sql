-- ============================================================================
-- DocuFlow Cloud — Migration 001: document_favorites
-- Non-destructive. Adds per-user favourites ("starred" documents).
-- Apply with:  mysql docuflow_cloud < database/migrations/001_document_favorites.sql
-- Fresh installs already include this table (database/database.sql).
-- ============================================================================
USE docuflow_cloud;

CREATE TABLE IF NOT EXISTS document_favorites (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id   INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  document_id INT UNSIGNED NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_favorite (user_id, document_id),
  KEY idx_fav_tenant (tenant_id),
  CONSTRAINT fk_fav_doc FOREIGN KEY (document_id) REFERENCES documents (id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
