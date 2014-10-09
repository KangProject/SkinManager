<?php
	namespace EphysCMS;

	class Router
	{
		const CMS_VERSION = 3.1;
		const SITE_LOCATION = null;
		const DEFAULT_PAGE = 'home';
		const DEV_MODE = false;

		const DIR_MODULES = 'modules';
		const DIR_TEMPLATES = 'templates';
		const DIR_SCRIPTS = 'php';

		const API_URL = 'json';

		public static $initial_page;
		private static $page;
		private static $response_code = 200;
		private static $redirect = false;

		//response code, setting default to 200
		public $url;
		private $subdomains = array('clockdown');

		public function __construct($url)
		{
			global $_URL;

			if (self::DEV_MODE)
				ini_set('display_errors', 'on');
			else
				ini_set('display_errors', 'off');

			if (substr($url, -1) != '/') {
				header('Location: ' . $url . '/');
				exit;
			}

			session_start();

			$this->url          = $this->parseURI($url);
			self::$initial_page = self::$page = (isset($this->url[1]) && !empty($this->url[1])) ? $this->url[1] : self::DEFAULT_PAGE;

			$_URL = $this->extractDataFromURL($this->url);

			// if(isset($_SERVER['REDIRECT_QUERY_STRING']))
			// 	$_GET = $this->parseGET($_SERVER['REDIRECT_QUERY_STRING']);


			$_REQUEST = array_merge($_REQUEST, $_URL);

			if (!self::DEV_MODE) {
				ob_start("self::sanitize_output");
				header('Content-Encoding: gzip');
			} else {
				ob_start();
			}

			if (!defined('DS'))
				define('DS', DIRECTORY_SEPARATOR);
			define('ROOT', dirname(__FILE__) . DS);

			define('MAIN_FOLDER', $this->get_main_folder());
			define('WEBSITE_ROOT', 'http://' . MAIN_FOLDER);
			define('PAGE_RELATIVE', str_repeat('../', count($this->url) - 2) . self::$page . '/');

			foreach ($this->subdomains as $subdomain) {
				define(strtoupper($subdomain) . '_ROOT', 'http://' . $subdomain . '.' . $this->get_main_folder());
			}

			$this->loadModules();
			self::setResponseCode($this->getDefaultResponseCode());

			if (self::$initial_page === self::API_URL) {
				self::setResponseCode(200);

				require_once(ROOT . 'assets' . DS . self::DIR_SCRIPTS . DS . 'API.class.php');
				if (get_magic_quotes_gpc()) {
					array_walk_recursive($_REQUEST, 'stripslashes_gpc');
				}

				function stripslashes_gpc(&$input, $key)
				{
					$input = stripslashes($input);
				}

				$api = new \API();

				$result = $api->selectMethod($_REQUEST);

				if (isset($result['error_code']))
					self::setResponseCode($result['error_code']);

				if ($result !== null)
					echo json_encode($result);
			} elseif (self::isAjax()) {
				include_once ROOT . 'assets' . DS . self::DIR_TEMPLATES . DS . self::$page . '.page.php';
			} else {
				include_once ROOT . 'assets' . DS . self::DIR_TEMPLATES . DS . 'header.tpl.php';
				include_once ROOT . 'assets' . DS . self::DIR_TEMPLATES . DS . self::$page . '.page.php';
				include_once ROOT . 'assets' . DS . self::DIR_TEMPLATES . DS . 'footer.tpl.php';
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
			$url = (self::SITE_LOCATION == null) ? $uri : strstr($uri, stripslashes(self::SITE_LOCATION));
			return explode('/', strstr($uri, $url));
		}

		private function extractDataFromURL($url)
		{
			if (!is_array($url))
				return array();

			$getList = array();
			$offset  = 2;

			$unparsedGet = array_slice($url, $offset, count($url) - $offset);

			foreach ($unparsedGet as $key => $val) {
				if ($key & 1)
					$getList[$unparsedGet[$key - 1]] = $val;
			}

			return $getList;
		}

		private function get_main_folder()
		{
			$dir = strrev(realpath(dirname(__FILE__)));
			return str_replace('\\', '/', $_SERVER['HTTP_HOST'] . strrev(substr($dir, 0, -(strlen($_SERVER['DOCUMENT_ROOT'])))) . '/');
		}

		private function loadModules()
		{
			if (!is_dir(ROOT . 'assets' . DS . self::DIR_MODULES))
				return;

			$loadList = array();
			foreach (glob(ROOT . 'assets' . DS . self::DIR_MODULES . DS . '*.module.*.php', GLOB_ERR) as $filename) {
				$file = explode('.', basename($filename));
				if (!is_numeric($file[2]))
					throw new Exception('id is not numeric ' . $file[2]);

				if (isset($loadList[$file[2]]))
					throw new Exception('Duplicate module id ' . $file[2]);
				else
					$loadList[$file[2]] = $filename;
			}

			$loadList = $loadList;

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
			if (is_numeric(self::$page))
				return intval(self::$page);

			if (!file_exists(ROOT . 'assets' . DS . self::DIR_TEMPLATES . DS . self::$page . '.page.php'))
				return self::$page = 404;

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

		private function parseGET($query)
		{
			$queryParts = explode('&', $query);

			$params = array();
			foreach ($queryParts as $param) {
				$item             = explode('=', $param);
				$params[$item[0]] = isset($item[1]) ? $item[1] : null;
			}

			return $params;
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

	$router = new router($_SERVER['REDIRECT_URL']);
	?>