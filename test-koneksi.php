<?php
// File: test-koneksi.php
// Test apakah koneksi database berhasil

echo "<h1>TEST KONEKSI DATABASE KPU PEKALONGAN</h1>";
echo "<p>Mencoba menghubungkan ke database...</p>";

// 1. Coba include file database.php
require_once 'config/database.php';

// 2. Tampilkan pesan sukses jika koneksi berhasil
echo "<div style='background: green; color: white; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "✓ SUCCESS: Koneksi ke database BERHASIL!";
echo "</div>";

// 3. Cek jumlah data pegawai
echo "<h3>Cek Data Pegawai:</h3>";

$sql = "SELECT COUNT(*) as total FROM pegawai";
$result = querySingle($sql);

echo "<p>Total pegawai di database: <span style='font-size: 24px; color: blue;'><strong>" . $result['total'] . "</strong></span> orang</p>";

// 4. Tampilkan beberapa data pegawai
$sql = "SELECT nama, jabatan FROM pegawai ORDER BY nama LIMIT 10";
$pegawai = query($sql);

echo "<h3>10 Pegawai Pertama:</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr><th>No</th><th>Nama</th><th>Jabatan</th></tr>";

$no = 1;
foreach ($pegawai as $p) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . $p['nama'] . "</td>";
    echo "<td>" . $p['jabatan'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// 5. Cek data laporan
echo "<h3>Cek Data Laporan:</h3>";

$sql = "SELECT COUNT(*) as total FROM laporan_kinerja";
$result = querySingle($sql);

echo "<p>Total laporan di database: <span style='font-size: 24px; color: blue;'><strong>" . $result['total'] . "</strong></span> laporan</p>";

// 6. Link ke halaman utama
echo "<br><br>";
echo "<a href='index.php' style='background: #0055a5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>";
echo "KEMBALI KE HALAMAN UTAMA";
echo "</a>";
?>