<?php
namespace Abstracts;

use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use Exception;

class Install {

  /* helpers */
  private $database = null;
  private $validation = null;
  private $translation = null;

  function __construct() {
    
    /* initialize: helpers */
    $this->database = new Database(null, Utilities::override_controls(true, true, true, true));
    $this->validation = new Validation();
    $this->translation = new Translation();
    
  }

  function config(
    $site_name,
    $password_salt,
    $database_host,
    $database_name,
    $database_login,
    $database_password
  ) {

    error_reporting(E_ERROR | E_PARSE);
    
    $template_file_path = "../templates/config.txt";
    try {
      $template_file = fopen($template_file_path, "r");
      if ($template_file) {
    
        $template = fread($template_file, filesize($template_file_path));
        fclose($template_file);
    
        $template = str_replace("{{site_name}}", $site_name, $template);
        $template = str_replace("{{base_url}}", $this->base_url(), $template);
        $template = str_replace("{{password_salt}}", $password_salt, $template);
        $template = str_replace("{{database_host}}", $database_host, $template);
        $template = str_replace("{{database_name}}", $database_name, $template);
        $template = str_replace("{{database_login}}", $database_login, $template);
        $template = str_replace("{{database_password}}", $database_password, $template);

        try {
          chmod($this->root_path(), 0777);
          shell_exec(sprintf('sudo chmod 777 "' . $this->root_path() . '"'));
        } catch (Exception $e) {}
    
        $destination_file_path = $this->root_path() . "/abstracts.config.php";
        if (!file_exists($destination_file_path)) {
          $destination_file = fopen($destination_file_path, 'w+');
          if ($destination_file) {
            fwrite($destination_file, "<?php " . $template);
            fclose($destination_file);
            return true;
          } else {
            throw new Exception($this->translation->translate(
              "Unable to install abstracts.config.php, please check your root permission (Temporarily set to 777)"
            ), 403);
          }
        } else {
          throw new Exception($this->translation->translate(
            "There is already abstracts.config.php, please remove existed abstracts.config.php to install"
          ), 409);
        }
      } else {
        throw new Exception($this->translation->translate("Template file not found"), 409);
      }
    } catch(Exception $e) {
      throw new Exception($this->translation->translate($e->getMessage()), 409);
    }

  }

