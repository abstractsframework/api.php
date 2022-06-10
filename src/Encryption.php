<?php 
namespace Abstracts;

use \Firebase\JWT\JWT;
use \Firebase\JWT\JWK;

use Exception;

class Encryption {

  function encode($parameters, $key, $encryption = "", $headers = null) {
    $encoded = false;
    try {
      if (isset($encryption) && !empty($encryption)) {
        $encoded = JWT::encode($parameters, $key, $encryption, null, $headers);
      } else {
        $encoded = JWT::encode($parameters, $key, null, $headers);
      }
    } catch(Exception $e) {
      $encoded = false;
    }
    if ($encoded) {
      return $encoded;
    } else {
      return false;
    }
  }
  
  function decode($token, $key, $encryption = "") {
    $decoded = false;
    try {
      if (isset($encryption) && !empty($encryption)) {
        $decoded = JWT::decode($token, $key, array($encryption));
      } else {
        $decoded = JWT::decode($token, $key);
      }
    } catch(Exception $e) {
      $decoded = false;
    }
    if ($decoded) {
      return $decoded;
    } else {
      return false;
    }
  }
  
  function decode_keyset($token, $keyset, $encryption = "") {
    $decoded = false;
    try {
  
      $token_parts = explode(".", $token);
      $token_header = json_decode(base64_decode($token_parts[0]), true);
  
      $key = null;
      $keys = JWK::parseKeySet($keyset);
      if (isset($token_header["kid"])) {
        if (isset($keys[$token_header["kid"]])) {
          $key_details = openssl_pkey_get_details($keys[$token_header["kid"]]);
          $key = $key_details["key"];
        }
      }
      if (!is_null($key)) {
        if (isset($encryption) && !empty($encryption)) {
          $decoded = JWT::decode($token, $key, array($encryption));
        } else {
          $decoded = JWT::decode($token, $key);
        }
      } else {
        $decoded = false;
      }
    } catch(Exception $e) {
      $decoded = false;
    }
    if ($decoded) {
      return $decoded;
    } else {
      return false;
    }
  }
  
  function encrypt_name($data) {
    
    $random_data = '';
    for ($i = 0; $i < 1; $i++) {
      $random_data .= mt_rand(0,5000);
    }
    $datetime_data = date("Ymd_His");
    $encrypted_data = md5($datetime_data . "_" . $random_data);
    if (strlen($data) != mb_strlen($data, 'utf-8')) {
      $data_new = str_replace(" ", "_", $data).'_'.$encrypted_data;
    } else {
      $data_new = str_replace(" ", "_", $data).'_'.$encrypted_data;
    }
    
    $data_new = trim($data_new);
    $data_new = preg_replace('!\s+!', ' ', $data_new);
    $data_new = str_replace(' ', '-', $data_new);
    $data_new = str_replace(array("^","!","@","#","%","^","&","*","(",")","+","=","~","{","}","[","]",";",":","\"","'","<",">",".","\,","\\","/",), "-", $data_new);
    $data_new = str_replace(array("?","\'","\""), "", $data_new);
    $data_new = preg_replace('/[-]+/', '-', $data_new);
    $data_new = str_replace(' ', '-', $data_new);
    $data_new = preg_replace('/[_]+/', '_', $data_new);
    $data_new = trim($data_new, '-');
    $data_new = trim($data_new, '_');
    $data_new = strtolower($data_new);
    
    return $data_new;
  
  }
  
  function generate_guid() { 
    return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
      mt_rand(0, 65535), mt_rand(0, 65535),
      mt_rand(0, 65535),
      mt_rand(0, 4095),
      bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
      mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)
    ); 
  }

}
?>