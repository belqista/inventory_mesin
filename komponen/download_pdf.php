<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

/* =========================================================
   DOWNLOAD PDF DETAIL KOMPONEN
   INVENTORY & MAINTENANCE SYSTEM
   PT GARUDAFOOD PUTRA PUTRI JAYA TBK

   VERSI HOSTING
   - Tidak menggunakan mysqli_stmt_get_result()
   - Kompatibel dengan hosting tanpa mysqlnd
   - Foto menggunakan Base64
   - DomPDF
========================================================= */


/* =========================================================
   ERROR HANDLING
========================================================= */

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);


/* =========================================================
   LOAD FILE
========================================================= */

$koneksiPath = dirname(__DIR__) . '/koneksi.php';
$vendorPath  = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($koneksiPath)) {
    die('File koneksi.php tidak ditemukan.');
}

if (!file_exists($vendorPath)) {
    die('File vendor/autoload.php tidak ditemukan.');
}

require_once $koneksiPath;
require_once $vendorPath;


/* =========================================================
   DOMPDF
========================================================= */

use Dompdf\Dompdf;
use Dompdf\Options;


/* =========================================================
   CEK DATABASE
========================================================= */

if (!isset($conn) || !$conn) {
    die('Koneksi database gagal.');
}


/* =========================================================
   PARAMETER ID
========================================================= */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die('ID komponen tidak valid.');
}


/* =========================================================
   HELPER ESCAPE HTML
========================================================= */

