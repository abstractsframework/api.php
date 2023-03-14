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

class Module {

  /* configuration */
  public $id = "5";
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
      } else if ($function == "patch") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters) ? $parameters : null)
        );
      } else if ($function == "delete") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null)
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
        "module", 
        "*", 
        $filters, 
        null, 
        $this->controls["view"]
      );
      if (!empty($data)) {
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "low",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data, $return_references),
          $this->session,
          $this->controls,
          $this->id
        );
      } else {
        return null;
      }

    } else {
      return false;
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
        "module", 
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
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "low",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          null,
          null
        );
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($list, $return_references),
          $this->session,
          $this->controls,
          $this->id
        );
      } else {
        return array();
      }
    } else {
      return false;
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
          "module", 
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
      return false;
    }
  }

  function create($parameters, $user_id = 0) {
      
    /* initialize: parameters */
    $parameters = $this->inform($parameters, false, $user_id);

    if ($this->validate($parameters)) {

      $data = $this->database->insert(
        "module", 
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
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data),
          $this->session,
          $this->controls,
          $this->id
        );
      } else {
        return $data;
      }

    } else {
      return false;
    }

  }

  function update($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters, true);

    if (
      $this->validation->require($id, "ID")
      && $this->validate($parameters, $id)
    ) {
      $data = $this->database->update(
        "module", 
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
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data),
          $this->session,
          $this->controls,
          $this->id
        );
      } else {
        return $data;
      }
    } else {
      return false;
    }

  }

  function patch($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters, true);
    
    if (
      $this->validation->require($id, "ID")
      && $this->validate($parameters, $id, true)
    ) {
      $data = $this->database->update(
        "module", 
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
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data),
          $this->session,
          $this->controls,
          $this->id
        );
      } else {
        return $data;
      }
    } else {
      return false;
    }

  }

  function delete($id) {
    if ($this->validation->require($id, "ID")) {
      $data = $this->database->delete(
        "module", 
        array("id" => $id), 
        null, 
        $this->controls["delete"]
      );
      if (!empty($data)) {
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
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data),
          $this->session,
          $this->controls,
          $this->id
        );
      } else {
        return $data;
      }
    } else {
      return false;
    }
  }

  function inform($parameters, $update = false, $user_id = 0) {
    if (!empty($parameters)) {
      if (isset($parameters["default_controls"]) && !empty($parameters["default_controls"])) {
        if (is_array($parameters["default_controls"])) {
          $parameters["default_controls"] = implode(",", $parameters["default_controls"]);
        }
      }
      if (isset($parameters["table_columns"]) && !empty($parameters["table_columns"])) {
        if (is_array($parameters["table_columns"])) {
          $parameters["table_columns"] = serialize($parameters["table_columns"]);
        }
      }
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

  function format($data, $return_references = false) {

    /* function: create referers before format (better performance for list) */
    $refer = function ($return_references = false, $abstracts_override = null) {

      $data = array();
    
      if (!empty($return_references)) {
        if ($return_references === true || (is_array($return_references) && in_array("user_id", $return_references))) {
          $data["user_id"] = new User($this->session, Utilities::override_controls(true, true, true, true));
        }
        if ($return_references === true || (is_array($return_references) && in_array("page_id", $return_references))) {
          $data["page_id"] = new Page($this->session, Utilities::override_controls(true, true, true, true));
        }
      }
  
      return $data;

    };

    /* function: format single data */
    $format = function ($data, $return_references = false, $referers = null) {
      if (!empty($data)) {
  
        if ($data->active === "1") {
          $data->active = true;
        } else if ($data->active === "0" || empty($data->active)) {
          $data->active = false;
        }
  
        if (is_array($referers) && !empty($referers)) {
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
          if ($return_references === true || (is_array($return_references) && in_array("page_id", $return_references))) {
            $data->page_id_reference = $this->format(
              $this->database->get_reference(
                $data->page_id, 
                "page", 
                "id"
              ),
              true
            );
          }
        }
  
        $data->default_controls = explode(",", $data->default_controls);
  
      }
      return $data;
    };

    /* create referers */
    $referers = $refer($return_references);
    if (!is_array($data)) {
      /* format single data */
      $data = $format($data, $return_references, $referers);
    } else {
      /* format array data */
      $data = array_map(
        function($value, $return_references, $referers, $format) { 
          return $format($value, $return_references, $referers); 
        }, 
        $data, 
        array_fill(0, count($data), $return_references), 
        array_fill(0, count($data), $referers), 
        array_fill(0, count($data), $format)
      );
    }
		
    return Utilities::callback(
      __METHOD__, 
      func_get_args(), 
      $data,
      $this->session,
      $this->controls,
      $this->id
    );

  }

  function validate($parameters, $target_id = null, $patch = false) {
    $result = false;
    if (!empty($parameters)) {
      if (
        $this->validation->set($parameters, "name", $patch)
        && $this->validation->set($parameters, "link", $patch)
        && $this->validation->set($parameters, "description", $patch)
        && $this->validation->set($parameters, "icon", $patch)
        && $this->validation->set($parameters, "category", $patch)
        && $this->validation->set($parameters, "subject", $patch)
        && $this->validation->set($parameters, "subject_icon", $patch)
        && $this->validation->set($parameters, "key", $patch)
        && $this->validation->set($parameters, "database_table", $patch)
        && $this->validation->set($parameters, "service", $patch)
        && $this->validation->set($parameters, "page_template", $patch)
        && $this->validation->set($parameters, "page_template_settings", $patch)
        && $this->validation->set($parameters, "page_parent_link_key", $patch)
        && $this->validation->set($parameters, "individual_page_parent_link", $patch)
        && $this->validation->set($parameters, "default_controls", $patch)
        && $this->validation->set($parameters, "table_columns", $patch)
        && $this->validation->set($parameters, "order", $patch)
        && $this->validation->set($parameters, "active", $patch)
        && $this->validation->set($parameters, "page_id", $patch)
      ) {
        if (
          $this->validation->require(isset($parameters["name"]) ? $parameters["name"] : null, "Name", $patch)
          && $this->validation->require(isset($parameters["link"]) ? $parameters["link"] : null, "Link", $patch)
          && $this->validation->require(isset($parameters["key"]) ? $parameters["key"] : null, "Key", $patch)
          && $this->validation->require(isset($parameters["database_table"]) ? $parameters["database_table"] : null, "Database Table", $patch)
          && $this->validation->unique(isset($parameters["database_table"]) ? $parameters["database_table"] : null, "Database Table", "database_table", "module", $target_id)
          && $this->validation->require(isset($parameters["service"]) ? $parameters["service"] : null, "Service Endpoint", $patch)
        ) {
          $result = true;
        }
      }
    } else {
      throw new Exception($this->translation->translate("Bad request"), 400);
    }
    return $result;
  }

}