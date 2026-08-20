# DASS-21 Migration — Phase 1 (Core Engine + Public Diagnosis Flow) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace ds3's Stadium 1/2/3 Dempster-Shafer diagnosis with the DASS-21 model (Depression/Anxiety/Stress,
4 severity levels each) end-to-end for the public patient-facing flow, so a patient can take a DASS-21 diagnosis
and see 3 independent results.

**Architecture:** New tables `ds_gejala` (21 DASS-21 items with mass functions) and `ds_tingkat` (12 severity-level
descriptions) replace the old `ds_gejala`/`ds_penyakit`/`ds_aturan` (renamed to `*_old` for backup, not deleted).
`controller/c_Diagnosa.php` gets a generalized N-ary Dempster-Shafer combination engine plus a
`hitungSubskala($subskala, $gejalaIds)` orchestration method that `hasil.php` calls 3 times (once per subscale).
Anything that still depends on the old schema and isn't part of this phase's scope (Admin gejala/penyakit/basisp
CRUD, dokter-panel diagnosis) gets a safe "coming in the next update" placeholder instead of being left to error.

**Tech Stack:** PHP 8.2 (no framework), MySQL/MariaDB via XAMPP, mysqli. No test framework is installed — "tests"
in this plan are standalone PHP CLI scripts under `tests/` that assert and print PASS/FAIL, run via `php.exe`.

**Scope note:** This is Phase 1 of 2. Phase 2 (separate plan, written after this ships) covers: Admin CRUD for
`ds_gejala`/`ds_tingkat`, removing the `ds_aturan`-based basis-pengetahuan admin page, and rebuilding the
dokter-panel diagnosis flow (`dokter/diagnosa.php` / `dokter/hdiagnosa.php`) on the new schema. Splitting here
because Phase 1 alone already produces working, testable software (a patient can take a full DASS-21 diagnosis
on the public site) without needing the admin/dokter rework to land first.

**Working directory for all file paths in this plan:** `C:\xampp\htdocs\ds3\` (the live XAMPP-served copy — this
is what Apache serves at `http://localhost/ds3` and what you test in the browser). The last task syncs the
finished result into the git repo at `C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3\`
and commits it there.

---

### Task 1: Schema migration SQL

**Files:**
- Create: `C:\xampp\htdocs\ds3\migrations\2026-08-20_01_schema.sql`

- [ ] **Step 1: Write the migration SQL**

```sql
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
```

- [ ] **Step 2: Run it against the local database**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer < "C:\xampp\htdocs\ds3\migrations\2026-08-20_01_schema.sql"
```
Expected: no output (success). If it errors with "table already exists", the migration was already partially run — check `SHOW TABLES` before re-running any part.

- [ ] **Step 3: Verify**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root -e "USE spdempstershafer; SHOW TABLES;"
```
Expected: includes `ds_gejala`, `ds_gejala_old`, `ds_penyakit_old`, `ds_aturan_old`, `ds_tingkat`, `diagnosa_detail` (in addition to the untouched `admin`, `diagnosa`, `pasien`, `riwayat`, `translations`).

---

### Task 2: Seed `ds_gejala` (21 DASS-21 items)

**Files:**
- Create: `C:\xampp\htdocs\ds3\migrations\2026-08-20_02_seed_gejala.sql`

- [ ] **Step 1: Write the seed SQL**

`nama_id`/`nama_tr` below are taken verbatim from the `dass21_knowledge_base.sql` the user supplied.
`nama_en`/`nama_zh` are new translations of that same Indonesian/Turkish text (not independently sourced
from the official English DASS-21 wording, to avoid any risk of mismatching item numbers from memory —
flag these for a native-speaker/advisor check later, same as any translated instrument).

```sql
INSERT INTO ds_gejala
(kode_gejala, subskala, item_dass, nama_id, nama_en, nama_tr, nama_zh, m_ho, m_oa, m_aca, m_theta, tipe_gejala, is_active)
VALUES
('G-D01','D',3,'Tidak dapat merasakan perasaan positif sama sekali (anhedoni)','Unable to feel any positive emotions at all (anhedonia)','Olumlu duygular hissedememe (anhedoni)','完全无法感受到任何正面情绪（快感缺失）',0.35,0.30,0.20,0.15,1,1),
('G-D02','D',5,'Merasa tidak ada hal yang dapat ditunggu atau diantisipasi','Feeling that there is nothing to look forward to or anticipate','Gelecekte olacak şeyler hakkında ümit ya da beklenti duymama','感觉没有什么值得期待的事情',0.35,0.30,0.20,0.15,1,1),
('G-D03','D',10,'Merasa hidup tidak berarti atau hampa','Feeling that life is meaningless or empty','Hayatı anlamsız hissetme / boşluk duygusu','感觉生活毫无意义或空虚',0.30,0.30,0.25,0.15,1,1),
('G-D04','D',13,'Sulit untuk bersemangat dalam melakukan sesuatu','Difficulty feeling enthusiastic or motivated to do things','Bir şeyler yapmaya istekli hissedememe / motivasyon eksikliği','很难对做任何事情感到有动力或热情',0.35,0.30,0.20,0.15,1,1),
('G-D05','D',16,'Merasa tidak berharga sebagai manusia','Feeling worthless as a person','Değersizlik duygusu / düşük benlik saygısı','感觉自己作为一个人毫无价值',0.30,0.30,0.25,0.15,1,1),
('G-D06','D',17,'Merasa tidak ada hal yang dilakukan membuat lebih baik (putus asa)','Feeling that nothing one does makes things better (hopelessness)','Hiçbir şeyin kendini daha iyi hissettirmeyeceğini düşünme (umutsuzluk)','感觉无论做什么都无法让情况变得更好（绝望感）',0.30,0.30,0.25,0.15,1,1),
('G-D07','D',21,'Hidup terasa tidak bernilai atau tanpa tujuan','Life feels worthless or without purpose','Yaşamın anlamsız / değersiz olduğunu hissetme','感觉生活毫无价值或没有目标',0.30,0.30,0.25,0.15,1,1),
('G-A01','A',2,'Mulut terasa kering','Dryness of the mouth','Ağız kuruluğu','感到口干',0.30,0.35,0.20,0.15,2,1),
('G-A02','A',4,'Mengalami kesulitan bernapas (sesak, ngos-ngosan)','Difficulty breathing (shortness of breath, gasping)','Nefes almada güçlük / nefes nefese kalma','感到呼吸困难（气短、喘不过气）',0.25,0.35,0.25,0.15,2,1),
('G-A03','A',7,'Tangan gemetar atau tremor','Trembling or shaking of the hands','Ellerde titreme / tremor','手部颤抖或抖动',0.30,0.35,0.20,0.15,2,1),
('G-A04','A',9,'Merasa khawatir akan panik dan mempermalukan diri sendiri','Worried about panicking and embarrassing oneself','Panik geçirme ve kendini utandırma konusunda kaygı duyma','担心自己会恐慌发作并因此出丑',0.25,0.35,0.25,0.15,2,1),
('G-A05','A',15,'Jantung berdebar kencang tanpa sebab fisik','Heart pounding rapidly without physical cause','Fiziksel bir neden olmaksızın kalp çarpıntısı yaşama','在没有体力消耗的情况下心跳剧烈',0.25,0.35,0.25,0.15,2,1),
('G-A06','A',19,'Merasa takut tanpa alasan yang jelas','Feeling scared without any clear reason','Açık bir neden olmaksızın korku ve endişe hissetme','无缘无故感到害怕',0.25,0.35,0.20,0.20,3,1),
('G-A07','A',20,'Merasa hampir panik atau akan pingsan','Feeling close to panic or about to faint','Panik eşiğinde olduğunu / bayılacakmış gibi hissetme','感觉快要恐慌发作或即将昏厥',0.20,0.35,0.25,0.20,3,1),
('G-S01','S',1,'Mudah marah terhadap hal-hal sepele','Getting easily upset or angry over trivial matters','Küçük şeylere aşırı tepki gösterme / kolayca sinirlenme','容易因为一些小事而生气',0.35,0.30,0.20,0.15,1,1),
('G-S02','S',6,'Cenderung bereaksi berlebihan terhadap situasi','Tending to over-react to situations','Durumlara aşırı tepki eğilimi','容易对各种情况反应过度',0.35,0.25,0.25,0.15,1,1),
('G-S03','S',8,'Sulit untuk tenang atau rileks','Difficulty staying calm or relaxed','Sakinleşmede / gevşemede güçlük çekme','很难保持平静或放松',0.30,0.30,0.25,0.15,1,1),
('G-S04','S',11,'Merasa sangat mudah tersinggung atau sensitif','Feeling very easily offended or sensitive','Aşırı duyarlılık / alınganlık','感觉自己非常容易被冒犯或敏感',0.35,0.25,0.25,0.15,1,1),
('G-S05','S',12,'Merasa menghabiskan banyak energi karena kecemasan','Feeling that a lot of energy is spent due to anxiousness','Kaygı nedeniyle çok fazla enerji harcandığını hissetme','感觉因焦虑而消耗了大量精力',0.30,0.30,0.25,0.15,1,1),
('G-S06','S',14,'Tidak sabar ketika mengalami penundaan atau hambatan','Feeling impatient when facing delays or obstacles','Gecikme ya da engellemeler karşısında sabırsızlanma','在遇到延误或阻碍时感到不耐烦',0.35,0.30,0.20,0.15,1,1),
('G-S07','S',18,'Mudah tersinggung atau iritabel secara umum','Generally easily irritated or irritable','Genel olarak kolayca tahrik olma / sinirlenme','总体上容易被激怒或烦躁',0.35,0.30,0.20,0.15,1,1);
```

- [ ] **Step 2: Run it**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer < "C:\xampp\htdocs\ds3\migrations\2026-08-20_02_seed_gejala.sql"
```
Expected: no output.

