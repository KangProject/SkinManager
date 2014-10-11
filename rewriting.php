<?php

	namespace EphysCMS;

	class Router
	{
		const DEV_MODE = true;
		const WEBSITE_SUBDIRECTORY = "SkinManager/";

		const WEBSITE_ROOT_DEV = "http://localhost/dev/web/SkinManager/";
		const WEBSITE_ROOT_PROD = "http://skin.outadoc.fr/";

		const DEFAULT_PAGE = 'home';
		const DIR_MODULES = 'modules';
		const DIR_TEMPLATES = 'templates';
		const DIR_SCRIPTS = 'php';

		const API_URL = 'json';

		public static $initial_page;
		private static $page;
		private static $response_code = 200;
		private static $redirect = false;

		public function __construct($url)
		{
			global $_URL;

			if (self::DEV_MODE) {
				ini_set('display_errors', 'on');
				define('WEBSITE_ROOT', self::WEBSITE_ROOT_DEV);
			} else {
				ini_set('display_errors', 'off');
				define('WEBSITE_ROOT', self::WEBSITE_ROOT_PROD);
			}

			if (substr($url, -1) != '/') {
				header('Location: ' . $url . '/');
				exit;
			}

			session_start();

			$this->url = $this->parseURI($url);

			//if we're changing the language, insert an empty page in the first index of the array
			if (isset($this->url[0]) && $this->url[0] === 'l') {
				array_splice($this->url, 0, 0, array(''));
			}

			if (isset($this->url[0]) && !empty($this->url[0])) {
				self::$initial_page = self::$page = $this->url[0];
			} else {
				self::$initial_page = self::$page = self::DEFAULT_PAGE;
			}

			$_URL     = $this->extractDataFromURL($this->url);
			$_REQUEST = array_merge($_REQUEST, $_URL);

			if (!self::DEV_MODE) {
				ob_start("self::sanitize_output");
				header('Content-Encoding: gzip');
			} else {
				ob_start();
			}

			define('ROOT', dirname(__FILE__) . '/');

			if (isset($_URL['l'])) {
				define('PAGE_RELATIVE', $url . '../../');
			} else {
				define('PAGE_RELATIVE', $url);
			}

			$this->loadModules();
			self::setResponseCode($this->getDefaultResponseCode());

			if (self::$initial_page === self::API_URL) {
				self::setResponseCode(200);
				require_once(ROOT . 'assets/' . self::DIR_SCRIPTS . '/API.class.php');

				if (get_magic_quotes_gpc()) {
					array_walk_recursive($_REQUEST, 'stripslashes_gpc');
				}

				function stripslashes_gpc(&$input, $key)
				{
					$input = stripslashes($input);
				}

				$api    = new API();
				$result = $api->selectMethod($_REQUEST);

				if (isset($result['error_code']))
					self::setResponseCode($result['error_code']);

				if ($result !== null) {
					echo json_encode($result);
				}

			} else if (self::isAjax()) {
				include_once ROOT . 'assets/' . self::DIR_TEMPLATES . '/' . self::$page . '.page.php';
			} else {
				include_once ROOT . 'assets/' . self::DIR_TEMPLATES . '/header.tpl.php';
				include_once ROOT . 'assets/' . self::DIR_TEMPLATES . '/' . self::$page . '.page.php';
				include_once ROOT . 'assets/' . self::DIR_TEMPLATES . '/footer.tpl.php';
			}

			//now, headers has been sent, it's time to send buffer to the output, let's flush !
			if (self::$redirect !== false) {
				header('Location: ' . self::$redirect);
				ob_end_clean();
			} else {
				http_response_code(self::$response_code);
				ob_end_flush();
			}
		}

		private function parseURI($uri)
		{
			$url = (self::WEBSITE_SUBDIRECTORY == null) ? $uri : str_replace(self::WEBSITE_SUBDIRECTORY, '', strstr($uri, self::WEBSITE_SUBDIRECTORY));
			return empty($url) ? null : explode('/', strstr($uri, $url));
		}

		private function extractDataFromURL($url)
		{
			if (!is_array($url)) {
				return array();
			}

			$getList = array();
			$offset  = 1;

			$unparsedGet = array_slice($url, $offset, count($url) - $offset);

			foreach ($unparsedGet as $key => $val) {
				if ($key & 1)
					$getList[$unparsedGet[$key - 1]] = $val;
			}

			return $getList;
		}

		private function loadModules()
		{
			if (!is_dir(ROOT . 'assets/' . self::DIR_MODULES)) return;

			$loadList = array();

			foreach (glob(ROOT . 'assets/' . self::DIR_MODULES . '/*.module.*.php', GLOB_ERR) as $filename) {
				$file = explode('.', basename($filename));

				if (!is_numeric($file[2])) {
					throw new \Exception('id is not numeric ' . $file[2]);
				}

				if (isset($loadList[$file[2]])) {
					throw new \Exception('Duplicate module id ' . $file[2]);
				} else {
					$loadList[$file[2]] = $filename;
				}
			}

			foreach ($loadList as $module) {
				require_once $module;
			}
		}

		public static function setResponseCode($code)
		{
			self::$response_code = (int)$code;
		}

		private function getDefaultResponseCode()
		{
			if (is_numeric(self::$page)) {
				return intval(self::$page);
			}

			if (!file_exists(ROOT . 'assets/' . self::DIR_TEMPLATES . '/' . self::$page . '.page.php')) {
				return self::$page = 404;
			}

			return self::$response_code;
		}

		public static function isAjax()
		{
			return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
		}

		public static function redirect($url)
		{
			self::$redirect = $url;
		}

		public static function isLogged()
		{
			return isset($_SESSION['user_id']);
		}

		public static function setPage($page)
		{
			self::$page = $page;
		}

		private function sanitize_output($buffer)
		{
			$search  = array('/\s+/');
			$replace = array(' ');
			$buffer  = preg_replace($search, $replace, $buffer);

			return ob_gzhandler($buffer, 5);
		}

	}

	if (!function_exists('http_response_code')) {
		function http_response_code($code = null)
		{
			if ($code !== null) {
				switch ($code) {
					case 200:
						$text = 'OK';
						break;
					case 301:
						$text = 'Moved Permanently';
						break;
					case 302:
						$text = 'Moved Temporarily';
						break;
					case 400:
						$text = 'Bad Request';
						break;
					case 401:
						$text = 'Unauthorized';
						break;
					case 402:
						$text = 'Payment Required';
						break;
					case 403:
						$text = 'Forbidden';
						break;
					case 404:
						$text = 'Not Found';
						break;
					case 500:
						$text = 'Internal Server Error';
						break;
					default:
						exit('Unknown http status code "' . htmlentities($code) . '"');
						break;
				}

				$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
				header($protocol . ' ' . $code . ' ' . $text, false, $code);
				$GLOBALS['http_response_code'] = $code;
			} else {
				$code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
			}

			return $code;
		}
	}

	new Router($_SERVER['REDIRECT_URL']);
