<?php
header("Content-Type: application/json");
ini_set("display_errors", "Off");
ini_set("error_reporting", E_ALL);

function install_server($type) {
  
  $response = array();
  
  $template_file_path = "../templates/htaccess.txt";
  $template_file = fopen($template_file_path, "r");
  if ($template_file) {

    $template = fread($template_file, filesize($template_file_path));
    fclose($template_file);

    $base_url = trim($_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], "/");

    $url_parts = parse_url($base_url);
    if (!empty($url_parts["path"])) {
      $rewrite_base_url = $url_parts["path"] . "/";
    } else {
      $rewrite_base_url = "/";
    }

    $template = str_replace("{{rewrite_base_url}}", $rewrite_base_url, $template);

    $destination_file_path = "../../../../../../../.htaccess";
    if (!file_exists($destination_file_path)) {

      try {

        $destination_file = fopen($destination_file_path, 'w+');
        if ($destination_file) {

          fwrite($destination_file, $template);
          fclose($destination_file);
    
          $template_file_path = "../templates/web.config.txt";
          $template_file = fopen($template_file_path, "r");
          if ($template_file) {

            $template = fread($template_file, filesize($template_file_path));
            fclose($template_file);

            $template = str_replace("{{base_url}}", $base_url, $template);

            $destination_file_path = "../../../../../../../web.config";
            if (!file_exists($destination_file_path)) {

              try {

                $destination_file = fopen($destination_file_path, 'w+');
                if ($destination_file) {

                  fwrite($destination_file, '<?xml version="1.0" encoding="UTF-8"?>' . $template);
                  fclose($destination_file);
            
                  $response["status"] = true;
                  $response["data"] = $base_url;
                  $response["message"] = "Successfully installed web.config";

                } else {
                  $response["status"] = false;
                  $response["message"] = "Unable to install web.config, please check your root permission (Temporarily set to 777)";
                }

              } catch(Exception $e) {
                $response["status"] = false;
                $response["message"] = "Unable to install web.config, please check your root permission (Temporarily set to 777)";
              }

            } else {
              $response["status"] = false;
              $response["message"] = "There is already web.config, please remove existed web.config to install";
            }

          } else {
            $response["status"] = false;
            $response["message"] = "Template file not found";
          }

        } else {
          $response["status"] = false;
          $response["message"] = "Unable to install .htaccess, please check your root permission (Temporarily set to 777)";
        }

      } catch(Exception $e) {
        $response["status"] = false;
        $response["message"] = "Unable to install .htaccess, please check your root permission (Temporarily set to 777)";
      }

    } else {

      $template_file_path = "../templates/web.config.txt";
      $template_file = fopen($template_file_path, "r");
      if ($template_file) {
    
        $template = fread($template_file, filesize($template_file_path));
        fclose($template_file);
    
        $template = str_replace("{{base_url}}", $base_url, $template);
    
        $destination_file_path = "../../../../../../../web.config";
        if (!file_exists($destination_file_path)) {
    
          try {
    
            $destination_file = fopen($destination_file_path, 'w+');
            if ($destination_file) {
    
              fwrite($destination_file, '<?xml version="1.0" encoding="UTF-8"?>' . $template);
              fclose($destination_file);
        
              $template_file_path = "../templates/web.config";
              $template_file = fopen($template_file_path, "r");
              if ($template_file) {
    
                $template = fread($template_file, filesize($template_file_path));
                fclose($template_file);
    
              } else {
                $response["status"] = false;
                $response["message"] = "Template file not found";
              }
    
            } else {
              $response["status"] = false;
              $response["message"] = "Unable to install web.config, please check your root permission (Temporarily set to 777)";
            }
    
          } catch(Exception $e) {
            $response["status"] = false;
            $response["message"] = "Unable to install web.config, please check your root permission (Temporarily set to 777)";
          }
    
        } else {
          $response["status"] = false;
          $response["message"] = "There is already .htaccess, web.config, please remove existed .htaccess, web.config to install";
        }
    
      } else {
        $response["status"] = false;
        $response["message"] = "Template file not found";
      }

    }

  } else {
    $response["status"] = false;
		$response["message"] = "Template file not found";
  }
  
  if (isset($response["status"]) && $response["status"]) {
    return true;
  } else {
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    return false;
  }

}

install_server($_POST["type"]);
?>