---

### Task 3: Seed `ds_tingkat` (12 severity-level rows)

**Files:**
- Create: `C:\xampp\htdocs\ds3\migrations\2026-08-20_03_seed_tingkat.sql`

- [ ] **Step 1: Write the seed SQL**

```sql
INSERT INTO ds_tingkat
(subskala, level, urutan, nama_id, nama_en, nama_tr, nama_zh, kett, kett_en, kett_tr, kett_zh)
VALUES
('D','H',1,'Depresi - Ringan','Depression - Mild','Depresyon - Hafif','抑郁 - 轻度',
 'Gejala depresi ringan terdeteksi. Cobalah untuk tetap aktif secara sosial, jaga pola tidur, dan bicarakan perasaan Anda dengan orang terdekat.',
 'Mild depressive symptoms detected. Try to stay socially active, maintain a regular sleep schedule, and talk about your feelings with someone close to you.',
 'Hafif düzeyde depresyon belirtisi tespit edildi. Sosyal olarak aktif kalmaya, düzenli uyku alışkanlığı sürdürmeye ve hislerinizi yakınlarınızla paylaşmaya çalışın.',
 '检测到轻度抑郁症状。尝试保持社交活跃、维持规律的睡眠，并与亲近的人谈谈您的感受。'),
('D','O',2,'Depresi - Sedang','Depression - Moderate','Depresyon - Orta','抑郁 - 中度',
 'Gejala depresi tingkat sedang terdeteksi. Disarankan untuk berkonsultasi dengan konselor atau psikolog kampus untuk pendampingan lebih lanjut.',
 'Moderate depressive symptoms detected. It is recommended to consult a counselor or campus psychologist for further guidance.',
 'Orta düzeyde depresyon belirtisi tespit edildi. Daha fazla destek için bir danışman veya kampüs psikoloğuyla görüşmeniz önerilir.',
 '检测到中度抑郁症状。建议咨询辅导员或校园心理师以获得进一步的支持。'),
('D','A',3,'Depresi - Berat','Depression - Severe','Depresyon - Ağır','抑郁 - 重度',
 'Gejala depresi tingkat berat terdeteksi. Segera lakukan konsultasi dengan psikolog atau psikiater untuk penanganan profesional.',
 'Severe depressive symptoms detected. Please consult a psychologist or psychiatrist immediately for professional care.',
 'Ağır düzeyde depresyon belirtisi tespit edildi. Profesyonel destek için lütfen bir psikolog veya psikiyatriste başvurun.',
 '检测到重度抑郁症状。请立即咨询心理师或精神科医生以获得专业治疗。'),
('D','CA',4,'Depresi - Sangat Berat','Depression - Extremely Severe','Depresyon - Çok Ağır','抑郁 - 非常严重',
 'Gejala depresi sangat berat terdeteksi. Segera cari bantuan profesional (psikiater) sesegera mungkin. Jika muncul pikiran untuk menyakiti diri sendiri, segera hubungi layanan darurat atau orang terdekat.',
 'Extremely severe depressive symptoms detected. Seek professional help (psychiatrist) as soon as possible. If you have thoughts of self-harm, contact emergency services or someone close to you immediately.',
 'Çok ağır düzeyde depresyon belirtisi tespit edildi. En kısa sürede profesyonel yardım (psikiyatrist) alın. Kendinize zarar verme düşünceleriniz varsa hemen acil yardım hattını veya yakınlarınızı arayın.',
 '检测到非常严重的抑郁症状。请尽快寻求专业帮助（精神科医生）。如果出现自我伤害的想法，请立即联系紧急服务或身边的人。'),
('A','H',1,'Anxiety - Ringan','Anxiety - Mild','Anksiyete - Hafif','焦虑 - 轻度',
 'Gejala kecemasan ringan terdeteksi. Coba teknik relaksasi pernapasan dan kelola waktu istirahat dengan baik.',
 'Mild anxiety symptoms detected. Try breathing relaxation techniques and manage your rest time well.',
 'Hafif düzeyde kaygı belirtisi tespit edildi. Nefes egzersizleri deneyin ve dinlenme sürenizi iyi yönetin.',
 '检测到轻度焦虑症状。尝试呼吸放松技巧，并合理安排休息时间。'),
('A','O',2,'Anxiety - Sedang','Anxiety - Moderate','Anksiyete - Orta','焦虑 - 中度',
 'Gejala kecemasan tingkat sedang terdeteksi. Disarankan berkonsultasi dengan konselor untuk mempelajari teknik manajemen kecemasan.',
 'Moderate anxiety symptoms detected. It is recommended to consult a counselor to learn anxiety management techniques.',
 'Orta düzeyde kaygı belirtisi tespit edildi. Kaygı yönetim tekniklerini öğrenmek için bir danışmanla görüşmeniz önerilir.',
 '检测到中度焦虑症状。建议咨询辅导员学习焦虑管理技巧。'),
('A','A',3,'Anxiety - Berat','Anxiety - Severe','Anksiyete - Ağır','焦虑 - 重度',
 'Gejala kecemasan tingkat berat terdeteksi. Segera konsultasikan dengan psikolog atau psikiater untuk penanganan lebih lanjut.',
 'Severe anxiety symptoms detected. Please consult a psychologist or psychiatrist immediately for further care.',
 'Ağır düzeyde kaygı belirtisi tespit edildi. Lütfen daha fazla destek için bir psikolog veya psikiyatriste başvurun.',
 '检测到重度焦虑症状。请立即咨询心理师或精神科医生以获得进一步治疗。'),
('A','CA',4,'Anxiety - Sangat Berat','Anxiety - Extremely Severe','Anksiyete - Çok Ağır','焦虑 - 非常严重',
 'Gejala kecemasan sangat berat terdeteksi. Segera cari bantuan profesional (psikiater) karena kecemasan pada tingkat ini dapat sangat mengganggu aktivitas sehari-hari.',
 'Extremely severe anxiety symptoms detected. Seek professional help (psychiatrist) immediately, as anxiety at this level can significantly disrupt daily activities.',
 'Çok ağır düzeyde kaygı belirtisi tespit edildi. Bu düzeydeki kaygı günlük yaşamı ciddi şekilde etkileyebileceğinden en kısa sürede profesyonel yardım (psikiyatrist) alın.',
 '检测到非常严重的焦虑症状。由于此程度的焦虑可能严重影响日常生活，请立即寻求专业帮助（精神科医生）。'),
('S','H',1,'Stres - Ringan','Stress - Mild','Stres - Hafif','压力 - 轻度',
 'Gejala stres ringan terdeteksi. Luangkan waktu untuk beristirahat dan melakukan aktivitas yang Anda sukai.',
 'Mild stress symptoms detected. Take time to rest and engage in activities you enjoy.',
 'Hafif düzeyde stres belirtisi tespit edildi. Dinlenmeye vakit ayırın ve keyif aldığınız aktivitelere zaman ayırın.',
 '检测到轻度压力症状。请抽出时间休息，并从事您喜欢的活动。'),
('S','O',2,'Stres - Sedang','Stress - Moderate','Stres - Orta','压力 - 中度',
 'Gejala stres tingkat sedang terdeteksi. Coba kelola beban tugas dan pertimbangkan konsultasi dengan konselor kampus.',
 'Moderate stress symptoms detected. Try to manage your workload and consider consulting a campus counselor.',
 'Orta düzeyde stres belirtisi tespit edildi. İş yükünüzü yönetmeye çalışın ve bir kampüs danışmanıyla görüşmeyi düşünün.',
 '检测到中度压力症状。请尝试管理您的工作负荷，并考虑咨询校园辅导员。'),
('S','A',3,'Stres - Berat','Stress - Severe','Stres - Ağır','压力 - 重度',
 'Gejala stres tingkat berat terdeteksi. Disarankan segera berkonsultasi dengan psikolog untuk mempelajari strategi manajemen stres yang efektif.',
 'Severe stress symptoms detected. It is recommended to consult a psychologist immediately to learn effective stress management strategies.',
 'Ağır düzeyde stres belirtisi tespit edildi. Etkili stres yönetimi stratejileri öğrenmek için en kısa sürede bir psikologla görüşmeniz önerilir.',
 '检测到重度压力症状。建议立即咨询心理师，学习有效的压力管理策略。'),
('S','CA',4,'Stres - Sangat Berat','Stress - Extremely Severe','Stres - Çok Ağır','压力 - 非常严重',
 'Gejala stres sangat berat terdeteksi. Segera cari bantuan profesional (psikolog/psikiater) karena tingkat stres ini berisiko mempengaruhi kesehatan fisik dan mental secara signifikan.',
 'Extremely severe stress symptoms detected. Seek professional help (psychologist/psychiatrist) immediately, as this level of stress risks significantly affecting your physical and mental health.',
 'Çok ağır düzeyde stres belirtisi tespit edildi. Bu düzeydeki stres fiziksel ve ruhsal sağlığınızı ciddi şekilde etkileme riski taşıdığından en kısa sürede profesyonel yardım (psikolog/psikiyatrist) alın.',
 '检测到非常严重的压力症状。由于此程度的压力可能严重影响您的身心健康，请立即寻求专业帮助（心理师/精神科医生）。');
```

