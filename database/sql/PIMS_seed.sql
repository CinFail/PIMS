-- ============================================================
-- PIMS DEMO SEED DATA
-- Run this AFTER importing PIMS_DB_corrected.sql.
--
-- All demo accounts share the password:  password123
-- (The hash below is a standard bcrypt hash that Laravel understands.)
--
-- Demo logins:
--   admin@pims.test     -> Super Admin
--   doctor@pims.test    -> Doctor
--   recept@pims.test    -> Receptionist
--   medtech@pims.test   -> MedTech
--   patient@pims.test   -> Patient
-- ============================================================

USE pims_db;

SET @pw = '$2y$10$KrLoDEf7xkLo3Uqpeqmr.eCjTtIQkyWDK8j2udz6Y1cIAF5zXW/1e';

-- ---------- USERS ----------
INSERT INTO users (user_id, first_name, last_name, email, mobile_number, password_hash, date_of_birth, account_status, is_approved_by_admin, has_accepted_privacy_notice, consented_privacy_at) VALUES
(1, 'System',  'Administrator', 'admin@pims.test',   '09170000001', @pw, '1985-01-15', 'Active', 1, 1, NOW()),
(2, 'Juan',    'Dela Cruz',     'doctor@pims.test',  '09170000002', @pw, '1980-05-20', 'Active', 1, 1, NOW()),
(3, 'Ana',     'Reyes',         'recept@pims.test',  '09170000003', @pw, '1992-09-10', 'Active', 1, 1, NOW()),
(4, 'Mark',    'Villanueva',    'medtech@pims.test', '09170000004', @pw, '1990-03-25', 'Active', 1, 1, NOW()),
(5, 'Maria',   'Santos',        'patient@pims.test', '09170000005', @pw, '1995-07-30', 'Active', 0, 1, NOW()),
(6, 'Liza',    'Gomez',         'doctor2@pims.test', '09170000006', @pw, '1983-11-05', 'Active', 1, 1, NOW());

-- ---------- PROFILES ----------
INSERT INTO admin_profiles (user_id) VALUES (1);

INSERT INTO doctor_profiles (doctor_id, user_id, specialization, license_number, contact_number, short_bio, is_active) VALUES
(1, 2, 'General Medicine', 'LIC-DOC-1001', '09170000002', 'General practitioner with 15 years of experience.', 1),
(2, 6, 'Pediatrics',       'LIC-DOC-1002', '09170000006', 'Pediatrician focused on child wellness.', 1);

INSERT INTO receptionist_profiles (user_id) VALUES (3);

INSERT INTO med_tech_profiles (medtech_id, user_id, license_number) VALUES
(1, 4, 'LIC-MT-2001');

INSERT INTO patient_profiles (patient_id, user_id, sex, contact_number, address, emergency_contact_name, emergency_contact_number, blood_type) VALUES
(1, 5, 'Female', '09170000005', '123 Mabini St, Quezon City', 'Pedro Santos', '09170000099', 'O+');

INSERT INTO patient_medical_histories (patient_id, allergies, chronic_conditions, current_medications) VALUES
(1, 'Penicillin', 'None', 'None');

-- ---------- USER ROLES ----------
INSERT INTO user_roles (user_id, role_id) VALUES
(1, 1),  -- admin -> super_admin
(2, 2),  -- Juan  -> doctor
(6, 2),  -- Liza  -> doctor
(3, 3),  -- Ana   -> receptionist
(4, 4),  -- Mark  -> med_tech
(5, 5);  -- Maria -> patient

-- ---------- PERMISSIONS ----------
INSERT INTO permissions (permission_id, name, guard_name) VALUES
(1,  'update-profile',       'web'),
(2,  'book-appointment',     'web'),
(3,  'request-lab-result',   'web'),
(4,  'view-patient-records', 'web'),
(5,  'manage-consultation',  'web'),
(6,  'record-diagnosis',     'web'),
(7,  'issue-prescription',   'web'),
(8,  'process-lab-request',  'web'),
(9,  'release-lab-result',   'web'),
(10, 'upload-lab-result',    'web'),
(11, 'manage-patients',      'web'),
(12, 'view-patient-info',    'web'),
(13, 'manage-roles',         'web'),
(14, 'manage-users',         'web'),
(15, 'view-audit-logs',      'web'),
(16, 'manage-maintenance',   'web');

-- ---------- ROLE -> PERMISSION ASSIGNMENTS ----------
-- super_admin (role 1): everything
INSERT INTO role_has_permissions (role_id, permission_id)
SELECT 1, permission_id FROM permissions;

-- doctor (role 2)
INSERT INTO role_has_permissions (role_id, permission_id) VALUES
(2, 4), (2, 5), (2, 6), (2, 7), (2, 9);

-- receptionist (role 3)
INSERT INTO role_has_permissions (role_id, permission_id) VALUES
(3, 11), (3, 12);

-- med_tech (role 4)
INSERT INTO role_has_permissions (role_id, permission_id) VALUES
(4, 8), (4, 9), (4, 10);

-- patient (role 5)
INSERT INTO role_has_permissions (role_id, permission_id) VALUES
(5, 1), (5, 2), (5, 3);

-- ---------- LAB CATEGORIES & TESTS ----------
INSERT INTO lab_test_categories (lab_category_id, category_name, description) VALUES
(1, 'Hematology', 'Blood-related tests'),
(2, 'Urinalysis', 'Urine examination'),
(3, 'Chemistry',  'Clinical chemistry panels');

INSERT INTO lab_tests (lab_test_id, lab_category_id, test_name, default_unit, default_reference_range, is_active) VALUES
(1, 1, 'Complete Blood Count (CBC)', 'x10^9/L', '4.0 - 11.0', 1),
(2, 2, 'Routine Urinalysis',         '',        'Normal',     1),
(3, 3, 'Fasting Blood Sugar (FBS)',  'mg/dL',   '70 - 100',   1),
(4, 3, 'Lipid Profile',              'mg/dL',   '< 200',      1);

-- ---------- DOCTOR DUTY SESSIONS (future, open slots) ----------
INSERT INTO doctor_duty_sessions (doctor_id, duty_date, start_time, end_time, status, assigned_by) VALUES
(1, CURDATE() + INTERVAL 1 DAY, '09:00:00', '09:30:00', 'Scheduled', 1),
(1, CURDATE() + INTERVAL 1 DAY, '10:00:00', '10:30:00', 'Scheduled', 1),
(1, CURDATE() + INTERVAL 2 DAY, '13:00:00', '13:30:00', 'Scheduled', 1),
(2, CURDATE() + INTERVAL 2 DAY, '09:00:00', '09:30:00', 'Scheduled', 1),
(2, CURDATE() + INTERVAL 3 DAY, '14:00:00', '14:30:00', 'Scheduled', 1);
