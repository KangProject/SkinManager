<?php
	require_once('inc/Database.class.php');

	$db    = Database::getInstance();
	$query = $db->query('SELECT `id` FROM `skins`');
	$i     = 0;
	while ($skin = $query->fetch(PDO::FETCH_ASSOC)) {
		if (!file_exists('../skins/' . $skin['id'] . '.png')) {
			$db->exec('DELETE FROM `skins` WHERE `id` = ' . $skin['id'] . ' LIMIT 1');
			$i++;
		}
	}

	echo 'Deleted ' . $i . ' fake skins';

// foreach($skinList as $skin) {
// 	$skin = explode('.', $skin);
// 	if(!isset($skin[1]) || $skin[1] != "png")
// 		continue;

// 	$skinFormater->setSkinFromURL('../skins/'.$skin[0].'.png');
// 	$skinFormater->createSkinBack('../skins/2D/'.$skin[0].'.png.back', 0.5);
// 	echo $skin[0].': 2D back created<br>';
// 	$skinFormater->createSkinFront('../skins/2D/'.$skin[0].'.png', 0.5);
// 	echo $skin[0].': 2D front created<br>';
// 	$skinFormater->clearSkin();
// }
?>