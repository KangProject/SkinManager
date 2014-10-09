<?php

	class SFormater
	{
		private $skindata = null;

		public function __construct($url = null)
		{
			if ($url !== null) {
				if (!$this->setSkinFromURL($url))
					throw new Exception('Skin not found');
			}
		}

		public function setSkinFromURL($url)
		{
			if (file_exists($url)) {
				$this->skindata = imagecreatefrompng($url);
				return true;
			}

			return false;
		}

		public function setSkinData($data)
		{
			$this->skindata = $data;
		}

		public function clearSkin()
		{
			imagedestroy($this->skindata);
		}

		public function createSkinBack($output, $size = 1)
		{
			$preview = imagecreatetruecolor(16, 32);

			$transparent = imagecolorallocatealpha($preview, 255, 255, 255, 127);
			imagefill($preview, 0, 0, $transparent);

			// Affichage de la face du skin
			imagecopy($preview, $this->skindata, 4, 0, 24, 8, 8, 8);

			// Affichage de l'extra du skin
			imagecopy($preview, $this->skindata, 4, 8, 32, 20, 8, 12);

			// Affichage des bras du skin
			imagecopy($preview, $this->skindata, 0, 8, 52, 20, 4, 12);
			imagecopy($preview, $this->skindata, 12, 8, 52, 20, 4, 12);

			// Affichage des jambes du skin
			imagecopy($preview, $this->skindata, 4, 20, 12, 20, 4, 12);
			imagecopy($preview, $this->skindata, 8, 20, 12, 20, 4, 12);

			// Affichage du casque du skin
			imagecopy($preview, $this->skindata, 4, 0, 56, 8, 8, 8);

			$fullsize = imagecreatetruecolor(200 * $size, 400 * $size);

			imagesavealpha($fullsize, true);
			$transparent = imagecolorallocatealpha($fullsize, 255, 255, 255, 127);
			imagefill($fullsize, 0, 0, $transparent);

			imagecopyresized($fullsize, $preview, 0, 0, 0, 0, imagesx($fullsize), imagesy($fullsize), imagesx($preview), imagesy($preview));

			imagepng($fullsize, $output);
		}

		public function createSkinFront($output, $size = 1)
		{
			$preview = imagecreatetruecolor(16, 32);

			$transparent = imagecolorallocatealpha($preview, 255, 255, 255, 127);
			imagefill($preview, 0, 0, $transparent);

			// Affichage de la face du skin
			imagecopy($preview, $this->skindata, 4, 0, 8, 8, 8, 8);

			// Affichage de l'extra du skin
			imagecopy($preview, $this->skindata, 4, 8, 20, 20, 8, 12);

			// Affichage des bras du skin
			imagecopy($preview, $this->skindata, 0, 8, 44, 20, 4, 12);
			imagecopy($preview, $this->skindata, 12, 8, 44, 20, 4, 12);

			// Affichage des jambes du skin
			imagecopy($preview, $this->skindata, 4, 20, 4, 20, 4, 12);
			imagecopy($preview, $this->skindata, 8, 20, 4, 20, 4, 12);

			// Affichage du casque du skin
			imagecopy($preview, $this->skindata, 4, 0, 40, 8, 8, 8);

			$fullsize = imagecreatetruecolor(200 * $size, 400 * $size);

			imagesavealpha($fullsize, true);
			$transparent = imagecolorallocatealpha($fullsize, 255, 255, 255, 127);
			imagefill($fullsize, 0, 0, $transparent);

			imagecopyresized($fullsize, $preview, 0, 0, 0, 0, imagesx($fullsize), imagesy($fullsize), imagesx($preview), imagesy($preview));

			imagepng($fullsize, $output);
		}
	}

?>