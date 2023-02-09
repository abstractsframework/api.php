<?php
namespace Abstracts;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Route;
use \Abstracts\Helpers\Utilities;
use \Abstracts\Helpers\Translation;

use \Abstracts\Built;
use \Abstracts\Page;

use Exception;

class Render {

  /* core */
  private $config = null;
  private $session = null;
  
  function __construct() {
    Initialize::headers();
    $initialize = new Initialize();
    $this->config = $initialize->config;
    $this->session = $initialize->session;
    if (empty($this->config)) {
      include(__DIR__ . "/Helpers/install/index.html");
    }
  }

  function response() {
    if (!empty($this->config)) {
      
      Initialize::response_headers();
      
      $translation = new Translation();
        
      try {
        
        $message = array(
          400 => $translation->translate("Bad request"),
          405 => $translation->translate("Module or method not found")
        );
        
        $request = Route::get_request();
        if ($request) {
          if ($request->class && $request->function) {
            $namespace = "\\Abstracts\\" . $request->class;
            $parameters = Utilities::handle_request();
            if (class_exists($namespace)) {
              if (method_exists($namespace, $request->function)) {
                $class = new $namespace($this->session, null, $request->module);
                $data = $class->request($request->function, $parameters);
                echo Utilities::handle_response($data);
              } else if (method_exists("\\Abstracts\\Built", $request->function)) {
                $built = new Built($this->session, null, $request->module);
                $data = $built->request($request->function, $parameters);
                echo Utilities::handle_response($data);
              } else {
                throw new Exception($message[405], 405);
              }
            } else {
              if (method_exists("\\Abstracts\\Built", $request->function)) {
                $built = new Built($this->session, null, $request->module);
                $data = $built->request($request->function, $parameters);
                echo Utilities::handle_response($data);
              } else {
                throw new Exception($message[405], 405);
              }
            }
          } else {
            throw new Exception($message[405], 405);
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

  function page() {
    if (!empty($this->config)) {
      
      $translation = new Translation();
        
      try {
        
        $message = array(
          400 => $translation->translate("Bad request"),
          404 => $translation->translate("Endpoint not found"),
          405 => $translation->translate("Method not found"),
          409 => $translation->translate("No response")
        );
        
        echo "";
      
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

}