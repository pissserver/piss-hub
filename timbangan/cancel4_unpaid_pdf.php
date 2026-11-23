<?php
session_start();
require_once __DIR__ . "/connection.php";

$userRole = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if (!in_array($userRole, array('spv', 'pp'), true)) {
    http_response_code(403);
    exit('Akses ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['weighing_ids']) || !is_array($_POST['weighing_ids'])) {
    http_response_code(400);
    exit('Pilih setidaknya satu tagihan.');
}

$weighingIds = array();
foreach ($_POST['weighing_ids'] as $weighingId) {
    if (!is_string($weighingId) || !ctype_digit($weighingId) || (int)$weighingId < 1) {
        http_response_code(400);
        exit('Pilihan tagihan tidak valid.');
    }

    $weighingIds[(int)$weighingId] = (int)$weighingId;
}

if (count($weighingIds) == 0) {
    http_response_code(400);
    exit('Pilih setidaknya satu tagihan.');
}

$dateNow = $date_now;
$oneYearAgo = date('Y-m-d', strtotime('-1 year', strtotime($dateNow)));
$idList = implode(',', $weighingIds);
$query = mysql_query("
    SELECT w.*, m.merchant_name, g.goods_name
    FROM weighing w
    LEFT JOIN merchant m ON m.merchant_id = w.merchant_id
    LEFT JOIN goods g ON g.goods_id = w.goods_id
    WHERE w.weighing_id IN ($idList)
        AND w.date_in BETWEEN '$oneYearAgo' AND '$dateNow'
        AND w.weighing_sts = 'Keluar'
        AND w.weighing_sts6 IS NULL
    ORDER BY w.weighing_id DESC
") or die(mysql_error());

if (mysql_num_rows($query) != count($weighingIds)) {
    http_response_code(422);
    exit('Satu atau beberapa tagihan sudah tidak tersedia atau sudah terbayar. Muat ulang halaman lalu pilih kembali.');
}

function unpaidPdfEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$html = '
<style>
	body { font-family: helvetica; color: #233247; }
	h1 { margin:20px 0 5px; color: #173b67; font-size: 18pt; text-align: center; text-transform: uppercase; }
	.report-meta { margin-bottom: 14px; color: #52657d; font-size: 9pt; text-align: center; }
	table { width: 100%; border-collapse: collapse; font-size: 7pt; }
	th { padding: 5px 2px; border: 1px solid #bdc9d6; background-color: #eaf0f6; color: #203650; font-weight: bold; text-align: center; }
	td { padding: 4px 2px; border: 1px solid #d5dde6; vertical-align: middle; }
	.center { text-align: center; }
	.weight { text-align: right; white-space: nowrap; }
</style>
<h1>Form Tagihan Tidak Terbayar</h1>
<div class="report-meta">Tanggal cetak: ' . date('d M Y', strtotime($dateNow)) . ' &nbsp;|&nbsp; Jumlah tagihan: ' . count($weighingIds) . '</div>
<table width="100%" style="width: 100%; border-collapse: collapse; margin-right: 0px; font-size: 7pt; margin-top: 30px; page-break-inside: avoid;">
	<thead>
		<tr>
			<th width="4%">No</th>
			<th width="11%">No Nota</th>
			<th width="9%">Nopol</th>
			<th width="14%">Pedagang</th>
			<th width="11%">Barang</th>
			<th width="7%">Masuk</th>
			<th width="7%">Keluar</th>
			<th width="7%">Netto</th>
			<th width="7%">Status</th>
			<th width="15%">Tanggal Masuk / Keluar</th>
			<th width="8%">Usia Tagihan</th>
		</tr>
	</thead>
	<tbody>';

$number = 1;
while ($data = mysql_fetch_array($query)) {
    $invoiceNumber = 'TG' . sprintf('%07d', $data['weighing_id']);
    $dateInTimestamp = !empty($data['date_in']) ? strtotime($data['date_in']) : false;
    $dateOutTimestamp = !empty($data['date_out']) ? strtotime($data['date_out']) : false;
    $dateIn = $dateInTimestamp !== false ? date('d M Y', $dateInTimestamp) : '-';
    $dateOut = $dateOutTimestamp !== false ? date('d M Y', $dateOutTimestamp) : 'Belum';
    if ($dateOutTimestamp !== false) {
        $ageDays = (int)floor((strtotime($dateNow) - $dateOutTimestamp) / 86400);
        $ageText = max(0, $ageDays) . ' hari';
    } else {
        $ageText = '-';
    }

    $weightIn = $data['weight_in'] != NULL ? $data['weight_in'] . ' Kg' : '0 Kg';
    $weightOut = $data['weight_out'] != NULL ? $data['weight_out'] . ' Kg' : '0 Kg';
    $netto = $data['netto'] != NULL ? $data['netto'] . ' Kg' : '0 Kg';

    $html .= '
		<tr>
			<td class="center">' . $number . '</td>
			<td class="center">' . unpaidPdfEscape($invoiceNumber) . '</td>
			<td class="center">' . unpaidPdfEscape($data['nopol']) . '</td>
			<td>' . unpaidPdfEscape($data['merchant_name']) . '</td>
			<td>' . unpaidPdfEscape($data['goods_name']) . '</td>
			<td class="weight">' . unpaidPdfEscape($weightIn) . '</td>
			<td class="weight">' . unpaidPdfEscape($weightOut) . '</td>
			<td class="weight">' . unpaidPdfEscape($netto) . '</td>
			<td class="center">' . unpaidPdfEscape($data['weighing_sts']) . '</td>
			<td class="center">Masuk: ' . unpaidPdfEscape($dateIn) . '<br>Keluar: ' . unpaidPdfEscape($dateOut) . '</td>
			<td class="center">' . unpaidPdfEscape($ageText) . '</td>
		</tr>';
    $number++;
}

$html .= '
	</tbody>
</table>
<div style="position: relative; width: 100%; height: 18mm; margin-top: 100px; page-break-inside: avoid;">
	<div style="position: absolute; top: 0; left: 10%; width: 35%; text-align: center;">
		Mengajukan,
		<br><br><br>
		( ................................ )
	</div>
	<div style="position: absolute; top: 0; left: 55%; width: 35%; text-align: center;">
		Menyetujui,
		<br><br><br>
		( ................................ )
	</div>
</div>';

require_once dirname(__FILE__) . '/htmltopdf/html2pdf.class.php';
try {
    $html2pdf = new HTML2PDF('P', 'A4', 'fr', array(5, 5, 5, 5));
    $html2pdf->pdf->SetDisplayMode('fullpage');
    $html2pdf->writeHTML($html);
    $html2pdf->Output('Form Tagihan Tidak Terbayar.pdf', 'I');
} catch (HTML2PDF_exception $exception) {
    echo $exception;
    exit;
}
?>
