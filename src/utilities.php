<?php
namespace Abstracts\Core;

use \Abstracts\Core\Database;

class Utilities {

  function get_headers_all() {
    $headers = array();
    if (function_exists("getallheaders")) {
      $headers = getallheaders();
    } else if (function_exists("apache_request_headers")) {
      $headers = apache_request_headers();
    } else {
      foreach($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
          $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
      }
    }
    return $headers;
  }

  function format_request() {

    $arrange = function($value) {
      $parameters = null;
      if (isset($value)) {
        if (is_string($value)) {
          if (is_array(json_decode($value, true))) {
            $parameters = json_decode($value, true);
          } else {
            $parameters = $value;
          }
        }
        return $parameters;
      } else {
        return null;
      }
    };

    $get = array();
    if (isset($_GET) && count($_GET)) {
      foreach($_GET as $key => $value) {
        $get[$key] = $arrange($value);
      }
      unset($get["module"]);
      unset($get["function"]);
    }

    $post = array();
    if (isset($_POST) && count($_POST)) {
      foreach($_POST as $key => $value) {
        $post[$key] = $arrange($value);
      }
    }

    $put = array();
    if (isset($_PUT) && count($_PUT)) {
      foreach($_PUT as $key => $value) {
        $put[$key] = $arrange($value);
      }
    }

    $patch = array();
    if (isset($_PATCH) && count($_PATCH)) {
      foreach($_PATCH as $key => $value) {
        $patch[$key] = $arrange($value);
      }
    }

    $delete = array();
    if (isset($_DELETE) && count($_DELETE)) {
      foreach($_DELETE as $key => $value) {
        $delete[$key] = $arrange($value);
      }
    }

    /* merge/override body json to post/put/patch/delete for request */
    $json = array();
    $json_body = json_decode(file_get_contents("php://input"), true);
    if ($json_body) {
      foreach($json_body as $key => $value) {
        $json[$key] = $arrange($value);
      }
    }
    
    return array(
      "get" => $get,
      "post" => ($_SERVER["REQUEST_METHOD"] === "POST" ? array_merge($post, $json) : null),
      "put" => ($_SERVER["REQUEST_METHOD"] === "PUT" ? array_merge($put, $json) : null),
      "patch" => ($_SERVER["REQUEST_METHOD"] === "PATCH" ? array_merge($patch, $json) : null),
      "delete" => ($_SERVER["REQUEST_METHOD"] === "DELETE" ? array_merge($delete, $json) : null)
    );

  }