- [ ] **Step 2: Run it**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer < "C:\xampp\htdocs\ds3\migrations\2026-08-20_03_seed_tingkat.sql"
```
Expected: no output.

---

### Task 4: Verify seed data

- [ ] **Step 1: Check row counts and mass totals**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root -e "USE spdempstershafer; SELECT subskala, COUNT(*) FROM ds_gejala GROUP BY subskala;"
```
Expected: 3 rows, each showing count `7` (D, A, S).

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root -e "USE spdempstershafer; SELECT COUNT(*) FROM ds_tingkat;"
```
Expected: `12`.

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root -e "USE spdempstershafer; SELECT kode_gejala FROM ds_gejala WHERE ROUND(m_ho+m_oa+m_aca+m_theta,2) <> 1.00;"
```
Expected: empty result (no rows) — confirms every gejala's mass function sums to 1.00.

- [ ] **Step 2: Commit the migration files**

```bash
cd "C:\xampp\htdocs\ds3"
git init 2>nul
```
(Skip git operations here if `C:\xampp\htdocs\ds3` isn't a git repo — this folder is the live server copy;
version control happens on the git repo copy in Task 18. Just confirm the 3 `.sql` files exist on disk under
`migrations\`.)

---

### Task 5: Write failing unit tests for the Dempster-Shafer math

**Files:**
- Create: `C:\xampp\htdocs\ds3\tests\test_dempster_shafer.php`

- [ ] **Step 1: Write the test script**

```php
<?php
require_once __DIR__ . '/../controller/c_Diagnosa.php';

$dg = new Diagnosa();
$failures = 0;

function assertClose($actual, $expected, $label, &$failures, $tolerance = 0.0001) {
    if (abs($actual - $expected) > $tolerance) {
        echo "FAIL: $label — expected $expected, got $actual\n";
        $failures++;
    } else {
        echo "PASS: $label\n";
    }
}

// Test 1: combineMass — two evidence sets with full overlap, no conflict
$m1 = ['1,2' => 0.6, '1,2,3,4' => 0.4];
$m2 = ['1,2' => 0.5, '1,2,3,4' => 0.5];
$combined = $dg->combineMass($m1, $m2);
assertClose($combined['1,2'], 0.80, 'combineMass no-conflict {1,2} total', $failures);
assertClose($combined['1,2,3,4'], 0.20, 'combineMass no-conflict theta total', $failures);
assertClose(array_sum($combined), 1.0, 'combineMass no-conflict sums to 1', $failures);

// Test 2: combineMass — with conflict (disjoint sets)
$m3 = ['1,2' => 0.7, '1,2,3,4' => 0.3];
$m4 = ['3,4' => 0.6, '1,2,3,4' => 0.4];
$combined2 = $dg->combineMass($m3, $m4);
assertClose($combined2['#CONFLICT#'], 0.42, 'combineMass conflict mass', $failures);
assertClose($combined2['1,2'], 0.28, 'combineMass conflict {1,2}', $failures);
assertClose($combined2['3,4'], 0.18, 'combineMass conflict {3,4}', $failures);
assertClose($combined2['1,2,3,4'], 0.12, 'combineMass conflict theta', $failures);

// Test 3: normalizeMass removes conflict and renormalizes to sum 1
$normalized = $dg->normalizeMass($combined2);
assertClose(array_sum($normalized), 1.0, 'normalizeMass sums to 1 after removing conflict', $failures);
assertClose($normalized['1,2'], 0.28 / (1 - 0.42), 'normalizeMass {1,2} value', $failures);
if (isset($normalized['#CONFLICT#'])) {
    echo "FAIL: normalizeMass should remove the #CONFLICT# key\n";
    $failures++;
} else {
    echo "PASS: normalizeMass removes the #CONFLICT# key\n";
}

// Test 4: pignistic transform splits multi-element mass evenly and sums to 1
$mixed = ['1' => 0.5, '2,3' => 0.3, '1,2,3,4' => 0.2];
$pig = $dg->pignistic($mixed);
assertClose(array_sum($pig), 1.0, 'pignistic sums to 1', $failures);
assertClose($pig[1], 0.5 + 0.2 / 4, 'pignistic singleton 1 gets its own mass plus theta share', $failures);
assertClose($pig[2], 0.3 / 2 + 0.2 / 4, 'pignistic value for level 2', $failures);
assertClose($pig[3], 0.3 / 2 + 0.2 / 4, 'pignistic value for level 3', $failures);
assertClose($pig[4], 0.2 / 4, 'pignistic value for level 4', $failures);

// Test 5: buildEvidence skips zero-mass entries and sums to 1
$ev = $dg->buildEvidence(0.35, 0.30, 0, 0.35);
if (isset($ev['3,4'])) {
    echo "FAIL: buildEvidence should omit zero-mass m_aca\n";
    $failures++;
} else {
    echo "PASS: buildEvidence omits zero-mass m_aca\n";
}
assertClose(array_sum($ev), 1.0, 'buildEvidence sums to 1', $failures);

echo "\n" . ($failures === 0 ? "ALL TESTS PASSED" : "$failures TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
```

- [ ] **Step 2: Run it and confirm it fails**

Run:
```
"C:\xampp\php\php.exe" "C:\xampp\htdocs\ds3\tests\test_dempster_shafer.php"
```
Expected: fatal error — `Call to undefined method Diagnosa::combineMass()` (the methods don't exist yet).

---

### Task 6: Implement the Dempster-Shafer engine in `c_Diagnosa.php`

**Files:**
- Modify: `C:\xampp\htdocs\ds3\controller\c_Diagnosa.php`

- [ ] **Step 1: Replace the file contents**

```php
<?php
/**
 * Dempster-Shafer engine for the DASS-21 model.
 * Frame of discernment per subscale: 1=Hafif, 2=Orta, 3=Agir, 4=CokAgir.
 * Focal sets are represented as comma-joined, numerically sorted strings, e.g. "1,2".
 */
class Diagnosa
{
    /**
     * Combine two mass functions via Dempster's rule of combination (unnormalized —
     * conflicting mass is collected under the '#CONFLICT#' key).
     *
     * @param array $m1 ['1,2' => 0.35, '2,3' => 0.30, ...]
     * @param array $m2 same shape
     * @return array combined, unnormalized
     */
    function combineMass($m1, $m2)
    {
        $combined = [];
        foreach ($m1 as $setA => $massA) {
            $a = explode(',', $setA);
            foreach ($m2 as $setB => $massB) {
                $b = explode(',', $setB);
                $intersection = array_values(array_unique(array_intersect($a, $b)));
                sort($intersection, SORT_NUMERIC);
                $key = empty($intersection) ? '#CONFLICT#' : implode(',', $intersection);
                $product = $massA * $massB;
                $combined[$key] = ($combined[$key] ?? 0) + $product;
            }
        }
        return $combined;
    }

    /**
     * Normalize a combined mass function by dividing out total conflict mass K.
     * Returns [] (total ignorance) if K >= 1 (fully conflicting, degenerate case).
     */
    function normalizeMass($combined)
    {
        $conflict = $combined['#CONFLICT#'] ?? 0;
        unset($combined['#CONFLICT#']);
        if ($conflict >= 1.0) {
            return [];
        }
        $normalized = [];
        foreach ($combined as $set => $mass) {
            $normalized[$set] = $mass / (1 - $conflict);
        }
        return $normalized;
    }

    /**
     * Build the initial mass function (evidence) for one DASS-21 gejala row.
     * Zero-mass entries are omitted.
     */
    function buildEvidence($m_ho, $m_oa, $m_aca, $m_theta)
    {
        $evidence = [];
        if ($m_ho > 0)    $evidence['1,2']     = (float)$m_ho;
        if ($m_oa > 0)    $evidence['2,3']     = (float)$m_oa;
        if ($m_aca > 0)   $evidence['3,4']     = (float)$m_aca;
        if ($m_theta > 0) $evidence['1,2,3,4'] = (float)$m_theta;
        return $evidence;
    }

    /**
     * Pignistic transformation: split each multi-element focal set's mass equally
     * across its member singletons, then sum per singleton.
     * Returns [1 => p1, 2 => p2, 3 => p3, 4 => p4] summing to 1.0.
     */
    function pignistic($combined)
    {
        $pig = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];
        foreach ($combined as $setStr => $mass) {
            $elems = explode(',', $setStr);
            $share = $mass / count($elems);
            foreach ($elems as $e) {
                $pig[(int)$e] += $share;
            }
        }
        return $pig;
    }

    /**
     * Legacy name kept as a thin alias in case any old call site still resolves to it.
     * Converts the old [ [code, mass], ... ] row-pair format to assoc arrays and
     * delegates to combineMass().
     */
    function perkaliantabel($m, $densitas1, $densitas2, $densitas_baru)
    {
        $m1 = [];
        foreach ($densitas1 as $row) $m1[$row[0]] = $row[1];
        $m2 = [];
        foreach ($densitas2 as $row) $m2[$row[0]] = $row[1];
        $raw = $this->combineMass($m1, $m2);
        foreach ($raw as $k => $v) {
            $densitas_baru[$k] = ($densitas_baru[$k] ?? 0) + $v;
        }
        return $densitas_baru;
    }

    /**
     * Run the full Dempster-Shafer combination for one DASS-21 subscale across
     * the gejala the patient selected in that subscale.
     *
     * @param string $subskala 'D', 'A', or 'S'
     * @param int[]  $gejalaIds ids from ds_gejala already filtered to this subskala
     * @return array|null null when no matching, active gejala found; otherwise
     *   ['level_kode'=>'H'|'O'|'A'|'CA', 'level_nama'=>string, 'nilai'=>float,
     *    'persentase'=>string, 'kett'=>string]
     */
    function hitungSubskala($subskala, array $gejalaIds)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        // __DIR__-relative on purpose: this method is called both from root-level
        // pages (hasil.php) and from CLI test scripts under tests/, which have
        // different working directories. A bare "koneksi/koneksi.php" include only
        // resolves from the first kind of caller.
        include __DIR__ . '/../koneksi/koneksi.php';

        if (empty($gejalaIds)) return null;

        $inList = implode(',', array_map('intval', $gejalaIds));
        $subskalaEsc = mysqli_real_escape_string($con, $subskala);
        $sql = "SELECT m_ho, m_oa, m_aca, m_theta FROM ds_gejala
                WHERE id IN ($inList) AND subskala = '$subskalaEsc' AND is_active = 1";
        $result = mysqli_query($con, $sql);
        if (!$result || mysqli_num_rows($result) === 0) return null;

        $combined = null;
        while ($row = mysqli_fetch_assoc($result)) {
            $evidence = $this->buildEvidence($row['m_ho'], $row['m_oa'], $row['m_aca'], $row['m_theta']);
            if ($combined === null) {
                $combined = $evidence;
            } else {
                $combined = $this->normalizeMass($this->combineMass($combined, $evidence));
            }
        }

        if (empty($combined)) return null;

        arsort($combined);
        $topKey   = array_key_first($combined);
        $topMass  = $combined[$topKey];
        $topElems = explode(',', $topKey);

        if (count($topElems) === 1) {
            $levelCodeInt = (int)$topElems[0];
            $nilai        = $topMass;
        } else {
            $pig = $this->pignistic($combined);
            arsort($pig);
            $levelCodeInt = array_key_first($pig);
            $nilai        = $pig[$levelCodeInt];
        }

        $levelMap  = [1 => 'H', 2 => 'O', 3 => 'A', 4 => 'CA'];
        $levelKode = $levelMap[$levelCodeInt];

        $validLangs = ['id', 'en', 'tr', 'zh'];
        $lang    = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $validLangs)) ? $_SESSION['lang'] : 'id';
        $namaCol = 'nama_' . $lang;
        $kettCol = ($lang === 'id') ? 'kett' : 'kett_' . $lang;

        $sql = "SELECT $namaCol as nama, IF($kettCol IS NULL OR $kettCol='', kett, $kettCol) as kett
                FROM ds_tingkat WHERE subskala = '$subskalaEsc' AND level = '$levelKode'";
        $result = mysqli_query($con, $sql);
        $obj    = $result ? mysqli_fetch_object($result) : null;

        return [
            'level_kode' => $levelKode,
            'level_nama' => $obj ? $obj->nama : $levelKode,
            'nilai'      => $nilai,
            'persentase' => round($nilai * 100, 2) . '%',
            'kett'       => $obj ? $obj->kett : '',
        ];
    }
}
```

- [ ] **Step 2: Run the Task 5 test and confirm it passes**

Run:
```
"C:\xampp\php\php.exe" "C:\xampp\htdocs\ds3\tests\test_dempster_shafer.php"
```
Expected: every line says `PASS:` and the last line is `ALL TESTS PASSED`. If anything says `FAIL:`, fix
`combineMass`/`normalizeMass`/`pignistic`/`buildEvidence` before moving on — don't touch `hitungSubskala` yet.

---

### Task 7: Write a failing integration test for `hitungSubskala()`

**Files:**
- Create: `C:\xampp\htdocs\ds3\tests\test_hitung_subskala.php`

- [ ] **Step 1: Write the test script**

This test needs the seeded database from Tasks 1–4, so it looks up real gejala ids by `kode_gejala` first.

```php
<?php
session_start();
$_SESSION['lang'] = 'id';

