<?php

	namespace EphysCMS;

	/**
	 * Dev note:
	 *
	 * Currently the methods requiring login are unreachable without being logged from the website and thus cannot be used by third-party tools.
	 * I am not sure on how to implement a login for third-party tools yet.
	 */
	class API
	{
		private $refl = null;
		private $allowedOrigins = ['http://skin.outadoc.fr', 'http://skin', 'http://localhost'];

		public function __construct()
		{
			if (!class_exists('Database')) {
				require_once dirname(__FILE__) . '/inc/Database.class.php';
			}
		}

		public function selectMethod($data)
		{
			if (isset($data['method']) && $this->methodExists($data['method'])) {

				if ($this->methodIs("protected", $data['method']) && $this->isEnvProtected()) {
					$this->getRefl($data['method'])->setAccessible(true);
				} else if (!$this->methodIs("public", $data['method'])) {
					return ['error_code' => 403, 'error' => ['Access denied']];
				}

				$message = $this->argsMatch($data, $data['method']);

				if ($message !== true) {
					return $message;
				} else {
					return $this->callMethod($data['method'], $data);
				}

			} else {
				return ['error_code' => 404, 'error' => ['Unknown method']];
			}
		}

		private function methodExists($method)
		{
			return ($method !== 'selectMethod') && ($method !== '__construct') && method_exists($this, $method) && !$this->methodIs("private", $method);
		}

		private function methodIs($type, $method)
		{
			$refl = $this->getRefl($method);

			switch ($type) {
				case "static":
					return $refl->isStatic();
				case "public":
					return $refl->isPublic();
				case "private":
					return $refl->isPrivate();
				case "protected":
					return $refl->isProtected();
				case "final":
					return $refl->isFinal();
				default:
					return false;
			}
		}

		private function getRefl($method)
		{
			if ($this->refl === null)
				$this->refl = new \ReflectionMethod($this, $method);

			return $this->refl;
		}

		private function isEnvProtected()
		{
			return !isset($_SERVER['HTTP_ORIGIN']) || in_array($_SERVER['HTTP_ORIGIN'], $this->allowedOrigins);
		}

		private function argsMatch($args, $method)
		{
			$refl = $this->getRefl($method);

			foreach ($refl->getParameters() as $arg) {
				if (!$arg->isOptional() && !array_key_exists((string)$arg->name, $args))
					return array('error_code' => 403, 'error' => ['Missing parameter ' . $arg->name]);
			}

			return true;
		}

		private function callMethod($method, $args)
		{
			$reflection = $this->getRefl($method);

			$pass = array();
			foreach ($reflection->getParameters() as $param) {
				if (isset($args[$param->getName()]))
					$pass[] = $args[$param->getName()];
				else
					$pass[] = $param->getDefaultValue();
			}
			return $reflection->invokeArgs($this, $pass);
		}

		/**
		 * Returns the list of author whose username matches (@link $match)
		 * Note: the dot (".") in (@link $match) is treated like regex dots, please escape it if you need to search the actual dot.
		 * All other regex characters are automatically escaped
		 * (@link $max) must be ranged from 1 to 20
		 *
		 * @author  EphysPotato
		 * @version 1.2
		 * @param   String $match the researched username
		 * @param   int $max the maximum amount of returned results
		 * @param   int $start the total of result to skip before starting to collect the results
		 * @return  a JSON Array  containing JSON Objects containing the keys 'id' and 'username'
		 */
		public function loadUserList($match, $max = 15, $start = 0)
		{
			$bdd = Database::getInstance();

			if (empty($match))
				return [];

			$max   = (int)$max;
			$start = (int)$start;
			if ($max < 1 || $max > 20)
				return ['error' => ['$max muse be ranged from 1 to 20']];

			$match = addcslashes($match, '[]()*?^\\+|$');
			$cmd   = "SELECT DISTINCT m.`username`, m.`id` FROM `skins` s LEFT JOIN `members` m ON m.`id` = s.`owner` WHERE m.`username` REGEXP :username ORDER BY m.`username` LIMIT :start, :max";
			$query = $bdd->prepare($cmd);
			$query->bindParam(':username', $match, \PDO::PARAM_STR);
			$query->bindParam(':max', $max, \PDO::PARAM_INT);
			$query->bindParam(':start', $start, \PDO::PARAM_INT);
			$query->execute();
			$data = $query->fetchAll(\PDO::FETCH_ASSOC);
			$query->closeCursor();

			return $data;
		}

		/**
		 * Returns the list of skins whose owner id is equal to (@link $user)
		 * if (@link $max) is < 1, it will return every matched skin
		 *
		 * @author  EphysPotato
		 * @version 1.1
		 * @param   int $user the skinlist owner
		 * @param   int $max the maximum amount of returned results
		 * @param   int $start the total of result to skip before starting to collect the results (used only if $max > 0)
		 * @return  a JSON Array  containing a JSON Object per skin which itself contains the keys 'id', 'title' and 'description'
		 */
		public function loadSkins($user, $max = 0, $start = 0)
		{
			if (!is_numeric($user))
				return ['error' => ['user must be of type int']];

			$bdd  = Database::getInstance();
			$user = (int)$user;
			$max  = (int)$max;
			if ($max > 0) {
				$start = (int)$start;

				$query = $bdd->prepare('SELECT s.`id`, s.`title`, s.`description`, s.`model`, m.`username` AS `owner_username` FROM `skins` s LEFT JOIN `members` m ON m.`id` = s.`owner` WHERE s.`owner` = :owner LIMIT :start, :max');
				$query->bindParam(':max', $max, \PDO::PARAM_INT);
				$query->bindParam(':start', $start, \PDO::PARAM_INT);
			} else {
				$query = $bdd->prepare('SELECT s.`id`, s.`title`, s.`description`, s.`model`, m.`username` AS `owner_username` FROM `skins` s LEFT JOIN `members` m ON m.`id` = s.`owner` WHERE s.`owner` = :owner');
			}

			$query->bindParam(':owner', $user, \PDO::PARAM_INT);
			$query->execute();
			$data = $query->fetchAll(\PDO::FETCH_ASSOC);
			$query->closeCursor();

			return $data;
		}

		// =====================================================================================
		//  Public methods
		// =====================================================================================

		/**
		 * Returns the details of the skin whose id matches (@link $id)
		 *
		 * @author  EphysPotato
		 * @version 1.0
		 * @param   int $id the skin id
		 * @return  a JSON Object containing the keys 'owner', 'title' and 'description' on success
		 * @return  a JSON Object containing the key 'error', an array of Strings on failure
		 */
		public function loadSkin($id)
		{
			if (!is_numeric($id))
				return ['error' => ['id must be of type int']];

			$id  = (int)$id;
			$bdd = Database::getInstance();

			$query = $bdd->prepare('SELECT m.`username` AS `owner_username`, s.`owner` AS `owner_id`, s.`title`, s.`description`, s.`model` FROM `skins` s LEFT JOIN `members` m ON m.`id` = s.`owner` WHERE s.`id` = :id LIMIT 1');
			$query->bindParam(':id', $id, \PDO::PARAM_INT);
			$query->execute();
			$data = $query->fetch(\PDO::FETCH_ASSOC);
			$query->closeCursor();

			if ($data === false)
				return ['error' => ['skin does not exists']];

			return $data;
		}

		/**
		 * Returns the details of the skin whose name matches (@link $match)
		 * Note: the dot (".") in (@link $match) is treated like regex dots, please escape it if you need to search the actual dot.
		 * All other regex characters are automatically escaped
		 * (@link $max) must be ranged from 1 to 20
		 *
		 * @author  EphysPotato
		 * @version 1.0
		 * @param   String $match A skin name
		 * @param   int $max The maximum amount of returned results
		 * @param   int $start the total of result to skip before starting to collect the results
		 * @return  a JSON Object containing the keys 'id', 'owner', 'title' and 'description' on success
		 * @return  a JSON Object containing the key 'error', an array of Strings on failure
		 */
		public function searchSkinByName($match, $max = 15, $start = 0)
		{
			$bdd = Database::getInstance();

			if (empty($match))
				return [];

			$max = (int)$max;
			if ($max < 1 || $max > 20)
				return ['error' => ['$max muse be ranged from 1 to 20']];

			$start = (int)$start;
			$match = addcslashes($match, '[]()*?^\\+|$');
			$bdd   = Database::getInstance();
			$query = $bdd->prepare('SELECT m.`username` AS `owner_username`, s.`owner` AS `owner_id`, s.`title`, s.`description`, s.`id`, s.`model` FROM `skins` s LEFT JOIN `members` m ON m.`id` = s.`owner` WHERE s.`title` REGEXP :title ORDER BY s.`title` LIMIT :start, :max');
			$query->bindParam(':title', $match, \PDO::PARAM_STR);
			$query->bindParam(':max', $max, \PDO::PARAM_INT);
			$query->bindParam(':start', $start, \PDO::PARAM_INT);
			$query->execute();
			$data = $query->fetchAll(\PDO::FETCH_ASSOC);
			$query->closeCursor();

			return $data;
		}

		/**
		 * Returns the details of the (@link $max) lasted uploaded skins
		 * (@link $max) must be ranged from 1 to 20
		 *
		 * @author  EphysPotato
		 * @version 1.0
		 * @param   int $max The maximum amount of returned results
		 * @param   int $start the total of result to skip before starting to collect the results
		 * @return  a JSON Object containing the keys 'id', 'owner', 'title' and 'description' on success
		 * @return  a JSON Object containing the key 'error', an array of Strings on failure
		 */
		public function getLastestSkins($max = 15, $start = 0)
		{
			$max = (int)$max;
			if ($max < 1 || $max > 20)
				return ['error' => ['$max muse be ranged from 1 to 20']];

			$start = (int)$start;
			$bdd   = Database::getInstance();
			$query = $bdd->prepare('SELECT m.`username` AS `owner_username`, s.`owner` AS `owner_id`, s.`title`, s.`description`, s.`id`, s.`model` FROM `skins` s LEFT JOIN `members` m ON m.`id` = s.`owner` ORDER BY s.`id` DESC LIMIT :start, :max');
			$query->bindParam(':max', $max, \PDO::PARAM_INT);
			$query->bindParam(':start', $start, \PDO::PARAM_INT);
			$query->execute();
			$data = $query->fetchAll(\PDO::FETCH_ASSOC);
			$query->closeCursor();

			return $data;
		}

		/**
		 * Returns the details of (@link $max) randomly selected skins
		 * (@link $max) must be ranged from 1 to 20
		 *
		 * @author  EphysPotato
		 * @version 1.0
		 * @param   int $max The maximum amount of returned results
		 * @return  a JSON Object containing the keys 'id', 'owner', 'title' and 'description' on success
		 * @return  a JSON Object containing the key 'error', an array of Strings on failure
		 */
		public function getRandomSkins($max = 15)
		{
			$bdd = Database::getInstance();

			$max = (int)$max;
			if ($max < 1 || $max > 20)
				return ['error' => ['$max muse be ranged from 1 to 20']];

			$bdd   = Database::getInstance();
			$query = $bdd->prepare('SELECT m.`username` AS `owner_username`, s.`owner` AS `owner_id`, s.`title`, s.`description`, s.`id`, s.`model` FROM `skins` s LEFT JOIN `members` m ON m.`id` = s.`owner` ORDER BY Rand() DESC LIMIT :max');
			$query->bindParam(':max', $max, \PDO::PARAM_INT);
			$query->execute();
			$data = $query->fetchAll(\PDO::FETCH_ASSOC);
			$query->closeCursor();

			return $data;
		}

		public function getPreview($url)
		{
			require_once(dirname(__FILE__) . '/inc/skin_creator.inc.php');

			$uploader = new SkinCreator();
			$uploader->upload_url($url, true);
		}

		/**
		 * Returns the skin (@link $id) as a binary-encoded image if (@link $base64) equals false or as a base64-encoded image otherwise
		 *
		 * @author  EphysPotato
		 * @version 1.1
		 * @param   int $id the skin id
		 * @param   bool $base64 select the output encoding as base64 instead of binary
		 * @return  a binary-encoded image if (@link $base64) equals false
		 * @return  a base64-encoded image if (@link $base64) equals true
		 */
		public function getSkin($id, $base64 = false)
		{
			if (!is_numeric($id) || !file_exists(ROOT . 'assets/skins/' . $id . '.png')) {
				header('Location: http://s3.amazonaws.com/MinecraftSkins/char.png');
				return;
			}

			if (!is_bool($base64))
				$base64 = ($base64 === 'false') ? false : (bool)$base64;

			if ($base64) {
				echo base64_encode(file_get_contents(ROOT . 'assets/skins/' . $id . '.png'));
				return;
			}

			$bdd = Database::getInstance();

			$query = $bdd->prepare('SELECT `title` FROM `skins` WHERE `id` = :id LIMIT 1');
			$query->bindParam(':id', $id, \PDO::PARAM_INT);
			$query->execute();
			$skin = $query->fetch();
			$query->closeCursor();

			$file = ROOT . 'assets/skins/' . $id . '.png';
			$name = (($skin === false) ? 'untitled' : $skin['title']) . '.png';

			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . $name);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			ob_clean();
			flush();
			readfile($file);
		}

		public function addSkin($name, $description, $url = '', $model = 'steve')
		{
			$errors = [];
			if (!self::isLogged())
				$errors[] = 'user not logged in';

			if (empty($name))
				$errors[] = Language::translate('ERROR_SKINNAME');

			if (!empty($errors))
				return ['error' => $errors];

			require_once(dirname(__FILE__) . '/inc/skin_creator.inc.php');

			$uploader              = new SkinCreator();
			$uploader->name        = $name;
			$uploader->description = $description;
			$uploader->model       = $model;

			if ($url !== '') {
				return $uploader->upload_url($url);
			} else {
				return ['error' => [Language::translate('ERROR_NO_URL')]];
			}
		}

		private function isLogged()
		{
			if (session_status() !== PHP_SESSION_ACTIVE) {
				session_start();
			}

			return isset($_SESSION['user_id']);
		}

		// =====================================================================================
		//  Public methods requiring the user to be logged in
		// =====================================================================================

		public function uploadSkinFromURL($url, $name, $description, $model)
		{
			if (!self::isLogged())
				return ['error' => ['user not logged in']];

			require_once(dirname(__FILE__) . '/inc/skin_creator.inc.php');

			$uploader              = new SkinCreator();
			$uploader->name        = $name;
			$uploader->description = $description;
			$uploader->model       = $model;

			return $uploader->upload_url($url);
		}

		public function uploadSkin($name, $description, $model)
		{
			if (!self::isLogged())
				return ['error' => ['user not logged in']];

			require_once dirname(__FILE__) . '/inc/skin_creator.inc.php';
			require_once dirname(__FILE__) . '/inc/qqFileUploader.class.php';

			$uploader                    = new \qqFileUploader();
			$uploader->allowedExtensions = array('png');
			$uploader->sizeLimit         = 102400;
			$uploader->inputName         = 'qqfile';
			$uploader->chunksFolder      = ROOT . 'assets/misc/qqGarbage/';

			$data = $uploader->getUploadedRessource();
			if (is_array($data['error']))
				return $data;

			if ($data['error'] !== 0)
				return ['error' => [Language::translate('ERROR_UNKNOW')], 'detail' => $data['error']];

			$uploader              = new SkinCreator();
			$uploader->name        = $name;
			$uploader->description = $description;
			$uploader->model       = $model;

			return $uploader->upload_url($data['tmp_name']);
		}

		public function deleteSkins($skinList)
		{
			$errors = [];
			if (!self::isLogged())
				$errors[] = 'user not logged in';

			if (!is_array($skinList))
				$errors[] = 'skinList must be of type array';

			if (!empty($errors))
				return ['error' => $errors];

			$bdd   = Database::getInstance();
			$query = $bdd->prepare('DELETE FROM `skins` WHERE `id` = :id AND `owner` = :owner');
			$query->bindParam(':owner', $_SESSION['user_id'], \PDO::PARAM_INT);
			foreach ($skinList as $skinId) {
				$query->bindParam(':id', $skinId, \PDO::PARAM_INT);
				$query->execute();
				if ($query->rowCount() === 1) {
					@unlink(ROOT . 'assets/skins/' . $skinId . '.png');
					@unlink(ROOT . 'assets/skins/2D/' . $skinId . '.png');
					@unlink(ROOT . 'assets/skins/2D/' . $skinId . '.png.back');
				}
			}

			$query->closeCursor();

			return ['error' => false];
		}

		public function duplicateSkin($sourceSkin_id)
		{
			$errors = [];
			if (!self::isLogged())
				$errors[] = 'user not logged in';

			if (!is_numeric($sourceSkin_id))
				$errors[] = 'id not numeric';

			if (!empty($errors))
				return ['error' => $errors];

			$sourceSkin_id = (int)$sourceSkin_id;
			$bdd           = Database::getInstance();
			$query         = $bdd->prepare('SELECT `title`, `description`, `model` FROM `skins` WHERE `id` = :id LIMIT 1');
			$query->bindParam(':id', $sourceSkin_id, \PDO::PARAM_INT);
			$query->execute();
			$sourceSkin_data = $query->fetch(\PDO::FETCH_ASSOC);
			$query->closeCursor();

			if ($sourceSkin_data === false)
				return ['error' => ['cannot find requested skin']];

			$query = $bdd->prepare('INSERT INTO `skins`(`owner`, `title`, `description`, `model`) VALUES(:owner, :title, :description, :model)');

			$query->bindValue(':title', $sourceSkin_data['title'], \PDO::PARAM_STR);
			$query->bindValue(':description', $sourceSkin_data['description'], \PDO::PARAM_STR);
			$query->bindParam(':owner', $_SESSION['user_id'], \PDO::PARAM_INT);
			$query->bindValue(':model', $sourceSkin_data['model'], \PDO::PARAM_STR);

			$query->execute();
			$newSkin_id = $bdd->lastInsertId();
			$query->closeCursor();

			@copy(ROOT . 'assets/skins/' . $sourceSkin_id . '.png', ROOT . 'assets/skins/' . $newSkin_id . '.png');
			@copy(ROOT . 'assets/skins/2D/' . $sourceSkin_id . '.png', ROOT . 'assets/skins/2D/' . $newSkin_id . '.png');
			@copy(ROOT . 'assets/skins/2D/' . $sourceSkin_id . '.png.back', ROOT . 'assets/skins/2D/' . $newSkin_id . '.png.back');

			return ['error'       => false, 'id' => $newSkin_id, 'title' => $sourceSkin_data['title'],
			        'description' => $sourceSkin_data['description'], 'model' => $sourceSkin_data['model']];
		}

		public function editSkin($id, $title, $description, $model)
		{
			$errors = [];
			if (!self::isLogged())
				$errors[] = 'user not logged in';

			if (!is_numeric($id))
				$errors[] = 'id not numeric';

			if (empty($title))
				$errors[] = Language::translate('ERROR_SKINNAME');

			if (!empty($errors))
				return ['error' => $errors];

			$id    = (int)$id;
			$bdd   = Database::getInstance();
			$query = $bdd->prepare('UPDATE `skins` SET `title` = :name, `description` = :description, `model` = :model WHERE `id` = :id AND `owner` = :owner LIMIT 1');
			$query->bindParam(':id', $id, \PDO::PARAM_INT);
			$query->bindParam(':owner', $_SESSION['user_id'], \PDO::PARAM_INT);
			$query->bindParam(':name', $title, \PDO::PARAM_STR);
			$query->bindParam(':description', $description, \PDO::PARAM_STR);
			$query->bindParam(':model', $model, \PDO::PARAM_STR);
			$query->execute();
			$rows = $query->rowCount();
			$query->closeCursor();

			return ["error" => false, 'affected_rows' => $rows];
		}

		public function updateSkin($id, $image_b64)
		{
			$errors = [];
			if (!self::isLogged())
				$errors[] = 'user not logged in';

			if (!is_numeric($id))
				$errors[] = 'id not numeric';

			if (!empty($errors))
				return ['error' => $errors];

			$id    = (int)$id;
			$bdd   = Database::getInstance();
			$query = $bdd->prepare('SELECT `id` FROM `skins` WHERE `id` = :id AND `owner` = :owner LIMIT 1');

			$query->bindParam(':id', $id, \PDO::PARAM_INT);
			$query->bindParam(':owner', $_SESSION['user_id'], \PDO::PARAM_INT);

			$query->execute();
			$skin_exists = $query->fetch(\PDO::FETCH_ASSOC);
			$query->closeCursor();

			if ($skin_exists === false)
				return ['error' => ['cannot find requested skin']];

			$image_b64 = str_replace('data:image/png;base64,', '', $image_b64);
			$image_b64 = str_replace(' ', '+', $image_b64);
			$image     = base64_decode($image_b64);

			if ($image === false)
				return ['error' => ['Invalid base image']];

			require_once dirname(__FILE__) . '/inc/skin_creator.inc.php';
			$uploader = new SkinCreator();
			return $uploader->updateSkin($image, $id);
		}

		public function switchSkin($url, $passphrase, $model = "steve")
		{
			if (!self::isLogged())
				return ['error' => ['user not logged in']];

			try {
				$db    = Database::getInstance();
				$query = $db->prepare('SELECT `minecraft_username`, `minecraft_password` FROM `members` WHERE `id` = :user_id');
				$query->bindParam(':user_id', $_SESSION['user_id'], \PDO::PARAM_INT);
				$query->execute();
				$data = $query->fetch(\PDO::FETCH_ASSOC);
				$query->closeCursor();

				if ($data === false)
					return ['error' => ['user does not exists']];

				$url = urldecode($url);

				require_once ROOT . 'assets/php/inc/Crypto.class.php';
				require_once ROOT . 'assets/php/inc/SkinSwitcher.class.php';

				$crypter            = new Crypto($passphrase);
				$minecraft_password = $crypter->decrypt($data['minecraft_password']);

				$skinswitch = new SkinSwitcher($data['minecraft_username'], $minecraft_password);

				$result = $skinswitch->switchSkin($url, $model);

				if ($result !== true)
					return ['error' => [$result]];

				return ['error' => false];
			} catch (\Exception $e) {
				return ['error' => ['Internal server error']];
			}
		}

		public function changeSettings($username, $email, $force2D, $minecraft_username, $minecraft_password, $minecraft_password_key)
		{
			if (!self::isLogged())
				return ['error' => ['user not logged in']];

			$errors = [];
			if ($username !== $_SESSION['username']) {
				if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username))
					$errors[] = Language::translate('USERNAME_ALPHANUMERIC');

				if (self::user_exists($username))
					$errors[] = Language::translate('USER_ALREADY_REGISTERED');
			}

			if ($email !== $_SESSION['email']) {
				if (self::user_exists($email))
					$errors[] = Language::translate('EMAIL_ALREADY_REGISTERED');

				if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
					$errors[] = Language::translate('EMAIL_INVALID');
			}

			if (empty($minecraft_username)) {
				$minecraft_password     = '';
				$minecraft_password_key = '';
			} else {
				if (!empty($minecraft_password)) {
					if (empty($minecraft_password_key))
						$errors[] = Language::translate('MINECRAFT_PASSWORD_KEY_MISSING');
					else {
						require_once ROOT . 'assets/php/inc/Crypto.class.php';
						$crypter            = new Crypto($minecraft_password_key);
						$minecraft_password = $crypter->crypt($minecraft_password);
					}
				} else {
					$minecraft_password = $_SESSION['minecraft_password'];
				}
			}

			if (empty($errors)) {
				$force2D = (bool)$force2D;

				try {
					$bdd   = Database::getInstance();
					$query = $bdd->prepare('UPDATE `members` SET
										`force2D` = :force2D,
										`username` = :username,
										`email` = :email,
										`minecraft_username` = :mcu,
										`minecraft_password` = :mcp
										WHERE `id` = :user_id');
					$query->bindParam(':force2D', $force2D, \PDO::PARAM_BOOL);
					$query->bindParam(':username', $username, \PDO::PARAM_STR);
					$query->bindParam(':email', $email, \PDO::PARAM_STR);
					$query->bindParam(':mcu', $minecraft_username, \PDO::PARAM_STR);
					$query->bindParam(':mcp', $minecraft_password, \PDO::PARAM_STR);
					$query->bindParam(':user_id', $_SESSION['user_id'], \PDO::PARAM_INT);
					$query->execute();

					$_SESSION['username']           = $username;
					$_SESSION['force2D']            = $force2D;
					$_SESSION['email']              = $email;
					$_SESSION['minecraft_username'] = $minecraft_username;
					$_SESSION['minecraft_password'] = $minecraft_password;

					return ['error' => false];
				} catch (\Exception $e) {
					return ['error' => ['Internal server error']];
				}
			} else {
				return ['error' => $errors];
			}
		}

		private static function user_exists($login)
		{
			if (filter_var($login, FILTER_VALIDATE_EMAIL))
				$isEmail = true;
			else
				$isEmail = false;

			$db = Database::getInstance();

			$cmd = 'SELECT `id` FROM `members` WHERE ';

			if ($isEmail)
				$cmd .= '`email` = :login';
			else
				$cmd .= '`username` = :login';

			$query = $db->prepare($cmd);
			$query->bindParam(':login', $login, \PDO::PARAM_STR);
			$query->execute();
			$rc = $query->rowCount();
			$query->closeCursor();

			return ($rc !== 0);
		}

		// =====================================================================================
		//  Protected methods
		// =====================================================================================

		protected function login($username, $password)
		{
			if (empty($username))
				return ['error' => [Language::translate('ERROR_LOGIN')]];
			if (empty($password))
				return ['error' => [Language::translate('ERROR_PASSWORD')]];

			$bdd = Database::getInstance();

			$query = $bdd->prepare('SELECT `username`, `password`, `id`, `email`, `language`, `force2D`, `minecraft_username`, `minecraft_password` FROM `members` WHERE `username` = :username LIMIT 1');
			$query->bindParam(':username', $username, \PDO::PARAM_STR);
			$query->execute();
			$data = $query->fetch();
			$query->closeCursor();

			if ($data === false)
				return ['error' => [Language::translate('ERROR_LOGIN')]];

			if (crypt($password, $data['password']) == $data['password']) {
				$_SESSION['username']           = $data['username'];
				$_SESSION['email']              = $data['email'];
				$_SESSION['user_id']            = (int)$data['id'];
				$_SESSION['language']           = $data['language'];
				$_SESSION['force2D']            = (bool)$data['force2D'];
				$_SESSION['minecraft_username'] = $data['minecraft_username'];
				$_SESSION['minecraft_password'] = $data['minecraft_password'];

				return ['error' => false];
			}

			return ['error' => [Language::translate('ERROR_PASSWORD')]];
		}

		protected function unregister()
		{
			if (!self::isLogged())
				return ['error' => ['user not logged in']];

			try {
				$db    = Database::getInstance();
				$query = $db->prepare("SELECT s.`id` FROM `skins` s WHERE s.`owner` = :owner");
				$query->bindParam(':owner', $_SESSION['user_id'], \PDO::PARAM_INT);
				$query->execute();
				$skin_list = $query->fetchAll(\PDO::FETCH_COLUMN);
				$query->closeCursor();

				foreach ($skin_list as $skinID) {
					@unlink(ROOT . 'assets/skins/' . $skinID . '.png');
					@unlink(ROOT . 'assets/skins/2D/' . $skinID . '.png');
					@unlink(ROOT . 'assets/skins/2D/' . $skinID . '.png.back');
				}

				$query = $db->prepare('DELETE FROM `skins` WHERE `owner` = :owner');
				$query->bindValue(':owner', $_SESSION['user_id'], \PDO::PARAM_INT);
				$query->execute();
				$query->closeCursor();
				$query = $db->prepare('DELETE FROM `members` WHERE `id` = :id');
				$query->bindValue(':id', $_SESSION['user_id'], \PDO::PARAM_INT);
				$query->execute();
				$query->closeCursor();

				session_destroy();

				return ['error' => false];
			} catch (\Exception $e) {
				return ['error' => ['Internal server error']];
			}
		}

		protected function register($username, $password, $password_conf, $email, $email_conf)
		{
			$errors = [];

			if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username))
				$errors[] = Language::translate('USERNAME_ALPHANUMERIC');

			if (self::user_exists($username))
				$errors[] = Language::translate('USER_ALREADY_REGISTERED');

			if (self::user_exists($email))
				$errors[] = Language::translate('EMAIL_ALREADY_REGISTERED');

			if (strlen($password) < 6)
				$errors[] = Language::translate('PASSWORD_TOO_SHORT', array(6));

			if ($password !== $password_conf)
				$errors[] = Language::translate('PASSWORD') . ': ' . Language::translate('CONF_NO_MATCH');

			if (strtolower($email) !== strtolower($email_conf))
				$errors[] = Language::translate('EMAIL') . ': ' . Language::translate('CONF_NO_MATCH');

			if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
				$errors[] = Language::translate('EMAIL_INVALID');

			if (count($errors) === 0) {
				$password = crypt($password);
				$db       = Database::getInstance();
				try {
					$query = $db->prepare('INSERT INTO `members`(`username`, `password`, `email`) VALUES(:username, :password, :email)');
					$query->bindParam(':username', $username, \PDO::PARAM_STR);
					$query->bindParam(':password', $password, \PDO::PARAM_STR);
					$query->bindParam(':email', $email, \PDO::PARAM_STR);
					$query->execute();
					$query->closeCursor();

					if (session_status() !== PHP_SESSION_ACTIVE) {
						session_start();
					}

					$_SESSION['username']           = $username;
					$_SESSION['email']              = $email;
					$_SESSION['user_id']            = $db->lastInsertId();
					$_SESSION['force2D']            = false;
					$_SESSION['minecraft_username'] = '';
					$_SESSION['minecraft_password'] = '';

					return ['error' => false];
				} catch (\PDOException $e) {
					return ['error' => ['Internal server error']];
				}
			} else {
				return ['error' => $errors];
			}
		}
	}

?>
