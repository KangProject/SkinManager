<?php

	namespace EphysCMS;

	require_once('SkinFormatter.class.php');

	class SkinCreator
	{
		public $name, $description, $model;

		public function upload_url($url, $isTemp = false)
		{
			$image = @getimagesize($url);

			if ($isTemp) {
				if (!is_array($image) || ($image[3] != 'width="64" height="32"' && $image[3] != 'width="64" height="64"')
					|| ($image['mime'] != 'image/png')
				)
					$url = "http://skin.outadoc.fr/assets/img/char.png";

				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-type: image/png');

				echo file_get_contents($url);
			} else {
				if (!is_array($image)) {
					return ['error' => [Language::translate('ERROR_NO_URL')]];
				} else if ($image[3] != 'width="64" height="32"' && $image[3] != 'width="64" height="64"') {
					return ['error' => [Language::translate('ERROR_SKIN_DIM')]];
				} else if ($image['mime'] != 'image/png') {
					return ['error' => [$Language::translate('ERROR_SKIN_TYPE')]];
				}

				$skin_id = $this->upload();

				if (!is_array($skin_id)) {
					$image = file_get_contents($url);
					$this->updateSkin($image, $skin_id);
					return ['success' => true, 'error' => false, 'id' => $skin_id];
				} else {
					return $skin_id;
				}
			}
		}

		private function upload()
		{
			if (empty($this->name))
				return ['error' => [Language::translate('ERROR_SKINNAME')]];

			if ($this->description === null)
				$this->description = '';

			$bdd   = Database::getInstance();
			$query = $bdd->prepare('INSERT INTO sm_skins(`owner`, `title`, `description`, `model`) VALUES(:owner, :title, :description, :model)');

			$query->bindParam(':owner', $_SESSION['user_id'], \PDO::PARAM_INT);
			$query->bindParam(':title', $this->name, \PDO::PARAM_STR);
			$query->bindParam(':model', $this->model, \PDO::PARAM_STR);
			$query->bindParam(':description', $this->description, \PDO::PARAM_STR);

			$query->execute();
			$query->closeCursor();

			return $bdd->lastInsertId();
		}

		public function updateSkin($skin, $skinId)
		{
			$this->deleteSkinPreview($skinId);
			file_put_contents(ROOT . 'assets/skins/' . $skinId . '.png', $skin);

			$skinFormater = new SkinFormatter();
			$skinFormater->setSkinData(imagecreatefromstring($skin));
			$skinFormater->createSkinBack(ROOT . 'assets/skins/2D/' . $skinId . '.png.back', 0.5);
			$skinFormater->createSkinFront(ROOT . 'assets/skins/2D/' . $skinId . '.png', 0.5);
			$skinFormater->clearSkin();

			return ['error' => false];
		}

		public function deleteSkinPreview($skinId)
		{
			@unlink(ROOT . 'assets/skins/' . $skinId . '.png');
			@unlink(ROOT . 'assets/skins/2D/' . $skinId . '.png');
			@unlink(ROOT . 'assets/skins/2D/' . $skinId . '.png.back');
		}
	}
