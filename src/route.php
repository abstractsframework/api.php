<?php
namespace Abstracts;

use \Abstracts\Database;

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

      $database = new Database($this->config);

      $filters = array(
        "db_name" => str_replace("-", "_", $_GET["module"])
      );
      $module_data = $database->select(
        "module", 
        "*", 
        $filters, 
        null, 
        true
      );
      if (!empty($module_data) && isset($module_data->default_control)) {
        $module_data->default_control = explode(",", $module_data->default_control);
      } else {
        $module_data->default_control = array();
      }

      $this->request = (object) array(
        "module" => (!empty($module_data) ? $module_data : null),
        "class" => $this->get_class_name($_GET["module"]),
        "function" => $function
      );

    }
    
  }

  private function get_class_name($key) {
    $parts = explode("-", $key);
    $formats = array();
    foreach($parts as $part) {
      array_push($formats, ucfirst($part));
    }
    return implode("", $formats);
  }

}