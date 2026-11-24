<?php
    include "../connection.php";
    require_once '../libs/PHPExcel/Classes/PHPExcel.php'; 
    // Load library PHPExcel

	$date1		= '2016-10-01';
	$date2		= $_POST['date2'];
	$commodity	= $_POST['commodity'];
	$date 		= date("d M Y");

    // Query untuk mengambil data
    if($commodity=='Semua'){
        $sql = "select 	w.weighing_id, 
                        m.merchant_id, 
                        w.checker_id, 
                        w.reffer, 
                        w.date_out, 
                        w.date_check, 
                        w.date_pay, 
                        w.nopol, 
                        w.netto, 
                        w.cost_tonase,
                        w.cost, 
                        m.merchant_name as name,
                        m.merchant_type as jenis
                from 	weighing w, 
                        merchant m 
                where 	w.merchant_id = m.merchant_id 
                        AND m.merchant_status = 'Aktif' 
                        and (w.date_check between '".$date1." 00:00:00' and '".$date2." 23:59:59') and
                        (w.date_pay > '".$date2."' || w.date_pay is NULL) and
                        w.weighing_sts != 'Batal' and
                        COALESCE(w.weighing_sts2, '') NOT IN ('Dipecah', 'Digabung') and
                        w.weighing_sts6 = 'Ditangguhkan'";
    }
    else{
        $sql = "select 	w.weighing_id, 
                        m.merchant_id, 
                        w.checker_id, 
                        w.reffer, 
                        w.date_out, 
                        w.date_check, 
                        w.date_pay, 
                        w.nopol, 
                        w.netto, 
                        w.cost_tonase,
                        w.cost, 
                        m.merchant_name as name,
                        m.merchant_type as jenis 
                from 	weighing w, 
                        merchant m 
                where 	w.merchant_id = m.merchant_id
                        AND m.merchant_status = 'Aktif' 
                        and m.merchant_type = '".$commodity."' and 
                        (w.date_check between '".$date1." 00:00:00' and '".$date2." 23:59:59') and
                        (w.date_pay > '".$date2."' || w.date_pay is NULL) and
                        w.weighing_sts != 'Batal' and
                        COALESCE(w.weighing_sts2, '') NOT IN ('Dipecah', 'Digabung') and
                        w.weighing_sts6 = 'Ditangguhkan'";
    }

    $query 	= mysql_query($sql);

    // Buat objek PHPExcel
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    $sheet->setTitle("Rincian Penangguhan Tagihan");

    // Menulis Informasi di Atas Tabel
    $sheet->setCellValue("A1", "RINCIAN PENANGGUHAN TAGIHAN");
    $sheet->setCellValue("A2", "PASAR INDUK SURABAYA SIDOTOPO (PISS)");
    $sheet->setCellValue("A3", "Jenis Pedagang");
    $sheet->setCellValue("A4", "Tanggal");
    $sheet->setCellValue("B3", " : ");
    $sheet->setCellValue("B4", " : ");
    $sheet->setCellValue("C3", $commodity);
    $sheet->setCellValue("C4", date('d M Y', strtotime($date2)));

    // Membuat text bold
    $sheet->getStyle("A1:B4")->getFont()->setBold(true);

    // Meratakan semua teks ke tengah
    $sheet->getStyle("A1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("A2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("B3:B4")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    // Merge cell
    $sheet->mergeCells("A1:H1");
    $sheet->mergeCells("A2:H2");

    // Menulis header
    $sheet->setCellValue("A6", "NO")
          ->setCellValue("B6", "TGL TAGIHAN")
          ->setCellValue("C6", "NOTA TAGIHAN")
          ->setCellValue("D6", "PEDAGANG")
          ->setCellValue("E6", "UMUR TAGIHAN")
          ->setCellValue("F6", "TONASE (KG)")
          ->setCellValue("G6", "TOTAL (RP)")
          ->setCellValue("H6", "KETERANGAN");

    // Mengatur warna latar belakang hijau untuk header
    // $sheet->getStyle('A6:I6')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    // $sheet->getStyle('A6:I6')->getFill()->getStartColor()->setRGB('7FBF7F'); // Warna Hijau

    // Membuat Text Bold
    $sheet->getStyle("A6:H6")->getFont()->setBold(true);

    // Meratakan semua teks ke tengah, kecuali kolom E (Pedagang)
    $sheet->getStyle('A6:H6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $no			= 1;
    $row        = 7;

    // Looping untuk mengambil dan menuliskan data
    while ($data = mysql_fetch_array($query)) {
        $date3 		= new DateTime(date("Y-m-d", strtotime($data['date_check'])));
        $date4 		= new DateTime(date("Y-m-d", strtotime($date2)));
        $diff 		= $date4->diff($date3)->format("%a");
        $reason = executeScalar("select pp_reason from pp_log where pp_target = '".$data['weighing_id']."'");

        $sheet->getStyle('A:H')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

        // Menulis data per baris
        $sheet->setCellValue("A" . $row, $no)
              ->setCellValue("B" . $row, date("d M Y", strtotime($data['date_check'])))
              ->setCellValue("C" . $row, 'TG'.$data['weighing_id'])
              ->setCellValue("D" . $row, $data['name'] . ' (' . $data['jenis'] . ')')
              ->setCellValue("E" . $row, $diff. ' Hari')
              ->setCellValue("F" . $row, $data['netto'])
              ->setCellValue("G" . $row, $data['cost_tonase'])
              ->setCellValue("H" . $row, $reason != '' ? $reason : '-');
        
        // Membuat format menjadi number
        $sheet->getStyle("F" . $row . ":G" . $row)->getNumberFormat()->setFormatCode('#,##0');
            
        // Perhitungan total
        // if ($data['weighing_sts'] != 'Batal' && $data['weighing_sts'] != 'Dipecah' && $data['weighing_sts'] != 'Digabung') {
        //     $weight_in += $data['weight_in'];
        //     $weight_out += $data['weight_out'];
        //     $noncommodity += $data['noncommodity'];
        //     $rafaksi += $data['rafaksi'];
        //     $netto += $data['netto'];
        // }

        $no++;
        $row++;
    }

    // Menulis total pada baris terakhir
    if ($row > 7) {
        $sheet->mergeCells("A" . $row . ":E" . $row);
        $sheet->setCellValue("A" . $row, "TOTAL")
              ->setCellValue("F" . $row, "=SUM(F7:F" . ($row - 1) . ")")
              ->setCellValue("G" . $row, "=SUM(G7:G" . ($row - 1) . ")");
    } else {
        $sheet->mergeCells("A" . $row . ":E" . $row);
        $sheet->setCellValue("A" . $row, "TOTAL")
              ->setCellValue("F" . $row,  0)
              ->setCellValue("G" . $row,  0);
    }

    // Mengatur warna latar belakang kuning untuk baris total
    // $sheet->getStyle("A" . $row . ":I" . $row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    // $sheet->getStyle("A" . $row . ":I" . $row)->getFill()->getStartColor()->setRGB('FFFF00'); // Warna Kuning

    // Membuat Text Bold
    $sheet->getStyle("A" . $row . ":H" . $row)->getFont()->setBold(true);

    // Meratakan teks "TOTAL" ke tengah
    $sheet->getStyle("A" . $row . ":H" . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    // Membuat format menjadi number
    $sheet->getStyle("F" . $row . ":G" . $row)->getNumberFormat()->setFormatCode('#,##0');

    // Menambahkan border pada seluruh tabel
    $sheet->getStyle("A6:H" . $row)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

    // Set header untuk download file Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Rincian Penangguhan Tagihan' . ' (' .$commodity. ')_' . date('d M Y') . '.xls"');
    header('Cache-Control: max-age=0');

    // Menyimpan dan mendownload file Excel
    $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
    $objWriter->save('php://output');
    exit;
?>
