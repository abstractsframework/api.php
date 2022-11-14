<?php
namespace Abstracts;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use \Abstracts\API;
use \Abstracts\Log;

use Exception;

class Page {

  /* configuration */
  public $id = "9";
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
  private $log = null;

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

    /* initialize: services */
    $this->api = new API($this->session, 
      Utilities::override_controls(true, true, true, true)
    );
    $this->log = new Log($this->session, 
      Utilities::override_controls(true, true, true, true)
    );

  }

  function request($function, $parameters) {
    $result = null;
    if ($this->api->authorize($this->id, $function, $this->public_functions)) {
      if ($function == "get") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters["active"]) ? $parameters["active"] : null),
          (isset($parameters["return_references"]) ? $parameters["return_references"] : false)
        );
      } else if ($function == "list") {
        $result = $this->$function(
          (isset($parameters["start"]) ? $parameters["start"] : null), 
          (isset($parameters["limit"]) ? $parameters["limit"] : null), 
          (isset($parameters["sort_by"]) ? $parameters["sort_by"] : null), 
          (isset($parameters["sort_direction"]) ? $parameters["sort_direction"] : null), 
          (isset($parameters["active"]) ? $parameters["active"] : null), 
          (isset($parameters) ? $parameters : null), 
          (isset($parameters["extensions"]) ? $parameters["extensions"] : null), 
          (isset($parameters["return_references"]) ? $parameters["return_references"] : false)
        );
      } else if ($function == "count") {
        $result = $this->$function(
          (isset($parameters["start"]) ? $parameters["start"] : null), 
          (isset($parameters["limit"]) ? $parameters["limit"] : null), 
          (isset($parameters["active"]) ? $parameters["active"] : null), 
          (isset($parameters) ? $parameters : null), 
          (isset($parameters["extensions"]) ? $parameters["extensions"] : null)
        );
      } else if ($function == "create") {
        $result = $this->$function($parameters);
      } else if ($function == "update") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters) ? $parameters : null)
        );
      } else if ($function == "delete") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null)
        );
      } else if ($function == "patch") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters) ? $parameters : null)
        );
      } else if ($function == "upload") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          $_FILES
        );
      } else if ($function == "remove") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters) ? $parameters : null)
        );
      } else if ($function == "data") {
        $result = $this->$function(
          (isset($parameters["key"]) ? $parameters["key"] : null),
          (isset($parameters["value"]) ? $parameters["value"] : null)
        );
      } else {
        throw new Exception($this->translation->translate("Function not supported"), 421);
      }
    }
    return $result;
  }

  function get($id, $active = null, $return_references = false) {
    if ($this->validation->require($id, "ID")) {

      $active = Initialize::active($active);
      $return_references = Initialize::return_references($return_references);

      $filters = array("id" => $id);
      if (isset($active)) {
        $filters["active"] = $active;
      }
      $data = $this->database->select(
        "page", 
        "*", 
        $filters, 
        null, 
        $this->controls["view"]
      );
      if (!empty($data)) {
        $referers = $this->refer($return_references);
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "low",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        return $this->callback(__METHOD__, func_get_args(), $this->format($data, $return_references, $referers));
      } else {
        return null;
      }

    } else {
      return null;
    }
  }

  function list(
    $start = null, 
    $limit = null, 
    $sort_by = "id", 
    $sort_direction = "desc", 
    $active = null, 
    $filters = array(), 
    $extensions = array(),
    $return_references = false
  ) {

    $start = Initialize::start($start);
    $limit = Initialize::limit($limit);
    $sort_by = Initialize::sort_by($sort_by);
    $sort_direction = Initialize::sort_direction($sort_direction);
    $active = Initialize::active($active);
    $filters = Initialize::filters($filters);
    $extensions = Initialize::extensions($extensions);
    $return_references = Initialize::return_references($return_references);
    
    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      if (isset($active)) {
        $filters["active"] = $active;
      }
      $list = $this->database->select_multiple(
        "page", 
        "*", 
        $filters, 
        $extensions, 
        $start, 
        $limit, 
        $sort_by, 
        $sort_direction, 
        $this->controls["view"]
      );
      if (!empty($list)) {
        $referers = $this->refer($return_references);
        $data = array();
        foreach ($list as $value) {
          array_push($data, $this->format($value, $return_references, $referers));
        }
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "low",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          null,
          null
        );
        return $this->callback(__METHOD__, func_get_args(), $data);
      } else {
        return array();
      }
    } else {
      return null;
    }
  }

  function count(
    $start = null, 
    $limit = null, 
    $active = null, 
    $filters = array(), 
    $extensions = array()
  ) {

    $start = Initialize::start($start);
    $limit = Initialize::limit($limit);
    $active = Initialize::active($active);
    $filters = Initialize::filters($filters);
    $extensions = Initialize::extensions($extensions);

    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      if (isset($active)) {
        $filters["active"] = $active;
      }
      if (
        $data = $this->database->count(
          "page", 
          $filters, 
          $extensions, 
          $start, 
          $limit, 
          $this->controls["view"]
        )
      ) {
        return $data;
      } else {
        return 0;
      }
    } else {
      return null;
    }
  }

  function create($parameters, $user_id = 0) {
      
    /* initialize: parameters */
    $parameters = $this->inform($parameters, false, $user_id);

    if ($this->validate($parameters)) {

      $data = $this->database->insert(
        "page", 
        $parameters, 
        $this->controls["create"]
      );
      if (!empty($data)) {
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "normal",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data)
        );
      } else {
        return $data;
      }

    } else {
      return null;
    }

  }

  function update($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters, true);

    if ($this->validate($parameters, $id)) {
      $data = $this->database->update(
        "page", 
        $parameters, 
        array("id" => $id), 
        null, 
        $this->controls["update"]
      );
      if (!empty($data)) {
        $data = $data[0];
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "normal",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data)
        );
      } else {
        return $data;
      }
    } else {
      return null;
    }

  }

  function patch($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters, true);
    
    if ($this->validation->require($id, "ID")) {

      if ($this->validate($parameters, $id, true)) {
        $data = $this->database->update(
          "page", 
          $parameters, 
          array("id" => $id), 
          null, 
          $this->controls["update"]
        );
        if (!empty($data)) {
          $data = $data[0];
          $this->log->log(
            __FUNCTION__,
            __METHOD__,
            "normal",
            func_get_args(),
            (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
            "id",
            $data->id
          );
          return $this->callback(
            __METHOD__, 
            func_get_args(), 
            $this->format($data)
          );
        } else {
          return $data;
        }
      } else {
        return null;
      }

    } else {
      return null;
    }

  }

  function delete($id) {
    if ($this->validation->require($id, "ID")) {
      if (
        $data = $this->database->delete(
          "page", 
          array("id" => $id), 
          null, 
          $this->controls["delete"]
        )
      ) {
        $data = $data[0];
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "risk",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data)
        );
      } else {
        return null;
      }
    } else {
      return null;
    }
  }

  function inform($parameters, $update = false, $user_id = 0) {
    if (!empty($parameters)) {
      if (!$update) {
        if (isset($parameters["id"])) {
          $parameters["id"] = $parameters["id"];
        } else {
          $parameters["id"] = null;
        }
        $parameters["active"] = (isset($parameters["active"]) ? $parameters["active"] : true);
        $parameters["user_id"] = (!empty($user_id) ? $user_id : (!empty($this->session) ? $this->session->id : 0));
        $parameters["create_at"] = gmdate("Y-m-d H:i:s");
      } else {
        unset($parameters["id"]);
        unset($parameters["create_at"]);
      }
    }
    return $parameters;
  }

  function refer($return_references = false, $abstracts_override = null) {

    $data = array();
    
    if (!empty($return_references)) {
      if ($return_references === true || (is_array($return_references) && in_array("module_id", $return_references))) {
        $data["module_id"] = new Module($this->session, Utilities::override_controls(true, true, true, true));
      }
      if ($return_references === true || (is_array($return_references) && in_array("user_id", $return_references))) {
        $data["user_id"] = new User($this->session, Utilities::override_controls(true, true, true, true));
      }
      if ($return_references === true || (is_array($return_references) && in_array("language_id", $return_references))) {
        $data["language_id"] = new Page($this->session, Utilities::override_controls(true, true, true, true));
      }
    }

    return $this->callback(__METHOD__, func_get_args(), $data);

  }

  function format($data, $return_references = false, $referers = null) {
    if (!empty($data)) {

      if ($data->active === "1") {
        $data->active = true;
      } else if ($data->active === "0" || empty($data->active)) {
        $data->active = false;
      }

      if ($return_references === true || (is_array($return_references) && in_array("translate", $return_references))) {
        $data->translate_reference = $this->format(
          $this->database->get_reference(
            $data->translate, 
            "page", 
            "id"
          ),
          true
        );
      }

      if (is_array($referers) && !empty($referers)) {
        if ($return_references === true || (is_array($return_references) && in_array("module_id", $return_references))) {
          if (isset($referers["module_id"])) {
            $data->module_id_reference = $referers["module_id"]->format(
              $this->database->get_reference(
                $data->module_id, 
                "module", 
                "id"
              ),
              true
            );
          }
        }
        if ($return_references === true || (is_array($return_references) && in_array("user_id", $return_references))) {
          if (isset($referers["user_id"])) {
            $data->user_id_reference = $referers["user_id"]->format(
              $this->database->get_reference(
                $data->user_id,
                "user",
                "id"
              )
            );
          }
        }
        if ($return_references === true || (is_array($return_references) && in_array("language_id", $return_references))) {
          if (isset($referers["language_id"])) {
            $data->language_id_reference = $referers["language_id"]->format(
              $this->database->get_reference(
                $data->language_id, 
                "language", 
                "id"
              ),
              true
            );
          }
        }
      }

    }
		return $data;
  }

  function validate($parameters, $target_id = null, $patch = false) {
    if (!empty($parameters)) {
      return true;
    } else {
      throw new Exception($this->translation->translate("Bad request"), 400);
    }
  }

  function callback($function, $arguments, $result) {
    $names = explode("::", $function);
    $classes = explode("\\", $names[0]);
    $namespace = "\\" . $classes[0] . "\\" . "Callback" . "\\" . $classes[1];
    if (class_exists($namespace)) {
      if (method_exists($namespace, $names[1])) {
        $callback = new $namespace($this->session, $this->controls, $this->id);
        try {
          $function_name = $names[1];
          return $callback->$function_name($arguments, $result);
        } catch(Exception $e) {
          throw new Exception($e->getMessage(), $e->getCode());
        }
      } else {
        return $result;
      }
    } else {
      return $result;
    }
  }

}