-- ============================================================
-- PIMS INCREMENTAL MIGRATION — 2026-06-13
-- Adds the `lab_appointments` table so patients can book
-- laboratory visits independently of any doctor consultation.
--
-- Safe to run against an existing pims_db that was created from
-- PIMS_DB_corrected.sql BEFORE this table existed. Fresh installs
-- already include the table and do NOT need this file.
--
-- Apply with:
--   mysql -u root -p pims_db < database/sql/PIMS_migration_2026_06_13_lab_appointments.sql
-- ============================================================

USE pims_db;

CREATE TABLE IF NOT EXISTS lab_appointments (
    lab_appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id         INT NOT NULL,
    lab_request_id     INT NULL,
    scheduled_at       DATETIME NOT NULL,
    status             ENUM('Scheduled','Confirmed','In Progress','Completed','Cancelled','No Show')
                          NOT NULL DEFAULT 'Scheduled',
    notes              TEXT NULL,
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
