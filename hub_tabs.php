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

$hubApps = [
	'timbangan' => ['label' => 'Timbangan', 'icon' => 'fa-balance-scale'],
	'lapak' => ['label' => 'Lapak', 'icon' => 'fa-building'],
	'listrik' => ['label' => 'Listrik', 'icon' => 'fa-bolt'],
];
$hubApp = isset($hubApps[$hubCurrent]) ? $hubApps[$hubCurrent] : ['label' => 'PISS Hub', 'icon' => 'fa-th-large'];
$hubVersionTables = [
	'timbangan' => 'update_timbangan',
	'lapak' => 'update_lapak',
	'listrik' => 'update_listrik',
];
$hubVersion = '';
if (isset($hubVersionTables[$hubCurrent])) {
	$hubVersionQuery = mysql_query("SELECT version FROM ".$hubVersionTables[$hubCurrent]." ORDER BY id DESC LIMIT 1") or die(mysql_error());
	$hubVersionData = mysql_fetch_assoc($hubVersionQuery);
	if ($hubVersionData && $hubVersionData['version'] !== '') {
		$hubVersion = $hubVersionData['version'];
	}
}
$hubAppPath = $hubBase.'/'.$hubCurrent.'/dashboard.php';
$hubActiveTabIndex = 0;
foreach ($hubTabs as $index => $tab) {
	if ($hubCurrent === $tab['dir']) {
		$hubAppPath = $hubBase.'/'.$tab['path'];
		$hubActiveTabIndex = $index;
		break;
	}
}
$hubTabCount = max(count($hubTabs), 1);
$hubTabWidth = 100 / $hubTabCount;
?>
<a class="hub-brand" href="<?= htmlspecialchars($hubAppPath, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($hubApp['label'], ENT_QUOTES, 'UTF-8') ?>">
	<span class="hub-brand-mark"><i class="fa <?= $hubApp['icon'] ?>" aria-hidden="true"></i></span>
	<span class="hub-brand-copy">
		<span class="hub-brand-title">
			<strong class="hub-brand-name"><?= htmlspecialchars($hubApp['label'], ENT_QUOTES, 'UTF-8') ?></strong>
			<?php if ($hubVersion !== '') : ?>
				<span class="hub-brand-version">v<?= htmlspecialchars($hubVersion, ENT_QUOTES, 'UTF-8') ?></span>
			<?php endif; ?>
		</span>
		<span class="hub-brand-caption">PISS HUB WORKSPACE</span>
	</span>
</a>
<div id="hub-tabs">
	<div class="hub-tabs-track">
		<span class="hub-tabs-indicator" aria-hidden="true" style="left: <?= $hubActiveTabIndex * $hubTabWidth ?>%; width: <?= $hubTabWidth ?>%;"></span>
		<?php foreach ($hubTabs as $tab) : ?>
		<?php if ($hubCurrent === $tab['dir']) : ?>
			<span class="hub-tab hub-tab-active" aria-current="page" title="<?= $tab['label'] ?>">
				<i class="fa fa-fw <?= $tab['icon'] ?>" aria-hidden="true"></i>
				<span class="hub-tab-label"><?= $tab['label'] ?></span>
			</span>
		<?php else : ?>
			<a href="<?= $hubBase.'/'.$tab['path'] ?>" class="hub-tab" title="<?= $tab['label'] ?>">
				<i class="fa fa-fw <?= $tab['icon'] ?>" aria-hidden="true"></i>
				<span class="hub-tab-label"><?= $tab['label'] ?></span>
			</a>
		<?php endif; ?>
		<?php endforeach; ?>
	</div>
