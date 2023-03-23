<?php
namespace Abstracts;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use Exception;

class Hash {

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

  function request($function, $parameters) {
    $result = null;
    if ($this->api->authorize($this->id, $function, $this->public_functions)) {
      if ($function == "get") {
        $result = $this->$function(
          (
            isset($parameters["id"]) ? $parameters["id"] 
            : (isset($parameters["hash"]) ? $parameters["hash"] : null)
          ),
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
          (isset($parameters["extensions"]) ? $parameters["extensions"] : null),(isset($parameters["key"]) ? $parameters["key"] : null),
          (isset($parameters["value"]) ? $parameters["value"] : null),
          (isset($parameters["return_references"]) ? $parameters["return_references"] : false),
          (isset($parameters["count_total"]) ? $parameters["count_total"] : false)
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
          (
            isset($parameters["id"]) ? $parameters["id"] 
            : (isset($parameters["hash"]) ? $parameters["hash"] : null)
          )
        );
      } else if ($function == "options") {
        $result = $this->$function(
          (isset($parameters["key"]) ? $parameters["key"] : null),
          (isset($parameters["start"]) ? $parameters["start"] : null), 
          (isset($parameters["limit"]) ? $parameters["limit"] : null), 
          (isset($parameters["sort_by"]) ? $parameters["sort_by"] : null), 
          (isset($parameters["sort_direction"]) ? $parameters["sort_direction"] : null), 
          (isset($parameters["active"]) ? $parameters["active"] : null), 
          (isset($parameters["filters"]) ? $parameters["filters"] : null), 
          (isset($parameters["extensions"]) ? $parameters["extensions"] : null),
          (isset($parameters["return_references"]) ? $parameters["return_references"] : false)
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
      
      $filters = array();
      if (isset($active)) {
        $filters["active"] = $active;
      }
      $extensions = array(
        array(
          "conjunction" => "",
          "key" => "id",
          "operator" => "=",
          "value" => "'" . $id . "'",
        ),
        array(
          "conjunction" => "OR",
          "key" => "hash",
          "operator" => "=",
          "value" => "'" . $id . "'",
        )
      );
      $data = $this->database->select(
        "hash", 
        "*", 
        $filters, 
        $extensions, 
        $this->controls["view"]
      );
      if (!empty($data)) {
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
        "hash", 
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
          "hash", 
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
        "hash", 
        $parameters, 
        $this->controls["create"]
      );
      if (!empty($data)) {
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
        "hash", 
        $parameters, 
        array("id" => $id), 
        null, 
        $this->controls["update"]
      );
      if (!empty($data)) {
        $data = $data[0];
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
        "hash", 
        $parameters, 
        array("id" => $id), 
        null, 
        $this->controls["update"]
      );
      if (!empty($data)) {
        $data = $data[0];
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
      $extensions = array(
        array(
          "conjunction" => "",
          "key" => "id",
          "operator" => "=",
          "value" => "'" . $id . "'",
        ),
        array(
          "conjunction" => "OR",
          "key" => "hash",
          "operator" => "=",
          "value" => "'" . $id . "'",
        )
      );
      $data = $this->database->delete(
        "hash", 
        null, 
        $extensions, 
        $this->controls["delete"]
      );
      if (!empty($data)) {
        $data = $data[0];
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
      if (empty($update)) {
        if (isset($parameters["id"])) {
          $parameters["id"] = $parameters["id"];
        } else {
          $parameters["id"] = null;
        }
        $parameters["active"] = (isset($parameters["active"]) ? $parameters["active"] : true);
        $parameters["user_id"] = (!empty($user_id) ? $user_id : (!empty($this->session) ? $this->session->id : 0));
        $parameters["create_at"] = gmdate("Y-m-d H:i:s");
        if (!isset($parameters["hash"]) || empty($parameters["hash"])) {
          $seed = str_split(
            "abcdefghijklmnopqrstuvwxyz"
            . "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
            . "0123456789"
          );
          shuffle($seed);
          $hash = "";
          foreach (array_rand($seed, 13) as $k) $hash .= $seed[$k];
          $parameters["hash"] = $hash;
        }
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
        if (Utilities::in_references("user_id", $return_references)) {
          $data["user_id"] = new User($this->session, Utilities::override_controls(true, true, true, true));
        }
      }
  
      return $data;

    };

    /* function: format single data */
    $format = function ($data, $return_references = false, $referers = null) {
      if (!empty($data)) {
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
    if (!empty($parameters)) {
      return true;
    } else {
      throw new Exception($this->translation->translate("Bad request"), 400);
    }
  }

}