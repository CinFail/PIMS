-- ============================================================
-- PIMS INCREMENTAL MIGRATION — 2026-06-13
-- Removes contact_number from patient_profiles and doctor_profiles.
-- Mobile number on the users table (users.mobile_number) is the
-- single contact number for all users going forward.
--
-- Apply with:
--   mysql -u root -p pims_db < database/sql/PIMS_migration_2026_06_13_drop_contact_number.sql
-- ============================================================

USE pims_db;

ALTER TABLE patient_profiles DROP COLUMN IF EXISTS contact_number;
ALTER TABLE doctor_profiles  DROP COLUMN IF EXISTS contact_number;
