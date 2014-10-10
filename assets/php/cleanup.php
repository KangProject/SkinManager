<?php

	namespace EphysCMS;

	require_once('inc/Database.class.php');

	$db    = Database::getInstance();
	$query = $db->query('SELECT `id` FROM `skins`');
	$i     = 0;

	while ($skin = $query->fetch(\PDO::FETCH_ASSOC)) {
		if (!file_exists('../skins/' . $skin['id'] . '.png')) {
			$db->exec('DELETE FROM `skins` WHERE `id` = ' . $skin['id'] . ' LIMIT 1');
			$i++;
		}
	}

	echo 'Deleted ' . $i . ' fake skins';
