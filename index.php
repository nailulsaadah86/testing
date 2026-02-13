<?php
// ==================== PHP CODE AWAL ====================
session_start();
require_once 'config/database.php';

// Ambil semua data pegawai dari database
$sql = "SELECT * FROM pegawai WHERE status = 'aktif' ORDER BY nama ASC";
$pegawai_list = query($sql);

// Hitung total pegawai
$total_pegawai = count($pegawai_list);

// Format tanggal Indonesia
function tanggalIndo($date) {
    $hari = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu');
    $bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    
    $day = date('w', strtotime($date));
    $tgl = date('d', strtotime($date));
    $bln = $bulan[date('n', strtotime($date))];
    $thn = date('Y', strtotime($date));
    
    return $hari[$day] . ', ' . $tgl . ' ' . $bln . ' ' . $thn;
}

$tanggal_sekarang = date('Y-m-d');
$tanggal_display = tanggalIndo($tanggal_sekarang);

// Cek jika form disubmit
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_laporan'])) {
        // Validasi dan sanitasi input
        $id_pegawai = isset($_POST['id_pegawai']) ? (int)$_POST['id_pegawai'] : 0;
        $tanggal = isset($_POST['tanggal']) ? date('Y-m-d', strtotime($_POST['tanggal'])) : date('Y-m-d');
        $uraian = isset($_POST['uraian_tugas']) ? escape($_POST['uraian_tugas']) : '';
        $output = isset($_POST['hasil_output']) ? escape($_POST['hasil_output']) : '';
        $jenis = isset($_POST['jenis_penugasan']) ? escape($_POST['jenis_penugasan']) : 'WFO';
        
        if ($id_pegawai > 0 && !empty($uraian) && !empty($output)) {
            $sql_insert = "INSERT INTO laporan_kinerja (id_pegawai, tanggal, uraian_tugas, hasil_output, jenis_penugasan) 
                           VALUES ('$id_pegawai', '$tanggal', '$uraian', '$output', '$jenis')";
            
            if (execute($sql_insert)) {
                $success_message = 'Laporan berhasil disimpan!';
                // Reset form setelah berhasil
                echo '<script>document.getElementById("reportForm").reset();</script>';
            } else {
                $error_message = 'Gagal menyimpan laporan. Silakan coba lagi.';
            }
        } else {
            $error_message = 'Harap lengkapi semua data yang diperlukan!';
        }
    }
    
    // Tambah pegawai baru via form
    if (isset($_POST['add_employee'])) {
        $nama = isset($_POST['nama']) ? strtoupper(escape($_POST['nama'])) : '';
        $nip = isset($_POST['nip']) ? escape($_POST['nip']) : '';
        $jabatan = isset($_POST['jabatan']) ? escape($_POST['jabatan']) : 'Staf Pelaksana';
        $bagian = isset($_POST['bagian']) ? escape($_POST['bagian']) : '';
        
        if (!empty($nama) && !empty($jabatan)) {
            $sql_tambah = "INSERT INTO pegawai (nama, nip, jabatan, bagian, status) 
                           VALUES ('$nama', '$nip', '$jabatan', '$bagian', 'aktif')";
            
            if (execute($sql_tambah)) {
                $success_message = 'Pegawai baru berhasil ditambahkan!';
                // Redirect untuk refresh data
                echo '<meta http-equiv="refresh" content="2">';
            } else {
                $error_message = 'Gagal menambahkan pegawai baru.';
            }
        }
    }
}
// ==================== END PHP CODE ====================
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Laporan Kinerja - KPU Kota Pekalongan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* CSS tambahan untuk PHP */
        .alert-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #b1dfbb;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #f1b0b7;
        }
        
        .pegawai-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
            display: none;
        }
        
        .info-box {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #0055a5;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: 600;
            color: #000;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Add employee section */
        .add-employee-section {
            display: none;
            background: #f8f9fa;
            border: 2px dashed #0055a5;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            animation: slideDown 0.3s ease;
        }
        
        .add-employee-section.active {
            display: block;
        }
        
        @keyframes slideDown {
            from { opacity: 0; height: 0; }
            to { opacity: 1; height: auto; }
        }
        
        .add-employee-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0055a5;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .toggle-add-employee {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            padding: 10px 15px;
            background: #0055a5;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .toggle-add-employee:hover {
            background: #003d82;
            transform: translateY(-2px);
        }
        
        .add-employee-btn-add {
            padding: 12px 25px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            margin-top: 15px;
        }
        
        .add-employee-btn-add:hover {
            background: #218838;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="animated-bg">
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
    </div>
    
    <!-- HALAMAN UTAMA -->
    <div class="page-main" id="pageMain">
        <div class="container">
            <header>
                <div class="kpu-header">
                    <div class="kpu-logo-container">
                        <div class="kpu-logo-wrapper">
                            <div class="kpu-logo">
                                <img src="https://mediacenter.batam.go.id/wp-content/uploads/sites/60/2019/03/KPU.jpg" 
                                     alt="Logo KPU Kota Pekalongan" 
                                     class="logo-image"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 200 200\"%3E%3Cdefs%3E%3ClinearGradient id=\"grad\" x1=\"0%25\" y1=\"0%25\" x2=\"100%25\" y2=\"100%25\"%3E%3Cstop offset=\"0%25\" style=\"stop-color:%23d52b1e;stop-opacity:1\"/%3E%3Cstop offset=\"100%25\" style=\"stop-color:%230055a5;stop-opacity:1\"/%3E%3C/linearGradient%3E%3C/defs%3E%3Ccircle cx=\"100\" cy=\"100\" r=\"95\" fill=\"url(%23grad)\" stroke=\"white\" stroke-width=\"5\"/%3E%3Ccircle cx=\"100\" cy=\"100\" r=\"75\" fill=\"white\"/%3E%3Ctext x=\"100\" y=\"85\" text-anchor=\"middle\" font-family=\"Arial, sans-serif\" font-size=\"22\" font-weight=\"bold\" fill=\"%23d52b1e\"%3EKOMISI%3C/text%3E%3Ctext x=\"100\" y=\"110\" text-anchor=\"middle\" font-family=\"Arial, sans-serif\" font-size=\"18\" font-weight=\"bold\" fill=\"%230055a5\"%3EPEMILIHAN%3C/text%3E%3Ctext x=\"100\" y=\"135\" text-anchor=\"middle\" font-family=\"Arial, sans-serif\" font-size=\"18\" font-weight=\"bold\" fill=\"%230055a5\"%3EUMUM%3C/text%3E%3C/svg%3E';">
                            </div>
                            <div style="font-size: 0.8rem; color: #0055a5; font-weight: 600;">
                                KPU KOTA PEKALONGAN
                            </div>
                        </div>
                        <div class="kpu-title">
                            <h2>KOMISI PEMILIHAN UMUM</h2>
                            <h1>KOTA <span style="color: #d52b1e;">PEKALONGAN</span></h1>
                            <div class="kpu-subtitle">SISTEM LAPORAN KINERJA HARIAN</div>
                            <div class="kpu-motto">
                                <i class="fas fa-quote-left"></i>
                                "Bersih, Transparan, dan Akuntabel"
                                <i class="fas fa-quote-right"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="date-display">
                        <i class="far fa-calendar-alt"></i> 
                        <span id="current-date"><?php echo $tanggal_display; ?></span>
                    </div>
                </div>
            </header>
            
            <!-- MODIFIKASI: Hanya menyisakan tombol REKAPITULASI saja -->
            <div class="recap-btn-container no-print">
                <a href="rekap.php" class="recap-btn" id="openRecapBtn">
                    <i class="fas fa-chart-bar"></i> REKAPITULASI LAPORAN KPU PEKALONGAN
                </a>
            </div>
            
            <main class="app-container">
                <section class="form-section">
                    <h2 class="section-title"><i class="fas fa-file-signature"></i> FORMULIR LAPORAN KINERJA KPU KOTA PEKALONGAN</h2>
                    
                    <?php if ($success_message): ?>
                    <div class="alert-message alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                    <div class="alert-message alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form id="reportForm" method="POST" action="">
                        <div class="form-group">
                            <label for="reportDate"><i class="far fa-calendar-check"></i> Tanggal Laporan</label>
                            <input type="date" id="reportDate" name="tanggal" value="<?php echo $tanggal_sekarang; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="employeeNameSelect"><i class="fas fa-user-tie"></i> Nama Petugas / Pegawai</label>
                            <select id="employeeNameSelect" name="id_pegawai" required class="employee-select">
                                <option value="">Pilih Nama Pegawai</option>
                                <?php foreach ($pegawai_list as $pegawai): ?>
                                <option value="<?php echo $pegawai['id']; ?>" 
                                        data-nip="<?php echo htmlspecialchars($pegawai['nip'] ?? ''); ?>"
                                        data-jabatan="<?php echo htmlspecialchars($pegawai['jabatan']); ?>"
                                        data-bagian="<?php echo htmlspecialchars($pegawai['bagian'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($pegawai['nama']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="pegawai-info" id="employeeInfoFields">
                                <div class="info-box">
                                    <div class="info-label">NIP:</div>
                                    <div class="info-value" id="employeeNipDisplay">-</div>
                                </div>
                                <div class="info-box">
                                    <div class="info-label">Jabatan:</div>
                                    <div class="info-value" id="employeePositionDisplay">-</div>
                                </div>
                            </div>
                            
                            <div class="toggle-add-employee no-print" id="toggleAddEmployee">
                                <i class="fas fa-plus-circle"></i>
                                <span>Tambah Nama Pegawai Baru</span>
                            </div>
                            
                            <!-- Form tambah pegawai -->
                            <div class="add-employee-section no-print" id="addEmployeeSection">
                                <div class="add-employee-title">
                                    <i class="fas fa-user-plus"></i> TAMBAH PEGAWAI BARU
                                </div>
                                <div class="add-employee-fields">
                                    <div class="form-group">
                                        <label for="newEmployeeName">Nama Lengkap <span style="color: red;">*</span></label>
                                        <input type="text" id="newEmployeeName" name="nama" placeholder="Masukkan nama lengkap" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="newEmployeeNip">NIP (Opsional)</label>
                                        <input type="text" id="newEmployeeNip" name="nip" placeholder="Masukkan NIP">
                                    </div>
                                    <div class="form-group">
                                        <label for="newEmployeePosition">Jabatan <span style="color: red;">*</span></label>
                                        <input type="text" id="newEmployeePosition" name="jabatan" placeholder="Masukkan jabatan" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="newEmployeeBagian">Bagian (Opsional)</label>
                                        <input type="text" id="newEmployeeBagian" name="bagian" placeholder="Masukkan bagian">
                                    </div>
                                </div>
                                <button type="submit" name="add_employee" class="add-employee-btn-add no-print">
                                    <i class="fas fa-save"></i> Simpan dan Pilih Pegawai Baru
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="workDescription"><i class="fas fa-tasks"></i> Uraian Tugas / Pekerjaan <span style="color: red;">*</span></label>
                            <textarea id="workDescription" name="uraian_tugas" placeholder="Jelaskan detail tugas/pekerjaan yang telah dilaksanakan hari ini..." required rows="4"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="workOutput"><i class="fas fa-file-contract"></i> Hasil / Output Pekerjaan <span style="color: red;">*</span></label>
                            <textarea id="workOutput" name="hasil_output" placeholder="Hasil atau capaian dari tugas/pekerjaan yang telah dilaksanakan..." required rows="4"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Lokasi / Jenis Penugasan</label>
                            <div class="work-type-container">
                                <div class="work-type-option">
                                    <input type="radio" id="wfo" name="jenis_penugasan" value="WFO" checked>
                                    <label for="wfo" class="work-type-card wfo-card">
                                        <i class="fas fa-landmark"></i>
                                        <span>DI KANTOR KPU</span>
                                        <small>Kantor KPU Kota Pekalongan</small>
                                    </label>
                                </div>
                                <div class="work-type-option">
                                    <input type="radio" id="wfh" name="jenis_penugasan" value="WFH">
                                    <label for="wfh" class="work-type-card wfh-card">
                                        <i class="fas fa-home"></i>
                                        <span>DI RUMAH</span>
                                        <small>(Work From Home)</small>
                                    </label>
                                </div>
                                <div class="work-type-option">
                                    <input type="radio" id="dl" name="jenis_penugasan" value="DL">
                                    <label for="dl" class="work-type-card dl-card">
                                        <i class="fas fa-map-marked-alt"></i>
                                        <span>DINAS LUAR</span>
                                        <small>(Lapangan / Luar Kantor)</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="submit-btn-container no-print">
                            <button type="submit" name="submit_laporan" class="submit-btn">
                                <i class="fas fa-paper-plane"></i> KIRIM LAPORAN KPU PEKALONGAN
                            </button>
                        </div>
                    </form>
                </section>
            </main>
            
            <footer class="no-print">
                <div class="kpu-footer">
                    <div class="kpu-footer-logo">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div>
                        <p>© 2026 <span class="footer-highlight">KOMISI PEMILIHAN UMUM KOTA PEKALONGAN</span></p>
                        <p>Sistem Pelaporan Kinerja Harian - Terintegrasi dan Terpercaya</p>
                    </div>
                </div>
                
                <div class="contact-info">
                    <p><i class="fas fa-map-marker-alt"></i> Alamat: Jl. Dr. Wahidin No. 12, Pekalongan, Jawa Tengah</p>
                    <p><i class="fas fa-phone"></i> Telepon: (0285) 421234</p>
                    <p><i class="fas fa-envelope"></i> Email: kpu@pekalongankota.go.id</p>
                </div>
                
                <p>Laporan WFO • WFH • Dinas Luar | Responsif di Semua Perangkat</p>
            </footer>
        </div>
    </div>

    <script>
        // Fungsi untuk menampilkan info pegawai
        document.getElementById('employeeNameSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const infoFields = document.getElementById('employeeInfoFields');
            
            if (this.value) {
                infoFields.style.display = 'grid';
                document.getElementById('employeeNipDisplay').textContent = selectedOption.dataset.nip || "-";
                document.getElementById('employeePositionDisplay').textContent = selectedOption.dataset.jabatan || "-";
            } else {
                infoFields.style.display = 'none';
            }
        });
        
        // Toggle form tambah pegawai baru
        document.getElementById('toggleAddEmployee').addEventListener('click', function() {
            const addSection = document.getElementById('addEmployeeSection');
            
            if (addSection.classList.contains('active')) {
                addSection.classList.remove('active');
                this.innerHTML = '<i class="fas fa-plus-circle"></i><span>Tambah Nama Pegawai Baru</span>';
            } else {
                addSection.classList.add('active');
                this.innerHTML = '<i class="fas fa-minus-circle"></i><span>Sembunyikan Form</span>';
            }
        });
        
        // Update waktu real-time
        function updateWaktu() {
            const sekarang = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            
            const formatted = sekarang.toLocaleDateString('id-ID', options);
            document.getElementById('current-date').textContent = formatted;
        }
        
        // Update setiap detik
        updateWaktu();
        setInterval(updateWaktu, 1000);
        
        // Set default date di form
        document.getElementById('reportDate').value = '<?php echo $tanggal_sekarang; ?>';
        
        // Auto-focus tanggal hari ini jika kosong
        window.onload = function() {
            const reportDate = document.getElementById('reportDate');
            if (!reportDate.value) {
                const today = new Date().toISOString().split('T')[0];
                reportDate.value = today;
            }
        };
    </script>
</body>
</html>