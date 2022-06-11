<?php
namespace Abstracts;

use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use Exception;

class Control {

  /* configuration */
  private $id = "14";
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

  function format($data, $prevent_data = false) {
    if (!empty($data)) {
    }
  }

  function arrange($data, $override_module = null) {

    if (!empty($data)) {

      $controls = array(
        "view" => false,
        "create" => false,
        "update" => false,
        "delete" => false
      );

      $module_data = $override_module;
      if (empty($module_data)) {
        $module_data = $this->database->select(
          "module", 
          "default_control", 
          array("id" => $data->module_id), 
          null, 
          true
        );
      }
      if (
        !empty($module_data) 
        && isset($module_data->default_control) 
        && !empty($module_data->default_control)
      ) {
        $behaviors = explode(",", $data->behaviors);
        if (count($behaviors)) {
          foreach($behaviors as $behavior) {
            $controls[$behavior] = true;
          }
        }
      }

      if (isset($data->behaviors) && !empty($data->behaviors)) {
        $behaviors = explode(",", $data->behaviors);
        if (count($behaviors)) {
          foreach($behaviors as $behavior) {
            if ($controls[$behavior] !== true) {
              if (empty($data->rules)) {
                $controls[$behavior] = true;
              } else {
                $rules = explode(",", $data->rules);
                for ($i = 0; $i < count($rules); $i++) {
                  $operator = "";
                  foreach(Database::$comparisons as $comparison) {
                    if (strpos(strtolower($rules[$i]), strtolower($comparison)) !== false) {
                      $operator = $comparison;
                    }
                  }
                  if (!empty($operator)) {
                    $rule_parts = explode($operator, $rules[$i]);
                    if (isset($rule_parts[1]) && $rule_parts[1] == "<session>") {
                      if (isset($this->session) && isset($this->session->id)) {
                        $rules[$i] = $rule_parts[0] . $operator . str_replace("<session>", $this->session->id, $rule_parts[1]);
                      }
                    }
                  }
                }
                $controls[$behavior] = $rules;
              }
            }
          }
        }
      }

      return $controls;

    } else {
      return $data;
    }

  }

}