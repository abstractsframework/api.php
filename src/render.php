<?php
namespace Abstracts;

use \Abstracts\Route;
use \Abstracts\Utilities;
use \Abstracts\Translation;
use \Abstracts\Authorization;
use \Abstracts\Built;

use Exception;

class Render {

  private $config = null;
  
  function __construct($config) {
    $this->config = $config;
  }

  function response() {

    header("Content-Type: application/json");

    try {

      $session = null;
      
      $translation = new Translation();
      
      $authorization = new Authorization($this->config);
      $session = $authorization->get_session();
      
      $message = array(
        400 => $translation->translate("Bad request"),
        404 => $translation->translate("Not found")
      );
    
      $route = new Route($this->config);
      if ($route->request) {
        if ($route->request->class && $route->request->function) {
          $module = $route->request->module;
          if ($route->request->class == "User" && empty($module)) {
            $module = $authorization->user_module;
          }
          $namespace = "\\Abstracts\\" . $route->request->class;
          if (class_exists($namespace)) {
            if (method_exists($namespace, $route->request->function)) {
              $class = new $namespace($this->config, $session, null, $module);
              $utilities = new Utilities();
              $parameters = $utilities->format_request();
              $data = $class->request($route->request->function, $parameters);
              echo json_encode($data);
            } else {
              throw new Exception($message[404], 404);
            }
          } else {
            if (method_exists("\\Abstracts\\Built", $route->request->function)) {
              $built = new Built($this->config, $session, null, $route->request);
              $utilities = new Utilities();
              $parameters = $utilities->format_request();
              $data = $built->request($route->request->function, $parameters);
              echo json_encode($data);
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
      echo json_encode(
        array(
          "status" => false,
          "code" => $e->getCode(),
          "message" => $e->getMessage()
        )
      );
    }
    
  }

}