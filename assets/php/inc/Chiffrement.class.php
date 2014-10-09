<?php

	class Chiffrement
	{
		public $key = '';
		private $cipher = MCRYPT_RIJNDAEL_128;
		private $mode = MCRYPT_MODE_CBC;

		public function __construct($key = '')
		{
			$this->key = $key;
		}

		public function crypt($data)
		{
			$keyHash = md5($this->key);
			$key     = substr($keyHash, 0, mcrypt_get_key_size($this->cipher, $this->mode));
			$iv      = substr($keyHash, 0, mcrypt_get_block_size($this->cipher, $this->mode));

			$data = mcrypt_encrypt($this->cipher, $key, $data, $this->mode, $iv);
			return base64_encode($data);
		}

		public function decrypt($data)
		{
			$keyHash = md5($this->key);
			$key     = substr($keyHash, 0, mcrypt_get_key_size($this->cipher, $this->mode));
			$iv      = substr($keyHash, 0, mcrypt_get_block_size($this->cipher, $this->mode));

			$data = base64_decode($data);
			return mcrypt_decrypt($this->cipher, $key, $data, $this->mode, $iv);
		}
	}

?>