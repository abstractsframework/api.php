<?php
namespace Abstracts\Helpers;

use \Abstracts\Helpers\Initialize;

class Security {

  /* core */
  private $config = null;

  function __construct() {
    
    /* initialize: core */
    $this->config = Initialize::config();

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