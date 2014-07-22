<?php
require_once(dirname(__FILE__).'/skin_formater.inc.php');

class skin_creator {
	public $name, $description;

	public function updateSkin($skin, $skin_id) {
		file_put_contents(dirname(__FILE__).'/../../skins/'.$skin_id.'.png', $skin);

		$skinFormater = new SFormater();
		$skinFormater->setSkinData(imagecreatefromstring($skin));
		$skinFormater->createSkinBack(dirname(__FILE__).'/../../skins/2D/'.$skin_id.'.png.back', 0.5);
		$skinFormater->createSkinFront(dirname(__FILE__).'/../../skins/2D/'.$skin_id.'.png', 0.5);
		$skinFormater->clearSkin();

		return ['error' => false];
	}

	public function upload_url($url, $isTemp = false) {
		$image = @getimagesize($url);

		if($isTemp) {
			if(!is_array($image) || ($image[3] != 'width="64" height="32"') || ($image['mime'] != 'image/png'))
				$url = "http://beta.skin.outadoc.fr/assets/img/char.png";

			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-type: image/png');

			echo file_get_contents($url);
		} else {
			if(!is_array($image))
				return ['error' => [Language::translate('ERROR_NO_URL')]];
			elseif($image[3] != 'width="64" height="32"')
				return ['error' => [Language::translate('ERROR_SKIN_DIM')]];
			elseif($image['mime'] != 'image/png')
				return ['error' => [$Language::translate('ERROR_SKIN_TYPE')]];

			$skin_id = $this->upload();

			if(!is_array($skin_id)) {
				$image = file_get_contents($url);

				$this->updateSkin($image, $skin_id);

				return ['success' => true, 'error' => false, 'id' => $skin_id];
			} else
				return $skin_id;
		}
	}

	private function upload() {
		if(empty($this->name))
			return ['error' => [Language::translate('ERROR_SKINNAME')]];

		if($this->description === null)
			$this->description = '';

		$bdd = Database::getInstance();
		$query = $bdd->prepare('INSERT INTO skins(`owner`, `title`, `description`) VALUES(:owner, :title, :description)');
		$query->bindParam(':owner', $_SESSION['user_id'], PDO::PARAM_INT);
		$query->bindParam(':title', $this->name, PDO::PARAM_STR);
		$query->bindParam(':description', $this->description, PDO::PARAM_STR);
		$query->execute();
		$query->closeCursor();

		return $bdd->lastInsertId();
	}
}
?>