<?php
namespace Abstracts;

use \Abstracts\Database;
use \Abstracts\Utilities;

class Route {

  /* initialization */
  private $config = null;
  
  public $path = "";
  public $parts = array();
  public $request = false;

  function __construct($config) {
    
    $this->config = $config;

    $this->initialize();

  }

  private function initialize() {

    if (isset($_GET["module"])) {

      $function = null;
      if (isset($_GET["function"])) {
        $function = $_GET["function"];
      } else {
        if ($_SERVER["REQUEST_METHOD"] === "GET") {
          $function = "get";
        } else if ($_SERVER["REQUEST_METHOD"] === "PUT") {
          $function = "update";
        } else if ($_SERVER["REQUEST_METHOD"] === "PATCH") {
          $function = "patch";
        } else if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
          $function = "delete";
        }
      }

      $this->request = (object) array(
        "module" => (isset($_GET["module"]) ? str_replace("-", "_", $_GET["module"]) : null),
        "class" => Utilities::get_class_name(isset($_GET["module"]) ? $_GET["module"] : null),
        "function" => $function
      );

    }
    
  }

}