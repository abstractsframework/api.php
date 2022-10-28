<?php
namespace Abstracts;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use \Abstracts\API;

use Exception;

class Device {

  /* configuration */
  public $id = "19";
  public $public_functions = array();
  public $module = null;

  /* core */
  private $config = null;
  private $session = null;
  private $controls = null;

  /* helpers */
  private $database = null;
  private $validation = null;
  private $translation = null;

  /* services */
  private $api = null;
  private $log = null;

  function __construct(
    $session = null,
    $controls = null
  ) {

    /* initialize: core */
    $initialize = new Initialize($session, $controls, $this->id);
    $this->config = $initialize->config;
    $this->session = $initialize->session;
    $this->controls = $initialize->controls;
    $this->module = $initialize->module;
    
    /* initialize: helpers */
    $this->database = new Database($this->session, $this->controls);
    $this->validation = new Validation();
    $this->translation = new Translation();

    /* initialize: services */
    $this->api = new API($this->session, 
      Utilities::override_controls(true, true, true, true)
    );
    $this->log = new Log($this->session, 
      Utilities::override_controls(true, true, true, true)
    );

  }

  function get_device($user_agent = null) {

    if (!isset($user_agent) || empty($user_agent)) {
      $user_agent = $_SERVER["HTTP_USER_AGENT"];
    }

    $platform = $this->get_platform($user_agent);
    $browser = $this->get_client($user_agent);
    $os = $this->get_os($user_agent);
    $is_mobile = $this->is_mobile($user_agent);
    $is_native = $this->is_native($user_agent);
    
    return (object) array(
      "platform" => $platform,
      "browser" => $browser,
      "os" => $os,
      "is_mobile" => $is_mobile,
      "is_native" => $is_native
    );
  
  }

  function get_platform($user_agent = null) {

    if (!isset($user_agent) || empty($user_agent)) {
      $user_agent = $_SERVER["HTTP_USER_AGENT"];
    }

    $is_native = $this->is_native($user_agent);

    $platform  = "unknown";

    $platform_array = array(
      '/windows/i' => 'Windows',
      '/win98/i' => 'Windows',
      '/win95/i' => 'Windows',
      '/win16/i' => 'Windows',
      '/macintosh|mac os x/i' => 'Mac OS',
      '/mac_powerpc/i' => 'Mac OS',
      '/linux/i' => 'Linux',
      '/ubuntu/i' => 'Ubuntu',
      '/iphone/i' => 'iOS',
      '/ipod/i' => 'iOS',
      '/ipad/i' => 'iOS',
      '/android/i' => 'Android',
      '/blackberry/i' => 'BlackBerry',
      '/webos/i' => 'Web OS',
      '/PostmanRuntime/i' => 'PostMan'
    );

    foreach ($platform_array as $regex => $value) {
      if (preg_match($regex, $user_agent)) {
        if ($is_native) {
          $platform = $value;
        } else {
          $platform = "Browser";
        }
      }
    }

    return $platform;

  }

