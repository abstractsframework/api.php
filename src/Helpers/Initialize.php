<?php
namespace Abstracts\Helpers;

use \Abstracts\Helpers\Encryption;
use \Abstracts\Helpers\Utilities;

use Exception;

use DateTimeZone;

class Initialize {

  /* core */
  public $id = null;
  public $class = null;
  public $config = null;
  public $module = null;
  public $session = null;
  public $controls = array(
    "view" => false,
    "create" => false,
    "update" => false,
    "delete" => false,
  );
  
  function __construct(
    $session = null, 
    $controls = null, 
    $identifier = null
  ) {
    $this->load();
    $this->config = $this->config();
    if (!empty($this->config)) {
      $this->session = $this->session($session);
      $this->module = $this->module($identifier);
      $this->id = ((!empty($this->module) && isset($this->module->id)) ? $this->module->id : null);
      $this->class = Utilities::create_class_name($identifier);
      $this->controls = $this->control(
        $this->id, 
        $this->session, 
        $controls,
        $this->module
      );
    }
  }

  public static function config() {
    $config = array();
    $config_path = Utilities::backtrace() . "abstracts.config.php";
    if (file_exists($config_path)) {

      require($config_path);
      
      $hour_zone = round((date("Z") / 60) / 60);
      $config["datetimezone"] = new DateTimeZone($config["timezone"]);
      if (date("Z") >= 0) {
        $config["timezone_difference"] = "+" . sprintf("%02d", $hour_zone);
      } else {
        $config["timezone_difference"] = "-" . sprintf("%02d", $hour_zone);
      }
  
      $base_last_string_position = strlen($config["base_url"]) - 1;
      if ($config["base_url"][$base_last_string_position] == "/") {
        $config["base_url"] = rtrim($config["base_url"], "/");
      }
  
      $config["website_url"] = $config["base_url"] . "/";
  
      $config["template_directory"] = "templates/" . $config["template"] . "/";
      $config["upload_directory"] = "media/";
      $config["service_directory"] = "services/";
  
      $config["template_path"] = $config["website_url"] . $config["template_directory"];
      $config["upload_path"] = realpath($config["website_url"] . $config["upload_directory"]);
      $config["service_path"] = $config["website_url"] . $config["service_directory"];
  
      $config["encrypt_key_filemanger"] = "dd17e9c5f93007885229a2049be6a678";

      if (isset($_POST["l"]) && !empty($_POST["l"])) {
        $config["language"] = $_POST["l"];
      } else if (isset($_REQUEST["l"]) && !empty($_REQUEST["l"])) {
        $config["language"] = $_REQUEST["l"];
      } else {
        foreach (Utilities::get_all_headers() as $key => $value) {
          if (strtolower($key) == "language") {
            $config["language"] = strtolower($value);
          }
        }
      }

    }
    return $config;
  }
  
  public static function load($paths = array()) {
    if (empty($paths)) {
      $paths = array();
    }
    $config = Initialize::config();
    if (!empty($config)) {
      if (isset($config["services_path"]) && file_exists(Utilities::backtrace() . trim($config["services_path"], "/"))) {
        array_push($paths, (Utilities::backtrace() . trim($config["services_path"], "/")));
      }
      if (isset($config["callback_path"]) && file_exists(Utilities::backtrace() . trim($config["callback_path"], "/"))) {
        array_push($paths, (Utilities::backtrace() . trim($config["callback_path"], "/")));
      }
    }
    $exceptions = array(
      "initial.php"
    );
    foreach ($paths as $path) {
      $files = scandir($path);
      foreach ($files as $file) {
        $info = pathinfo($file);
        if (isset($info["extension"])) {
          $extension = strtolower($info["extension"]);
          if ($extension == "php" && !in_array($file, $exceptions)) {
            require_once($path . "/" . $file);
          }
        }
      }
    }
  }

  public static function headers() {

    $config = Initialize::config();
    if (!empty($config)) {
      try {
        mb_internal_encoding($config["encoding"]);  
      } catch (Exception $e) {}
      date_default_timezone_set($config["timezone"]);
    }
    
    if (!isset($_SESSION)) {
    
      session_cache_limiter('private');
      $cache_limiter = session_cache_limiter();
      session_cache_expire(5);
      $cache_expire = session_cache_expire();
    
      session_start();
    
    }

  }

