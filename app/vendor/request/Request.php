<?php

namespace App\Vendor\Request;

class Request {

  public $query, $request, $put, $cookies, $files, $server, $content;

  function __construct() {
    $this->query = new foo($_GET);
    $this->request = new foo($_POST);
    $this->put = new foo($this->_parsePut());
    $this->cookies = new foo($_COOKIE);
    $this->files = new foo($_FILES);
    $this->server = new foo($_SERVER);
  }

  function __get($name) {
    return $this->get($name);
  }

  function get($key) {
    if ($this !== $result = $this->query->get($key, $this)) {
      return $result;
    }
    if ($this !== $result = $this->request->get($key, $this)) {
      return $result;
    }
    if ($this !== $result = $this->put->get($key, $this)) {
      return $result;
    }
  }

  function file($key) {
    if ($this !== $result = $this->files->get($key, $this)) {
      return new fooFile($result);
    }
  }

  function getContent() {
    $this->content = file_get_contents('php://input');
    return $this->content;
  }

  function has($key) {
    return ($this->get($key)) ? true : false;
  }

  function hasFile($key) {
    return ($this->file($key)) ? true : false;
  }

  function all() {
    return array_merge($this->query->all(), $this->request->all(), $this->files->all(), $this->put->all());
  }

  private function _parsePut() {

    if ($_SERVER['REQUEST_METHOD'] != 'PUT') {
      return array();
    }

    /* PUT data comes in on the stdin stream */
    $putdata = fopen("php://input", "r");

    $raw_data = '';

    /* Read the data 1 KB at a time
       and write to the file */
    while ($chunk = fread($putdata, 1024))
      $raw_data .= $chunk;

    /* Close the streams */
    fclose($putdata);

    // Fetch content and determine boundary
    $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));

    // not form-data
    if (empty($boundary)) {

      $ct = ($_SERVER['CONTENT_TYPE']) ?? '';

      if ($ct == 'application/x-www-form-urlencoded') {
        parse_str($raw_data, $data);
        return $data;
      } elseif ($ct == 'text/plain') {
        return (array) json_decode($raw_data);
      }

      return array();
    }

    // Fetch each part
    $parts = array_slice(explode($boundary, $raw_data), 1);
    $data = array();

    foreach ($parts as $part) {
      // If this is the last part, break
      if ($part == "--\r\n") break;

      // Separate content from headers
      $part = ltrim($part, "\r\n");
      list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);

      // Parse the headers list
      $raw_headers = explode("\r\n", $raw_headers);
      $headers = array();
      foreach ($raw_headers as $header) {
        list($name, $value) = explode(':', $header);
        
        $headers[strtolower($name)] = ltrim($value, ' ');
      }

      // Parse the Content-Disposition to get the field name, etc.
      if (isset($headers['content-disposition'])) {
        $filename = null;
        $tmp_name = null;
        preg_match(
          '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
          $headers['content-disposition'],
          $matches
        );
        list(, $type, $name) = $matches;

        //Parse File
        if (isset($matches[4])) {
          //if labeled the same as previous, skip
          if (isset($_FILES[$matches[2]])) {
            continue;
          }

          //get filename
          $filename = $matches[4];

          //get tmp name
          $filename_parts = pathinfo($filename);
          $tmp_name2 = tempnam(ini_get('upload_tmp_dir'), $filename_parts['filename']);
          $tmp_name = sys_get_temp_dir().'/php'.substr(sha1(rand()), 0, 6);

          //populate $_FILES with information, size may be off in multibyte situation
          $_FILES[$matches[2]] = array(
            'error' => 0,
            'name' => $filename,
            'tmp_name' => $tmp_name,
            'size' => strlen($body),
            'type' => $value
          );

          //place in temporary directory
          file_put_contents($tmp_name, $body);
        }
        //Parse Field
        else {

          // Check for array
          if (preg_match('/^(.*)\[\]$/i', $name, $tmp)) {
            $data[$tmp[1]][] = substr($body, 0, strlen($body) - 2);
          } else {
            $data[$name] = substr($body, 0, strlen($body) - 2);

          }
        }
      }
    }
    return $data;
  }

  // You need to send 'X-Requested-With': 'XMLHttpRequest' header with ajax request to be detected
  public function ajax() {
    return 
      !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ;
  }

  public function isHTTPS() {
    return $this->server->has('HTTPS');
  }

  public function protocol() {
    return  ($this->isHTTPS()) ? 'https://' : 'http://';
  }

  public function url($withHost = false, $withQueryString = false) {

    $result = '';

    if ($withHost) {
      $result .= $this->protocol() . $this->server->get('HTTP_HOST');
    }

    if ($withQueryString) {
      $result .= $this->server->get('REQUEST_URI');
    } else {
      $result .= parse_url($this->server->get('REQUEST_URI'))['path'];
    }

    return $result;    
  }

  public function queryStringArray($queryString = null) {

    $result = [];
    $queryString = $queryString ?? $this->server->get('QUERY_STRING');

    if (!empty($queryString)) {

      $querys = explode('&', $this->server->get('QUERY_STRING'));
      
      foreach ($querys as $query) {
        list($key, $value) = explode('=',$query);
        $result[$key] = $value;
      }
    }

    return $result;
  }

}
