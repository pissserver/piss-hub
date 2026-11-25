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
	$nextInvoiceId = (int)executeScalar("
		SELECT COALESCE(AUTO_INCREMENT, 1)
		FROM information_schema.TABLES
		WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'unpaid_invoices'
	", 1);
	$nextInvoiceCode = 'UNPAID-' . sprintf('%09d', max(1, $nextInvoiceId));
	if (!isset($_SESSION['unpaid_invoice_csrf'])) {
		$_SESSION['unpaid_invoice_csrf'] = sha1(uniqid(mt_rand(), true) . session_id());
	}
	$unpaidInvoiceHistoryQuery = mysql_query("
		SELECT h.id, h.code, COUNT(d.id) AS detail_count
		FROM unpaid_invoices h
		LEFT JOIN unpaid_invoice_details d ON d.unpaid_invoice_id = h.id
		GROUP BY h.id, h.code
		ORDER BY h.id DESC
	") or die(mysql_error());
	$unpaidInvoiceHistory = array();
	while ($history = mysql_fetch_array($unpaidInvoiceHistoryQuery)) {
		$unpaidInvoiceHistory[] = $history;
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
    <title>Tagihan Tidak Terbayar | PISS</title>
	<?php echo headScript(); ?>
    <link href="../vendor/select2/select2.min.css" rel="stylesheet">
    <style>
		.unpaid-invoice-page-content {
			padding-top: 22px;
		}

		.unpaid-invoice-panel {
			overflow: hidden;
			border: 1px solid #dce6ef;
			border-radius: 18px;
			background: #fff;
			box-shadow: 0 16px 42px rgba(24, 48, 74, .11);
		}

		.unpaid-invoice-panel .panel-heading {
			display: flex;
			align-items: center;
			gap: 14px;
			padding: 18px 22px;
			border: 0;
			background: linear-gradient(112deg, #142f50 0%, #176d73 100%) !important;
			color: #fff;
		}

		.unpaid-invoice-heading-icon {
			display: inline-flex;
			width: 46px;
			height: 46px;
			flex: 0 0 46px;
			align-items: center;
			justify-content: center;
			border: 1px solid rgba(255, 255, 255, .25);
			border-radius: 14px;
			background: rgba(255, 255, 255, .12);
			font-size: 19px;
		}

		.unpaid-invoice-heading-copy {
			min-width: 0;
		}

		.unpaid-invoice-heading-copy h3 {
			margin: 0;
			color: #fff;
			font-size: 18px;
			font-weight: 700;
			line-height: 1.35;
		}

		.unpaid-invoice-heading-copy p {
			margin: 4px 0 0;
			color: rgba(255, 255, 255, .82);
			font-size: 13px;
			line-height: 1.45;
		}

		.unpaid-invoice-heading-badge {
			margin-left: auto;
			padding: 6px 11px;
			border: 1px solid rgba(255, 255, 255, .35);
			border-radius: 999px;
			background: rgba(255, 255, 255, .1);
			color: #fff;
			font-size: 11px;
			font-weight: 700;
			letter-spacing: .08em;
		}

		.unpaid-invoice-panel .panel-body {
			padding: 22px;
			background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
		}

		.unpaid-invoice-history-button {
			display: inline-flex;
			min-height: 46px;
			align-items: center;
			gap: 10px;
			padding: 6px 9px 6px 12px;
			border: 1px solid rgba(255, 255, 255, .28);
			border-radius: 13px;
			background: rgba(255, 255, 255, .1);
			color: #fff;
			font-size: 13px;
			font-weight: 600;
			white-space: nowrap;
			transition: background .2s ease, border-color .2s ease, transform .2s ease;
		}

		.unpaid-invoice-history-button:hover,
		.unpaid-invoice-history-button:focus {
			transform: translateY(-1px);
			border-color: rgba(255, 255, 255, .65);
			background: rgba(255, 255, 255, .18);
			color: #fff;
		}

		.unpaid-invoice-history-button:focus-visible,
		.unpaid-invoice-history-item .btn:focus-visible,
		.unpaid-history-close:focus-visible,
		.unpaid-history-footer-button:focus-visible {
			outline: 3px solid rgba(35, 136, 140, .45);
			outline-offset: 3px;
		}

		.unpaid-invoice-history-button-icon {
			display: inline-flex;
			width: 31px;
			height: 31px;
			align-items: center;
			justify-content: center;
			border: 1px solid rgba(255, 255, 255, .22);
			border-radius: 9px;
			background: rgba(255, 255, 255, .13);
		}

		.unpaid-invoice-history-button-count {
			display: inline-flex;
			min-width: 25px;
			height: 25px;
			align-items: center;
			justify-content: center;
			padding: 0 7px;
			border-radius: 8px;
			background: rgba(255, 255, 255, .16);
			color: #fff;
			font-size: 11px;
			font-weight: 700;
		}

		.unpaid-invoice-code-field {
			position: relative;
			display: grid;
			grid-template-columns: 48px minmax(0, 1fr) auto;
			align-items: center;
			gap: 16px;
			overflow: hidden;
			margin-bottom: 20px;
			padding: 17px 19px;
			border: 1px solid #dce8ed;
			border-radius: 16px;
			background: linear-gradient(112deg, #f3f8fa 0%, #fbfdfe 58%, #f0f8f7 100%);
			box-shadow: 0 7px 20px rgba(27, 57, 78, .055);
		}

		.unpaid-invoice-code-icon {
			display: inline-flex;
			width: 46px;
			height: 46px;
			align-items: center;
			justify-content: center;
			border: 1px solid #cfe2e4;
			border-radius: 14px;
			background: linear-gradient(145deg, #fff, #e6f3f2);
			box-shadow: 0 5px 12px rgba(23, 109, 115, .09);
			color: #176d73;
			font-size: 18px;
		}

		.unpaid-invoice-code-copy {
			min-width: 0;
		}

		.unpaid-invoice-code-copy label {
			display: block;
			margin: 2px 0 3px;
			color: #213d54;
			font-size: 15px;
			font-weight: 700;
		}

		.unpaid-invoice-code-eyebrow {
			color: #73889a;
			font-size: 10px;
			font-weight: 700;
			letter-spacing: .13em;
			text-transform: uppercase;
		}

		.unpaid-invoice-code-copy small {
			display: block;
			color: #718395;
			font-size: 12px;
			line-height: 1.45;
		}

		.unpaid-invoice-code-value {
			display: flex;
			align-items: center;
			gap: 3px;
			padding: 5px;
			border: 1px solid #d8e4e9;
			border-radius: 12px;
			background: rgba(255, 255, 255, .88);
			box-shadow: inset 0 1px 2px rgba(22, 46, 63, .035), 0 4px 11px rgba(22, 46, 63, .04);
		}

		.unpaid-invoice-code-value-tag {
			padding: 7px 9px;
			border-radius: 8px;
			background: #eaf4f4;
			color: #176d73;
			font-size: 10px;
			font-weight: 800;
			letter-spacing: .08em;
		}

		.unpaid-invoice-code-value input {
			width: 188px;
			height: 35px;
			padding: 5px 8px;
			border: 0;
			background: transparent;
			box-shadow: none;
			color: #183c56;
			font-family: Menlo, Monaco, Consolas, monospace;
			font-size: 15px;
			font-weight: 700;
			letter-spacing: .035em;
			text-align: center;
		}

		.unpaid-invoice-code-value input:focus {
			border: 0;
			box-shadow: none;
			outline: 2px solid #23888c;
			outline-offset: 2px;
		}

		.unpaid-invoice-history-dialog {
			max-width: 760px;
		}

		.unpaid-invoice-history-modal-content {
			overflow: hidden;
			border: 1px solid rgba(214, 227, 234, .9);
			border-radius: 22px;
			background: #f7fafc;
			box-shadow: 0 28px 80px rgba(13, 35, 55, .28);
		}

		.unpaid-history-header {
			position: relative;
			display: flex;
			align-items: center;
			gap: 15px;
			overflow: hidden;
			padding: 25px 28px;
			background: linear-gradient(118deg, #102941 0%, #164b5b 58%, #176d73 100%);
			color: #fff;
		}

		.unpaid-history-header:after {
			position: absolute;
			top: -74px;
			right: 74px;
			width: 190px;
			height: 190px;
			border: 1px solid rgba(255, 255, 255, .1);
			border-radius: 50%;
			box-shadow: 0 0 0 23px rgba(255, 255, 255, .035), 0 0 0 48px rgba(255, 255, 255, .025);
			content: "";
			pointer-events: none;
		}

		.unpaid-history-header-icon {
			position: relative;
			z-index: 1;
			display: inline-flex;
			width: 48px;
			height: 48px;
			flex: 0 0 48px;
			align-items: center;
			justify-content: center;
			border: 1px solid rgba(255, 255, 255, .22);
			border-radius: 15px;
			background: rgba(255, 255, 255, .13);
			box-shadow: inset 0 1px rgba(255, 255, 255, .12);
			font-size: 19px;
		}

		.unpaid-history-header-copy {
			position: relative;
			z-index: 1;
			min-width: 0;
		}

		.unpaid-history-eyebrow {
			display: block;
			margin-bottom: 5px;
			color: #a9d8d8;
			font-size: 10px;
			font-weight: 700;
			letter-spacing: .16em;
		}

		.unpaid-history-header h3 {
			margin: 0;
			color: #fff;
			font-size: 20px;
			font-weight: 700;
			line-height: 1.3;
		}

		.unpaid-history-header p {
			margin: 5px 0 0;
			color: rgba(255, 255, 255, .76);
			font-size: 12px;
			line-height: 1.5;
		}

		.unpaid-history-total {
			position: relative;
			z-index: 1;
			display: inline-flex;
			align-items: center;
			gap: 7px;
			margin-left: auto;
			padding: 8px 11px;
			border: 1px solid rgba(255, 255, 255, .2);
			border-radius: 10px;
			background: rgba(255, 255, 255, .1);
			color: #fff;
			font-size: 11px;
			font-weight: 600;
			white-space: nowrap;
		}

		.unpaid-history-total strong {
			font-size: 14px;
		}

		.unpaid-history-close {
			position: relative;
			z-index: 2;
			display: inline-flex;
			width: 34px;
			height: 34px;
			flex: 0 0 34px;
			align-items: center;
			justify-content: center;
			margin: 0 0 0 2px;
			border: 1px solid rgba(255, 255, 255, .2);
			border-radius: 10px;
			background: rgba(255, 255, 255, .09);
			color: #fff;
			opacity: 1;
			text-shadow: none;
			transition: background .18s ease, transform .18s ease;
		}

		.unpaid-history-close:hover {
			transform: rotate(4deg);
			background: rgba(255, 255, 255, .2);
			color: #fff;
		}

		.unpaid-history-body {
			padding: 20px 24px 18px;
			background: #f7fafc;
		}

		.unpaid-invoice-history-list {
			display: grid;
			gap: 10px;
			max-height: 56vh;
			overflow-y: auto;
			padding: 2px 5px 4px 2px;
		}

		.unpaid-invoice-history-item {
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

		.unpaid-invoice-history-item:hover {
			transform: translateY(-1px);
			border-color: #b8d5d8;
			box-shadow: 0 8px 18px rgba(25, 74, 84, .08);
		}

		.unpaid-invoice-history-icon {
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

		.unpaid-invoice-history-item-copy {
			min-width: 0;
		}

		.unpaid-invoice-history-item-label {
			margin-bottom: 3px;
			color: #8292a1;
			font-size: 9px;
			font-weight: 700;
			letter-spacing: .12em;
		}

		.unpaid-invoice-history-code {
			overflow: hidden;
			color: #203b55;
			font-family: Menlo, Monaco, Consolas, monospace;
			font-size: 14px;
			font-weight: 700;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		.unpaid-invoice-history-count {
			margin-top: 5px;
			color: #708294;
			font-size: 11px;
		}

		.unpaid-invoice-history-count i {
			margin-right: 4px;
			color: #41888b;
		}

		.unpaid-invoice-history-item .btn {
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
			transition: box-shadow .18s ease, transform .18s ease;
		}

		.unpaid-invoice-history-item .btn:hover {
			transform: translateY(-1px);
			box-shadow: 0 7px 15px rgba(23, 109, 115, .22);
			color: #fff;
		}

		.unpaid-invoice-history-item .btn i {
			font-size: 11px;
		}

		.unpaid-invoice-history-empty {
			padding: 42px 18px;
			border: 1px dashed #cad8e1;
			border-radius: 15px;
			background: rgba(255, 255, 255, .72);
			color: #708294;
			text-align: center;
		}

		.unpaid-invoice-history-empty i {
			display: block;
			margin-bottom: 11px;
			color: #7dabb0;
			font-size: 28px;
		}

		.unpaid-history-footer {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			padding: 14px 24px;
			border-top: 1px solid #e6edf2;
			background: #fff;
		}

		.unpaid-history-footer-note {
			color: #7a8b99;
			font-size: 11px;
		}

		.unpaid-history-footer-note i {
			margin-right: 5px;
			color: #4b9092;
		}

		.unpaid-history-footer-button {
			min-height: 38px;
			padding: 8px 14px;
			border: 1px solid #d5e0e7;
			border-radius: 9px;
			background: #fff;
			color: #42596d;
			font-size: 12px;
			font-weight: 600;
		}

		.unpaid-history-footer-button:hover {
			border-color: #aac7cc;
			background: #f5f9fa;
			color: #1d5e65;
		}

		.unpaid-invoice-intro {
			display: flex;
			align-items: flex-start;
			gap: 9px;
			margin: 0 0 18px;
			padding: 11px 14px;
			border-left: 3px solid #31979a;
			border-radius: 8px;
			background: #eef7f7;
			color: #3a5667;
			font-size: 13px;
			line-height: 1.5;
		}

		.unpaid-invoice-intro i {
			margin-top: 2px;
			color: #177276;
		}

		.unpaid-invoice-rows {
			display: grid;
			gap: 14px;
			counter-reset: unpaid-invoice;
		}

		.unpaid-invoice-row {
			counter-increment: unpaid-invoice;
			padding: 16px;
			border: 1px solid #dfe7ef;
			border-radius: 14px;
			background: #fff;
			box-shadow: 0 4px 14px rgba(34, 57, 78, .045);
			transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
		}

		.unpaid-invoice-row:hover,
		.unpaid-invoice-row:focus-within {
			border-color: #9dc9ce;
			box-shadow: 0 9px 22px rgba(23, 109, 115, .1);
		}

		.unpaid-invoice-row:focus-within {
			transform: translateY(-1px);
		}

		.unpaid-invoice-field label {
			display: flex;
			min-height: 22px;
			align-items: center;
			gap: 8px;
			margin-bottom: 8px;
			color: #263b55;
			font-size: 13px;
			font-weight: 700;
		}

		.unpaid-invoice-select-label::before {
			display: inline-flex;
			width: 25px;
			height: 25px;
			align-items: center;
			justify-content: center;
			border-radius: 8px;
			background: #e8f3f3;
			color: #176d73;
			content: counter(unpaid-invoice, decimal-leading-zero);
			font-size: 10px;
			font-weight: 700;
		}

		.unpaid-invoice-fields {
			display: grid;
			grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) 44px;
			align-items: center;
			gap: 14px;
		}

		.unpaid-invoice-field {
			min-width: 0;
		}

		.unpaid-invoice-fields .select2-container {
			width: 100% !important;
		}

		.unpaid-invoice-fields .select2-container--default .select2-selection--single {
			height: 44px;
			border: 1px solid #ccd8e4;
			border-radius: 10px;
			background: #fff;
			transition: border-color .18s ease, box-shadow .18s ease;
		}

		.unpaid-invoice-fields .select2-container--default.select2-container--focus .select2-selection--single,
		.unpaid-invoice-reason:focus {
			border-color: #23888c;
			box-shadow: 0 0 0 3px rgba(35, 136, 140, .13);
			outline: 0;
		}

		.unpaid-invoice-fields .select2-container--default .select2-selection--single .select2-selection__rendered {
			padding: 7px 34px 7px 12px;
			color: #263b55;
			line-height: 28px;
		}

		.unpaid-invoice-fields .select2-container--default .select2-selection--single .select2-selection__arrow {
			top: 8px;
			right: 5px;
		}

		.unpaid-invoice-reason {
			min-height: 72px;
			padding: 10px 12px;
			border: 1px solid #ccd8e4;
			border-radius: 10px;
			background: #fff;
			color: #263b55;
			font-size: 13px;
			line-height: 1.5;
			resize: vertical;
			box-shadow: none;
			transition: border-color .18s ease, box-shadow .18s ease;
		}

		.unpaid-invoice-reason::placeholder {
			color: #8998a8;
		}

		.unpaid-invoice-fields .remove-unpaid-invoice {
			width: 44px;
			height: 44px;
			align-self: center;
			border: 1px solid #f0d7d7;
			border-radius: 12px;
			background: #fff8f8;
			color: #a33a3a;
			transition: background .18s ease, border-color .18s ease, color .18s ease;
		}

		.unpaid-invoice-fields .remove-unpaid-invoice:hover:not(:disabled) {
			border-color: #d99191;
			background: #fff0f0;
			color: #8e2525;
		}

		.unpaid-invoice-fields .remove-unpaid-invoice:disabled {
			opacity: .4;
		}

		.unpaid-invoice-actions {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			justify-content: space-between;
			gap: 10px;
			margin-top: 18px;
		}

		.unpaid-invoice-actions .btn {
			min-height: 44px;
			padding: 10px 14px;
			border-radius: 10px;
			font-size: 13px;
			font-weight: 600;
			transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
		}

		.unpaid-invoice-actions .btn:hover:not(:disabled) {
			transform: translateY(-1px);
		}

		.unpaid-invoice-actions .btn:focus-visible,
		.unpaid-invoice-fields .remove-unpaid-invoice:focus-visible {
			outline: 3px solid rgba(35, 136, 140, .35);
			outline-offset: 2px;
		}

		.unpaid-invoice-actions #add-unpaid-invoice {
			border: 1px solid #cbd9e5;
			background: #fff;
			color: #2e4a62;
		}

		.unpaid-invoice-actions #add-unpaid-invoice:hover:not(:disabled) {
			border-color: #8bb9c0;
			background: #f2f9f9;
		}

		.unpaid-invoice-actions .btn-primary {
			padding-right: 18px;
			padding-left: 18px;
			border-color: #176d73;
			background: linear-gradient(110deg, #176d73, #21888a);
			box-shadow: 0 5px 12px rgba(23, 109, 115, .18);
		}

		.unpaid-invoice-actions .btn-primary:hover:not(:disabled) {
			border-color: #125d63;
			background: linear-gradient(110deg, #125d63, #18787b);
			box-shadow: 0 8px 17px rgba(23, 109, 115, .24);
		}

		.unpaid-invoice-actions .btn:disabled {
			cursor: not-allowed;
			opacity: .55;
		}

		.unpaid-invoice-empty {
			margin: 15px 0 0;
			padding: 11px 13px;
			border: 1px solid #f0dfb8;
			border-radius: 10px;
			background: #fff9eb;
			color: #755316;
			font-size: 13px;
			line-height: 1.5;
		}

		.unpaid-invoice-feedback {
			display: none;
			margin-top: 16px;
			padding: 11px 13px;
			border: 1px solid #e6c4c4;
			border-radius: 10px;
			background: #fff5f5;
			color: #8e3030;
			font-size: 13px;
			line-height: 1.5;
		}

		@media (max-width: 700px) {
			.unpaid-invoice-panel .panel-body {
				padding: 16px;
			}

			.unpaid-invoice-heading {
				flex-wrap: wrap;
				gap: 11px;
				padding: 16px;
			}

			.unpaid-invoice-history-button {
				width: 100%;
				justify-content: space-between;
			}

			.unpaid-invoice-code-field {
				grid-template-columns: 42px minmax(0, 1fr);
				gap: 12px;
				padding: 14px;
			}

			.unpaid-invoice-code-icon {
				width: 42px;
				height: 42px;
			}

			.unpaid-invoice-code-value {
				grid-column: 1 / -1;
				justify-content: center;
			}

			.unpaid-invoice-code-value input {
				width: 205px;
			}

			.unpaid-history-header {
				flex-wrap: wrap;
				gap: 12px;
				padding: 20px;
			}

			.unpaid-history-header-copy {
				padding-right: 36px;
			}

			.unpaid-history-total {
				margin-left: 60px;
			}

			.unpaid-history-close {
				position: absolute;
				top: 14px;
				right: 14px;
			}

			.unpaid-history-body {
				padding: 14px 14px 12px;
			}

			.unpaid-invoice-history-item {
				grid-template-columns: 42px minmax(0, 1fr);
				gap: 10px;
				padding: 12px;
			}

			.unpaid-invoice-history-item .btn {
				grid-column: 2;
				justify-content: center;
				width: 100%;
			}

			.unpaid-history-footer {
				padding: 12px 14px;
			}

			.unpaid-invoice-heading-icon {
				width: 40px;
				height: 40px;
				flex-basis: 40px;
				border-radius: 12px;
			}

			.unpaid-invoice-heading-copy h3 {
				font-size: 16px;
			}

			.unpaid-invoice-heading-copy p {
				font-size: 12px;
			}

			.unpaid-invoice-row {
				padding: 13px;
			}

			.unpaid-invoice-actions {
				display: grid;
				grid-template-columns: 1fr;
			}

			.unpaid-invoice-actions .btn {
				width: 100%;
			}

			.unpaid-invoice-fields {
				grid-template-columns: minmax(0, 1fr) 40px;
				gap: 10px;
			}

			.unpaid-invoice-fields .remove-unpaid-invoice {
				width: 40px;
				height: 40px;
				grid-column: 2;
				grid-row: 1 / span 2;
			}

			.unpaid-invoice-heading-badge {
				padding: 5px 8px;
				font-size: 10px;
			}
		}

		@media (max-width: 420px) {
			.unpaid-invoice-heading-badge {
				display: none;
			}

			.unpaid-history-header-icon {
				width: 42px;
				height: 42px;
				flex-basis: 42px;
			}

			.unpaid-history-header h3 {
				font-size: 17px;
			}

			.unpaid-history-footer-note {
				max-width: 62%;
				font-size: 10px;
			}
		}

		@media (prefers-reduced-motion: reduce) {
			.unpaid-invoice-row,
			.unpaid-invoice-history-button,
			.unpaid-invoice-history-item,
			.unpaid-invoice-history-item .btn,
			.unpaid-history-close,
			.unpaid-invoice-fields .select2-container--default .select2-selection--single,
			.unpaid-invoice-reason,
			.unpaid-invoice-fields .remove-unpaid-invoice,
			.unpaid-invoice-actions .btn {
				transition: none;
			}

			.unpaid-invoice-row:focus-within,
			.unpaid-invoice-actions .btn:hover:not(:disabled) {
				transform: none;
			}
		}
    </style>

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
								<p>Pilih tagihan dan simpan form beserta alasannya.</p>
							</div>
							<span class="unpaid-invoice-heading-badge">PDF</span>
							<button type="button" class="btn unpaid-invoice-history-button" data-toggle="modal" data-target="#unpaidInvoiceHistoryModal">
								<span class="unpaid-invoice-history-button-icon"><i class="fa fa-history" aria-hidden="true"></i></span>
								<span>Riwayat Form</span>
								<span class="unpaid-invoice-history-button-count"><?= count($unpaidInvoiceHistory); ?></span>
							</button>
						</div>
						<div class="panel-body">
							<p class="unpaid-invoice-intro"><i class="fa fa-info-circle" aria-hidden="true"></i> Kode form dibuat otomatis saat disimpan. Alasan tersimpan bersama rincian tagihan.</p>
							<form id="unpaid-invoice-form" action="cancel4_unpaid_pdf.php" method="POST">
								<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['unpaid_invoice_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
								<div class="unpaid-invoice-code-field">
									<span class="unpaid-invoice-code-icon"><i class="fa fa-file-text-o" aria-hidden="true"></i></span>
									<div class="unpaid-invoice-code-copy">
										<span class="unpaid-invoice-code-eyebrow">Nomor dokumen otomatis</span>
										<label for="unpaid-invoice-code">Kode form berikutnya</label>
										<small>Kode resmi dibuat saat form berhasil disimpan.</small>
									</div>
									<div class="unpaid-invoice-code-value">
										<span class="unpaid-invoice-code-value-tag">FORM</span>
										<input type="text" id="unpaid-invoice-code" value="<?= htmlspecialchars($nextInvoiceCode, ENT_QUOTES, 'UTF-8'); ?>" readonly aria-readonly="true" aria-label="Kode form berikutnya">
									</div>
								</div>
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
								<div id="unpaid-invoice-feedback" class="unpaid-invoice-feedback" role="alert" aria-live="polite"></div>
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
	<div class="modal fade" id="unpaidInvoiceHistoryModal" tabindex="-1" role="dialog" aria-labelledby="unpaidInvoiceHistoryTitle" aria-hidden="true">
		<div class="modal-dialog modal-lg unpaid-invoice-history-dialog" role="document">
			<div class="modal-content unpaid-invoice-history-modal-content">
				<div class="unpaid-history-header">
					<span class="unpaid-history-header-icon"><i class="fa fa-folder-open-o" aria-hidden="true"></i></span>
					<div class="unpaid-history-header-copy">
						<span class="unpaid-history-eyebrow">ARSIP DOKUMEN</span>
						<h3 id="unpaidInvoiceHistoryTitle">Riwayat Form Tagihan</h3>
						<p>Pilih dokumen untuk membuka kembali PDF tersimpan.</p>
					</div>
					<span class="unpaid-history-total"><i class="fa fa-files-o" aria-hidden="true"></i><strong><?= count($unpaidInvoiceHistory); ?></strong> form</span>
					<button type="button" class="unpaid-history-close" data-dismiss="modal" aria-label="Tutup">
						<i class="fa fa-times" aria-hidden="true"></i>
					</button>
				</div>
				<div class="modal-body unpaid-history-body">
					<?php if (count($unpaidInvoiceHistory) == 0) { ?>
						<div class="unpaid-invoice-history-empty">
							<i class="fa fa-folder-open-o" aria-hidden="true"></i>
							<strong>Belum ada form tersimpan</strong><br>
							Form yang dibuat akan muncul di sini.
						</div>
					<?php } else { ?>
						<div class="unpaid-invoice-history-list">
							<?php foreach ($unpaidInvoiceHistory as $history) { ?>
								<div class="unpaid-invoice-history-item">
									<span class="unpaid-invoice-history-icon"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></span>
									<div class="unpaid-invoice-history-item-copy">
										<div class="unpaid-invoice-history-item-label">FORM TERSIMPAN</div>
										<div class="unpaid-invoice-history-code"><?= htmlspecialchars($history['code'], ENT_QUOTES, 'UTF-8'); ?></div>
										<div class="unpaid-invoice-history-count"><i class="fa fa-list-ul" aria-hidden="true"></i><?= (int)$history['detail_count']; ?> tagihan</div>
									</div>
									<a class="btn btn-primary" target="_blank" rel="noopener" href="cancel4_unpaid_pdf.php?invoice_id=<?= (int)$history['id']; ?>">
										<span>Lihat PDF</span><i class="fa fa-external-link" aria-hidden="true"></i>
									</a>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
				<div class="unpaid-history-footer">
					<span class="unpaid-history-footer-note"><i class="fa fa-lock" aria-hidden="true"></i> Arsip form tersimpan di riwayat.</span>
					<button type="button" class="btn unpaid-history-footer-button" data-dismiss="modal">Selesai</button>
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

			$("#unpaid-invoice-form").on("submit", function(event) {
				event.preventDefault();
				var $form = $(this);
				var $submit = $form.find("button[type='submit']");
				var submitLabel = $submit.html();
				var pdfWindow = window.open("about:blank", "_blank");

				if (!pdfWindow) {
					$("#unpaid-invoice-feedback").text("Izinkan pop-up untuk membuka PDF, lalu coba kembali.").show();
					return;
				}

				$("#unpaid-invoice-feedback").hide().text("");
				$submit.prop("disabled", true).html("<i class='fa fa-spinner fa-spin' aria-hidden='true'></i> Menyimpan form...");
				$.ajax({
					url: $form.attr("action"),
					type: "POST",
					data: $form.serialize(),
					dataType: "json"
				}).done(function(response) {
					if (!response.invoice_id) {
						pdfWindow.close();
						$("#unpaid-invoice-feedback").text("Form tersimpan tetapi kode form tidak diterima. Buka kembali dari riwayat.").show();
						$submit.prop("disabled", false).html(submitLabel);
						return;
					}

					pdfWindow.location = "cancel4_unpaid_pdf.php?invoice_id=" + encodeURIComponent(response.invoice_id);
					window.location.reload();
				}).fail(function(xhr) {
					pdfWindow.close();
					var message = xhr.responseText || "Form tidak dapat disimpan. Periksa koneksi lalu coba kembali.";
					$("#unpaid-invoice-feedback").text(message).show();
					$submit.prop("disabled", false).html(submitLabel);
				});
			});
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