<?php
session_start();
include "../connection.php";

function changelogUpdateFail($message) {
    $_SESSION['success'] = false;
    $_SESSION['title'] = 'Gagal memperbarui changelog';
    $_SESSION['message'] = $message;
    header('Location: changelog.php');
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'spv') {
    http_response_code(403);
    exit('Akses ditolak.');
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

$expectedToken = hash('sha256', session_id() . '|changelog-edit');
if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) || $_POST['csrf_token'] !== $expectedToken) {
    changelogUpdateFail('Permintaan tidak valid. Muat ulang halaman lalu coba kembali.');
}

$updateId = isset($_POST['update_id']) ? $_POST['update_id'] : '';
$semantic = isset($_POST['semantic']) ? $_POST['semantic'] : '';
$version = isset($_POST['version']) && is_string($_POST['version']) ? trim($_POST['version']) : '';
$title = isset($_POST['title']) && is_string($_POST['title']) ? trim($_POST['title']) : '';
$date = isset($_POST['date']) ? $_POST['date'] : '';
$signatureAction = isset($_POST['signature_action']) && is_string($_POST['signature_action']) ? $_POST['signature_action'] : 'keep';

if (!is_string($updateId) || !ctype_digit($updateId) || (int)$updateId < 1) {
    changelogUpdateFail('Data changelog tidak valid.');
}

if (!in_array($semantic, array('major', 'minor', 'patch'), true) ||
    !is_string($version) || $version == '' ||
    !is_string($title) || $title == '' ||
    !is_string($date) || !preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $date) ||
    !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
    changelogUpdateFail('Pastikan semantic versioning, versi, judul, dan tanggal sudah valid.');
}

if (!in_array($signatureAction, array('keep', 'replace', 'remove'), true)) {
    changelogUpdateFail('Pilihan perubahan tanda tangan tidak valid.');
}

$signatureUpdate = '';
if ($signatureAction == 'replace') {
    $signatureData = isset($_POST['signature_data']) && is_string($_POST['signature_data']) ? $_POST['signature_data'] : '';
    if (strlen($signatureData) > 1048576 ||
        !preg_match('/\Adata:image\/png;base64,([A-Za-z0-9+\/=]+)\z/', $signatureData, $signatureMatch)) {
        changelogUpdateFail('Tanda tangan baru tidak valid. Silakan gambar ulang tanda tangan.');
    }

    $signatureBinary = base64_decode($signatureMatch[1], true);
    if ($signatureBinary === false || substr($signatureBinary, 0, 8) !== "\x89PNG\r\n\x1a\n") {
        changelogUpdateFail('Format tanda tangan tidak valid. Silakan gambar ulang tanda tangan.');
    }

    $signatureData = mysql_real_escape_string($signatureMatch[1]);
    $signatureUpdate = ", signature = '$signatureData'";
} elseif ($signatureAction == 'remove') {
    $signatureUpdate = ", signature = NULL";
}

$descriptions = array();
if (isset($_POST['descriptions'])) {
    if (!is_array($_POST['descriptions'])) {
        changelogUpdateFail('Format deskripsi tidak valid.');
    }

    foreach ($_POST['descriptions'] as $description) {
        if (!is_string($description)) {
            changelogUpdateFail('Format deskripsi tidak valid.');
        }

        $description = trim($description);
        if ($description != '') {
            $descriptions[] = $description;
        }
    }
}

$updateId = mysql_real_escape_string($updateId);
$semantic = mysql_real_escape_string($semantic);
$version = mysql_real_escape_string($version);
$title = mysql_real_escape_string($title);
$date = mysql_real_escape_string($date);

if (!mysql_query("START TRANSACTION")) {
    changelogUpdateFail('Tidak dapat memulai penyimpanan: ' . mysql_error());
}

$existingUpdate = mysql_query("SELECT id FROM update_timbangan WHERE id = '$updateId' LIMIT 1");
if (!$existingUpdate) {
    $error = mysql_error();
    mysql_query("ROLLBACK");
    changelogUpdateFail('Gagal memeriksa data changelog: ' . $error);
}
if (mysql_num_rows($existingUpdate) == 0) {
    mysql_query("ROLLBACK");
    changelogUpdateFail('Data changelog tidak ditemukan.');
}

$update = mysql_query("
    UPDATE update_timbangan
    SET semantic = '$semantic', version = '$version', title = '$title', date = '$date'$signatureUpdate
    WHERE id = '$updateId'
");

if (!$update) {
    $error = mysql_error();
    mysql_query("ROLLBACK");
    changelogUpdateFail('Gagal memperbarui data utama: ' . $error);
}

$deleteDescriptions = mysql_query("
    DELETE FROM update_desc_timbangan
    WHERE update_timbangan_id = '$updateId'
");

if (!$deleteDescriptions) {
    $error = mysql_error();
    mysql_query("ROLLBACK");
    changelogUpdateFail('Gagal memperbarui deskripsi: ' . $error);
}

foreach ($descriptions as $description) {
    $description = mysql_real_escape_string($description);
    $insertDescription = mysql_query("
        INSERT INTO update_desc_timbangan (update_timbangan_id, description)
        VALUES ('$updateId', '$description')
    ");

    if (!$insertDescription) {
        $error = mysql_error();
        mysql_query("ROLLBACK");
        changelogUpdateFail('Gagal menyimpan deskripsi: ' . $error);
    }
}

if (!mysql_query("COMMIT")) {
    $error = mysql_error();
    mysql_query("ROLLBACK");
    changelogUpdateFail('Gagal menyimpan perubahan: ' . $error);
}

$_SESSION['success'] = true;
$_SESSION['title'] = 'Berhasil';
$_SESSION['message'] = 'Data changelog berhasil diperbarui.';
header('Location: changelog.php');
exit;
?>
