<?php
namespace Abstracts;

use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Security;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use \Abstracts\Lock;

use Exception;

class API {

  /* configuration */
  private $id = "12";
  private $public_functions = array();
  private $allowed_keys = array();

  /* core */
  public $module = null;
  private $config = null;
  private $session = null;
  private $controls = null;

  /* helpers */
  private $database = null;
  private $security = null;
  private $validation = null;
  private $translation = null;
  private $utilities = null;

  /* services */
  private $lock = null;

  function __construct(
    $config,
    $session = null,
    $controls = null,
    $identifier = null
  ) {

    /* initialize: core */
    $this->config = $config;
    $this->session = $session;
    $this->module = Utilities::sync_module($config, $identifier);
    $this->controls = Utilities::sync_control(
      $this->id, 
      $session, 
      $controls,
      $this->module
    );
    
    /* initialize: helpers */
    $this->database = new Database($this->config, $this->session, $this->controls);
    $this->security = new Security($this->config);
    $this->validation = new Validation($this->config);
    $this->translation = new Translation();
    $this->utilities = new Utilities();

    /* initialize: services */
    $this->lock = new Lock($this->config, $this->session, 
      Utilities::override_controls(true, true, true, true)
    );

    /* initialize: module */
    $this->initialize();

  }

  private function initialize() {
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

  function authorize($module_id = null, $function, $public_functions = array()) {

    $result = false;
    
    if (in_array($function, $public_functions)) {
      $result = true;
    } else {

      $allows = array("127.0.0.1", "::1");
      if (isset($config["allowed_remote_address"]) && !empty($this->config["allowed_remote_address"])) {
        $allows = explode(",", $this->config["allowed_remote_address"]);
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

        $extract = function($authorization) {
          $authorization_parts = explode(":", base64_decode($authorization));
          $credentials = (object) array(
            "key" => null,
            "secret" => null,
            "nonce" => null
          );
          $credentials->key = $authorization_parts[0];
          if (isset($authorization_parts[1])) {
            $credentials->secret = $authorization_parts[1];
          }
          if (isset($authorization_parts[2])) {
            $credentials->nonce = $authorization_parts[2];
          }
          return $credentials;
        };

        $key = null;
        $secret = null;
        $nonce = null;
        if (isset($_POST["token"]) && !empty($_POST["token"])) {
          $key = $extract($_POST["token"])->key;
          $secret = $extract($_POST["token"])->secret;
          $nonce = $extract($_POST["token"])->nonce;
        } else if (isset($_GET["token"]) && !empty($_GET["token"])) {
          $key = $extract($_GET["token"])->key;
          $secret = $extract($_GET["token"])->secret;
          $nonce = $extract($_POST["token"])->nonce;
        } else {
          foreach(Utilities::get_headers_all() as $name => $value) {
            if (strtolower($name) == "token") {
              $key = $extract($value)->key;
              $secret = $extract($value)->secret;
              $nonce = $extract($value)->nonce;
            } else if (strtolower($name) == "key") {
              $key = $extract($value)->key;
            } else if (strtolower($name) == "secret") {
              $secret = $extract($value)->secret;
            }
          }
        }
        
        if (!empty($key) && $this->lock->unlock() && $this->security->verify_nonce($nonce)) {
          if (
            $api_data = $this->database->select(
              "api", 
              "*", 
              array("key" => $key), 
              null, 
              true,
              $this->allowed_keys
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
      throw new Exception($this->translation->translate("Unauthorized API"), 401);
      return false;
    }

  }

}