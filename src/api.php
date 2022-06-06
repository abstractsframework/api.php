<?php
namespace Abstracts;

use \Abstracts\Database;
use \Abstracts\Validation;
use \Abstracts\Translation;
use \Abstracts\Utilities;

use Exception;

class API {

  /* configuration */
  private $id = "12";
  private $public_functions = array(
	);

  /* initialization */
  public $module = null;
  private $config = null;
  private $session = null;
  private $controls = null;

  /* helpers */
  private $database = null;
  private $validation = null;
  private $translation = null;
  private $utilities = null;

  function __construct(
    $config,
    $session = null,
    $controls = null,
    $module = null
  ) {

    $this->module = $module;
    $this->config = $config;
    $this->session = $session;
    $this->controls = Utilities::sync_control(
      $this->id, 
      $session, 
      $controls,
      $module
    );
    
    $this->database = new Database($this->config, $this->session, $this->controls);
    $this->validation = new Validation($this->config);
    $this->translation = new Translation();
    $this->utilities = new Utilities();

    $this->initialize();

  }

  function initialize() {
    if (empty($this->module)) {
      $this->module = $this->database->select(
        "module", 
        "*", 
        array("id" => $this->id), 
        null, 
        true
      );
    }
  }

  function validate_request($module_id = null, $function, $public_functions = array()) {

    $result = false;
    
    if (in_array($function, $public_functions)) {
      $result = true;
    } else {

      $allows = array("127.0.0.1", "::1");
      if (isset($config["allowed_remote_address"]) && !empty($config["allowed_remote_address"])) {
        $allows = explode(",", $config["allowed_remote_address"]);
      }
  
      if (isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], $allows)) {
        $result = true;
      } else if (isset($_SERVER["HTTP_CLIENT_IP"]) && in_array($_SERVER["HTTP_CLIENT_IP"], $allows)) {
        $result = true;
      } else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && in_array($_SERVER["HTTP_X_FORWARDED_FOR"], $allows)) {
        $result = true;
      } else if (isset($_SERVER["HTTP_ORIGIN"]) && in_array($_SERVER["HTTP_ORIGIN"], $allows)) {
        $result = true;
      } else if (isset($_SERVER["HTTP_REFERER"]) && in_array($_SERVER["HTTP_REFERER"], $allows)) {
        $result = true;
      } else {
        
        $authorization = null;
        $key = null;
        $secret = null;
        foreach($this->utilities->get_headers_all() as $name => $value) {
          if (strtolower($name) == "token") {
            $authorization = $value;
          } else if (strtolower($name) == "key") {
            $key = $value;
          } else if (strtolower($name) == "secret") {
            $secret = $value;
          }
        }
        if (is_null($authorization)) {
          if (isset($_POST["token"]) && !empty($_POST["token"])) {
            $authorization = $_POST["token"];
          } else if (isset($_GET["token"]) && !empty($_GET["token"])) {
            $authorization = $_GET["token"];
          }
        }

        $nonced = false;
        if (!is_null($authorization)) {
          $authorization_parts = explode(":", base64_decode($authorization));
          $key = $authorization_parts[0];
          if (isset($authorization_parts[1])) {
            $secret = $authorization_parts[1];
          }
          if (isset($authorization_parts[2])) {
            $nonce = explode(".", $authorization_parts[2]);
            if (isset($nonce[0]) && isset($nonce[1])) {
              $nonced = true;
            }
          } else {
            $nonced = !$this->config["nonce"];
          }
        } else {
          $nonced = !$this->config["nonce"];
        }
  
        $unlock = false;
        if (isset($config["lock"]) && $config["lock"]) {
          $lock = null;
          foreach($this->utilities->get_headers_all() as $key => $value) {
            if (strtolower($key) == "lock") {
              $lock = $value;
            }
          }
          if (is_null($lock)) {
            if (isset($_POST["lock"]) && !empty($_POST["lock"])) {
              $lock = $_POST["lock"];
            } else if (isset($_GET["lock"]) && !empty($_GET["lock"])) {
              $lock = $_GET["lock"];
            }
          }
          if (isset($lock) && $lock != null) {
            $filters = array(
              "hash" => $lock
            );
            $extensions = array(
              array(
                "conjunction" => "",
                "key" => "`ip`",
                "operator" => "=",
                "value" => "'" . $_SERVER["REMOTE_ADDR"] . "'",
              )
            );
            for ($i = 0; $i < count($allows); $i++) {
              array_push($extensions, 
                array(
                  "conjunction" => "OR",
                  "key" => "`ip`",
                  "operator" => "=",
                  "value" => "'" . $allows[$i] . "'",
                )
              );
            }
            if (
              $lock_data = $this->database->select(
                "lock", 
                array("id"), 
                $filters, 
                $extensions, 
                true
              )
            ) {
              $unlock = true;
              $this->database->delete(
                "lock", 
                array("id" => $lock_data->id), 
                null, 
                true
              );
            }
          } else {
            $unlock = true;
          }
        }
  
        if ($nonced && $unlock && isset($key)) {
          if (
            $api_data = $this->database->select(
              "api", 
              array("*"), 
              array("key" => $key), 
              null, 
              true
            )
          ) {
            $scope = array();
            if ($api_data->scope) {
              $scope = unserialize($api_data->scope);
            }
            if ($api_data->type == "public" || $api_data->secret == $secret) {
              if (in_array($module_id, $scope)) {
                $result = true;
              }
            }
          }
        }

      }

    }
    
    if ($result) {
      return $result;
    } else {
      throw new Exception($this->translation->translate("Permission denied"), 403);
      return false;
    }

  }
}