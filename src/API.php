<?php
namespace Abstracts;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Security;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use \Abstracts\Lock;

use Exception;

class API {

  /* configuration */
  public $id = "12";
  public $public_functions = array(
    "authorize"
  );
  public $module = null;

  /* core */
  private $config = null;
  private $session = null;
  private $controls = null;

  /* helpers */
  private $database = null;
  private $security = null;
  private $validation = null;
  private $translation = null;

  /* services */
  private $lock = null;

  function __construct(
    $session = null,
    $controls = null,
    $identifier = null
  ) {

    /* initialize: core */
    $initialize = new Initialize($session, $controls, $this->id);
    $this->config = $initialize->config;
    $this->session = $initialize->session;
    $this->controls = $initialize->controls;
    $this->module = $initialize->module;
    
    /* initialize: helpers */
    $this->database = new Database($this->session, $this->controls);
    $this->security = new Security();
    $this->validation = new Validation();
    $this->translation = new Translation();

    /* initialize: services */
    $this->lock = new Lock($this->session, 
      Utilities::override_controls(true, true, true, true)
    );

  }

  function request($function, $parameters) {
    $result = null;
    if ($this->authorize($this->id, $function, $this->public_functions)) {
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
      } else if ($function == "delete") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null)
        );
      } else if ($function == "patch") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters) ? $parameters : null)
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
        if (isset($_POST["t"]) && !empty($_POST["t"])) {
          $key = $extract($_POST["t"])->key;
          $secret = $extract($_POST["t"])->secret;
          $nonce = $extract($_POST["t"])->nonce;
        } else if (isset($_REQUEST["t"]) && !empty($_REQUEST["t"])) {
          $key = $extract($_REQUEST["t"])->key;
          $secret = $extract($_REQUEST["t"])->secret;
          $nonce = $extract($_REQUEST["t"])->nonce;
        } else {
          foreach (Utilities::get_all_headers() as $name => $value) {
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
          try {
            if (!empty(
              $api_data = $this->database->select(
                "api", 
                "*", 
                array("key" => $key), 
                null, 
                true,
                false
              )
            )) {
              $scope = array();
              if ($api_data->scope) {
                $scope = unserialize($api_data->scope);
                if (empty($scope)) {
                  $scope = array();
                }
              }
              if ($api_data->type == "public" || $api_data->secret == $secret) {
                if (in_array($module_id, $scope)) {
                  $result = true;
                }
              }
            }
          } catch(Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
          }
        }

      }

    }
    
    if ($result) {
      return $result;
    } else {
      throw new Exception($this->translation->translate("Unauthorized API"), 401);
    }

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
        "api", 
        "*", 
        array("id" => $id), 
        null, 
        $this->controls["view"]
      );
      if (!empty($data)) {
        return $this->callback(__METHOD__, func_get_args(), $this->format($data, $return_references));
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
        "api", 
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
        $data = array();
        foreach ($list as $value) {
          array_push($data, $this->format($value, $return_references));
        }
        return $this->callback(__METHOD__, func_get_args(), $data);
      } else {
        return array();
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
        "api", 
        $parameters, 
        $this->controls["create"]
      );
      if (!empty($data)) {
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
          "api", 
          $filters, 
          $extensions, 
          $start, 
          $limit, 
          $this->controls["view"]
        )
      ) {
        return $data;
      } else {
        return null;
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
        "api", 
        $parameters, 
        array("id" => $id), 
        null, 
        $this->controls["update"]
      );
      if (!empty($data)) {
        $data = $data[0];
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
          "api", 
          $parameters, 
          array("id" => $id), 
          null, 
          $this->controls["update"]
        );
        if (!empty($data)) {
          $data = $data[0];
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
          "api", 
          array("id" => $id), 
          null, 
          $this->controls["delete"]
        )
      ) {
        $data = $data[0];
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

  function options(
    $key, 
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
    $filters = is_array($filters) ? $filters : array();
    $extensions = Initialize::extensions($extensions);
    $return_references = Initialize::return_references($return_references);

    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      $filters["active"] = true;
      if (isset($active)) {
        $filters["active"] = $active;
      }
      $list = $this->database->select_multiple(
        $key, 
        array("id", "name"), 
        $filters, 
        $extensions, 
        $start, 
        $limit, 
        $sort_by, 
        $sort_direction, 
        $this->controls["view"]
      );
      if (!empty($list)) {
        $data = array();
        foreach ($list as $value) {
          array_push($data, $value);
        }
        return $this->callback(__METHOD__, func_get_args(), $data);
      } else {
        return array();
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
      $parameters["scope"] = serialize($parameters["scope"]);
    }
    return $parameters;
  }

  function format($data, $return_references = false) {
    if (!empty($data)) {
      
      if ($data->active === "1") {
        $data->active = true;
      } else if ($data->active === "0" || empty($data->active)) {
        $data->active = false;
      }

      if ($return_references === true || (is_array($return_references) && in_array("user_id", $return_references))) {
        $data->user_id_reference = $this->format(
          $this->database->get_reference(
            $data->user_id, 
            "user", 
            "id"
          ),
          true
        );
      }

      $data->scope = unserialize($data->scope);
      if (empty($data->scope)) {
        $data->scope = array();
      }
      if ($return_references === true || (is_array($return_references) && in_array("scope", $return_references))) {

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
          array("default_controls"), 
          array("id" => $data->module_id), 
          null, 
          true,
          false
        );
      }
      if (
        !empty($module_data) 
        && isset($module_data->default_controls) 
        && !empty($module_data->default_controls)
      ) {
        $behaviors = explode(",", $module_data->default_controls);
        if (!empty($behaviors)) {
          foreach ($behaviors as $behavior) {
            $controls[$behavior] = true;
          }
        }
      }

      if (isset($data->behaviors) && !empty($data->behaviors)) {
        $behaviors = explode(",", $data->behaviors);
        if (!empty($behaviors)) {
          foreach ($behaviors as $behavior) {
            if ($controls[$behavior] !== true) {
              if (empty($data->rules)) {
                $controls[$behavior] = true;
              } else {
                $rules = explode(",", $data->rules);
                for ($i = 0; $i < count($rules); $i++) {
                  $operator = "";
                  foreach (Database::$comparisons as $comparison) {
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