require_once __DIR__ . '/../koneksi/koneksi.php';
require_once __DIR__ . '/../controller/c_Diagnosa.php';

$dg = new Diagnosa();
$failures = 0;

function fetchIdByKode($con, $kode) {
    $kode = mysqli_real_escape_string($con, $kode);
    $res  = mysqli_query($con, "SELECT id FROM ds_gejala WHERE kode_gejala = '$kode'");
    $row  = mysqli_fetch_assoc($res);
    return $row ? (int)$row['id'] : null;
}

// Test 1: three Depression gejala selected -> a plausible D result
$ids = array_filter([fetchIdByKode($con, 'G-D01'), fetchIdByKode($con, 'G-D02'), fetchIdByKode($con, 'G-D07')]);
$r = $dg->hitungSubskala('D', $ids);
if ($r === null) {
    echo "FAIL: hitungSubskala('D', 3 gejala) returned null\n";
    $failures++;
} else {
    $validLevels = ['H', 'O', 'A', 'CA'];
    if (in_array($r['level_kode'], $validLevels)) {
        echo "PASS: hitungSubskala('D', ...) returned a valid level ({$r['level_kode']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', ...) returned invalid level_kode {$r['level_kode']}\n";
        $failures++;
    }
    if ($r['nilai'] >= 0 && $r['nilai'] <= 1) {
        echo "PASS: hitungSubskala('D', ...) nilai is within [0,1] ({$r['nilai']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', ...) nilai out of range: {$r['nilai']}\n";
        $failures++;
    }
    if (!empty($r['kett'])) {
        echo "PASS: hitungSubskala('D', ...) returned non-empty kett\n";
    } else {
        echo "FAIL: hitungSubskala('D', ...) kett is empty\n";
        $failures++;
    }
}

