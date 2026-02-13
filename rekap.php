<?php
// File: rekap.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

// Data default untuk periode 30 hari terakhir
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Ambil parameter filter dari URL
$filter_start = isset($_GET['start_date']) ? $_GET['start_date'] : $start_date;
$filter_end = isset($_GET['end_date']) ? $_GET['end_date'] : $end_date;
$filter_pegawai = isset($_GET['pegawai']) ? $_GET['pegawai'] : '';
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';

// Validasi tanggal
if (!empty($filter_start) && !empty($filter_end) && strtotime($filter_start) > strtotime($filter_end)) {
    $temp = $filter_start;
    $filter_start = $filter_end;
    $filter_end = $temp;
}

// Cek apakah request untuk download Excel
$download_excel = isset($_GET['download_excel']) ? true : false;
// Cek apakah request untuk download PDF
$download_pdf = isset($_GET['download_pdf']) ? true : false;

// Format tanggal Indonesia
function tanggalIndo($date) {
    $bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    
    $tgl = date('d', strtotime($date));
    $bln = date('m', strtotime($date));
    $thn = date('Y', strtotime($date));
    
    return $tgl . ' ' . $bulan[$bln] . ' ' . $thn;
}

// Format tanggal pendek (dd/mm/yyyy)
function tanggalPendek($date) {
    return date('d/m/Y', strtotime($date));
}

// Fungsi untuk konversi jenis penugasan ke keterangan
function getKeteranganPenugasan($jenis) {
    $keterangan = [
        'WFO' => 'DI KANTOR',
        'WFH' => 'DI RUMAH',
        'DL' => 'DINAS LUAR'
    ];
    
    return isset($keterangan[$jenis]) ? $keterangan[$jenis] : $jenis;
}

// Query untuk mengambil data laporan
$sql = "SELECT l.*, p.nama, p.jabatan, p.nip, p.bagian 
        FROM laporan_kinerja l 
        LEFT JOIN pegawai p ON l.id_pegawai = p.id 
        WHERE 1=1";

// Tambahkan filter status aktif hanya jika kolom status ada
$sql .= " AND (p.status = 'aktif' OR p.status IS NULL)";

// Tambahkan filter jika ada
if (!empty($filter_start) && !empty($filter_end)) {
    $sql .= " AND l.tanggal BETWEEN '$filter_start' AND '$filter_end'";
}

if (!empty($filter_pegawai) && is_numeric($filter_pegawai)) {
    $sql .= " AND l.id_pegawai = '$filter_pegawai'";
}

if (!empty($filter_jenis)) {
    $sql .= " AND p.jabatan = '$filter_jenis'";
}

$sql .= " ORDER BY l.tanggal DESC, p.nama ASC";

// Jalankan query
$laporan_list = query($sql);
$total_laporan = count($laporan_list);

// Ambil data pegawai untuk dropdown filter
$pegawai_options = query("SELECT * FROM pegawai WHERE status = 'aktif' ORDER BY nama ASC");

// Ambil data jabatan unik untuk dropdown filter
$jabatan_options = query("SELECT DISTINCT jabatan FROM pegawai WHERE status = 'aktif' AND jabatan != '' ORDER BY jabatan ASC");

// Ambil informasi pegawai jika dipilih
$pegawai_info = null;
if ($filter_pegawai && is_numeric($filter_pegawai)) {
    $pegawai_info = querySingle("SELECT * FROM pegawai WHERE id = '$filter_pegawai'");
}

$start_display = tanggalIndo($filter_start);
$end_display = tanggalIndo($filter_end);

