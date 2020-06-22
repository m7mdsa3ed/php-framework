<?php 

namespace App\Vendor\Request;

class foo implements \Countable, \IteratorAggregate {

  protected $data;

  function __construct(Array $data = []) {
    $this->data = $data;
  }

  function keys() {
    return array_keys($this->data);
  }

  function replace(Array $data = []) {
    $this->data = $data;
  }

  function add(Array $data = []) {
    $this->data = array_replace($this->data, $data);
  }

  function remove($key) {
    unset($this->data[$key]);
  }

  function set($key, $value) {
    $this->data[$key] = $value;
  }

  function has($key) {
    return array_key_exists($key, $this->data);
  }

  function all() {
    return $this->data;
  }

  function get($key, $default = null) {
    return ($this->has($key)) ? $this->data[$key] : $default;
  }

  function count() {
    return count($this->count);
  }

  function getIterator() {
    return new \ArrayIterator($this->data);
  }

}
