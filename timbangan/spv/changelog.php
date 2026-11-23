<?php
	session_start();
	include "../connection.php";
	include "components/role_session.php";

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
		<link rel="icon" href="../img/logo.png">
		<link rel="shortcut icon" href="../img/logo.png" />
		<title>Update Log | PISS</title>

		<?php echo headScript(); ?>
		<link href="../css/changelog-premium.css" rel="stylesheet">
	</head>
	<body>
		<div id="wrapper">
		<?php include "components/sidebar.php" ?>

			<div id="page-wrapper">
				
				<?php 
					$username	= $_SESSION['username'];
					$privileges = executeScalar("SELECT privileges FROM admin WHERE username = '$username'");
					$canEditChangelog = isset($_SESSION['role']) && $_SESSION['role'] == 'spv';
					$changelogEditToken = hash('sha256', session_id() . '|changelog-edit');
					if ($privileges == 'developer') { ?>

					<div class="row">
						<div class="col-lg-12">
							<br><br><br><br>
							<div class="panel panel-primary">
								<div class="panel-heading">
									<i class="fa fa-plus fa-fw"></i> Buat Update Log / Changelog
								</div>
								<div class="panel-body">
									<form class="form-horizontal" id="formUpdateLog" name = "formUpdateLog" method="POST" action="changelog_action.php">
										<fieldset>
											<div class="form-group" style="padding:10px; margin-top:-10px; margin-bottom:0px;">
												<label for="semantic">Semantic Versioning</label>
												<select  name="semantic" id="semantic" class="form-control" required>
													<option value="" selected>Pilih Semantic Versioning</option>
													<option value="major">Major (Format Perubahan 1.x.x)</option>
													<option value="minor">Minor (Format Perubahan x.1.x)</option>
													<option value="patch">Patch (Format Perubahan x.x.1)</option>
												</select>                  
											</div>
											<div class="form-group" style="padding:10px; margin-top: -5px; margin-bottom:0px;">
												<label for="version">Version</label>
												<input type="text" class="form-control" id="version" name="version" placeholder="Contoh: 1.0.0" required>                  
											</div>
											<div class="form-group" style="padding:10px; margin-top: -5px; margin-bottom:0px;">
												<label for="title">Title Update</label>
												<input type="text" class="form-control" id="title" name="title" placeholder="Masukkan Title Update" required>                  
											</div>
											<div class="form-group" style="padding:10px; margin-top: -5px; margin-bottom:0px;">
												<label for="date">Tanggal Update</label>
												<input type="date" class="form-control" id="date" name="date" placeholder="Tanggal Update" required>                  
											</div>
											<div class="form-group" style="padding:10px; margin-top: -5px; margin-bottom:0px;">
												<label for="lots">Banyak Deskripsi Update</label>
												<input type="number" class="form-control" id="lots" name="lots" placeholder="Banyak Deskripsi Update" required>
											</div>

											<!-- Tempat untuk input deskripsi otomatis -->
											<div id="desc-wrapper"></div>

											<div class="form-group" style="padding:10px; margin-top:-5px; margin-bottom:0px;">
												<label for="signature-pad">Tanda Tangan</label>
												<p class="help-block">Gunakan mouse atau sentuh area di bawah untuk membubuhkan tanda tangan.</p>
												<canvas id="signature-pad" width="900" height="180" role="img" aria-label="Area untuk menggambar tanda tangan" tabindex="0" style="display:block; width:100%; height:180px; border:1px solid #ccc; border-radius:4px; background:#fff; touch-action:none;"></canvas>
												<input type="hidden" id="signature-data" name="signature_data">
												<p id="signature-error" class="help-block text-danger" role="alert" aria-live="polite" style="display:none;">Tanda tangan wajib diisi sebelum menyimpan.</p>
												<button type="button" id="clear-signature" class="btn btn-default" style="margin-top:8px; min-height:44px;"><i class="fa fa-eraser"></i> Hapus tanda tangan</button>
											</div>

											<div class="form-group" style="padding:10px; margin-bottom:0px;">
												<center><button type="submit" class="btn btn-block btn-primary"><i class="fa fa-floppy-o fa-fw"></i>&nbsp;Simpan</button></center>
											</div>
										</fieldset>
									</form> 
								</div>
							</div>
							<!-- <div class="panel panel-primary">
								<div class="panel-heading">
									<i class="fa fa-truck fa-fw"></i> Print Data Kir Kendaraan
								</div>
								<div class="panel-body">
									<form action="htmltopdf/reportKir.php" target="_blank" class="form-horizontal" id="formTransportation" name = "formTransportation" method="POST">
										<div class="form-group" style="padding:10px; margin-bottom:-10px;">
											<center><button type="submit" class="btn btn-block btn-primary">Print</button></center>
										</div>
									</form> 
								</div>
							</div> -->
						</div>
					</div>
				<?php } elseif ($privileges == 'manager') { ?>
					<br><br><br><br>
				<?php } ?>
				
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-primary changelog-container">
							<div class="panel-heading">
								<div class="changelog-heading-copy">
									<span class="changelog-eyebrow">Riwayat Pembaruan</span>
									<h2 class="changelog-heading">Update Log</h2>
									<p class="changelog-subtitle">Catatan fitur baru, peningkatan, dan perbaikan sistem.</p>
								</div>
								<span class="changelog-heading-icon" aria-hidden="true"><i class="fa fa-history"></i></span>
							</div>
							<div class="panel-body">
								<div class="panel-group changelog-list" id="accordionChangelog">

								<?php
								    $first = true;
									$query = mysql_query("SELECT * FROM update_timbangan ORDER BY id DESC");

									while($data = mysql_fetch_array($query)){
										$update_id = $data['id'];
										$semantic = $data['semantic'];
										$version = $data['version'];
										$title = $data['title'];
										$date = date("d F Y", strtotime($data['date']));
										$panelClass = $semantic == 'major' ? 'panel-success' :
														($semantic == 'minor' ? 'panel-info' : 'panel-default');
										$semanticLabel = ucfirst($semantic);
									?>

								<div class="panel <?php echo $panelClass; ?> changelog-item">
									<div class="panel-heading">
										<h4 class="panel-title">
											<a class="changelog-toggle" role="button" data-toggle="collapse" data-parent="#accordionChangelog" href="#ver<?= $update_id ?>" aria-controls="ver<?= $update_id ?>" aria-expanded="<?= $first ? 'true' : 'false' ?>">
												<span class="changelog-version-icon" aria-hidden="true"><i class="fa fa-code-fork"></i></span>
												<span class="changelog-version-copy">
													<span class="changelog-version">Version <?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8'); ?></span>
													<span class="changelog-meta">
														<span class="changelog-semantic"><?= htmlspecialchars($semanticLabel, ENT_QUOTES, 'UTF-8'); ?></span>
														<span class="changelog-date"><i class="fa fa-calendar-o" aria-hidden="true"></i> <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?></span>
													</span>
												</span>
												<i class="fa fa-chevron-down changelog-chevron" aria-hidden="true"></i>
											</a>
										</h4>
									</div>

									<div id="ver<?= $update_id ?>" class="panel-collapse collapse <?= $first ? 'in' : '' ?>">
										<div class="panel-body">
											<h5 class="changelog-entry-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h5>
											<ul class="changelog-description">
												<?php
													$desc_query = mysql_query("SELECT * FROM update_desc_timbangan WHERE update_timbangan_id = '$update_id'");
													$emoji = [
																// Objects
																'💡','📌','📍','📎','✏','✒','🖊','🖋','📝','📖','📚','📦','📁','📂','🗂',
																'💼','👜','🎒','💻','🖥','🖨','⌨','🖱','💾','📱','📲','☎','📞',

																// Weather
																'☀','🌤','⛅','🌥','🌦','🌧','⛈','🌩','🌨','❄','🌬','💨','🌪','🌈',

																// Hearts / Symbols
																'❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎',
																'💔','❣','💕','💞','💓','💗','💖','💘','💝','💟',
																'✨','⭐','🌟','💫','🔥','💥','⚡','🌟','🎇','🎆',

																// Food
																'🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍',
																'🥥','🥝','🍅','🍆','🥑','🥦','🥬','🥒','🌶','🫑','🌽','🥕','🧄','🧅','🥔',

																// Misc fun
																'🎲','🎯','🎮','🎰','🎵','🎶','🎤','🎧',
																'🧩','🎎','🎏','🎐','🎀','🎁','🎉','🎊','🎈','🏆','🥇','🥈','🥉',
																'🚀','✈','🚁','🚗','🚌','🚜','🚲','🏍','🛵','🚂'
															];

													while($desc_data = mysql_fetch_array($desc_query)){

														$descriptions = $desc_data['description'];

														// ambil emoji acak
														$random_emoji = $emoji[array_rand($emoji)];

														echo "<li><i class=\"fa fa-check-circle changelog-description-icon\" aria-hidden=\"true\"></i><span>" . htmlspecialchars($descriptions, ENT_QUOTES, 'UTF-8') . "</span></li>";
													}
												?>
											</ul>
											<?php if (!empty($data['signature']) && preg_match('/\A[A-Za-z0-9+\/=]+\z/', $data['signature'])) { ?>
												<figure class="changelog-signature">
													<figcaption>Tanda tangan</figcaption>
													<img src="data:image/png;base64,<?= htmlspecialchars($data['signature'], ENT_QUOTES, 'UTF-8'); ?>" alt="Tanda tangan pada update <?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8'); ?>">
												</figure>
											<?php } ?>
											<?php if ($canEditChangelog) { ?>
												<div class="changelog-edit-toolbar">
													<button type="button" class="btn btn-default btn-sm" data-toggle="collapse" data-target="#edit-changelog-<?= $update_id ?>" aria-expanded="false" aria-controls="edit-changelog-<?= $update_id ?>">
														<i class="fa fa-pencil" aria-hidden="true"></i> Edit changelog
													</button>
												</div>
												<div class="collapse changelog-edit-collapse" id="edit-changelog-<?= $update_id ?>">
													<form class="changelog-edit-form" method="POST" action="changelog_update_action.php">
														<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($changelogEditToken, ENT_QUOTES, 'UTF-8'); ?>">
														<input type="hidden" name="update_id" value="<?= (int)$update_id; ?>">
														<div class="row">
															<div class="col-sm-4">
																<div class="form-group">
																	<label for="semantic-<?= (int)$update_id; ?>">Semantic versioning</label>
																	<select class="form-control" id="semantic-<?= (int)$update_id; ?>" name="semantic" required>
																		<option value="major" <?= $semantic == 'major' ? 'selected' : ''; ?>>Major</option>
																		<option value="minor" <?= $semantic == 'minor' ? 'selected' : ''; ?>>Minor</option>
																		<option value="patch" <?= $semantic == 'patch' ? 'selected' : ''; ?>>Patch</option>
																	</select>
																</div>
															</div>
															<div class="col-sm-4">
																<div class="form-group">
																	<label for="version-<?= (int)$update_id; ?>">Version</label>
																	<input class="form-control" type="text" id="version-<?= (int)$update_id; ?>" name="version" value="<?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8'); ?>" required>
																</div>
															</div>
															<div class="col-sm-4">
																<div class="form-group">
																	<label for="date-<?= (int)$update_id; ?>">Tanggal update</label>
																	<input class="form-control" type="date" id="date-<?= (int)$update_id; ?>" name="date" value="<?= htmlspecialchars(date('Y-m-d', strtotime($data['date'])), ENT_QUOTES, 'UTF-8'); ?>" required>
																</div>
															</div>
														</div>
														<div class="form-group">
															<label for="title-<?= (int)$update_id; ?>">Judul update</label>
															<input class="form-control" type="text" id="title-<?= (int)$update_id; ?>" name="title" value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" required>
														</div>
														<div class="form-group">
															<label>Deskripsi update</label>
															<div class="changelog-edit-descriptions">
																<?php
																	$edit_desc_query = mysql_query("SELECT description FROM update_desc_timbangan WHERE update_timbangan_id = '$update_id'");
																	$descriptionIndex = 0;
																	while ($edit_desc = mysql_fetch_array($edit_desc_query)) {
																		$descriptionIndex++;
																?>
																	<div class="changelog-edit-description-row">
																		<input class="form-control" type="text" name="descriptions[]" value="<?= htmlspecialchars($edit_desc['description'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="Deskripsi update <?= $descriptionIndex; ?>">
																		<button type="button" class="btn btn-default remove-changelog-description" aria-label="Hapus deskripsi"><i class="fa fa-minus" aria-hidden="true"></i></button>
																	</div>
																<?php } ?>
															</div>
															<button type="button" class="btn btn-default btn-sm add-changelog-description"><i class="fa fa-plus" aria-hidden="true"></i> Tambah deskripsi</button>
														</div>
														<div class="form-group">
															<label for="edit-signature-<?= (int)$update_id; ?>">Tanda tangan</label>
															<canvas class="changelog-edit-signature-pad" id="edit-signature-<?= (int)$update_id; ?>" width="900" height="160" role="img" aria-label="Area untuk mengganti tanda tangan" tabindex="0"></canvas>
															<input type="hidden" name="signature_data" value="">
															<input type="hidden" name="signature_action" value="keep">
															<button type="button" class="btn btn-default clear-changelog-signature"><i class="fa fa-eraser" aria-hidden="true"></i> Hapus tanda tangan</button>
															<p class="help-block">Biarkan tanda tangan tetap, gambar tanda tangan baru untuk menggantinya, atau pilih Hapus untuk mengosongkannya.</p>
														</div>
														<div class="changelog-edit-actions">
															<button type="submit" class="btn btn-primary"><i class="fa fa-floppy-o" aria-hidden="true"></i> Simpan perubahan</button>
															<button type="button" class="btn btn-default" data-toggle="collapse" data-target="#edit-changelog-<?= $update_id ?>">Batal</button>
														</div>
													</form>
												</div>
											<?php } ?>
										</div>
									</div>
								</div>

								<?php 
									$first = false;
								} ?>

								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- End Main Content -->
		</div>

		<!-- Input Show By Input Lots Of Description -->
		<script>
			$(document).ready(function(){
				var canvas = document.getElementById("signature-pad");
				if (canvas) {
					var context = canvas.getContext("2d");
					var isDrawing = false;
					var hasSignature = false;

					context.strokeStyle = "#111";
					context.lineWidth = 3;
					context.lineCap = "round";
					context.lineJoin = "round";

					function getPosition(event) {
						var bounds = canvas.getBoundingClientRect();
						return {
							x: (event.clientX - bounds.left) * canvas.width / bounds.width,
							y: (event.clientY - bounds.top) * canvas.height / bounds.height
						};
					}

					canvas.addEventListener("pointerdown", function(event) {
						event.preventDefault();
						isDrawing = true;
						canvas.setPointerCapture(event.pointerId);
						var position = getPosition(event);
						context.beginPath();
						context.moveTo(position.x, position.y);
					});

					canvas.addEventListener("pointermove", function(event) {
						if (!isDrawing) return;
						event.preventDefault();
						var position = getPosition(event);
						context.lineTo(position.x, position.y);
						context.stroke();
						hasSignature = true;
					});

					function stopDrawing() {
						isDrawing = false;
						$("#signature-error").hide();
						if (hasSignature) {
							$("#signature-data").val(canvas.toDataURL("image/png"));
						}
					}

					canvas.addEventListener("pointerup", stopDrawing);
					canvas.addEventListener("pointercancel", stopDrawing);

					$("#clear-signature").on("click", function() {
						context.clearRect(0, 0, canvas.width, canvas.height);
						hasSignature = false;
						$("#signature-data").val("");
						$("#signature-error").hide();
					});

					$("#formUpdateLog").on("submit", function(event) {
						if (!hasSignature) {
							event.preventDefault();
							$("#signature-error").show();
							canvas.focus();
						} else {
							$("#signature-data").val(canvas.toDataURL("image/png"));
						}
					});
				}

				$("#lots").on("input", function(){
					var lots = $(this).val();
					var descWrapper = $("#desc-wrapper");
					descWrapper.empty(); // Clear previous inputs

					for(var i=1; i<=lots; i++){
						var descInput = `
							<div class="form-group" style="padding:10px; margin-top: -5px; margin-bottom:0px;">
								<label for="descriptions_`+i+`">Deskripsi Update `+i+`</label>
								<input type="text" class="form-control" id="descriptions_`+i+`" name="descriptions_`+i+`" placeholder="Masukkan Deskripsi Update `+i+`" required>                  
							</div>
						`;
						descWrapper.append(descInput);
					}
				});

				$(document).on("click", ".add-changelog-description", function() {
					var row = $("<div class='changelog-edit-description-row'></div>");
					$("<input class='form-control' type='text' name='descriptions[]' aria-label='Deskripsi update'>").appendTo(row);
					$("<button type='button' class='btn btn-default remove-changelog-description' aria-label='Hapus deskripsi'><i class='fa fa-minus' aria-hidden='true'></i></button>").appendTo(row);
					$(this).siblings(".changelog-edit-descriptions").append(row);
					row.find("input").focus();
				});

				$(document).on("click", ".remove-changelog-description", function() {
					$(this).closest(".changelog-edit-description-row").remove();
				});

				$(".changelog-edit-signature-pad").each(function() {
					var canvas = this;
					var context = canvas.getContext("2d");
					var form = $(canvas).closest("form");
					var isDrawing = false;

					context.strokeStyle = "#111";
					context.lineWidth = 3;
					context.lineCap = "round";
					context.lineJoin = "round";

					function getEditSignaturePosition(event) {
						var bounds = canvas.getBoundingClientRect();
						return {
							x: (event.clientX - bounds.left) * canvas.width / bounds.width,
							y: (event.clientY - bounds.top) * canvas.height / bounds.height
						};
					}

					canvas.addEventListener("pointerdown", function(event) {
						event.preventDefault();
						isDrawing = true;
						canvas.setPointerCapture(event.pointerId);
						var position = getEditSignaturePosition(event);
						context.beginPath();
						context.moveTo(position.x, position.y);
					});

					canvas.addEventListener("pointermove", function(event) {
						if (!isDrawing) return;
						event.preventDefault();
						var position = getEditSignaturePosition(event);
						context.lineTo(position.x, position.y);
						context.stroke();
						form.find("[name='signature_action']").val("replace");
					});

					function stopEditSignatureDrawing() {
						isDrawing = false;
						if (form.find("[name='signature_action']").val() == "replace") {
							form.find("[name='signature_data']").val(canvas.toDataURL("image/png"));
						}
					}

					canvas.addEventListener("pointerup", stopEditSignatureDrawing);
					canvas.addEventListener("pointercancel", stopEditSignatureDrawing);

					form.find(".clear-changelog-signature").on("click", function() {
						context.clearRect(0, 0, canvas.width, canvas.height);
						form.find("[name='signature_data']").val("");
						form.find("[name='signature_action']").val("remove");
					});
				});
			});
		</script>

		<!-- jQuery -->
		<script src="../js/jquery.js"></script>
		<!-- Bootstrap Core JavaScript -->
		<script src="../js/bootstrap.min.js"></script>
		<!-- Metis Menu Plugin JavaScript -->
		<script src="../js/plugins/metisMenu/metisMenu.min.js"></script>
		<!-- Custom Theme JavaScript -->
		<script src="../js/sb-admin-2.js"></script>
		<script>
			$(document).ready(function(){
				//Modal Password
				$('#modalPassword').on('shown.bs.modal', function (e) {
					$("#notifArea").html(alertLoadProcess);
					$("#data_password").load("password.php",function(){clearNotif();});
				})
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