// ============================================================
// FUNGSI UNTUK MENDAPATKAN LOGO
// ============================================================
function getLogoBase64() {
    // Cari file logo di berbagai lokasi
    $logo_paths = [
        __DIR__ . '/assets/img/logo-kpu.png',
        __DIR__ . '/assets/img/logo-kpu.jpg',
        __DIR__ . '/assets/img/kpu-logo.png',
        __DIR__ . '/assets/logo-kpu.png',
        __DIR__ . '/assets/img/KPU.jpg',
        __DIR__ . '/temp_logo.jpg'
    ];
    
    $logo_file = '';
    foreach ($logo_paths as $path) {
        if (file_exists($path)) {
            $logo_file = $path;
            break;
        }
    }
    
    // Jika tidak ada, download dari URL
    if (empty($logo_file)) {
        $temp_logo = __DIR__ . '/temp_logo.jpg';
        $logo_url = 'https://upload.wikimedia.org/wikipedia/commons/6/69/Logo_of_the_General_Elections_Commission_of_Indonesia.png';
        
        // Coba download dengan file_get_contents
        $image_data = @file_get_contents($logo_url);
        if ($image_data) {
            file_put_contents($temp_logo, $image_data);
            $logo_file = $temp_logo;
        } else {
            // Alternatif URL
            $logo_url = 'https://www.kpu.go.id/images/logo-kpu.png';
            $image_data = @file_get_contents($logo_url);
            if ($image_data) {
                file_put_contents($temp_logo, $image_data);
                $logo_file = $temp_logo;
            }
        }
    }
    
    // Konversi ke base64
    if (!empty($logo_file) && file_exists($logo_file)) {
        $image_data = file_get_contents($logo_file);
        $base64 = base64_encode($image_data);
        $extension = strtolower(pathinfo($logo_file, PATHINFO_EXTENSION));
        $type = ($extension == 'png') ? 'png' : 'jpeg';
        return ['base64' => $base64, 'type' => $type];
    }
    
    return null;
}

