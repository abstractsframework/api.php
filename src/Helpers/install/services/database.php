<?php
header("Content-Type: application/json");
ini_set("display_errors", "Off");
ini_set("error_reporting", E_ALL);

function install_database(
  $name, 
  $username, 
  $password, 
  $password_salt,
  $database_host, 
  $database_table, 
  $database_login, 
  $database_password
) {
  
  $response = array();

  $template_file_path = "../templates/database.sql";
  $date = gmdate("Y-m-d H:i:s");
  $template_file = fopen($template_file_path, "r");
  if ($template_file) {
    $template = fread($template_file, filesize($template_file_path));
    fclose($template_file);

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

    $connection = mysqli_connect(
      $database_host, 
      $database_login, 
      $database_password,
      $database_table
    );
    if ($connection) {
      if ($result = mysqli_multi_query($connection, $template)) {
        $response["status"] = true;
        $response["api"] = array(
          "key" => $key_random,
          "secret" => $secret_random
        );
        $response["message"] = "Successfully installed database";
      } else {
        $response["status"] = false;
        $response["message"] = "Database encountered error";
      }
      mysqli_close($connection);
    } else {
      $response["status"] = false;
      $response["message"] = "Template file not found";
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

install_database(
  $_POST["name"],
  $_POST["username"],
  $_POST["password"],
  $_POST["password_salt"],
  $_POST["database_host"],
  $_POST["database_table"],
  $_POST["database_login"],
  $_POST["database_password"]
);
?>