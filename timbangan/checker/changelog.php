<?php
	session_start();
	include "../connection.php";

	if($_SESSION['checker']==''){
		header("Location:login.html");
	}
	$checker 		= executeScalar("select checker_name from checker where checker_code = '".$_SESSION['checker']."'");

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
				<br><br><br><br>
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
	</body>
</html>