  public static function response_headers() {
    if (isset($_SERVER["HTTP_ORIGIN"])) {
      header("Access-Control-Allow-Origin: *");
      header("Access-Control-Request-Headers: *");
    }
    if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
      if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, COPY, DELETE, OPTIONS");
      }
      if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])) {
        header("Access-Control-Allow-Headers: Access-Control-Allow-Origin, Access-Control-Allow-Methods, Content-Type, Authorization, Key, Secret, Token, Hash, Language");
      }
      exit(0);
    }
    header("Content-Type: application/json");
  }

  public static function timeout($seconds = 0) {
    set_time_limit($seconds);
  }

  public static function display_errors($display = false) {
    ini_set("display_errors", ($display ? "On" : "Off"));
    ini_set("error_reporting", E_ALL);
  }

  public static function start($start) {
    return !empty($start) ? $start : null;
  }

  public static function limit($limit) {
    return !empty($limit) ? $limit : null;
  }

  public static function active($active) {
    return isset($active) ? (
      ($active === "true" || $active === "1" || $active === 1) ? true 
      : ($active === "false" || $active === "0" || $active === 0 ? false : $active)
    ) : null;
  }

  public static function return_references($return_references) {
    return !empty($return_references) ? (
      ($return_references === "true" || $return_references === true || $return_references === "1" || $return_references === 1) ? true 
      : (!is_array($return_references) ? explode(",", $return_references) : $return_references)
    ) : false;
  }

  public static function translation($translation) {
    return isset($translation) ? (
      ($translation === "true" || $translation === "1" || $translation === 1) ? true 
      : ($translation === "false" || $translation === "0" || $translation === 0 ? false : $translation)
    ) : null;
  }

  public static function filters($filters) {
    $filters = is_array($filters) ? $filters : array();
    if (!empty($filters)) {
      $function_parameters = array(
        "start", "limit", "sort_by", "sort_direction", "active", "filters", "extensions", "return_references"
      );
      foreach ($filters as $key => $value) {
        if (in_array($key, $function_parameters)) {
          unset($filters[$key]);
        }
      }
    }
    return $filters;
  }

  public static function extensions($extensions) {
    return is_array($extensions) ? $extensions : array();
  }

  public static function sort_by($sort_by, $specific = "id") {
    if (empty($specific)) {
      $specific = "id";
    }
    $sort_by = (!is_array($sort_by) ? explode(",", $sort_by) : $sort_by);
    return !empty($sort_by) ? $sort_by : array($specific);
  }

  public static function sort_direction($sort_direction, $specific = "desc") {
    if (empty($specific)) {
      $specific = "desc";
    }
    $sort_direction = (!is_array($sort_direction) ? explode(",", $sort_direction) : $sort_direction);
    return !empty($sort_direction) ? $sort_direction : array($specific);
  }

  private function session($override_session = null) {
    
    $session = $this->session;
    if (empty($session)) {
      $session = $override_session;
    }
    if (empty($session)) {
      
      $config = Initialize::config();
      $encryption = new Encryption();
  
      $authorization = null;
      if (empty($authorization)) {
        if (isset($_POST["a"]) && !empty($_POST["a"])) {
          $authorization = $_POST["a"];
        } else if (isset($_REQUEST["a"]) && !empty($_REQUEST["a"])) {
          $authorization = $_REQUEST["a"];
        } else {
          foreach (Utilities::get_all_headers() as $key => $value) {
            if (strtolower($key) == "authorization") {
              $authorization= $value;
            }
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
              $decoded = $encryption->decode(
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
              }
            }
          } else {
            $token = str_replace("Bearer ", "", $authorization);
            if (!empty($token)) {
              $session_parts = explode(".", base64_decode($token));
              if (count($session_parts) == 2) {
                $user_id = $session_parts[0];
                $session_id = $session_parts[1];
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
            }
          }
        }
        if (
          isset($user_id) && !empty($user_id)
          && isset($session_id) && !empty($session_id)
        ) {
          
          $database = new Database(null, Utilities::override_controls(true, true, true, true));
  
          if (!empty(
            $data = $database->select(
              "user", 
              "*", 
              array("id" => $user_id), 
              null, 
              true,
              false
            )
          )) {
            $filters = array(
              "user_id" => $data->id,
              "session" => $session_id
            );
            if (!empty(
              $device_data = $database->select(
                "device", 
                "*", 
                $filters, 
                null, 
                true,
                false
              )
            )) {
              $update_at = gmdate("Y-m-d H:i:s");
              $expire_at = gmdate("Y-m-d H:i:s", strtotime($config["session_duration"]));
              $parameters = array(
                "ip_recent" => $_SERVER["REMOTE_ADDR"],
                "update_at" => $update_at,
                "expire_at" => $expire_at
              );
              if (
                $database->update(
                  "device", 
                  $parameters, 
                  array("id" => $device_data->id), 
                  null, 
                  true,
                  false
                )
              ) {
  
                $data->session_id = $session_id;
                
                $arrange_controls = function($controls = array()) {
  
                  $arrange = function($data, $session) {
  
                    $database = new Database(null, Utilities::override_controls(true, true, true, true));
  
                    if (!empty($data)) {
                      
                      $controls = array(
                        "view" => false,
                        "create" => false,
                        "update" => false,
                        "delete" => false
                      );
                
                      if (
                        !empty(
                          $module_data = $database->select(
                            "module", 
                            array("default_controls"), 
                            array("id" => $data->module_id), 
                            null, 
                            true,
                            false
                          )
                        ) 
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
                                      if (isset($session) && isset($session->id)) {
                                        $rules[$i] = $rule_parts[0] . $operator . str_replace("<session>", $session->id, $rule_parts[1]);
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
                
                  };
  
                  $data = array();
                  if (!empty($controls) && is_array($controls)) {
                    foreach ($controls as $controls_value) {
                      $value = (array) $controls_value;
                      if (isset($data[$value["module_id"]])) {
                        $data[$value["module_id"]] = array_merge(
                          $data[$value["module_id"]],
                          $arrange((object) $value, $data)
                        );
                      } else {
                        $data[$value["module_id"]] = $arrange((object) $value, $data);
                      }
                    }
                  }
                  return $data;
                };
                $member_list = $database->select_multiple(
                  "member", 
                  "*", 
                  array("user" => $data->id), 
                  null, 
                  null, 
                  null, 
                  "id", 
                  "asc", 
                  true,
                  false
                );
                if (!empty($member_list)) {
                  for ($i = 0; $i < count($member_list); $i++) {
                    if ($member_list[$i]->active === "1") {
                      $member_list[$i]->active = true;
                    } else if ($member_list[$i]->active === "0" || empty($member_list[$i]->active)) {
                      $member_list[$i]->active = false;
                    }
                    $group_data = $database->select(
                      "group", 
                      "*", 
                      array("id" => $member_list[$i]->group_id), 
                      null, 
                      true,
                      false
                    );
                    if (!empty($group_data)) {
                      if (isset($group_data->controls) && !empty($group_data->controls)) {
                        $group_data->controls = $arrange_controls(unserialize($group_data->controls));
                        $member_list[$i]->group_id_data = $group_data;
                      }
                    }
                  }
                }
                $data->members = $member_list;
            
                $controls = array();
                $control_list = $database->select_multiple(
                  "control", 
                  "*", 
                  array("user" => $data->id), 
                  null, 
                  null, 
                  null, 
                  "module_id", 
                  "asc", 
                  true,
                  false
                );
                if (!empty($control_list)) {
                  $controls = $arrange_controls($control_list);
                }
                if ($data->members) {
                  for ($i = 0; $i < count($member_list); $i++) {
                    if ($member_list[$i]->group_id_data && $member_list[$i]->group_id_data->controls) {
                      $data->controls = array_merge($controls, $member_list[$i]->group_id_data->controls);
                      unset($member_list[$i]->group_id_data->controls);
                    }
                  }
                } else {
                  $data->controls = $controls;
                }
                $data->controls = $controls;
          
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
                  $data->image_reference = (object) array(
                    "id" => $data->image,
                    "name" => basename($data->image),
                    "original" => $data->image,
                    "thumbnail" => null,
                    "large" => null
                  );
                  $data->image_reference->original = $data->image;
                  if (strpos($data->image, "http://") !== 0 || strpos($data->image, "https://") !== 0) {
                    $data->image_reference->original = $config["base_url"] . $data->image;
                  }
                  $data->image_reference->thumbnail = Utilities::get_thumbnail($data->image_reference->original);
                  $data->image_reference->large = Utilities::get_large($data->image_reference->original);
                }
  
                $session = $data;
                
              }
            }
          }
        }
      }

    }

    return $session;

  }
  
  private function module($identifier = null) {
    if (!empty($identifier)) {
      $database = new Database();
      $extensions = array(
        array(
          "conjunction" => "",
          "key" => "id",
          "operator" => "=",
          "value" => "'" . $identifier . "'"
        ),
        array(
          "conjunction" => "OR",
          "key" => "database_table",
          "operator" => "=",
          "value" => "'" . $identifier . "'"
        )
      );
      $module_data = $database->select(
        "module", 
        "*", 
        null, 
        $extensions, 
        true,
        false
      );
      if (!empty($module_data)) {
        if (!empty($module_data) && isset($module_data->default_controls)) {
          $module_data->default_controls = explode(",", $module_data->default_controls);
        } else {
          $module_data->default_controls = array();
        }
      }
      return $module_data;
    } else {
      return null;
    }
  }

  private function control(
    $module_id, 
    $session, 
    $controls = array(
      "view" => false,
      "create" => false,
      "update" => false,
      "delete" => false
    ),
    $module = null
  ) {
    if (!$controls) {
      $controls = array(
        "view" => false,
        "create" => false,
        "update" => false,
        "delete" => false
      );
    }
    if (isset($session) && isset($session->controls) && !empty($session->controls)) {
      if (isset($session->controls[$module_id])) {
        foreach ($controls as $key => $value) {
          if (empty($value)) {
            $controls[$key] = $session->controls[$module_id][$key];
          }
        }
      }
    }
    if (!empty($module) && isset($module->default_controls)) {
      foreach ($module->default_controls as $key => $value) {
        $controls[$value] = true;
      }
    }
    return $controls;
  }

}