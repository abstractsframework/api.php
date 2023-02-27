<?php
namespace Abstracts\Helpers;

use \Abstracts\Helpers\Initialize;

class Translation {

  public $enable = true;
  public $language = "en";

  /* core */
  private $config = null;

  function __construct($language = "en", $enable = true) {

    /* initialize: core */
    $this->config = Initialize::config();

    if (isset($enable)) {
      $this->enable = $enable;
    }
    if (isset($language)) {
      $this->language = $language;
    }
    if (isset($this->config["language"])) {
      $this->language = $this->config["language"];
    }

  }

  function translate($text, $language = null) {
    if ($this->enable) {

      if (empty($language)) {
        $language = $this->language;
      }
      
      $translation_path = "../translations/" . strtolower($language) . ".json";
      if (file_exists($translation_path) && $translation_file = file_get_contents($translation_path)) {

        $translation = json_decode($translation_file, true);
        
        $translate = $text;
        foreach ($translation as $key => $value) {
          if ($key == $text) {
            $translate = $value;
          }
        }

        return $translate;

      } else {
        return $text;
      }

    } else {
      return $text;
    }
  }

}