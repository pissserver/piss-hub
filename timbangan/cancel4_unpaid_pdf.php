<?php
session_start();
require_once __DIR__ . "/connection.php";

$userRole = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if (!in_array($userRole, array('spv', 'pp', 'keuangan'), true)) {
    http_response_code(403);
    exit('Akses ditolak.');
}

function unpaidPdfError($status, $message, $databaseError) {
    if ($databaseError !== '') {
        error_log('Unpaid invoice PDF error: ' . $databaseError);
    }
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($message);
}

function unpaidPdfEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$dateNow = $date_now;
$oneYearAgo = date('Y-m-d', strtotime('-1 year', strtotime($dateNow)));
$invoiceId = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!in_array($userRole, array('spv', 'pp'), true)) {
        unpaidPdfError(403, 'Akses ditolak.', '');
    }

    if (
        !isset($_SESSION['unpaid_invoice_csrf']) ||
        !isset($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['unpaid_invoice_csrf']
    ) {
        unpaidPdfError(403, 'Sesi form tidak valid. Muat ulang halaman lalu coba kembali.', '');
    }

    if (
        !isset($_POST['weighing_ids']) ||
        !is_array($_POST['weighing_ids']) ||
        !isset($_POST['reasons']) ||
        !is_array($_POST['reasons']) ||
        count($_POST['weighing_ids']) == 0 ||
        count($_POST['weighing_ids']) != count($_POST['reasons'])
    ) {
        unpaidPdfError(400, 'Pilih tagihan dan isi alasan untuk setiap tagihan.', '');
    }

    $selectedIds = array();
    $invoiceReasons = array();
    foreach ($_POST['weighing_ids'] as $index => $weighingId) {
        if (!is_string($weighingId) || !ctype_digit($weighingId) || (int)$weighingId < 1) {
            unpaidPdfError(400, 'Pilihan tagihan tidak valid.', '');
        }
        if (!array_key_exists($index, $_POST['reasons']) || !is_string($_POST['reasons'][$index])) {
            unpaidPdfError(400, 'Alasan untuk setiap tagihan tidak valid.', '');
        }

        $weighingId = (int)$weighingId;
        $reason = trim($_POST['reasons'][$index]);
        if ($reason === '' || strlen($reason) > 2000) {
            unpaidPdfError(400, 'Isi alasan untuk setiap tagihan (maksimal 500 karakter).', '');
        }
        if (isset($invoiceReasons[$weighingId])) {
            unpaidPdfError(400, 'Tagihan yang sama tidak boleh dipilih lebih dari sekali.', '');
        }

        $selectedIds[] = $weighingId;
        $invoiceReasons[$weighingId] = $reason;
    }

    $idList = implode(',', $selectedIds);
    if (!mysql_query('START TRANSACTION')) {
        unpaidPdfError(500, 'Form tidak dapat disimpan. Silakan coba kembali.', mysql_error());
    }

    $eligibleQuery = mysql_query("
        SELECT w.weighing_id
        FROM weighing w
        WHERE w.weighing_id IN ($idList)
            AND w.date_in BETWEEN '" . mysql_real_escape_string($oneYearAgo) . "' AND '" . mysql_real_escape_string($dateNow) . "'
            AND w.weighing_sts = 'Keluar'
            AND w.weighing_sts6 IS NULL
        FOR UPDATE
    ");
    if (!$eligibleQuery) {
        $databaseError = mysql_error();
        mysql_query('ROLLBACK');
        unpaidPdfError(500, 'Gagal memvalidasi pilihan tagihan.', $databaseError);
    }
    if (mysql_num_rows($eligibleQuery) != count($selectedIds)) {
        mysql_free_result($eligibleQuery);
        mysql_query('ROLLBACK');
        unpaidPdfError(422, 'Satu atau beberapa tagihan sudah tidak tersedia atau sudah diproses. Muat ulang halaman lalu pilih kembali.', '');
    }
    mysql_free_result($eligibleQuery);

    $temporaryCode = 'TMP-' . substr(sha1(uniqid(mt_rand(), true)), 0, 15);
    if (!mysql_query("INSERT INTO unpaid_invoices (code) VALUES ('" . mysql_real_escape_string($temporaryCode) . "')")) {
        $databaseError = mysql_error();
        mysql_query('ROLLBACK');
        unpaidPdfError(500, 'Form tidak dapat disimpan. Silakan coba kembali.', $databaseError);
    }
    $invoiceId = (int)mysql_insert_id();
    if ($invoiceId < 1) {
        mysql_query('ROLLBACK');
        unpaidPdfError(500, 'Form tidak dapat disimpan. Silakan coba kembali.', 'Missing inserted invoice id.');
    }

    $invoiceCode = 'UNPAID-' . sprintf('%09d', $invoiceId);
    if (!mysql_query("UPDATE unpaid_invoices SET code = '" . mysql_real_escape_string($invoiceCode) . "' WHERE id = $invoiceId")) {
        $databaseError = mysql_error();
        mysql_query('ROLLBACK');
        unpaidPdfError(500, 'Form tidak dapat disimpan. Silakan coba kembali.', $databaseError);
    }

    foreach ($selectedIds as $weighingId) {
        $reason = mysql_real_escape_string($invoiceReasons[$weighingId]);
        if (!mysql_query("
            INSERT INTO unpaid_invoice_details (unpaid_invoice_id, weighing_id, reason)
            VALUES ($invoiceId, $weighingId, '$reason')
        ")) {
            $databaseError = mysql_error();
            mysql_query('ROLLBACK');
            unpaidPdfError(500, 'Rincian form tidak dapat disimpan. Silakan coba kembali.', $databaseError);
        }
    }

    if (!mysql_query('COMMIT')) {
        $databaseError = mysql_error();
        mysql_query('ROLLBACK');
        unpaidPdfError(500, 'Form tidak dapat disimpan. Silakan coba kembali.', $databaseError);
    }

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array(
        'invoice_id' => $invoiceId,
        'code' => $invoiceCode
    ));
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['invoice_id']) && ctype_digit((string)$_GET['invoice_id'])) {
    $invoiceId = (int)$_GET['invoice_id'];
    if ($invoiceId < 1) {
        unpaidPdfError(400, 'Kode form tidak valid.', '');
    }
} else {
    unpaidPdfError(400, 'Permintaan form tidak valid.', '');
}

