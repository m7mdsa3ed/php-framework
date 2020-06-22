<?php

namespace App\Vendor;

use App\Controllers\Auth;
use App\Vendor\Request\Request;

/*
  image 
  Size:5
  mime:png, jpg
  min:8
  max:32
  confirmed
  unique:modelName 
  int
for Example : 

  $valid = new Validation($values as an Array, [
    '$item' => ['required', 'min:8', 'max:32', 'confirmed', 'unique:user','image', 'int']
  ])

*/

class Validator {

  private $errors = [];
  private $passed = false;

  function __construct($request, array $items) {

    foreach ($items as $item => $props) {
      foreach ($props as $prop) {
        if ( array_key_exists($item, $request) ) {
          $givenValue = $this->getValue($request[$item]);
          // Required
          if ($prop == 'required' && empty($givenValue) ) {
            $this->errors[] = "$item is required ";
          } elseif (!empty($givenValue)) {

            // Confirmation 
            if ($prop == 'confirmed') {
              $value_confirmation = "$item" . "_confirmation";
              if (isset($request[$value_confirmation])) {
                if ($givenValue !== $request[$value_confirmation]) {
                  $this->errors[] = "$item not matched ";
                }
              } else {
                $this->errors[] = "$item not matched ";
              }
            }

            // min
            if (strstr($prop, 'min:')) {
              $min = str_replace('min:', '', $prop);
              if (strlen($givenValue) < $min) {
                $this->errors[] = "$item should have more than $min chars";
              }
            }

            // max
            if (strstr($prop, 'max:')) {
              $max = str_replace('max:', '', $prop);
              if (strlen($givenValue) > $max) {
                $this->errors[] = "$item should have less than $max chars";
              }
            }

            if ($prop === 'email') {
              if (!filter_var($givenValue, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "$item not a valid email";
              }
            }


            if ($prop == 'image') {
              if (!$this->isImage($givenValue)) {
                $this->errors[] = "$item is not an image";
              }
            }

            // Image Extensions
            if (strstr($prop, 'mime:')) {
              if ($this->isImage($givenValue)) {
                $mimes = explode(',', str_replace('mime:', '', $prop));
                if (!in_array(strtolower(pathinfo($givenValue['name'], PATHINFO_EXTENSION)), $mimes)) {
                  $this->errors[] = 'Extension not allowed';
                }
              }
            }

            // Image Size
            if (strstr($prop, 'size:')) {
              if ($this->isImage($givenValue)) {
                if (strstr($prop, 'size:')) {
                  $size = str_replace('size:', '', $prop);
                  if ($givenValue['size'] > ($size * 1024 * 1024)) {
                    $this->errors[] = 'Oversized';
                  }
                }
              }
            }

            // Integer
            if ($prop == 'int') {
              if (!is_numeric($givenValue)) {
                $this->errors[] = "$item should be a number";
              }
            }

            // Unique
            if (strstr($prop, 'unique:')) {

              $modelName = explode(', ',  str_replace('unique:', '', $prop))[0];
              $modelName = "App\Models\\".$modelName;

              // Check if class exists
              if (class_exists($modelName)) {

                // Search fro matches
                $data = $modelName::where([$item => htmlentities($givenValue)])->get();

                // There's a match [UNIQUE]
                if ( $data->count() ) { 
                  
                  // Get session user data if exists
                  $userForSession = (Auth::check())
                    ? Auth::user()
                    : null;

                  // Check whether it matchs with database data or not [ THE OWNER ]
                  // The owner can use the same unique values, it's - MY MINE - his
                  if ( $userForSession && $userForSession->$item == $givenValue ) continue;

                  // Then it's unique and dosen't match with session user
                  $this->errors[] = "$item already exists";
                }
              }
            }
          }
        } else {
          $this->errors[] = "$item not provided";
        }
      }
      
    }

    if (empty($this->errors)) {
      $this->passed = true;
    }

    return $this;
  }

  private function isImage($value) {
    $checkFile = (file_exists($value["tmp_name"])) ? true : false;
    if ($checkFile) {
      if (getimagesize($value["tmp_name"]) !== false) {
        return true;
      }
      return false;
    }
  }

  private function getValue($value) {
    if (is_array($value) && isset($value['tmp_name'])) {
      if (empty($value['tmp_name'])) {
        return '';
      }
    }
    return (is_array($value)) ? $value : trim($value);
  }

  function errors() {
    return array_values(array_unique($this->errors));
  }

  function passed() {
    return $this->passed;
  }

}