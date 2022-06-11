<?php
namespace Abstracts;

use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;
use \Abstracts\Helpers\Encryption;

use \Abstracts\API;
use \Abstracts\Device;

use Exception;

class User {

  /* configuration */
  private $id = "6";
  private $public_functions = array(
		"verify"
	);
  private $allowed_keys = array(
    "id",
    "username",
    "password",
    "email",
    "name",
    "last_name",
    "nick_name",
    "image",
    "phone",
    "passcode",
    "email_verified",
    "phone_verified",
    "ndid_verified",
    "face_verified",
    "date_created",
    "activate",
    "user_id"
  );

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
  private $encryption = null;

  /* services */
  private $api = null;
  private $device = null;
  private $control = null;

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
    $this->encryption = new Encryption();

    /* initialize: services */
    $this->api = new API($this->config, $this->session, 
      Utilities::override_controls(true, true, true, true)
    );
    $this->device = new Device($this->config, $this->session, 
      Utilities::override_controls(true, true, true, true)
    );
    $this->control = new Control($this->config, $this->session, 
      Utilities::override_controls(true, true, true, true)
    );

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
    if (!empty($this->module) && !count($this->allowed_keys)) {
      $columns = $this->database->columns($this->module->key);
      if (count($columns)) {
        foreach($columns as $column) {
          $this->allowed_keys = array_merge($this->allowed_keys, $column["COLUMN_NAME"]);
        }
      }
    }
  }

  function request($function, $parameters) {
    $result = null;
    if ($this->api->authorize($this->id, $function, $this->public_functions)) {
      if ($function == "login") {
        $result = $this->$function(
          (isset($parameters["post"]["username"]) ? $parameters["post"]["username"] : null),
          (isset($parameters["post"]["password"]) ? $parameters["post"]["password"] : null),
          (isset($parameters["get"]["remember"]) ? $parameters["get"]["remember"] : 
            (isset($parameters["post"]["remember"]) ? $parameters["post"]["remember"] : null)
          ),
          (isset($parameters["post"]["device"]) ? $parameters["post"]["device"] : null)
        );
      } else if ($function == "authenticate") {
        $result = $this->$function(
          (isset($parameters["get"]["authorization"]) ? $parameters["get"]["authorization"] : 
            (isset($parameters["post"]["authorization"]) ? $parameters["post"]["authorization"] : null)
          )
        );
      } else if ($function == "logout") {
        $result = $this->$function(
          (isset($parameters["get"]["authorization"]) ? $parameters["get"]["authorization"] : 
            (isset($parameters["post"]["authorization"]) ? $parameters["post"]["authorization"] : null)
          )
        );
      } else if ($function == "signup") {
        $result = $this->$function($parameters["post"]);
      } else if ($function == "get") {
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
          (isset($parameters["post"]["extensions"]) ? $parameters["post"]["extensions"] : null), 
          (isset($parameters["get"]["key"]) ? $parameters["get"]["key"] : 
            (isset($parameters["post"]["key"]) ? $parameters["post"]["key"] : null)
          ),
          (isset($parameters["get"]["value"]) ? $parameters["get"]["value"] : 
            (isset($parameters["post"]["value"]) ? $parameters["post"]["value"] : null)
          )
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
      } else if ($function == "upload") {
        $result = $this->$function(
          (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
          $_FILES
        );
      } else if ($function == "remove_file") {
        $result = $this->$function(
          (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
          (isset($parameters["patch"]) ? $parameters["patch"] : null)
        );
      } else if ($function == "data") {
        $result = $this->$function(
          (isset($parameters["get"]["key"]) ? $parameters["get"]["key"] : null),
          (isset($parameters["get"]["value"]) ? $parameters["get"]["value"] : null)
        );
      } else {
        throw new Exception($this->translation->translate("Function not supported"), 421);
      }
    }
    return $result;
  }

  function login($username, $password, $remember = false, $device = null) {

    if (
      !isset($username) || empty($username)
      || !isset($password) || empty($password)
    ) {
      foreach(Utilities::get_headers_all() as $key => $value) {
        if (strtolower($key) == "authorization") {
          if (strpos($value, "Basic") === 0) {
            $authorization = base64_decode(str_replace("Basic ", "", $value));
            $authorization_parts = explode(":", $authorization);
            if (isset($authorization_parts[0])) {
              $username = $authorization_parts[0];
            }
            if (isset($authorization_parts[1])) {
              $password = $authorization_parts[1];
            }
          }
        }
      }
    }

    if (
      $this->validation->require($username, "Username")
      && $this->validation->require($password, "Password")
    ) {

      $password_hash = hash("sha256", md5($password . $this->config["password_salt"]));
      $filters = array(
        "activate" => true,
        "password" => $password_hash
      );
      $extensions = array(
        array(
          "conjunction" => "",
          "key" => "`username`",
          "operator" => "=",
          "value" => "'" . $username . "'",
        ),
        array(
          "conjunction" => "OR",
          "key" => "`email`",
          "operator" => "=",
          "value" => "'" . $username . "'",
        )
      );
      $data = $this->database->select(
        "user", 
        "*", 
        $filters, 
        $extensions, 
        true,
        $this->allowed_keys
      );
      if (!empty($data)) {

        $result = function($data, $session_id, $date_recent, $date_expire) {
          if (
            isset($this->config["encrypt_authorization"]) 
            && !empty($this->config["encrypt_authorization"])
          ) {
            
            $values = array(
              "session_id" => $session_id,
              "id" => $data->id,
              "iss" => strtotime($date_recent),
              "exp" => strtotime($date_expire)
            );
  
            try {
              $token = $this->encryption->encode(
                $values, 
                $this->config["encrypt_ssl_private_key"], 
                $this->config["encrypt_authorization"]
              );
              if ($token !== false) {
                $data->token = $token;
                return $this->callback(__METHOD__, func_get_args(), $data);
              } else {
                throw new Exception($this->translation->translate("Invalid authorization"), 403);
                return null;
              }
            } catch(Exception $e) {
              throw new Exception($e->getMessage(), 500);
              return null;
            }
            
          } else {
            $data->token = base64_encode($data->id . "." . $session_id);
            return $this->callback(__METHOD__, func_get_args(), $data);
          }
        };

        $data = $this->format($data);

        $session_id = session_id();
        $date_recent = gmdate("Y-m-d H:i:s");
        $date_expire = gmdate("Y-m-d H:i:s", strtotime($this->config["session_duration"]));
        
        if (isset($remember) && (!empty($remember))) {
          if (!$this->update_device($data->id, $session_id)) {
            $device_user_agent = $this->device->get_device();
            $device_parameters = array(
              "id" => null,
              "name" => "",
              "session" => $session_id,
              "token" => ((isset($device) && isset($device["token"])) ? $device["token"] : ""),
              "platform" => $device_user_agent->platform,
              "browser" => $device_user_agent->browser,
              "os" => $device_user_agent->os->name,
              "os_version" => $device_user_agent->os->version,
              "model" => ((isset($device) && isset($device["model"])) ? $device["model"] : ""),
              "manufacturer" => ((isset($device) && isset($device["manufacturer"])) ? $device["manufacturer"] : ""),
              "uuid" => ((isset($device) && isset($device["uuid"])) ? $device["uuid"] : ""),
              "user_agent" => $_SERVER["HTTP_USER_AGENT"],
              "is_mobile" => ($device_user_agent->is_mobile) ? true : false,
              "is_native" => ($device_user_agent->is_native) ? true : false,
              "is_virtual" => ((isset($device) && isset($device["is_virtual"])) ? true : false),
              "app_name" => ((isset($device) && isset($device["app_name"])) ? $device["app_name"] : ""),
              "app_id" => ((isset($device) && isset($device["app_id"])) ? $device["app_id"] : ""),
              "app_version" => ((isset($device) && isset($device["app_version"])) ? $device["app_version"] : ""),
              "app_build" => ((isset($device) && isset($device["app_build"])) ? $device["app_build"] : ""),
              "ip_recent" => $_SERVER["REMOTE_ADDR"],
              "date_recent" => $date_recent,
              "date_expire" => $date_expire,
              "activate" => true,
              "user_id" => $data->id
            );
            if (
              $this->database->insert(
                "device", 
                $device_parameters, 
                true
              )
            ) {
              return $result($data, $session_id, $date_recent, $date_expire);
            } else {
              return null;
            }
          } else {
            return $result($data, $session_id, $date_recent, $date_expire);
          }
        } else {
          return $result($data, $session_id, $date_recent, $date_expire);
        }

      } else {
        throw new Exception($this->translation->translate("Invalid account"), 401);
        return null;
      }
    } else {
      return null;
    }
  }

  function logout($authorization = null) {

    $result = false;

    $message = $this->translation->translate("Invalid authorization");

    if (empty($authorization)) {
      if (isset($_POST["authorization"]) && !empty($_POST["authorization"])) {
        $authorization = $_POST["authorization"];
      } else if (isset($_GET["authorization"]) && !empty($_GET["authorization"])) {
        $authorization = $_GET["authorization"];
      }
      foreach(Utilities::get_headers_all() as $key => $value) {
        if (strtolower($key) == "authorization") {
          $authorization= $value;
        }
      }
    }

    if (!empty($authorization)) {
      $session_id = null;
      $user_id = null;
      if (strpos($authorization, "Bearer") === 0) {
        if (isset($config["encrypt_authorization"]) && !empty($config["encrypt_authorization"])) {
          $token = str_replace("Bearer ", "", $authorization);
          if (!empty($token)) {
            $decoded = $this->encryption->decode(
              $token, 
              $config["encrypt_ssl_public_key"], 
              $config["encrypt_authorization"]
            );
            if ($decoded !== false) {
              if (
                isset($decoded->session_id)
                && isset($decoded->id)
              ) {
                $user_id = $decoded->id;
                $session_id = $decoded->session_id;
              }
            } else {
              throw new Exception($message, 400);
            }
          }
        } else {
          $token = str_replace("Bearer ", "", $authorization);
          if (!empty($token)) {
            $session_parts = explode(".", base64_decode($token));
            if (count($session_parts) == 2) {
              $user_id = $session_parts[0];
              $session_id = $session_parts[1];
            } else {
              throw new Exception($message, 400);
            }
          } else {
            throw new Exception($message, 400);
          }
        }
      } else if (strpos($authorization, "Basic") === 0) {
        $token = str_replace("Basic ", "", $authorization);
        if (!empty($token)) {
          $session_parts = explode(".", base64_decode($token));
          if (count($session_parts) == 2) {
            $user_id = $session_parts[0];
            $session_id = $session_parts[1];
          } else {
            throw new Exception($message, 400);
          }
        } else {
          throw new Exception($message, 400);
        }
      }
      if (
        isset($user_id) && !empty($user_id)
        && isset($session_id) && !empty($session_id)
      ) {
        $filters = array(
          "user_id" => $user_id,
          "session" => $session_id
        );
        if (
          $this->database->delete(
            "device", 
            $filters, 
            null, 
            true
          )
        ) {
          $result = true;
        }
      } else {
        throw new Exception($message, 400);
      }
    }

    return $result;

  }

  function authenticate($authorization = null, $throw_error = true) {

    $session = null;

    $message = $this->translation->translate("Invalid authorization");

    if (empty($authorization)) {
      if (isset($_POST["authorization"]) && !empty($_POST["authorization"])) {
        $authorization = $_POST["authorization"];
      } else if (isset($_GET["authorization"]) && !empty($_GET["authorization"])) {
        $authorization = $_GET["authorization"];
      }
      foreach(Utilities::get_headers_all() as $key => $value) {
        if (strtolower($key) == "authorization") {
          $authorization= $value;
        }
      }
    }

    if (!empty($authorization)) {
      $session_id = null;
      $user_id = null;
      if (strpos($authorization, "Bearer") === 0) {
        if (isset($config["encrypt_authorization"]) && !empty($config["encrypt_authorization"])) {
          $token = str_replace("Bearer ", "", $authorization);
          if (!empty($token)) {
            $decoded = $this->encryption->decode(
              $token, 
              $config["encrypt_ssl_public_key"], 
              $config["encrypt_authorization"]
            );
            if ($decoded !== false) {
              if (
                isset($decoded->session_id)
                && isset($decoded->id)
              ) {
                $user_id = $decoded->id;
                $session_id = $decoded->session_id;
              }
            } else {
              if ($throw_error) {
                throw new Exception($message, 400);
              }
            }
          } else {
            if ($throw_error) {
              throw new Exception($message, 400);
            }
          }
        } else {
          $token = str_replace("Bearer ", "", $authorization);
          if (!empty($token)) {
            $session_parts = explode(".", base64_decode($token));
            if (count($session_parts) == 2) {
              $user_id = $session_parts[0];
              $session_id = $session_parts[1];
            } else {
              if ($throw_error) {
                throw new Exception($message, 400);
              }
            }
          } else {
            if ($throw_error) {
              throw new Exception($message, 400);
            }
          }
        }
      } else if (strpos($authorization, "Basic") === 0) {
        $token = str_replace("Basic ", "", $authorization);
        $session_parts = explode(".", base64_decode($token));
        if (!empty($token)) {
          if (count($session_parts) == 2) {
            $user_id = $session_parts[0];
            $session_id = $session_parts[1];
          } else {
            if ($throw_error) {
              throw new Exception($message, 400);
            }
          }
        } else {
          if ($throw_error) {
            throw new Exception($message, 400);
          }
        }
      }
      if (
        isset($user_id) && !empty($user_id)
        && isset($session_id) && !empty($session_id)
      ) {
        $data = $this->database->select(
          "user", 
          "*", 
          array("id" => $user_id), 
          null, 
          true,
          $this->allowed_keys
        );
        if (!empty($data)) {
          if ($this->update_device($data->id, $session_id)) {
            $data->session_id = $session_id;
            $session = $this->format($data);
          }
        }
      } else {
        if ($throw_error) {
          throw new Exception($message, 400);
        }
      }
    } else {
      if ($throw_error) {
        throw new Exception($message, 400);
      }
    }

    return $session;

  }

  function signup($parameters, $user_id = 0) {
      
    /* initialize: parameters */
    $parameters = $this->inform($parameters);
    $parameters["id"] = (isset($parameters["id"]) ? $parameters["id"] : null);
    $parameters["activate"] = (isset($parameters["activate"]) ? $parameters["activate"] : true);
    $parameters["user_id"] = (!empty($user_id) ? $user_id : (!empty($this->session) ? $this->session->id : 0));
    $parameters["date_created"] = gmdate("Y-m-d H:i:s");
    if (!isset($parameters["username"])) {
      $parameters["username"] = $this->random_username();
    }

    if ($this->validate($parameters)) {

      unset($parameters["confirm_password"]);
      $parameters["password"] = hash("sha256", md5($parameters["password"] . $this->config["password_salt"]));
      $parameters["email_verified"] = 0;
      $parameters["phone_verified"] = 0;
      $parameters["ndid_verified"] = 0;
      $parameters["face_verified"] = 0;

      if (
        $data = $this->database->insert(
          "user", 
          $parameters, 
          true,
          $this->allowed_keys
        )
      ) {
        if (
          $data_update = $this->database->update(
            "user",
            array("user_id" => $data->id),
            array("id" => $data->id),
            null,
            true,
            $this->allowed_keys
          )
        ) {
          $data = $data_update[0];
          if (isset($this->config["signup_default_group"]) && !empty($this->config["signup_default_group"])) {
            $group_data = $this->database->select(
              "group", 
              array("id", "controls"), 
              array("id" => $this->config["signup_default_group"]), 
              null, 
              true
            );
            if (!empty($group_data)) {
              $member_parameters = array(
                "id" => null,
                "user" => $data->id,
                "group_id" => $group_data->id,
                "date_created" => $parameters["date_created"],
                "activate" => true,
                "user_id" => $data->id
              );
              if (
                $this->database->insert(
                  "member", 
                  $member_parameters, 
                  true
                )
              ) {
                $controls = unserialize($group_data->controls);
                if (is_array($controls) && count($controls)) {
                  $controls_parameters = array();
                  foreach($controls as $value) {
                    array_push($controls_parameters, array(
                      "id" => null,
                      "user" => $data->id,
                      "rules" => $value["rules"],
                      "behaviors" => $value["behaviors"],
                      "date_created" => $parameters["date_created"],
                      "activate" => true,
                      "module_id" => $value["module_id"],
                      "group_id" => $group_data->id,
                      "user_id" => $data->id
                    ));
                  }
                  if (
                    $this->database->insert_multiple(
                      "control", 
                      $controls_parameters, 
                      true
                    )
                  ) {
                    return $this->callback(__METHOD__, func_get_args(), $data);
                  } else {
                    $this->database->delete(
                      "user", 
                      array("id" => $data->id), 
                      null, 
                      true,
                      $this->allowed_keys
                    );
                    $this->database->delete(
                      "member", 
                      array("user" => $data->id), 
                      null, 
                      true
                    );
                    return false;
                  }
                } else {
                  return $this->callback(__METHOD__, func_get_args(), $data);
                }
              } else {
                $this->database->delete(
                  "user", 
                  array("id" => $data->id), 
                  null, 
                  true,
                  $this->allowed_keys
                );
                return false;
              }
            } else {
              $this->database->delete(
                "user", 
                array("id" => $data->id), 
                null, 
                true
              );
              return false;
            }
          } else {
            return $this->callback(__METHOD__, func_get_args(), $data);
          }
        } else {
          $this->database->delete(
            "user", 
            array("id" => $data->id), 
            null, 
            true
          );
          return false;
        }
      } else {
        return false;
      }

    } else {
      return false;
    }
  }

  function get($id, $activate = null) {
    if ($this->validation->require($id, "ID")) {

      $activate = !empty($activate) ? $activate : null;

      $filters = array("id" => $id);
      if ($activate) {
        $filters["activate"] = "1";
      }
      $data = $this->database->select(
        "user", 
        "*", 
        $filters, 
        null, 
        true,
        $this->allowed_keys
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

  function list(
    $start = null, 
    $limit = null, 
    $sort_by = "id", 
    $sort_direction = "desc", 
    $activate = null, 
    $filters = array(), 
    $extensions = array(),
    $key = null, 
    $value = null
  ) {

    $start = !empty($start) ? $start : null;
    $limit = !empty($limit) ? $limit : null;
    $sort_by = !empty($sort_by) ? $sort_by : "id";
    $sort_direction = !empty($sort_direction) ? $sort_direction : "desc";
    $activate = !empty($activate) ? $activate : null;
    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    
    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      if (!empty($activate)) {
        array_push($filters, array("activate" => true));
      }
      if (!empty($key) && !empty($value)) {
        $filters[$key] = $value;
      }
      $list = $this->database->select_multiple(
        "user", 
        "*", 
        $filters, 
        $start, 
        $extensions, 
        $limit, 
        $sort_by, 
        $sort_direction, 
        $this->controls["view"],
        $this->allowed_keys
      );
      if (!empty($list)) {
        $data = array();
        foreach($list as $value) {
          array_push($data, $this->format($value));
        }
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
    $activate = null, 
    $filters = array(), 
    $extensions = array()
  ) {

    $start = !empty($start) ? $start : null;
    $limit = !empty($limit) ? $limit : null;
    $activate = !empty($activate) ? $activate : null;
    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();

    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      if (!empty($activate)) {
        array_push($filters, array("activate" => true));
      }
      if (!empty($key) && !empty($value)) {
        $filters[$key] = $value;
      }
      if (
        $data = $this->database->count(
          "user", 
          "*", 
          $filters, 
          $start, 
          $extensions, 
          $limit, 
          $this->controls["view"],
          $this->allowed_keys
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

  function create($parameters, $user_id = 0) {
      
    /* initialize: parameters */
    $parameters = $this->inform($parameters);
    $parameters["id"] = (isset($parameters["id"]) ? $parameters["id"] : null);
    $parameters["activate"] = (isset($parameters["activate"]) ? $parameters["activate"] : true);
    $parameters["user_id"] = (!empty($user_id) ? $user_id : (!empty($this->session) ? $this->session->id : 0));
    $parameters["date_created"] = gmdate("Y-m-d H:i:s");

    if ($this->validate($parameters)) {
    
      unset($parameters["confirm_password"]);
      $parameters["password"] = hash("sha256", md5($parameters["password"] . $this->config["password_salt"]));
      $parameters["email_verified"] = 0;
      $parameters["phone_verified"] = 0;
      $parameters["ndid_verified"] = 0;
      $parameters["face_verified"] = 0;

      return $this->callback(
        __METHOD__, 
        func_get_args(), 
        $this->format(
          $this->database->insert(
            "user", 
            $parameters, 
            $this->controls["create"],
            $this->allowed_keys
          )
        )
      );

    } else {
      return null;
    }

  }

  function update($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters, $id);

    if ($this->validate($parameters, $id)) {
      return $this->callback(
        __METHOD__, 
        func_get_args(), 
        $this->format(
          $this->database->update(
            "user", 
            $parameters, 
            array("id" => $id), 
            null, 
            $this->controls["update"],
            $this->allowed_keys
          )
        )
      );
    } else {
      return null;
    }

  }

  function patch($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters, $id);
    
    if ($this->validation->require($id, "ID")) {

      if ($this->validate($parameters, $id, true)) {
        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $this->format(
            $this->database->update(
              "user", 
              $parameters, 
              array("id" => $id), 
              null, 
              $this->controls["update"],
              $this->allowed_keys
            )
          )
        );
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
        $data = $this->format(
          $this->database->delete(
            "user", 
            array("id" => $id), 
            null, 
            $this->controls["delete"],
            $this->allowed_keys
          )
        )
      ) {

        foreach($data as $value) {
          $file_old = ".." . $value->image;
          if (!empty($value->image) && file_exists($file_old)) {
            chmod($file_old, 0777);
            unlink($file_old);
          }
        }

        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $data
        );

      } else {
        return null;
      }
    } else {
      return null;
    }
  }

  function inform($parameters, $target_id = null) {
    if (!empty($parameters)) {
      if (isset($parameters["password"]) && !empty($target_id)) {
        unset($parameters["password"]);
      }
      if (isset($parameters["image"])) {
        unset($parameters["image"]);
        $parameters["image"] = "";
      }
      if (isset($parameters["email_verified"])) {
        unset($parameters["email_verified"]);
        if (empty($target_id)) {
          $parameters["email_verified"] = 0;
        }
      }
      if (isset($parameters["phone_verified"])) {
        unset($parameters["phone_verified"]);
        if (empty($target_id)) {
          $parameters["phone_verified"] = 0;
        }
      }
      if (isset($parameters["ndid_verified"])) {
        unset($parameters["ndid_verified"]);
        if (empty($target_id)) {
          $parameters["ndid_verified"] = 0;
        }
      }
      if (isset($parameters["face_verified"])) {
        unset($parameters["face_verified"]);
        if (empty($target_id)) {
          $parameters["face_verified"] = 0;
        }
      }
    }
    return $parameters;
  }

  function format($data, $prevent_data = false) {

    if (!empty($data)) {
      
      if (!$prevent_data) {
        $data->user_id_data = $this->data(null, "user_id", $data);
      }

      if (!$prevent_data) {
        $data->password_set = false;
        if (!empty($data->password)) {
          $data->password_set = true;
        }
      }
      unset($data->password);

      if (!$prevent_data) {
        $data->passcode_set = false;
        if (!empty($data->passcode)) {
          $data->passcode_set = true;
        }
      }
      unset($data->passcode);
      
      if (isset($data->image) && !empty($data->image)) {
        $data->image_path = (object) array(
          "name" => basename($data->image),
          "original" => $data->image,
          "thumbnail" => null,
          "large" => null
        );
        $data->image = $data->image_path->name;
        $data->image_path->original = $data->image;
        if (strpos($data->image, "http://") !== 0 || strpos($data->image, "https://") !== 0) {
          $data->image_path->original = $this->config->base_url . $data->image;
        }
        $data->image_path->thumbnail = Utilities::get_thumbnail($data->image_path->original);
        $data->image_path->large = Utilities::get_large($data->image_path->original);
      }
  
      $arrange_controls = function($controls = array()) {
        $data = array();
        if (!empty($controls) && is_array($controls)) {
          foreach($controls as $value) {
            $value = (array) $value;
            if (isset($data[$value["module_id"]])) {
              $data[$value["module_id"]] = array_merge(
                $data[$value["module_id"]],
                $this->control->arrange((object) $value)
              );
            } else {
              $data[$value["module_id"]] = $this->control->arrange((object) $value);
            }
          }
        }
        return $data;
      };

      if (!$prevent_data) {
        $member_data = $this->database->select_multiple(
          "member", 
          "*", 
          array("user" => $data->id), 
          null, 
          null, 
          null, 
          null, 
          null, 
          true
        );
        if (!empty($member_data)) {
          for ($i = 0; $i < count($member_data); $i++) {
            $group_data = $this->database->select(
              "group", 
              "*", 
              array("id" => $member_data[$i]->group_id), 
              null, 
              true
            );
            if (!empty($group_data)) {
              if (isset($group_data->controls) && !empty($group_data->controls)) {
                $group_data->controls = $arrange_controls(unserialize($group_data->controls));
                $member_data[$i]->group_id_data = $group_data;
              }
            }
          }
        }
        $data->members = $member_data;
    
        $controls = array();
        $control_data = $this->database->select_multiple(
          "control", 
          "*", 
          array("user" => $data->id), 
          null, 
          null, 
          null, 
          null, 
          null, 
          true
        );
        if (!empty($control_data)) {
          $controls = $arrange_controls($control_data);
        }
        if ($data->members) {
          for ($i = 0; $i < count($member_data); $i++) {
            if ($member_data[$i]->group_id_data && $member_data[$i]->group_id_data->controls) {
              $data->controls = array_merge($controls, $member_data[$i]->group_id_data->controls);
              unset($member_data[$i]->group_id_data->controls);
            }
          }
        } else {
          $data->controls = $controls;
        }
        $data->controls = $controls;
      }

    }

		return $data;

  }

  function data($id = null, $key, $data_override = null) {
    if ($this->validation->require($key, "Key")) {
      if (empty($data_override)) {
        if ($this->validation->require($id, "ID")  && $this->validation->require($key, "Key")) {
          $filters = array("id" => $id);
          $data_override = $this->database->select(
            ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
            array("`" . $key . "`"), 
            $filters, 
            null, 
            true
          );
        } else {
          return null;
        }
      }
      if (!empty($data_override) && isset($data_override->$key)) {
        $result = null;
        $get_key_data = function($table, $table_key, $value) {
          if (
            $value = $this->database->select(
              $table, 
              "*", 
              array($table_key => $value), 
              null, 
              true
            )
          ) {
            return $value;
          } else {
            return $value;
          }
        };
        if ($key == "user_id") {

          $data = $get_key_data("user", "id", $data_override->$key);
          if (!empty($data)) {

            $data->password_set = false;
            if (!empty($data->password)) {
              $data->password_set = true;
            }
            unset($data->password);
      
            $data->passcode_set = false;
            if (!empty($data->passcode)) {
              $data->passcode_set = true;
            }
            unset($data->passcode);
            
            if (isset($data->image) && !empty($data->image)) {
              $data->image_path = (object) array(
                "name" => basename($data->image),
                "original" => $data->image,
                "thumbnail" => null,
                "large" => null
              );
              $data->image = $data->image_path->name;
              $data->image_path->original = $data->image;
              if (strpos($data->image, "http://") !== 0 || strpos($data->image, "https://") !== 0) {
                $data->image_path->original = $this->config->base_url . $data->image;
              }
              $data->image_path->thumbnail = Utilities::get_thumbnail($data->image_path->original);
              $data->image_path->large = Utilities::get_large($data->image_path->original);
            }

            $result = $data;
      
          }
        }
        return $this->callback(__METHOD__, func_get_args(), $result);
      } else {
        throw new Exception($this->translation->translate("Not found"), 404);
        return null;
      }
    } else {
      return null;
    }
  }

  function validate($parameters, $target_id = null, $patch = false) {
    $result = false;
    if (!empty($parameters)) {
      if (
        (
          $this->validation->set($parameters, "username")
          && ($this->validation->set($parameters, "password") || !empty($target_id))
          && ($this->validation->set($parameters, "confirm_password") || !empty($target_id))
          && $this->validation->set($parameters, "email")
          && $this->validation->set($parameters, "name")
          && $this->validation->set($parameters, "last_name")
          && $this->validation->set($parameters, "nick_name")
          && $this->validation->set($parameters, "phone")
          && ($this->validation->set($parameters, "passcode") || !empty($target_id))
        ) || $patch
      ) {
        if (
          $this->validation->require($parameters["username"], "Username")
          && $this->validation->string_min($parameters["username"], "Username", 3)
          && $this->validation->string_max($parameters["username"], "Username", 100)
          && $this->validation->no_special_characters($parameters["username"], "Username")
          && $this->validation->unique($parameters["username"], "Username", "username", "user", $target_id)
          && $this->validation->require($parameters["password"], "Password")
          && $this->validation->string_min($parameters["password"], "Password", 8)
          && $this->validation->string_max($parameters["password"], "Password", 100)
          && $this->validation->password($parameters["password"], "password")
          && $this->validation->password_equal_to($parameters["password"], "password", $parameters["confirm_password"])
          && $this->validation->number($parameters["passcode"], "passcode")
          && $this->validation->string_max($parameters["name"], "Name", 100)
          && $this->validation->string_max($parameters["last_name"], "Last Name", 100)
          && $this->validation->string_max($parameters["nick_name"], "Nick Name", 50)
          && $this->validation->email($parameters["email"], "Email")
          && $this->validation->unique($parameters["email"], "Email", "email", "user", $target_id)
        ) {
          $result = true;
        }
      }
    } else {
      throw new Exception($this->translation->translate("Bad request"), 400);
    }
    return $result;
  }

  private function update_device($user_id, $session_id) {
    $filters = array(
      "user_id" => $user_id,
      "session" => $session_id
    );
    if (
      $data = $this->database->select(
        "device", 
        "*", 
        $filters, 
        null, 
        true
      )
    ) {
      $date_recent = gmdate("Y-m-d H:i:s");
      $date_expire = gmdate("Y-m-d H:i:s", strtotime($this->config["session_duration"]));
      $parameters = array(
        "ip_recent" => $_SERVER["REMOTE_ADDR"],
        "date_recent" => $date_recent,
        "date_expire" => $date_expire
      );
      if (
        $this->database->update(
          "device", 
          $parameters, 
          array("id" => $data->id), 
          null, 
          true
        )
      ) {
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  private function random_username() {
    $seed = str_split("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_");
    shuffle($seed);
    $random = "";
    foreach(array_rand($seed, 22) as $i) $random .= $seed[$i];
    if ($this->validation->unique($random, "Username", "username", "user")) {
      return $random;
    } else {
      return $this->random_username();
    }
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