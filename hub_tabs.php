<?php
// Tab switcher aplikasi PISS Hub — include di dalam sidebar tiap aplikasi.
// Taruh setelah role_session + connection (butuh $_SESSION dan mysql).

$hubCurrent = '';
if (preg_match('#/(timbangan|lapak|listrik)/#', $_SERVER['SCRIPT_NAME'], $hm)) {
	$hubCurrent = $hm[1];
}
$hubBase = preg_replace('#/'.preg_quote($hubCurrent, '#').'/.*$#i', '', $_SERVER['SCRIPT_NAME']);

$hubRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if (in_array($hubRole, ['spv', 'pp', 'keuangan'], true)) {
	$hubUser = mysql_real_escape_string($_SESSION['username']);
	$hubQ = mysql_query("SELECT lapak, timbangan, listrik FROM admin WHERE username = '".$hubUser."' LIMIT 1");
	$hubF = mysql_fetch_assoc($hubQ);
	$hubLapak = ($hubF && $hubF['lapak'] === 'Yes');
	$hubTimbangan = ($hubF && $hubF['timbangan'] === 'Yes');
	$hubListrik = ($hubF && $hubF['listrik'] === 'Yes');

	$hubFolder = '';
	if ($hubRole === 'spv') $hubFolder = 'spv/';
	elseif ($hubRole === 'pp') $hubFolder = 'pp/';
	elseif ($hubRole === 'keuangan') $hubFolder = 'kk/';
	$hubTimbanganPath = 'timbangan/'.$hubFolder.'dashboard.php';
} else {
	// Cashier / Checker
	$hubLapak = false;
	$hubTimbangan = true;
	$hubListrik = true;
	$hubTimbanganPath = 'timbangan/dashboard.php';
}

$hubTabs = [];
if ($hubTimbangan) $hubTabs[] = ['dir' => 'timbangan', 'path' => $hubTimbanganPath, 'icon' => 'fa-balance-scale', 'label' => 'Timbangan'];
if ($hubLapak)     $hubTabs[] = ['dir' => 'lapak',     'path' => 'lapak/dashboard.php',     'icon' => 'fa-building',       'label' => 'Lapak'];
if ($hubListrik)   $hubTabs[] = ['dir' => 'listrik',   'path' => 'listrik/dashboard.php',   'icon' => 'fa-bolt',           'label' => 'Listrik'];
?>
<div id="hub-tabs" style="display:flex;gap:4px;margin:0 10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.08);">
	<?php foreach ($hubTabs as $tab) : ?>
		<?php if ($hubCurrent === $tab['dir']) : ?>
			<span class="hub-tab hub-tab-active"><i class="fa fa-fw <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?></span>
		<?php else : ?>
			<a href="<?= $hubBase.'/'.$tab['path'] ?>" class="hub-tab"><i class="fa fa-fw <?= $tab['icon'] ?>"></i> <?= $tab['label'] ?></a>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
<style>
	.hub-tab{flex:1;display:inline-block;text-align:center;padding:8px 4px;border-radius:10px;font-size:12px;font-weight:600;text-decoration:none;background:rgba(255,255,255,0.06);color:#cbd5e1;transition:all .2s ease;white-space:nowrap;}
	.hub-tab:hover{background:rgba(250,204,21,0.15);color:#fff;text-decoration:none;transform:translateY(-1px);}
	.hub-tab-active,.hub-tab-active:hover{background:linear-gradient(135deg,#facc15,#f59e0b);color:#1f2937;box-shadow:0 0 12px rgba(250,204,21,.8);text-decoration:none;}
	body.sidebar-collapse #hub-tabs{display:none;}
</style>
