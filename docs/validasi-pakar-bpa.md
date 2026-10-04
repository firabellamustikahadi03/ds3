# Lembar Validasi Pakar: Penilaian Tingkat Keparahan 21 Gejala DASS-21

Dokumen ini meminta pendapat pakar psikologi atas **satu keputusan penelitian**:
gejala DASS-21 mana yang menjadi penanda gangguan **ringan**, dan mana yang penanda
**berat**. Penilaian ini menjadi dasar nilai probabilitas (BPA) pada sistem pakar
berbasis Dempster-Shafer.

## 1. Latar belakang singkat

- Instrumen DASS-21 (Lovibond & Lovibond, 1995) tidak mendefinisikan bobot per item;
  semua item dijumlahkan. Pembobotan per gejala di sistem ini adalah **kontribusi
  peneliti**, sehingga perlu divalidasi pakar.
- Sistem memakai checkbox (gejala ada / tidak ada), bukan skala 0-3.
- Tiap gejala dikelompokkan ke satu dari empat kategori. Semakin berat kategorinya,
  semakin kuat gejala itu mendorong hasil ke tingkat keparahan yang lebih tinggi.

| Kategori | Arti klinis | Pengaruh terhadap hasil |
|---|---|---|
| Ringan | Umum terjadi, tidak spesifik | Lemah; baru berpengaruh bila banyak |
| Sedang | Bermakna, namun lazim | Sedang |
| Berat | Penanda klinis yang jelas | Kuat |
| Sangat Berat | Penanda risiko tinggi | Sangat kuat, satu gejala saja sudah berarti |

## 2. Penilaian yang diusulkan peneliti

**Dasar penilaian:** makna klinis kalimat tiap item, dengan rujukan kriteria DSM-5 dan
deskripsi faset tiap subskala DASS. Kolom paling kanan untuk diisi pakar.

> Peneliti belum memiliki penilaian pakar; seluruh kolom "Usulan" berasal dari kajian
> peneliti. Mohon cocokkan juga teks item dengan naskah DASS-21 resmi yang dipakai.

### Depresi

| Kode | Gejala (teks pada sistem) | Usulan | Alasan | Pakar: Setuju / Ubah ke | Catatan |
|---|---|---|---|---|---|
| D01 | Tidak dapat merasakan perasaan positif sama sekali | Berat | Anhedonia, kriteria inti depresi mayor | | |
| D02 | Merasa tidak ada hal yang dapat ditunggu | Sedang | Hilangnya antisipasi masa depan | | |
| D03 | Merasa hidup tidak berarti atau hampa | Berat | Devaluasi hidup, faset inti subskala | | |
| D04 | Sulit untuk bersemangat | Ringan | Inersia; juga muncul pada kelelahan biasa | | |
| D05 | Merasa tidak berharga sebagai manusia | Berat | Perendahan diri, kriteria depresi mayor | | |
| D06 | Putus asa, tidak ada yang membuat lebih baik | Sangat Berat | Keputusasaan, prediktor risiko bunuh diri | | |
| D07 | Hidup terasa tidak bernilai atau tanpa tujuan | Sangat Berat | Terdekat dengan ide bunuh diri pasif | | |

### Anxiety

| Kode | Gejala (teks pada sistem) | Usulan | Alasan | Pakar: Setuju / Ubah ke | Catatan |
|---|---|---|---|---|---|
| A01 | Mulut terasa kering | Ringan | Gejala otonom paling tidak spesifik | | |
| A02 | Kesulitan bernapas | Sedang | Gejala pernapasan pada serangan panik | | |
| A03 | Tangan gemetar | Ringan | Efek otot rangka, tidak spesifik | | |
| A04 | Khawatir akan panik dan mempermalukan diri | Sedang | Kecemasan antisipatif | | |
| A05 | Jantung berdebar tanpa sebab fisik | Sedang | Hiperarousal otonom | | |
| A06 | Takut tanpa alasan yang jelas | Berat | Kecemasan tanpa pemicu | | |
| A07 | Hampir panik atau akan pingsan | Sangat Berat | Episode panik yang sedang berlangsung | | |

### Stres

| Kode | Gejala (teks pada sistem) | Usulan | Alasan | Pakar: Setuju / Ubah ke | Catatan |
|---|---|---|---|---|---|
| S01 | Mudah marah terhadap hal sepele | Ringan | Iritabilitas ringan, sangat umum | | |
| S02 | Cenderung bereaksi berlebihan terhadap situasi | Sedang | Reaktivitas berlebih | | |
| S03 | Sulit untuk tenang atau rileks | Sedang | Kesulitan relaksasi, faset inti subskala | | |
| S04 | Merasa **sangat** mudah tersinggung atau sensitif | Berat | Penguat "sangat" | | |
| S05 | Merasa menghabiskan banyak energi karena kecemasan | Berat | Arousal saraf berkelanjutan | | |
| S06 | Tidak sabar ketika mengalami penundaan | Ringan | Sangat umum pada orang sehat | | |
| S07 | Mudah tersinggung atau iritabel secara umum | Sedang | Iritabilitas umum | | |

## 3. Pertanyaan khusus untuk pakar

1. Subskala **Stres tidak memiliki gejala "Sangat Berat"**. Apakah wajar, mengingat
   subskala ini mengukur ketegangan kronis dan tidak ada item sepadan ide bunuh diri?
2. **S04 dan S07** sama-sama tentang mudah tersinggung, dibedakan hanya oleh kata
   "sangat". Apakah pembedaan ini layak?
3. **D02** (tidak ada yang dinanti): lebih tepat Sedang atau Berat?
4. **A06** (takut tanpa alasan): lebih tepat Berat atau Sedang?
5. Apakah ada gejala yang sebaiknya dinaikkan atau diturunkan kategorinya?

## 4. Aturan penentuan hasil (mohon ditanggapi)

Sistem mewajibkan **minimal 2 gejala** dicentang (kebijakan antarmuka). Hasil per
subskala adalah tingkat **tertinggi** yang tingkat keyakinannya masih mencapai **50%**.
Contoh perilaku yang dihasilkan sistem:

| Subskala | Gejala dicentang | Hasil |
|---|---|---|
| Anxiety | 2 gejala ringan (A01 + A03) | Mild |
| Depresi | 2 gejala (D04 ringan + D02 sedang) | Moderate |
| Depresi | 3 gejala (ditambah D01 berat) | Severe |
| Depresi | 7 gejala (semua) | Extreme |

Pertanyaan: apakah ambang 50% dan pola eskalasi di atas masuk akal secara klinis?

## 5. Keterbatasan yang sudah diketahui peneliti

- Sistem membuang informasi intensitas (skala 0-3) karena memakai checkbox, sehingga
  cenderung melebihkan keparahan pada gejala ringan.
- Sistem tidak memiliki kategori "Normal" seperti DASS-21 resmi.
- Pada pengujian dengan 23 skenario terstruktur, kecocokan persis dengan klasifikasi
  resmi sebesar 22.6% bila jawaban "kadang" dianggap gejala ada, dan 35.1% bila hanya
  jawaban "sering" atau lebih yang dianggap ada.

---

Nama pakar: ............................................  Tanggal: ....................

Jabatan / institusi: .................................  Tanda tangan: ...............
