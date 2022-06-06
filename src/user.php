<?php
namespace Abstracts;

use \Abstracts\Database;
use \Abstracts\Validation;
use \Abstracts\Translation;
use \Abstracts\Utilities;
use \Abstracts\Encryption;
use \Abstracts\API;
use \Abstracts\Device;

use Exception;

class User {

  /* configuration */
  private $id = "6";
  private $public_functions = array(
		"verify"
	);

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
  private $encryption = null;

  /* services */
  private $api = null;
  private $device = null;

  function __construct(
    $config,
    $session = null,
    $controls = null,
    $module = null
  ) {

    $this->module = $module;
    $this->config = $config;
    $this->session = $session;
    $this->controls = Utilities::sync_control(
      $this->id, 
      $session, 
      $controls,
      $module
    );
    
    $this->database = new Database($this->config, $this->session, $this->controls);
    $this->validation = new Validation($this->config);
    $this->translation = new Translation();
    $this->utilities = new Utilities();
    $this->encryption = new Encryption();

    $this->api = new API($this->config);
    $this->device = new Device($this->config);

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
    if ($this->api->validate_request($this->id, $function, $this->public_functions)) {
      if ($function == "login") {
        $result = $this->$function(
          (isset($parameters["post"]["username"]) ? $parameters["post"]["username"] : null),
          (isset($parameters["post"]["password"]) ? $parameters["post"]["password"] : null),
          (isset($parameters["get"]["remember"]) ? $parameters["get"]["remember"] : 
            (isset($parameters["post"]["remember"]) ? $parameters["post"]["remember"] : null)
          ),
          (isset($parameters["post"]["device"]) ? $parameters["post"]["device"] : null)
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
      } else if ($function == "upload") {
        $result = $this->$function(
          (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
          $_FILES
        );
      } else if ($function == "file") {
        $result = $this->$function(
          (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
          (isset($parameters["delete"]) ? $parameters["delete"] : null)
        );
      } else {
        throw new Exception($this->translation->translate("Not found"), 404);
      }
    } else {
      throw new Exception($this->translation->translate("Permission denied"), 403);
    }
    return $result;
  }

  function login($username, $password, $remember = false, $device = null) {

    if (
      !isset($username) || empty($username)
      || !isset($password) || empty($password)
    ) {
      foreach($this->utilities->get_headers_all() as $key => $value) {
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
      $this->validation->requires($username, "Username")
      && $this->validation->requires($password, "Password")
    ) {

      $password_hash = hash("sha256", md5($password . $this->config["password_salt"]));
      $filters = array(
        "activate" => "1",
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
        true
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
                if ($this->callback("login", $data)) {
                  return $data;
                } else {
                  return null;
                }
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
            if ($this->callback("login", $data)) {
              return $data;
            } else {
              return null;
            }
            return $data;
          }
        };

        $data = $this->format($data);

        $session_id = session_id();
        $date_recent = gmdate("Y-m-d H:i:s");
        $date_expire = gmdate("Y-m-d H:i:s", strtotime($this->config["session_duration"]));

        if (isset($remember) && (!empty($remember))) {

          $device_filters = array(
            "user_id" => "1",
            "session" => $session_id
          );
          $device_data = $this->database->select(
            "device", 
            "*", 
            $device_filters, 
            null, 
            true
          );
          if (!empty($device_data)) {
            $device_parameters = array(
              "ip_recent" => $_SERVER["REMOTE_ADDR"],
              "date_recent" => $date_recent,
              "date_expire" => $date_expire
            );
            if (
              $this->database->update(
                "device", 
                $device_parameters, 
                array("id" => $device_data->id), 
                null, 
                true
              )
            ) {
              return $result($data, $session_id, $date_recent, $date_expire);
            } else {
              return null;
            }
          } else {
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
              "is_mobile" => ($device_user_agent->is_mobile) ? "1" : "0",
              "is_native" => ($device_user_agent->is_native) ? "1" : "0",
              "is_virtual" => ((isset($device) && isset($device["is_virtual"])) ? $device["is_virtual"] : "0"),
              "app_name" => ((isset($device) && isset($device["app_name"])) ? $device["app_name"] : ""),
              "app_id" => ((isset($device) && isset($device["app_id"])) ? $device["app_id"] : ""),
              "app_version" => ((isset($device) && isset($device["app_version"])) ? $device["app_version"] : ""),
              "app_build" => ((isset($device) && isset($device["app_build"])) ? $device["app_build"] : ""),
              "ip_recent" => $_SERVER["REMOTE_ADDR"],
              "date_recent" => $date_recent,
              "date_expire" => $date_expire,
              "activate" => "1",
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
          }
    
        } else {
          return $result($data, $session_id, $date_recent, $date_expire);
        }

      } else {
        throw new Exception($this->translation->translate("Invalid account"), 404);
        return null;
      }
    } else {
      return null;
    }
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
          true
        )
      ) {
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
              "activate" => "1",
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
                    "activate" => "1",
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
                  if ($this->callback("signup", $data)) {
                    return true;
                  } else {
                    return null;
                  }
                } else {
                  $this->database->delete(
                    "user", 
                    array("id" => $data->id), 
                    null, 
                    true
                  );
                  return null;
                }
                
              } else {
                if ($this->callback("signup", $data)) {
                  return true;
                } else {
                  return null;
                }
              }
            } else {
              $this->database->delete(
                "user", 
                array("id" => $data->id), 
                null, 
                true
              );
              return null;
            }
          } else {
            $this->database->delete(
              "user", 
              array("id" => $data->id), 
              null, 
              true
            );
            return null;
          }
        } else {
          if ($this->callback("signup", $data)) {
            return true;
          } else {
            return null;
          }
        }
      } else {
        return null;
      }

    } else {
      return null;
    }
  }

  function get($id, $activate = null) {
    if ($this->validation->requires($id, "ID")) {
      $filters = array("id" => $id);
      if ($activate) {
        $filters["activate"] = "1";
      }
      $data = $this->database->select(
        "user", 
        "*", 
        array("id" => $id), 
        null, 
        true
      );
      if (!empty($data)) {
        if ($this->callback("get", $data)) {
          return $this->format($data);
        } else {
          return null;
        }
      } else {
        throw new Exception($this->translation->translate("Not found"), 404);
        return null;
      }
    } else {
      return null;
    }
  }

  function list(
    $start = 0, 
    $limit = "", 
    $sort_by = "id", 
    $sort_direction = "desc", 
    $activate = false, 
    $filters = array(), 
    $extensions = array()
  ) {
    if (!empty($activate)) {
      array_push($filters, array("activate" => "1"));
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
      $this->controls["view"]
    );
    if (!empty($list)) {
      $data = array();
      foreach($list as $value) {
        array_push($data, $this->format($value));
      }
      if ($this->callback("list", $data)) {
        return $data;
      } else {
        return null;
      }
    } else {
      return array();
    }
  }

  function count(
    $start = 0, 
    $limit = "", 
    $activate = false, 
    $filters = array(), 
    $extensions = array()
  ) {
    if (!empty($activate)) {
      array_push($filters, array("activate" => "1"));
    }
    if (
      $data = $this->database->count(
        "user", 
        "*", 
        $filters, $start, $extensions, $limit, $this->controls["view"]
      )
    ) {
      return $data;
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

      if (
        $this->callback(
          "create", 
          $data = $this->format(
            $this->database->insert(
              "user", 
              $parameters, 
              $this->controls["create"]
            )
          )
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
    $parameters = $this->inform($parameters, $id);

    if ($this->validate($parameters, $id)) {
      if (
        $this->callback(
          "update", 
          $data = $this->format(
            $this->database->update(
              "user", 
              $parameters, 
              array("id" => $id), 
              null, 
              $this->controls["update"]
            )
          )
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

  function patch($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters, $id);
    
    if ($this->validation->requires($id, "ID")) {

      if ($this->validate($parameters, $id, true)) {
        if (
          $this->callback(
            "patch", 
            $data = $this->format(
              $this->database->update(
                "user", 
                $parameters, 
                array("id" => $id), 
                null, 
                $this->controls["update"]
              )
            )
          )
        ) {
          return $data;
        } else {
          return null;
        }
      } else {
        return null;
      }

    } else {
      return null;
    }

  }

  function delete($id) {
    if ($this->validation->requires($id, "ID")) {
      if (
        $data = $this->format(
          $this->database->delete(
            "user", 
            array("id" => $id), 
            null, 
            $this->controls["delete"]
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

      } else {
        return null;
      }
    } else {
      return null;
    }
  }

  function get_user_by_user_id($user_id) {
    $data = $this->database->select(
      "user", 
      "*", 
      array("id" => $user_id), 
      null, 
      true
    );
    if (!empty($data)) {

      $data->password_set = false;
      if (!empty($data->password)) {
        $data->password_set = true;
      }
      unset($data->password);

      $data->passcode_set = false;
      if (!empty($data->passcode)) {
        $data->passcode_set = true;
      } else {
      }
      unset($data->passcode);
      
      $data->image_name = basename($data->image);
      if (isset($data->image) && !empty($data->image)) {
        if (strpos($data->image, "http://") !== false || strpos($data->image, "https://") !== false) {
          $data->image_path = $data->image;
          $data->image_thumbnail_path = get_thumbnail($data->image);
          $data->image_large_thumbnail_path = get_large($data->image);
        } else {
          $data->image_path = $this->config->base_url . $data->image;
          $data->image_thumbnail_path = $this->config->base_url . get_thumbnail($data->image);
          $data->image_large_thumbnail_path = $this->config->base_url . get_large($data->image);
        }
      }

      return $data;

    } else {
      throw new Exception($this->translation->translate("Not found"), 404);
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

  function format($data) {

    if (!empty($data)) {

      $data->password_set = false;
      if (!empty($data->password)) {
        $data->password_set = true;
      }
      unset($data->password);
  
      $data->passcode_set = false;
      if (!empty($data->passcode)) {
        $data->passcode_set = true;
      } else {
      }
      unset($data->passcode);
      
      $data->image_name = basename($data->image);
      if (isset($data->image) && !empty($data->image)) {
        if (strpos($data->image, "http://") !== false || strpos($data->image, "https://") !== false) {
          $data->image_path = $data->image;
          $data->image_thumbnail_path = get_thumbnail($data->image);
          $data->image_large_thumbnail_path = get_large($data->image);
        } else {
          $data->image_path = $this->config->base_url . $data->image;
          $data->image_thumbnail_path = $this->config->base_url . get_thumbnail($data->image);
          $data->image_large_thumbnail_path = $this->config->base_url . get_large($data->image);
        }
      }
  
      $format_controls = function($controls = array()) {
        $data = array();
        if (!empty($controls) && is_array($controls)) {
          foreach($controls as $value) {
            $value = (array) $value;
            $control = array(
              "view" => false,
              "create" => false,
              "update" => false,
              "delete" => false,
            );
            if ($value["behaviors"] && !empty($value["behaviors"])) {
              $behaviors = explode(",", $value["behaviors"]);
              for ($i = 0; $i < count($behaviors); $i++) {
                if ($control[$behaviors[$i]] !== true) {
                  if (!$value["rules"] || empty($value["rules"])) {
                    $control[$behaviors[$i]] = true;
                  } else {
                    if (!is_array($control[$behaviors[$i]])) {
                      $control[$behaviors[$i]] = array();
                    }
                    array_push($control[$behaviors[$i]], explode(",", $value["rules"]));
                  }
                }
              }
            }
            $data[$value["module_id"]] = $control;
          }
        }
        return $data;
      };
  
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
              $group_data->controls = $format_controls(unserialize($group_data->controls));
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
        $controls = $format_controls($control_data);
      }
      if ($data->members) {
        foreach($data->members as $value) {
          if ($value->group_id_data && $value->group_id_data->controls) {
            $data->controls = array_merge($controls, $value->group_id_data->controls);
          }
        }
      } else {
        $data->controls = $controls;
      }
      $data->controls = $controls;
      
      $data->user_id_data = $this->get_user_by_user_id($data->user_id);

    }

		return $data;

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
          $this->validation->requires($parameters["username"], "Username")
          && $this->validation->string_min($parameters["username"], "Username", 3)
          && $this->validation->string_max($parameters["username"], "Username", 100)
          && $this->validation->no_special_characters($parameters["username"], "Username")
          && $this->validation->unique($parameters["username"], "Username", "username", "user", $target_id)
          && $this->validation->requires($parameters["password"], "Password")
          && $this->validation->string_min($parameters["password"], "Password", 8)
          && $this->validation->string_max($parameters["password"], "Password", 100)
          && $this->validation->password($parameters["password"], "password")
          && $this->validation->password_equal_to($parameters["password"], "password", $parameters["confirm_password"])
          && $this->validation->number($parameters["passcode"], "passcode")
          && $this->validation->string_max($parameters["name"], "Name", 100)
          && $this->validation->string_max($parameters["last_name"], "Last Name", 100)
          && $this->validation->string_max($parameters["nick_name"], "Nick Name", 50)
          && $this->validation->email($parameters["email"], "Email")
        ) {
          $result = true;
        }
      }
    } else {
      throw new Exception($this->translation->translate("Bad request"), 400);
    }
    return $result;
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

  private function callback($function, $parameters) {
    $namespace = "\\Abstracts\\Callback\\User";
    if (class_exists($namespace)) {
      if (method_exists($namespace, $function)) {
        $callback = new $namespace($this->config, $this->session, $this->controls);
        try {
          $callback->$function($parameters);
          return true;
        } catch(Exception $e) {
          throw new Exception($e->getMessage() . " ". $this->translation->translate("on callback"), $e->getCode());
          return false;
        }
      } else {
        return true;
      }
    } else {
      return true;
    }
  }

}