</div>
<style>
	.hub-brand{display:flex;align-items:center;gap:12px;margin:14px 12px 10px;padding:11px 12px;min-height:64px;border:1px solid rgba(148,163,184,.16);border-radius:14px;background:linear-gradient(145deg,#243449,#182235 72%);color:#f8fafc;text-decoration:none;transition:border-color .2s ease,transform .2s ease,box-shadow .2s ease;}
	.hub-brand:hover,.hub-brand:focus{color:#fff;text-decoration:none;border-color:rgba(96,165,250,.45);box-shadow:0 8px 22px rgba(2,8,23,.24);transform:translateY(-1px);}
	.hub-brand:focus{outline:2px solid #93c5fd;outline-offset:2px;}
	.hub-brand-mark{display:flex;flex:0 0 40px;align-items:center;justify-content:center;width:40px;height:40px;border-radius:12px;background:linear-gradient(145deg,#38bdf8,#2563eb);color:#fff;font-size:17px;box-shadow:inset 0 1px 0 rgba(255,255,255,.25),0 5px 12px rgba(37,99,235,.25);}
	.hub-brand-copy{display:flex;min-width:0;flex-direction:column;gap:3px;}
	.hub-brand-title{display:flex;min-width:0;align-items:center;gap:6px;}
	.hub-brand-name{overflow:hidden;color:#f8fafc;font-size:14px;font-weight:700;letter-spacing:.045em;text-overflow:ellipsis;white-space:nowrap;}
	.hub-brand-version{flex:none;padding:2px 5px;border:1px solid rgba(147,197,253,.28);border-radius:6px;background:rgba(37,99,235,.16);color:#bfdbfe;font-size:9px;font-weight:700;letter-spacing:.02em;line-height:1.2;}
	.hub-brand-caption{color:#94a3b8;font-size:9px;font-weight:700;letter-spacing:.16em;white-space:nowrap;}
	#hub-tabs{margin:0 12px;padding:0 0 12px;border-bottom:1px solid rgba(148,163,184,.14);}
	.hub-tabs-track{position:relative;display:flex;min-width:0;padding:0;border:1px solid rgba(148,163,184,.12);border-radius:12px;background:rgba(4,12,24,.3);}
	.hub-tabs-indicator{position:absolute;top:3px;bottom:3px;border-radius:9px;background:linear-gradient(135deg,#facc15,#f59e0b);box-shadow:0 3px 9px rgba(245,158,11,.2);transition:left .25s ease,width .25s ease;}
	.hub-tab{position:relative;z-index:1;display:flex;flex:1;min-width:0;min-height:48px;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:5px 2px;border:0;border-radius:9px;background:transparent;color:#cbd5e1;text-align:center;text-decoration:none;transition:color .2s ease;}
	.hub-tab i{font-size:11px;line-height:1;}
	.hub-tab-label{display:block;max-width:100%;overflow:hidden;font-size:10px;font-weight:600;line-height:1.1;text-overflow:ellipsis;white-space:nowrap;}
	.hub-tab:hover,.hub-tab:focus{background:rgba(255,255,255,.07);color:#fff;text-decoration:none;}
	.hub-tab:focus{outline:2px solid #93c5fd;outline-offset:-2px;}
	.hub-tab-active,.hub-tab-active:hover{background:transparent;color:#1f2937;text-decoration:none;}
	.hub-tab-active i,.hub-tab-active .hub-tab-label{color:#1f2937;}
	body.sidebar-collapse #hub-tabs{display:none;}
	body.sidebar-collapse .hub-brand{justify-content:center;margin:14px 8px 10px;padding:10px 0;}
	body.sidebar-collapse .hub-brand-copy{display:none;}
	body.sidebar-collapse .hub-brand-mark{flex-basis:40px;}
	@media (min-width:1025px){
		body,#page-wrapper{background-color:#f1f3f5!important;}
		#wrapper{background-color:#f1f3f5;}
		.navbar.navbar-fixed-top{top:14px;left:268px;right:14px;height:56px;display:flex;align-items:center;justify-content:space-between;border:0;border-radius:16px;background:#428bca!important;box-shadow:0 10px 28px rgba(15,23,42,.18);transition:left .25s ease;}
		.navbar-header.bg-primary{position:absolute;top:0;left:12px;width:42px;height:56px;min-height:56px;background:transparent!important;}
		.navbar-header{display:flex;align-items:center;min-height:56px;}
		.sidebar-toggle-btn{width:42px;height:42px;margin:0;border:1px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(255,255,255,.07);color:#e2e8f0;transition:background .2s ease,border-color .2s ease,color .2s ease;}
		.sidebar-toggle-btn:hover,.sidebar-toggle-btn:focus{border-color:rgba(147,197,253,.45);border-radius:12px;background:rgba(255,255,255,.14);color:#fff;}
		.sidebar-toggle-btn:focus{outline:2px solid #93c5fd;outline-offset:2px;}
		.navbar .navbar-top-links{position:absolute;top:0;right:0;height:56px;display:flex;align-items:center;gap:8px;margin:0!important;padding:0 12px;float:none!important;}
		.navbar .navbar-top-links>li,.navbar .navbar-top-links>.btn{display:flex;float:none!important;align-items:center;margin:0!important;}
		.navbar .navbar-top-links>li>a.dropdown-toggle{display:flex;min-height:42px;align-items:center;gap:5px;padding:0 13px!important;border:1px solid rgba(255,255,255,.2);border-radius:13px;color:#fff!important;transition:border-color .2s ease,background .2s ease;}
		.navbar .navbar-top-links>li>a.dropdown-toggle:hover,.navbar .navbar-top-links>li>a.dropdown-toggle:focus{background-color:transparent!important;border-color:rgba(255,255,255,.2);color:#fff!important;}
		.navbar .navbar-top-links>li>a.dropdown-toggle:focus{outline:2px solid rgba(255,255,255,.75);outline-offset:2px;}
		.navbar .navbar-top-links>li>a.dropdown-toggle i{color:#fff!important;}
		.navbar .btn.btn-clock,.navbar .btn.btn-calc,.navbar .btn.btn-canvas,.navbar .btn.btn-cut{display:inline-flex;min-width:42px;height:42px;align-items:center;justify-content:center;margin:0!important;padding:0 12px;border:1px solid rgba(255,255,255,.2);border-radius:13px;background:rgba(255,255,255,.14);box-shadow:inset 0 1px 0 rgba(255,255,255,.12);color:#fff;transition:background .2s ease,border-color .2s ease,transform .2s ease;}
		.navbar .btn.btn-clock{gap:8px;}
		.navbar .btn.btn-clock:hover,.navbar .btn.btn-calc:hover,.navbar .btn.btn-canvas:hover,.navbar .btn.btn-cut:not(.blink):hover{transform:translateY(-1px);border-color:rgba(255,255,255,.36);background:rgba(255,255,255,.22)!important;}
		.navbar .btn.btn-clock:focus,.navbar .btn.btn-calc:focus,.navbar .btn.btn-canvas:focus,.navbar .btn.btn-cut:focus{outline:2px solid rgba(255,255,255,.75);outline-offset:2px;}
		.navbar .btn.btn-clock .time-text,.navbar .btn.btn-clock .icon-clock,.navbar .btn.btn-calc i,.navbar .btn.btn-canvas i,.navbar .btn.btn-cut i{color:#fff!important;}
		.navbar .notification{top:-5px;right:-5px;min-width:17px;padding:3px 5px;border:2px solid #428bca;font-size:9px;font-weight:700;line-height:1;}
		.navbar .navbar-top-links .dropdown-menu{margin-top:10px;border:0;border-radius:12px;box-shadow:0 14px 36px rgba(15,23,42,.2);}
		.navbar-default.sidebar{position:fixed!important;top:14px!important;left:14px!important;width:240px!important;height:calc(100vh - 28px)!important;margin:0!important;overflow-x:hidden;overflow-y:auto;border:0;border-radius:18px;background:#172235;box-shadow:none;scrollbar-width:thin;scrollbar-color:rgba(148,163,184,.25) transparent;transition:width .25s ease,transform .25s ease;}
		.sidebar-nav.navbar-collapse{height:auto!important;min-height:100%!important;padding:0!important;overflow:visible!important;border:0!important;border-radius:18px;background:linear-gradient(180deg,#1d2a3e,#172235 34%,#141f30)!important;}
		#page-wrapper{margin-left:270px!important;padding-top:16px!important;border-left:0!important;transition:margin-left .25s ease;}
		body.sidebar-collapse .navbar.navbar-fixed-top{left:104px;}
		body.sidebar-collapse .navbar-default.sidebar{width:76px!important;}
		body.sidebar-collapse #page-wrapper{margin-left:104px!important;}
	}
</style>