  function generate_link($text) {
	
    $text = strip_tags($text);
    // Preserve escaped octets.
    $text = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $text);
    // Remove percent signs that are not part of an octet.
    $text = str_replace('%', '', $text);
    // Restore octets.
    $text = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $text);
  
    if ($this->is_utf8($text)) {
      if (function_exists('mb_strtolower')) {
        $text = mb_strtolower($text, 'UTF-8');
      }
      $text = $this->utf8_url_encode($text, 1000);
    }
  
    $text = strtolower($text);
    $text = preg_replace('/&.+?;/', '', $text); // kill entities
    $text = str_replace('.', '-', $text);
    $text = preg_replace('/[^%a-z0-9 _-]/', '', $text);
    $text = preg_replace('/\s+/', '-', $text);
    $text = preg_replace('|-+|', '-', $text);
    $text = trim($text, '-');
  
    return urldecode($text);
    
  }

  function utf8_url_encode( $utf8_string, $length = 0 ) {

    $unicode = '';
    $values = array();
    $num_octets = 1;
    $unicode_length = 0;
  
    $string_length = strlen( $utf8_string );
    for ($i = 0; $i < $string_length; $i++ ) {
  
      $value = ord( $utf8_string[ $i ] );
  
      if ( $value < 128 ) {
        if ( $length && ( $unicode_length >= $length ) )
          break;
        $unicode .= chr($value);
        $unicode_length++;
      } else {
          if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;
  
          $values[] = $value;
  
          if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
              break;
          if ( count( $values ) == $num_octets ) {
              if ($num_octets == 3) {
                  $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
                  $unicode_length += 9;
              } else {
                  $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
                  $unicode_length += 6;
              }
  
              $values = array();
              $num_octets = 1;
          }
      }
  
    }
  
    return $unicode;
  
  }

  public static function get_class_name($key) {
    if (!empty($key)) {
      $parts = explode("_", $key);
      $formats = array();
      foreach($parts as $part) {
        array_push($formats, ucfirst($part));
      }
      $parts = explode("-", implode("", $formats));
      $formats = array();
      foreach($parts as $part) {
        array_push($formats, ucfirst($part));
      }
      return implode("", $formats);
    } else {
      return null;
    }
  }

  public static function sync_module($config, $database_table) {
    if (!empty($database_table)) {
      $database = new Database($config);
      $filters = array(
        "database_table" => $database_table
      );
      $module_data = $database->select(
        "module", 
        "*", 
        $filters, 
        null, 
        true
      );
      if (!empty($module_data) && isset($module_data->default_control)) {
        $module_data->default_control = explode(",", $module_data->default_control);
      } else {
        $module_data->default_control = array();
      }
      return $module_data;
    } else {
      return null;
    }
  }

  public static function sync_control(
    $module_id, 
    $session, 
    $controls,
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
    if (isset($session) && isset($session->controls) && count($session->controls)) {
      if (isset($session->controls[$module_id])) {
        foreach($controls as $key => $value) {
          if (empty($value)) {
            $controls[$key] = $session->controls[$module_id][$key];
          }
        }
      }
    }
    if (!empty($module) && isset($module->default_control)) {
      foreach($module->default_control as $key => $value) {
        if (empty($value)) {
          $controls[$key] = true;
        }
      }
    }
    return $controls;
  }

  function is_utf8($str) {
    $length = strlen($str);
    for ($i=0; $i < $length; $i++) {
        $c = ord($str[$i]);
        if ($c < 0x80) $n = 0; # 0bbbbbbb
        elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
        elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
        elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
        elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
        elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
        else return false; # Does not match any model
        for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
            if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                return false;
        }
    }
    return true;
  }
  
  public static function generate_thumbnail($source, $destination, $target_width, $target_height = null, $aspectratio = true, $quality = 100) {

    $image_handlers = array(
      "IMAGETYPE_JPEG" => array(
        'load' => 'imagecreatefromjpeg',
        'save' => 'imagejpeg',
        'quality' => 100
      ),
      "IMAGETYPE_PNG" => array(
        'load' => 'imagecreatefrompng',
        'save' => 'imagepng',
        'quality' => 0
      ),
      "IMAGETYPE_GIF" => array(
        'load' => 'imagecreatefromgif',
        'save' => 'imagegif'
      )
    );    
    
    // 1. Load the image from the given $source
    // - see if the file actually exists
    // - check if it's of a valid image type
    // - load the image resource
  
    // get the type of the image
    // we need the type to determine the correct loader
    $info = getimagesize($source);
    if ($info["mime"] == "image/png") {
      $type = "IMAGETYPE_PNG";
    } else if ($info["mime"] == "image/gif") {
      $type = "IMAGETYPE_GIF";
    } else if ($info["mime"] == "image/jpeg") {
      $type = "IMAGETYPE_JPEG";
    }
  
    // if no valid type or no handler found -> exit
    if (!isset($type) || !isset($image_handlers[$type])) {
      return null;
    }
    
    // load the image with the correct loader
    $image = call_user_func($image_handlers[$type]['load'], $source);
  
  
    // no image found at supplied location -> exit
    if (!isset($image)) {
      return null;
    }
  
  
    // 2. Create a thumbnail and resize the loaded $image
    // - get the image dimensions
    // - define the output size appropriately
    // - create a thumbnail based on that size
    // - set alpha transparency for GIFs and PNGs
    // - draw the final thumbnail
  
    // get original image width and height
    $width = imagesx($image);
    $height = imagesy($image);
  
    // maintain aspect ratio when no height set
    if ($target_height == null) {
  
        // get width to height ratio
        $ratio = $width / $height;
  
        // if is portrait
        // use ratio to scale height to fit in square
        if ($width > $height) {
          $target_height = floor($target_width / $ratio);
        }
        // if is landscape
        // use ratio to scale width to fit in square
        else {
          $target_height = $target_width;
          $target_width = floor($target_width * $ratio);
        }
    }
  
    // create duplicate image based on calculated target size
    $thumbnail = imagecreatetruecolor($target_width, $target_height);
    
    // set transparency options for GIFs and PNGs
    if ($type == "IMAGETYPE_GIF" || $type == "IMAGETYPE_PNG") {
  
      // make image transparent
      imagecolortransparent(
        $thumbnail,
        imagecolorallocate($thumbnail, 0, 0, 0)
      );
      $quality = "";
  
      // additional settings for PNGs
      if ($type == "IMAGETYPE_PNG") {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $quality = 0;
      }
    }
    
    // copy entire source image to duplicate image and resize
    if ($aspectratio == true) {
      if ($width/$height > $target_width/$target_height) {
        $target_width_temp = round($width*($target_height/$height));
        $target_height_temp = $target_height;
        $target_width_temp_re = round($target_width*($height/$target_height));
        $target_height_temp_re = $height;
        $image_Crop_thumbnail_x = round(($width - $target_width_temp_re)/2);
        $image_Crop_thumbnail_y = 0;
      } else if ($width/$height < $target_width/$target_height) {
        $target_width_temp = $target_width;
        $target_height_temp = round($height*($target_width/$width));
        $target_width_temp_re = $width;
        $target_height_temp_re = round($target_height*($width/$target_width));
        $image_Crop_thumbnail_x = 0;
        $image_Crop_thumbnail_y = round(($height - $target_height_temp_re)/2);
      } else {
        $target_width_temp = $target_width;
        $target_height_temp = $target_height;
        $image_Crop_thumbnail_x = 0;
        $image_Crop_thumbnail_y = 0;
      }
      imagecopyresampled(
        $thumbnail, 
        $image, 0, 0, $image_Crop_thumbnail_x, $image_Crop_thumbnail_y, 
        $target_width_temp, $target_height_temp, 
        $width, $height
      );
    } else {
      imagecopyresampled(
        $thumbnail,
        $image,
        0, 0, 0, 0,
        $target_width, $target_height,
        $width, $height
      );
    }
  
    // 3. Save the $thumbnail to disk
    // - call the correct save method
    // - set the correct quality level
  
    // save the duplicate version of the image to disk
    return call_user_func(
      $image_handlers[$type]['save'],
      $thumbnail,
      $destination,
      $quality
    );
  
  }

  public static function get_thumbnail($path) {
    $file_path = basename($path);
    $directory_path = str_replace($file_path, "", $path);
    return $directory_path . "thumbnail/" . $file_path;
  }
  
  public static function get_large($path) {
    $file_path = basename($path);
    $directory_path = str_replace($file_path, "", $path);
    return $directory_path . "large/" . $file_path;
  }

}