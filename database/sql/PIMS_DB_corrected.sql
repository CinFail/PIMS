-- PIMS DATABASE v2 — Single source of truth for fresh installs.
-- Migration files (drop_contact_number, lab_appointments) are now obsolete.
--
-- Changes from v1 (review 2026-06-19):
--   - med_tech_profiles.is_active added
--   - lab_test_categories.is_active added
--   - doctor_duty_session_items removed (unused)
--   - consultation_notes removed (unused)
--   - lab_appointments void columns removed (redundant with status)
--   - lab_result_requests void columns removed (redundant with status)
--   - All FK constraints named for reliable maintenance
--   - ON DELETE rules corrected on 6 foreign keys
--   - void_requests table added

DROP DATABASE IF EXISTS pims_db;
CREATE DATABASE pims_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pims_db;

SET FOREIGN_KEY_CHECKS = 0;

-- Roles & Permissions

CREATE TABLE roles (
    role_id      INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(50)  NOT NULL UNIQUE,
    display_name VARCHAR(100),
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    guard_name    VARCHAR(50)  NOT NULL,
    UNIQUE KEY uq_permission (name, guard_name),
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE role_has_permissions (
    role_id       INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rhp_role       FOREIGN KEY (role_id)       REFERENCES roles(role_id)             ON DELETE CASCADE,
    CONSTRAINT fk_rhp_permission FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
);

-- Users

CREATE TABLE users (
    user_id                     INT AUTO_INCREMENT PRIMARY KEY,
    first_name                  VARCHAR(50)  NOT NULL,
    middle_name                 VARCHAR(50),
    last_name                   VARCHAR(50)  NOT NULL,
    email                       VARCHAR(254) NOT NULL UNIQUE,
    mobile_number               VARCHAR(20)  NULL,
    password_hash               VARCHAR(255) NOT NULL,
    date_of_birth               DATE         NOT NULL,
    account_status              ENUM('Active','Deactivated','Archived') DEFAULT 'Active',
    remember_token              VARCHAR(255),
    is_approved_by_admin        TINYINT(1)   DEFAULT 0,
    failed_login_attempts       TINYINT      DEFAULT 0,
    locked_until                DATETIME     NULL,
    is_2fa_enabled              TINYINT(1)   DEFAULT 0,
    is_otp_bypassed             TINYINT(1)   DEFAULT 0,
    auto_logout_minutes         SMALLINT     DEFAULT 15,
    email_verified_at           DATETIME     NULL,
    has_accepted_privacy_notice TINYINT(1)   DEFAULT 0,
    consented_privacy_at        DATETIME     NULL,
    created_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at                  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_contact CHECK (email IS NOT NULL OR mobile_number IS NOT NULL)
);

-- User Role & Permission Junctions

CREATE TABLE user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE
);

CREATE TABLE user_permissions (
    user_id       INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (user_id, permission_id),
    CONSTRAINT fk_up_user       FOREIGN KEY (user_id)       REFERENCES users(user_id)             ON DELETE CASCADE,
    CONSTRAINT fk_up_permission FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
);

-- Profile Tables

