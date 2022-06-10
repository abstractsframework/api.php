<?php
namespace Abstracts;

use \Abstracts\Database;
use \Abstracts\Utilities;
use \Abstracts\Encryption;
use \Abstracts\User;

class Authorization {

  /* initialization */
  private $config = null;
  public $user_module = null;

  /* helpers */
  private $utilities = null;
  private $encryption = null;

  /* services */
  private $user = null;

  function __construct($config) {

    $this->config = $config;

    $this->utilities = new Utilities();
    $this->encryption = new Encryption();

    $this->user = new User($this->config);
    if ($this->user->module) {
      $this->user_module = $this->user->module;
    }

  }

  function get_session() {

    $session = false;

    if (isset($_SESSION) && isset($_SESSION["id"]) && isset($_SESSION["session_id"])) {
      $session = $_SESSION;
    } else {
      $session_id = null;
      $user_id = null;
      $authorization = null;
      foreach($this->utilities->get_headers_all() as $key => $value) {
        if (strtolower($key) == "authorization") {
          if (strpos($value, "Bearer") === 0) {
            $authorization = str_replace("Bearer ", "", $value);
          }
        }
      }
      if (is_null($authorization)) {
        if (isset($_POST["authorization"]) && !empty($_POST["authorization"])) {
          $authorization = $_POST["authorization"];
        } else if (isset($_GET["authorization"]) && !empty($_GET["authorization"])) {
          $authorization = $_GET["authorization"];
        }
      }
      if (!is_null($authorization)) {
        if (isset($config["encrypt_authorization"]) && !empty($config["encrypt_authorization"])) {
          $decoded = $this->encryption->decode(
            $authorization, 
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
        } else {
          $session_parts = explode(".", base64_decode($authorization));
          if (count($session_parts) == 2) {
            $user_id = $session_parts[0];
            $session_id = $session_parts[1];
          }
        }
      }
      
      if (
        isset($session_id) && !empty($session_id)
        && isset($user_id) && !empty($user_id)
      ) {
        $database = new Database($this->config);
        $session = $database->select(
          "user", 
          "*", 
          array("id" => $user_id), 
          null, 
          true
        );
        if ($session) {
          $session = $this->user->format($session);
          $session->session_id = $session_id;
        }
      }

    }

    return $session;

  }

}