  function get_os($user_agent = null) {

    if (!isset($user_agent) || empty($user_agent)) {
      $user_agent = $_SERVER["HTTP_USER_AGENT"];
    }

    $os  = "unknown";
    $os_version  = "unknown";

    $os_array = array(
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+windows\s([a-zA-Z0-9\.\_\s]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Windows',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+win98\s([a-zA-Z0-9\.\_\s]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Windows 98',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+win95\s([a-zA-Z0-9\.\_\s]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Windows 95',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+win16\s([a-zA-Z0-9\.\_\s]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Windows 3.11',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+macintosh\s([a-zA-Z0-9\.\_\s]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Mac OS',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+mac os x\s([0-9\.]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Mac OS',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+mac os x\s([0-9\.]+)\)/i' =>  'Mac OS',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+mac_powerpc\s([a-zA-Z0-9\.\_\s]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Mac OS',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+linux\s([a-zA-Z0-9\_\.]+)\)[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Linux',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+linux\s([a-zA-Z0-9\_\.]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Linux',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+ubuntu\s([a-zA-Z0-9\.\_\s]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Ubuntu',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+iphone os\s([a-zA-Z0-9\.\_]+)\s[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'iOS',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+iphone os\s([0-9\.]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'iOS',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+android\s([a-zA-Z0-9\.\_\s]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' => 'Android',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+blackberry\s([a-zA-Z0-9\.\_\s]+)\;[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Blackberry',
      '/[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+webos[a-zA-Z0-9\_\-\+\/\.\,\(\)\:\;\s]+/i' =>  'Web OS',
      '/postmanruntime\/([a-zA-Z0-9\.\_\s]+)/i' =>  'PostMan'
    );

    foreach ($os_array as $regex => $value) {
      if (preg_match($regex, $user_agent)) {
        $os = $value;
        $version = preg_replace($regex, '$1', $user_agent);
        if ($os == "Windows") {
          if (
            $os == "Windows 98"
            || $os == "Windows 95"
            || $os == "Windows 3.11"
          ) {
            $os_version = $value;
            $os = str_replace(" " . $os_version, "", $os);
          } else {
            if ($version = "NT 10.0") {
              $os_version = "10";
            } else if ($version = "NT 6.3") {
              $os_version = "8.1";
            } else if ($version = "NT 6.2") {
              $os_version = "8";
            } else if ($version = "NT 6.1") {
              $os_version = "7";
            } else if ($version = "NT 6.0") {
              $os_version = "Vista";
            } else if ($version = "NT 5.2") {
              $os_version = "Server 2003/XP x64";
            } else if ($version = "NT 5.1") {
              $os_version = "XP";
            } else {
              $os_version = $version;
            }
          }
        } else if ($os == "iOS") {
          $os_version = str_replace("_", ".", $version);
        } else if ($os == "Web OS") {
          $os_version = "unknown";
        } else {
          $os_version = $version;
        }
      }
    }

    return (object) array(
      "name" => $os,
      "version" => $os_version
    );

  }

  function get_client($user_agent = null) {

    if (!isset($user_agent) || empty($user_agent)) {
      $user_agent = $_SERVER["HTTP_USER_AGENT"];
    }

    $browser = "unknown";

    $browser_array = array(
      '/msie/i'      => "Internet Explorer",
      '/firefox/i'   => "Firefox",
      '/safari/i'    => "Safari",
      '/chrome/i'    => "Chrome",
      '/edge/i'      => "Edge",
      '/opera/i'     => "Opera",
      '/netscape/i'  => "Netscape",
      '/maxthon/i'   => "Maxthon",
      '/konqueror/i' => "Konqueror",
      '/postmanruntime/i'    => "",
      '/mobile/i'    => ""
    );

    foreach ($browser_array as $regex => $value) {
      if (preg_match($regex, $user_agent)) {
        $browser = $value;
      }
    }

    return $browser;

  }

  function is_mobile($user_agent = null) {

    if (!isset($user_agent) || empty($user_agent)) {
      $user_agent = $_SERVER["HTTP_USER_AGENT"];
    }

    $is_mobile = false;

    $mobile_array = array(
      '/msie/i' => false,
      '/firefox/i' => false,
      '/safari/i' => false,
      '/chrome/i' => false,
      '/edge/i' => false,
      '/opera/i' => false,
      '/netscape/i' => false,
      '/maxthon/i' => false,
      '/konqueror/i' => false,
      '/mobile/i' => true
    );

    foreach ($mobile_array as $regex => $value) {
      if (preg_match($regex, $user_agent)) {
        $is_mobile = $value;
      }
    }

    return $is_mobile;

  }

  function is_native($user_agent = null) {

    if (!isset($user_agent) || empty($user_agent)) {
      $user_agent = $_SERVER["HTTP_USER_AGENT"];
    }

    $is_native = false;

    $browser = $this->get_client($user_agent);
    if (empty($browser)) {
      $is_native = true;
    }

    return $is_native;

  }

}