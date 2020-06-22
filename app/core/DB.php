<?php

namespace App\Core;

use PDO;

class DB {
	
	private static 
		$_instance = NULL, 
		$_connection; 

	public static function getInstance() {
		if(!self::$_instance) { 
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	private function __construct() {
		
    $HOST   = env('HOST');
    $DBNAME = env('DBNAME');
    $USER   = env('USER');
    $PASS   = env('PASS');
		try {
      self::$_connection = new PDO("mysql:host=$HOST;dbname=$DBNAME", $USER, $PASS);
      self::$_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      self::$_connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

		} catch (\PDOException $e) {
			echo "Connection error: " . $e->getMessage();
		}
		
	}
	
	public function getConnection() {
		return self::$_connection;
	}

	public static function RAW(string $statement) {
		// To Execute SQL statement
		$stmt = self::$_connection->prepare($statement);
		if ( $stmt->execute() ) {
			return $stmt->fetchAll();
		}
	}

	private function __clone() { }

}

