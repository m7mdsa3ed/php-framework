<?php

function autoload($class) {
  $file_position = strrpos($class, '\\');
  $path = __DIR__ .'/../../'.
    str_replace('\\', '/', strtolower(substr($class, 0, $file_position + 1))) .
    substr($class, $file_position + 1) . '.php';
  if (file_exists($path)) {
    include_once $path;
  }
}

spl_autoload_register('autoload');