function e($text)
{
    return htmlspecialchars(
        (string)$text,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   HELPER FOTO BASE64
========================================================= */

function imageToDataUri($path)
{
    if (empty($path)) {
        return '';
    }

    if (!file_exists($path)) {
        return '';
    }

    $data = @file_get_contents($path);

    if ($data === false) {
        return '';
    }

    /*
     * Jangan bergantung pada mime_content_type()
     * karena beberapa hosting tidak mengaktifkannya.
     */

    $extension = strtolower(
        pathinfo($path, PATHINFO_EXTENSION)
    );

    switch ($extension) {

        case 'jpg':
        case 'jpeg':
            $mime = 'image/jpeg';
            break;

        case 'png':
            $mime = 'image/png';
            break;

        case 'gif':
            $mime = 'image/gif';
            break;

        case 'webp':
            $mime = 'image/webp';
            break;

        default:
            return '';
    }

    return 'data:' . $mime . ';base64,' .
           base64_encode($data);
}


/* =========================================================
   DATA KOMPONEN
   TIDAK MENGGUNAKAN get_result()
========================================================= */

$sql = "
    SELECT 
        k.id,
        k.serial_number,
        k.id_sub_mesin,
        k.mesin,
        k.sub_mesin,
        k.nama_bagian,
        k.jenis_komponen,
        k.spesifikasi,
        k.kategori,
        k.brand,
        k.tipe,
        k.part_number,
        k.daya,
        k.io_address,
        k.ip_address,
        k.input_voltage,
        k.frekuensi_input,
        k.arus_input,
        k.output,
        k.frekuensi_output,
        k.ip_rating,
        k.keterangan,
        k.kondisi,
        k.gambar,

        sm.nama_sub_mesin,

        m.nama_mesin,
        m.serial_number AS sn_mesin,

        jm.nama_jenis_mesin,

        ab.nama_area,
        ab.lokasi

    FROM komponen k

    LEFT JOIN sub_mesin sm
        ON k.id_sub_mesin = sm.id

    LEFT JOIN mesin m
        ON sm.id_mesin = m.id

    LEFT JOIN jenis_mesin jm
        ON m.id_jenis_mesin = jm.id

    LEFT JOIN area_bagian ab
        ON m.id_area = ab.id

    WHERE k.id = ?
    LIMIT 1
";


$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die(
        'Query komponen gagal: ' .
        mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmt,
    'i',
    $id
);


if (!mysqli_stmt_execute($stmt)) {

    die(
        'Eksekusi query komponen gagal: ' .
        mysqli_stmt_error($stmt)
    );
}


/* =========================================================
   BIND RESULT
========================================================= */

mysqli_stmt_bind_result(
    $stmt,

    $k_id,
    $k_serial_number,
    $k_id_sub_mesin,
    $k_mesin,
    $k_sub_mesin,
    $k_nama_bagian,
    $k_jenis_komponen,
    $k_spesifikasi,
    $k_kategori,
    $k_brand,
    $k_tipe,
    $k_part_number,
    $k_daya,
    $k_io_address,
    $k_ip_address,
    $k_input_voltage,
    $k_frekuensi_input,
    $k_arus_input,
    $k_output,
    $k_frekuensi_output,
    $k_ip_rating,
    $k_keterangan,
    $k_kondisi,
    $k_gambar,

    $sm_nama_sub_mesin,

    $m_nama_mesin,
    $m_sn_mesin,

    $jm_nama_jenis_mesin,

    $ab_nama_area,
    $ab_lokasi
);


$found = mysqli_stmt_fetch($stmt);

mysqli_stmt_close($stmt);


if (!$found) {
    die('Data komponen tidak ditemukan.');
}


/* =========================================================
   SUSUN DATA KOMPONEN
========================================================= */

$komponen = [

    'id' => $k_id,

    'serial_number' => $k_serial_number,

    'id_sub_mesin' => $k_id_sub_mesin,

    'mesin' => $k_mesin,

    'sub_mesin' => $k_sub_mesin,

    'nama_bagian' => $k_nama_bagian,

    'jenis_komponen' => $k_jenis_komponen,

    'spesifikasi' => $k_spesifikasi,

    'kategori' => $k_kategori,

    'brand' => $k_brand,

    'tipe' => $k_tipe,

    'part_number' => $k_part_number,

    'daya' => $k_daya,

    'io_address' => $k_io_address,

    'ip_address' => $k_ip_address,

    'input_voltage' => $k_input_voltage,

    'frekuensi_input' => $k_frekuensi_input,

    'arus_input' => $k_arus_input,

    'output' => $k_output,

    'frekuensi_output' => $k_frekuensi_output,

    'ip_rating' => $k_ip_rating,

    'keterangan' => $k_keterangan,

    'kondisi' => $k_kondisi,

    'gambar' => $k_gambar,

    'nama_sub_mesin' => $sm_nama_sub_mesin,

    'nama_mesin' => $m_nama_mesin,

    'sn_mesin' => $m_sn_mesin,

    'nama_jenis_mesin' => $jm_nama_jenis_mesin,

    'nama_area' => $ab_nama_area,

    'lokasi' => $ab_lokasi
];


/* =========================================================
   FOTO KOMPONEN
========================================================= */

$foto = '';

if (!empty($komponen['gambar'])) {

    $fotoPath =
        dirname(__DIR__) .
        '/uploads/komponen/' .
        basename($komponen['gambar']);

    $foto = imageToDataUri($fotoPath);
}


/* =========================================================
   DATA MAINTENANCE
   TANPA get_result()
========================================================= */

$dataMaintenance = [];


$sqlMaintenance = "
    SELECT
        rm.id,
        rm.id_komponen,
        rm.tanggal,
        rm.jenis,
        rm.tindakan,
        rm.teknisi,
        rm.status

    FROM riwayat_maintenance rm

    WHERE rm.id_komponen = ?

    ORDER BY
        rm.tanggal DESC,
        rm.id DESC
";


$stmtM = mysqli_prepare(
    $conn,
    $sqlMaintenance
);


if ($stmtM) {

    mysqli_stmt_bind_param(
        $stmtM,
        'i',
        $id
    );


    if (mysqli_stmt_execute($stmtM)) {

        mysqli_stmt_bind_result(
            $stmtM,

            $m_id,
            $m_id_komponen,
            $m_tanggal,
            $m_jenis,
            $m_tindakan,
            $m_teknisi,
            $m_status
        );


        while (mysqli_stmt_fetch($stmtM)) {

            $dataMaintenance[] = [

                'id' =>
                    $m_id,

                'id_komponen' =>
                    $m_id_komponen,

                'tanggal' =>
                    $m_tanggal,

                'jenis' =>
                    $m_jenis,

                'tindakan' =>
                    $m_tindakan,

                'teknisi' =>
                    $m_teknisi,

                'status' =>
                    $m_status
            ];
        }
    }


    mysqli_stmt_close($stmtM);
}


/* =========================================================
   KONDISI KOMPONEN
========================================================= */

$kondisi =
    $komponen['kondisi'] ?? 'Baik';


$kondisiClass = 'good';


if ($kondisi === 'Perlu Pemeriksaan') {

    $kondisiClass = 'warning';

} elseif (
    $kondisi === 'Dalam Perbaikan' ||
    $kondisi === 'Rusak'
) {

    $kondisiClass = 'danger';
}


/* =========================================================
   JENIS KOMPONEN
========================================================= */

$jenisKomponen =
    $komponen['jenis_komponen'] ?? '-';


if (trim($jenisKomponen) === '') {

    $jenisKomponen = '-';
}


/* =========================================================
   HTML PDF
========================================================= */

$html = '
<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

@page {
    margin: 35px 35px 45px 35px;
}


body {

    font-family:
        DejaVu Sans,
        sans-serif;

    font-size: 10px;

    color: #263238;

}


.header {

    border-bottom:
        3px solid #005baa;

    padding-bottom: 12px;

    margin-bottom: 18px;

}


.title {

    font-size: 20px;

    font-weight: bold;

    color: #005baa;

}


.subtitle {

    font-size: 9px;

    color: #64748b;

    margin-top: 3px;

}


.date {

    float: right;

    color: #64748b;

    font-size: 8px;

}


.component-box {

    border:
        1px solid #dce3ea;

    padding: 15px;

    margin-bottom: 18px;

}


.photo {

    width: 160px;

    height: 135px;

    object-fit: contain;

}


.no-photo {

    width: 160px;

    height: 135px;

    background: #f1f5f9;

    text-align: center;

    vertical-align: middle;

    color: #94a3b8;

}


.component-name {

    font-size: 19px;

    font-weight: bold;

    color: #111827;

    margin-bottom: 10px;

}


.info {

    width: 100%;

    border-collapse:
        collapse;

}


.info td {

    padding: 5px 7px;

    border-bottom:
        1px solid #edf0f2;

}


.label {

    width: 125px;

    color: #64748b;

}


.condition {

    display: inline-block;

    padding: 4px 9px;

    border-radius: 12px;

    font-weight: bold;

}


.section {

    background: #005baa;

    color: white;

    padding: 8px 10px;

    font-weight: bold;

    margin-top: 15px;

    margin-bottom: 8px;

}


.table {

    width: 100%;

    border-collapse:
        collapse;

    page-break-inside:
        auto;

}


.table thead {

    display: table-header-group;

}


.table tr {

    page-break-inside:
        avoid;

}


.table th {

    background: #eef5fb;

    color: #174a73;

    padding: 7px 6px;

    border:
        1px solid #d7e1ea;

    font-size: 9px;

}


.table td {

    padding: 7px 6px;

    border:
        1px solid #d7e1ea;

    vertical-align: top;

}


.center {

    text-align: center;

}


.good {

    background: #dcfce7;

    color: #166534;

}


.warning {

    background: #fef3c7;

    color: #92400e;

}


.danger {

    background: #fee2e2;

    color: #991b1b;

}


.badge {

    display: inline-block;

    padding: 3px 7px;

    border-radius: 10px;

}


.small {

    font-size: 8px;

    color: #64748b;

}


.footer {

    position: fixed;

    bottom: -25px;

    left: 0;

    right: 0;

    text-align: center;

    color: #94a3b8;

    font-size: 8px;

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">

<div class="date">

Dicetak:
' .
date('d/m/Y H:i') .
' WIB

</div>


<div class="title">

DETAIL KOMPONEN

</div>


<div class="subtitle">

Inventory & Maintenance System —
PT Garudafood Putra Putri Jaya Tbk

</div>

</div>


<!-- =====================================================
     DETAIL KOMPONEN
===================================================== -->

<div class="component-box">

<table width="100%">

<tr>


<td width="180" valign="top">
';


if (!empty($foto)) {

    $html .= '

    <img
        src="' . $foto . '"
        class="photo"
    >

    ';

} else {

    $html .= '

    <div class="no-photo">

        Tidak ada foto

    </div>

    ';
}


$html .= '

</td>


<td valign="top">


<div class="component-name">

' .
e(
    $komponen['nama_bagian']
    ?? '-'
) .
'

</div>


<table class="info">


<tr>

<td class="label">
Serial Number
</td>

<td>
<b>' .
e(
    $komponen['serial_number']
    ?? '-'
) .
'</b>
</td>

</tr>


<tr>

<td class="label">
Mesin Induk
</td>

<td>' .
e(
    $komponen['nama_mesin']
    ?? '-'
) .
'</td>

</tr>


<tr>

<td class="label">
SN Mesin
</td>

<td>' .
e(
    $komponen['sn_mesin']
    ?? '-'
) .
'</td>

</tr>


<tr>

<td class="label">
Sub Mesin
</td>

<td>' .
e(
    $komponen['nama_sub_mesin']
    ?? '-'
) .
'</td>

</tr>


<tr>

<td class="label">
Area
</td>

<td>' .
e(
    $komponen['nama_area']
    ?? '-'
) .
'</td>

</tr>


<tr>

<td class="label">
Lokasi
</td>

<td>' .
e(
    $komponen['lokasi']
    ?? '-'
) .
'</td>

</tr>


<tr>

<td class="label">
Jenis Komponen
</td>

<td>' .
e($jenisKomponen) .
'</td>

</tr>


<tr>

<td class="label">
Kondisi
</td>

<td>

<span class="condition ' .
$kondisiClass .
'">

' .
e($kondisi) .
'

</span>

</td>

</tr>


</table>


</td>


</tr>


</table>


</div>


<!-- =====================================================
     SPESIFIKASI
===================================================== -->

<div class="section">

SPESIFIKASI TEKNIS

</div>


<table class="table">


<tr>

<td width="25%">
Brand / Merk
</td>

<td width="25%">
' .
e(
    $komponen['brand']
    ?? '-'
) .
'
</td>


<td width="25%">
Tipe
</td>

<td width="25%">
' .
e(
    $komponen['tipe']
    ?? '-'
) .
'
</td>

</tr>


<tr>

<td>
Part Number
</td>

<td>
' .
e(
    $komponen['part_number']
    ?? '-'
) .
'
</td>


<td>
Daya
</td>

<td>
' .
e(
    $komponen['daya']
    ?? '-'
) .
'
</td>

</tr>


<tr>

<td>
IO Address
</td>

<td>
' .
e(
    $komponen['io_address']
    ?? '-'
) .
'
</td>


<td>
IP Address
</td>

<td>
' .
e(
    $komponen['ip_address']
    ?? '-'
) .
'
</td>

</tr>


<tr>

<td>
Input Voltage
</td>

<td>
' .
e(
    $komponen['input_voltage']
    ?? '-'
) .
'
</td>


<td>
Frekuensi Input
</td>

<td>
' .
e(
    $komponen['frekuensi_input']
    ?? '-'
) .
'
</td>

</tr>


<tr>

<td>
Arus Input
</td>

<td>
' .
e(
    $komponen['arus_input']
    ?? '-'
) .
'
</td>


<td>
Output
</td>

<td>
' .
e(
    $komponen['output']
    ?? '-'
) .
'
</td>

</tr>


<tr>

<td>
Frekuensi Output
</td>

<td>
' .
e(
    $komponen['frekuensi_output']
    ?? '-'
) .
'
</td>


<td>
IP Rating
</td>

<td>
' .
e(
    $komponen['ip_rating']
    ?? '-'
) .
'
</td>

</tr>


</table>


<!-- =====================================================
     KETERANGAN
===================================================== -->

<div class="section">

KETERANGAN

</div>


<table class="table">

<tr>

<td>

';


$keterangan =
    $komponen['keterangan']
    ?? '';


if (trim($keterangan) !== '') {

    $html .= nl2br(
        e($keterangan)
    );

} else {

    $html .= '

    <span class="small">
        Tidak ada keterangan tambahan.
    </span>

    ';
}


$html .= '

</td>

</tr>

</table>


<!-- =====================================================
     RIWAYAT MAINTENANCE
===================================================== -->

<div class="section">

RIWAYAT MAINTENANCE

</div>


<table class="table">


<thead>

<tr>

<th width="30">
No
</th>

<th width="80">
Tanggal
</th>

<th>
Jenis
</th>

<th>
Tindakan
</th>

<th>
Teknisi
</th>

<th width="75">
Status
</th>

</tr>

</thead>


<tbody>

';


if (!empty($dataMaintenance)) {

    $no = 1;


    foreach (
        $dataMaintenance
        as $m
    ) {


        $status =
            $m['status']
            ?? 'Pending';


        $class = 'danger';


        if ($status === 'Selesai') {

            $class = 'good';

        } elseif ($status === 'Proses') {

            $class = 'warning';

        }


        $tanggal = '-';


        if (
            !empty(
                $m['tanggal']
            )
        ) {

            $timestamp =
                strtotime(
                    $m['tanggal']
                );


            if (
                $timestamp !== false
            ) {

                $tanggal =
                    date(
                        'd/m/Y H:i',
                        $timestamp
                    );
            }
        }


        $html .= '

        <tr>

        <td class="center">

        ' .
        $no++ .
        '

        </td>


        <td>

        ' .
        e($tanggal) .
        '

        </td>


        <td>

        ' .
        e(
            $m['jenis']
            ?? '-'
        ) .
        '

        </td>


        <td>

        ' .
        nl2br(
            e(
                $m['tindakan']
                ?? '-'
            )
        ) .
        '

        </td>


        <td>

        ' .
        e(
            $m['teknisi']
            ?? '-'
        ) .
        '

        </td>


        <td class="center">


        <span class="badge ' .
        $class .
        '">

        ' .
        e($status) .
        '

        </span>


        </td>


        </tr>

        ';
    }


} else {

    $html .= '

    <tr>

    <td
        colspan="6"
        class="center"
    >

    Belum ada riwayat
    maintenance.

    </td>

    </tr>

    ';
}


$html .= '

</tbody>

</table>


<!-- =====================================================
     FOOTER
===================================================== -->

<div class="footer">

Inventory & Maintenance System —
PT Garudafood Putra Putri Jaya Tbk

</div>


</body>

</html>
';


/* =========================================================
   DOMPDF OPTIONS
========================================================= */

$options = new Options();


$options->set(
    'isHtml5ParserEnabled',
    true
);


$options->set(
    'isRemoteEnabled',
    true
);


$options->set(
    'defaultFont',
    'DejaVu Sans'
);


/*
 * Jangan aktifkan PHP dalam HTML.
 * Tidak diperlukan untuk laporan ini.
 */


$dompdf =
    new Dompdf($options);


/* =========================================================
   LOAD HTML
========================================================= */

$dompdf->loadHtml(
    $html,
    'UTF-8'
);


/* =========================================================
   PAPER
========================================================= */

$dompdf->setPaper(
    'A4',
    'portrait'
);


/* =========================================================
   RENDER
========================================================= */

$dompdf->render();


/* =========================================================
   NAMA FILE
========================================================= */

$namaKomponen =
    $komponen['nama_bagian']
    ?? 'komponen';


$namaFile =
    'Detail_Komponen_' .
    preg_replace(
        '/[^A-Za-z0-9_-]/',
        '_',
        $namaKomponen
    ) .
    '.pdf';


/* =========================================================
   DOWNLOAD
========================================================= */

$dompdf->stream(

    $namaFile,

    [
        'Attachment' => true
    ]

);


exit;

?>