  function database(
    $name, 
    $username, 
    $password, 
    $password_salt,
    $database_host, 
    $database_name, 
    $database_login, 
    $database_password
  ) {
  
    $template_file_path = "../templates/database.sql";
    try {
      $template_file = fopen($template_file_path, "r");
      if ($template_file) {
        $template = fread($template_file, filesize($template_file_path));
        fclose($template_file);
    
        $date = gmdate("Y-m-d H:i:s");

        $template = str_replace("NOW()", '"' . $date . '"', $template);
        $template = str_replace("{{name}}", $name, $template);
        $template = str_replace("{{username}}", $username, $template);
        $template = str_replace("{{password}}", hash("sha256", md5($password.$password_salt)), $template);
    
        $key_seed = str_split('abcdefghijklmnopqrstuvwxyz'.'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.'0123456789');
        shuffle($key_seed);
        $key_random = "";
        foreach (array_rand($key_seed, 20) as $k) $key_random .= $key_seed[$k];
      
        $secret_seed = str_split('abcdefghijklmnopqrstuvwxyz'.'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.'0123456789!@#$%^&*()');
        shuffle($secret_seed);
        $secret_random = "";
        foreach (array_rand($secret_seed, 8) as $k) $secret_random .= $secret_seed[$k];
        
        $template = str_replace("{{api_key}}", $key_random, $template);
        $template = str_replace("{{api_secret}}", $secret_random, $template);

        $connection = $this->database->connect(
          array(
            "host" => $database_host,
            "name" => $database_name,
            "login" => $database_login,
            "password" => $database_password
          )
        );
        if (!empty($connection)) {
          if (mysqli_multi_query($connection, $template)) {
            return array(
              "key" => $key_random,
              "secret" => $secret_random
            );
          } else {
            throw new Exception($this->translation->translate("Database encountered error"), 409);
          }
          $this->database->disconnect($connection);
        } else {
          throw new Exception($this->translation->translate("Unable to connect to database"), 409);
        }
      } else {
        throw new Exception($this->translation->translate("Template file not found"), 409);
      }
    } catch(Exception $e) {
      throw new Exception($this->translation->translate($e->getMessage()), 409);
    }
  
  }

  function directories($type = "web") {

    error_reporting(E_ERROR | E_PARSE);
  
    try {

      $service_directory_path = $this->root_path() . "/services/";
      if (!file_exists($service_directory_path)) {
        mkdir($service_directory_path, 0777, true);
      } else {
        chmod($service_directory_path, 0777);
        shell_exec(sprintf('sudo chmod 777 "' . $service_directory_path . '"'));
      }

      $media_directory_path = $this->root_path() . "/media/";
      if (!file_exists($media_directory_path)) {
        mkdir($media_directory_path, 0777, true);
      } else {
        chmod($media_directory_path, 0777);
        shell_exec(sprintf('sudo chmod 777 "' . $service_directory_path . '"'));
      }

      if ($type == "web") {
        $api_directory_path = $this->root_path() . "/api/";
        if (!file_exists($api_directory_path)) {
          mkdir($api_directory_path, 0777, true);
        } else {
          chmod($api_directory_path, 0777);
          shell_exec(sprintf('sudo chmod 777 "' . $service_directory_path . '"'));
        }
      }

      return true;
      
    } catch(Exception $e) {
      throw new Exception($this->translation->translate($e->getMessage()), 409);
    }
  
  }

  function server($type = "web") {

    error_reporting(E_ERROR | E_PARSE);
  
    $errors = array();
    
    $template_file_path = "../templates/htaccess.txt";
    try {
      $template_file = fopen($template_file_path, "r");
      if ($template_file) {
    
        $template = fread($template_file, filesize($template_file_path));
        fclose($template_file);

        $rewrite_base_url = 
        str_replace(
          "/vendor/abstracts/core/src/Helpers/install",
          "",
          implode('/', 
            array_intersect(
              explode("/", __DIR__),
              explode("/", $_SERVER["REQUEST_URI"])
            )
          ) . "/"
        );
    
        $template = str_replace("{{rewrite_base_url}}", $rewrite_base_url, $template);
    
        $destination_file_path = $this->root_path() . "/.htaccess";
        if (!file_exists($destination_file_path)) {
    
          $destination_file = fopen($destination_file_path, 'w+');
          if ($destination_file) {
  
            fwrite($destination_file, $template);
            fclose($destination_file);
  
          } else {
            array_push($errors, $this->translation->translate(
              "Unable to install .htaccess, please check your root permission (Temporarily set to 777)"
            ));
          }
    
        } else {
          array_push($errors, $this->translation->translate(
            "There is already .htaccess, please remove existed .htaccess to install"
          ));
        }
    
      } else {
        array_push($errors, $this->translation->translate("Template file not found"));
      }
    
      $template_file_path = "../templates/web.config.txt";
      $template_file = fopen($template_file_path, "r");
      if ($template_file) {
    
        $template = fread($template_file, filesize($template_file_path));
        fclose($template_file);
    
        $template = str_replace("{{base_url}}", $this->base_url(), $template);
    
        $destination_file_path = $this->root_path() . "/web.config";
        if (!file_exists($destination_file_path)) {
    
          $destination_file = fopen($destination_file_path, 'w+');
          if ($destination_file) {
  
            fwrite($destination_file, '<?xml version="1.0" encoding="UTF-8"?>' . $template);
            fclose($destination_file);
  
          } else {
            array_push($errors, $this->translation->translate(
              "Unable to install web.config, please check your root permission (Temporarily set to 777)"
            ));
          }
    
        } else {
          array_push($errors, $this->translation->translate(
            "There is already web.config, please remove existed web.config to install"
          ));
        }
    
      } else {
        array_push($errors, $this->translation->translate("Template file not found"));
      }

      if ($type == "web") {

        $template_file_path = "../templates/api/index.txt";
        $template_file = fopen($template_file_path, "r");
        if ($template_file) {
      
          $template = fread($template_file, filesize($template_file_path));
          fclose($template_file);
      
          $destination_file_path = $this->root_path() . "/api/index.php";
          if (!file_exists($destination_file_path)) {
      
            $destination_file = fopen($destination_file_path, 'w+');
            if ($destination_file) {
    
              fwrite($destination_file, "<?php " . $template);
              fclose($destination_file);
    
            } else {
              array_push($errors, $this->translation->translate(
                "Unable to install api/index.php, please check your root permission (Temporarily set to 777)"
              ));
            }
      
          } else {
            array_push($errors, $this->translation->translate(
              "There is already api/index.php, please remove existed api/index.php to install"
            ));
          }
      
        } else {
          array_push($errors, $this->translation->translate("Template file not found"));
        }

        $template_file_path = "../templates/api/htaccess.txt";
        $template_file = fopen($template_file_path, "r");
        if ($template_file) {
      
          $template = fread($template_file, filesize($template_file_path));
          fclose($template_file);
    
          $rewrite_base_url = 
          str_replace(
            "/vendor/abstracts/core/src/Helpers/install",
            "",
            implode('/', 
              array_intersect(
                explode("/", __DIR__),
                explode("/", $_SERVER["REQUEST_URI"])
              )
            ) . "/"
            );
      
          $template = str_replace("{{rewrite_base_url}}", $rewrite_base_url, $template);
      
          $destination_file_path = $this->root_path() . "/api/.htaccess";
          if (!file_exists($destination_file_path)) {
      
            $destination_file = fopen($destination_file_path, 'w+');
            if ($destination_file) {
    
              fwrite($destination_file, $template);
              fclose($destination_file);
    
            } else {
              array_push($errors, $this->translation->translate(
                "Unable to install api/.htaccess, please check your root permission (Temporarily set to 777)"
              ));
            }
      
          } else {
            array_push($errors, $this->translation->translate(
              "There is already api/.htaccess, please remove existed api/.htaccess to install"
            ));
          }
      
        } else {
          array_push($errors, $this->translation->translate("Template file not found"));
        }
      
        $template_file_path = "../templates/api/web.config.txt";
        $template_file = fopen($template_file_path, "r");
        if ($template_file) {
      
          $template = fread($template_file, filesize($template_file_path));
          fclose($template_file);
      
          $template = str_replace("{{base_url}}", $this->base_url() . "/api", $template);
      
          $destination_file_path = $this->root_path() . "/api/web.config";
          if (!file_exists($destination_file_path)) {
      
            $destination_file = fopen($destination_file_path, 'w+');
            if ($destination_file) {
    
              fwrite($destination_file, '<?xml version="1.0" encoding="UTF-8"?>' . $template);
              fclose($destination_file);
    
            } else {
              array_push($errors, $this->translation->translate(
                "Unable to install api/web.config, please check your root permission (Temporarily set to 777)"
              ));
            }
      
          } else {
            array_push($errors, $this->translation->translate(
              "There is already api/web.config, please remove existed api/web.config to install"
            ));
          }
      
        } else {
          array_push($errors, $this->translation->translate("Template file not found"));
        }

      }
    } catch(Exception $e) {
      array_push($errors, $this->translation->translate($e->getMessage()));
    }

    if (empty($errors)) {
      return true;
    } else {
      throw new Exception(implode(", ", $errors), 409);
    }
  
  }

  private static function root_path() {
    return rtrim(
      str_replace(
        "vendor/abstracts/core/src/Helpers/install",
        "",
        implode('/', 
          array_intersect(
            explode("/", __DIR__),
            explode("/", getcwd())
          )
        )
      ),
      "/"
    );
  }

  private static function base_url() {
    return 
    str_replace(
      "/vendor/abstracts/core/src/Helpers/install",
      "",
      (
        (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") 
        . "://" 
        . $_SERVER["HTTP_HOST"]
        . implode('/', 
          array_intersect(
            explode("/", __DIR__),
            explode("/", $_SERVER["REQUEST_URI"])
          )
        )
      )
    );
  }

}