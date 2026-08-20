-- Backup existing knowledge-base tables (Stadium 1/2/3 model) — not dropped, just renamed.
RENAME TABLE ds_gejala TO ds_gejala_old;
RENAME TABLE ds_penyakit TO ds_penyakit_old;
RENAME TABLE ds_aturan TO ds_aturan_old;

-- New DASS-21 gejala table: 21 rows, one per DASS-21 item, mass function baked in.
CREATE TABLE ds_gejala (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    kode_gejala   VARCHAR(10)  NOT NULL UNIQUE,
    subskala      ENUM('D','A','S') NOT NULL,
    item_dass     TINYINT      NOT NULL,
    nama_id       VARCHAR(255) NOT NULL,
    nama_en       VARCHAR(255) NOT NULL,
    nama_tr       VARCHAR(255) NOT NULL,
    nama_zh       VARCHAR(255) NOT NULL,
    m_ho          DECIMAL(4,2) NOT NULL,
    m_oa          DECIMAL(4,2) NOT NULL,
    m_aca         DECIMAL(4,2) NOT NULL,
    m_theta       DECIMAL(4,2) NOT NULL,
    tipe_gejala   TINYINT      NOT NULL,
    is_active     TINYINT(1)   DEFAULT 1,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- New severity-level table (replaces ds_penyakit): 12 rows = 3 subskala x 4 level.
CREATE TABLE ds_tingkat (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    subskala      ENUM('D','A','S') NOT NULL,
    level         ENUM('H','O','A','CA') NOT NULL,
    urutan        TINYINT NOT NULL,
    nama_id       VARCHAR(100) NOT NULL,
    nama_en       VARCHAR(100) NOT NULL,
    nama_tr       VARCHAR(100) NOT NULL,
    nama_zh       VARCHAR(100) NOT NULL,
    kett          MEDIUMTEXT NOT NULL,
    kett_en       MEDIUMTEXT,
    kett_tr       MEDIUMTEXT,
    kett_zh       MEDIUMTEXT,
    UNIQUE KEY uq_subskala_level (subskala, level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Per-subscale diagnosis result detail (3 rows per diagnosis session).
CREATE TABLE diagnosa_detail (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_diagnosa   INT NOT NULL,
    sumber        ENUM('diagnosa','riwayat') NOT NULL DEFAULT 'diagnosa',
    subskala      ENUM('D','A','S') NOT NULL,
    level_kode    ENUM('H','O','A','CA') NOT NULL,
    level_nama    VARCHAR(100) NOT NULL,
    nilai         DECIMAL(6,4) NOT NULL,
    persentase    VARCHAR(10) NOT NULL,
    INDEX idx_diagnosa (id_diagnosa, sumber)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