// Test 2: empty gejala list -> null
$r2 = $dg->hitungSubskala('D', []);
if ($r2 === null) {
    echo "PASS: hitungSubskala('D', []) returns null\n";
} else {
    echo "FAIL: hitungSubskala('D', []) should return null, got " . print_r($r2, true) . "\n";
    $failures++;
}

// Test 3: gejala ids from the wrong subskala -> null (no matching rows)
$aIds = array_filter([fetchIdByKode($con, 'G-A01')]);
$r3 = $dg->hitungSubskala('D', $aIds);
if ($r3 === null) {
    echo "PASS: hitungSubskala('D', <Anxiety ids>) returns null\n";
} else {
    echo "FAIL: hitungSubskala('D', <Anxiety ids>) should return null, got " . print_r($r3, true) . "\n";
    $failures++;
}

echo "\n" . ($failures === 0 ? "ALL TESTS PASSED" : "$failures TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
```

- [ ] **Step 2: Run it and confirm the first assertions fail**

Run:
```
"C:\xampp\php\php.exe" "C:\xampp\htdocs\ds3\tests\test_hitung_subskala.php"
```
Expected: this should actually mostly PASS already, since `hitungSubskala()` was implemented in Task 6. If it
does, that's fine — it confirms Task 6's implementation is correct end-to-end against real seeded data. If any
line says `FAIL:`, fix `hitungSubskala()` in `controller/c_Diagnosa.php` before continuing.

---

### Task 8: Add `TampilBySubskala()` to `c_Gejala.php`

**Files:**
- Modify: `C:\xampp\htdocs\ds3\controller\c_Gejala.php:67-81` (the old `TampilSemuaWeb()` method)

- [ ] **Step 1: Replace `TampilSemuaWeb()` with `TampilBySubskala()`**

Find this block (lines 67-81):
```php
    function TampilSemuaWeb() {
        include "koneksi/koneksi.php";
        if (session_status() === PHP_SESSION_NONE) session_start();
        $valid = ['id', 'en', 'tr', 'zh'];
        $lang  = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $valid)) ? $_SESSION['lang'] : 'id';
        $col   = 'nama_' . $lang;
        $query = mysqli_query($con, "SELECT id, $col as nama FROM ds_gejala");
        $i = 0;
        while ($d = mysqli_fetch_array($query)) {
            $data[$i]['id']   = $d['id'];
            $data[$i]['nama'] = $d['nama'];
            $i++;
        }
        return $data;
    }
```

Replace with:
```php
    /** Gejala for one DASS-21 subscale, ordered by item number — used by the public diagnosa form. */
    function TampilBySubskala($subskala) {
        include "koneksi/koneksi.php";
        if (session_status() === PHP_SESSION_NONE) session_start();
        $valid = ['id', 'en', 'tr', 'zh'];
        $lang  = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $valid)) ? $_SESSION['lang'] : 'id';
        $col   = 'nama_' . $lang;
        $subskala = mysqli_real_escape_string($con, $subskala);
        $query = mysqli_query($con, "SELECT id, $col as nama FROM ds_gejala
                                      WHERE subskala = '$subskala' AND is_active = 1
                                      ORDER BY item_dass");
        $i = 0;
        while ($d = mysqli_fetch_array($query)) {
            $data[$i]['id']   = $d['id'];
            $data[$i]['nama'] = $d['nama'];
            $i++;
        }
        return $data;
    }
```

Leave `TampilSemua()`, `InsertGejala()`, `HapusGejala()`, `EditGejala()`, `TampilSatuData()`, `TampilAngka()`
untouched for now — they still reference the old flat `ds_gejala` shape (`kode`, `nama`) and will be rewritten
in the Phase 2 plan alongside the Admin CRUD pages that call them. They aren't called anywhere in Phase 1's
scope, so leaving them as-is doesn't break anything (see Task 16, which places a placeholder in front of the
Admin pages that would otherwise call them against the new table shape).

- [ ] **Step 2: Sanity-check with a quick CLI probe**

Run:
```
"C:\xampp\php\php.exe" -r "session_start(); $_SESSION['lang']='id'; chdir('C:/xampp/htdocs/ds3'); require 'controller/c_Gejala.php'; $g = new Gejala(); print_r($g->TampilBySubskala('D'));"
```
Expected: an array of 7 elements, each with `id` and `nama` (Indonesian symptom descriptions for the
Depression subscale).

---

### Task 9: Add new language keys

**Files:**
- Modify: `C:\xampp\htdocs\ds3\lang\id.php`
- Modify: `C:\xampp\htdocs\ds3\lang\en.php`
- Modify: `C:\xampp\htdocs\ds3\lang\tr.php`
- Modify: `C:\xampp\htdocs\ds3\lang\zh.php`

- [ ] **Step 1: Add these 4 keys to the end of the returned array in each file** (before the closing `];`)

`lang/id.php`:
```php
    'subskala_depresi' => 'Depresi',
    'subskala_anxiety' => 'Anxiety',
    'subskala_stres' => 'Stres',
    'tidak_ada_gejala_dipilih' => 'Tidak ada gejala dipilih di kategori ini',
```

`lang/en.php`:
```php
    'subskala_depresi' => 'Depression',
    'subskala_anxiety' => 'Anxiety',
    'subskala_stres' => 'Stress',
    'tidak_ada_gejala_dipilih' => 'No symptoms selected in this category',
```

`lang/tr.php`:
```php
    'subskala_depresi' => 'Depresyon',
    'subskala_anxiety' => 'Anksiyete',
    'subskala_stres' => 'Stres',
    'tidak_ada_gejala_dipilih' => 'Bu kategoride seçilmiş belirti yok',
```

`lang/zh.php`:
```php
    'subskala_depresi' => '抑郁',
    'subskala_anxiety' => '焦虑',
    'subskala_stres' => '压力',
    'tidak_ada_gejala_dipilih' => '此类别中未选择任何症状',
