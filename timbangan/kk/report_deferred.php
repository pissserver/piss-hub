<?php
	session_start();
	include "../connection.php";
	include "components/role_session.php";

	$unpaidInvoiceHistoryQuery = mysql_query("
		SELECT h.id, h.code, COUNT(d.id) AS detail_count
		FROM unpaid_invoices h
		LEFT JOIN unpaid_invoice_details d ON d.unpaid_invoice_id = h.id
		GROUP BY h.id, h.code
		ORDER BY h.id DESC
	");
	if (!$unpaidInvoiceHistoryQuery) {
		error_log('Unpaid invoice history query failed: ' . mysql_error());
		http_response_code(500);
		exit('Riwayat form tidak dapat dibaca.');
	}
	$unpaidInvoiceHistory = array();
	while ($history = mysql_fetch_array($unpaidInvoiceHistoryQuery)) {
		$unpaidInvoiceHistory[] = $history;
	}
	
	function headScript(){
		$headScript=file_get_contents("headScript.html");
		return $headScript;
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
	<link rel="icon" href="../img/icon.png">
    <link rel="shortcut icon" href="../img/icon.png" />
    <title>Laporan Tagihan Tidak Terbayar | PISS</title>
	<?php echo headScript(); ?>
	<script src="../dataTables/dataTables.tableTools.css"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <style>
	.deferred-history-panel {
		overflow: hidden;
		border-radius: 15px;
	}

	.deferred-history-heading {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 14px 18px;
		background: linear-gradient(115deg, #176d73, #21888a);
		color: #fff;
	}

	.deferred-history-heading-icon {
		display: inline-flex;
		width: 36px;
		height: 36px;
		align-items: center;
		justify-content: center;
		border: 1px solid rgba(255, 255, 255, .25);
		border-radius: 11px;
		background: rgba(255, 255, 255, .12);
		font-size: 16px;
	}

	.deferred-history-heading-copy {
		flex: 1;
		min-width: 0;
	}

	.deferred-history-heading-copy strong {
		display: block;
		font-size: 15px;
	}

	.deferred-history-heading-copy span {
		display: block;
		margin-top: 2px;
		color: rgba(255, 255, 255, .82);
		font-size: 11px;
	}

	.deferred-history-total {
		padding: 5px 10px;
		border: 1px solid rgba(255, 255, 255, .28);
		border-radius: 999px;
		background: rgba(255, 255, 255, .1);
		font-size: 12px;
		font-weight: 600;
	}

	.deferred-history-list {
		display: grid;
		gap: 10px;
		max-height: 420px;
		overflow-y: auto;
		padding: 2px;
	}

	.deferred-history-item {
		display: grid;
		grid-template-columns: 44px minmax(0, 1fr) auto;
		align-items: center;
		gap: 13px;
		padding: 13px 14px;
		border: 1px solid #e2eaf0;
		border-radius: 14px;
		background: #fff;
		box-shadow: 0 3px 10px rgba(28, 54, 74, .035);
		transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
	}

	.deferred-history-item:hover {
		transform: translateY(-1px);
		border-color: #b8d5d8;
		box-shadow: 0 8px 18px rgba(25, 74, 84, .08);
	}

	.deferred-history-icon {
		display: inline-flex;
		width: 42px;
		height: 42px;
		align-items: center;
		justify-content: center;
		border: 1px solid #f1dada;
		border-radius: 12px;
		background: linear-gradient(145deg, #fff8f8, #fff0ef);
		color: #bd4d4a;
		font-size: 17px;
	}

	.deferred-history-item-copy {
		min-width: 0;
	}

	.deferred-history-item-label {
		margin-bottom: 3px;
		color: #8292a1;
		font-size: 9px;
		font-weight: 700;
		letter-spacing: .12em;
	}

	.deferred-history-code {
		overflow: hidden;
		color: #203b55;
		font-family: Menlo, Monaco, Consolas, monospace;
		font-size: 14px;
		font-weight: 700;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.deferred-history-count {
		margin-top: 5px;
		color: #708294;
		font-size: 11px;
	}

	.deferred-history-count i {
		margin-right: 4px;
		color: #41888b;
	}

	.deferred-history-open {
		display: inline-flex;
		min-height: 40px;
		align-items: center;
		gap: 9px;
		padding: 9px 13px;
		border: 1px solid #176d73;
		border-radius: 10px;
		background: linear-gradient(115deg, #176d73, #21888a);
		box-shadow: 0 4px 10px rgba(23, 109, 115, .14);
		color: #fff;
		font-size: 12px;
		font-weight: 600;
		text-decoration: none;
		transition: box-shadow .18s ease, transform .18s ease;
	}

	.deferred-history-open:hover,
	.deferred-history-open:focus {
		transform: translateY(-1px);
		box-shadow: 0 7px 15px rgba(23, 109, 115, .22);
		color: #fff;
		text-decoration: none;
	}

	.deferred-history-open:focus-visible {
		outline: 3px solid rgba(35, 136, 140, .45);
		outline-offset: 3px;
	}

	.deferred-history-empty {
		padding: 38px 18px;
		border: 1px dashed #cad8e1;
		border-radius: 15px;
		background: rgba(255, 255, 255, .72);
		color: #708294;
		text-align: center;
	}

	.deferred-history-empty i {
		display: block;
		margin-bottom: 11px;
		color: #7dabb0;
		font-size: 28px;
	}

	@media (max-width: 700px) {
		.deferred-history-heading {
			flex-wrap: wrap;
		}

		.deferred-history-total {
			margin-left: 48px;
		}

		.deferred-history-item {
			grid-template-columns: 42px minmax(0, 1fr);
			gap: 10px;
			padding: 12px;
		}

		.deferred-history-open {
			grid-column: 2;
			justify-content: center;
			width: 100%;
		}
	}

	@media (prefers-reduced-motion: reduce) {
		.deferred-history-item,
		.deferred-history-open {
			transition: none;
		}
	}
    </style>
</head>
<body>
    <div id="wrapper">
	<?php include 'components/sidebar.php'; ?>

        <div id="page-wrapper">
			<br /><br><br><br>
            <div class="row">
                <div class="col-lg-12">
					<div id="Area1">
						<div class="alert alert-info" onclick="clearNotif()">
							<center><h3><i class="fa fa-spinner fa-spin"></i> Loading...</h3></center>
						</div>
					</div>
					<input type="hidden" id="value_day1" />
				</div>
				<div class="col-lg-12">
					<div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="	fa fa-file-text-o fa-fw"></i> Rincian Tagihan Tidak Terbayar
                        </div>
                        <div class="panel-body">
							<form class="form-horizontal" id="reportRevenue2" name="reportRevenue2" method="POST" target="_blank">
								<div class="row">
									<div class="col-md-6">
										<div class="form-group" style="padding:10px 15px; margin-top:-10px;">
											<label>Tanggal Akhir</label>
											<input name="date2" max="<?php echo $date_now;?>" type="date" class="form-control" placeholder="DD/MM/YYYY" required
											data-fv-notempty-message="Tanggal Belum Diisi"/>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group" style="padding:10px 15px; margin-top:-10px;">
											<label>Jenis Komoditi</label>
											<select class="form-control" name="commodity" id="commodity" required>
												<option value="">- Pilih Jenis Komoditi -</option>
												<option value="Semua">Semua</option>
												<?php 
													$sql2 = mysql_query("select distinct(merchant_type) from merchant");
													while($d2 = mysql_fetch_array($sql2))
												{?>
													<option value="<?php echo $d2['merchant_type'];?>"><?php echo $d2['merchant_type'];?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group" style="margin-top:5px; margin-right:0px; margin-left:0px">
												<button type="submit" class="btn btn-primary btn-block"  onclick="setAction('pdf')"><i class="fa fa-file-pdf-o fa-lg" style="font-size: 16px;"></i>&nbsp;&nbsp;Print PDF</button>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group" style="margin-top:5px; margin-left:0px; margin-right: 0px">
											<button type="submit" class="btn btn-success btn-block" style="background-color: #008000;" onclick="setAction('excel')"><i class="fa fa-file-excel-o fa-lg" style="font-size: 16px;"></i>&nbsp;&nbsp;Export Excel</button>
										</div>
									</div>
								</div>
							</form>
                        </div>
                    </div>
				</div>
				<div class="col-lg-12">
					<div class="panel panel-default deferred-history-panel">
						<div class="deferred-history-heading">
							<span class="deferred-history-heading-icon" aria-hidden="true"><i class="fa fa-history"></i></span>
							<div class="deferred-history-heading-copy">
								<strong>Riwayat Form Tagihan Tidak Terbayar</strong>
								<span>Arsip form tersimpan, buka kembali PDF kapan saja.</span>
							</div>
							<span class="deferred-history-total"><i class="fa fa-files-o" aria-hidden="true"></i> <?php echo count($unpaidInvoiceHistory); ?> form</span>
						</div>
						<div class="panel-body">
							<?php if (count($unpaidInvoiceHistory) == 0) { ?>
								<div class="deferred-history-empty">
									<i class="fa fa-folder-open-o" aria-hidden="true"></i>
									<strong>Belum ada form tersimpan</strong><br>
									Form yang dibuat akan muncul di sini.
								</div>
							<?php } else { ?>
								<div class="deferred-history-list">
									<?php foreach ($unpaidInvoiceHistory as $history) { ?>
										<div class="deferred-history-item">
											<span class="deferred-history-icon" aria-hidden="true"><i class="fa fa-file-pdf-o"></i></span>
											<div class="deferred-history-item-copy">
												<div class="deferred-history-item-label">FORM TERSIMPAN</div>
												<div class="deferred-history-code"><?php echo htmlspecialchars($history['code'], ENT_QUOTES, 'UTF-8'); ?></div>
												<div class="deferred-history-count"><i class="fa fa-list-ul" aria-hidden="true"></i><?php echo (int)$history['detail_count']; ?> tagihan</div>
											</div>
											<a class="deferred-history-open" target="_blank" rel="noopener" href="../cancel4_unpaid_pdf.php?invoice_id=<?php echo (int)$history['id']; ?>">
												<span>Lihat PDF</span><i class="fa fa-external-link" aria-hidden="true"></i>
											</a>
										</div>
									<?php } ?>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
            </div>
        </div>
    </div>
    
	<!-- jQuery -->
    <script src="../js/jquery.js"></script>
	<script src="../dataTables/jquery.dataTables.js"></script>
	<script src="../dataTables/dataTables.tableTools.js"></script>
	<script src="../dataTables/dataTables.bootstrap.js"></script>
	<!-- highcharts -->
	<script src="../highcharts/js/highcharts.js"></script>
	<script src="../highcharts/js/highcharts-more.js"></script>
	
    <!-- Bootstrap Core JavaScript -->
    <script src="../js/bootstrap.min.js"></script>
    <!-- Metis Menu Plugin JavaScript -->
    <script src="../js/plugins/metisMenu/metisMenu.min.js"></script>
    <!-- Custom Theme JavaScript -->
    <script src="../js/sb-admin-2.js"></script>
	<script>
		$(document).ready(function(){
			$("#notifArea").html(alertLoadProcess);
			$('#Area1').load("../line_deferred.php",function(){clearNotif();});
			//$('#Area2').load("line_omzet_merchant.php",function(){clearNotif();});
			setTimeout('$("html").removeClass("js")',1000); 
			
			//Modal Password
			$('#modalPassword').on('shown.bs.modal', function (e) {
				$("#notifArea").html(alertLoadProcess);
				$("#data_password").load("password.php",function(){clearNotif();});
			})
			//data table Tonase
			$('#tableTonase').dataTable();
			//data table Outstanding
		});
		//Memanggil Modal Outstanding
		function callOutstanding(getDay){
			$("#value_day1").val(getDay);
			$("#body_outstanding").load("../detail_outstanding.php?day="+$("#value_day1").val(),function(){clearNotif();});
			$('#modalOutstanding').modal('show');
		}
		function clearNotif(){$("#notifArea").html("");}
    </script>
	<script>
		function setAction(type) {
			var form1 = document.getElementById("reportRevenue2");
			if (type === 'pdf') {
				form1.action = "../htmltopdf/reportDeferred.php";
				form1.target = "_blank";
			} else if (type === 'excel') {
				form1.action = "../exportExcel/exportReportDeferred.php";
				form1.target = "_self";
			}
		}
	</script>
</body>
</html>