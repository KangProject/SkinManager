<?php namespace EphysCMS; ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<base href="<?= WEBSITE_ROOT ?>">

	<meta name="keywords" lang="fr" content="skin, manage, favori, outadoc, ephyspotato, Minecraft">
	<meta name="description" content="SkinSwitch's companion. Easily switch between your Minecraft skins!">

	<title>Skin Manager</title>

	<link rel="shortcut icon" href="">
	<link rel="stylesheet" media="screen" type="text/css" title="design" href="assets/css/main.css">
	<link rel="stylesheet" media="screen" type="text/css" title="design" href="assets/css/scrollbar.css">
	<link rel="stylesheet" media="screen" type="text/css" href="assets/js/plugins/fineuploader/fineuploader-3.6.4.css">

	<script type="text/javascript" src="assets/js/language/<?= Language::getLanguage() ?>.lang.js"></script>
	<script type="text/javascript" src="assets/js/plugins/jquery-1.7.1.min.js"></script>
	<script type="text/javascript" src="assets/js/plugins/bootstrap-button.min.js"></script>
	<script type="text/javascript" src="assets/js/FormHandler.class.js"></script>

	<?= (isset($_SESSION['user_id'])) ? '
	<script type="text/javascript" src="assets/js/Modal.class.js"></script>
	<script type="text/javascript" src="assets/js/Notifier.class.js"></script>
	<script type="text/javascript" src="assets/js/SkinManager.class.js"></script>
	<script type="text/javascript" src="assets/js/preload.js"></script>
	<script type="text/javascript" src="assets/js/plugins/Three.js"></script>
	<script type="text/javascript" src="assets/js/plugins/RequestAnimationFrame.js"></script>
	<script type="text/javascript" src="assets/js/SkinRender.class.js"></script>' : ''
	?>
</head>
<body>
<aside id="skinList"></aside>
<div id="body">
	<nav>
		<h1><a href="">Skin Manager</a></h1>
		<ul><?php
				if (isset($_SESSION['user_id']))
					echo '<li><a href="#" id="showSkins">' . Language::translate('SKIN_LIST') . '</a></li>
					<li><a href="newskin/">' . Language::translate('SKIN_ADD') . '</a></li>
					<li><a href="profiles/">' . Language::translate('PROFILES_BTN') . '</a></li>
					<li><a href="account/">' . Language::translate('ACCOUNT') . '</a></li>
					<li><a href="logout/">' . Language::translate('DISCONNECT') . '</a></li>';
				else
					echo '<li><a href="register/">' . Language::translate('REGISTER') . '</a></li>';
			?>
		</ul>
	</nav>