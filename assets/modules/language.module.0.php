<?php
	require_once(ROOT . 'assets' . DS . self::DIR_SCRIPTS . DS . 'inc/Database.class.php');

	class Language
	{
		public static $language_list = array();
		private static $language;
		private static $_TRANSLATIONS;

		public function __construct()
		{
			trigger_error("Language cannot be instancied");
		}

		# update the user's language preference if he decided to change it

		public static function getLanguage()
		{
			self::init();
			return self::$language;
		}

		# load a language translations file and store it

		public static function setLanguage($lang)
		{
			self::$language = $lang;
			self::loadTranslations();
		}

		# load the list of available languages

		private static function init()
		{
			if (self::$language !== null)
				return;

			self::updateLanguage();
			self::loadLanguageList();
		}

		# returns current website language

		private static function updateLanguage()
		{
			global $_URL;
			if (isset($_SESSION['user_id']) && isset($_URL['l']) && ($_URL['l'] != $_SESSION['language'])) {
				$bdd   = Database::getInstance();
				$query = $bdd->prepare('UPDATE `members` SET `language` = :language WHERE `id` = :id');
				$query->bindParam(':language', $_URL['l'], PDO::PARAM_STR);
				$query->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
				$query->execute();
				$query->closeCursor();
			}

			self::setLanguage($_SESSION['language'] = isset($_URL['l']) ? $_URL['l'] : (isset($_SESSION['language']) ? $_SESSION['language'] : 'en_EN'));
		}

		# set the current website language

		private static function loadLanguageList()
		{
			foreach (glob(ROOT . 'assets/php/language/*.lang.php', GLOB_ERR) as $filename) {
				$file = explode('.', basename($filename));
				array_push(self::$language_list, $file[0]);
			}
		}

		# returns the translation associed with $key in the current language

		private static function loadTranslations()
		{
			if (file_exists(ROOT . 'assets' . DS . 'php/language/' . self::$language . '.lang.php')) {
				require_once(ROOT . 'assets' . DS . 'php/language/' . self::$language . '.lang.php');
				self::$_TRANSLATIONS = $_LANGUAGE;
			} else
				throw new Exception('Can\'t find requested language ' . self::$language);
		}

		public static function translate($key, $params = null)
		{
			self::init();

			if (array_key_exists($key, self::$_TRANSLATIONS)) {
				if (gettype($params) === "array") {
					return vsprintf(self::$_TRANSLATIONS[$key], $params);
				} else {
					return self::$_TRANSLATIONS[$key];
				}
			} else
				throw new Exception('Can\'t find requested translation ' . $key . ' in ' . self::$language);
		}
	}

?>