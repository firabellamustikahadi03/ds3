-- Phase 3: public screening intake (name+age) + send-to-doctor promotion.
-- Additive only - no existing column is changed or dropped.

ALTER TABLE diagnoses
  ADD COLUMN patient_name VARCHAR(100) NOT NULL DEFAULT '-' AFTER symptoms_text,
  ADD COLUMN patient_age VARCHAR(10) NOT NULL DEFAULT '-' AFTER patient_name,
  ADD COLUMN sent_to_doctor_id INT NULL AFTER confidence_percentage,
  ADD COLUMN sent_at VARCHAR(50) NULL AFTER sent_to_doctor_id,
  ADD CONSTRAINT fk_diagnoses_sent_to_doctor FOREIGN KEY (sent_to_doctor_id) REFERENCES admins (id);

ALTER TABLE diagnosis_history
  ADD COLUMN origin ENUM('dokter','screening_mandiri') NOT NULL DEFAULT 'dokter' AFTER patient_id;
