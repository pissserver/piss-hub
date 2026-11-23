<?php
	session_start();
	include "../connection.php";
	// Validasi role tanpa mengubah sesi pengguna.
	if(isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) == 'pp' && isset($_SESSION['username']) && $_SESSION['username'] != ''){
		$admin = $_SESSION['username'];
	}else{
		header("Location:../login.html");
		exit();
	}
	$id = $_GET['id'];
	$status = $_GET['status'];
?>

<div class="modal-header bg-primary text-white" style="border-top-left-radius: 5px; border-top-right-radius: 5px;">
	<h4 style="margin-left: 20px;"><i class="fa fa-close fa-fw"></i>&nbsp;Tagihan/Nota Tidak Terbayar</h4>
	<button style="margin-top: -35px; margin-right: 20px;" type="button" class="close" data-dismiss="modal" aria-label="Close">
		<span aria-hidden="true">&times;</span>
	</button>
</div>
<div class="modal-body">
	<form action="weighing_action.php?task=<?php echo $status == 'Tangguhkan' ? 'Tangguhkan' : 'Aktifkan'; ?>&id=<?php echo $id; ?>"  id="form_edit2" name="form_edit2" method="POST" class="form-horizontal">
		<div class="form-group" style="padding:10px; margin-left:10px; margin-right: 10px;">
			<label>No Nota</label>
			<input id="weighing_id" name="weighing_id" value="<?php echo $id; ?>" type="number" class="form-control" disabled>  
		</div>
		<div class="form-group" style="padding:10px; margin-left:10px; margin-right: 10px; margin-top: -15px;">
			<label>Alasan Tidak Terbayar</label>
			<input id="reason" name="reason" placeholder="Alasan Mengapa Tidak Terbayar" type="text" class="form-control" required>                  
		</div>
		<div class="form-group" style="padding:10px; margin-top:-10px; margin-left:10px; margin-right: 10px;">
			<button type="submit" class="btn btn-outline btn-primary btn-block btn-sm" style="font-size: 14px;"><i class="fa fa-floppy-o fa-fw" style="font-size: 16px;"></i>&nbsp;Simpan</button>               
		</div>
	</form>
	<input type="hidden" id="value_id" />
</div>