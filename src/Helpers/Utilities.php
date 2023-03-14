<?php
namespace Abstracts\Helpers;

use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Translation;

use Exception;

class Utilities {

  public static function handle_request() {

    $rest_parameters = array(
      "m", // Module key
      "f", // Function name
      "a", // Authorization code (User token)
      "t", // API token
      "l", // Expected response language
      "h", // Hash
      "n", // Nonce for Hash
      "c", // Code for Hash
      "v" // Version (Timestamp)
    );
    // vanmltf

    $arrange = function($value) {
      $parameters = null;
      if (isset($value)) {
        if (is_string($value) && is_array(json_decode($value, true))) {
          $parameters = json_decode($value, true);
        } else {
          $parameters = $value;
        }
        return $parameters;
      } else {
        return null;
      }
    };
    
    $get = array();
    if (isset($_REQUEST) && !empty($_REQUEST)) {
      foreach ($_REQUEST as $key => $value) {
        if (!in_array($key, $rest_parameters)) {
          $get[$key] = $arrange($value);
        } else {
          unset($get[$key]);
        }
      }
    }

    $post = array();
    if (isset($_POST) && !empty($_POST)) {
      foreach ($_POST as $key => $value) {
        if (!in_array($key, $rest_parameters)) {
          $post[$key] = $arrange($value);
        } else {
          unset($post[$key]);
        }
      }
    }

    /* merge/override body json to post/put/patch/delete for request */
    $json = array();
    $json_body = json_decode(file_get_contents("php://input"), true);
    if (!empty($json_body)) {
      foreach ($json_body as $key => $value) {
        $json[$key] = $arrange($value);
      }
    }
    
    $data = array();
    if (
      empty($json) 
      && (
        $_SERVER["REQUEST_METHOD"] == "PUT" 
        || $_SERVER["REQUEST_METHOD"] == "PATCH"
      )
    ) {
      // Fetch content and determine boundary
      $raw_data = file_get_contents('php://input');
      if (!empty($raw_data)) {
        $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));

        if (!empty($boundary)) {
          // Fetch each part
          $parts = array_slice(explode($boundary, $raw_data), 1);
      
          foreach ($parts as $part) {
            
            // If this is the last part, break
            if ($part == "--\r\n") break; 
      
            // Separate content from headers
            $part = ltrim($part, "\r\n");
            list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);
      
            // Parse the headers list
            $raw_headers = explode("\r\n", $raw_headers);
            $headers = array();
            foreach ($raw_headers as $header) {
              list($name, $value) = explode(':', $header);
              $headers[strtolower($name)] = ltrim($value, ' '); 
            } 
      
            // Parse the Content-Disposition to get the field name, etc.
            if (isset($headers['content-disposition'])) {
              $filename = null;
              preg_match(
                '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/', 
                $headers['content-disposition'], 
                $matches
              );
              list(, $type, $name) = $matches;
              isset($matches[4]) and $filename = $matches[4]; 
      
              // handle your fields here
              switch ($name) {
                // this is a file upload
                case 'userfile':
                  file_put_contents($filename, $body);
                  break;
      
                // default for all other files is to populate $data
                default: 
                  $data[$name] = substr($body, 0, strlen($body) - 2);
                  break;
              } 
            }

          }
        }
        
      }

    }
    
    return array_merge($get, $post, $json, $data);

  }

  public static function handle_response($result) {
    $translation = new Translation();
    if (is_null($result)) {
      throw new Exception($translation->translate("Not found"), 404);
    }
    if (is_bool($result) && $result === false) {
      throw new Exception($translation->translate("Unknown error"), 409);
    }
    if (!is_array($result) && !is_object($result)) {
      return json_encode(array("result" => $result), JSON_UNESCAPED_UNICODE);
    } else {
      return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
  }

  public static function get_all_headers() {
    $headers = array();
    if (function_exists("getallheaders")) {
      $headers = getallheaders();
    } else if (function_exists("apache_request_headers")) {
      $headers = apache_request_headers();
    } else {
      foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
          $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
      }
    }
    return $headers;
  }

  public static function override_controls($view = false, $create = false, $update = false, $delete = false) {
    if (is_null($view)) {
      $view = false;
    }
    if (is_null($create)) {
      $create = false;
    }
    if (is_null($update)) {
      $update = false;
    }
    if (is_null($delete)) {
      $delete = false;
    }
    return array(
      "view" => $view,
      "create" => $create,
      "update" => $update,
      "delete" => $delete
    );
  }

  public static function backtrace($origin = "") {
    if (empty($origin)) {
      $origin = getcwd();
    }
    $base_path = rtrim(str_replace("vendor/abstracts/core/src/Helpers", "", __DIR__), "/");
    $path = str_replace($base_path, "", $origin);
    $backtrace = "";
    for ($i = 0; $i < count(explode("/", $path)); $i++) $i > 0 ? $backtrace .= "../" : $backtrace .= "";
    return $backtrace;
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

  public static function create_class_name($key) {
    if (!empty($key)) {
      $parts = explode("_", $key);
      $formats = array();
      foreach ($parts as $part) {
        array_push($formats, ucfirst($part));
      }
      $parts = explode("-", implode("", $formats));
      $formats = array();
      foreach ($parts as $part) {
        array_push($formats, ucfirst($part));
      }
      return implode("", $formats);
    } else {
      return null;
    }
  }

  public static function create_link($text) {
	
    $text = strip_tags($text);
    $text = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $text);
    $text = str_replace('%', '', $text);
    $text = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $text);
  
    $is_utf8 = true;
    $length = strlen($text);
    for ($i=0; $i < $length; $i++) {
        $c = ord($text[$i]);
        if ($c < 0x80) $n = 0; # 0bbbbbbb
        elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
        elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
        elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
        elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
        elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
        else $is_utf8 = false; # Does not match any model
        for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
          if ((++$i == $length) || ((ord($text[$i]) & 0xC0) != 0x80)) {
            $is_utf8 = false;
          }
        }
    }
    if ($is_utf8) {
      if (function_exists('mb_strtolower')) {
        $text = mb_strtolower($text, 'UTF-8');
      } else {
        $text = strtolower($text);
      }
      $unicode = '';
      $values = array();
      $num_octets = 1;
      $unicode_length = 0;
      $string_length = strlen($text);
      $length = 1000;
      for ($s = 0; $s < $string_length; $s++) {
        $value = ord($text[$s]);
        if ($value < 128) {
          if ($length && ($unicode_length >= $length)) 
            break;
          $unicode .= chr($value);
          $unicode_length++;
        } else {
          if (count($values ) == 0) $num_octets = ($value < 224) ? 2 : 3;
          $values[] = $value;
          if ($length && ($unicode_length + ($num_octets * 3)) > $length)
            break;
          if (count($values) == $num_octets) {
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

  public static function create_files_from_url($url = null, $multiple = false) {
    if (!empty($url)) {

      $get_info = function ($url) {
        $data = null;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_NOBODY, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl);
        $data = array(
          "type" => curl_getinfo($curl, CURLINFO_CONTENT_TYPE),
          "size" => curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD),
        );
        curl_close($curl);
        return $data;
      };

      if ($multiple === true) {
        $urls = array_map(function($url) { return trim($url); }, explode(",", trim($url)));
        $types = array();
        $sizes = array();
        foreach ($urls as $url) {
          $info = $get_info($url);
          array_push($types, $info["type"]);
          array_push($sizes, $info["size"]);
        }
        $names = array_map(function($url) { return basename(trim($url)); }, $urls);
        $types = $types;
        $errors = array_map(function() { return 0; }, $urls);
        $size = $sizes;
        $tmp_names = $urls;
        return array(
          "name" => $names,
          "type" => $types,
          "tmp_name" => $tmp_names,
          "error" => $errors,
          "size" => $size
        );
      } else {
        $info = $get_info($url);
        return array(
          "name" => basename(trim($url)),
          "type" => $info["type"],
          "tmp_name" => trim($url),
          "error" => 0,
          "size" => $info["size"],
        );
      }
    } else {
      return null;
    }
  }
  
  public static function create_image(
    $source, 
    $destination, 
    $resize = false,
    $target_width, 
    $target_height = null, 
    $aspectratio = true, 
    $quality = 75,
    $override_type = null
  ) {

    $translation = new Translation();

    if (!empty($resize)) {
      $resize = true;
      if (!empty($aspectratio)) {
        $aspectratio = true;
      }
    }
    if (empty($quality)) {
      $quality = 75;
    }

    $image_handlers = array(
      "JPEG" => array(
        "load" => "imagecreatefromjpeg",
        "save" => "imagejpeg"
      ),
      "PNG" => array(
        "load" => "imagecreatefrompng",
        "save" => "imagepng"
      ),
      "GIF" => array(
        "load" => "imagecreatefromgif",
        "save" => "imagegif"
      ),
      "WEBP" => array(
        "load" => "imagecreatefromwebp",
        "save" => "imagewebp"
      )
    );

    $info = getimagesize($source);
    if ($info["mime"] == "image/jpeg") {
      $type = "JPEG";
    } else if ($info["mime"] == "image/png") {
      $type = "PNG";
    } else if ($info["mime"] == "image/gif") {
      $type = "GIF";
    } else if ($info["mime"] == "image/webp") {
      $type = "WEBP";
    }
    if (!empty($override_type)) {
      if ($override_type == "image/jpeg") {
        $image_handlers[$type]["save"] = "imagejpeg";
      } else if ($override_type == "image/png") {
        $image_handlers[$type]["save"] = "imagepng";
      } else if ($override_type == "image/gif") {
        $image_handlers[$type]["save"] = "imagegif";
      } else if ($override_type == "image/webp") {
        $image_handlers[$type]["save"] = "imagewebp";
      }
    }
  
    if (!isset($type) || !isset($image_handlers[$type])) {
      throw new Exception($translation->translate("Unsupported image"), 415);
    }
    
    if (function_exists($image_handlers[$type]["load"])) {
      $image = call_user_func($image_handlers[$type]["load"], $source);
      if (!isset($image)) {
        throw new Exception($translation->translate("Unable to create image"), 409);
      }
    } else {
      throw new Exception("'" . $image_handlers[$type]["load"] . "' " . $translation->translate("does not exist"), 500);
    }
      
    $width = imagesx($image);
    $height = imagesy($image);

    if ($type == "PNG" && (empty($override_type) || $override_type == "image/png")) {
      $quality = intval($quality / 10);
    } else if ($type == "GIF" && (empty($override_type) || $override_type == "image/gif")) {
      $quality = "";
    }

    if ($resize) {
    
      if (is_null($target_height)) {
        $ratio = $width / $height;
        if ($width > $height) {
          $target_height = floor($target_width / $ratio);
        } else {
          $target_height = $target_width;
          $target_width = floor($target_width * $ratio);
        }
      }
    
      $truecolor = imagecreatetruecolor($target_width, $target_height);
      if ($type == "GIF" || $type == "PNG") {
        imagecolortransparent(
          $truecolor,
          imagecolorallocate($truecolor, 255, 255, 255)
        );
        if ($type == "PNG" && (empty($override_type) || $override_type == "image/png")) {
          imagealphablending($truecolor, false);
          imagesavealpha($truecolor, true);
        }
      }
      
      if ($aspectratio === true) {
        if ($width/$height > $target_width / $target_height) {
          $destination_width = round($width * ($target_height / $height));
          $destination_height = $target_height;
          $compare = round($target_width * ($height / $target_height));
          $offset_x = round(($width - $compare) / 2);
          $offset_y = 0;
        } else if ($width/$height < $target_width / $target_height) {
          $destination_width = $target_width;
          $destination_height = round($height * ($target_width / $width));
          $compare = round($target_height * ($width / $target_width));
          $offset_x = 0;
          $offset_y = round(($height - $compare) / 2);
        } else {
          $destination_width = $target_width;
          $destination_height = $target_height;
          $offset_x = 0;
          $offset_y = 0;
        }
        imagecopyresampled(
          $truecolor, 
          $image, 
          0, 
          0, 
          $offset_x, 
          $offset_y, 
          $destination_width, 
          $destination_height, 
          $width, 
          $height
        );
      } else {
        imagecopyresampled(
          $truecolor,
          $image,
          0, 
          0, 
          0, 
          0,
          $target_width, 
          $target_height,
          $width, $height
        );
      }

    } else {
    
      $truecolor = imagecreatetruecolor($width, $height);
      if ($type == "GIF" || $type == "PNG") {
        imagecolortransparent(
          $truecolor,
          imagecolorallocate($truecolor, 255, 255, 255)
        );
        if ($type == "PNG" && (empty($override_type) || $override_type == "image/png")) {
          imagealphablending($truecolor, false);
          imagesavealpha($truecolor, true);
        }
      }

      imagecopyresampled(
        $truecolor, 
        $image, 
        0, 
        0, 
        0, 
        0, 
        $width, 
        $height, 
        $width, 
        $height
      );

    }

    if (function_exists($image_handlers[$type]["save"])) {
      return call_user_func(
        $image_handlers[$type]["save"],
        $truecolor,
        $destination,
        $quality
      );
    } else {
      throw new Exception("'" . $image_handlers[$type]["save"] . "' " . $translation->translate("does not exist"), 500);
    }
  
  }

  public static function length($string) {
    if (function_exists("mb_strlen")) {
      return mb_strlen($string);
    } else {
      return strlen($string);
    }
  }

  public static function callback($function, $arguments, $result, $session, $controls, $identifier) {
    $names = explode("::", $function);
    $classes = explode("\\", $names[0]);
    $namespace = "\\" . $classes[0] . "\\" . "Callback" . "\\" . $classes[1];
    if (class_exists($namespace)) {
      if (method_exists($namespace, $names[1])) {
        $callback = new $namespace($session, $controls, $identifier);
        try {
          $function_name = $names[1];
          return $callback->$function_name($arguments, $result);
        } catch(Exception $e) {
          throw new Exception($e->getMessage(), $e->getCode());
        }
        return $result;
      } else {
        return $result;
      }
    } else {
      return $result;
    }
  }

}