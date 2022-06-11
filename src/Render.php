<?php
namespace Abstracts;

use \Abstracts\Helpers\Route;
use \Abstracts\Helpers\Utilities;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Initialization;

use \Abstracts\User;
use \Abstracts\Built;

use Exception;

class Render {

  /* core */
  private $config = null;
  
  function __construct($config) {
    $this->config = Initialization::config($config);
    Initialization::headers($this->config);
    Initialization::load($this->config);
  }

  function response() {

    Initialization::response_headers($this->config);
      
    try {
      
      $translation = new Translation();
      
      $user = new User($this->config);
      $session = $user->authenticate(null, false);
      
      $message = array(
        400 => $translation->translate("Bad request"),
        404 => $translation->translate("Not found"),
        409 => $translation->translate("No response")
      );
    
      $route = new Route($this->config);
      if ($route->request) {
        if ($route->request->class && $route->request->function) {
          $namespace = "\\Abstracts\\" . $route->request->class;
          $parameters = Utilities::format_request();
          if (class_exists($namespace)) {
            if (method_exists($namespace, $route->request->function)) {
              $class = new $namespace($this->config, $session, null, $route->request->module);
              $data = $class->request($route->request->function, $parameters);
              echo Utilities::handle_response($data);
            } else if (method_exists("\\Abstracts\\Built", $route->request->function)) {
              $built = new Built($this->config, $session, null, $route->request->module);
              $data = $built->request($route->request->function, $parameters);
              echo Utilities::handle_response($data);
            } else {
              throw new Exception($message[404], 404);
            }
          } else {
            if (method_exists("\\Abstracts\\Built", $route->request->function)) {
              $built = new Built($this->config, $session, null, $route->request->module);
              $data = $built->request($route->request->function, $parameters);
              echo Utilities::handle_response($data);
            } else {
              throw new Exception($message[404], 404);
            }
          }
        } else {
          throw new Exception($message[400], 400);
        }
      } else {
        throw new Exception($message[400], 400);
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
    
  }

}