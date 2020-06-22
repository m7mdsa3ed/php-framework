<?php

namespace App\Vendor;

use App\Core\DB as DB;

class Model {

  protected $table; 
  private static $db = null;
  private static $query = null;
  private static $results =null;
  private static $error = false;

  private static function resetToDefault() {
    self::$query = null;
    self::$results =null;
    self::$error = false;
  }
  
  private static function setConnection() {
    // Get connection of database
    self::$db = DB::getInstance()->getConnection();
  }

  private static function query($sql, $params = null) {

    // Get database connection
    self::setConnection();

    // Set error to false
    self::$error = false; 

    // Prepare SQL statement query
    if ( $stmt = self::$db->prepare($sql) ) {

      // Bind params if exists
      if ( $params != null && count($params) ) {

        $x = 1;

        foreach( $params as $param ) {
          $stmt->bindvalue($x, $param);
          $x++;
        }
      }

      // Execute the statement
      if ( $stmt->execute() ) {
        
        // Fetch Data on select only
        // Create new object
        self::$results = new \stdclass;
        
        if (strstr($sql, 'SELECT')) {
          self::$results->data = $stmt->fetchAll();
        }
        // Add last inserted id
        self::$results->insertId = self::$db->lastInsertId();
      } 
      
      else {

        // Something wrong happend 
        return self::$error = true;
      }
      
      return new static;
    }
  }

  private static function get_non_static_proparty($key) {

    // Get proparties of the object 
    $vars = get_object_vars(new static);

    // Search for the proparty
    if (array_key_exists($key, $vars)) {

      // Get the proparty 
      return $vars[$key];
    }
  }

  private static function prepare_sql_query() {
    $query = self::$query;
    $action = isset($query->action) ? $query->action : 'SELECT';
    if ($action == 'DELETE') {
      $columns = null;
    } elseif (isset($query->cols) && $action == 'SELECT') {
      $columns = $query->cols;
    } else {
      $columns = '*';
    }
    $table = self::get_non_static_proparty('table');
    $where = isset($query->where) ? $query->where : '';
    $order = isset($query->order) ? $query->order : '';
    $limit = isset($query->limit) ? $query->limit : '';
    return "$action $columns FROM $table $where $order $limit";
  }

  public static function execute_sql_query() {

    // Get SQL query string 
    $sql = self::prepare_sql_query();
    
    // Pass it to query function to bind params and execute it 
    return (!self::query($sql, (self::$query->fields) ?? [])->error()) 
      ? true
      : false ;
    
  }
  
  public static function where() {
    if (\func_num_args() == 1) {
      if (is_array(\func_get_arg(0))) {
        $x = 1;
        $where = '';
        $fields = func_get_arg(0);
        foreach ($fields as $col => &$value) {
          $where .= ($x == 1) 
            ? " WHERE " 
            : " AND ";
          if (is_array($value)) {
            $operator = $value[0];
            $where .= "`$col` $operator ?";

            $value = ($value[0] == "LIKE") 
              ? "%$value[1]%" 
              : $value[1];
          } else {
            $where .= "`$col` = ?";
          }
          $x++;
        }
        
        self::set_query('where', $where);
        self::set_query('fields', $fields);

      }
    }

    elseif (func_num_args() <= 3) {
      
      $col = func_get_arg(0);

      $value = (func_num_args() == 2 )
        ? func_get_arg(1)
        : func_get_arg(2);
      
      $operator = (func_num_args() == 2 )
        ? '='
        : func_get_arg(1);      
        
      if ($operator == "LIKE") {
        $value = "%$value%";
      }

      self::set_query('where', "WHERE `$col` $operator ?");
      self::set_query('fields', [ $col => $value ]);

    }
    
    return new static;
  }

	public static function create($fields) {
		if (count($fields)) {
			$keys = array_keys($fields);
			$values = '';
			$x = 1;
			foreach ($fields as &$field) {
        $field = htmlspecialchars($field);
				$values .= "?";
				if ($x < count($fields)) {
					$values .= ", ";
				}
				$x++;
      }

      $table = self::get_non_static_proparty('table');
			$sql = " INSERT INTO {$table} ( `" . implode('`, `', $keys) . "`) VALUES ({$values}) ";
			if (!self::query($sql, $fields)->error()) {
        return self::find(self::insertId())->first();
      }
		}
		return false;
	}

	public static function update($id, $fields) {
		if (count($fields)) {
			$set = '';
			$x = 1;
			foreach ($fields as $name => &$value) {
        $value = htmlspecialchars($value);
				$set .= "$name = ?";
				if ($x < count($fields)) {
					$set .= ", ";
				}
				$x++;
      }
      $table = self::get_non_static_proparty('table');
			$sql = "UPDATE $table SET $set WHERE id = '$id'";
			if (!self::query($sql, $fields)->error()) {
				return self::find($id)->first();
			}
		}
		return false;
  }
  
  public static function get() {

    // Select all rows
    self::action('SELECT');

    // Execute it
    if (self::execute_sql_query()) {
      $results = self::results();
      self::resetToDefault();
      return $results;
    }    
    
  }

  public static function find($id) {

    // Find by id
    self::action('SELECT')->where('id', $id);

    // Check if data exists or not 
    if (self::execute_sql_query()) {
      return self::results();
    }
    return false;
  }

  public static function delete($id) {

    // Set action to delete then delete
    return self::action('DELETE')->where('id', $id)->execute_sql_query();    
  }

  private static function set_query($key, $value) {

    // Check if it's created alreay or not
    if (!is_object(self::$query)) {

      // Create new object
      self::$query = new \stdclass;
    }

    // Add to object
    return self::$query->$key = $value;
  }

  private static function action(string $action) {
    self::set_query('action', $action);
    return new static;
  }
  
  public static function select(string $cols = '*') {
    self::set_query('cols', $cols);
    return new static;
  }

  public static function orderBy($col, $type) {
    self::set_query('order', "ORDER BY $col $type");
    return new static;
  }
  
  public static function limit($offset = 0, $limit) {
    self::set_query('limit', "LIMIT $offset,$limit");
    return new static;
  }

  public static function results() {
    return new Collection(self::$results->data);
  }
 
  private static function error() {
    return self::$error;
  }

  public static function insertId() {
    return self::$results->insertId;
  }
  
  public static function paginate(int $perPage, string $name = 'page', bool $sql = true) {
    
    if ($sql) {
      
      // Current PAge
      $currentPage = isset($_GET[$name]) 
        ? $_GET[$name] 
        : 1;

      // PAgination results  
      $results = 
        self::select('SQL_CALC_FOUND_ROWS *')->limit(( $currentPage - 1) * $perPage, $perPage)->get()->toArray();

      // Total recoreds base on the same query
      $total = 
        self::query('SELECT FOUND_ROWS() as found_rows')->results()->first()->get('found_rows');
      return Paginator::paginate($results, $perPage, $name, $total);
    }

    return self::get()->paginate($perPage, $name);
  }

  public static function first() {
    return self::get()->first();
  }

}