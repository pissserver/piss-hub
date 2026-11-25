<?php
	include "../connection.php";
?>
<!-- jquery -->
<script src="../dataTables/jquery.dataTables.js"></script>
<script src="../dataTables/dataTables.tableTools.js"></script>
<script src="../dataTables/dataTables.bootstrap.js"></script>
<script>
	$(document).ready(function() {
	//data table
	$('#transactionView').dataTable({"aLengthMenu": [[10, 50, 100, -1], [10, 50, 100, "All"]]});
	});
</script>
<!--<div class="table-responsive">-->
<table id="transactionView" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%" style="font-size: 13px;">
	<thead>
		<tr>
			<th style="text-align: center; vertical-align: middle; width:3%;">NO</th>
			<th style="text-align: center; vertical-align: middle; width:5%;">NO NOTA</th>
			<th style="text-align: center; vertical-align: middle; width:5%;">NOPOL</th>
			<th style="text-align: center; vertical-align: middle; width:15%;">PEDAGANG</th>
			<th style="text-align: center; vertical-align: middle; width:7%;">BARANG</th>
			<th style="text-align: center; vertical-align: middle; width:5%;">MASUK</th>
			<th style="text-align: center; vertical-align: middle; width:5%;">KELUAR</th>
			<th style="text-align: center; vertical-align: middle; width:5%;">NETTO</th>
			<th style="text-align: center; vertical-align: middle; width:5%;">STATUS</th>
			<th style="vertical-align: middle; text-align: center; width:8%;">TOTAL TAGIHAN</th>
			<th style="vertical-align: middle; text-align: center; width:8%;">TANGGAL <br>MASUK & KELUAR</th>
			<th style="text-align: center; vertical-align: middle; width:5%;">AKSI</th>
		</tr>
	</thead>
	<tbody>
		<?php
			$no		= 1;
			$one_year_ago = date('Y-m-d', strtotime('-1 year', strtotime($date_now)));
			$query 	= mysql_query("select * from weighing where (date_in between '$one_year_ago' and '$date_now') and weighing_sts = 'Keluar' and weighing_sts6 IS NULL order by weighing_id desc");
			while($data=mysql_fetch_array($query)){
			$merchant = executeScalar("select merchant_name from merchant where merchant_id = '".$data['merchant_id']."'");
			$goods 	= executeScalar("select goods_name from goods where goods_id = '".$data['goods_id']."'");
			$sama 	= executeScalar("select count(transportation_id) from transportation where transportation_nopol = '".$data['nopol']."'");
			$costMitra = executeScalar("select sum(cost_bayar) from weighing_mitra where weighing_id = '".$data['weighing_id']."'");
		?>
		<tr>
			<td style="vertical-align: middle; text-align:center;"><?php echo $no; ?></td>
			<td style="vertical-align: middle; text-align:center;">
				<?php 
					$wid 	= sprintf("%07d", $data['weighing_id']);
					$status = executeScalar("select weighing_sts from weighing where weighing_id = '".$data['weighing_id']."'");
					if($status=='Masuk'||$status=='Batal'){ 
						//$year = executeScalar("select date_format(date_in, '%y') from weighing where weighing_id = '".$data['cashier_target']."'"); ?>
						
						<div style="vertical-align: middle; background-color: #428bca; color: #fff; padding: 3px; border-radius: 5px; margin: 5px auto 5px auto; width: 110px;">		
							<?php echo 'TB'.$wid; ?>
						</div>
				<?php }
					elseif($status=='Keluar'||$status=='Lunas'){
						//$year = executeScalar("select date_format(date_in, '%y') from weighing where weighing_id = '".$data['cashier_target']."'"); ?>
						
						<div style="vertical-align: middle; background-color: green; color: #fff; padding: 3px; border-radius: 5px; margin: 5px auto 5px auto; width: 110px;">		
							<?php echo 'TG'.$wid; ?>
						</div>
					<?php } 
				?>
			</td>
			<td style="vertical-align: middle; text-align: center;"><?php echo $data['nopol']; ?></td>
			<td style="vertical-align: middle; text-align: left"><?php echo $merchant; ?></td>
			<td style="vertical-align: middle; text-align: center;"><?php echo $goods; ?></td>
			<td style="vertical-align: middle; text-align:center;"><?php echo $data['weight_in'] != NULL ? $data['weight_in'].' Kg' : '0 Kg'; ?></td>
			<td style="vertical-align: middle; text-align:center;"><?php echo $data['weight_out'] != NULL ? $data['weight_out'].' Kg' : '0 Kg'; ?></td>
			<td style="vertical-align: middle; text-align:center;"><?php echo $data['netto'] != NULL ? $data['netto'].' Kg' : '0 Kg'; ?></td>
			<td style="vertical-align: middle; text-align:center;"><?php echo $data['weighing_sts']; ?></td>
			<td style="vertical-align: middle; text-align:center;">
				<?php
					$totalTagihan = (float)$data['cost_tonase'] + (float)$costMitra;
					echo 'Rp. ' . number_format($totalTagihan, 0, ',', '.');
				?>
			</td>
			<td style="vertical-align: middle; text-align:center;">
				<div style="background-color: #428bca; color: #fff; padding: 3px; border-radius: 5px; margin: 5px auto 0 auto; width: 130px;">		
					<i class="fa fa-arrow-circle-down fa-fw"></i>&nbsp;<?php echo date("d M Y", strtotime($data['date_in'])); ?>
				</div>
				<div style="background-color:rgb(202, 66, 66); color: #fff; padding: 3px; border-radius: 5px; margin: 5px auto 0 auto; width: 130px;">		
					<i class="fa fa-arrow-circle-up fa-fw"></i>&nbsp;<?php echo $data['date_out'] != NULL ? date("d M Y", strtotime($data['date_out'])) : 'Belum'; ?>
				</div>
			</td>
			<td style="text-align: center; vertical-align: middle;">
				<!-- Button HTML (to Trigger Modal) -->
				<?php
					echo "<a class='btn btn-xs btn-outline btn-danger modalCancelOut' data-toggle='modal' data-target='#modalCancelOut' data-id='" . $data['weighing_id'] . "' data-status='Tangguhkan'><i class='fa fa-close fa-fw'></i>&nbsp;Tidak Terbayar</a>";	
				?>
			</td>
		</tr>
		<?php 
			$no++;
			} ?>
	</tbody>
</table>

<script>
	$(document).ready(function() {
		$('body').on('click', '.modalCancelOut', function() {
			var weighingId = $(this).data('id'); 
			var status = $(this).data('status');

			$.ajax({
				url: 'cancel_modal4.php',
				type: 'GET',
				data: { 
					id: weighingId,
					status: status,
				 }, 
				success: function(response) {
					$('#modalCancelOut .modal-content').html(response);
					$('#modalCancelOut').modal('show');
				}
			});
		});
	});
</script>