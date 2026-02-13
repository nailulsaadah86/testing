<?php
// File: tcpdf/test_pdf.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html>
<html>
<head>
    <title>Test TCPDF Installation</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; border-radius: 5px; }
        .btn { 
            display: inline-block; 
            padding: 10px 20px; 
            background: #0055a5; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            margin-top: 20px; 
        }
        .btn:hover { background: #003d82; }
    </style>
</head>
<body>
    <h2>Test TCPDF Installation</h2>
    <div class="info">';

// Cek apakah file tcpdf.php ada
$tcpdf_path = __DIR__ . '/tcpdf.php';
if (file_exists($tcpdf_path)) {
    echo '<p class="success">✓ File tcpdf.php ditemukan di: ' . $tcpdf_path . '</p>';
    
    // Include file utama TCPDF
    require_once($tcpdf_path);
    
    // Cek apakah class TCPDF ada
    if (class_exists('TCPDF')) {
        echo '<p class="success">✓ Class TCPDF berhasil di-load!</p>';
        
        // Test membuat PDF sederhana
        try {
            // Create new PDF document
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('KPU Pekalongan');
            $pdf->SetAuthor('KPU Test');
            $pdf->SetTitle('Test Installation TCPDF');
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', 'B', 16);
            
            // Add content
            $pdf->Cell(0, 10, 'TCPDF BERHASIL DIINSTALL!', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Ln(5);
            $pdf->Cell(0, 10, 'KPU Kota Pekalongan', 0, 1, 'C');
            $pdf->Cell(0, 10, 'Sistem Laporan Kinerja Harian', 0, 1, 'C');
            $pdf->Ln(5);
            $pdf->Cell(0, 10, 'Tanggal: ' . date('d F Y H:i:s'), 0, 1, 'C');
            
            // Output ke browser
            $pdf->Output('Test_TCPDF.pdf', 'I');
            
            echo '<p class="success">✓ PDF berhasil dibuat dan ditampilkan!</p>';
            
        } catch (Exception $e) {
            echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';
        }
        
    } else {
        echo '<p class="error">✗ Class TCPDF tidak ditemukan!</p>';
    }
} else {
    echo '<p class="error">✗ File tcpdf.php tidak ditemukan!</p>';
    echo '<p>Pastikan Anda telah mengekstrak folder TCPDF ke folder "tcpdf/"</p>';
}

echo '</div>';

echo '<p><a href="../rekap.php" class="btn">Kembali ke Rekapitulasi</a></p>';

echo '<h3>Struktur Folder yang Diperlukan:</h3>
    <pre>
/tcpdf/
├── tcpdf.php
├── fonts/
├── config/
├── include/
└── test_pdf.php
    </pre>
    
    <h3>Cara Install TCPDF:</h3>
    <ol>
        <li>Download TCPDF dari: <a href="https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip" target="_blank">GitHub TCPDF</a></li>
        <li>Extract file ZIP</li>
        <li>Rename folder hasil extract menjadi <strong>tcpdf</strong></li>
        <li>Pindahkan folder <strong>tcpdf</strong> ke folder aplikasi ini (sejajar dengan index.php dan rekap.php)</li>
        <li>Akses halaman ini lagi: <a href="test_pdf.php">test_pdf.php</a></li>
    </ol>
</body>
</html>';