CREATE TABLE admin_profiles (
    admin_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE receptionist_profiles (
    receptionist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_recept_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE doctor_profiles (
    doctor_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    specialization VARCHAR(100),
    license_number VARCHAR(50) NOT NULL,
    short_bio      VARCHAR(500),
    is_active      TINYINT(1)  DEFAULT 1,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE med_tech_profiles (
    medtech_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    license_number VARCHAR(50),
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_medtech_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
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
    CONSTRAINT fk_patient_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE patient_medical_histories (
    medical_history_id  INT AUTO_INCREMENT PRIMARY KEY,
    patient_id          INT NOT NULL UNIQUE,
    allergies           TEXT,
    chronic_conditions  TEXT,
    past_surgeries      TEXT,
    current_medications TEXT,
    family_history      TEXT,
    notes               TEXT,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_medhistory_patient FOREIGN KEY (patient_id) REFERENCES patient_profiles(patient_id) ON DELETE CASCADE
);

-- Doctor Duty Sessions

CREATE TABLE doctor_duty_sessions (
    duty_session_id  INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id        INT NOT NULL,
    duty_date        DATE NOT NULL,
    start_time       TIME NOT NULL,
    end_time         TIME NOT NULL,
    status           ENUM('Scheduled','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
    assigned_by      INT NULL,
    is_voided        TINYINT(1) DEFAULT 0,
    void_at          DATETIME   NULL,
    void_reason      TEXT       NULL,
    void_approved_by INT        NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_duty_time         CHECK (end_time > start_time),
    CONSTRAINT fk_duty_doctor        FOREIGN KEY (doctor_id)        REFERENCES doctor_profiles(doctor_id),
    CONSTRAINT fk_duty_assigned_by   FOREIGN KEY (assigned_by)      REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_duty_void_approved FOREIGN KEY (void_approved_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Laboratory

CREATE TABLE lab_test_categories (
    lab_category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name   VARCHAR(100) NOT NULL,
    description     TEXT,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    CONSTRAINT fk_labtest_category FOREIGN KEY (lab_category_id) REFERENCES lab_test_categories(lab_category_id)
);

CREATE TABLE lab_requests (
    lab_request_id   INT AUTO_INCREMENT PRIMARY KEY,
    patient_id       INT NOT NULL,
    doctor_id        INT NULL,
    request_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    priority         ENUM('Routine','STAT') NOT NULL DEFAULT 'Routine',
    clinical_notes   TEXT,
    status           ENUM('Pending','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    is_voided        TINYINT(1) DEFAULT 0,
    void_at          DATETIME   NULL,
    void_reason      TEXT       NULL,
    void_approved_by INT        NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_labreq_patient       FOREIGN KEY (patient_id)       REFERENCES patient_profiles(patient_id),
    CONSTRAINT fk_labreq_doctor        FOREIGN KEY (doctor_id)        REFERENCES doctor_profiles(doctor_id),
    CONSTRAINT fk_labreq_void_approved FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

CREATE TABLE lab_request_items (
    request_item_id  INT AUTO_INCREMENT PRIMARY KEY,
    lab_request_id   INT NOT NULL,
    lab_test_id      INT NOT NULL,
    status           ENUM('Pending','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    specimen_type    VARCHAR(50),
    remarks          TEXT,
    is_voided        TINYINT(1) DEFAULT 0,
    void_at          DATETIME   NULL,
    void_reason      TEXT       NULL,
    void_approved_by INT        NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_request_test (lab_request_id, lab_test_id),
    CONSTRAINT fk_labreqitem_request       FOREIGN KEY (lab_request_id)   REFERENCES lab_requests(lab_request_id) ON DELETE CASCADE,
    CONSTRAINT fk_labreqitem_test          FOREIGN KEY (lab_test_id)      REFERENCES lab_tests(lab_test_id),
    CONSTRAINT fk_labreqitem_void_approved FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

CREATE TABLE lab_results (
    result_id            INT AUTO_INCREMENT PRIMARY KEY,
    request_item_id      INT NOT NULL UNIQUE,
    result_value         VARCHAR(255),
    unit                 VARCHAR(20),
    reference_range      VARCHAR(50),
    abnormal_flag        ENUM('High','Low','Normal','Critical') DEFAULT 'Normal',
    remarks              TEXT,
    workflow_status      ENUM('Encoded','Checked','Validated','Released','Viewed') DEFAULT 'Encoded',
    result_file_path     VARCHAR(500) NULL,
    performed_by         INT NULL,
    checked_by           INT NULL,
    validated_by         INT NULL,
    released_by          INT NULL,
    result_at            DATETIME NULL,
    released_at          DATETIME NULL,
    is_identity_verified TINYINT(1) DEFAULT 0,
    verification_method  ENUM('In-Clinic ID Verification','Uploaded Government ID','None') DEFAULT 'None',
    identity_verified_by INT NULL,
    is_voided            TINYINT(1) DEFAULT 0,
    void_at              DATETIME   NULL,
    void_reason          TEXT       NULL,
    void_approved_by     INT        NULL,
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_result_item           FOREIGN KEY (request_item_id)      REFERENCES lab_request_items(request_item_id) ON DELETE CASCADE,
    CONSTRAINT fk_result_performed_by   FOREIGN KEY (performed_by)         REFERENCES med_tech_profiles(medtech_id)      ON DELETE SET NULL,
    CONSTRAINT fk_result_checked_by     FOREIGN KEY (checked_by)           REFERENCES users(user_id),
    CONSTRAINT fk_result_validated_by   FOREIGN KEY (validated_by)         REFERENCES doctor_profiles(doctor_id),
    CONSTRAINT fk_result_released_by    FOREIGN KEY (released_by)          REFERENCES users(user_id),
    CONSTRAINT fk_result_verified_by    FOREIGN KEY (identity_verified_by) REFERENCES users(user_id),
    CONSTRAINT fk_result_void_approved  FOREIGN KEY (void_approved_by)     REFERENCES users(user_id)
);

-- Soft-copy requests: status (Pending/Fulfilled/Cancelled) handles full lifecycle.
CREATE TABLE lab_result_requests (
    result_request_id INT AUTO_INCREMENT PRIMARY KEY,
    result_id         INT NOT NULL,
    patient_id        INT NULL,
    doctor_id         INT NULL,
    requested_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    status            ENUM('Pending','Fulfilled','Cancelled') NOT NULL DEFAULT 'Pending',
    fulfilled_by      INT NULL,
    fulfilled_at      DATETIME NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_requester CHECK (
        (patient_id IS NOT NULL AND doctor_id IS NULL) OR
        (patient_id IS NULL     AND doctor_id IS NOT NULL)
    ),
    CONSTRAINT fk_resultreq_result    FOREIGN KEY (result_id)    REFERENCES lab_results(result_id)        ON DELETE CASCADE,
    CONSTRAINT fk_resultreq_patient   FOREIGN KEY (patient_id)   REFERENCES patient_profiles(patient_id),
    CONSTRAINT fk_resultreq_doctor    FOREIGN KEY (doctor_id)    REFERENCES doctor_profiles(doctor_id),
    CONSTRAINT fk_resultreq_fulfilled FOREIGN KEY (fulfilled_by) REFERENCES med_tech_profiles(medtech_id)
);

-- Lab appointments: status (Scheduled/Confirmed/In Progress/Completed/Cancelled/No Show) handles full lifecycle.
CREATE TABLE lab_appointments (
    lab_appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id         INT NOT NULL,
    lab_request_id     INT NULL,
    scheduled_at       DATETIME NOT NULL,
    status             ENUM('Scheduled','Confirmed','In Progress','Completed','Cancelled','No Show') NOT NULL DEFAULT 'Scheduled',
    notes              TEXT NULL,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_labappt_patient  FOREIGN KEY (patient_id)     REFERENCES patient_profiles(patient_id),
    CONSTRAINT fk_labappt_request  FOREIGN KEY (lab_request_id) REFERENCES lab_requests(lab_request_id)
);

CREATE INDEX idx_lab_appointments_patient ON lab_appointments(patient_id);
CREATE INDEX idx_lab_appointments_date    ON lab_appointments(scheduled_at);

-- Appointments & Consultations

CREATE TABLE appointment_statuses (
    appointment_status_id INT AUTO_INCREMENT PRIMARY KEY,
    status_name           VARCHAR(50) NOT NULL UNIQUE,
    created_at            DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO appointment_statuses (status_name) VALUES
    ('Scheduled'), ('Confirmed'), ('In Progress'),
    ('Completed'), ('Cancelled'), ('No Show'), ('Rescheduled');

CREATE TABLE appointments (
    appointment_id      INT AUTO_INCREMENT PRIMARY KEY,
    patient_id          INT NOT NULL,
    doctor_id           INT NOT NULL,
    duty_session_id     INT NULL,
    appointment_at      DATETIME NOT NULL,
    duration_minutes    SMALLINT DEFAULT 30,
    rescheduled_from_id INT NULL,
    reason_for_visit    TEXT,
    appointment_type    ENUM('Scheduled','Walk-in','Follow-up') DEFAULT 'Scheduled',
    status_id           INT NOT NULL,
    is_voided           TINYINT(1) DEFAULT 0,
    void_at             DATETIME   NULL,
    void_reason         TEXT       NULL,
    void_approved_by    INT        NULL,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_appt_duration     CHECK (duration_minutes > 0),
    CONSTRAINT fk_appt_patient       FOREIGN KEY (patient_id)          REFERENCES patient_profiles(patient_id),
    CONSTRAINT fk_appt_doctor        FOREIGN KEY (doctor_id)           REFERENCES doctor_profiles(doctor_id),
    CONSTRAINT fk_appt_duty_session  FOREIGN KEY (duty_session_id)     REFERENCES doctor_duty_sessions(duty_session_id),
    CONSTRAINT fk_appt_status        FOREIGN KEY (status_id)           REFERENCES appointment_statuses(appointment_status_id),
    CONSTRAINT fk_appt_rescheduled   FOREIGN KEY (rescheduled_from_id) REFERENCES appointments(appointment_id)  ON DELETE SET NULL,
    CONSTRAINT fk_appt_void_approved FOREIGN KEY (void_approved_by)    REFERENCES users(user_id)
);

CREATE INDEX idx_appointments_doctor_date  ON appointments(doctor_id, appointment_at);
CREATE INDEX idx_appointments_patient      ON appointments(patient_id);
CREATE INDEX idx_appointments_duty_session ON appointments(duty_session_id);

-- Walk-in consultations have appointment_id = NULL.
CREATE TABLE consultations (
    consultation_id  INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id   INT NULL UNIQUE,
    doctor_id        INT NOT NULL,
    patient_id       INT NOT NULL,
    consultation_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    chief_complaint  TEXT,
    weight_kg        DECIMAL(5,2),
    height_cm        DECIMAL(5,2),
    temp_c           DECIMAL(4,2),
    bp_systolic      SMALLINT,
    bp_diastolic     SMALLINT,
    heart_rate       SMALLINT,
    respiratory_rate SMALLINT,
    clinical_notes   TEXT,
    follow_up_at     DATETIME NULL,
    is_voided        TINYINT(1) DEFAULT 0,
    void_at          DATETIME   NULL,
    void_reason      TEXT       NULL,
    void_approved_by INT        NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_consult_appointment   FOREIGN KEY (appointment_id)  REFERENCES appointments(appointment_id)  ON DELETE SET NULL,
    CONSTRAINT fk_consult_doctor        FOREIGN KEY (doctor_id)       REFERENCES doctor_profiles(doctor_id),
    CONSTRAINT fk_consult_patient       FOREIGN KEY (patient_id)      REFERENCES patient_profiles(patient_id),
    CONSTRAINT fk_consult_void_approved FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

CREATE TABLE diagnoses (
    diagnosis_id     INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id  INT NOT NULL,
    diagnosed_by     INT NOT NULL,
    icd_code         VARCHAR(20) NULL,
    description      TEXT        NOT NULL,
    diagnosis_type   ENUM('Primary','Secondary','Differential') DEFAULT 'Primary',
    diagnosed_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_voided        TINYINT(1) DEFAULT 0,
    void_at          DATETIME   NULL,
    void_reason      TEXT       NULL,
    void_approved_by INT        NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_diagnosis_consultation  FOREIGN KEY (consultation_id)  REFERENCES consultations(consultation_id),
    CONSTRAINT fk_diagnosis_doctor        FOREIGN KEY (diagnosed_by)     REFERENCES doctor_profiles(doctor_id),
    CONSTRAINT fk_diagnosis_void_approved FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

-- Prescriptions

CREATE TABLE prescriptions (
    prescription_id  INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id  INT NOT NULL UNIQUE,
    prescribed_by    INT NOT NULL,
    prescribed_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarks          TEXT,
    validity_days    SMALLINT,
    is_voided        TINYINT(1) DEFAULT 0,
    void_at          DATETIME   NULL,
    void_reason      TEXT       NULL,
    void_approved_by INT        NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rx_consultation   FOREIGN KEY (consultation_id)  REFERENCES consultations(consultation_id),
    CONSTRAINT fk_rx_doctor         FOREIGN KEY (prescribed_by)    REFERENCES doctor_profiles(doctor_id),
    CONSTRAINT fk_rx_void_approved  FOREIGN KEY (void_approved_by) REFERENCES users(user_id)
);

CREATE TABLE prescription_items (
    prescription_item_id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id      INT NOT NULL,
    medicine_name        VARCHAR(150) NOT NULL,
    dosage               VARCHAR(50),
    form                 VARCHAR(50),
    frequency            VARCHAR(50),
    duration             VARCHAR(50),
    quantity             SMALLINT,
    instructions         TEXT,
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rxitem_prescription FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE
);

-- Void Requests

CREATE TABLE void_requests (
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

-- Audit Logs

CREATE TABLE audit_logs (
    log_id          BIGINT AUTO_INCREMENT PRIMARY KEY,
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

-- Roles seed

INSERT INTO roles (name, display_name) VALUES
    ('super_admin',  'Super Admin / Owner'),
    ('doctor',       'Doctor'),
    ('receptionist', 'Receptionist'),
    ('med_tech',     'Medical Technologist'),
    ('patient',      'Patient');
