<?php
namespace Abstracts\Core;

class Callback {

  private $paths = array();
  private $exceptions = array(
    "initial.php"
  );

  function __construct($config) {
    if (!empty($config) && isset($config["callback_path"])) {
      array_push($this->paths, ("../" . $config["callback_path"]));
    }
    $this->load();
  }
  
  public function load() {
    foreach($this->paths as $path) {
      $files = scandir($path);
      foreach($files as $file) {
        $info = pathinfo($file);
        if (isset($info["extension"])) {
          $extension = strtolower($info["extension"]);
          if ($extension == "php" && !in_array($file, $this->exceptions)) {
            require_once($path . "/" . $file);
          }
        }
      }
    }
  }

}