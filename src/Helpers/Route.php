<?php
namespace Abstracts\Helpers;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Utilities;

class Route {
  
  public $path = "";
  public $parts = array();
  public $request = false;

  function __construct() {
    Initialize::load();
  }

  public static function get_request() {

    $request = ltrim(
      str_replace(
        str_replace($_SERVER["DOCUMENT_ROOT"], "", getcwd()), "", $_SERVER["REQUEST_URI"]
      ), "/"
    );
    $request_module = null;
    $request_function = null;
    
    if (preg_match("/^([A-Za-z0-9\.\_\-]+)\/(\?(.*))?$/", $request, $matches)) {
      $request_module = $matches[1];
    } else if (preg_match("/^([A-Za-z0-9\.\_\-]+)\/([A-Za-z\_]+)(\?(.*))?$/", $request, $matches)) {
      $request_module = $matches[1];
      $request_function = $matches[2];
    } else if (preg_match("/^([A-Za-z0-9\.\_\-]+)\/([0-9]+)(\?(.*))?$/", $request, $matches)) {
      $request_module = $matches[1];
      $_REQUEST["id"] = $matches[2];
    } else if (preg_match("/^([A-Za-z0-9\.\_\-]+)\/([0-9]+)\/([A-Za-z\_]+)(\?(.*))?$/", $request, $matches)) {
      $request_module = $matches[1];
      $request_function = $matches[3];
      $_REQUEST["id"] = $matches[2];
    } else if (preg_match("/^([A-Za-z0-9\.\_\-]+)\/([0-9]+)\/([A-Za-z\_]+)\/data(\?(.*))?$/", $request, $matches)) {
      $request_module = $matches[1];
      $request_function = "data";
      $_REQUEST["id"] = $matches[2];
      $_REQUEST["key"] = $matches[3];
    } else if (preg_match("/^([A-Za-z0-9\.\_\-]+)\/([A-Za-z\_]+)\/options(\?(.*))?$/", $request, $matches)) {
      $request_module = $matches[1];
      $request_function = "options";
      $_REQUEST["key"] = $matches[2];
    } else if (preg_match("/^([A-Za-z0-9\.\_\-]+)\/([A-Za-z\_]+)\/([0-9]+)(\?(.*))?$/", $request, $matches)) {
      $request_module = $matches[1];
      $request_function = $matches[2];
      $_REQUEST[$matches[2]] = $matches[3];
    }
    
    if (isset($request_module)) {

      $function = null;
      if (isset($request_function)) {
        $function = $request_function;
        if ($function == "file" && $_SERVER["REQUEST_METHOD"] === "DELETE") {
          $function = "remove";
        } else if ($function == "abstracts" && $_SERVER["REQUEST_METHOD"] === "POST") {
          $function = "import";
        } else if ($function == "abstracts" && $_SERVER["REQUEST_METHOD"] === "GET") {
          $function = "export";
        } else if ($request_module == "group") {
          if ($function == "members") {
            if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_REQUEST["id"])) {
              $function = "add_member";
            } else if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
              $function = "remove_member";
            }
          }
        }
      } else {
        if (isset($_REQUEST["id"])) {
          if ($_SERVER["REQUEST_METHOD"] === "PUT") {
            $function = "update";
          } else if ($_SERVER["REQUEST_METHOD"] === "PATCH") {
            $function = "patch";
          } else if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
            $function = "delete";
          } else {
            $function = "get";
          }
        } else {
          if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $function = "create";
          } else if ($_SERVER["REQUEST_METHOD"] === "COPY") {
            $function = "copy";
          }
        }
      }

      return (object) array(
        "module" => (isset($request_module) ? str_replace("-", "_", $request_module) : null),
        "class" => Utilities::create_class_name(isset($request_module) ? $request_module : null),
        "function" => $function
      );

    }
  }

}