$invoiceQuery = mysql_query("SELECT id, code FROM unpaid_invoices WHERE id = $invoiceId LIMIT 1");
if (!$invoiceQuery) {
    unpaidPdfError(500, 'Data form tidak dapat dibaca.', mysql_error());
}
if (mysql_num_rows($invoiceQuery) != 1) {
    unpaidPdfError(404, 'Form tidak ditemukan.', '');
}
$invoice = mysql_fetch_array($invoiceQuery);

$detailsQuery = mysql_query("
    SELECT d.weighing_id, d.reason, w.nopol, w.merchant_id, w.goods_id,
        w.weight_in, w.weight_out, w.netto, w.date_in, w.date_out,
        w.weighing_sts, w.cost_tonase, m.merchant_name, g.goods_name,
        wm.cost_mitra
    FROM unpaid_invoice_details d
    LEFT JOIN weighing w ON w.weighing_id = d.weighing_id
    LEFT JOIN merchant m ON m.merchant_id = w.merchant_id
    LEFT JOIN goods g ON g.goods_id = w.goods_id
    LEFT JOIN (
        SELECT weighing_id, SUM(cost_bayar) AS cost_mitra
        FROM weighing_mitra
        GROUP BY weighing_id
    ) wm ON wm.weighing_id = w.weighing_id
    WHERE d.unpaid_invoice_id = $invoiceId
    ORDER BY d.id ASC
");
if (!$detailsQuery) {
    unpaidPdfError(500, 'Rincian form tidak dapat dibaca.', mysql_error());
}
if (mysql_num_rows($detailsQuery) == 0) {
    unpaidPdfError(404, 'Rincian form tidak ditemukan.', '');
}

$html = '
<style>
    body { font-family: helvetica; color: #233247; }
    h1 { margin: 15px 0 4px; color: #173b67; font-size: 18pt; text-align: center; text-transform: uppercase; }
    .form-code { margin: 0 0 6px; color: #176d73; font-size: 11pt; font-weight: bold; text-align: center; }
    .report-meta { margin-bottom: 14px; color: #52657d; font-size: 9pt; text-align: center; }
    table { width: 100%; border-collapse: collapse; font-size: 7pt; }
    th { padding: 5px 2px; border: 1px solid #bdc9d6; background-color: #eaf0f6; color: #203650; font-weight: bold; text-align: center; }
    td { padding: 4px 2px; border: 1px solid #d5dde6; vertical-align: middle; }
    .center { text-align: center; }
    .weight { text-align: right; white-space: nowrap; }
    .reason-row td { padding: 5px; background-color: #f5f8fb; }
    .summary-table { width: 42%; margin: 10px 0 0 auto; font-size: 8pt; }
    .summary-table td { padding: 5px 8px; }
    .summary-total td { background-color: #eaf0f6; color: #203650; font-weight: bold; }
</style>
<h1>Form Tagihan Tidak Terbayar</h1>
<div class="form-code">' . unpaidPdfEscape($invoice['code']) . '</div>
<div class="report-meta">Tanggal cetak: ' . date('d M Y', strtotime($dateNow)) . ' &nbsp;|&nbsp; Jumlah tagihan: ' . mysql_num_rows($detailsQuery) . '</div>
<table width="100%" style="width: 100%; border-collapse: collapse; margin-right: 0px; font-size: 7pt; margin-top: 20px;">
    <thead>
        <tr>
            <th width="4%">No</th>
            <th width="9%">No Nota</th>
            <th width="7%">Nopol</th>
            <th width="14%">Pedagang</th>
            <th width="8%">Barang</th>
            <th width="5%">Masuk</th>
            <th width="5%">Keluar</th>
            <th width="5%">Netto</th>
            <th width="6%">Status</th>
            <th width="11%">Tanggal Masuk / Keluar</th>
            <th width="5%">Usia Tagihan</th>
            <th width="7%">Tagihan Tonase</th>
            <th width="7%">Tagihan Mitra</th>
            <th width="7%">Total Tagihan<br>(Tonase + Mitra)</th>
        </tr>
    </thead>
    <tbody>';

$number = 1;
$totalTonase = 0;
$totalMitra = 0;
while ($data = mysql_fetch_array($detailsQuery)) {
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

    $weightIn = $data['weight_in'] !== NULL ? $data['weight_in'] . ' Kg' : '0 Kg';
    $weightOut = $data['weight_out'] !== NULL ? $data['weight_out'] . ' Kg' : '0 Kg';
    $netto = $data['netto'] !== NULL ? $data['netto'] . ' Kg' : '0 Kg';
    $reasonHtml = nl2br(unpaidPdfEscape($data['reason']));
    $costTonase = $data['cost_tonase'] !== NULL ? (float)$data['cost_tonase'] : 0;
    $costMitra = $data['cost_mitra'] !== NULL ? (float)$data['cost_mitra'] : 0;
    $totalTonase += $costTonase;
    $totalMitra += $costMitra;
    $totalCharge = $costTonase + $costMitra;

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
            <td class="center">' . unpaidPdfEscape(format_rupiah($data['cost_tonase'])) . '</td>
            <td class="center">' . unpaidPdfEscape($data['cost_mitra'] !== NULL ? format_rupiah($data['cost_mitra']) : '-') . '</td>
            <td class="center">' . unpaidPdfEscape(format_rupiah($totalCharge)) . '</td>
        </tr>
        <tr class="reason-row">
            <td colspan="14"><strong>Alasan:</strong> ' . $reasonHtml . '</td>
        </tr>';
    $number++;
}

$totalOverall = $totalTonase + $totalMitra;
$html .= '
    </tbody>
</table>
<table class="summary-table">
    <tbody>
        <tr>
            <td>Total Tagihan Tonase</td>
            <td class="weight">' . unpaidPdfEscape(format_rupiah($totalTonase)) . '</td>
        </tr>
        <tr>
            <td>Total Tagihan Mitra</td>
            <td class="weight">' . unpaidPdfEscape(format_rupiah($totalMitra)) . '</td>
        </tr>
        <tr class="summary-total">
            <td>Total Keseluruhan</td>
            <td class="weight">' . unpaidPdfEscape(format_rupiah($totalOverall)) . '</td>
        </tr>
    </tbody>
</table>
<div style="position: relative; width: 100%; height: 18mm; margin-top: 55px; page-break-inside: avoid;">
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
    $html2pdf = new HTML2PDF('L', 'A4', 'fr', array(5, 5, 5, 5));
    $html2pdf->pdf->SetDisplayMode('fullpage');
    $html2pdf->writeHTML($html);
    $html2pdf->Output($invoice['code'] . ' - Form Tagihan Tidak Terbayar.pdf', 'I');
} catch (HTML2PDF_exception $exception) {
    echo $exception;
    exit;
}
?>
