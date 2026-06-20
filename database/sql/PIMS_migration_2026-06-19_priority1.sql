-- ============================================================
-- PIMS Incremental Migration — 2026-06-19: Priority 1 features
-- ============================================================
-- What this changes (commit 0ceb0b3 — "Implement Priority 1 security
-- and workflow features"):
--
--   1.  med_tech_profiles        — ADD is_active column
--   2.  lab_test_categories      — ADD is_active column
--   3.  lab_appointments         — DROP void columns (is_voided, void_at,
--                                  void_reason, void_approved_by); the
--                                  status column handles the full lifecycle
--   4.  lab_result_requests      — same void columns dropped
--   5.  doctor_duty_session_items — entire table dropped (unused)
--   6.  consultation_notes        — entire table dropped (unused)
--   7.  void_requests             — new table created
--
-- NOTE: This migration also corrects ON DELETE rules and adds named FK
-- constraints. Because anonymous FK names differ per install, those FK
-- changes are NOT included here — they are cosmetic/correctness fixes that
-- do not affect application behaviour. If you need them, reimport from
-- PIMS_DB_corrected.sql into a fresh database.
--
-- How to run:
--   mysql -u <user> -p <database_name> < PIMS_migration_2026-06-19_priority1.sql
--
-- Apply BEFORE PIMS_migration_2026-06-20_schema-fixes.sql.
-- ============================================================

-- 1. Add is_active to med_tech_profiles (defaults TRUE for existing rows)
ALTER TABLE med_tech_profiles
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1
    AFTER license_number;

-- 2. Add is_active to lab_test_categories (defaults TRUE for existing rows)
ALTER TABLE lab_test_categories
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1
    AFTER description;

-- 3. Remove void columns from lab_appointments
--    (status column handles Cancelled lifecycle; void columns were redundant)
ALTER TABLE lab_appointments
    DROP COLUMN IF EXISTS is_voided,
    DROP COLUMN IF EXISTS void_at,
    DROP COLUMN IF EXISTS void_reason,
    DROP COLUMN IF EXISTS void_approved_by;

-- 4. Remove void columns from lab_result_requests
ALTER TABLE lab_result_requests
    DROP COLUMN IF EXISTS is_voided,
    DROP COLUMN IF EXISTS void_at,
    DROP COLUMN IF EXISTS void_reason,
    DROP COLUMN IF EXISTS void_approved_by;

-- 5. Drop doctor_duty_session_items (table was unused)
DROP TABLE IF EXISTS doctor_duty_session_items;

-- 6. Drop consultation_notes (table was unused)
DROP TABLE IF EXISTS consultation_notes;

-- 7. Create void_requests table
CREATE TABLE IF NOT EXISTS void_requests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    table_name   VARCHAR(100) NOT NULL,
    record_id    INT          NOT NULL,
    requested_by INT          NOT NULL,
    reason       TEXT         NOT NULL,
    status       ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    reviewed_by  INT          NULL,
    reviewed_at  DATETIME     NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_voidreq_requested_by FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE RESTRICT,
    CONSTRAINT fk_voidreq_reviewed_by  FOREIGN KEY (reviewed_by)  REFERENCES users(user_id) ON DELETE SET NULL
);
