<?php
    session_start();
    include "../connection.php";

    $semantic = $_POST['semantic'];
    $version  = $_POST['version'];
    $title    = $_POST['title'];
    $date     = $_POST['date'];
    $lots     = $_POST['lots']; // jumlah deskripsi
    $signatureData = isset($_POST['signature_data']) ? $_POST['signature_data'] : '';

    if (strlen($signatureData) > 1048576 ||
        !preg_match('/\Adata:image\/png;base64,([A-Za-z0-9+\/=]+)\z/', $signatureData, $signatureMatch)) {
        $_SESSION['success'] = false;
        $_SESSION['title'] = 'Tanda tangan tidak valid';
        $_SESSION['message'] = 'Silakan bubuhkan tanda tangan sebelum menyimpan.';
        header('Location: changelog.php');
        exit;
    }

    $signature = base64_decode($signatureMatch[1], true);
    if ($signature === false || substr($signature, 0, 8) !== "\x89PNG\r\n\x1a\n") {
        $_SESSION['success'] = false;
        $_SESSION['title'] = 'Tanda tangan tidak valid';
        $_SESSION['message'] = 'Silakan bubuhkan tanda tangan sebelum menyimpan.';
        header('Location: changelog.php');
        exit;
    }

    $signature = mysql_real_escape_string($signatureMatch[1]);

    // 1. Insert ke tabel update_timbangan
    $query1 = mysql_query("
        INSERT INTO update_timbangan (semantic, version, title, date, signature)
        VALUES ('$semantic', '$version', '$title', '$date', '$signature')
    ");

    if (!$query1) {
        die("Gagal insert update_timbangan: " . mysql_error());
    }

    // 2. Ambil ID terakhir
    $update_id = mysql_insert_id();

    // 3. Loop semua deskripsi
    for ($i = 1; $i <= $lots; $i++) {
        $field = "descriptions_" . $i;

        if (!isset($_POST[$field])) continue;

        $desc = trim($_POST[$field]);
        if ($desc == "") continue;

        // Escape petik & karakter berbahaya
        $desc = mysql_real_escape_string($desc);

        $query2 = mysql_query("
            INSERT INTO update_desc_timbangan (update_timbangan_id, description)
            VALUES ('$update_id', '$desc')
        ");

        if (!$query2) {
            die("Gagal insert deskripsi $i: " . mysql_error());
        }
    }

    if($query1 && $query2){
		// Alert Information
		$_SESSION['success'] = true;
		$_SESSION['title'] = 'Berhasil';
		$_SESSION['message'] = "Menambahkan Update Log";
		echo "<script>window.location=('changelog.php')</script>";
	}
	else{
		// Alert Information
		$_SESSION['success'] = false;
		$_SESSION['title'] = 'Gagal';
		$_SESSION['message'] = "Menambahkan Update Log";
		echo "<script>window.location=('changelog.php')</script>";
	}
?>
