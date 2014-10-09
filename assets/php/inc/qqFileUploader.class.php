<?php

	class qqFileUploader
	{
		private static $isWin;
		public $allowedExtensions = array();
		public $sizeLimit = null;
		public $inputName = 'qqfile';
		public $chunksFolder = 'chunks'; // Once in 1000 requests on avg
		public $chunksCleanupProbability = 0.001; // One week
		public $chunksExpireIn = 604800;
		protected $uploadName;

		function __construct()
		{
			$this->sizeLimit = $this->toBytes(ini_get('upload_max_filesize'));
			self::$isWin     = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
		}

		/**
		 * Converts a given size with units to bytes.
		 *
		 * @param string $str
		 */
		protected function toBytes($str)
		{
			$val  = trim($str);
			$last = strtolower($str[strlen($str) - 1]);
			switch ($last) {
				case 'g':
					$val *= 1024;
				case 'm':
					$val *= 1024;
				case 'k':
					$val *= 1024;
			}
			return $val;
		}

		public function getName()
		{
			if (isset($_REQUEST['qqfilename']))
				return $_REQUEST['qqfilename'];

			if (isset($_FILES[$this->inputName]))
				return $_FILES[$this->inputName]['name'];
		}

		public function getUploadName()
		{
			return $this->uploadName;
		}

		public function getUploadedRessource()
		{
			if (($validation = $this->validateUpload()) !== true) {
				return $validation;
			}

			return $_FILES[$this->inputName];
		}

		public function validateUpload()
		{
			if ($this->toBytes(ini_get('post_max_size')) < $this->sizeLimit || $this->toBytes(ini_get('upload_max_filesize')) < $this->sizeLimit) {
				$size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
				return ['error' => ["Server error. Increase post_max_size and upload_max_filesize to " . $size]];
			}

			if (!isset($_SERVER['CONTENT_TYPE'])) {
				return ['error' => ["No files were uploaded."]];
			} else if (strpos(strtolower($_SERVER['CONTENT_TYPE']), 'multipart/') !== 0) {
				return ['error' => ["Server error. Not a multipart request. Please set forceMultipart to default value (true)."]];
			}

			$file = $_FILES[$this->inputName];
			$size = $file['size'];

			if ($size === 0)
				return ['error' => ['Fichier vide.']];

			if ($size > $this->sizeLimit)
				return ['error' => ['Fichier trop grand.']];

			$pathinfo = pathinfo($_FILES[$this->inputName]['name']);
			$ext      = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

			if ($this->allowedExtensions && !in_array(strtolower($ext), array_map("strtolower", $this->allowedExtensions))) {
				$these = implode(', ', $this->allowedExtensions);
				return ['error' => ['Cette extension est invalide, elle devrait être ' . $these . '.']];
			}

			return true;
		}

		public function storeUpload($uploadDirectory, $name)
		{
			if ($name === null || $name === '')
				throw new Exception('Filename empty');

			if (is_writable($this->chunksFolder) && 1 == mt_rand(1, 1 / $this->chunksCleanupProbability))
				$this->cleanupChunks();

			$folderInaccessible = !is_writable($uploadDirectory) || (!self::$isWin && !is_executable($uploadDirectory));

			if ($folderInaccessible)
				return ['error' => ["Server error. Uploads directory isn't writable" . ((!self::$isWin) ? " or executable." : ".")]];

			if (($validation = $this->validateUpload()) !== true) {
				return $validation;
			}

			// Save a chunk
			$totalParts = isset($_REQUEST['qqtotalparts']) ? (int)$_REQUEST['qqtotalparts'] : 1;

			if ($totalParts > 1) {
				$chunksFolder = $this->chunksFolder;
				$partIndex    = (int)$_REQUEST['qqpartindex'];
				$uuid         = $_REQUEST['qquuid'];

				if (!is_writable($chunksFolder) && !is_executable($uploadDirectory)) {
					return ['error' => ["Server error. Chunks directory isn't writable or executable."]];
				}

				$targetFolder = $this->chunksFolder . DIRECTORY_SEPARATOR . $uuid;

				if (!file_exists($targetFolder)) {
					mkdir($targetFolder);
				}

				$target  = $targetFolder . '/' . $partIndex;
				$success = move_uploaded_file($_FILES[$this->inputName]['tmp_name'], $target);

				// Last chunk saved successfully
				if ($success AND ($totalParts - 1 == $partIndex)) {

					$target           = $this->getUniqueTargetPath($uploadDirectory, $name);
					$this->uploadName = basename($target);

					$target = fopen($target, 'wb');

					for ($i = 0; $i < $totalParts; $i++) {
						$chunk = fopen($targetFolder . DIRECTORY_SEPARATOR . $i, "rb");
						stream_copy_to_stream($chunk, $target);
						fclose($chunk);
					}

					// Success
					fclose($target);

					for ($i = 0; $i < $totalParts; $i++) {
						unlink($targetFolder . DIRECTORY_SEPARATOR . $i);
					}

					rmdir($targetFolder);

					return array("success" => true);

				}

				return array("success" => true);

			} else {
				// $target = $this->getUniqueTargetPath($uploadDirectory, $name);
				$target = $uploadDirectory . $name;

				if ($target) {
					$this->uploadName = basename($target);

					if (move_uploaded_file($_FILES[$this->inputName]['tmp_name'], $target)) {
						return array('success' => true);
					}
				}

				return ['error' => ['Could not save uploaded file. The upload was cancelled, or server error encountered']];
			}
		}

		/**
		 * Deletes all file parts in the chunks folder for files uploaded
		 * more than chunksExpireIn seconds ago
		 */
		protected function cleanupChunks()
		{
			foreach (scandir($this->chunksFolder) as $item) {
				if ($item == "." || $item == "..")
					continue;

				$path = $this->chunksFolder . DIRECTORY_SEPARATOR . $item;

				if (!is_dir($path))
					continue;

				if (time() - filemtime($path) > $this->chunksExpireIn) {
					$this->removeDir($path);
				}
			}
		}

		/**
		 * Removes a directory and all files contained inside
		 *
		 * @param string $dir
		 */
		protected function removeDir($dir)
		{
			foreach (scandir($dir) as $item) {
				if ($item == "." || $item == "..")
					continue;

				unlink($dir . DIRECTORY_SEPARATOR . $item);
			}
			rmdir($dir);
		}
	}

?>