```

- [ ] **Step 2: Verify each file still parses**

Run (repeat for `en`, `tr`, `zh` by swapping the filename):
```
"C:\xampp\php\php.exe" -l "C:\xampp\htdocs\ds3\lang\id.php"
```
Expected: `No syntax errors detected in ...` for all 4 files.

---

### Task 10: Update `diagnosa.php` — group symptoms into 3 subscale sections

**Files:**
- Modify: `C:\xampp\htdocs\ds3\diagnosa.php:10-75`

- [ ] **Step 1: Replace the controller include and the symptom grid**

Find (lines 10-11):
```php
include "controller/c_Gejala.php";
$pt = new Gejala;
```
Leave this as-is — `TampilBySubskala()` is now a method on the same `Gejala` class from Task 8.

Find the symptom grid block (lines 52-75):
```php
        <?php
        $dotColors = ['#6C63FF','#FF6584','#43D9AD','#FFD166','#9B89FF','#FF8C42','#00C9A7','#F67280'];
        $data = $pt->TampilSemuaWeb();
        $ci = 0;
        ?>

        <div class="row g-3">
          <?php foreach ($data as $d) :
            $color = $dotColors[$ci % count($dotColors)];
            $ci++;
          ?>
          <div class="col-md-6 col-xl-4">
            <label class="symptom-label" for="gejala_<?php echo (int)$d['id']; ?>">
              <input type="checkbox"
                     name="gejala[]"
                     value="<?php echo (int)$d['id']; ?>"
                     id="gejala_<?php echo (int)$d['id']; ?>"
                     class="symptom-check">
              <span class="symptom-dot" style="background:<?php echo $color; ?>;"></span>
              <span class="symptom-text"><?php echo htmlspecialchars($d['nama']); ?></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
```

Replace with:
```php
        <?php
        $subskalaList = [
            'D' => ['color' => '#6C63FF', 'labelKey' => 'subskala_depresi', 'fallback' => 'Depresi'],
            'A' => ['color' => '#FF6584', 'labelKey' => 'subskala_anxiety', 'fallback' => 'Anxiety'],
            'S' => ['color' => '#43D9AD', 'labelKey' => 'subskala_stres',   'fallback' => 'Stres'],
        ];
        ?>

        <?php foreach ($subskalaList as $sk => $meta):
            $sectionData = $pt->TampilBySubskala($sk);
            $label = isset($_SESSION['langArray'][$meta['labelKey']])
                ? htmlspecialchars($_SESSION['langArray'][$meta['labelKey']])
                : $meta['fallback'];
        ?>
        <h5 class="fw-700 mt-4 mb-3" style="color:<?php echo $meta['color']; ?>;"><?php echo $label; ?></h5>
        <div class="row g-3 mb-2">
          <?php foreach ($sectionData as $d): ?>
          <div class="col-md-6 col-xl-4">
            <label class="symptom-label" for="gejala_<?php echo (int)$d['id']; ?>">
              <input type="checkbox"
                     name="gejala[]"
                     value="<?php echo (int)$d['id']; ?>"
                     id="gejala_<?php echo (int)$d['id']; ?>"
                     class="symptom-check">
              <span class="symptom-dot" style="background:<?php echo $meta['color']; ?>;"></span>
              <span class="symptom-text"><?php echo htmlspecialchars($d['nama']); ?></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
```

The JS min-2-symptoms counter at the bottom of the file (`.symptom-check` selectors) needs no changes — it
selects by class, which every checkbox in every section still has.

- [ ] **Step 2: Manual check**

Open `http://localhost/ds3/diagnosa.php` in a browser. Expected: 3 headed sections (Depresi/Anxiety/Stres),
7 checkboxes each, 21 total. Selecting checkboxes still updates the counter at the bottom.

---

### Task 11: Add CSS for level-based result colors

**Files:**
- Modify: `C:\xampp\htdocs\ds3\assets\css\modern.css`

- [ ] **Step 1: Add these rules after the existing `.confidence-fill` rule (around line 379)**

```css
/* ── DASS-21 severity level colors ─────────────── */
.result-card.level-h {
  background: linear-gradient(135deg, rgba(67,217,173,.15), rgba(67,217,173,.08));
  border: 2px solid rgba(67,217,173,.5);
}
.result-card.level-o {
  background: linear-gradient(135deg, rgba(255,209,102,.18), rgba(255,209,102,.08));
  border: 2px solid rgba(255,209,102,.55);
}
.result-card.level-a {
  background: linear-gradient(135deg, rgba(255,140,66,.15), rgba(255,140,66,.08));
  border: 2px solid rgba(255,140,66,.5);
}
.result-card.level-ca {
  background: linear-gradient(135deg, rgba(255,101,132,.15), rgba(255,101,132,.08));
  border: 2px solid rgba(255,101,132,.5);
}
.result-card.level-none {
  background: #F5F4FF;
  border: 2px dashed #D9D6F5;
}
.result-card.level-h h3    { color: #2FAE8C; }
.result-card.level-o h3    { color: #C99A1D; }
.result-card.level-a h3    { color: #E06A1D; }
.result-card.level-ca h3   { color: #E0355A; }
.confidence-fill.level-h   { background: linear-gradient(90deg, #43D9AD, #2FAE8C); }
.confidence-fill.level-o   { background: linear-gradient(90deg, #FFD166, #E0B23A); }
.confidence-fill.level-a   { background: linear-gradient(90deg, #FF8C42, #E0691D); }
.confidence-fill.level-ca  { background: linear-gradient(90deg, #FF6584, #E0355A); }
```

- [ ] **Step 2: Confirm the file still loads without errors**

Open `http://localhost/ds3/diagnosa.php` and check the browser dev console — no CSS parse errors, page still
styled as before (these are additive rules, nothing existing is removed).

---

### Task 12: Rewrite `hasil.php` — processing block

**Files:**
- Modify: `C:\xampp\htdocs\ds3\hasil.php:1-129`

- [ ] **Step 1: Replace lines 1–129**

```php
<?php
include 'controller/c_Riwayat.php';
$cl = new Riwayat;
$cl->Count();

session_start();
include('function.php');
loadLanguage();

include "controller/c_Diagnosa.php";
$dg = new Diagnosa;
include "koneksi/koneksi.php";

// ── Computation variables ─────────────────────────────────────
$hasDiagnosis     = false;
$errorMinGejala   = false;
$results          = ['D' => null, 'A' => null, 'S' => null];
$selectedSymptoms = [];

$subskalaLabelKey = ['D' => 'subskala_depresi', 'A' => 'subskala_anxiety', 'S' => 'subskala_stres'];
$subskalaFallback = ['D' => 'Depresi', 'A' => 'Anxiety', 'S' => 'Stres'];

if (isset($_POST['gejala'])) {
    if (count($_POST['gejala']) < 2) {
        $errorMinGejala = true;
    } else {
        $hasDiagnosis = true;

        $inList = implode(',', array_map('intval', $_POST['gejala']));

        // Group the selected gejala ids by subskala
        $sql    = "SELECT id, subskala FROM ds_gejala WHERE id IN ($inList) AND is_active = 1";
        $result = mysqli_query($con, $sql);
        $bySubskala = ['D' => [], 'A' => [], 'S' => []];
        while ($row = mysqli_fetch_assoc($result)) {
            $bySubskala[$row['subskala']][] = (int)$row['id'];
        }

        foreach (['D', 'A', 'S'] as $sk) {
            $results[$sk] = $dg->hitungSubskala($sk, $bySubskala[$sk]);
        }

        // Language column for symptom names
        $_validLangs = ['id', 'en', 'tr', 'zh'];
        $_lang       = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $_validLangs)) ? $_SESSION['lang'] : 'id';
        $_namaCol    = 'nama_' . $_lang;

        // Selected symptoms list (for display + history text)
        $gejalaDbStr = '';
        $i = 0;
        foreach ($_POST['gejala'] as $item) {
            $query   = "SELECT $_namaCol as nama FROM ds_gejala WHERE id = " . (int)$item;
            $result  = mysqli_query($con, $query);
            $obj     = mysqli_fetch_object($result);
            $i++;
            $namaGejala         = $obj ? $obj->nama : '';
            $selectedSymptoms[] = $namaGejala;
            $gejalaDbStr       .= $i . '. ' . $namaGejala . '<br>';
        }

        // Persist header row — keeps legacy diagnosa.penyakit/persentase columns
        // populated with a readable summary so pages that still read them directly
        // (e.g. an un-migrated riwayat view) show something sensible.
        $tanggal       = date('d-m-Y') . '<br>' . date('h:i:s A');
        $ringkasanNama = [];
        $ringkasanPct  = [];
        foreach (['D', 'A', 'S'] as $sk) {
            if ($results[$sk]) {
                $ringkasanNama[] = $subskalaFallback[$sk] . ': ' . $results[$sk]['level_nama'];
                $ringkasanPct[]  = $subskalaFallback[$sk] . ': ' . $results[$sk]['persentase'];
            }
        }
        $penyakitStr   = implode(' | ', $ringkasanNama);
        $persentaseStr = implode(' | ', $ringkasanPct);
        $nilaiStr      = $results['D']['nilai'] ?? ($results['A']['nilai'] ?? ($results['S']['nilai'] ?? 0));

        mysqli_query($con,
            "INSERT INTO diagnosa (tanggal, gejala, penyakit, nilai, persentase)
             VALUES ('$tanggal', '$gejalaDbStr', '" . mysqli_real_escape_string($con, $penyakitStr) . "',
                     '$nilaiStr', '" . mysqli_real_escape_string($con, $persentaseStr) . "')"
        );
        $idDiagnosa = mysqli_insert_id($con);

        // Persist per-subscale detail rows
        foreach (['D', 'A', 'S'] as $sk) {
            if (!$results[$sk]) continue;
            $r = $results[$sk];
            $nilaiEsc = (float)$r['nilai'];
            mysqli_query($con,
                "INSERT INTO diagnosa_detail (id_diagnosa, sumber, subskala, level_kode, level_nama, nilai, persentase)
                 VALUES ($idDiagnosa, 'diagnosa', '$sk', '{$r['level_kode']}',
                         '" . mysqli_real_escape_string($con, $r['level_nama']) . "',
                         $nilaiEsc, '{$r['persentase']}')"
            );
        }
    }
}
?>
```

