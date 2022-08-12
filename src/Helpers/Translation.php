<?php
namespace Abstracts\Helpers;

use \Abstracts\Helpers\Initialize;

class Translation {

  public $enable = true;
  public $language = "en";

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

  function translate($text) {
    if ($this->enable) {
      
      $translation_path = "../translations/" . strtolower($this->language) . ".json";
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