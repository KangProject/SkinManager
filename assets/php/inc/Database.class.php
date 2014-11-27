<?php

	namespace EphysCMS;

	class Database
	{
		private static $instance;

		public function __construct()
		{
			throw new Error500Exception('Class EphysCMS/Database cannot be instanciated');
		}

		/**
		 * Gets an instance of the database.
		 *
		 * @return \PDO the database
		 */
		public static function getInstance()
		{
			if (!self::$instance instanceOf \PDO) {
				$pdo_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

				$mysql_host     = '127.0.0.1';
				$mysql_database = 'skinmanager';
				$mysql_user     = 'root';
				$mysql_password = '';

				try {
					$bdd = new \PDO('mysql:host=' . $mysql_host . ';dbname=' . $mysql_database, $mysql_user, $mysql_password, $pdo_options);
					$bdd->exec("SET NAMES 'utf8'");
					Database::setInstance($bdd);
				} catch (\PDOException $e) {
					echo json_encode(array('error' => $e->getMessage()));
				}
			}
			return self::$instance;
		}

		public static function setInstance($instance)
		{
			self::$instance = $instance;
		}
	}
