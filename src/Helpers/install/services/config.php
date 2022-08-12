<?php
header("Content-Type: application/json");
ini_set("display_errors", "Off");
ini_set("error_reporting", E_ALL);

function install_config(
  $site_name,
  $password_salt,
  $db_host,
  $database_table,
  $db_login,
  $db_password = ""
) {
  
  $response = array();
  
  $template_file_path = "../templates/config.txt";
  $template_file = fopen($template_file_path, "r");
  if ($template_file) {

    $template = fread($template_file, filesize($template_file_path));
    fclose($template_file);

    $base_url = trim($_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], "/");

    $template = str_replace("{{site_name}}", $site_name, $template);
    $template = str_replace("{{base_url}}", $base_url, $template);
    $template = str_replace("{{password_salt}}", $password_salt, $template);
    $template = str_replace("{{db_host}}", $db_host, $template);
    $template = str_replace("{{database_table}}", $database_table, $template);
    $template = str_replace("{{db_login}}", $db_login, $template);
    $template = str_replace("{{db_password}}", $db_password, $template);

    $destination_file_path = "../../../../../../../abstracts.config.php";
    if (!file_exists($destination_file_path)) {

      try {

        $destination_file = fopen($destination_file_path, 'w+');
        if ($destination_file) {

          fwrite($destination_file, "<?php " . $template . " ?>");
          fclose($destination_file);
    
          $response["status"] = true;
          $response["message"] = "Successfully installed abstracts.config.php";

        } else {
          $response["status"] = false;
          $response["message"] = "Unable to install abstracts.config.php, please check your root permission (Temporarily set to 777)";
        }

      } catch(Exception $e) {
        $response["status"] = false;
        $response["message"] = "Unable to install abstracts.config.php, please check your root permission (Temporarily set to 777)";
      }

    } else {
      $response["status"] = false;
      $response["message"] = "There is already abstracts.config.php, please remove existed abstracts.config.php to install";
    }

  } else {
    $response["status"] = false;
		$response["message"] = "Template file not found";
  }

  echo json_encode($response, JSON_UNESCAPED_UNICODE);
  if (isset($response["status"]) && $response["status"]) {
    return true;
  } else {
    return false;
  }

}

install_config(
  $_POST["site_name"],
  $_POST["password_salt"],
  $_POST["db_host"],
  $_POST["database_table"],
  $_POST["db_login"],
  $_POST["db_password"]
);
?>