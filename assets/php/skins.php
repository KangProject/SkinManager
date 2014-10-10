<?php

	namespace EphysCMS;

	require_once('inc/skin_formater.inc.php');

	$skinList = scandir('../skins/');
	$skinFormater = new SkinFormatter();

	foreach ($skinList as $skin) {
		$skin = explode('.', $skin);
		if (!isset($skin[1]) || $skin[1] != "png")
			continue;

		$skinFormater->setSkinFromURL('../skins/' . $skin[0] . '.png');
		$skinFormater->createSkinBack('../skins/2D/' . $skin[0] . '.png.back', 0.5);
		echo $skin[0] . ': 2D back created<br>';
		$skinFormater->createSkinFront('../skins/2D/' . $skin[0] . '.png', 0.5);
		echo $skin[0] . ': 2D front created<br>';
		$skinFormater->clearSkin();
	}
?>