<?php
namespace Abstracts;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;
use \Abstracts\Helpers\Encryption;

use \Abstracts\API;
use \Abstracts\Device;
use \Abstracts\Group;
use \Abstracts\Log;
use \Abstracts\Hash;
use \Abstracts\Mail;

use Exception;
use finfo;

class User {

  /* configuration */
  public $id = "6";
  public $public_functions = array(
    "verify",
    "validate_username",
    "reset_password"
	);
  public $module = null;

  /* core */
  private $config = null;
  private $session = null;
  private $controls = null;

  /* helpers */
  private $database = null;
  private $validation = null;
  private $translation = null;
  private $encryption = null;

  /* services */
  private $api = null;
  private $control = null;
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
    $this->encryption = new Encryption();

    /* initialize: services */
    $this->api = new API($this->session, 
      Utilities::override_controls(true, true, true, true)
    );
    $this->control = new Control($this->session, 
      Utilities::override_controls(true, true, true, true)
    );
    $this->log = new Log($this->session, 
      Utilities::override_controls(true, true, true, true)
    );

  }

  function request($function, $parameters) {
    $result = null;
    if ($this->api->authorize($this->id, $function, $this->public_functions)) {
      if ($function == "login") {
        $result = $this->$function(
          (isset($parameters["username"]) ? $parameters["username"] : null),
          (isset($parameters["password"]) ? $parameters["password"] : null),
          (isset($parameters["remember"]) ? $parameters["remember"] : 
            (isset($parameters["remember"]) ? $parameters["remember"] : null)
          ),
          (isset($parameters["device"]) ? $parameters["device"] : null)
        );
      } else if ($function == "authenticate") {
        $result = $this->$function(
          (isset($parameters["authorization"]) ? $parameters["authorization"] : 
            (isset($parameters["authorization"]) ? $parameters["authorization"] : null)
          )
        );
      } else if ($function == "logout") {
        $result = $this->$function(
          (isset($parameters["authorization"]) ? $parameters["authorization"] : 
            (isset($parameters["authorization"]) ? $parameters["authorization"] : null)
          )
        );
      } else if ($function == "signup") {
        $result = $this->$function($parameters);
      } else if ($function == "get") {
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
        $result = $this->$function(
          $parameters, 
          null,
          $_FILES
        );
      } else if ($function == "update") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters) ? $parameters : null),
          $_FILES
        );
      } else if ($function == "patch") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters) ? $parameters : null),
          $_FILES
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
      } else if ($function == "update_password") {
        $result = $this->$function(
          (isset($parameters["username"]) ? $parameters["username"] : null),
          (isset($parameters["password"]) ? $parameters["password"] : null),
          (isset($parameters["password_new"]) ? $parameters["password_new"] : null),
          (isset($parameters["confirm_password_new"]) ? $parameters["confirm_password_new"] : null)
        );
      } else if ($function == "reset_password") {
        $result = $this->$function(
          (isset($parameters["email"]) ? $parameters["email"] : null),
          (isset($parameters["phone"]) ? $parameters["phone"] : null),
        );
      } else if ($function == "verify") {
        $result = $this->$function(
          (isset($parameters["code"]) ? $parameters["code"] : null)
        );
      } else if ($function == "validate_credential") {
        $result = $this->$function(
          (isset($parameters["username"]) ? $parameters["username"] : null),
          (isset($parameters["password"]) ? $parameters["password"] : null)
        );
      } else if ($function == "validate_username") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters["username"]) ? $parameters["username"] : null)
        );
      } else {
        throw new Exception($this->translation->translate("Function not supported"), 421);
      }
    }
    return $result;
  }

  function login($username, $password, $remember = false, $device_info = null) {

    if (
      !isset($username) || empty($username)
      || !isset($password) || empty($password)
    ) {
      if (isset($_POST["a"]) && !empty($_POST["a"])) {
        $authorization = $_POST["a"];
      } else if (isset($_REQUEST["a"]) && !empty($_REQUEST["a"])) {
        $authorization = $_REQUEST["a"];
      } else {
        foreach (Utilities::get_all_headers() as $key => $value) {
          if (strtolower($key) == "authorization") {
            if (strpos($value, "Basic") === 0) {
              $authorization = base64_decode(str_replace("Basic ", "", $value));
            }
          }
        }
      }
    }

    if (!empty($authorization)) {
      $authorization_parts = explode(":", $authorization);
      if (isset($authorization_parts[0])) {
        $username = $authorization_parts[0];
      }
      if (isset($authorization_parts[1])) {
        $password = $authorization_parts[1];
      }
    }

    if (
      $this->validation->require($username, "Username")
      && $this->validation->require($password, "Password")
    ) {

      $filters = array(
        "active" => true
      );
      $extensions = array(
        array(
          "conjunction" => "",
          "key" => "username",
          "operator" => "=",
          "value" => "'" . $username . "'",
        ),
        array(
          "conjunction" => "OR",
          "key" => "email",
          "operator" => "=",
          "value" => "'" . $username . "'",
        ),
        array(
          "conjunction" => "OR",
          "key" => "phone",
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
        false
      );
      if (!empty($data)) {

        $password_hash = hash("sha256", md5($password . $this->config["password_salt"]));
        $filters["password"] = hash("sha256", md5($password . $this->config["password_salt"]));

        $data_password = $this->database->select(
          "user", 
          "*", 
          $filters, 
          $extensions, 
          true,
          false
        );
        
        $data_hash = $this->database->select(
          "hash", 
          "*", 
          array(
            "hash" => $password_hash,
            "content" => $data->id
          ), 
          null, 
          true,
          false
        );

        if (!empty($data_password) || !empty($data_hash)) {

          $result = function($data, $session_id, $update_at, $expire_at, $function) {
            if (
              isset($this->config["encrypt_authorization"]) 
              && !empty($this->config["encrypt_authorization"])
            ) {
              
              $values = array(
                "session_id" => $session_id,
                "id" => $data->id,
                "iss" => strtotime($update_at),
                "exp" => strtotime($expire_at)
              );
    
              try {
                $token = $this->encryption->encode(
                  $values, 
                  $this->config["encrypt_ssl_private_key"], 
                  $this->config["encrypt_authorization"]
                );
                if ($token !== false) {
                  $data->token = $token;
                  return Utilities::callback(
                    $function, 
                    func_get_args(), 
                    $data,
                    $this->session,
                    $this->controls,
                    $this->id
                  );
                } else {
                  throw new Exception($this->translation->translate("Invalid authorization"), 401);
                }
              } catch(Exception $e) {
                throw new Exception($e->getMessage(), 500);
              }
              
            } else {
              $data->token = base64_encode($data->id . "." . $session_id);
              return Utilities::callback(
                $function, 
                func_get_args(), 
                $data,
                $this->session,
                $this->controls,
                $this->id
              );
            }
          };

          $data = $this->format($data, false, true);

          $session_id = session_id();
          $update_at = gmdate("Y-m-d H:i:s");
          $expire_at = gmdate("Y-m-d H:i:s", strtotime($this->config["session_duration"]));
          
          if (isset($remember) && (!empty($remember))) {
            if (!$this->update_device($data->id, $session_id)) {
              $device = new Device($this->session, 
                Utilities::override_controls(true, true, true, true)
              );
              $device_user_agent = $device->get_device();
              $device_parameters = array(
                "id" => null,
                "name" => "",
                "session" => $session_id,
                "token" => ((isset($device_info) && isset($device_info["token"])) ? $device_info["token"] : ""),
                "platform" => $device_user_agent->platform,
                "browser" => $device_user_agent->browser,
                "os" => $device_user_agent->os->name,
                "os_version" => $device_user_agent->os->version,
                "model" => ((isset($device_info) && isset($device_info["model"])) ? $device_info["model"] : ""),
                "manufacturer" => ((isset($device_info) && isset($device_info["manufacturer"])) ? $device_info["manufacturer"] : ""),
                "uuid" => ((isset($device_info) && isset($device_info["uuid"])) ? $device_info["uuid"] : ""),
                "user_agent" => $_SERVER["HTTP_USER_AGENT"],
                "is_mobile" => ($device_user_agent->is_mobile) ? true : false,
                "is_native" => ($device_user_agent->is_native) ? true : false,
                "is_virtual" => ((isset($device_info) && isset($device_info["is_virtual"])) ? true : false),
                "app_name" => ((isset($device_info) && isset($device_info["app_name"])) ? $device_info["app_name"] : ""),
                "app_id" => ((isset($device_info) && isset($device_info["app_id"])) ? $device_info["app_id"] : ""),
                "app_version" => ((isset($device_info) && isset($device_info["app_version"])) ? $device_info["app_version"] : ""),
                "app_build" => ((isset($device_info) && isset($device_info["app_build"])) ? $device_info["app_build"] : ""),
                "ip_recent" => $_SERVER["REMOTE_ADDR"],
                "update_at" => $update_at,
                "expire_at" => $expire_at,
                "active" => true,
                "user_id" => $data->id
              );
              if (
                $this->database->insert(
                  "device", 
                  $device_parameters, 
                  true,
                  false
                )
              ) {
                $this->log->log(
                  __FUNCTION__,
                  __METHOD__,
                  "low",
                  func_get_args(),
                  (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
                  "id",
                  $data->id
                );
                return $result($data, $session_id, $update_at, $expire_at, __METHOD__);
              } else {
                return false;
              }
            } else {
              return $result($data, $session_id, $update_at, $expire_at, __METHOD__);
            }
          } else {
            return $result($data, $session_id, $update_at, $expire_at, __METHOD__);
          }

        } else {
          throw new Exception($this->translation->translate("Invalid authorization"), 401);
        }

      } else {
        throw new Exception($this->translation->translate("Invalid authorization"), 401);
      }
    } else {
      return false;
    }
  }

  function logout($authorization = null) {

    $result = false;

    $message = $this->translation->translate("Invalid authorization");

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
              throw new Exception($message, 401);
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
              throw new Exception($message, 401);
            }
          } else {
            throw new Exception($message, 401);
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
            throw new Exception($message, 401);
          }
        } else {
          throw new Exception($message, 401);
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
            true,
            false
          )
        ) {
          $this->log->log(
            __FUNCTION__,
            __METHOD__,
            "low",
            func_get_args(),
            (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
            "id",
            $user_id
          );
          $result = true;
        }
      } else {
        throw new Exception($message, 401);
      }
    }

    return $result;

  }

  function authenticate($authorization = null, $throw_error = true) {

    $session = null;

    $message = $this->translation->translate("Invalid authorization");

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
                throw new Exception($message, 401);
              }
            }
          } else {
            if ($throw_error) {
              throw new Exception($message, 401);
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
                throw new Exception($message, 401);
              }
            }
          } else {
            if ($throw_error) {
              throw new Exception($message, 401);
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
              throw new Exception($message, 401);
            }
          }
        } else {
          if ($throw_error) {
            throw new Exception($message, 401);
          }
        }
      }
      
      if (
        isset($user_id) && !empty($user_id)
        && isset($session_id) && !empty($session_id)
      ) {
        try {
          $data = $this->database->select(
            "user", 
            "*", 
            array("id" => $user_id), 
            null, 
            true,
            false
          );
          if (!empty($data)) {
            try {
              $this->update_device($data->id, $session_id);
            } catch (Exception $e) {
              throw new Exception($e->getMessage(), 400);
            }
            $data->session_id = $session_id;
            $session = $this->format($data, false, true);
          } else {
            if ($throw_error) {
              throw new Exception($message, 401);
            }
          }
        } catch (Exception $e) {
          if ($throw_error) {
            if ($e->getCode() === 404) {
              throw new Exception($message, 401);
            } else {
              throw new Exception($e->getMessage(), $e->getCode());
            }
          }
        }
      } else {
        if ($throw_error) {
          throw new Exception($message, 401);
        }
      }
    } else {
      if ($throw_error) {
        throw new Exception($message, 401);
      }
    }

    return $session;

  }

  function signup($parameters, $user_id = 0) {

    $result = false;
      
    /* initialize: parameters */
    $parameters = $this->inform($parameters, false, $user_id);
    if (!isset($parameters["username"])) {
      $parameters["username"] = $this->random_username();
    }

    if ($this->validate($parameters)) {

      unset($parameters["confirm_password"]);
      $parameters["password"] = hash("sha256", md5($parameters["password"] . $this->config["password_salt"]));

      if (
        $data = $this->database->insert(
          "user", 
          $parameters, 
          true
        )
      ) {
        if (
          $data_update = $this->database->update(
            "user",
            array("user_id" => $data->id),
            array("id" => $data->id),
            null,
            true,
            false
          )
        ) {
          $data = $data_update[0];
          if (isset($this->config["signup_default_group"]) && !empty($this->config["signup_default_group"])) {
            $group_data = $this->database->select(
              "group", 
              array("id", "controls"), 
              array("id" => $this->config["signup_default_group"]), 
              null, 
              true,
              false
            );
            if (!empty($group_data)) {
              $member_parameters = array(
                "id" => null,
                "user" => $data->id,
                "group_id" => $group_data->id,
                "create_at" => $parameters["create_at"],
                "active" => true,
                "user_id" => $data->id
              );
              if (
                $this->database->insert(
                  "member", 
                  $member_parameters, 
                  true,
                  false
                )
              ) {
                $controls = unserialize($group_data->controls);
                if (is_array($controls) && !empty($controls)) {
                  $controls_parameters = array();
                  foreach ($controls as $controls_value) {
                    array_push($controls_parameters, array(
                      "id" => null,
                      "user" => $data->id,
                      "rules" => $controls_value["rules"],
                      "behaviors" => $controls_value["behaviors"],
                      "create_at" => $parameters["create_at"],
                      "active" => true,
                      "module_id" => $controls_value["module_id"],
                      "group_id" => $group_data->id,
                      "user_id" => $data->id
                    ));
                  }
                  if (
                    $this->database->insert_multiple(
                      "control", 
                      $controls_parameters, 
                      true,
                      false
                    )
                  ) {
                    $result = $data;
                  } else {
                    $this->database->delete(
                      "user", 
                      array("id" => $data->id), 
                      null, 
                      true,
                      false
                    );
                    $this->database->delete(
                      "member", 
                      array("user" => $data->id), 
                      null, 
                      true,
                      false
                    );
                  }
                } else {
                  $result = $data;
                }
              } else {
                $this->database->delete(
                  "user", 
                  array("id" => $data->id), 
                  null, 
                  true,
                  false
                );
              }
            } else {
              $this->database->delete(
                "user", 
                array("id" => $data->id), 
                null, 
                true,
                false
              );
            }
          } else {
            $result = $data;
          }
        } else {
          $this->database->delete(
            "user", 
            array("id" => $data->id), 
            null, 
            true,
            false
          );
        }
      }

    }

    if (!empty($result)) {

      if (!empty($result->email)) {

        try {

          $mail = new Mail($this->session, 
            Utilities::override_controls(true, true, true, true)
          );
  
          $mail_subject = 
          $this->translation->translate("Welcome to") 
          . " " 
          . $this->config["site_name"];
          $mail_body = 
          $result->name . ","
          . "\n\n"
          . $this->translation->translate("You have signed up at") 
          . " " 
          . $this->config["site_name"];
          if (!empty($template = $mail->template("welcome.php"))) {
            foreach (get_object_vars($result) as $key => $value) {
              if ($key != "password" && $key != "passcode") {
                $template = str_replace("{{" . $key . "}}", $value, $template);
              }
            }
            $mail_body = $template;
          }
          $mail->send(
            $this->config["email"], 
            $this->config["site_name"], 
            $result->email, 
            null,
            null,
            $mail_subject, 
            $mail_body
          );
  
          $hash = new Hash($this->session, 
            Utilities::override_controls(true, true, true, true)
          );
          $hash_parameters = array(
            "content" => $result->email,
            "active" => true
          );
          if (!empty($hash_data = $hash->create($hash_parameters))) {
            $result->hash_email = $hash_data->hash;
            $mail_subject = 
            $this->translation->translate("Verify your email at") 
            . " " 
            . $this->config["site_name"];
            $mail_body = 
            $result->name . ","
            . "\n\n"
            . $this->translation->translate("Verification code is") 
            . " " 
            . $hash_data->hash
            . "\n"
            . "(" . ($this->config["url_rewriting"] ? "api/user/verify?c=" : "api/?m=user&f=verify&c=") . ")"
            . $hash_data->hash;
            if (!empty($template = $mail->template("verify.php"))) {
              foreach (get_object_vars($result) as $key => $value) {
                if ($key != "password" && $key != "passcode") {
                  $template = str_replace("{{" . $key . "}}", $value, $template);
                }
              }
              $template = str_replace("{{hash}}", $hash_data->hash, $template);
              $mail_body = $template;
            }
            $mail->send(
              $this->config["email"], 
              $this->config["site_name"], 
              $result->email, 
              null,
              null,
              $mail_subject, 
              $mail_body
            );
          }
          
        } catch (Exception $e) {}

      }

      if (!empty($result->phone)) {
        $hash = new Hash($this->session, 
          Utilities::override_controls(true, true, true, true)
        );
        $hash_parameters = array(
          "content" => $result->phone,
          "active" => true
        );
        if (!empty($hash_data = $hash->create($hash_parameters))) {
          $result->hash_phone = $hash_data->hash;
        }
      }

      $this->log->log(
        __FUNCTION__,
        __METHOD__,
        "low",
        func_get_args(),
        (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
        "id",
        $result->id
      );
      return Utilities::callback(
        __METHOD__, 
        func_get_args(), 
        $this->format($result, false, true),
        $this->session,
        $this->controls,
        $this->id
      );

    } else {
      return false;
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
        "user", 
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
    
    if (!empty($filters) && isset($filters["groups"])) {
      $groups = $filters["groups"];
      unset($filters["groups"]);
    }

    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      if (isset($active)) {
        $filters["active"] = $active;
      }
      $list = array();
      if (empty($groups)) {
        $list = $this->database->select_multiple(
          "user", 
          "*", 
          $filters, 
          $extensions, 
          $start, 
          $limit, 
          $sort_by, 
          $sort_direction, 
          $this->controls["view"]
        );
      } else {
    
        $start = !empty($start) ? $start : null;
        $limit = !empty($limit) ? $limit : null;
        $sort_by = !empty($sort_by) ? $sort_by : "id";
        $sort_direction = !empty($sort_direction) ? $sort_direction : "desc";
        $active = !empty($active) ? $active : null;
        $filters = is_array($filters) ? $filters : array();
        $extensions = is_array($extensions) ? $extensions : array();
        $fetch_type = !empty($fetch_type) ? $fetch_type : "assoc";
        $controls_view = !empty($controls_view) ? $controls_view : false;
        $controls = $this->controls["view"];
        
        if (!empty($controls)) {
    
          $connection = $this->database->connect();
          if (!empty($connection)) {
            
            $error = false;
    
            $start = $this->database->escape_string($start, $connection);
            $limit = $this->database->escape_string($limit, $connection);
            $sort_by = $this->database->escape_string($sort_by, $connection);
            $sort_direction = $this->database->escape_string($sort_direction, $connection);
    
            $conditions = $this->database->condition(
              $this->database->escape_string($filters, $connection), 
              $this->database->escape_string($extensions, $connection), 
              $this->database->escape_string($controls, $connection),
              "user"
            );
    
            $query = 
            "SELECT `user`.* FROM `user` 
            LEFT JOIN `member` ON (`user`.`id` = `member`.`user`) 
            " . (!empty($conditions) ? $conditions . " AND " : " WHERE ") .
            "(" . implode(" OR ", array_map(function ($group_id) { return  "`member`.`group_id` = '" . $group_id . "'"; }, $groups)) . ") 
            GROUP BY `user`.`id` "
            . $this->database->order($sort_by, $sort_direction) . " " . $this->database->limit($start, $limit) . ";";
            if ($result = mysqli_query($connection, $query)) {
              $rows = array();
              if ($fetch_type == "assoc") {
                while($row = $result->fetch_assoc()) {
                  array_push($rows, (object) $row);
                }
              } else {
                while($row = $result->fetch_array()) {
                  array_push($rows, (object) $row);
                }
              }
              $list = $rows;
              mysqli_free_result($result);
            } else {
              $error = true;
            }
    
            $this->database->disconnect($connection);
    
            if ($error) {
              throw new Exception($this->translation->translate("Database encountered error"), 409);
            }
    
          } else {
            throw new Exception($this->translation->translate("Unable to connect to database"), 500);
          }
    
        } else {
          throw new Exception($this->translation->translate("Permission denied"), 403);
        }

      }
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
    
    if (!empty($filters) && isset($filters["groups"])) {
      $groups = $filters["groups"];
      unset($filters["groups"]);
    }

    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      if (isset($active)) {
        $filters["active"] = $active;
      }

      $data = 0;

      if (empty($groups)) {
        $data = $this->database->count(
          "user", 
          $filters, 
          $extensions, 
          $start, 
          $limit, 
          $this->controls["view"]
        );
      } else {
    
        $start = !empty($start) ? $start : null;
        $limit = !empty($limit) ? $limit : null;
        $sort_by = !empty($sort_by) ? $sort_by : "id";
        $sort_direction = !empty($sort_direction) ? $sort_direction : "desc";
        $active = !empty($active) ? $active : null;
        $filters = is_array($filters) ? $filters : array();
        $extensions = is_array($extensions) ? $extensions : array();
        $fetch_type = !empty($fetch_type) ? $fetch_type : "assoc";
        $controls_view = !empty($controls_view) ? $controls_view : false;
        $controls = $this->controls["view"];
        
        if (!empty($controls)) {
    
          $connection = $this->database->connect();
          if (!empty($connection)) {
            
            $error = false;
    
            $start = $this->database->escape_string($start, $connection);
            $limit = $this->database->escape_string($limit, $connection);
            $sort_by = $this->database->escape_string($sort_by, $connection);
            $sort_direction = $this->database->escape_string($sort_direction, $connection);
    
            $conditions = $this->database->condition(
              $this->database->escape_string($filters, $connection), 
              $this->database->escape_string($extensions, $connection), 
              $this->database->escape_string($controls, $connection),
              "user"
            );
            
            $query = 
            "SELECT NULL FROM `user` 
            LEFT JOIN `member` ON (`user`.`id` = `member`.`user`) 
            " . (!empty($conditions) ? $conditions . " AND " : " WHERE ") .
            "(" . implode(" OR ", array_map(function ($group_id) { return  "`member`.`group_id` = '" . $group_id . "'"; }, $groups)) . ") 
            GROUP BY `user`.`id` "
            . $this->database->limit($start, $limit) . ";";
            
            if ($result = mysqli_query($connection, $query)) {
              $data = mysqli_num_rows($result);
              mysqli_free_result($result);
            } else {
              $error = true;
            }
    
            $this->database->disconnect($connection);
    
            if ($error) {
              throw new Exception($this->translation->translate("Database encountered error"), 409);
            }
    
          } else {
            throw new Exception($this->translation->translate("Unable to connect to database"), 500);
          }
    
        } else {
          throw new Exception($this->translation->translate("Permission denied"), 403);
        }

      }

      if (!empty($data)) {
        return $data;
      } else {
        return 0;
      }

    } else {
      return false;
    }
  }

  function create($parameters, $user_id = 0, $files) {

    $result = false;
      
    /* initialize: parameters */
    $parameters = $this->inform($parameters, false, $user_id);
    $groups = null;
    if (isset($parameters["groups"])) {
      $groups = array();
      if (!empty($parameters["groups"])) {
        $groups = (!is_array($parameters["groups"]) ? explode(",", $parameters["groups"]) : $parameters["groups"]);
      }
    }

    if ($this->validate($parameters)) {
    
      unset($parameters["confirm_password"]);
      unset($parameters["groups"]);
      $parameters["password"] = hash("sha256", md5($parameters["password"] . $this->config["password_salt"]));

      $data = $this->database->insert(
        "user", 
        $parameters, 
        $this->controls["create"]
      );
      if (!empty($data)) {
        $error = false;
        if (!empty($files)) {
          try {
            $this->upload($data->id, $files);
          } catch (Exception $e) {
            $error = true;
          }
        }
        if (!is_null($groups)) {
          if (
            !empty($this->session) 
            && isset($this->session->controls)
            && isset($this->session->controls[7])
            && isset($this->session->controls[7]["update"])
            && $this->session->controls[7]["update"] === true
          ) {
            $errors = array();
            $group = new Group(
              $this->session, 
              Utilities::override_controls(
                $this->controls["create"], 
                $this->controls["create"], 
                $this->controls["create"], 
                $this->controls["create"]
              )
            );
            foreach ($groups as $group_id) {
              try {
                $group->add_member($group_id, $data->id);
              } catch (Exception $e) {
                array_push($errors, $group_id);
              }
            }
            if (empty($errors)) {
              if (!$error || empty($files)) {
                $result = $data;
              } else {
                throw new Exception($this->translation->translate("Unable to upload"), 409);
              }
            } else {
              throw new Exception($this->translation->translate("Unable to update member") . " '" . implode("', '", $errors) . "'", 409);
            }
          } else {
            throw new Exception($this->translation->translate("Permission denied"), 403);
          }
        } else {
          if (!$error || empty($files)) {
            $result = $data;
          } else {
            throw new Exception($this->translation->translate("Unable to upload"), 409);
          }
        }
      } else {
        $result = $data;
      }

    }

    if (!empty($result)) {

      if (!empty($result->email)) {

        try {

          $mail = new Mail($this->session, 
            Utilities::override_controls(true, true, true, true)
          );

          $mail_subject = 
          $this->translation->translate("Created account at") 
          . " " 
          . $this->config["site_name"];
          $mail_body = 
          $result->name . ","
          . "\n\n"
          . $this->translation->translate("You have created account at") 
          . " " 
          . $this->config["site_name"];
          if (!empty($template = $mail->template("user-created.php"))) {
            foreach (get_object_vars($result) as $key => $value) {
              if ($key != "password" && $key != "passcode") {
                $template = str_replace("{{" . $key . "}}", $value, $template);
              }
            }
            $mail_body = $template;
          }
          $mail->send(
            $this->config["email"], 
            $this->config["site_name"], 
            $result->email, 
            null,
            null,
            $mail_subject, 
            $mail_body
          );

          $hash = new Hash($this->session, 
            Utilities::override_controls(true, true, true, true)
          );
          $hash_parameters = array(
            "content" => $result->email,
            "active" => true
          );
          if (!empty($hash_data = $hash->create($hash_parameters))) {
            $result->hash_email = $hash_data->hash;
            $mail_subject = 
            $this->translation->translate("Verify your email at") 
            . " " 
            . $this->config["site_name"];
            $mail_body = 
            $result->name . ","
            . "\n\n"
            . $this->translation->translate("Verification code is") 
            . " " 
            . $hash_data->hash
            . "\n"
            . "(" . ($this->config["url_rewriting"] ? "api/user/verify?c=" : "api/?m=user&f=verify&c=") . ")"
            . $hash_data->hash;
            if (!empty($template = $mail->template("verify.php"))) {
              foreach (get_object_vars($result) as $key => $value) {
                if ($key != "password" && $key != "passcode") {
                  $template = str_replace("{{" . $key . "}}", $value, $template);
                }
              }
              $template = str_replace("{{hash}}", $hash_data->hash, $template);
              $mail_body = $template;
            }
            $mail->send(
              $this->config["email"], 
              $this->config["site_name"], 
              $result->email, 
              null,
              null,
              $mail_subject, 
              $mail_body
            );
          }

        } catch (Exception $e) {}

      }

      if (!empty($result->phone)) {
        $hash = new Hash($this->session, 
          Utilities::override_controls(true, true, true, true)
        );
        $hash_parameters = array(
          "content" => $result->phone,
          "active" => true
        );
        if (!empty($hash_data = $hash->create($hash_parameters))) {
          $result->hash_phone = $hash_data->hash;
        }
      }

      $this->log->log(
        __FUNCTION__,
        __METHOD__,
        "normal",
        func_get_args(),
        (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
        "id",
        $result->id
      );
      return Utilities::callback(
        __METHOD__, 
        func_get_args(), 
        $this->format($result),
        $this->session,
        $this->controls,
        $this->id
      );

    } else {
      return false;
    }

  }

  function update($id, $parameters, $files) {

    $result = false;

    /* initialize: parameters */
    $parameters = $this->inform($parameters, true);
    $groups = null;
    if (isset($parameters["groups"])) {
      $groups = array();
      if (!empty($parameters["groups"])) {
        $groups = (!is_array($parameters["groups"]) ? explode(",", $parameters["groups"]) : $parameters["groups"]);
      }
    }

    if (
      $this->validation->require($id, "ID")
      && $this->validate($parameters, $id)
    ) {
      $data = $this->database->update(
        "user", 
        $parameters, 
        array("id" => $id), 
        null, 
        $this->controls["update"]
      );
      if (!empty($data)) {
        $data = $data[0];
        $error = false;
        if (!empty($files)) {
          try {
            $this->upload($data->id, $files);
          } catch (Exception $e) {
            $error = true;
          }
        }
        if (!is_null($groups)) {
          if (
            !empty($this->session) 
            && isset($this->session->controls)
            && isset($this->session->controls[7])
            && isset($this->session->controls[7]["update"])
            && $this->session->controls[7]["update"] === true
          ) {
            if (!empty(
              $data_current = $this->get($data->id, null, array("members"))
            )) {
              $errors = array();
              $group = new Group(
                $this->session, 
                Utilities::override_controls(
                  true, 
                  true, 
                  true, 
                  true
                )
              );
              foreach ($groups as $group_id) {
                if (
                  !in_array(
                    $group_id,
                    array_map(
                      function($value) {
                        return $value->group_id;
                      }, $data_current->members
                    )
                  )
                ) {
                  try {
                    $group->add_member($group_id, $data->id);
                  } catch (Exception $e) {
                    array_push($errors, $group_id);
                  }
                }
              }
              foreach ($data_current->members as $member) {
                if (!in_array($member->group_id, $groups)) {
                  try {
                    $group->remove_member($member->group_id, $data->id);
                  } catch (Exception $e) {
                  }
                }
              }
              if (empty($errors)) {
                if (!$error || empty($files)) {
                  $result = $data;
                } else {
                  throw new Exception($this->translation->translate("Unable to upload"), 409);
                }
              } else {
                throw new Exception($this->translation->translate("Unable to update member") . " '" . implode("', '", $errors) . "'", 409);
              }
            } else {
              throw new Exception($this->translation->translate("Not exist or gone"), 410);
            }
          } else {
            throw new Exception($this->translation->translate("Permission denied"), 403);
          }
        } else {
          if (!$error || empty($files)) {
            $result = $data;
          } else {
            throw new Exception($this->translation->translate("Unable to upload"), 409);
          }
        }
      }
    }

    if (!empty($result)) {

      if (
        isset($parameters["email"]) && !empty($parameters["email"])
        && $this->session && $this->session->email != $parameters["email"]
      ) {
        $hash = new Hash($this->session, 
          Utilities::override_controls(true, true, true, true)
        );
        $hash_parameters = array(
          "content" => $parameters["email"],
          "active" => true
        );
        try {
          $mail = new Mail($this->session, 
            Utilities::override_controls(true, true, true, true)
          );
          if (!empty($hash_data = $hash->create($hash_parameters))) {
            $result->hash_email = $hash_data->hash;
            $mail_subject = 
            $this->translation->translate("Verify your email at") 
            . " " 
            . $this->config["site_name"];
            $mail_body = 
            $result->name . ","
            . "\n\n"
            . $this->translation->translate("Verification code is") 
            . " " 
            . $hash_data->hash
            . "\n"
            . "(" . ($this->config["url_rewriting"] ? "api/user/verify?c=" : "api/?m=user&f=verify&c=") . ")"
            . $hash_data->hash;
            if (!empty($template = $mail->template("verify.php"))) {
              foreach (get_object_vars($result) as $key => $value) {
                if ($key != "password" && $key != "passcode") {
                  $template = str_replace("{{" . $key . "}}", $value, $template);
                }
              }
              $template = str_replace("{{hash}}", $hash_data->hash, $template);
              $mail_body = $template;
            }
            $mail->send(
              $this->config["email"], 
              $this->config["site_name"], 
              $parameters["email"], 
              null,
              null,
              $mail_subject, 
              $mail_body
            );
          }
        } catch (Exception $e) {}

      }

      if (
        isset($parameters["phone"]) && !empty($parameters["phone"])
        && $this->session && $this->session->phone != $parameters["phone"]
      ) {
        $hash = new Hash($this->session, 
          Utilities::override_controls(true, true, true, true)
        );
        $hash_parameters = array(
          "content" => $parameters["phone"],
          "active" => true
        );
        if (!empty($hash_data = $hash->create($hash_parameters))) {
          $result->hash_phone = $hash_data->hash;
        }
      }

      $this->log->log(
        __FUNCTION__,
        __METHOD__,
        "normal",
        func_get_args(),
        (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
        "id",
        $result->id
      );
      return Utilities::callback(
        __METHOD__, 
        func_get_args(), 
        $this->format($result),
        $this->session,
        $this->controls,
        $this->id
      );

    } else {
      return $result;
    }

  }

  function patch($id, $parameters, $files) {

    $result = false;

    /* initialize: parameters */
    $parameters = $this->inform($parameters, true);
    $groups = null;
    if (isset($parameters["groups"])) {
      $groups = array();
      if (!empty($parameters["groups"])) {
        $groups = (!is_array($parameters["groups"]) ? explode(",", $parameters["groups"]) : $parameters["groups"]);
      }
    }
    
    if (
      $this->validation->require($id, "ID")
      && $this->validate($parameters, $id, true)
    ) {

      $error = false;

      $data = $this->database->update(
        "user", 
        $parameters, 
        array("id" => $id), 
        null, 
        $this->controls["update"]
      );
      if (!empty($data)) {
        $data = $data[0];
        $error = false;
        if (!empty($files)) {
          try {
            $this->upload($data->id, $files);
          } catch (Exception $e) {
            $error = true;
          }
        }
        if (!is_null($groups)) {
          if (
            !empty($this->session) 
            && isset($this->session->controls)
            && isset($this->session->controls[7])
            && isset($this->session->controls[7]["update"])
            && $this->session->controls[7]["update"] === true
          ) {
            if (!empty(
              $data_current = $this->get($data->id, null, array("members"))
            )) {
              $errors = array();
              $group = new Group(
                $this->session, 
                Utilities::override_controls(
                  true, 
                  true, 
                  true, 
                  true
                )
              );
              foreach ($groups as $group_id) {
                if (
                  !in_array(
                    $group_id,
                    array_map(
                      function($value) {
                        return $value->group_id;
                      }, $data_current->members
                    )
                  )
                ) {
                  try {
                    $group->add_member($group_id, $data->id);
                  } catch (Exception $e) {
                    array_push($errors, $group_id);
                  }
                }
              }
              foreach ($data_current->members as $member) {
                if (!in_array($member->group_id, $groups)) {
                  try {
                    $group->remove_member($member->group_id, $data->id);
                  } catch (Exception $e) {
                  }
                }
              }
              if (empty($errors)) {
                if (!$error || empty($files)) {
                  $result = $data;
                } else {
                  throw new Exception($this->translation->translate("Unable to upload"), 409);
                }
              } else {
                throw new Exception($this->translation->translate("Unable to update member") . " '" . implode("', '", $errors) . "'", 409);
              }
            } else {
              throw new Exception($this->translation->translate("Not exist or gone"), 410);
            }
          } else {
            throw new Exception($this->translation->translate("Permission denied"), 403);
          }
        } else {
          if (!$error || empty($files)) {
            $result = $data;
          } else {
            throw new Exception($this->translation->translate("Unable to upload"), 409);
          }
        }
      }
    }

    if (!empty($result)) {

      if (
        isset($parameters["email"]) && !empty($parameters["email"])
        && $this->session && $this->session->email != $parameters["email"]
      ) {
        $hash = new Hash($this->session, 
          Utilities::override_controls(true, true, true, true)
        );
        $hash_parameters = array(
          "content" => $parameters["email"],
          "active" => true
        );
        try {
          $mail = new Mail($this->session, 
            Utilities::override_controls(true, true, true, true)
          );
          if (!empty($hash_data = $hash->create($hash_parameters))) {
            $result->hash_email = $hash_data->hash;
            $mail_subject = 
            $this->translation->translate("Verify your email at") 
            . " " 
            . $this->config["site_name"];
            $mail_body = 
            $result->name . ","
            . "\n\n"
            . $this->translation->translate("Verification code is") 
            . " " 
            . $hash_data->hash
            . "\n"
            . "(" . ($this->config["url_rewriting"] ? "api/user/verify?c=" : "api/?m=user&f=verify&c=") . ")"
            . $hash_data->hash;
            if (!empty($template = $mail->template("verify.php"))) {
              foreach (get_object_vars($result) as $key => $value) {
                if ($key != "password" && $key != "passcode") {
                  $template = str_replace("{{" . $key . "}}", $value, $template);
                }
              }
              $template = str_replace("{{hash}}", $hash_data->hash, $template);
              $mail_body = $template;
            }
            $mail->send(
              $this->config["email"], 
              $this->config["site_name"], 
              $parameters["email"], 
              null,
              null,
              $mail_subject, 
              $mail_body
            );
          }
        } catch (Exception $e) {}
      }

      if (
        isset($parameters["phone"]) && !empty($parameters["phone"])
        && $this->session && $this->session->phone != $parameters["phone"]
      ) {
        $hash = new Hash($this->session, 
          Utilities::override_controls(true, true, true, true)
        );
        $hash_parameters = array(
          "content" => $parameters["phone"],
          "active" => true
        );
        if (!empty($hash_data = $hash->create($hash_parameters))) {
          $result->hash_phone = $hash_data->hash;
        }
      }

      $this->log->log(
        __FUNCTION__,
        __METHOD__,
        "normal",
        func_get_args(),
        (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
        "id",
        $result->id
      );
      return Utilities::callback(
        __METHOD__, 
        func_get_args(), 
        $this->format($result),
        $this->session,
        $this->controls,
        $this->id
      );

    } else {
      return $result;
    }

  }

  function delete($id) {
    if ($this->validation->require($id, "ID")) {
      $data = $this->database->delete(
        "user", 
        array("id" => $id), 
        null, 
        $this->controls["delete"]
      );
      if (!empty($data)) {

        $data = $data[0];

        $file_old = Utilities::backtrace() . trim($data->image, "/");
        if (!empty($data->image) && file_exists($file_old)) {
          try {
            chmod($file_old, 0777);
          } catch (Exception $e) {}
          try {
            unlink($file_old);
          } catch (Exception $e) {}
        }

        try {
          $this->database->delete(
            "member", 
            array("user" => $id), 
            null, 
            true
          );
        } catch (Exception $e) {}

        try {
          $this->database->delete(
            "control", 
            array("user" => $id), 
            null, 
            true
          );
        } catch (Exception $e) {}

        try {
          $this->database->delete(
            "device", 
            array("user_id" => $id), 
            null, 
            true
          );
        } catch (Exception $e) {}

        try {
          $this->database->delete(
            "connect", 
            array("user_id" => $id), 
            null, 
            true
          );
        } catch (Exception $e) {}

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

  function upload($id, $files, $input_multiple = null) {
    if ($this->validation->require($id, "ID")) {
      
      $data_current = $this->database->select(
        (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
        "*", 
        array("id" => $id), 
        null, 
        (
          ($this->controls["create"] === true || $this->controls["update"] === true) ?
          true : 
          array_merge($this->controls["create"], $this->controls["update"])
        )
      );
      if (!empty($data_current)) {
        
        $upload = function(
          $data_target, 
          $destination = "", 
          $name, 
          $type, 
          $tmp_name, 
          $error, 
          $size
        ) {
          
          $image_options = array(
            "quality" => (
              (isset($this->config["image_quality"]) ? $this->config["image_quality"] : 75)
            ),
            "thumbnail" => (
              (isset($this->config["image_thumbnail"]) ? $this->config["image_thumbnail"] : true)
            ),
            "thumbnail_aspectratio" => (
              (isset($this->config["image_thumbnail_aspectratio"]) ? $this->config["image_thumbnail_aspectratio"] : 75)
            ),
            "thumbnail_quality" => (
              (isset($this->config["image_thumbnail_quality"]) ? $this->config["image_thumbnail_quality"] : 75)
            ),
            "thumbnail_width" => (
              (isset($this->config["image_thumbnail_width"]) ? $this->config["image_thumbnail_width"] : 200)
            ),
            "thumbnail_height" => (
              (isset($this->config["image_thumbnail_height"]) ? $this->config["image_thumbnail_height"] : 200)
            ),
            "large" => (
              (isset($this->config["image_large"]) ? $this->config["image_large"] : true)
            ),
            "large_aspectratio" => (
              (isset($this->config["image_large_aspectratio"]) ? $this->config["image_large_aspectratio"] : 75)
            ),
            "large_quality" => (
              (isset($this->config["image_large_quality"]) ? $this->config["image_large_quality"] : 75)
            ),
            "large_width" => (
              (isset($this->config["image_large_width"]) ? $this->config["image_large_width"] : 400)
            ),
            "large_height" => (
              (isset($this->config["image_large_height"]) ? $this->config["image_large_height"] : 400)
            )
          );
          
          $info = pathinfo($name);
          $extension = strtolower(isset($info["extension"]) ? $info["extension"] : null);
          if (empty($extension)) {
            if (strpos($type, "image/") === 0) {
              $extension = str_replace("image/", "", $type);
            }
          }
          $basename = $info["basename"];
          $file_name = $basename . "." . $extension;
          $name_encrypted = gmdate("YmdHis") . "_" . $data_target->id . "_" . uniqid();
          $file = $name_encrypted . "." . $extension;
    
          $media_directory = "/" . trim((isset($this->config["media_path"]) ? $this->config["media_path"] : "media"), "/") . "/";
          $media_directory_path = Utilities::backtrace() . trim($media_directory, "/") . "/";

          $upload_directory = $media_directory . trim($destination, "/") . "/";
          $upload_directory_path = $media_directory_path . trim($destination, "/") . "/";
          if (!file_exists($media_directory_path)) {
            mkdir($media_directory_path, 0777, true);
          }
          if (!file_exists($upload_directory_path)) {
            mkdir($upload_directory_path, 0777, true);
          }
          $destination = $upload_directory_path . $file;
          $path = $upload_directory . $file;
    
          if (file_exists($upload_directory_path)) {
            $upload_result = false;
            $unsupported_image = false;
            try {
              $upload_result = Utilities::create_image(
                $tmp_name, 
                $destination, 
                true,
                640, 
                640, 
                null, 
                $image_options["quality"]
              );
            } catch(Exception $e) {
              if ($e->getCode() == 415 || $e->getCode() == 500) {
                $upload_result = move_uploaded_file($tmp_name, $destination);
                $unsupported_image = true;
              }
            }
            if ($upload_result) {
              
              $parameter = $path;
              $parameters = array(
                "image" => $parameter
              );
              if (empty($input_multiple)) {
                $update_data = $this->database->update(
                  (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : "user"), 
                  $parameters, 
                  array("id" => $data_target->id), 
                  null, 
                  (
                    ($this->controls["create"] === true || $this->controls["update"] === true) ?
                    true : 
                    array_merge($this->controls["create"], $this->controls["update"])
                  )
                );
              }
              if (!empty($update_data) || !empty($input_multiple)) {

                if (!empty($image_options["thumbnail"]) && !$unsupported_image) {
                  $thumbnail_directory_path	= $upload_directory_path . "thumbnail/";
                  if (!file_exists($thumbnail_directory_path)) {
                    mkdir($thumbnail_directory_path, 0777, true);
                  }
                  $thumbnail = $thumbnail_directory_path . $file;
                  Utilities::create_image(
                    $destination, 
                    $thumbnail, 
                    true,
                    $image_options["thumbnail_width"], 
                    $image_options["thumbnail_height"], 
                    $image_options["thumbnail_aspectratio"], 
                    $image_options["thumbnail_quality"]
                  );
                }
                if (!empty($image_options["large"]) && !$unsupported_image) {
                  $large_directory_path	= $upload_directory_path . "large/";
                  if (!file_exists($large_directory_path)) {
                    mkdir($large_directory_path, 0777, true);
                  }
                  $large = $large_directory_path . $file;
                  Utilities::create_image(
                    $destination, 
                    $large, 
                    true,
                    $image_options["large_width"], 
                    $image_options["large_height"], 
                    $image_options["large_aspectratio"], 
                    $image_options["large_quality"]
                  );
                }
                
                return $path;

              } else {
                if (file_exists($destination) && !is_dir($destination)) {
                  try {
                    chmod($destination, 0777);
                  } catch (Exception $e) {}
                  try {
                    unlink($destination);
                  } catch (Exception $e) {}
                }
                return false;
              }

            } else {
              return false;
            }
          } else {
            return false;
          }
        };

        $successes = array();
        $errors = array();

        if (isset($files["image"]) && isset($files["image"]["name"])) {
          if (isset($data_current->image) && !empty($data_current->image)) {
            try {
              $this->remove($data_current->id, array("image" => $data_current->image));
            } catch (Exception $e) {
              
            };
          }
          if (
            $path_id = $upload(
              $data_current,
              "user",
              $files["image"]["name"],
              $files["image"]["type"],
              $files["image"]["tmp_name"],
              $files["image"]["error"],
              $files["image"]["size"]
            )
          ) {
            $path = (object) array(
              "id" => $path_id,
              "name" => basename($path_id),
              "path" => null
            );
            if (strpos($path_id, "http://") !== 0 || strpos($path_id, "https://") !== 0) {
              $path->path = $this->config["base_url"] . $path_id;
            }
            array_push($successes, array(
              "source" => $files["image"]["name"],
              "destination" => $path_id
            ));
          } else {
            array_push($errors, $files["image"]["name"]);
          }
        }
        
        if (empty($errors)) {
          if (!empty($successes)) {
            return Utilities::callback(
              __METHOD__, 
              func_get_args(), 
              $successes,
              $this->session,
              $this->controls,
              $this->id
            );
          } else {
            throw new Exception($this->translation->translate("No file has been uploaded"), 409);
          }
        } else {
          throw new Exception($this->translation->translate("Unable to upload") . " '" . implode("', '", $errors) . "'", 409);
        }

      } else {
        return $data_current;
      }

    } else {
      return false;
    }
  }
  
  function remove($id, $parameters) {
    if ($this->validation->require($id, "ID")) {
      if (!empty($parameters)) {
        
        if (!empty(
          $data_current = $this->database->select(
            (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
            "*", 
            array("id" => $id), 
            null, 
            (
              ($this->controls["create"] === true || $this->controls["update"] === true || $this->controls["delete"] === true) ?
              true : 
              array_merge($this->controls["create"], $this->controls["update"], $this->controls["delete"])
            )
          )
        )) {

          $delete = function($file) {
            try {
              $file_old = Utilities::backtrace() . trim($file, "/");
              if (!empty($file) && file_exists($file_old)) {
                try {
                  chmod($file_old, 0777);
                } catch (Exception $e) {}
                try {
                  unlink($file_old);
                } catch (Exception $e) {}
              }
              $thumbnail_old = Utilities::get_thumbnail($file_old);
              if (file_exists($thumbnail_old) && !is_dir($thumbnail_old)) {
                try {
                  chmod($thumbnail_old, 0777);
                } catch (Exception $e) {}
                try {
                  unlink($thumbnail_old);
                } catch (Exception $e) {}
              }
              $large_old = Utilities::get_large($file_old);
              if (file_exists($large_old) && !is_dir($large_old)) {
                try {
                  chmod($large_old, 0777);
                } catch (Exception $e) {}
                try {
                  unlink($large_old);
                } catch (Exception $e) {}
              }
              return true;
            } catch(Exception $e) {
              return false;
            }
          };

          $successes = array();
          $errors = array();

          if (isset($parameters["image"])) {

            if (is_array($parameters["image"])) {
              foreach ($parameters["image"] as $file) {
                if ($delete($file)) {
                  array_push($successes, $file);
                } else {
                  array_push($errors, $file);
                }
              }
            } else {
              if ($delete($parameters["image"])) {
                array_push($successes, $parameters["image"]);
              } else {
                array_push($errors, $parameters["image"]);
              }
            }

            if (count($successes)) {
              $parameter = "";
              $parameters = array(
                "image" => $parameter
              );
              $this->database->update(
                (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : "user"), 
                $parameters, 
                array("id" => $data_current->id), 
                null, 
                (
                  ($this->controls["create"] === true || $this->controls["update"] === true || $this->controls["delete"] === true) ?
                  true : 
                  array_merge($this->controls["create"], $this->controls["update"], $this->controls["delete"])
                )
              );
              
            }

          }

          if (empty($errors)) {
            return Utilities::callback(
              __METHOD__, 
              func_get_args(), 
              $successes,
              $this->session,
              $this->controls,
              $this->id
            );
          } else {
            throw new Exception($this->translation->translate("Unable to delete") . " '" . implode("', '", $errors) . "'", 409);
          }

        } else {
          throw new Exception($this->translation->translate("Not exist or gone"), 410);
        }

      } else {
        throw new Exception($this->translation->translate("File(s) not found"), 400);
      }
    } else {
      return false;
    }
  }

  function update_password(
    $username = null, 
    $password = null, 
    $password_new = null, 
    $confirm_password_new = null
  ) {

    if (
      (
        !empty($username) 
        || (!empty($this->session) && !empty($this->session->username))
      )
    ) {

      if (empty($username)) {
        $username = $this->session->username;
      }
      
      $filters = array(
        "active" => true
      );
      $extensions = array(
        array(
          "conjunction" => "",
          "key" => "username",
          "operator" => "=",
          "value" => "'" . $username . "'",
        ),
        array(
          "conjunction" => "OR",
          "key" => "email",
          "operator" => "=",
          "value" => "'" . $username . "'",
        ),
        array(
          "conjunction" => "OR",
          "key" => "phone",
          "operator" => "=",
          "value" => "'" . $username . "'",
        )
      );
      $user_data = $this->database->select(
        "user", 
        "*", 
        $filters, 
        $extensions, 
        true,
        false
      );
      if (!empty($user_data)) {

        $password_hash = hash("sha256", md5($password . $this->config["password_salt"]));
        if ($this->session->password_set) {

          $filters["password"] = hash("sha256", md5($password . $this->config["password_salt"]));

          $data_password = $this->database->select(
            "user", 
            "*", 
            $filters, 
            $extensions, 
            true,
            false
          );
          
          $data_hash = $this->database->select(
            "hash", 
            "*", 
            array(
              "hash" => $password_hash,
              "content" => $user_data->id
            ), 
            null, 
            true,
            false
          );

        }

        if ((!empty($data_password) || !empty($data_hash)) || !$this->session->password_set) {

          if (!empty($user_data->email) || !empty($user_data->phone)) {

            if (
              $this->validation->require($password, "Current Password")
              && $this->validation->require($password_new, "New Password")
              && $this->validation->string_min($password_new, "New Password", 8)
              && $this->validation->string_max($password_new, "New Password", 100)
              && $this->validation->password($password_new, "New Password")
              && $this->validation->password_equal_to($password_new, "New Password", $confirm_password_new)
            ) {
        
              $data = $this->database->update(
                "user", 
                array(
                  "password" => hash(
                    "sha256", 
                    md5($password_new . $this->config["password_salt"])
                  )
                ), 
                array("id" => $user_data->id), 
                null, 
                true
              );
              if (!empty($data)) {

                $data = $data[0];

                if (!empty($data_hash)) {
                  try {
                    $this->database->delete(
                      "hash", 
                      array(
                        "id" => $data_hash->id
                      )
                    );
                  } catch (Exception $e) {}
                }
        
                if (!empty($user_data->email)) {
                  try {
                    $mail = new Mail($this->session, 
                      Utilities::override_controls(true, true, true, true)
                    );
                    $mail_subject = 
                    $this->translation->translate("You have updated password at") 
                    . " " 
                    . $this->config["site_name"];
                    $mail_body = 
                    $user_data->name . ","
                    . "\n\n"
                    . $this->translation->translate("You have updated password at") 
                    . " " 
                    . $this->config["site_name"];
                    if (!empty($template = $mail->template("password-updated.php"))) {
                      foreach (get_object_vars($user_data) as $key => $value) {
                        if ($key != "password" && $key != "passcode") {
                          $template = str_replace("{{" . $key . "}}", $value, $template);
                        }
                      }
                      $mail_body = $template;
                    }
                    $mail->send(
                      $this->config["email"], 
                      $this->config["site_name"], 
                      $user_data->email, 
                      null,
                      null,
                      $mail_subject, 
                      $mail_body
                    );
                  } catch (Exception $e) {}
                }
        
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
        
            }
        
          } else {
            throw new Exception(
              $this->translation->translate("Email or Phone is required to reset password"), 
              400
            );
          }

        } else {
          throw new Exception("Invalid authorization", 401);
        }

      } else {
        throw new Exception("Invalid authorization", 401);
      }
      
    } else {
      throw new Exception(
        $this->translation->translate("Username or Email is required to update password"), 
        400
      );
    }
  }

  function reset_password($username) {
    if (!empty($email) || !empty($phone)) {

      $filters = array(
        "active" => true
      );
      $extensions = array(
        array(
          "conjunction" => "",
          "key" => "username",
          "operator" => "=",
          "value" => "'" . $username . "'",
        ),
        array(
          "conjunction" => "OR",
          "key" => "email",
          "operator" => "=",
          "value" => "'" . $username . "'",
        ),
        array(
          "conjunction" => "OR",
          "key" => "phone",
          "operator" => "=",
          "value" => "'" . $username . "'",
        )
      );
      if (!empty(
        $user_data = $this->database->select(
          "user", 
          "*", 
          $filters, 
          $extensions, 
          true
        )
      )) {

        $seed_alphabet = str_split(
          "abcdefghijklmnopqrstuvwxyz"
          . "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
        );
        shuffle($seed_alphabet);
        $password_alphabet = "";
        foreach (array_rand($seed_alphabet, 5) as $k) $password_alphabet .= $seed_alphabet[$k];
  
        $seed_number = str_split("0123456789");
        shuffle($seed_number);
        $password_number = "";
        foreach (array_rand($seed_number, 5) as $k) $password_number .= $seed_number[$k];
  
        $seed_special = str_split("!#$%&()*+,-./:;<=>?@[\]^_{|}~");
        shuffle($seed_special);
        $password_special = "";
        foreach (array_rand($seed_special, 5) as $k) $password_special .= $seed_special[$k];
  
        $password = str_split($password_alphabet . $password_number . $password_special);
        shuffle($password);

        $password_reset = implode("", $password);

        $password_reset_hash = hash(
          "sha256", 
          md5($password_reset . $this->config["password_salt"])
        );

        $hash = new Hash($this->session, 
          Utilities::override_controls(true, true, true, true)
        );
        $hash_parameters = array(
          "hash" => $password_reset_hash,
          "content" => $user_data->id,
          "active" => true
        );
        if (!empty($hash_data = $hash->create($hash_parameters))) {

          if (!empty($user_data->email)) {
            try {
              $mail = new Mail($this->session, 
                Utilities::override_controls(true, true, true, true)
              );
              $mail_subject = 
              $this->translation->translate("You have requested to reset password at") 
              . " " 
              . $this->config["site_name"];
              $mail_body = 
              $user_data->name . ","
              . "\n\n"
              . $this->translation->translate("You have requested to reset password at") 
              . " " 
              . $this->config["site_name"]
              . "\n"
              . $this->translation->translate("Your temporary password is") 
              . " "
              . $password_reset;
              if (!empty($template = $mail->template("password-reset.php"))) {
                foreach (get_object_vars($user_data) as $key => $value) {
                  if ($key != "password" && $key != "passcode") {
                    $template = str_replace("{{" . $key . "}}", $value, $template);
                  }
                }
                $template = str_replace("{{password}}", $password_reset, $template);
                $template = str_replace("{{hash}}", $hash_data->hash, $template);
                $mail_body = $template;
              }
              $mail->send(
                $this->config["email"], 
                $this->config["site_name"], 
                $user_data->email, 
                null,
                null,
                $mail_subject, 
                $mail_body
              );
            } catch (Exception $e) {
              throw new Exception($e->getMessage(), $e->getCode());
            }
          }
  
          $this->log->log(
            __FUNCTION__,
            __METHOD__,
            "normal",
            func_get_args(),
            (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
            "id",
            $password_reset_hash,
          );
          return Utilities::callback(
            __METHOD__, 
            func_get_args(), 
            true,
            $this->session,
            $this->controls,
            $this->id
          );

        }

      } else {
        throw new Exception("Invalid authorization", 401);
      }

    } else {
      throw new Exception($this->translation->translate("Email or Phone is required to reset password"), 400);
    }
  }

  function verify($code = null) {
    $result = false;
    if ($this->validation->require($code, "Verfification Code")) {
      $hash = new Hash($this->session, 
        Utilities::override_controls(true, true, true, true)
      );
      if (!empty($hash_data = $hash->get($code))) {
        if (!empty($hash_data->content)) {
          $email_list = false;
          $phone_list = false;
          try {
            if (!empty(
              $email_list = $this->database->update(
                "user",
                array("email_verified" => "1"),
                array("email" => $hash_data->content),
                null,
                true,
                false
              )
            )) {
              foreach ($email_list as $email_data) {
                $hash->delete($email_data->id);
              }
            }
          } catch (Exception $e) {}
          try {
            if (!empty(
              $phone_list = $this->database->update(
                "user",
                array("phone_verified" => "1"),
                array("phone" => $hash_data->content),
                null,
                true,
                false
              )
            )) {
              foreach ($phone_list as $phone_data) {
                $hash->delete($phone_data->id);
              }
            }
          } catch (Exception $e) {}
          if (!empty($email_list) || !empty($phone_list)) {
            $result = true;
          }
        } else {
          throw new Exception($this->translation->translate("Not exist or gone"), 410);
        }
      } else {
        throw new Exception($this->translation->translate("Not exist or gone"), 410);
      }
    }
    if ($result) {
      return Utilities::callback(
        __METHOD__, 
        func_get_args(), 
        $result,
        $this->session,
        $this->controls,
        $this->id
      );
    } else {
      throw new Exception($this->translation->translate("Unknown error"), 409);
    }
  }

  function validate_credential($username = null, $password = null) {

    $message = $this->translation->translate("Invalid authorization");

    if (!empty($this->session)) {

      if (!isset($username) || empty($username)) {
        $username = $this->session->username;
      }
  
      if (
        $this->validation->require($username, "Username")
        && $this->validation->require($password, "Password")
      ) {
  
        $filters = array(
          "active" => true
        );
        $extensions = array(
          array(
            "conjunction" => "",
            "key" => "username",
            "operator" => "=",
            "value" => "'" . $username . "'",
          ),
          array(
            "conjunction" => "OR",
            "key" => "email",
            "operator" => "=",
            "value" => "'" . $username . "'",
          ),
          array(
            "conjunction" => "OR",
            "key" => "phone",
            "operator" => "=",
            "value" => "'" . $username . "'",
          )
        );
        if (!empty(
          $data = $this->database->select(
            "user", 
            "*", 
            $filters, 
            $extensions, 
            true,
            false
          )
        )) {

          $password_hash = hash("sha256", md5($password . $this->config["password_salt"]));
          $filters["password"] = hash("sha256", md5($password . $this->config["password_salt"]));
  
          $data_password = $this->database->select(
            "user", 
            "*", 
            $filters, 
            $extensions, 
            true,
            false
          );
          
          $data_hash = $this->database->select(
            "hash", 
            "*", 
            array(
              "hash" => $password_hash,
              "content" => $data->id
            ), 
            null, 
            true,
            false
          );
  
          if (!empty($data_password) || !empty($data_hash)) {
            return true;
          } else {
            throw new Exception($message, 401);
          }

        } else {
          throw new Exception($message, 401);
        }
      } else {
        throw new Exception($this->translation->translate("Bad request"), 400);
      }

    } else {
      throw new Exception($message, 401);
    }

  }

  function validate_username($id = null, $username) {
    try {
      $this->validation->require($username, "Username");
      $this->validation->string_min($username, "Username", 3);
      $this->validation->string_max($username, "Username", 100);
      $this->validation->no_special_characters($username, "Username");
      $this->validation->unique($username, "Username", "username", "user", $id);
      return true;
    } catch (Exception $e) {
      throw new Exception($this->translation->translate($e->getMessage()), $e->getCode());
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
        if (!isset($parameters["username"]) || empty($parameters["username"])) {
          $parameters["username"] = $this->random_username();
        }
        if (!isset($parameters["email"]) || empty($parameters["email"])) {
          $parameters["email"] = "";
        }
        if (!isset($parameters["nick_name"]) || empty($parameters["nick_name"])) {
          $parameters["nick_name"] = "";
        }
        if (!isset($parameters["name"]) || empty($parameters["name"])) {
          $parameters["name"] = "";
        }
        if (!isset($parameters["last_name"]) || empty($parameters["last_name"])) {
          $parameters["last_name"] = "";
        }
        if (!isset($parameters["image"]) || empty($parameters["image"])) {
          $parameters["image"] = "";
        }
        if (!isset($parameters["phone"]) || empty($parameters["phone"])) {
          $parameters["phone"] = "";
        }
        if (!isset($parameters["passcode"]) || empty($parameters["passcode"])) {
          $parameters["passcode"] = "";
        }
        if (!isset($parameters["passcode"]) || empty($parameters["passcode"])) {
          $parameters["passcode"] = "";
        }
        $parameters["active"] = (isset($parameters["active"]) ? $parameters["active"] : true);
        $parameters["user_id"] = (!empty($user_id) ? $user_id : (!empty($this->session) ? $this->session->id : 0));
        $parameters["create_at"] = gmdate("Y-m-d H:i:s");
      } else {
        unset($parameters["id"]);
        unset($parameters["create_at"]);
      }
      if (isset($parameters["password"]) && !empty($update)) {
        unset($parameters["password"]);
      }
      
      if (isset($parameters["image"]) && !empty($parameters["image"])) {
        if (
          is_string($parameters["image"])
          && base64_decode($parameters["image"], true) !== false 
          && base64_encode(base64_decode($parameters["image"], true)) === $parameters["image"]
        ) {
          try {
            $base64_data = substr($parameters["image"], strpos($parameters["image"], ',') + 1);
            $base64_data_decoded = base64_decode($base64_data);
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($base64_data_decoded);
            $extension = strtolower(pathinfo($mime, PATHINFO_EXTENSION));
            $base64_decoded = base64_decode($parameters["image"]);
            $type = finfo_buffer(finfo_open(), $base64_decoded, FILEINFO_MIME_TYPE);
            $tmp_file = tmpfile();
            fwrite($tmp_file, $base64_decoded);
            $uploaded_file = [
              "name" => "image." . $extension,
              "type" => $type,
              "size" => strlen($base64_decoded),
              "tmp_name" => stream_get_meta_data($tmp_file)["uri"],
              "error" => UPLOAD_ERR_OK
            ];
            $_FILES["image"] = $uploaded_file;
          } catch (Exception $e) {}
          if (empty($update)) {
            $parameters["image"] = "";
          } else {
            unset($parameters["image"]);
          }
        } else {
          if (!is_string($parameters["image"])) {
            if (empty($update)) {
              $parameters["image"] = "";
            } else {
              unset($parameters["image"]);
            }
          }
        }
      }
      if (isset($parameters["email_verified"])) {
        unset($parameters["email_verified"]);
      }
      if (empty($update) || (isset($parameters["email"]) && !empty($parameters["email"]))) {
        $parameters["email_verified"] = 0;
      }
      if (isset($parameters["phone_verified"])) {
        unset($parameters["phone_verified"]);
      }
      if (empty($update) || (isset($parameters["phone"]) && !empty($parameters["phone"]))) {
        $parameters["phone_verified"] = 0;
      }
      if (isset($parameters["ndid_verified"])) {
        unset($parameters["ndid_verified"]);
      }
      if (empty($update)) {
        $parameters["ndid_verified"] = 0;
      }
      if (isset($parameters["face_verified"])) {
        unset($parameters["face_verified"]);
      }
      if (empty($update)) {
        $parameters["face_verified"] = 0;
      }
    }
    return Utilities::callback(
      __METHOD__, 
      func_get_args(), 
      $parameters,
      $this->session,
      $this->controls,
      $this->id
    );
  }

  function format($data, $return_references = false, $return_authoritiy = false) {

    /* function: create referers before format (better performance for list) */
    $refer = function ($return_references = false, $abstracts_override = null) {

      $data = array();
  
      return $data;

    };

    /* function: format single data */
    $format = function ($data, $return_references = false, $return_authoritiy = false) {
      if (!empty($data)) {
  
        if ($data->active === "1") {
          $data->active = true;
        } else if ($data->active === "0" || empty($data->active)) {
          $data->active = false;
        }
  
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
  
        $data->email_verified = false;
        if (!empty($data->email_verified)) {
          $data->email_verified = true;
        }
  
        $data->phone_verified = false;
        if (!empty($data->phone_verified)) {
          $data->phone_verified = true;
        }
  
        $data->ndid_verified = false;
        if (!empty($data->ndid_verified)) {
          $data->ndid_verified = true;
        }
  
        $data->face_verified = false;
        if (!empty($data->face_verified)) {
          $data->face_verified = true;
        }
        
        $data->image_reference = null;
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
            $data->image_reference->original = $this->config["base_url"] . $data->image;
          }
          $data->image_reference->thumbnail = Utilities::get_thumbnail($data->image_reference->original);
          $data->image_reference->large = Utilities::get_large($data->image_reference->original);
        }
  
        if ($return_references === true || (is_array($return_references) && in_array("members", $return_references))) {
          if (!empty(
            $member_list = $this->database->select_multiple(
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
            )
          )) {
            $data->members = $member_list;
          }
        }
  
        if ($return_references === true || (is_array($return_references) && in_array("user_id", $return_references))) {
          $data->user_id_reference = $this->format(
            $this->database->get_reference(
              $data->user_id, 
              "user", 
              "id"
            )
          );
        }
    
        if ($return_authoritiy) {
          $arrange_controls = function($controls = array()) {
            $control_behaviors = array("view", "create", "update", "delete");
            $data = array();
            if (!empty($controls) && is_array($controls)) {
              foreach ($controls as $controls_value) {
                $value = (array) $controls_value;
                $control_arranged = $this->control->arrange((object) $value);
                if (!array_key_exists($value["module_id"], $data)) {
                  $data[$value["module_id"]] = $control_arranged;
                } else {
                  foreach ($control_behaviors as $control_behavior) {
                    if (!array_key_exists($control_behavior, $data[$value["module_id"]])) {
                      $data[$value["module_id"]][$control_behavior] = $control_arranged[$control_behavior];
                    } else {
                      if ($control_arranged[$control_behavior] === true) {
                        $data[$value["module_id"]][$control_behavior] = $control_arranged[$control_behavior];
                      } else if ($data[$value["module_id"]][$control_behavior] !== true) {
                        $controls_existed = $data[$value["module_id"]][$control_behavior];
                        if (!is_array($data[$value["module_id"]][$control_behavior])) {
                          $controls_existed = array($data[$value["module_id"]][$control_behavior]);
                        }
                        $controls_current = $control_arranged[$control_behavior];
                        if (!is_array($control_arranged[$control_behavior])) {
                          $controls_current = array($control_arranged[$control_behavior]);
                        }
                        $data[$value["module_id"]][$control_behavior] = array_merge($controls_existed, $controls_current);
                      }
                    }
                  }
                }
              }
            }
            return $data;
          };
          $member_list = $this->database->select_multiple(
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
              $group_data = $this->database->select(
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
          $control_list = $this->database->select_multiple(
            "control", 
            "*", 
            array("user" => $data->id), 
            null, 
            null, 
            null, 
            array("module_id"), 
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
        }
  
      }
      return $data;
    };

    /* create referers */
    $referers = $refer($return_references);
    if (!is_array($data)) {
      /* format single data */
      $data = $format($data, $return_references, $return_authoritiy, $referers);
    } else {
      /* format array data */
      $data = array_map(
        function($value, $return_references, $return_authoritiy, $referers, $format) { 
          return $format($value, $return_references, $return_authoritiy, $referers); 
        }, 
        $data, 
        array_fill(0, count($data), $return_references), 
        array_fill(0, count($data), $return_authoritiy), 
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
        $this->validation->set($parameters, "username", ($patch || !empty($target_id)))
        && $this->validation->set($parameters, "password", ($patch || !empty($target_id)))
        && $this->validation->set($parameters, "confirm_password", ($patch || !empty($target_id)))
        && $this->validation->set($parameters, "email", $patch)
        && $this->validation->set($parameters, "name", $patch)
        && $this->validation->set($parameters, "last_name", $patch)
        && $this->validation->set($parameters, "nick_name", $patch)
        && $this->validation->set($parameters, "phone", $patch)
        && $this->validation->set($parameters, "passcode", ($patch || !empty($target_id)))
      ) {
        if (
          $this->validation->require(isset($parameters["username"]) ? $parameters["username"] : null, "Username", ($patch || !empty($target_id)))
          && $this->validation->string_min(isset($parameters["username"]) ? $parameters["username"] : null, "Username", 3)
          && $this->validation->string_max(isset($parameters["username"]) ? $parameters["username"] : null, "Username", 100)
          && $this->validation->no_special_characters(isset($parameters["username"]) ? $parameters["username"] : null, "Username")
          && $this->validation->unique(isset($parameters["username"]) ? $parameters["username"] : null, "Username", "username", "user", $target_id)
          && $this->validation->require(isset($parameters["password"]) ? $parameters["password"] : null, "Password", ($patch || !empty($target_id)))
          && $this->validation->string_min(isset($parameters["password"]) ? $parameters["password"] : null, "Password", 8)
          && $this->validation->string_max(isset($parameters["password"]) ? $parameters["password"] : null, "Password", 100)
          && $this->validation->password(isset($parameters["password"]) ? $parameters["password"] : null, "Password")
          && $this->validation->password_equal_to(isset($parameters["password"]) ? $parameters["password"] : null, "Password", isset($parameters["confirm_password"]) ? $parameters["confirm_password"] : null)
          && $this->validation->number(isset($parameters["passcode"]) ? $parameters["passcode"] : null, "passcode")
          && $this->validation->string_max(isset($parameters["name"]) ? $parameters["name"] : null, "Name", 100)
          && $this->validation->string_max(isset($parameters["last_name"]) ? $parameters["last_name"] : null, "Last Name", 100)
          && $this->validation->string_max(isset($parameters["nick_name"]) ? $parameters["nick_name"] : null, "Nick Name", 50)
          && $this->validation->email(isset($parameters["email"]) ? $parameters["email"] : null, "Email")
          && $this->validation->unique(isset($parameters["email"]) ? $parameters["email"] : null, "Email", "email", "user", $target_id)
          && $this->validation->unique(isset($parameters["phone"]) ? $parameters["phone"] : null, "Phone", "phone", "user", $target_id)
        ) {
          $result = true;
        }
      }
    } else {
      throw new Exception($this->translation->translate("Bad request"), 400);
    }
    return Utilities::callback(
      __METHOD__, 
      func_get_args(), 
      $result,
      $this->session,
      $this->controls,
      $this->id
    );
  }

  private function update_device($user_id, $session_id) {
    $filters = array(
      "user_id" => $user_id,
      "session" => $session_id
    );
    if (!empty(
      $device_data = $this->database->select(
        "device", 
        "*", 
        $filters, 
        null, 
        true,
        false
      )
    )) {
      $update_at = gmdate("Y-m-d H:i:s");
      $expire_at = gmdate("Y-m-d H:i:s", strtotime($this->config["session_duration"]));
      $parameters = array(
        "ip_recent" => $_SERVER["REMOTE_ADDR"],
        "update_at" => $update_at,
        "expire_at" => $expire_at
      );
      if (
        $this->database->update(
          "device", 
          $parameters, 
          array("id" => $device_data->id), 
          null, 
          true,
          false
        )
      ) {
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          true,
          $this->session,
          $this->controls,
          $this->id
        );
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
    foreach (array_rand($seed, 22) as $i) $random .= $seed[$i];
    if ($this->validation->unique($random, "Username", "username", "user")) {
      return $random;
    } else {
      return $this->random_username();
    }
  }

}