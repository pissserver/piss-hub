<?php
	// Unified login PISS Hub
	session_start();
	include "connection.php";

	session_unset();

	function antiInjection($data){
		return mysql_real_escape_string(stripslashes(strip_tags(htmlspecialchars($data, ENT_QUOTES))));
	}

	$user = antiInjection(trim($_POST["user"]));
	$pass = antiInjection(trim($_POST["pass"]));

	if (empty($user) || empty($pass)) {
		alertGagal("Harap masukkan username dan password!");
		exit;
	}

	// ============ LOGIN ADMIN (SPV / PP / Keuangan) ============
	$login = mysql_query("select username, role, lapak, timbangan, listrik, photo from admin where username = '".$user."' and password = '".$pass."'");
	if (mysql_num_rows($login) == 1) {
		$r = mysql_fetch_array($login);

		$_SESSION['username'] = $r['username'];
		$_SESSION['name'] = '';
		$_SESSION['role'] = $r['role'];
		$_SESSION['photo'] = $r['photo'];
		$_SESSION['id_admin'] = executeScalar("select id_admin from admin where username = '".$r['username']."'");
		$_SESSION['app_access'] = [];
		$_SESSION['active_app'] = 'timbangan';
		if ($r['timbangan'] == 'Yes') $_SESSION['app_access'][] = 'timbangan';
		if ($r['lapak'] == 'Yes') $_SESSION['app_access'][] = 'lapak';
		if ($r['listrik'] == 'Yes') $_SESSION['app_access'][] = 'listrik';

		if (count($_SESSION['app_access']) == 0) {
			alertGagal("Anda Tidak Memiliki Hak Untuk Masuk!");
			exit;
		}

		// default app timbangan sbg landing
		$role = $r['role'];
		if ($role == 'spv') $_SESSION['redirect_app'] = 'timbangan/spv/dashboard.php';
		elseif ($role == 'pp') $_SESSION['redirect_app'] = 'timbangan/pp/dashboard.php';
		elseif ($role == 'keuangan') $_SESSION['redirect_app'] = 'timbangan/kk/dashboard.php';
		else $_SESSION['redirect_app'] = 'timbangan/dashboard.php';

		include "timbangan/components/welcome_animation.php";
		exit;
	}

	// ============ LOGIN CHECKER (Cashier / Checker / Checker Verifikasi) ============
	$login2 = mysql_query("select checker_code, checker_role, checker_sts from checker where checker_code = '".$user."' and checker_pass = '".$pass."'");
	if (mysql_num_rows($login2) == 1) {
		$r2 = mysql_fetch_array($login2);
		if ($r2['checker_sts'] !== 'Aktif') {
			alertGagal("Akun Anda Tidak Aktif!");
			exit;
		}

		$_SESSION['username'] = $r2['checker_code'];
		$_SESSION['name'] = '';
		$_SESSION['checker'] = $r2['checker_code'];
		$_SESSION['role'] = $r2['checker_role'];
		$_SESSION['photo'] = '';
		$_SESSION['id_admin'] = 0;
		$_SESSION['app_access'] = ['timbangan', 'listrik'];
		$_SESSION['active_app'] = 'timbangan';
		$_SESSION['redirect_app'] = 'timbangan/dashboard.php';

		$_SESSION['show_welcome_modal'] = true;

		include "timbangan/components/welcome_animation.php";
		exit;
	}

	alertGagal("Username atau password salah!");
	exit;

	function alertGagal($msg) {
		echo "<script>window.alert('".$msg."'); window.location = 'index.php';</script>";
		exit;
	}
?>
