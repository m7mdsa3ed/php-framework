<?php

namespace App\Vendor;

// Source: https://codeshack.io/lightweight-template-engine-php/

class View {

  // Sections Array 
  static $sections = [];

  /** Enabled: That means every view will compile once at first 
   *           and when original view updated otherwise won't be compiled
   *  Disabled: That means on every request the compiling will happen */
  static $cache_enabled = false;

  private static function getCachePath() {
    return dirname(__DIR__, 1) .'/'. 'cache/views/';
  }

  public static function get(string $fileName, array $data = []) {

    // Get compiled file / create one
    $view  = self::prepare($fileName);

    // Extract the given data to view
    extract($data, EXTR_SKIP);

    // View the View xD 
    require $view;
  }

  private static function prepare(string $file) {

    
    // Create Cache Folder to work within
    if (!file_exists(self::getCachePath())) {
      mkdir(self::getCachePath(), 0744, true);
    }
    
    // Path to cached file 
    $tmpfile = self::getCachePath() . pathinfo($file)['filename'] . '.cache.file';
        
    if (!self::$cache_enabled || !file_exists($tmpfile) || filemtime($tmpfile) < filemtime($file)) {
      $fileContents = self::extendsIncludeCode($file);
      $fileContents = self::prepareCode($fileContents);
      
      file_put_contents($tmpfile, $fileContents);
    }
    return $tmpfile;
  }

  // Delete all files in $cache_path
  public static function clearCache() {

    foreach (glob(self::getCachePath() . '*') as $file) {
      unlink($file);
    }

  }

  // Prepare everything 
  private static function prepareCode($fileContents) {

    $fileContents = self::sectionCode($fileContents);
    $fileContents = self::slotCode($fileContents);    

    $fileContents = self::echoCode($fileContents);
    $fileContents = self::escapedEchosCode($fileContents);
    $fileContents = self::ifCode($fileContents);
    $fileContents = self::phpCode($fileContents);
    $fileContents = self::foreachCode($fileContents);
    
    return $fileContents;
  }

  // @extends('file.php') @include('file')
  private static function extendsIncludeCode($file) {

    // Get the content ( code ) of given file
    $fileContents = file_get_contents(trim($file,'\'"'));
    
    // Searching Regex
    $regex = '/(@extends|@include)\s*\((.*)\)/i';

    // If there's a match with search pattern 
    // then include the file 
    if ( preg_match_all($regex, $fileContents, $matches, PREG_SET_ORDER) ) {      
      foreach ($matches as $value) {

        // Include file content
        $fileContents = str_replace($value[0], self::extendsIncludeCode($value[2]), $fileContents);
      }
    }

    // Clean up the file and return it
    return preg_replace($regex, '', $fileContents);
  }

  // Slots to be filled with sections 
  private static function slotCode($fileContents) {

    foreach (self::$sections as $section => $value) {

      // Replace each slot with its section value 
      $fileContents = preg_replace("/@slot\s*\(\"?\'?".$section."\'?\"?\)/", $value, $fileContents);
    }

    return preg_replace('~@slot\s*\((.*?)\)~i', '', $fileContents);
  }

  private static function sectionCode($fileContents) {
    $regex = '~@section\s*\((.*?)\)\s*(.*?)@endsection~mis';

    // There's sections 
    if ( preg_match_all($regex, $fileContents, $matches, PREG_SET_ORDER) ) {

      foreach ($matches as $value) {

        list(, $sectionName, $sectionContents) = $value;

        $sectionName = trim($sectionName, '\'"');

        // If not exists before in the same page
        if ( !array_key_exists($sectionName, self::$sections) ) {
          self::$sections[$sectionName] = '';
        }

        if (strpos($value[2], '@parent') === false) {
          
          self::$sections[$sectionName] = $sectionContents;
        } 
        
        else {
          self::$sections[$sectionName] = str_replace('@parent', self::$sections[$sectionName], $sectionContents);
        }

        $fileContents = str_replace($value[0], '', $fileContents);
      }

    }
    return $fileContents;
  }

  // @foreach (condition) @endforeach
  private static function foreachCode($fileContents) {
    return preg_replace('~@foreach\s*\((.*)\)\s*(.*)\s*@endforeach~mis','<?php foreach($1): ?> $2 <?php endforeach; ?>', $fileContents);
  }

  // @php code @endphp
  private static function phpCode($fileContents) {
    return preg_replace('~@php\s*(.*)\s*@endphp~mis', '<?php $1 ?>', $fileContents);
  }

  // @if (condition) @endif
  private static function ifCode($fileContents) {
    return preg_replace('~@if\s*\((.*?)\)\s*(.*)(@elseif\s*\((.*)\)\s*(.*))@else\s*(.*)\s*@endif~mis', '<?php if ($1): ?> $2 <?php elseif ($4): ?> $5 <?php else: ?> $6 <?php endif; ?>', $fileContents);
  }

  private static function echoCode($fileContents) {
    return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo "$1" ?>', $fileContents);
  }

  // {! <p> Hello </p> !}
  private static function escapedEchosCode($fileContents) {
    return preg_replace('~\{!\s*(.+?)\s*\!}~is', '<?php echo htmlentities("$1", ENT_QUOTES, \'UTF-8\') ?>', $fileContents);
  }

}
