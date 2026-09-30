-- Adds a proper symptom-selection junction table. Previously the only record of which
-- symptoms a patient picked was a pre-rendered text blob (diagnoses.symptoms_text /
-- diagnosis_history.symptoms_text), frozen in whatever language was active at diagnosis
-- time. That made the historical detail pages unable to re-translate the symptom list
-- when an admin/doctor later viewed it in a different language. This table stores the
-- actual symptom_id selections so the detail pages can re-derive the list live, in
-- whatever language is currently active — same pattern as diagnosis_details already
-- uses for the subscale results (diagnosis_id + source, no FK since diagnosis_id points
-- at either `diagnoses` or `diagnosis_history` depending on source).
CREATE TABLE diagnosis_symptoms (
    id            INT NOT NULL AUTO_INCREMENT,
    diagnosis_id  INT NOT NULL,
    source        ENUM('diagnosa','riwayat') NOT NULL DEFAULT 'diagnosa',
    symptom_id    INT NOT NULL,
    PRIMARY KEY (id),
    KEY idx_diagnosis (diagnosis_id, source),
    KEY idx_symptom (symptom_id),
    CONSTRAINT fk_diagnosis_symptoms_symptom FOREIGN KEY (symptom_id) REFERENCES ds_symptoms (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
