<?php
namespace Abstracts;

use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use Exception;

class Lock {

  /* configuration */
  private $id = "13";
  private $public_functions = array();
  private $allowed_keys = array();

  /* core */
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
    $this->validation = new Validation($this->config);
    $this->translation = new Translation();
    $this->utilities = new Utilities();

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

  function unlock() {

    $result = false;

    $allows = array("127.0.0.1", "::1");
    if (isset($config["allowed_remote_address"]) && !empty($this->config["allowed_remote_address"])) {
      $allows = explode(",", $this->config["allowed_remote_address"]);
    }
    
    if (isset($this->config["lock"]) && $this->config["lock"]) {
      $lock = null;
      foreach(Utilities::get_headers_all() as $key => $value) {
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
            "value" => "'" . $_SERVER["REMOTE_ADDR"] . "'"
          )
        );
        for ($i = 0; $i < count($allows); $i++) {
          array_push($extensions, 
            array(
              "conjunction" => "OR",
              "key" => "`ip`",
              "operator" => "=",
              "value" => "'" . $allows[$i] . "'"
            )
          );
        }
        if (
          $lock_data = $this->database->select(
            "lock", 
            array("id"), 
            $filters, 
            $extensions, 
            true,
            $this->allowed_keys
          )
        ) {
          $result = true;
          $this->database->delete(
            "lock", 
            array("id" => $lock_data->id), 
            null, 
            true,
            $this->allowed_keys
          );
        }
      } else {
        $result = true;
      }
    } else {
      $result = true;
    }

    return $result;

  }

}