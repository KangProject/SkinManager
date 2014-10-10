<?php

	class SkinSwitcher
	{
		private static $WEB_LOGIN_URL = "https://minecraft.net/login";
		private static $PROFILE_URL = "https://minecraft.net/profile";
		private static $SKIN_CHANGE_URL = "https://minecraft.net/profile/skin/remote?url=";

		private $ch = null;
		private $cookieFileName;
		private $username;
		private $password;

		public function __construct($username, $password)
		{
			$this->cookieFileName = dirname(__FILE__) . '/tmp/' . uniqid('COOKIE_');
			$this->username       = trim($username);
			$this->password       = trim($password);
		}

		public function __destruct()
		{
			if ($this->ch !== null)
				curl_close($this->ch);

			unlink($this->cookieFileName);
		}

		public function switchSkin($url, $model)
		{
			$login = $this->getAuth();

			if ($login !== true)
				return $login;

			$token = $this->getAutenticityToken();
			if ($token === false)
				return Language::translate('ERROR_UNKNOW');

			if ($this->doTheSwitch($token, $url, $model) === false)
				return Language::translate('ERROR_UNKNOW');

			return true;
		}

		private function getAuth()
		{
			$params = [
				'username' => urlencode($this->username),
				'password' => urlencode($this->password),
				'remember' => false
			];

			$paramStr = self::paramsEncode($params);

			$ch = $this->getCh();

			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_setopt($ch, CURLOPT_URL, self::$WEB_LOGIN_URL);
			curl_setopt($ch, CURLOPT_POST, count($params));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $paramStr);

			$page = curl_exec($ch);

			if (curl_errno($ch) !== 0)
				return Language::translate('ERROR_UNKNOW');
			else {
				if (preg_match("#justLoggedIn#", $page) || preg_match("#301#", $page)) {
					return true;
				} else if (preg_match("#migrated#", $page)) {
					return Language::translate('ERROR_MIGRATED');
				} else {
					return Language::translate('ERROR_PASSWORD');
				}
			}
		}

		public static function paramsEncode($params)
		{
			$fields_string = '';

			foreach ($params as $key => $value) {
				$fields_string .= $key . '=' . $value . '&';
			}

			return rtrim($fields_string, '&');
		}

		private function getCh()
		{
			if ($this->ch === null) {
				$this->ch = curl_init();
				curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookieFileName);
				curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookieFileName);
			}

			return $this->ch;
		}

		private function getAutenticityToken()
		{
			$ch = $this->getCh();

			curl_setopt($ch, CURLOPT_URL, self::$PROFILE_URL);
			curl_setopt($ch, CURLOPT_POST, false);
			$page = curl_exec($ch);

			if (curl_errno($ch) !== 0)
				return false;
			else {
				if (preg_match("#name=\"authenticityToken\" value=\"(.+)\">#", $page, $autenticityToken)) {
					return $autenticityToken[1];
				} else {
					return false;
				}
			}
		}

		// utils

		private function doTheSwitch($token, $url, $model)
		{
			$ch = $this->getCh();

			curl_setopt($ch, CURLOPT_URL, self::$SKIN_CHANGE_URL . $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'authenticityToken=' . urlencode($token));
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'model=' . urlencode($model));

			$page = curl_exec($ch);

			if (curl_errno($ch) !== 0) {
				return false;
			} else {
				return preg_match("#success#", $page);
			}
		}
	}

?>