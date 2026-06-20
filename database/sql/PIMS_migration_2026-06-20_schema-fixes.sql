-- ============================================================
-- PIMS Incremental Migration — 2026-06-20: Schema fixes
-- ============================================================
-- What this changes:
--   1. users.mobile_number          NULL  → NOT NULL
--   2. patient_profiles.sex         nullable ENUM → NOT NULL ENUM
--   3. void_requests.status ENUM    add 'Restored' value
--   4. audit_logs.action            ENUM → VARCHAR(20)
--      (allows RESTORE, REJECT, and future action names)
--
-- How to run:
--   mysql -u <user> -p <database_name> < PIMS_migration_2026-06-20_schema-fixes.sql
--
-- Prerequisites / caveats:
--   • Statements 1 and 2 will FAIL if any existing rows have NULL in
--     those columns. Clean them up first:
--       UPDATE users          SET mobile_number = '' WHERE mobile_number IS NULL;
--       UPDATE patient_profiles SET sex = 'Male'  WHERE sex IS NULL;
--     (replace defaults with whatever makes sense for your data)
--   • Statement 3 is safe — adding an ENUM value is non-destructive.
--   • Statement 4 is safe — widening to VARCHAR does not affect existing data.
-- ============================================================

-- 1. Require mobile_number on every user account
ALTER TABLE users
    MODIFY COLUMN mobile_number VARCHAR(20) NOT NULL;

-- 2. Require sex on every patient profile
ALTER TABLE patient_profiles
    MODIFY COLUMN sex ENUM('Male','Female') NOT NULL;

-- 3. Add 'Restored' as a valid void-request status
ALTER TABLE void_requests
    MODIFY COLUMN status ENUM('Pending','Approved','Rejected','Restored') NOT NULL DEFAULT 'Pending';

-- 4. Widen audit_logs.action from a fixed ENUM to VARCHAR(20)
--    so new action names (RESTORE, REJECT, etc.) are accepted
ALTER TABLE audit_logs
    MODIFY COLUMN action VARCHAR(20) NOT NULL;
