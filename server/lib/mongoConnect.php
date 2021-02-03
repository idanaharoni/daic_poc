<?php

require_once __DIR__ . "/vendor/autoload.php";

class DBConnection {
	const HOST = 'localhost';
	const PORT = 27017;
	const DBNAME = 'daic';
	const USERNAME = '';
	const PASSWORD = '';
	private static $instance;
	public $connection;
	public $database;
	public $typeMap;
	
	private function __construct() {
				
		if (self::USERNAME != '' && self::PASSWORD != '') {
			$auth = self::USERNAME.':'.self::PASSWORD.'@';
			$authDb = '/'.self::DBNAME;
		}
		else {
			$auth = '';
			$authDb = '';
		}
		
		if (!extension_loaded('mongodb')) die("MongoDB is not installed!");
		try {
			$this->connection = new MongoDB\Client('mongodb://'.$auth.self::HOST.':'.self::PORT.$authDb, ['socketTimeoutMS' => 36000000]);
			$dbName = self::DBNAME;
			$this->database = $this->connection->$dbName;
		} catch (MongoConnectionException $e) {
			throw $e;
		}

		$this->typeMap = ['array' => 'array', 'root' => 'array', 'document' => 'array'];
	}
	
	static public function instantiate() {
		if (!isset(self::$instance)) {
			$class = __CLASS__;
			self::$instance = new $class;
		}
		return self::$instance;
	}

	public function isValid($id) {
		if (strlen($id) === 24 && strspn($id,'0123456789ABCDEFabcdef') === 24) return true;
		else return false;
	}


	public function date($timestamp = '') {
		if ($timestamp == '') $timestamp = date('Y-m-d H:i:s');
		$timestamp = strtotime($timestamp) * 1000;
		$object = new MongoDB\BSON\UTCDateTime($timestamp);
		return $object;
	}
	
	public function getCollection($name) {
		return $this->database->selectCollection($name);
	}
	
	public function execute($code) {
		return $this->database->execute($code);
	}
}

?>
