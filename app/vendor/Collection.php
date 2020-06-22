<?php

namespace App\Vendor;

class Collection implements \Countable, \IteratorAggregate, \JsonSerializable  {

  private $data = [];

  function __construct($data = []) {
    $this->data = (array) $data;    
  }

  function __get($name) {
    return $this->get($name);
  }

  function all() {
    return $this->data;
  }

  function first() {
    if ($this->count()) {
      return new self($this->data[0]);
    }
  }

  function has($key) {
    return array_key_exists($key, $this->data);
  }
  
  function keys() {
    return array_keys($this->data);
  }
  
  public function set($key, $value) {
      $this->data[$key] = $value;
  }

  function get($key, $default = null) {
    return ($this->has($key)) 
      ? $this->data[$key] 
      : $default;
  }

  function count() {
    return count($this->data);
  }

  function getIterator() {
    return new \ArrayIterator($this->data);
  }

  function toArray() {
    return $this->data;
  }

  function pluck($key) {

    $this->data = array_map(function($v) use ($key) {
      return is_object($v) ? $v->$key : $v[$key];
    }, $this->data);

    return $this;
  }
   
  function jsonSerialize() {
    return $this->data;
  }

  function paginate($perPage, $queryName = 'page') {
    return Paginator::paginate($this->data, $perPage, $queryName, null);
  }

  function add(...$params) {
    if ( func_num_args() == 1 ) {
      $this->data[] = $params;
    } 
    
    elseif ( func_num_args() == 2 ) {
      list($key, $value) = $params;
      $this->data[$key] = $value;
    }

    return $this;
  }

}