<?php
require("../../../../../../autoload.php");
require("../services/Install.php");

use \Abstracts\Install;
use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Utilities;

$install = new Install();

try {
  if (!empty($_GET["install"])) {
    Initialize::headers();
    Initialize::response_headers();
    $parameters = Utilities::handle_request();
    if ($_GET["install"] == "config") {
      $result = $install->config(
        (isset($parameters["site_name"]) ? $parameters["site_name"] : null), 
        (isset($parameters["password_salt"]) ? $parameters["password_salt"] : null), 
        (isset($parameters["database_host"]) ? $parameters["database_host"] : null), 
        (isset($parameters["database_name"]) ? $parameters["database_name"] : null), 
        (isset($parameters["database_login"]) ? $parameters["database_login"] : null), 
        (isset($parameters["database_password"]) ? $parameters["database_password"] : null)
      );
      echo Utilities::handle_response($result);
    } else if ($_GET["install"] == "database") {
      $result = $install->database(
        (isset($parameters["name"]) ? $parameters["name"] : null), 
        (isset($parameters["username"]) ? $parameters["username"] : null), 
        (isset($parameters["password"]) ? $parameters["password"] : null), 
        (isset($parameters["password_salt"]) ? $parameters["password_salt"] : null), 
        (isset($parameters["database_host"]) ? $parameters["database_host"] : null), 
        (isset($parameters["database_name"]) ? $parameters["database_name"] : null), 
        (isset($parameters["database_login"]) ? $parameters["database_login"] : null), 
        (isset($parameters["database_password"]) ? $parameters["database_password"] : null)
      );
      echo Utilities::handle_response($result);
    } else if ($_GET["install"] == "directories") {
      $result = $install->directories(
        (isset($parameters["type"]) ? $parameters["type"] : "web")
      );
      echo Utilities::handle_response($result);
    } else if ($_GET["install"] == "server") {
      $result = $install->server(
        (isset($parameters["type"]) ? $parameters["type"] : "web")
      );
      echo Utilities::handle_response($result);
    }
  }
} catch(Exception $e) {
  header($e->getMessage(), true, $e->getCode());
  echo Utilities::handle_response(
    array(
      "status" => false,
      "code" => $e->getCode(),
      "message" => $e->getMessage()
    )
  );
}