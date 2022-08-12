<?php
namespace Abstracts;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use Exception;

class Lock {

  /* configuration */
  public $id = "13";
  public $public_functions = array();
  public $module = null;

  /* core */
  private $config = null;
  private $session = null;
  private $controls = null;

  /* helpers */
  private $database = null;
  private $validation = null;
  private $translation = null;

  /* services */
  private $api = null;

  function __construct(
    $session = null,
    $controls = null
  ) {

    /* initialize: core */
    $initialize = new Initialize($session, $controls, $this->id);
    $this->config = $initialize->config;
    $this->session = $initialize->session;
    $this->controls = $initialize->controls;
    $this->module = $initialize->module;
    
    /* initialize: helpers */
    $this->database = new Database($this->session, $this->controls);
    $this->validation = new Validation();
    $this->translation = new Translation();

  }

  function unlock() {

    $result = false;

    $allows = array("127.0.0.1", "::1");
    if (isset($config["allowed_remote_address"]) && !empty($this->config["allowed_remote_address"])) {
      $allows = explode(",", $this->config["allowed_remote_address"]);
    }
    
    if (isset($this->config["lock"]) && $this->config["lock"]) {
      $hash = null;
      if (isset($_POST["h"]) && !empty($_POST["h"])) {
        $hash = $_POST["h"];
      } else if (isset($_REQUEST["h"]) && !empty($_REQUEST["h"])) {
        $hash = $_REQUEST["h"];
      } else {
        foreach (Utilities::get_all_headers() as $key => $value) {
          if (strtolower($key) == "hash") {
            $hash = $value;
          }
        }
      }
      if (isset($hash) && $hash != null) {
        $filters = array(
          "hash" => $hash
        );
        $extensions = array(
          array(
            "conjunction" => "",
            "key" => "ip",
            "operator" => "=",
            "value" => "'" . $_SERVER["REMOTE_ADDR"] . "'"
          )
        );
        for ($i = 0; $i < count($allows); $i++) {
          array_push($extensions, 
            array(
              "conjunction" => "OR",
              "key" => "ip",
              "operator" => "=",
              "value" => "'" . $allows[$i] . "'"
            )
          );
        }
        if (!empty(
          $lock_data = $this->database->select(
            "lock", 
            array("id"), 
            $filters, 
            $extensions, 
            true,
            false
          )
        )) {
          $result = true;
          $this->database->delete(
            "lock", 
            array("id" => $lock_data->id), 
            null, 
            true,
            false
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