<?php
	session_start();
	include "../connection.php";
	include "components/role_session.php";

	function headScript(){
		$headScript=file_get_contents("headScript.html");
		return $headScript;
	}

	$one_year_ago = date('Y-m-d', strtotime('-1 year', strtotime($date_now)));
	$unpaidInvoiceQuery = mysql_query("
		SELECT w.weighing_id, w.nopol, w.date_out, m.merchant_name, g.goods_name
		FROM weighing w
		LEFT JOIN merchant m ON m.merchant_id = w.merchant_id
		LEFT JOIN goods g ON g.goods_id = w.goods_id
		WHERE w.date_in BETWEEN '$one_year_ago' AND '$date_now'
			AND w.weighing_sts = 'Keluar'
			AND w.weighing_sts6 IS NULL
		ORDER BY w.weighing_id DESC
	") or die(mysql_error());
	$unpaidInvoices = array();
	while ($invoice = mysql_fetch_array($unpaidInvoiceQuery)) {
		$dateOutTimestamp = !empty($invoice['date_out']) ? strtotime($invoice['date_out']) : false;
		$invoiceLabel = 'TG' . sprintf('%07d', $invoice['weighing_id']);
		$invoiceLabel .= ' | ' . $invoice['nopol'];
		$invoiceLabel .= ' | ' . $invoice['merchant_name'];
		$invoiceLabel .= ' | ' . $invoice['goods_name'];
		$invoiceLabel .= ' | Keluar ' . ($dateOutTimestamp !== false ? date('d M Y', $dateOutTimestamp) : 'Belum');
		$unpaidInvoices[] = array(
			'id' => $invoice['weighing_id'],
			'label' => $invoiceLabel
		);
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
    <title>Pembatalan Kendaraan Keluar | PISS</title>
	<?php echo headScript(); ?>
    <link href="../vendor/select2/select2.min.css" rel="stylesheet">
    <link href="../css/unpaid-invoice-form.css" rel="stylesheet">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
	<script src="../js/jquery.min.js"></script>
	<script>
		/* hide all elements & show preloader*/
		document.documentElement.className += 'js';

		/* display variabel */
		var alertLoadProcess = "<span class='alert alert-danger' onclick='clearNotif()'><i class='fa fa-spinner fa-spin'></i> Loading...</span>";
    </script>
</head>
<body>
    <div id="wrapper">
	<?php include 'components/sidebar.php'; ?>

        <div id="page-wrapper">
			<br><br><br><br>
            <div class="row">
                <div class="col-lg-12 unpaid-invoice-page-content">
					<div class="panel panel-primary unpaid-invoice-panel">
						<div class="panel-heading unpaid-invoice-heading">
							<span class="unpaid-invoice-heading-icon"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></span>
							<div class="unpaid-invoice-heading-copy">
								<h3>Buat Form Tagihan Tidak Terbayar</h3>
								<p>Pilih tagihan dan siapkan alasan untuk dicantumkan pada PDF.</p>
							</div>
							<span class="unpaid-invoice-heading-badge">PDF</span>
						</div>
						<div class="panel-body">
							<p class="unpaid-invoice-intro"><i class="fa fa-info-circle" aria-hidden="true"></i> Alasan akan tampil pada PDF dan tidak disimpan ke database.</p>
							<form id="unpaid-invoice-form" action="cancel4_unpaid_pdf.php" method="POST" target="_blank">
								<div id="unpaid-invoice-rows" class="unpaid-invoice-rows">
									<div class="unpaid-invoice-row">
										<div class="unpaid-invoice-fields">
											<div class="unpaid-invoice-field">
												<label class="unpaid-invoice-select-label" for="unpaid-invoice-select-1">Tagihan 1</label>
												<select class="form-control unpaid-invoice-select" id="unpaid-invoice-select-1" name="weighing_ids[]" required <?= count($unpaidInvoices) == 0 ? 'disabled' : ''; ?>>
													<option value="">Pilih tagihan belum terbayar</option>
													<?php foreach ($unpaidInvoices as $invoice) { ?>
														<option value="<?= (int)$invoice['id']; ?>"><?= htmlspecialchars($invoice['label'], ENT_QUOTES, 'UTF-8'); ?></option>
													<?php } ?>
												</select>
											</div>
											<div class="unpaid-invoice-field">
												<label for="unpaid-invoice-reason-1">Alasan</label>
												<textarea class="form-control unpaid-invoice-reason" id="unpaid-invoice-reason-1" name="reasons[]" rows="2" maxlength="500" required placeholder="Jelaskan alasan tagihan belum terbayar"></textarea>
											</div>
											<button type="button" class="btn btn-default remove-unpaid-invoice" aria-label="Hapus tagihan" disabled><i class="fa fa-trash" aria-hidden="true"></i></button>
										</div>
									</div>
								</div>
								<div class="unpaid-invoice-actions">
									<button type="button" class="btn btn-default" id="add-unpaid-invoice" <?= count($unpaidInvoices) == 0 ? 'disabled' : ''; ?>><i class="fa fa-plus" aria-hidden="true"></i> Tambah tagihan</button>
									<button type="submit" class="btn btn-primary" <?= count($unpaidInvoices) == 0 ? 'disabled' : ''; ?>><i class="fa fa-file-pdf-o" aria-hidden="true"></i> Buat Form Tagihan Tidak Terbayar</button>
								</div>
								<?php if (count($unpaidInvoices) == 0) { ?>
									<p class="unpaid-invoice-empty">Tidak ada tagihan belum terbayar dalam satu tahun terakhir.</p>
								<?php } ?>
							</form>
						</div>
					</div>
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-truck fa-fw"></i> Data Tagihan/Nota
                        </div>
                        <div class="panel-body">
							<div class="row">
								<div class="col-md-12">
									<div id="Area1">
										<div class="alert alert-info" onclick="clearNotif()">
											<center><h3><i class="fa fa-spinner fa-spin"></i> Loading...</h3></center>
										</div>
									</div>
								</div>
							</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
	
	<!-- Modal Masuk -->
	<div id="modalIn" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Meload modal "cancel_modal.php" file -->
            </div>
        </div>
    </div>

	<!-- Modal Batal -->
	<div id="modalCancelOut" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Meload modal "cancel_modal.php" file -->
            </div>
        </div>
    </div>
	
	<!-- Modal Password -->
	<div class="modal fade" id="modalPassword" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><img src='../assets/image/Delete.png' width='25' height='25'/></button>
					<h4 id="header_password">Ganti Password</h4>
				</div>
				<div class="modal-body">
					<div id="data_password" style="margin-bottom: 0px;">
						<div class="alert alert-info" onclick="clearNotif()"><h3><i class="fa fa-spinner fa-spin"></i> Loading...</h3></div>
					</div>
					<input type="hidden" id="value_id" />
				</div>
				<div class="modal-footer">
				</div>
			</div>
		</div>
	</div>
	<script>
		$(document).ready(function(){
			$("#notifArea").html(alertLoadProcess);
		$('#Area1').load("cancel_table4.php",function(){clearNotif();});
			setTimeout('$("html").removeClass("js")',100); 
			
			// Auto Refresh Page
			/*$.ajaxSetup({ cache: false }); // This part addresses an IE bug.  without it, IE will only load the first number and will never refresh
			setInterval(function() {
				$('#weight_in').load('random.php');
			}, 1000);*/
			
			//Modal Password
			$('#modalPassword').on('shown.bs.modal', function (e) {
				$("#notifArea").html(alertLoadProcess);
				$("#data_password").load("password.php",function(){clearNotif();});
			})
		});
		function clearNotif(){$("#notifArea").html("");}
    </script>
    <!-- jQuery -->
    <script src="../js/jquery.js"></script>
	<script src="../vendor/select2/select2.min.js"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="../js/bootstrap.min.js"></script>
    <!-- Metis Menu Plugin JavaScript -->
    <script src="../js/plugins/metisMenu/metisMenu.min.js"></script>
    <!-- Custom Theme JavaScript -->
    <script src="../js/sb-admin-2.js"></script>
	<script>
		$(document).ready(function() {
			function initializeInvoiceSelect($select) {
				$select.select2({
					placeholder: "Pilih tagihan belum terbayar",
					width: "100%"
				});
			}

			var nextInvoiceRowId = 1;
			var invoiceOptions = $("#unpaid-invoice-select-1 option").clone();

			function refreshInvoiceRows() {
				var selectedInvoices = {};
				var $selects = $(".unpaid-invoice-select");

				$selects.each(function() {
					if (this.value) selectedInvoices[this.value] = true;
				});

				$selects.each(function(index) {
					var currentValue = this.value;
					var $row = $(this).closest(".unpaid-invoice-row");
					$row.find(".unpaid-invoice-select-label").text("Tagihan " + (index + 1)).attr("for", this.id);
					$row.find(".remove-unpaid-invoice").prop("disabled", $selects.length == 1);

					$(this).find("option").each(function() {
						this.disabled = this.value != "" &&
							selectedInvoices[this.value] &&
							this.value != currentValue;
					});
					$(this).trigger("change.select2");
				});
			}

			initializeInvoiceSelect($("#unpaid-invoice-select-1"));

			$("#add-unpaid-invoice").on("click", function() {
				nextInvoiceRowId++;
				var selectId = "unpaid-invoice-select-" + nextInvoiceRowId;
				var reasonId = "unpaid-invoice-reason-" + nextInvoiceRowId;
				var $row = $("<div class='unpaid-invoice-row'></div>");
				var $fields = $("<div class='unpaid-invoice-fields'></div>");
				var $selectField = $("<div class='unpaid-invoice-field'></div>");
				var $reasonField = $("<div class='unpaid-invoice-field'></div>");
				var $label = $("<label class='unpaid-invoice-select-label'></label>").attr("for", selectId);
				var $reasonLabel = $("<label>Alasan</label>").attr("for", reasonId);
				var $select = $("<select class='form-control unpaid-invoice-select' required></select>")
					.attr("id", selectId)
					.attr("name", "weighing_ids[]")
					.append(invoiceOptions.clone());
				var $reason = $("<textarea class='form-control unpaid-invoice-reason' name='reasons[]' rows='2' maxlength='500' required placeholder='Jelaskan alasan tagihan belum terbayar'></textarea>")
					.attr("id", reasonId);
				var $removeButton = $("<button type='button' class='btn btn-default remove-unpaid-invoice' aria-label='Hapus tagihan'><i class='fa fa-trash' aria-hidden='true'></i></button>");

				$selectField.append($label).append($select);
				$reasonField.append($reasonLabel).append($reason);
				$fields.append($selectField).append($reasonField).append($removeButton);
				$row.append($fields);
				$("#unpaid-invoice-rows").append($row);
				initializeInvoiceSelect($select);
				refreshInvoiceRows();
			});

			$(document).on("click", ".remove-unpaid-invoice", function() {
				if ($(".unpaid-invoice-row").length == 1) return;
				var $select = $(this).closest(".unpaid-invoice-row").find(".unpaid-invoice-select");
				$select.select2("destroy");
				$(this).closest(".unpaid-invoice-row").remove();
				refreshInvoiceRows();
			});

			$(document).on("change", ".unpaid-invoice-select", refreshInvoiceRows);
		});
	</script>
	<!-- Sweet Alert -->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js"></script>
	<?php
		// Menampilkan SweetAlert jika session success ada dan bernilai true
		if (isset($_SESSION['success'])) {
			$title = $_SESSION['title'];
			$message = $_SESSION['message'];
			$icon = $_SESSION['success'] ? 'success' : 'error';
			echo "<script>
				Swal.fire({
					title: '$title',
					text: '$message',
					icon: '$icon'
				});
			</script>";
			unset($_SESSION['success']);
			unset($_SESSION['message']);
		}
	?>
</body>
</html>