<?php
namespace Abstracts;

use \Abstracts\Database;
use \Abstracts\Validation;
use \Abstracts\Translation;
use \Abstracts\Utilities;

use Exception;

class Abstracts {

  /* configuration */
  private $id = "1";
  private $public_functions = array();

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
    $identifier = null
  ) {

    $this->config = $config;
    $this->session = $session;
    $this->module = Utilities::sync_module($config, $identifier);
    $this->controls = Utilities::sync_control(
      $this->id, 
      $session, 
      $controls,
      $this->module
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

  function request($function, $parameters) {
    $result = null;
    if ($this->api->authorize($this->id, $function, $this->public_functions)) {
      if ($function == "get") {
        $result = $this->$function(
          (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
          (isset($parameters["get"]["activate"]) ? $parameters["get"]["activate"] : null)
        );
      } else if ($function == "list") {
        $result = $this->$function(
          (isset($parameters["get"]["start"]) ? $parameters["get"]["start"] : null), 
          (isset($parameters["get"]["limit"]) ? $parameters["get"]["limit"] : null), 
          (isset($parameters["get"]["sort_by"]) ? $parameters["get"]["sort_by"] : null), 
          (isset($parameters["get"]["sort_direction"]) ? $parameters["get"]["sort_direction"] : null), 
          (isset($parameters["get"]["activate"]) ? $parameters["get"]["activate"] : null), 
          (isset($parameters["post"]["filters"]) ? $parameters["post"]["filters"] : null), 
          (isset($parameters["post"]["extensions"]) ? $parameters["post"]["extensions"] : null)
        );
      } else if ($function == "count") {
        $result = $this->$function(
          (isset($parameters["get"]["start"]) ? $parameters["get"]["start"] : null), 
          (isset($parameters["get"]["limit"]) ? $parameters["get"]["limit"] : null), 
          (isset($parameters["get"]["activate"]) ? $parameters["get"]["activate"] : null), 
          (isset($parameters["post"]["filters"]) ? $parameters["post"]["filters"] : null), 
          (isset($parameters["post"]["extensions"]) ? $parameters["post"]["extensions"] : null)
        );
      } else if ($function == "create") {
        $result = $this->$function($parameters["post"]);
      } else if ($function == "update") {
        $result = $this->$function(
          (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
          (isset($parameters["put"]["parameters"]) ? $parameters["put"]["parameters"] : null)
        );
      } else if ($function == "delete") {
        $result = $this->$function(
          (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null)
        );
      } else if ($function == "patch") {
        $result = $this->$function(
          (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
          (isset($parameters["patch"]) ? $parameters["patch"] : null)
        );
      } else {
        throw new Exception($this->translation->translate("Function not supported"), 421);
      }
    } else {
      throw new Exception($this->translation->translate("Permission denied"), 403);
    }
    return $result;
  }

  function get($id, $activate = null) {
    if ($this->validation->require($id, "ID")) {
      $filters = array("id" => $id);
      if ($activate) {
        $filters["activate"] = "1";
      }
      $data = $this->database->select(
        "abstracts", 
        "*", 
        array("id" => $id), 
        null, 
        true
      );
      if (!empty($data)) {
        return $this->callback(__METHOD__, func_get_args(), $this->format($data));
      } else {
        throw new Exception($this->translation->translate("Not found"), 404);
        return null;
      }
    } else {
      return null;
    }
  }

  function format($data) {

    if (!empty($data)) {

      $reference_data = $this->database->select_multiple(
        "reference", 
        "*", 
        array("module" => $data->id), 
        null, 
        null, 
        null, 
        null, 
        null, 
        true
      );
      if (!empty($reference_data)) {
        for ($i = 0; $i < count($reference_data); $i++) {
          $reference_multiple_data = $this->database->select_multiple(
            "reference", 
            "*", 
            array("reference" => $reference_data[$i]->id), 
            null, 
            null, 
            null, 
            null, 
            null, 
            true
          );
          $reference_data[$i]->multiples = $reference_multiple_data;
        }
      }
      $data->references = $reference_data;

    }

		return $data;

  }

  function callback($function, $arguments, $result) {
    $names = explode("::", $function);
    $classes = explode("\\", $names[0]);
    $namespace = "\\" . $classes[0] . "\\" . "Callback" . "\\" . $classes[1];
    if (class_exists($namespace)) {
      if (method_exists($namespace, $names[1])) {
        $callback = new $namespace($this->config, $this->session, $this->controls);
        try {
          $function_name = $names[1];
          return $callback->$function_name($arguments, $result);
        } catch(Exception $e) {
          throw new Exception($e->getMessage(), $e->getCode());
          return false;
        }
      } else {
        return $result;
      }
    } else {
      return $result;
    }
  }

}