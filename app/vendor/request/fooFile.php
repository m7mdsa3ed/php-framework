<?php 

namespace App\Vendor\Request;

class fooFile extends foo {

  protected $data;

  function __construct(Array $data = []) {
    $this->data = $data;
  }

  function getimagesize() {
    return getimagesize($this->getName());
  }

  function getName() {
    return $this->get('tmp_name');
  }

  function getExtension() {
    return strtolower(pathinfo(basename($this->data["name"]),PATHINFO_EXTENSION));
  }

  function getSize() {
    return $this->get('size');
  }

  function getMime() {
    return $this->getimagesize()['mime'];
  }

  function getOriginalName() {
    return $this->get('name');
  }

  function upload($dir = null, $filename = null, $compressLevel = null) {

    // Default dir
    $dir = ($dir == null) 
      ? ''
      : trim($dir, '/\\');
    
    $dir = "uploads/$dir";
    // Default filename
    $filename = ($filename == null)  ? 'file_' : $filename;
    $filename .= time().'_'.bin2hex(random_bytes(8)) .'.'. $this->getExtension();
    
    // Created dir if not exist
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
    }
    
    // $path to upload
    $path = "$dir/".$filename;

    
    if ($compressLevel) {
      
      $image = imagecreatefromstring(file_get_contents($this->getName()));

      imagejpeg($image, $path, $compressLevel);

      return getDirname() . "/$path";
    }

    // Upload
    return (rename($this->get('tmp_name'), $path))
      ? getDirname() . "/$path"
      : false;
  }
  
  function compress($level) {
    return $this->upload(null,null,$level);
  }

}