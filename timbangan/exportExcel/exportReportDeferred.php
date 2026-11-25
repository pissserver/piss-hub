<?php
include "../connection.php";
require_once '../libs/PHPExcel/Classes/PHPExcel.php';

if (
    $_SERVER['REQUEST_METHOD'] != 'POST' ||
    !isset($_POST['date2']) ||
    !isset($_POST['commodity']) ||
    !is_string($_POST['date2']) ||
    !is_string($_POST['commodity']) ||
    $_POST['commodity'] === '' ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date2']) ||
    !checkdate((int)substr($_POST['date2'], 5, 2), (int)substr($_POST['date2'], 8, 2), (int)substr($_POST['date2'], 0, 4))
) {
    http_response_code(400);
    exit('Tanggal atau jenis komoditi tidak valid.');
}

$date1 = '2016-10-01';
$date2 = $_POST['date2'];
$commodity = $_POST['commodity'];
$date1Esc = mysql_real_escape_string($date1);
$date2Esc = mysql_real_escape_string($date2);
$commodityEsc = mysql_real_escape_string($commodity);

$commodityFilter = '';
if ($commodity != 'Semua') {
    $commodityFilter = " AND m.merchant_type = '" . $commodityEsc . "'";
}

$sql = "
    SELECT w.weighing_id, w.date_out, w.date_check, w.date_pay, w.nopol,
        w.netto, w.cost_tonase, w.weighing_sts2,
        wm.cost_bayar AS cost_mitra,
        m.merchant_name AS name, m.merchant_type AS jenis
    FROM weighing w
    JOIN merchant m ON m.merchant_id = w.merchant_id
    LEFT JOIN weighing_mitra wm ON wm.weighing_id = w.weighing_id
    WHERE m.merchant_status = 'Aktif'
        AND (w.date_check BETWEEN '" . $date1Esc . " 00:00:00' AND '" . $date2Esc . " 23:59:59')
        AND (w.date_pay > '" . $date2Esc . "' OR w.date_pay IS NULL)
        AND w.weighing_sts != 'Batal'
        AND COALESCE(w.weighing_sts2, '') NOT IN ('Dipecah', 'Digabung')
        AND w.weighing_sts6 = 'Ditangguhkan'
        $commodityFilter
    ORDER BY w.weighing_id DESC
";
$query = mysql_query($sql);
if (!$query) {
    error_log('Deferred report Excel query failed: ' . mysql_error());
    http_response_code(500);
    exit('Data laporan tidak dapat dibaca.');
}

$excel = new PHPExcel();
$sheet = $excel->getActiveSheet();
$sheet->setTitle("Rincian Tagihan");
$sheet->setShowGridlines(false);
$sheet->getPageSetup()
    ->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE)
    ->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

$sheet->setCellValue("A1", "RINCIAN TAGIHAN TIDAK TERBAYAR");
$sheet->setCellValue("A2", "PASAR INDUK SURABAYA SIDOTOPO (PISS)");
$sheet->setCellValue("A3", "Jenis Pedagang");
$sheet->setCellValue("A4", "Tanggal");
$sheet->setCellValue("B3", ":");
$sheet->setCellValue("B4", ":");
$sheet->setCellValue("C3", $commodity);
$sheet->setCellValue("C4", date('d M Y', strtotime($date2)));
$sheet->getStyle("A1:C4")->getFont()->setBold(true);
$sheet->getStyle("A1:J2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->mergeCells("A1:J1");
$sheet->mergeCells("A2:J2");

$headers = array(
    "NO",
    "TGL TAGIHAN",
    "NOPOL",
    "NOTA TAGIHAN",
    "PEDAGANG",
    "UMUR TAGIHAN",
    "TONASE (KG)",
    "TOTAL TONASE (RP)",
    "TOTAL MITRA (RP)",
    "KETERANGAN"
);
foreach ($headers as $index => $header) {
    $sheet->setCellValueByColumnAndRow($index, 6, $header);
}
$sheet->getStyle("A6:J6")->getFont()->setBold(true);
$sheet->getStyle("A6:J6")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$columnWidths = array(7, 16, 13, 17, 28, 16, 15, 20, 20, 42);
foreach ($columnWidths as $index => $width) {
    $sheet->getColumnDimensionByColumn($index)->setWidth($width);
}

$number = 1;
$row = 7;
while ($data = mysql_fetch_array($query)) {
    $dateCheck = date('Y-m-d', strtotime($data['date_check']));
    $dateAtEnd = date('Y-m-d', strtotime($date2));
    $age = (int)(new DateTime($dateCheck))->diff(new DateTime($dateAtEnd))->format('%a');
    $reason = executeScalar("
        SELECT pp_reason
        FROM pp_log
        WHERE pp_target = '" . (int)$data['weighing_id'] . "'
        ORDER BY action_time DESC
        LIMIT 1
    ", '');

    $sheet->setCellValue("A" . $row, $number)
        ->setCellValue("B" . $row, date("d M Y", strtotime($data['date_check'])))
        ->setCellValue("C" . $row, $data['nopol'])
        ->setCellValue("D" . $row, 'TG' . $data['weighing_id'])
        ->setCellValue("E" . $row, $data['name'] . ' (' . $data['jenis'] . ')')
        ->setCellValue("F" . $row, $age . ' Hari')
        ->setCellValue("G" . $row, $data['netto'])
        ->setCellValue("H" . $row, $data['cost_tonase'])
        ->setCellValue("I" . $row, $data['cost_mitra'] !== NULL ? $data['cost_mitra'] : '-')
        ->setCellValue("J" . $row, $reason != '' ? $reason : '-');
    $sheet->getStyle("A" . $row . ":D" . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("F" . $row . ":I" . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("E" . $row . ":E" . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle("J" . $row)->getAlignment()->setWrapText(true);
    $sheet->getStyle("G" . $row . ":I" . $row)->getNumberFormat()->setFormatCode('#,##0');

    $number++;
    $row++;
}

$lastDataRow = $row - 1;
$sheet->mergeCells("A" . $row . ":F" . $row);
$sheet->setCellValue("A" . $row, "TOTAL");
if ($lastDataRow >= 7) {
    $sheet->setCellValue("G" . $row, "=SUM(G7:G" . $lastDataRow . ")");
    $sheet->setCellValue("H" . $row, "=SUM(H7:H" . $lastDataRow . ")");
    $sheet->setCellValue("I" . $row, "=SUM(I7:I" . $lastDataRow . ")");
} else {
    $sheet->setCellValue("G" . $row, 0);
    $sheet->setCellValue("H" . $row, 0);
    $sheet->setCellValue("I" . $row, 0);
}
$sheet->getStyle("A" . $row . ":J" . $row)->getFont()->setBold(true);
$sheet->getStyle("A" . $row . ":J" . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("G" . $row . ":I" . $row)->getNumberFormat()->setFormatCode('#,##0');
$sheet->getStyle("A6:J" . $row)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Rincian Tagihan Tidak Terbayar (' . $commodity . ')_' . date('d M Y') . '.xls"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
$objWriter->save('php://output');
exit;
?>