- [ ] **Step 2: Quick syntax check**

Run:
```
"C:\xampp\php\php.exe" -l "C:\xampp\htdocs\ds3\hasil.php"
```
Expected: `No syntax errors detected in ...`. (The HTML section below the PHP block, edited in Task 13, is
unaffected by this check since it's still valid at this point — untouched from the original file.)

---

### Task 13: Rewrite `hasil.php` — display block

**Files:**
- Modify: `C:\xampp\htdocs\ds3\hasil.php` (the `<!-- ── Result area` section, originally lines 159–292)

- [ ] **Step 1: Replace the `<?php elseif ($hasDiagnosis && !empty($codes)): ?> … <?php else: ?>` branch**

Find the whole block from `<?php elseif ($hasDiagnosis && !empty($codes)): ?>` down to (but not including) the
final `<?php else: ?>` / "No POST – direct access" branch, and replace it with:

```php
        <?php elseif ($hasDiagnosis): ?>

          <!-- ── 3 subscale result cards ───────────── -->
          <div class="row g-3 justify-content-center mb-2">
            <?php foreach (['D', 'A', 'S'] as $sk):
                $r = $results[$sk];
                $subskalaLabel = isset($_SESSION['langArray'][$subskalaLabelKey[$sk]])
                    ? htmlspecialchars($_SESSION['langArray'][$subskalaLabelKey[$sk]])
                    : $subskalaFallback[$sk];
            ?>
            <div class="col-12">
              <?php if ($r): ?>
              <div class="result-card level-<?php echo strtolower($r['level_kode']); ?>" style="padding:1.75rem;">
                <p class="mb-1" style="font-size:.95rem; color:#888;"><?php echo $subskalaLabel; ?></p>
                <h3 class="mb-1"><?php echo htmlspecialchars($r['level_nama']); ?></h3>
                <p class="mb-2" style="font-size:.85rem; color:#666;">
                  <?php echo isset($_SESSION['langArray']['dengan_derajat'])
                      ? htmlspecialchars($_SESSION['langArray']['dengan_derajat'])
                      : 'derajat kepercayaan'; ?>
                  <strong><?php echo $r['persentase']; ?></strong>
                </p>
                <div class="confidence-bar mb-2">
                  <div class="confidence-fill level-<?php echo strtolower($r['level_kode']); ?>"
                       data-w="<?php echo (float)str_replace('%', '', $r['persentase']); ?>"></div>
                </div>
                <?php if (!empty($r['kett'])): ?>
                <p class="text-muted-mod mb-0" style="font-size:.85rem; line-height:1.7; text-align:left;">
                  <?php echo nl2br(htmlspecialchars($r['kett'])); ?>
                </p>
                <?php endif; ?>
              </div>
              <?php else: ?>
              <div class="result-card level-none" style="padding:1.75rem;">
                <p class="mb-1" style="font-size:.95rem; color:#888;"><?php echo $subskalaLabel; ?></p>
                <p class="mb-0 text-muted-mod">
                  <?php echo isset($_SESSION['langArray']['tidak_ada_gejala_dipilih'])
                      ? htmlspecialchars($_SESSION['langArray']['tidak_ada_gejala_dipilih'])
                      : 'Tidak ada gejala dipilih di kategori ini'; ?>
                </p>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Selected symptoms list -->
          <div class="card-modern mb-4">
            <h5 class="fw-700 mb-3">
              <?php echo isset($_SESSION['langArray']['gejala_dipilih'])
                  ? htmlspecialchars($_SESSION['langArray']['gejala_dipilih'])
                  : 'Gejala yang Dipilih'; ?>
            </h5>
            <?php foreach ($selectedSymptoms as $idx => $s): ?>
            <div class="symptom-list-item">
              <div class="symptom-num"><?php echo $idx + 1; ?></div>
              <span><?php echo htmlspecialchars($s); ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Back button -->
          <div class="text-center">
            <a href="diagnosa.php" class="btn-primary-mod">
              ← <?php echo isset($_SESSION['langArray']['diagnosa'])
                  ? htmlspecialchars($_SESSION['langArray']['diagnosa'])
                  : 'Diagnosa Ulang'; ?>
            </a>
          </div>

        <?php else: ?>
```

Everything from that final `<?php else: ?>` (the "No POST – direct access" card) through the end of the file
stays exactly as it was — only the branch above it changes.

- [ ] **Step 2: Syntax check**

Run:
```
"C:\xampp\php\php.exe" -l "C:\xampp\htdocs\ds3\hasil.php"
```
Expected: `No syntax errors detected in ...`.

- [ ] **Step 3: Manual browser check**

1. Go to `http://localhost/ds3/diagnosa.php`.
2. Check 2 symptoms under Depresi and 1 under Anxiety (leave Stres empty), submit.
3. Expected on `hasil.php`: 3 cards — Depresi and Anxiety show a level name + percentage + colored card +
   recommendation text; Stres shows "Tidak ada gejala dipilih di kategori ini". The "Gejala yang Dipilih" list
   shows all 3 checked symptoms.
4. Repeat with all 21 boxes checked — all 3 cards should show a result.

---

### Task 14: Guard `dokter/diagnosa.php` against the schema change

**Files:**
- Modify: `C:\xampp\htdocs\ds3\dokter\diagnosa.php:118-134`

`dokter/hdiagnosa.php` (the form target) still queries `ds_aturan`/`ds_penyakit` directly, which no longer
exist under those names after Task 1. Rebuilding this dokter-facing flow on the new schema is Phase 2 scope
(it writes to `riwayat` keyed by `id_pasien`, a separate flow from the public `diagnosa` table). For Phase 1,
replace the symptom-picker form with a placeholder so the page degrades gracefully instead of throwing a SQL
error when submitted.

- [ ] **Step 1: Replace the form block**

Find (lines 118-134):
```php
                            <form method="post" action="hdiagnosa.php">
                                <input type="hidden" name="id_pasien" value="<?php print $_GET['id_pasien'] ?>">
                                
                                <h2>Silahkan pilih apa yang anda rasakan</h2><hr>
                                <?php
                                $data = $pt->TampilSemua();
                                foreach($data as $d){ ?>

                                    <label class="container"><?php print $d['nama'] ?>
                                    <input type="checkbox" name='gejala[]' value='<?php print $d['id'] ?>' >
                                    <span class="checkmark"></span>
                                </label>

                            <?php } ?>
                            <br><hr>
                            <input type="submit" value="Diagnosa Penyakit" name="ok" class="btn btn-danger text-white">
                        </form>
```

Replace with:
```php
                            <div class="alert alert-info">
                                Fitur diagnosa di panel dokter sedang dalam migrasi ke metode DASS-21 dan akan
                                tersedia kembali pada update berikutnya. Untuk saat ini, silakan gunakan alur
                                diagnosa di halaman publik (<a href="../diagnosa.php">/diagnosa.php</a>).
                            </div>
```

- [ ] **Step 2: Manual check**

Log in as `pakar`/`pakar`, navigate to a patient's diagnosa page in the dokter panel. Expected: the info
message shows instead of a symptom checklist, and nothing errors.

---

### Task 15: Guard the Admin gejala/penyakit/basisp pages against the schema change

**Files:**
- Modify: `C:\xampp\htdocs\ds3\Admin\gejala.php`
- Modify: `C:\xampp\htdocs\ds3\Admin\penyakit.php`
- Modify: `C:\xampp\htdocs\ds3\Admin\basisp.php`

These 3 pages (and their tambah/edit sub-pages, reached only by links from these list pages) still call
`Gejala::TampilSemua()` / `Penyakit::TampilSemua()` and query `ds_aturan` directly — shapes that no longer
match the new tables. Rebuilding them for `ds_gejala`/`ds_tingkat` CRUD is Phase 2 scope. For Phase 1, replace
each page's body with a placeholder so admins get a clear message instead of a broken/blank page.

- [ ] **Step 1: Read the top of each file to find its `include '_header.php'` / `include '_footer.php'` wrapper**

Run:
```
"C:\xampp\php\php.exe" -r "echo file_get_contents('C:/xampp/htdocs/ds3/Admin/gejala.php');" | find /N "_header.php"
```
(Or open the file — you're looking for the line right after `include '_header.php';` where the page content
starts, and the line right before `include '_footer.php';` where it ends.)

- [ ] **Step 2: Replace each page's body**

For `Admin/gejala.php`, `Admin/penyakit.php`, and `Admin/basisp.php`, keep the `include '_header.php';` line
at the top and `include '_footer.php';` line at the bottom exactly as they are (these render the sidebar/topbar
shell), and replace everything between them with:

```php
<div class="container-fluid">
  <div class="alert alert-info mt-3">
    <strong>Sedang dalam migrasi ke DASS-21.</strong><br>
    Halaman ini akan tersedia kembali dengan struktur data baru pada update berikutnya.
  </div>
</div>
```

- [ ] **Step 3: Manual check**

Log in as `admin`/`admin`, click "Penyakit", "Gejala Penyakit", and "Basis Pengetahuan" in the sidebar.
Expected: each shows the info message inside the normal admin shell (sidebar/topbar still render), no PHP
errors or blank pages.

---

### Task 16: Full end-to-end manual verification

- [ ] **Step 1: Run both automated test scripts one more time**

```
"C:\xampp\php\php.exe" "C:\xampp\htdocs\ds3\tests\test_dempster_shafer.php"
"C:\xampp\php\php.exe" "C:\xampp\htdocs\ds3\tests\test_hitung_subskala.php"
```
Expected: both print `ALL TESTS PASSED`.

- [ ] **Step 2: Full patient journey in the browser, once per language**

For each of `id`, `en`, `tr`, `zh` (switch via the navbar language dropdown):
1. Go to `diagnosa.php`. Confirm 3 section headers and 21 checkboxes are all translated.
2. Select at least 2 symptoms spread across all 3 subscales, submit.
3. On `hasil.php`, confirm all 3 result cards show a level name, percentage, colored card, and recommendation
   text in the selected language, and the "Gejala yang Dipilih" list shows translated symptom names.

- [ ] **Step 3: Edge cases**

1. Submit with exactly 2 symptoms both in the same subscale (e.g. 2 Depresi, 0 Anxiety, 0 Stres) — confirm
   Anxiety and Stres show the "no symptoms selected" placeholder card, not an error.
2. Submit with only 1 symptom checked — confirm the "minimal 2 gejala" warning still shows (this logic is
   unchanged from before).
3. Go directly to `hasil.php` without submitting the form — confirm the "Belum ada diagnosa" placeholder still
   shows (unchanged branch).

- [ ] **Step 4: Confirm the database recorded the session correctly**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root -e "USE spdempstershafer; SELECT * FROM diagnosa ORDER BY id_diagnosa DESC LIMIT 1;"
"C:\xampp\mysql\bin\mysql.exe" -u root -e "USE spdempstershafer; SELECT * FROM diagnosa_detail WHERE id_diagnosa = (SELECT MAX(id_diagnosa) FROM diagnosa);"
```
Expected: the `diagnosa` row's `penyakit`/`persentase` columns show a readable "Depresi: ... | Anxiety: ... |
Stres: ..." summary, and `diagnosa_detail` has one row per subscale that had symptoms selected.

---

### Task 17: Sync to the git repo and commit

**Files:**
- Target directory: `C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3\`

- [ ] **Step 1: Mirror the htdocs copy into the git repo (excluding `.git`)**

Run (PowerShell):
```powershell
$src = "C:\xampp\htdocs\ds3"
$dst = "C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3"
robocopy $src $dst /E /XD ".git" /XF "*.git*" /NFL /NDL /NP
```
Expected exit code 1 or 3 (robocopy's success codes — 1 or 3 both mean files were copied without failure;
only 8+ indicates an actual error).

- [ ] **Step 2: Review the diff**

```bash
cd "C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3"
git status
git diff --stat
```
Expected: modified `controller/c_Diagnosa.php`, `controller/c_Gejala.php`, `diagnosa.php`, `hasil.php`,
`assets/css/modern.css`, `lang/id.php`, `lang/en.php`, `lang/tr.php`, `lang/zh.php`,
`Admin/gejala.php`, `Admin/penyakit.php`, `Admin/basisp.php`, `dokter/diagnosa.php`; new `migrations/` and
`tests/` directories.

- [ ] **Step 3: Commit**

```bash
git add -A -- ':!docs/superpowers'
git add controller/c_Diagnosa.php controller/c_Gejala.php diagnosa.php hasil.php assets/css/modern.css \
        lang/id.php lang/en.php lang/tr.php lang/zh.php \
        Admin/gejala.php Admin/penyakit.php Admin/basisp.php dokter/diagnosa.php \
        migrations tests
git commit -m "feat: migrate diagnosis engine to DASS-21 (Phase 1 — core + public flow)

- New ds_gejala (21 items) and ds_tingkat (12 severity levels) tables,
  old ds_gejala/ds_penyakit/ds_aturan renamed to *_old for backup
- Generalized Dempster-Shafer combination in c_Diagnosa.php (N-ary mass
  functions, pignistic transform for multi-element decisions)
- diagnosa.php / hasil.php now run 3 independent subscale diagnoses
  (Depression/Anxiety/Stress) per session
- Admin gejala/penyakit/basisp CRUD and dokter-panel diagnosis show a
  migration placeholder until Phase 2 rebuilds them on the new schema

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

- [ ] **Step 4: Verify**

```bash
git log --oneline -3
git status
```
Expected: the new commit on top, working tree clean except for anything intentionally left out (e.g. any
stray files unrelated to this change).

---

## Out of scope for this plan (Phase 2)

- Admin CRUD for `ds_gejala` (add/edit/delete DASS-21 items with mass-value validation) and `ds_tingkat`
  (edit level names/recommendations).
- Removing the `ds_aturan`-based basis-pengetahuan admin page from the sidebar nav entirely (it currently
  still links to the Task 15 placeholder).
- Rebuilding `dokter/diagnosa.php` + `dokter/hdiagnosa.php` on the new schema (currently a placeholder).
- Any update to `Admin/riwayatd.php` or other history views to show the 3 per-subscale results from
  `diagnosa_detail` instead of the flattened summary string.
