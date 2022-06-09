<?php
namespace Abstracts\Core;

class Translation {

  public $enable = false;
  public $language = "en";

  function __construct($language = "en", $enable = true) {
    if (isset($language) && !empty($language)) {
      $this->language = $language;
    }
    if (isset($enable) && !empty($enable)) {
      $this->enable = $enable;
    }
  }

  function translate($text) {
    if ($this->enable) {

      $translation_path = "../translations/" . strtolower($this->language) . ".json";
      if (file_exists($translation_path) && $translation_file = file_get_contents($translation_path)) {

        $translation = json_decode($translation_file, true);

        $translate = $text;
        foreach($translation as $key => $value) {
          if ($key === $text) {
            $translate = $value;
            return;
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