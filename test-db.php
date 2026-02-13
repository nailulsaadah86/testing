<?php
// ==================== TEST_PDF.PHP ====================
// File untuk mengetes instalasi TCPDF
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek keberadaan folder tcpdf
$tcpdf_path = __DIR__ . '/tcpdf.php';
$tcpdf_folder = __DIR__;

echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test TCPDF - KPU Pekalongan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: "Poppins", sans-serif;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            max-width: 900px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 85, 165, 0.15);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #0055a5, #d52b1e, #0055a5);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d52b1e, #0055a5);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 25px rgba(0,85,165,0.3);
            border: 4px solid white;
        }
        
        .logo svg {
            width: 80px;
            height: 80px;
        }
        
        h1 {
            color: #0055a5;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        h2 {
            color: #d52b1e;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .status-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #edf2f7;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
        }
        
        .success-icon {
            background: #d4edda;
            color: #28a745;
        }
        
        .error-icon {
            background: #f8d7da;
            color: #dc3545;
        }
        
        .warning-icon {
            background: #fff3cd;
            color: #ffc107;
        }
        
        .status-text {
            flex: 1;
        }
        
        .status-title {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 3px;
        }
        
        .status-desc {
            color: #6c757d;
            font-size: 14px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0055a5, #003d82);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .btn i {
            font-size: 16px;
        }
        
        .folder-structure {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 20px;
            font-family: monospace;
            font-size: 14px;
            border-left: 4px solid #0055a5;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6c757d;
            font-size: 13px;
        }
        
        .highlight {
            background: #fff3cd;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
                    <circle cx="100" cy="100" r="95" fill="white" stroke="white" stroke-width="5"/>
                    <text x="100" y="120" font-size="50" text-anchor="middle" fill="white" font-family="Arial" font-weight="bold">KPU</text>
                </svg>
            </div>
            <h1>TCPDF INSTALLATION TEST</h1>
            <h2>KPU KOTA PEKALONGAN</h2>
        </div>';
        
        // ========== CEK FOLDER TCPDF ==========
        echo '<div class="status-card">';
        echo '<h3 style="margin-bottom: 20px; color: #0055a5; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-folder-tree"></i> Pengecekan Instalasi TCPDF
              </h3>';
        
        // CEK 1: Apakah folder tcpdf ada?
        $folder_exists = is_dir($tcpdf_folder);
        echo '<div class="status-item">';
        if ($folder_exists) {
            echo '<div class="status-icon success-icon"><i class="fas fa-check"></i></div>';
            echo '<div class="status-text">';
            echo '<div class="status-title">✓ Folder TCPDF ditemukan</div>';
            echo '<div class="status-desc">Lokasi: ' . $tcpdf_folder . '</div>';
            echo '</div>';
        } else {
            echo '<div class="status-icon error-icon"><i class="fas fa-times"></i></div>';
            echo '<div class="status-text">';
            echo '<div class="status-title">✗ Folder TCPDF tidak ditemukan</div>';
            echo '<div class="status-desc">Folder tcpdf/ harus berada di: ' . dirname($tcpdf_folder) . '/tcpdf/</div>';
            echo '</div>';
        }
        echo '</div>';
        
        // CEK 2: Apakah file tcpdf.php ada?
        $file_exists = file_exists($tcpdf_path);
        echo '<div class="status-item">';
        if ($file_exists) {
            echo '<div class="status-icon success-icon"><i class="fas fa-check"></i></div>';
            echo '<div class="status-text">';
            echo '<div class="status-title">✓ File tcpdf.php ditemukan</div>';
            echo '<div class="status-desc">Lokasi: ' . $tcpdf_path . '</div>';
            echo '</div>';
        } else {
            echo '<div class="status-icon error-icon"><i class="fas fa-times"></i></div>';
            echo '<div class="status-text">';
            echo '<div class="status-title">✗ File tcpdf.php tidak ditemukan</div>';
            echo '<div class="status-desc">File utama TCPDF tidak ada di folder tcpdf/</div>';
            echo '</div>';
        }
        echo '</div>';
        
        // CEK 3: Apakah class TCPDF bisa di-load?
        if ($file_exists) {
            require_once($tcpdf_path);
            $class_exists = class_exists('TCPDF');
            
            echo '<div class="status-item">';
            if ($class_exists) {
                echo '<div class="status-icon success-icon"><i class="fas fa-check"></i></div>';
                echo '<div class="status-text">';
                echo '<div class="status-title">✓ Class TCPDF berhasil di-load</div>';
                echo '<div class="status-desc">TCPDF siap digunakan</div>';
                echo '</div>';
            } else {
                echo '<div class="status-icon error-icon"><i class="fas fa-times"></i></div>';
                echo '<div class="status-text">';
                echo '<div class="status-title">✗ Class TCPDF gagal di-load</div>';
                echo '<div class="status-desc">File tcpdf.php mungkin corrupt</div>';
                echo '</div>';
            }
            echo '</div>';
        }
        
        // CEK 4: Test generate PDF
        if ($file_exists && class_exists('TCPDF')) {
            try {
                // Create new PDF document
                $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
                
                // Set document information
                $pdf->SetCreator('KPU Pekalongan');
                $pdf->SetAuthor('Sistem Laporan Kinerja');
                $pdf->SetTitle('Test TCPDF Installation');
                
                // Remove default header/footer
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
                
                // Add a page
                $pdf->AddPage();
                
                // Set font
                $pdf->SetFont('helvetica', 'B', 16);
                
                // Content
                $pdf->Cell(0, 10, 'TCPDF BERHASIL DIINSTALL!', 0, 1, 'C');
                $pdf->SetFont('helvetica', '', 12);
                $pdf->Ln(5);
                $pdf->Cell(0, 10, 'KPU Kota Pekalongan', 0, 1, 'C');
                $pdf->Cell(0, 10, 'Sistem Laporan Kinerja Harian', 0, 1, 'C');
                $pdf->Ln(5);
                $pdf->Cell(0, 10, 'Tanggal Test: ' . date('d F Y H:i:s'), 0, 1, 'C');
                
                // Save to file
                $test_file = __DIR__ . '/test_output.pdf';
                $pdf->Output($test_file, 'F');
                
                if (file_exists($test_file)) {
                    echo '<div class="status-item">';
                    echo '<div class="status-icon success-icon"><i class="fas fa-check"></i></div>';
                    echo '<div class="status-text">';
                    echo '<div class="status-title">✓ Test PDF berhasil dibuat</div>';
                    echo '<div class="status-desc">Lokasi: ' . $test_file . ' | Ukuran: ' . round(filesize($test_file)/1024, 2) . ' KB</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Hapus file test
                    unlink($test_file);
                }
                
            } catch (Exception $e) {
                echo '<div class="status-item">';
                echo '<div class="status-icon error-icon"><i class="fas fa-exclamation"></i></div>';
                echo '<div class="status-text">';
                echo '<div class="status-title">✗ Error membuat PDF</div>';
                echo '<div class="status-desc">' . $e->getMessage() . '</div>';
                echo '</div>';
                echo '</div>';
            }
        }
        
        echo '</div>';
        
        // ========== STATUS KESIMPULAN ==========
        $is_ready = $file_exists && class_exists('TCPDF');
        
        echo '<div style="text-align: center; margin-bottom: 30px;">';
        if ($is_ready) {
            echo '<div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 12px; border: 2px solid #c3e6cb;">';
            echo '<i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px;"></i>';
            echo '<h3 style="margin-bottom: 10px;">✓ TCPDF SIAP DIGUNAKAN</h3>';
            echo '<p>Anda dapat menggunakan fitur Download PDF di halaman Rekapitulasi</p>';
            echo '</div>';
        } else {
            echo '<div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 12px; border: 2px solid #ffeaa7;">';
            echo '<i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i>';
            echo '<h3 style="margin-bottom: 10px;">⚠ TCPDF BELUM TERINSTALL</h3>';
            echo '<p>Silakan ikuti petunjuk instalasi di bawah ini</p>';
            echo '</div>';
        }
        echo '</div>';
        
        // ========== PETUNJUK INSTALASI ==========
        if (!$is_ready) {
            echo '<div style="background: #e7f5ff; border-radius: 16px; padding: 25px; margin-bottom: 30px; border: 2px solid #0055a5;">';
            echo '<h3 style="color: #0055a5; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-download"></i> CARA INSTAL TCPDF
                  </h3>';
            
            echo '<ol style="margin-left: 20px; line-height: 1.8;">';
            echo '<li><strong>Download TCPDF</strong> dari GitHub: <a href="https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip" target="_blank">https://github.com/tecnickcom/TCPDF</a></li>';
            echo '<li><strong>Extract file</strong> yang sudah di-download</li>';
            echo '<li><strong>Rename folder</strong> hasil extract menjadi <span class="highlight">tcpdf</span></li>';
            echo '<li><strong>Pindahkan folder</strong> <span class="highlight">tcpdf</span> ke folder aplikasi ini:<br>';
            echo '<code style="background: #2d3748; color: #e2e8f0; padding: 8px 15px; border-radius: 8px; display: inline-block; margin-top: 5px;">';
            echo dirname(__DIR__) . '/tcpdf/';
            echo '</code></li>';
            echo '<li><strong>Akses ulang</strong> halaman ini untuk memverifikasi instalasi</li>';
            echo '</ol>';
            
            echo '<div class="folder-structure" style="margin-top: 20px;">';
            echo '<strong style="color: #0055a5;">📁 Struktur folder yang benar:</strong><br><br>';
            echo '📁 ' . basename(dirname(__DIR__)) . '/<br>';
            echo '├── 📁 config/<br>';
            echo '├── 📁 tcpdf/<br>';
            echo '│   ├── 📄 tcpdf.php<br>';
            echo '│   ├── 📁 fonts/<br>';
            echo '│   ├── 📁 config/<br>';
            echo '│   └── ...<br>';
            echo '├── 📄 index.php<br>';
            echo '├── 📄 rekap.php<br>';
            echo '└── 📁 tcpdf/ (folder ini)<br>';
            echo '&nbsp;&nbsp;&nbsp;&nbsp;└── 📄 test_pdf.php';
            echo '</div>';
            
            echo '</div>';
        }
        
        // ========== INFORMASI SISTEM ==========
        echo '<div style="background: #f8f9fa; border-radius: 16px; padding: 25px; margin-bottom: 30px;">';
        echo '<h3 style="color: #0055a5; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-info-circle"></i> INFORMASI SISTEM
              </h3>';
        
        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<tr><td style="padding: 8px 0; width: 200px;"><strong>PHP Version</strong></td><td>: ' . phpversion() . '</td></tr>';
        echo '<tr><td style="padding: 8px 0;"><strong>Server Software</strong></td><td>: ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</td></tr>';
        echo '<tr><td style="padding: 8px 0;"><strong>Document Root</strong></td><td>: ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . '</td></tr>';
        echo '<tr><td style="padding: 8px 0;"><strong>Script Path</strong></td><td>: ' . __DIR__ . '</td></tr>';
        echo '<tr><td style="padding: 8px 0;"><strong>Memory Limit</strong></td><td>: ' . ini_get('memory_limit') . '</td></tr>';
        echo '<tr><td style="padding: 8px 0;"><strong>Max Execution Time</strong></td><td>: ' . ini_get('max_execution_time') . ' detik</td></tr>';
        echo '<tr><td style="padding: 8px 0;"><strong>Upload Max Filesize</strong></td><td>: ' . ini_get('upload_max_filesize') . '</td></tr>';
        echo '</table>';
        echo '</div>';
        
        // ========== TOMBOL AKSI ==========
        echo '<div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">';
        
        if ($is_ready) {
            echo '<a href="../rekap.php" class="btn btn-primary">';
            echo '<i class="fas fa-chart-bar"></i> Buka Rekapitulasi';
            echo '</a>';
        }
        
        echo '<a href="../index.php" class="btn btn-success">';
        echo '<i class="fas fa-file-alt"></i> Buka Form Laporan';
        echo '</a>';
        
        echo '<button onclick="window.location.reload()" class="btn" style="background: #6c757d; color: white;">';
        echo '<i class="fas fa-sync-alt"></i> Refresh Halaman';
        echo '</button>';
        
        echo '</div>';
        
        // ========== FOOTER ==========
        echo '<div class="footer">';
        echo '<p>© ' . date('Y') . ' KPU Kota Pekalongan - Sistem Laporan Kinerja Harian</p>';
        echo '<p style="margin-top: 5px;">TCPDF Version: ' . ($is_ready ? TCPDF::VERSION : 'Not Installed') . '</p>';
        echo '</div>';
        
echo '</div>';

// Font Awesome untuk icon
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>';
echo '</body></html>';
?>