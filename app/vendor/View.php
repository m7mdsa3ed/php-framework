<?php 

namespace App\Vendor;

class View {

  private static 
    $file,
    $data;

  public static function template($file) {
    $template =  __DIR__ . "/../views/" . $file . ".view.php";

    if (is_file($template)) {
      self::$file = $template;
    }
    return new self;
  }

  public function h($data) {
    return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
  }

  public static function data($data) {
    self::$data = $data;
    return new self;
  }

  public static function render() {
    if (is_array(self::$data) && !empty(self::$data)) {
      extract(self::$data);
    }
    ob_start();
    include self::$file;
    return ob_get_clean();
  }

}