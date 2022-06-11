<?php
namespace Abstracts\Helpers;

use DateTimeZone;

class Initialization {
  
  public static function load($config, $paths = array()) {
    if (!empty($config)) {
      if (isset($config["services_path"])) {
        array_push($paths, ("../" . $config["services_path"]));
      }
      if (isset($config["callback_path"])) {
        array_push($paths, ("../" . $config["callback_path"]));
      }
    }
    $exceptions = array(
      "initial.php"
    );
    foreach($paths as $path) {
      $files = scandir($path);
      foreach($files as $file) {
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

  public static function headers($config) {

    mb_internal_encoding($config["encoding"]);   
    date_default_timezone_set($config["timezone"]);
    
    if (!isset($_SESSION)) {
    
      session_cache_limiter('private');
      $cache_limiter = session_cache_limiter();
      session_cache_expire(5);
      $cache_expire = session_cache_expire();
    
      session_start();
    
    }

  }

  public static function response_headers($config) {

    if (isset($_SERVER["HTTP_ORIGIN"])) {
      header("Access-Control-Allow-Origin: *");
      header("Access-Control-Request-Headers: *");
    }
    if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
      if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, COPY, DELETE, OPTIONS");
      }
      if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])) {
        header("Access-Control-Allow-Headers: Access-Control-Allow-Origin, Access-Control-Allow-Methods, Content-Type, Authorization, Key, Secret, Token, Lock, Language");
      }
      exit(0);
    }

    header("Content-Type: application/json");

  }

  public static function timeout($seconds = 5) {
    set_time_limit($seconds);
  }

  public static function display_errors($display = false) {
    ini_set("display_errors", ($display ? "On" : "Off"));
    ini_set("error_reporting", E_ALL);
  }

  public static function config($config) {
    
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
    $config["service_directory"] = "service/";
    $config["service_core_directory"] = "service/core/" . $config["version"] . "/";

    $config["template_path"] = $config["website_url"].$config["template_directory"];
    $config["upload_path"] = realpath($config["website_url"].$config["upload_directory"]);
    $config["service_path"] = $config["website_url"].$config["service_directory"];
    $config["service_core_path"] = $config["website_url"].$config["service_core_directory"];

    $config["encrypt_key_filemanger"] = "dd17e9c5f93007885229a2049be6a678";

    return $config;

  }

}