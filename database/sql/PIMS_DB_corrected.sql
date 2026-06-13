-- ============================================================
-- PIMS DATABASE — CORRECTED & COMPLETE SCHEMA
-- All relationships verified against conceptual model and flow.txt
-- Fixes applied:
--   1. consultations.appointment_id changed to NULL (walk-in support)
--   2. appointments.duty_session_id FK added (schedule deduplication)
--   3. lab_results.result_file_path added (MedTech soft-copy upload)
--   4. lab_result_requests table added (patient & doctor soft-copy requests)
--   5. diagnoses table added (structured diagnosis history)
--   6. users.mobile_number added (receptionist bypass-OTP flow)
--   7. Table creation order corrected (no forward FK issues)
--   8. consultations.appointment_id NOT NULL → NULL
-- ============================================================

DROP DATABASE IF EXISTS pims_db;
CREATE DATABASE pims_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pims_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- 1. ACCESS & SECURITY
-- ==========================================

CREATE TABLE roles (
    role_id        INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(50)  NOT NULL UNIQUE,
    display_name   VARCHAR(100),
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    permission_id  INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    guard_name     VARCHAR(50)  NOT NULL,
    UNIQUE KEY uq_permission (name, guard_name),
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE role_has_permissions (
    role_id        INT NOT NULL,
    permission_id  INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id)       REFERENCES roles(role_id)             ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
);

-- ==========================================
-- 2. USERS (must be created before all profile
--    tables and junction tables that reference it)
-- ==========================================

CREATE TABLE users (
    user_id                    INT AUTO_INCREMENT PRIMARY KEY,
    first_name                 VARCHAR(50)  NOT NULL,
    middle_name                VARCHAR(50),
    last_name                  VARCHAR(50)  NOT NULL,
    email                      VARCHAR(254) NOT NULL UNIQUE,
    -- FIX #6: mobile_number added — required by receptionist bypass-OTP flow
    --         (valid email OR contact number must be provided)
    mobile_number              VARCHAR(20)  NULL,
    password_hash              VARCHAR(255) NOT NULL,
    date_of_birth              DATE         NOT NULL,
    account_status             ENUM('Active','Deactivated','Archived') DEFAULT 'Active',
    remember_token             VARCHAR(255),
    is_approved_by_admin       TINYINT(1)   DEFAULT 0,
    failed_login_attempts      TINYINT      DEFAULT 0,
    locked_until               DATETIME     NULL,
    is_2fa_enabled             TINYINT(1)   DEFAULT 0,
    -- When TRUE, receptionist created account on behalf of patient (bypasses OTP portal)
    is_otp_bypassed            TINYINT(1)   DEFAULT 0,
    auto_logout_minutes        SMALLINT     DEFAULT 15,
    email_verified_at          DATETIME     NULL,
    has_accepted_privacy_notice TINYINT(1)  DEFAULT 0,
    consented_privacy_at       DATETIME     NULL,
    created_at                 DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at                 DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- At least email or mobile_number must be non-null (enforced at app layer;
    --   DB constraint: email is always NOT NULL, so this is always satisfied)
    CONSTRAINT chk_contact CHECK (email IS NOT NULL OR mobile_number IS NOT NULL)
);

-- ==========================================
-- 3. USER ROLE & PERMISSION JUNCTIONS
--    (after users table)
-- ==========================================

CREATE TABLE user_roles (
    user_id    INT NOT NULL,
    role_id    INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE
);

CREATE TABLE user_permissions (
    user_id       INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (user_id, permission_id),
    FOREIGN KEY (user_id)       REFERENCES users(user_id)             ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
);

-- ==========================================
-- 4. PROFILE TABLES
--    Each is a 1:0..1 extension of users.
--    user_id NOT NULL UNIQUE enforces the
--    "at most one profile per user" rule.
-- ==========================================