// ============================================================
// PROSES DOWNLOAD PDF DENGAN TANDA TANGAN
// ============================================================
if ($download_pdf && $total_laporan > 0) {
    
    // Cek apakah folder tcpdf ada
    $tcpdf_paths = [
        __DIR__ . '/tcpdf/tcpdf.php',
        __DIR__ . '/TCPDF-main/tcpdf.php',
        __DIR__ . '/TCPDF/tcpdf.php',
        __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php'
    ];
    
    $tcpdf_found = false;
    foreach ($tcpdf_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $tcpdf_found = true;
            break;
        }
    }
    
    if (!$tcpdf_found) {
        header('Content-Type: text/html; charset=utf-8');
        echo "<h3 style='color: red;'>❌ TCPDF tidak ditemukan!</h3>";
        echo "<p>Silakan download TCPDF dari: <a href='https://github.com/tecnickcom/TCPDF' target='_blank'>GitHub TCPDF</a></p>";
        echo "<p><a href='?download_excel=1&" . http_build_query($_GET) . "'>Download Excel</a></p>";
        echo "<p><a href='rekap.php'>Kembali ke Rekapitulasi</a></p>";
        exit;
    }
    
    // EXTEND TCPDF untuk Header & Footer custom
    class PDF_Rekap extends TCPDF {
        
        public function Header() {
            $logo_file = __DIR__ . '/assets/img/logo-kpu.png';
            if (file_exists($logo_file)) {
                $this->Image($logo_file, 10, 10, 25, 25, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            } else {
                // Coba logo alternatif
                $temp_logo = __DIR__ . '/temp_logo.jpg';
                if (file_exists($temp_logo)) {
                    $this->Image($temp_logo, 10, 10, 25, 25, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
                }
            }
            
            $this->SetY(12);
            $this->SetFont('helvetica', 'B', 12);
            $this->SetX(40);
            $this->Cell(0, 6, 'KOMISI PEMILIHAN UMUM', 0, 1, 'L');
            
            $this->SetX(40);
            $this->SetFont('helvetica', 'B', 14);
            $this->Cell(0, 6, 'KOTA PEKALONGAN', 0, 1, 'L');
            
            $this->SetX(40);
            $this->SetFont('helvetica', 'B', 11);
            $this->Cell(0, 6, 'REKAPITULASI LAPORAN KINERJA HARIAN', 0, 1, 'L');
            
            $this->SetLineWidth(0.5);
            $this->SetDrawColor(0, 85, 165);
            $this->Line(10, 38, $this->getPageWidth()-10, 38);
            $this->Ln(12);
        }
        
        public function Footer() {
            $this->SetY(-20);
            $this->SetFont('helvetica', 'I', 8);
            $this->SetTextColor(100, 100, 100);
            $this->Line(10, $this->GetY(), $this->getPageWidth()-10, $this->GetY());
            $this->Ln(2);
            $this->Cell(0, 5, 'Dicetak pada: ' . date('d/m/Y H:i:s') . ' WIB', 0, 0, 'L');
            $this->Cell(0, 5, 'Halaman ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
        }
    }
    
    // Buat PDF baru
    $pdf = new PDF_Rekap('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('KPU Kota Pekalongan');
    $pdf->SetAuthor('Sistem Laporan Kinerja');
    $pdf->SetTitle('Rekap Laporan Kinerja');
    
    $pdf->SetMargins(10, 45, 10);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(20);
    $pdf->SetAutoPageBreak(TRUE, 25);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->AddPage();
    
    // Informasi Filter
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(35, 7, 'Periode', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, ': ' . $start_display . ' s/d ' . $end_display, 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(35, 7, 'Total Laporan', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, ': ' . $total_laporan . ' Data', 0, 1, 'L');
    
    if ($pegawai_info) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(35, 7, 'Nama Pegawai', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, ': ' . $pegawai_info['nama'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(35, 7, 'NIP', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, ': ' . ($pegawai_info['nip'] ?: '-'), 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(35, 7, 'Jabatan', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, ': ' . $pegawai_info['jabatan'], 0, 1, 'L');
    }
    
    $pdf->Ln(5);
    
    // Header Tabel
    $pdf->SetFillColor(0, 85, 165);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 8);
    
    $w = [8, 18, 30, 58, 58, 16];
    $header = ['No', 'Tanggal', 'NIP', 'Uraian Tugas', 'Hasil Output', 'Lokasi'];
    
    for ($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', 1);
    }
    $pdf->Ln();
    
    // Isi Tabel
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 7.5);
    
    $no = 1;
    foreach ($laporan_list as $laporan) {
        $tanggal = tanggalPendek($laporan['tanggal']);
        $lokasi_text = getKeteranganPenugasan($laporan['jenis_penugasan']);
        
        switch($laporan['jenis_penugasan']) {
            case 'WFO': $pdf->SetFillColor(212, 237, 218); break;
            case 'WFH': $pdf->SetFillColor(209, 236, 241); break;
            case 'DL': $pdf->SetFillColor(255, 243, 205); break;
            default: $pdf->SetFillColor(255, 255, 255);
        }
        
        $nip_display = !empty($laporan['nip']) ? $laporan['nip'] : '-';
        $uraian = (strlen($laporan['uraian_tugas']) > 120) ? substr($laporan['uraian_tugas'], 0, 117) . '...' : $laporan['uraian_tugas'];
        $hasil = (strlen($laporan['hasil_output']) > 120) ? substr($laporan['hasil_output'], 0, 117) . '...' : $laporan['hasil_output'];
        
        $pdf->Cell($w[0], 8, $no, 1, 0, 'C', 1);
        $pdf->Cell($w[1], 8, $tanggal, 1, 0, 'C', 1);
        $pdf->Cell($w[2], 8, $nip_display, 1, 0, 'L', 1);
        $pdf->Cell($w[3], 8, $uraian, 1, 0, 'L', 1);
        $pdf->Cell($w[4], 8, $hasil, 1, 0, 'L', 1);
        $pdf->Cell($w[5], 8, $lokasi_text, 1, 1, 'C', 1);
        
        $no++;
    }
    
    // ============================================================
    // BAGIAN TANDA TANGAN PDF - PEKALONGAN DI ATAS "YANG MEMBUAT LAPORAN"
    // ============================================================
    $pdf->Ln(15);
    
    // Hitung lebar halaman
    $page_width = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
    $half_width = $page_width / 2;
    
    // BARIS 1: Kiri "Mengetahui,", Kanan "Pekalongan, [tanggal]"
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell($half_width, 6, 'Mengetahui,', 0, 0, 'L');
    $pdf->Cell($half_width, 6, 'Pekalongan, ' . tanggalIndo(date('Y-m-d')), 0, 1, 'R');
    
    // BARIS 2: Kiri kosong, Kanan "Yang membuat laporan Ketua KPU"
    $pdf->Cell($half_width, 6, '', 0, 0, 'L');
    $pdf->Cell($half_width, 6, 'Yang membuat laporan Ketua KPU', 0, 1, 'R');
    
    // SPASI UNTUK TANDA TANGAN
    $pdf->Ln(15);
    
    // BARIS 3: Nama dengan underline
    $pdf->SetFont('helvetica', 'BU', 11);
    $pdf->Cell($half_width, 6, 'Istadi, SH', 0, 0, 'L');
    $pdf->Cell($half_width, 6, 'Fajar Randi Yogananda', 0, 1, 'R');
    
    // Kembalikan font normal
    $pdf->SetFont('helvetica', '', 9);
    
    // Output PDF
    if (ob_get_length()) ob_end_clean();
    $filename = 'Rekap_Laporan_KPU_' . date('Ymd_His') . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
}

// ============================================================
// PROSES DOWNLOAD EXCEL DENGAN LOGO DAN TANDA TANGAN
// ============================================================
if ($download_excel && $total_laporan > 0) {
    
    // Bersihkan output buffer
    if (ob_get_length()) ob_end_clean();
    
    // Set header untuk download Excel
    $filename = 'Rekap_Laporan_KPU_' . date('Ymd_His') . '.xls';
    
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Cache-Control: max-age=0");
    header("Pragma: public");
    header("Expires: 0");
    
    // Dapatkan logo
    $logo = getLogoBase64();
    
    // Mulai output Excel
    echo '<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Rekap Laporan Kinerja KPU Pekalongan</title>
        <style>
            body { 
                font-family: Arial, Helvetica, sans-serif; 
                padding: 20px;
                margin: 0;
            }
            
            /* Kop Surat */
            .kop {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 3px solid #0055a5;
            }
            
            .logo {
                width: 80px;
                height: 80px;
                margin-right: 20px;
            }
            
            .logo img {
                width: 80px;
                height: 80px;
                object-fit: contain;
            }
            
            .kop-text {
                flex: 1;
            }
            
            .kop-text h1 {
                font-size: 22px;
                color: #d52b1e;
                margin: 0 0 5px 0;
                padding: 0;
            }
            
            .kop-text h2 {
                font-size: 20px;
                color: #0055a5;
                margin: 0 0 5px 0;
                padding: 0;
            }
            
            .kop-text h3 {
                font-size: 16px;
                color: #333;
                margin: 5px 0 0 0;
                padding: 0;
            }
            
            /* Info Section */
            .info {
                background: #f8f9fa;
                padding: 15px;
                margin-bottom: 20px;
                border-left: 4px solid #0055a5;
            }
            
            .info table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .info td {
                padding: 5px;
                border: none;
                font-size: 12px;
            }
            
            .label {
                font-weight: bold;
                color: #0055a5;
                width: 120px;
            }
            
            /* Table */
            table {
                border-collapse: collapse;
                width: 100%;
                margin-top: 15px;
            }
            
            th {
                background: #0055a5;
                color: white;
                font-weight: bold;
                padding: 10px 5px;
                border: 1px solid #000;
                font-size: 11px;
                text-align: center;
            }
            
            td {
                border: 1px solid #999;
                padding: 8px 5px;
                font-size: 10px;
                vertical-align: top;
            }
            
            .text-center {
                text-align: center;
            }
            
            .text-right {
                text-align: right;
            }
            
            /* Badge */
            .badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            .badge-wfo {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            
            .badge-wfh {
                background: #d1ecf1;
                color: #0c5460;
                border: 1px solid #bee5eb;
            }
            
            .badge-dl {
                background: #fff3cd;
                color: #856404;
                border: 1px solid #ffeeba;
            }
            
            /* ============================================ */
            /* TANDA TANGAN EXCEL - PEKALONGAN DI ATAS "YANG MEMBUAT LAPORAN" */
            /* ============================================ */
            .signature-section {
                margin-top: 50px;
                width: 100%;
            }
            
            .signature-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .signature-table td {
                border: none;
                padding: 5px;
                vertical-align: top;
            }
            
            /* Kolom kiri 50% */
            .signature-left-col {
                width: 50%;
                text-align: left;
            }
            
            /* Kolom kanan 50% */
            .signature-right-col {
                width: 50%;
                text-align: right;
            }
            
            .signature-left {
                text-align: left;
                font-size: 12px;
                font-weight: bold;
            }
            
            .signature-right {
                text-align: right;
                font-size: 12px;
                font-weight: bold;
            }
            
            .signature-underline-left {
                text-align: left;
                font-size: 12px;
                font-weight: bold;
                text-decoration: underline;
            }
            
            .signature-underline-right {
                text-align: right;
                font-size: 12px;
                font-weight: bold;
                text-decoration: underline;
            }
            
            .signature-space {
                height: 60px;
            }
            
            /* Footer */
            .footer {
                margin-top: 30px;
                padding-top: 15px;
                border-top: 1px solid #ddd;
                font-size: 10px;
                color: #666;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <!-- KOP DENGAN LOGO -->
        <div class="kop">';
    
    // Tampilkan logo
    echo '<div class="logo">';
    if ($logo) {
        echo '<img src="data:image/' . $logo['type'] . ';base64,' . $logo['base64'] . '" alt="Logo KPU">';
    } else {
        // Logo fallback
        echo '<div style="width:80px;height:80px;background:linear-gradient(135deg,#d52b1e,#0055a5);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:12px;text-align:center;">KPU<br>KOTA<br>PEKALONGAN</div>';
    }
    echo '</div>';
    
    echo '<div class="kop-text">
                <h1>KOMISI PEMILIHAN UMUM</h1>
                <h2>KOTA PEKALONGAN</h2>
                <h3>REKAPITULASI LAPORAN KINERJA HARIAN</h3>
              </div>
        </div>
        
        <!-- INFORMASI FILTER -->
        <div class="info">
            <table>
                <tr>
                    <td class="label">Periode</td>
                    <td>: ' . $start_display . ' s/d ' . $end_display . '</td>
                </tr>
                <tr>
                    <td class="label">Total Laporan</td>
                    <td>: ' . $total_laporan . ' Data</td>
                </tr>';
    
    if ($pegawai_info) {
        echo '<tr>
                <td class="label">Nama Pegawai</td>
                <td>: ' . htmlspecialchars($pegawai_info['nama']) . '</td>
              </tr>
              <tr>
                <td class="label">NIP</td>
                <td>: ' . htmlspecialchars($pegawai_info['nip'] ?? '-') . '</td>
              </tr>
              <tr>
                <td class="label">Jabatan</td>
                <td>: ' . htmlspecialchars($pegawai_info['jabatan']) . '</td>
              </tr>';
    }
    
    echo '</table>
        </div>
        
        <!-- TABEL DATA -->
        <table>
            <thead>
                <tr>
                    <th width="5%">NO</th>
                    <th width="10%">TANGGAL</th>
                    <th width="12%">NIP</th>
                    <th width="30%">URAIAN TUGAS</th>
                    <th width="30%">HASIL OUTPUT</th>
                    <th width="13%">LOKASI</th>
                </tr>
            </thead>
            <tbody>';
    
    $no = 1;
    foreach ($laporan_list as $laporan) {
        $tanggal = tanggalPendek($laporan['tanggal']);
        $lokasi_text = getKeteranganPenugasan($laporan['jenis_penugasan']);
        $class = 'badge-' . strtolower($laporan['jenis_penugasan']);
        
        echo '<tr>';
        echo '<td class="text-center" style="font-weight:bold;">' . $no++ . '</td>';
        echo '<td class="text-center">' . $tanggal . '</td>';
        echo '<td>' . htmlspecialchars($laporan['nip'] ?? '-') . '</td>';
        echo '<td>' . nl2br(htmlspecialchars($laporan['uraian_tugas'])) . '</td>';
        echo '<td>' . nl2br(htmlspecialchars($laporan['hasil_output'])) . '</td>';
        echo '<td class="text-center">
                <span class="badge ' . $class . '">' . $lokasi_text . '</span>
              </td>';
        echo '</tr>';
    }
    
    echo '</tbody>
        </table>
        
        <!-- ============================================ -->
        <!-- BAGIAN TANDA TANGAN EXCEL - PEKALONGAN DI ATAS "YANG MEMBUAT LAPORAN" -->
        <!-- ============================================ -->
        <div class="signature-section">
            <table class="signature-table">
                <!-- BARIS 1: Mengetahui (KIRI) - Pekalongan (KANAN) -->
                <tr>
                    <td style="width: 50%; text-align: left; font-size: 12px; font-weight: bold; border: none;">Mengetahui,</td>
                    <td style="width: 50%; text-align: right; font-size: 12px; font-weight: bold; border: none;">Pekalongan, ' . tanggalIndo(date('Y-m-d')) . '</td>
                </tr>
                <!-- BARIS 2: Kosong (KIRI) - Yang membuat laporan Ketua KPU (KANAN) -->
                <tr>
                    <td style="width: 50%; text-align: left; border: none;">&nbsp;</td>
                    <td style="width: 50%; text-align: right; font-size: 12px; font-weight: bold; border: none;">Yang membuat laporan Ketua KPU</td>
                </tr>
                <!-- SPASI UNTUK TANDA TANGAN -->
                <tr>
                    <td style="height: 60px; border: none;"></td>
                    <td style="height: 60px; border: none;"></td>
                </tr>
                <!-- BARIS 3: Nama dengan UNDERLINE -->
                <tr>
                    <td style="width: 50%; text-align: left; font-size: 12px; font-weight: bold; text-decoration: underline; border: none;">Istadi, SH</td>
                    <td style="width: 50%; text-align: right; font-size: 12px; font-weight: bold; text-decoration: underline; border: none;">Fajar Randi Yogananda</td>
                </tr>
            </table>
        </div>
        
        <!-- FOOTER -->
        <div class="footer">
            <p>Dicetak pada: ' . date('d F Y H:i:s') . ' WIB</p>
            <p>Sistem Laporan Kinerja Harian - KPU Kota Pekalongan</p>
        </div>
    </body>
    </html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Laporan - KPU Kota Pekalongan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 85, 165, 0.1);
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #0055a5 0%, #d52b1e 100%);
        }

        /* Header Styles */
        .kpu-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eaeaea;
        }

        .kpu-logo-wrapper {
            flex: 0 0 100px;
            margin-right: 20px;
        }

        .kpu-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 3px solid white;
            background: linear-gradient(135deg, #d52b1e 0%, #0055a5 100%);
            overflow: hidden;
        }

        .logo-image {
            width: 90%;
            height: 90%;
            object-fit: cover;
            border-radius: 50%;
        }

        .kpu-title {
            flex: 1;
        }

        .kpu-title h1 {
            font-size: 24px;
            font-weight: 800;
            color: #0055a5;
            margin-bottom: 5px;
        }

        .kpu-title h1 span {
            color: #d52b1e;
        }

        .kpu-title h2 {
            font-size: 16px;
            font-weight: 600;
            color: #d52b1e;
            margin-bottom: 10px;
        }

        .kpu-subtitle {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .period-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 10px 20px;
            border-radius: 8px;
            border-left: 4px solid #0055a5;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }

        /* Filter Section */
        .filter-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 992px) {
            .filter-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #0055a5;
            font-size: 14px;
        }

        .filter-input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }

        .filter-input:focus {
            outline: none;
            border-color: #0055a5;
            box-shadow: 0 0 0 3px rgba(0, 85, 165, 0.1);
        }

        .btn-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-back {
            background: white;
            color: #0055a5;
            border: 2px solid #0055a5;
        }

        .btn-back:hover {
            background: #0055a5;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 85, 165, 0.2);
        }

        .btn-excel {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
        }

        .btn-excel:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
        }

        .btn-pdf {
            background: linear-gradient(135deg, #0055a5 0%, #003d82 100%);
            color: white;
        }

        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 85, 165, 0.2);
        }

        /* Employee Info Card */
        .employee-info-card {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 2px solid #2196f3;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            display: <?php echo ($pegawai_info) ? 'block' : 'none'; ?>;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .info-label {
            font-weight: 700;
            color: #0055a5;
            width: 100px;
            min-width: 100px;
        }

        .info-value {
            font-weight: 600;
            color: #000;
            flex: 1;
        }

        /* Table Styles */
        .table-container {
            margin-top: 20px;
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 800px;
        }

        .table th {
            background: linear-gradient(135deg, #0055a5 0%, #003d82 100%);
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eaeaea;
            vertical-align: top;
            line-height: 1.5;
        }

        .table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .table tr:hover {
            background: #e3f2fd;
            transition: background 0.3s;
        }

        .badge-lokasi {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-lokasi.wfo {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .badge-lokasi.wfh {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .badge-lokasi.dl {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        /* ============================================ */
        /* TANDA TANGAN WEB - PEKALONGAN DI ATAS "YANG MEMBUAT LAPORAN" */
        /* ============================================ */
        .signature-section {
            margin-top: 60px;
            margin-bottom: 30px;
            width: 100%;
        }
        
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .signature-table td {
            border: none;
            padding: 5px;
            vertical-align: top;
        }
        
        /* Kolom kiri 50% - rata kiri */
        .signature-col-left {
            width: 50%;
            text-align: left;
            font-size: 13px;
            font-weight: bold;
            border: none;
        }
        
        /* Kolom kanan 50% - rata kanan */
        .signature-col-right {
            width: 50%;
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            border: none;
        }
        
        /* Nama dengan underline */
        .signature-underline-left {
            text-align: left;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
        }
        
        .signature-underline-right {
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
        }
        
        .signature-space {
            height: 60px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #666;
            border: 2px dashed #ddd;
            border-radius: 10px;
            margin: 30px 0;
            background: #f8f9fa;
        }

        .empty-state i {
            font-size: 48px;
            color: #b0b0b0;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #495057;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
            color: #666;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .kpu-header {
                flex-direction: column;
                text-align: center;
            }
            
            .kpu-logo-wrapper {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .btn-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .kpu-logo {
                width: 80px;
                height: 80px;
            }
            
            .kpu-title h1 {
                font-size: 20px;
            }
            
            .kpu-subtitle {
                font-size: 16px;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header dengan Logo -->
        <div class="kpu-header">
            <div class="kpu-logo-wrapper">
                <div class="kpu-logo">
                    <?php 
                    $logo = getLogoBase64();
                    if ($logo): 
                    ?>
                    <img src="data:image/<?php echo $logo['type']; ?>;base64,<?php echo $logo['base64']; ?>" 
                         alt="Logo KPU Kota Pekalongan" 
                         class="logo-image">
                    <?php else: ?>
                    <div style="color: white; font-weight: bold; text-align: center; font-size: 14px;">
                        KPU<br>KOTA<br>PEKALONGAN
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="kpu-title">
                <h2>KOMISI PEMILIHAN UMUM</h2>
                <h1>KOTA <span>PEKALONGAN</span></h1>
                <div class="kpu-subtitle">REKAPITULASI LAPORAN KINERJA HARIAN</div>
                <div class="period-info">
                    PERIODE: <?php echo $start_display . ' s/d ' . $end_display; ?> | 
                    Total: <?php echo $total_laporan; ?> Laporan
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section no-print">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label><i class="fas fa-user"></i> Nama Pegawai</label>
                        <select name="pegawai" class="filter-input">
                            <option value="">Semua Pegawai</option>
                            <?php foreach ($pegawai_options as $pegawai): ?>
                            <option value="<?php echo $pegawai['id']; ?>" 
                                <?php echo ($filter_pegawai == $pegawai['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pegawai['nama']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-day"></i> Tanggal Mulai</label>
                        <input type="date" name="start_date" class="filter-input" 
                               value="<?php echo $filter_start; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-check"></i> Tanggal Selesai</label>
                        <input type="date" name="end_date" class="filter-input" 
                               value="<?php echo $filter_end; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label><i class="fas fa-briefcase"></i> Jabatan</label>
                        <select name="jenis" class="filter-input">
                            <option value="">Semua Jabatan</option>
                            <?php foreach ($jabatan_options as $jabatan): ?>
                            <option value="<?php echo htmlspecialchars($jabatan['jabatan']); ?>" 
                                <?php echo ($filter_jenis == $jabatan['jabatan']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($jabatan['jabatan']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="btn-group">
                    <div class="left-buttons">
                        <a href="index.php" class="btn btn-back">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                    
                    <div class="right-buttons">
                        <button type="submit" class="btn btn-back" style="background: #0055a5; color: white; border: none;">
                            <i class="fas fa-search"></i> Tampilkan Data
                        </button>
                        
                        <?php if ($total_laporan > 0): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['download_excel' => 1])); ?>" class="btn btn-excel">
                            <i class="fas fa-file-excel"></i> Excel
                        </a>
                        
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['download_pdf' => 1])); ?>" class="btn btn-pdf">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Employee Info Card -->
        <?php if ($pegawai_info): ?>
        <div class="employee-info-card">
            <div class="info-row">
                <div class="info-label">NAMA</div>
                <div class="info-value">: <?php echo htmlspecialchars($pegawai_info['nama']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">NIP</div>
                <div class="info-value">: <?php echo htmlspecialchars($pegawai_info['nip'] ?? '-'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">JABATAN</div>
                <div class="info-value">: <?php echo htmlspecialchars($pegawai_info['jabatan']); ?></div>
            </div>
            <?php if (!empty($pegawai_info['bagian'])): ?>
            <div class="info-row">
                <div class="info-label">BAGIAN</div>
                <div class="info-value">: <?php echo htmlspecialchars($pegawai_info['bagian']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Reports Table -->
        <?php if ($total_laporan > 0): ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>TANGGAL</th>
                        <th>NIP</th>
                        <th>URAIAN TUGAS</th>
                        <th>HASIL OUTPUT</th>
                        <th>LOKASI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php foreach ($laporan_list as $laporan): ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold;"><?php echo $counter++; ?></td>
                        <td style="text-align: center;"><?php echo date('d/m/Y', strtotime($laporan['tanggal'])); ?></td>
                        <td><?php echo htmlspecialchars($laporan['nip'] ?? '-'); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($laporan['uraian_tugas'])); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($laporan['hasil_output'])); ?></td>
                        <td style="text-align: center;">
                            <span class="badge-lokasi <?php echo strtolower($laporan['jenis_penugasan']); ?>">
                                <?php echo getKeteranganPenugasan($laporan['jenis_penugasan']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- ============================================ -->
        <!-- BAGIAN TANDA TANGAN WEB - PEKALONGAN DI ATAS "YANG MEMBUAT LAPORAN" -->
        <!-- ============================================ -->
        <div class="signature-section no-print">
            <table class="signature-table" style="width: 100%; border-collapse: collapse;">
                <!-- BARIS 1: Mengetahui (KIRI) - Pekalongan (KANAN) -->
                <tr>
                    <td style="width: 50%; text-align: left; font-size: 13px; font-weight: bold; border: none;">Mengetahui,</td>
                    <td style="width: 50%; text-align: right; font-size: 13px; font-weight: bold; border: none;">Pekalongan, <?php echo tanggalIndo(date('Y-m-d')); ?></td>
                </tr>
                <!-- BARIS 2: Kosong (KIRI) - Yang membuat laporan Ketua KPU (KANAN) -->
                <tr>
                    <td style="width: 50%; text-align: left; border: none;">&nbsp;</td>
                    <td style="width: 50%; text-align: right; font-size: 13px; font-weight: bold; border: none;">Yang membuat laporan Ketua KPU</td>
                </tr>
                <!-- SPASI UNTUK TANDA TANGAN -->
                <tr>
                    <td style="height: 60px; border: none;"></td>
                    <td style="height: 60px; border: none;"></td>
                </tr>
                <!-- BARIS 3: Nama dengan UNDERLINE -->
                <tr>
                    <td style="width: 50%; text-align: left; font-size: 13px; font-weight: bold; text-decoration: underline; border: none;">Istadi, SH</td>
                    <td style="width: 50%; text-align: right; font-size: 13px; font-weight: bold; text-decoration: underline; border: none;">Fajar Randi Yogananda</td>
                </tr>
            </table>
        </div>
        
        <?php else: ?>
        <div class="empty-state">
            <i class="far fa-clipboard"></i>
            <h3>TIDAK ADA DATA LAPORAN</h3>
            <p>Tidak ditemukan laporan yang sesuai dengan filter yang dipilih</p>
            <p style="margin-top: 15px; font-size: 14px; color: #6c757d;">
                <i class="fas fa-info-circle"></i> Coba ubah periode tanggal atau pilih pegawai lain
            </p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer no-print">
            <p>&copy; <?php echo date('Y'); ?> KOMISI PEMILIHAN UMUM KOTA PEKALONGAN</p>
            <p>Sistem Rekapitulasi Laporan Kinerja Harian - Versi 2.0</p>
        </div>
    </div>

    <script>
        // Auto submit form saat filter berubah
        document.querySelectorAll('.filter-input').forEach(input => {
            input.addEventListener('change', function() {
                this.form.submit();
            });
        });
        
        // Update date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const startDate = document.querySelector('input[name="start_date"]');
            const endDate = document.querySelector('input[name="end_date"]');
            
            if (startDate) startDate.max = today;
            if (endDate) endDate.max = today;
        });
    </script>
</body>
</html>