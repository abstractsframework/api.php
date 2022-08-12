<?php
header("Content-Type: application/json");
ini_set("display_errors", "Off");
ini_set("error_reporting", E_ALL);

function install_directories($type) {
  
  $response = array();

  $api_directory_path = "../../api/";
  $media_directory_path = "../../media/";
  $service_directory_path = "../../services/";
  try {
    chmod($service_directory_path, 0777);
    if (!file_exists($media_directory_path)) {
      mkdir($media_directory_path, 0777, true);
    } else {
      chmod($media_directory_path, 0777);
    }
    if ($type == "web") {
      if (!file_exists($api_directory_path)) {
        mkdir($api_directory_path, 0777, true);
      } else {
        chmod($api_directory_path, 0777);
      }
    }
    $response["status"] = true;
    $response["message"] = "Successfully installed directories";
  } catch(Exception $e) {
    $response["status"] = false;
    $response["message"] = "Unable to install directories, please check your root or media permission (Temporarily set to 777)";
  }

  echo json_encode($response, JSON_UNESCAPED_UNICODE);
  if (isset($response["status"]) && $response["status"]) {
    return true;
  } else {
    return false;
  }

}

install_directories($_POST["type"]);
?>