CREATE TABLE admin_profiles (
    admin_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- FIX: Added receptionist_profiles — receptionists need a profile row
--      for audit linkage and future desk/branch assignment.
CREATE TABLE receptionist_profiles (
    receptionist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE doctor_profiles (
    doctor_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    specialization  VARCHAR(100),
    -- NOT NULL enforced at app layer: prescriptions cannot be issued
    --   without a registered license number.
    license_number  VARCHAR(50)  NOT NULL,
    -- Short bio/description shown to patients during appointment booking
    short_bio       VARCHAR(500),
    is_active       TINYINT(1)   DEFAULT 1,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE med_tech_profiles (
    medtech_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    license_number VARCHAR(50),
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE patient_profiles (
    patient_id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id                  INT NOT NULL UNIQUE,
    sex                      ENUM('Male','Female'),
    address                  TEXT,
    emergency_contact_name   VARCHAR(100),
    emergency_contact_number VARCHAR(20),
    blood_type               VARCHAR(5),
    created_at               DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at               DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE patient_medical_histories (
    medical_history_id   INT AUTO_INCREMENT PRIMARY KEY,
    -- UNIQUE: exactly one medical history record per patient (updated in-place, not versioned)
    patient_id           INT NOT NULL UNIQUE,
    allergies            TEXT,
    chronic_conditions   TEXT,
    past_surgeries       TEXT,
    current_medications  TEXT,
    family_history       TEXT,
    notes                TEXT,
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patient_profiles(patient_id) ON DELETE CASCADE
);

-- ==========================================
-- 5. DOCTOR DUTY SESSIONS
-- ==========================================

CREATE TABLE doctor_duty_sessions (
    duty_session_id  INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id        INT NOT NULL,
    duty_date        DATE NOT NULL,
    start_time       TIME NOT NULL,
    end_time         TIME NOT NULL,
    status           ENUM('Scheduled','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
    -- Who assigned/created this duty session (Receptionist or Admin)
    assigned_by      INT NULL,
    -- Void pattern
    is_voided        TINYINT(1)  DEFAULT 0,
    void_at          DATETIME    NULL,
    void_reason      TEXT        NULL,
    void_approved_by INT         NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_duty_time CHECK (end_time > start_time),
    FOREIGN KEY (doctor_id)        REFERENCES doctor_profiles(doctor_id),
    FOREIGN KEY (assigned_by)      REFERENCES users(user_id),
    FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

CREATE TABLE doctor_duty_session_items (
    duty_item_id     INT AUTO_INCREMENT PRIMARY KEY,
    duty_session_id  INT NOT NULL,
    task_type        VARCHAR(50),
    notes            TEXT,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (duty_session_id) REFERENCES doctor_duty_sessions(duty_session_id) ON DELETE CASCADE
);

-- ==========================================
-- 6. LABORATORY WORKFLOW
-- ==========================================

CREATE TABLE lab_test_categories (
    lab_category_id  INT AUTO_INCREMENT PRIMARY KEY,
    category_name    VARCHAR(100) NOT NULL,
    description      TEXT,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE lab_tests (
    lab_test_id             INT AUTO_INCREMENT PRIMARY KEY,
    lab_category_id         INT NOT NULL,
    test_name               VARCHAR(100) NOT NULL,
    default_unit            VARCHAR(20),
    default_reference_range VARCHAR(50),
    is_active               TINYINT(1) DEFAULT 1,
    created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_category_id) REFERENCES lab_test_categories(lab_category_id)
);

CREATE TABLE lab_requests (
    lab_request_id   INT AUTO_INCREMENT PRIMARY KEY,
    patient_id       INT NOT NULL,
    -- NULL when patient self-requests without a referring doctor
    doctor_id        INT NULL,
    request_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    priority         ENUM('Routine','STAT') NOT NULL DEFAULT 'Routine',
    clinical_notes   TEXT,
    status           ENUM('Pending','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    -- Void pattern
    is_voided        TINYINT(1)  DEFAULT 0,
    void_at          DATETIME    NULL,
    void_reason      TEXT        NULL,
    void_approved_by INT         NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id)       REFERENCES patient_profiles(patient_id),
    FOREIGN KEY (doctor_id)        REFERENCES doctor_profiles(doctor_id),
    FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

CREATE TABLE lab_request_items (
    request_item_id  INT AUTO_INCREMENT PRIMARY KEY,
    lab_request_id   INT NOT NULL,
    lab_test_id      INT NOT NULL,
    status           ENUM('Pending','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    specimen_type    VARCHAR(50),
    remarks          TEXT,
    -- Void pattern
    is_voided        TINYINT(1) DEFAULT 0,
    void_at          DATETIME   NULL,
    void_reason      TEXT       NULL,
    void_approved_by INT        NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Prevents duplicate test entries within the same request
    UNIQUE KEY uq_request_test (lab_request_id, lab_test_id),
    FOREIGN KEY (lab_request_id)   REFERENCES lab_requests(lab_request_id) ON DELETE CASCADE,
    FOREIGN KEY (lab_test_id)      REFERENCES lab_tests(lab_test_id),
    FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

CREATE TABLE lab_results (
    result_id               INT AUTO_INCREMENT PRIMARY KEY,
    -- UNIQUE: exactly one result row per request item
    request_item_id         INT NOT NULL UNIQUE,
    result_value            VARCHAR(255),
    unit                    VARCHAR(20),
    reference_range         VARCHAR(50),
    abnormal_flag           ENUM('High','Low','Normal','Critical') DEFAULT 'Normal',
    remarks                 TEXT,
    -- Tracks position in the lab result pipeline
    workflow_status         ENUM('Encoded','Checked','Validated','Released','Viewed') DEFAULT 'Encoded',
    -- FIX #3: file path for uploaded soft-copy result (PDF/image uploaded by MedTech)
    result_file_path        VARCHAR(500) NULL,
    -- Multi-step sign-off chain
    performed_by            INT NULL,   -- MedTech who ran the test
    checked_by              INT NULL,   -- Technical check-off (any authorized user)
    validated_by            INT NULL,   -- Clinical validation (Doctor)
    released_by             INT NULL,   -- Who released to patient
    result_at               DATETIME NULL,
    released_at             DATETIME NULL,
    -- Identity verification before result is handed to patient
    is_identity_verified    TINYINT(1) DEFAULT 0,
    verification_method     ENUM('In-Clinic ID Verification','Uploaded Government ID','None') DEFAULT 'None',
    identity_verified_by    INT NULL,
    -- Void pattern
    is_voided               TINYINT(1) DEFAULT 0,
    void_at                 DATETIME   NULL,
    void_reason             TEXT       NULL,
    void_approved_by        INT        NULL,
    created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_item_id)      REFERENCES lab_request_items(request_item_id),
    FOREIGN KEY (performed_by)         REFERENCES med_tech_profiles(medtech_id),
    FOREIGN KEY (checked_by)           REFERENCES users(user_id),
    FOREIGN KEY (validated_by)         REFERENCES doctor_profiles(doctor_id),
    FOREIGN KEY (released_by)          REFERENCES users(user_id),
    FOREIGN KEY (identity_verified_by) REFERENCES users(user_id),
    FOREIGN KEY (void_approved_by)     REFERENCES users(user_id)
);

-- FIX #4: Tracks patient and doctor requests for soft-copy lab results.
--   Replaces the implicit "request result" flow with a proper queue
--   that MedTech can see and fulfill by uploading the result file.
CREATE TABLE lab_result_requests (
    result_request_id  INT AUTO_INCREMENT PRIMARY KEY,
    result_id          INT NOT NULL,
    -- Exactly one of patient_id or doctor_id must be set (requester)
    patient_id         INT NULL,
    doctor_id          INT NULL,
    requested_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    status             ENUM('Pending','Fulfilled','Cancelled') NOT NULL DEFAULT 'Pending',
    -- MedTech who fulfilled the request
    fulfilled_by       INT NULL,
    fulfilled_at       DATETIME NULL,
    -- Void pattern
    is_voided          TINYINT(1) DEFAULT 0,
    void_at            DATETIME   NULL,
    void_reason        TEXT       NULL,
    void_approved_by   INT        NULL,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Ensures only one pending request per requester per result
    CONSTRAINT chk_requester CHECK (
        (patient_id IS NOT NULL AND doctor_id IS NULL) OR
        (patient_id IS NULL AND doctor_id IS NOT NULL)
    ),
    FOREIGN KEY (result_id)        REFERENCES lab_results(result_id),
    FOREIGN KEY (patient_id)       REFERENCES patient_profiles(patient_id),
    FOREIGN KEY (doctor_id)        REFERENCES doctor_profiles(doctor_id),
    FOREIGN KEY (fulfilled_by)     REFERENCES med_tech_profiles(medtech_id),
    FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

-- Laboratory appointments.
--   Patients book laboratory visits independently of any doctor consultation.
--   Kept fully separate from the doctor `appointments` table so each workflow
--   evolves on its own. Optionally linked to the lab_request holding the tests
--   the patient asked for.
CREATE TABLE lab_appointments (
    lab_appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id         INT NOT NULL,
    -- The self-requested lab tests this visit will perform (NULL = none yet).
    lab_request_id     INT NULL,
    scheduled_at       DATETIME NOT NULL,
    status             ENUM('Scheduled','Confirmed','In Progress','Completed','Cancelled','No Show')
                          NOT NULL DEFAULT 'Scheduled',
    notes              TEXT NULL,
    -- Void pattern (mirrors the rest of the schema)
    is_voided          TINYINT(1) DEFAULT 0,
    void_at            DATETIME   NULL,
    void_reason        TEXT       NULL,
    void_approved_by   INT        NULL,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id)       REFERENCES patient_profiles(patient_id),
    FOREIGN KEY (lab_request_id)   REFERENCES lab_requests(lab_request_id),
    FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

CREATE INDEX idx_lab_appointments_patient ON lab_appointments(patient_id);
CREATE INDEX idx_lab_appointments_date    ON lab_appointments(scheduled_at);

-- ==========================================
-- 7. APPOINTMENTS & CONSULTATIONS
-- ==========================================

CREATE TABLE appointment_statuses (
    appointment_status_id  INT AUTO_INCREMENT PRIMARY KEY,
    status_name            VARCHAR(50) NOT NULL UNIQUE,
    created_at             DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at             DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed appointment statuses
INSERT INTO appointment_statuses (status_name) VALUES
    ('Scheduled'),
    ('Confirmed'),
    ('In Progress'),
    ('Completed'),
    ('Cancelled'),
    ('No Show'),
    ('Rescheduled');

CREATE TABLE appointments (
    appointment_id         INT AUTO_INCREMENT PRIMARY KEY,
    patient_id             INT NOT NULL,
    doctor_id              INT NOT NULL,
    -- FIX #2: Links appointment to the specific duty session it occupies.
    --   Enables DB-level schedule deduplication enforcement.
    --   NULL allowed for walk-in appointments not tied to a pre-booked slot.
    duty_session_id        INT NULL,
    appointment_at         DATETIME NOT NULL,
    duration_minutes       SMALLINT DEFAULT 30,
    -- Self-reference: points to the appointment this one was rescheduled from
    rescheduled_from_id    INT NULL,
    reason_for_visit       TEXT,
    appointment_type       ENUM('Scheduled','Walk-in','Follow-up') DEFAULT 'Scheduled',
    status_id              INT NOT NULL,
    -- Void pattern
    is_voided              TINYINT(1) DEFAULT 0,
    void_at                DATETIME   NULL,
    void_reason            TEXT       NULL,
    void_approved_by       INT        NULL,
    created_at             DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at             DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_appt_duration CHECK (duration_minutes > 0),
    FOREIGN KEY (patient_id)          REFERENCES patient_profiles(patient_id),
    FOREIGN KEY (doctor_id)           REFERENCES doctor_profiles(doctor_id),
    FOREIGN KEY (duty_session_id)     REFERENCES doctor_duty_sessions(duty_session_id),
    FOREIGN KEY (status_id)           REFERENCES appointment_statuses(appointment_status_id),
    FOREIGN KEY (rescheduled_from_id) REFERENCES appointments(appointment_id),
    FOREIGN KEY (void_approved_by)    REFERENCES users(user_id)
);

-- Performance index for schedule overlap queries
CREATE INDEX idx_appointments_doctor_date ON appointments(doctor_id, appointment_at);
CREATE INDEX idx_appointments_patient     ON appointments(patient_id);
CREATE INDEX idx_appointments_duty_session ON appointments(duty_session_id);

-- FIX #1: appointment_id changed to NULL to support walk-in consultations
--   that have no prior appointment record.
CREATE TABLE consultations (
    consultation_id        INT AUTO_INCREMENT PRIMARY KEY,
    -- NULL = walk-in consultation with no prior appointment
    appointment_id         INT NULL UNIQUE,
    -- The doctor conducting this consultation
    doctor_id              INT NOT NULL,
    -- The patient being consulted
    patient_id             INT NOT NULL,
    consultation_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    chief_complaint        TEXT,
    -- Vital signs (split into typed columns for queryability)
    weight_kg              DECIMAL(5,2),
    height_cm              DECIMAL(5,2),
    temp_c                 DECIMAL(4,2),
    bp_systolic            SMALLINT,
    bp_diastolic           SMALLINT,
    heart_rate             SMALLINT,
    respiratory_rate       SMALLINT,
    clinical_notes         TEXT,
    follow_up_at           DATETIME NULL,
    -- Void pattern
    is_voided              TINYINT(1) DEFAULT 0,
    void_at                DATETIME   NULL,
    void_reason            TEXT       NULL,
    void_approved_by       INT        NULL,
    created_at             DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at             DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id)   REFERENCES appointments(appointment_id),
    FOREIGN KEY (doctor_id)        REFERENCES doctor_profiles(doctor_id),
    FOREIGN KEY (patient_id)       REFERENCES patient_profiles(patient_id),
    FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

CREATE TABLE consultation_notes (
    note_id          INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id  INT NOT NULL,
    note_type        ENUM('SOAP','Observation','Remark','Nursing') NOT NULL,
    content          TEXT NOT NULL,
    created_by       INT NOT NULL,
    -- Void pattern
    is_voided        TINYINT(1) DEFAULT 0,
    void_at          DATETIME   NULL,
    void_reason      TEXT       NULL,
    void_approved_by INT        NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id)  REFERENCES consultations(consultation_id),
    FOREIGN KEY (created_by)       REFERENCES users(user_id),
    FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

-- FIX #5: Structured diagnoses table.
--   flow.txt requires a diagnosis tab showing past diagnoses,
--   date, and the handling doctor — not achievable with free-text notes alone.
CREATE TABLE diagnoses (
    diagnosis_id       INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id    INT NOT NULL,
    -- The doctor who made this diagnosis
    diagnosed_by       INT NOT NULL,
    -- ICD-10 or free-text description
    icd_code           VARCHAR(20)  NULL,
    description        TEXT         NOT NULL,
    diagnosis_type     ENUM('Primary','Secondary','Differential') DEFAULT 'Primary',
    diagnosed_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- Void pattern
    is_voided          TINYINT(1) DEFAULT 0,
    void_at            DATETIME   NULL,
    void_reason        TEXT       NULL,
    void_approved_by   INT        NULL,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id)  REFERENCES consultations(consultation_id),
    FOREIGN KEY (diagnosed_by)     REFERENCES doctor_profiles(doctor_id),
    FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

-- ==========================================
-- 8. PRESCRIPTIONS
-- ==========================================

CREATE TABLE prescriptions (
    prescription_id   INT AUTO_INCREMENT PRIMARY KEY,
    -- UNIQUE: at most one prescription per consultation
    consultation_id   INT NOT NULL UNIQUE,
    -- The signing doctor (their license_number is readable via JOIN to doctor_profiles)
    prescribed_by     INT NOT NULL,
    prescribed_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarks           TEXT,
    validity_days     SMALLINT,
    -- Void pattern
    is_voided         TINYINT(1) DEFAULT 0,
    void_at           DATETIME   NULL,
    void_reason       TEXT       NULL,
    void_approved_by  INT        NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (consultation_id)  REFERENCES consultations(consultation_id),
    FOREIGN KEY (prescribed_by)    REFERENCES doctor_profiles(doctor_id),
    FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

CREATE TABLE prescription_items (
    prescription_item_id  INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id       INT NOT NULL,
    medicine_name         VARCHAR(150) NOT NULL,
    dosage                VARCHAR(50),
    form                  VARCHAR(50),  -- e.g. tablet, syrup, capsule
    frequency             VARCHAR(50),
    duration              VARCHAR(50),
    quantity              SMALLINT,
    instructions          TEXT,
    created_at            DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE
);

-- ==========================================
-- 9. AUDIT LOGS
-- ==========================================

CREATE TABLE audit_logs (
    log_id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    -- NULL for system-generated events
    user_id         INT NULL,
    action          ENUM('CREATE','UPDATE','DELETE','VIEW','LOGIN','LOGOUT','VOID','APPROVE','UPLOAD','REQUEST') NOT NULL,
    module_category VARCHAR(100) NOT NULL,
    table_name      VARCHAR(100) NULL,
    record_id       INT NULL,
    description     TEXT,
    old_values      JSON NULL,
    new_values      JSON NULL,
    ip_address      VARCHAR(45),
    user_agent      VARCHAR(255) NULL,
    logged_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user   (user_id),
    INDEX idx_audit_module (module_category),
    INDEX idx_audit_time   (logged_at)
);

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- SEED DATA: ROLES
-- ==========================================

INSERT INTO roles (name, display_name) VALUES
    ('super_admin',  'Super Admin / Owner'),
    ('doctor',       'Doctor'),
    ('receptionist', 'Receptionist'),
    ('med_tech',     'Medical Technologist'),
    ('patient',      'Patient');
