<?php
namespace Abstracts\Helpers;

class Security {

  /* core */
  private $config = null;

  function __construct($config) {
    
    /* initialize: core */
    $this->config = $config;

  }

  function verify_nonce($nonce) {
    $result = false;
    if (isset($this->config["nonce"])) {
      if (!empty($nonce)) {
        $result = true;
      }
    } else {
      $result = true;
    }
    return $result;
  }

}