-- Phase 6: restructure the BPA model.
-- Focal sets change from adjacent pairs ({Mild,Moderate}, {Moderate,Severe},
-- {Severe,Extreme}) to nested "at least X" sets ({Moderate,Severe,Extreme},
-- {Severe,Extreme}, {Extreme}). The columns are renamed to match the new meaning
-- and every row is rewritten - old values are NOT carried over, because the old
-- numbers describe a different semantics entirely.

ALTER TABLE ds_symptoms
  CHANGE COLUMN m_mild_moderate   m_min_moderate DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  CHANGE COLUMN m_moderate_severe m_min_severe   DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  CHANGE COLUMN m_severe_extreme  m_extreme      DECIMAL(4,2) NOT NULL DEFAULT 0.00;

-- Ringan: mostly Theta so one mild symptom cannot force the level up, but keeps
-- 0.20 on "at least Moderate" so many mild symptoms still accumulate.
UPDATE ds_symptoms SET m_min_moderate=0.20, m_min_severe=0.05, m_extreme=0.00, m_theta=0.75
  WHERE symptom_code IN ('G-D04','G-A01','G-A03','G-S01','G-S06');

-- Sedang
UPDATE ds_symptoms SET m_min_moderate=0.45, m_min_severe=0.15, m_extreme=0.00, m_theta=0.40
  WHERE symptom_code IN ('G-D02','G-A02','G-A04','G-A05','G-S02','G-S03','G-S07');

-- Berat
UPDATE ds_symptoms SET m_min_moderate=0.25, m_min_severe=0.50, m_extreme=0.05, m_theta=0.20
  WHERE symptom_code IN ('G-D01','G-D03','G-D05','G-A06','G-S04','G-S05');

-- Sangat Berat: direct mass on {Extreme} so one clinically critical symptom
-- (hopelessness, life-not-worth-living, active panic) moves the result by itself.
UPDATE ds_symptoms SET m_min_moderate=0.10, m_min_severe=0.35, m_extreme=0.40, m_theta=0.15
  WHERE symptom_code IN ('G-D06','G-D07','G-A07');
