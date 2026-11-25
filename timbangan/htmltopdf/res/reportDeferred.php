<?php
	include "../connection.php";
	//$date1	= $_POST['date1'];
	$date1		= '2016-10-01';
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
	$date2		= $_POST['date2'];
	$commodity	= is_string($_POST['commodity']) ? $_POST['commodity'] : '';
	$date 		= date("d M Y");
?>
<page backtop="8mm" backbottom="10mm" backleft="0mm" backright="0mm">
	<page_header>
        <table style="width:95%; border:0px; margin-top:10px; margin-left:5px; margin-right:5px; font-size:8pt;">
            <tr>
                <td style="text-align: left; width: 50%"></td>
                <td style="text-align: center; width: 25%"></td>
                <td style="text-align: right; width: 25%"></td>
            </tr>
        </table>
    </page_header>
    <page_footer>
        <table style="width: 95%; border: 0px black; margin-top:10px; margin-left:5px; margin-right:5px; font-size:8pt;">
            <tr>
                <td style="text-align: left;width: 50%">Tgl Cetak: <?php echo $date; ?></td>
				<td style="text-align: center;width: 25%"></td>
                <td style="text-align: right;width: 25%">Halaman [[page_cu]]/[[page_nb]]</td>
            </tr>
        </table>
    </page_footer>
    <div style="font-size: 12px; font-weight: bold; text-align: center; margin-top:0px;">
		RINCIAN TAGIHAN TIDAK TERBAYAR<br>PASAR INDUK SURABAYA SIDOTOPO (PISS)
	</div>
    <br/>
	<table style="margin-bottom: 5px;font-size:10pt; width:300px;" >
		<tr>
			<td style="width: 35%;">Jenis Pedagang</td>
			<td style="width: 65%;">: <?php echo $commodity; ?></td>
		</tr>
		<tr>
			<td style="width: 35%;">Tanggal</td>
			<td style="width: 65%;">: <?php echo date("d M Y", strtotime($date2)); ?></td>
		</tr>
	</table>
	<br />
	<table style="border: solid 1px black; border-collapse: collapse; margin-bottom: 5px;font-size:8pt; width:100%; margin-right:0px;" id="tableNonkomoditi">
		<tr>
			<th style="text-align: center;width: 3%;height: 3%;border: solid 1px black;">NO</th>
			<th style="text-align: center;width: 8%;border: solid 1px black;">TGL TAGIHAN</th>
			<th style="text-align: center;width: 8%;border: solid 1px black;">NOPOL</th>
			<th style="text-align: center;width: 9%;border: solid 1px black;">NOTA TAGIHAN</th>
			<th style="text-align: center;width: 15%;border: solid 1px black;">PEDAGANG</th>
			<th style="text-align: center;width: 6%;border: solid 1px black;">UMUR TAGIHAN</th>
			<th style="text-align: center;width: 6%;border: solid 1px black;">TONASE<br>(KG)</th>
			<th style="text-align: center;width: 8%;border: solid 1px black;">TOTAL TONASE<br>(RP)</th>
			<th style="text-align: center;width: 8%;border: solid 1px black;">TOTAL MITRA<br>(RP)</th>
			<th style="text-align: center;width: 11%;border: solid 1px black;">KETERANGAN</th>
		</tr>
		<?php
			$date1Esc = mysql_real_escape_string($date1);
			$date2Esc = mysql_real_escape_string($date2);
			$commodityEsc = mysql_real_escape_string($commodity);

			$sql = '';
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
								wm.cost_bayar as cost_mitra,
								m.merchant_name as name,
								m.merchant_type as jenis
						from 	weighing w
								join merchant m on m.merchant_id = w.merchant_id
								left join weighing_mitra wm on wm.weighing_id = w.weighing_id
						where 	w.merchant_id = m.merchant_id 
								AND m.merchant_status = 'Aktif' 
								and (w.date_check between '".$date1Esc." 00:00:00' and '".$date2Esc." 23:59:59') and
								(w.date_pay > '".$date2Esc."' || w.date_pay is NULL) and
								w.weighing_sts != 'Batal' and
								COALESCE(w.weighing_sts2, '') NOT IN ('Dipecah', 'Digabung') and
								w.weighing_sts6 = 'Ditangguhkan'
						order by w.weighing_id desc";
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
								wm.cost_bayar as cost_mitra,
								m.merchant_name as name,
								m.merchant_type as jenis 
						from 	weighing w
								join merchant m on m.merchant_id = w.merchant_id
								left join weighing_mitra wm on wm.weighing_id = w.weighing_id
						where 	w.merchant_id = m.merchant_id
								AND m.merchant_status = 'Aktif' 
								and m.merchant_type = '".$commodityEsc."' and
								(w.date_check between '".$date1Esc." 00:00:00' and '".$date2Esc." 23:59:59') and
								(w.date_pay > '".$date2Esc."' || w.date_pay is NULL) and
								w.weighing_sts != 'Batal' and
								COALESCE(w.weighing_sts2, '') NOT IN ('Dipecah', 'Digabung') and
								w.weighing_sts6 = 'Ditangguhkan'
						order by w.weighing_id desc";
			}
			// debug_query($sql);
			$query 	= mysql_query($sql);
			if (!$query) {
				error_log('Deferred report PDF query failed: ' . mysql_error());
				http_response_code(500);
				exit('Data laporan tidak dapat dibaca.');
			}
			$no 		= 1;
			while($r= mysql_fetch_array($query)){
				//$wid 		= sprintf("%07d", $r['weighing_id']);
				//$rid 		= sprintf("%07d", $r['reffer']);
				//$year1 		= executeScalar("select date_format(date_out, '%y') from weighing where weighing_id = '".$r['weighing_id']."'");
				//$year2 		= executeScalar("select date_format(date_out, '%y') from weighing where weighing_id = '".$r['weighing_id']."'");

				//Hitung Umur Tagihan
				$date3 		= new DateTime(date("Y-m-d", strtotime($r['date_check'])));
				$date4 		= new DateTime(date("Y-m-d", strtotime($date2)));
				$diff 		= $date4->diff($date3)->format("%a");

				$reason = executeScalar("select pp_reason from pp_log where pp_target = '".$r['weighing_id']."' order by action_time desc limit 1");
				$costMitra = $r['cost_mitra'];
		?>
		<tr>
			<td style="text-align: center;border: solid 1px black;"><?php echo $no; ?></td>
			<td style="text-align: center;border: solid 1px black;"><?php echo date("d M Y", strtotime($r['date_check'])); ?></td>
			<td style="text-align: center;border: solid 1px black;"><?php echo $r['nopol']; ?></td>
			<td style="text-align: center;border: solid 1px black;"><?php echo 'TG'.$r['weighing_id']; ?></td>
			<td style="text-align: left;border: solid 1px black;"><?php echo $r['name'] . ' (' . $r['jenis'] . ')'; ?></td>
			<td style="text-align: center;border: solid 1px black;"><?php echo $diff; ?> Hari </td>
			<td style="text-align: right;border: solid 1px black;"><?php echo format_no($r['netto']); ?></td>
			<td style="text-align: right;border: solid 1px black;"><?php echo format_no($r['cost_tonase']); ?></td>
			<td style="text-align: right;border: solid 1px black;"><?php echo $costMitra != NULL ? format_no($costMitra) : '-'; ?></td>
			<td style="text-align: left;border: solid 1px black;"><?php echo $reason != '' ? $reason : '-'; ?></td>
		</tr>
		<?php 
			$no++;
			}
			if($commodity=='Semua'){
				$cost = executeScalar("	select 	sum(w.cost_tonase) 
										from 	weighing w, 
												merchant m 
										where 	w.merchant_id = m.merchant_id and 
												(w.date_check between '".$date1Esc." 00:00:00' and '".$date2Esc." 23:59:59') and
												m.merchant_status = 'Aktif' and
												(w.date_pay > '".$date2Esc."' || w.date_pay is NULL) and
												w.weighing_sts != 'Batal' and
												COALESCE(w.weighing_sts2, '') NOT IN ('Dipecah', 'Digabung') and
												w.weighing_sts6 = 'Ditangguhkan'");

				$costTotalMitra = executeScalar("	select 	sum(wm.cost_bayar)
										from 	weighing w
										join merchant m on m.merchant_id = w.merchant_id
										left join weighing_mitra wm on wm.weighing_id = w.weighing_id
										where 	(w.date_check between '".$date1Esc." 00:00:00' and '".$date2Esc." 23:59:59') and
												m.merchant_status = 'Aktif' and
												(w.date_pay > '".$date2Esc."' || w.date_pay is NULL) and
												w.weighing_sts != 'Batal' and
												COALESCE(w.weighing_sts2, '') NOT IN ('Dipecah', 'Digabung') and
												w.weighing_sts6 = 'Ditangguhkan'");

				$netto = executeScalar("	select 	sum(w.netto) 
										from 	weighing w, 
												merchant m 
										where 	w.merchant_id = m.merchant_id and 
												(w.date_check between '".$date1Esc." 00:00:00' and '".$date2Esc." 23:59:59') and
												m.merchant_status = 'Aktif' and
												(w.date_pay > '".$date2Esc."' || w.date_pay is NULL) and
												w.weighing_sts != 'Batal' and
												COALESCE(w.weighing_sts2, '') NOT IN ('Dipecah', 'Digabung') and
												w.weighing_sts6 = 'Ditangguhkan'");
			}
			else{
				$cost = executeScalar("	select 	sum(w.cost_tonase)
										from 	weighing w, 
												merchant m 
										where 	w.merchant_id = m.merchant_id and 
												m.merchant_status = 'Aktif' and
												m.merchant_type = '".$commodityEsc."' and
												(w.date_check between '".$date1Esc." 00:00:00' and '".$date2Esc." 23:59:59') and
												(w.date_pay > '".$date2Esc."' || w.date_pay is NULL) and
												w.weighing_sts != 'Batal' and
												COALESCE(w.weighing_sts2, '') NOT IN ('Dipecah', 'Digabung') and
												w.weighing_sts6 = 'Ditangguhkan'");

				$costTotalMitra = executeScalar("	select 	sum(wm.cost_bayar)
										from 	weighing w
										join merchant m on m.merchant_id = w.merchant_id
										left join weighing_mitra wm on wm.weighing_id = w.weighing_id
										where 	m.merchant_status = 'Aktif' and
												m.merchant_type = '".$commodityEsc."' and
												(w.date_check between '".$date1Esc." 00:00:00' and '".$date2Esc." 23:59:59') and
												(w.date_pay > '".$date2Esc."' || w.date_pay is NULL) and
												w.weighing_sts != 'Batal' and
												COALESCE(w.weighing_sts2, '') NOT IN ('Dipecah', 'Digabung') and
												w.weighing_sts6 = 'Ditangguhkan'");

				$netto = executeScalar("	select 	sum(w.netto)
										from 	weighing w, 
												merchant m 
										where 	w.merchant_id = m.merchant_id and 
												m.merchant_status = 'Aktif' and
												m.merchant_type = '".$commodityEsc."' and
												(w.date_check between '".$date1Esc." 00:00:00' and '".$date2Esc." 23:59:59') and
												(w.date_pay > '".$date2Esc."' || w.date_pay is NULL) and
												w.weighing_sts != 'Batal' and
												COALESCE(w.weighing_sts2, '') NOT IN ('Dipecah', 'Digabung') and
												w.weighing_sts6 = 'Ditangguhkan'");
			}
		?>
		<tr>
			<td style="text-align: center;border: solid 1px black;" colspan="6"><b>TOTAL </b></td>
			<td style="text-align: right;border: solid 1px black;font-weight:bold;"><?php echo format_no($netto); ?> </td>
			<td style="text-align: right;border: solid 1px black;font-weight:bold;"> <?php echo format_no($cost); ?></td>
			<td style="text-align: right;border: solid 1px black;font-weight:bold;"> <?php echo format_no($costTotalMitra); ?></td>
			<td style="text-align: right;border: solid 1px black;font-weight:bold;"></td>
		</tr>
	</table>
	<br />Keterangan:
</page>