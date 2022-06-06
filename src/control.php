<?php
namespace Abstracts;

use \Abstracts\Database;
use \Abstracts\Validation;
use \Abstracts\Translation;
use \Abstracts\Utilities;

use Exception;

class Control {

  /* configuration */
  private $id = "14";
  private $public_functions = array(
	);

  /* initialization */
  public $module = null;
  private $config = null;
  private $session = null;
  private $controls = null;

  /* helpers */
  private $database = null;
  private $validation = null;
  private $translation = null;
  private $utilities = null;

  function __construct(
    $config,
    $session = null,
    $controls = null,
    $module = null
  ) {

    $this->module = $module;
    $this->config = $config;
    $this->session = $session;
    $this->controls = Utilities::sync_control(
      $this->id, 
      $session, 
      $controls,
      $module
    );
    
    $this->database = new Database($this->config, $this->session, $this->controls);
    $this->validation = new Validation($this->config);
    $this->translation = new Translation();
    $this->utilities = new Utilities();

    $this->initialize();

  }

  function initialize() {
    if (empty($this->module)) {
      $this->module = $this->database->select(
        "module", 
        "*", 
        array("id" => $this->id), 
        null, 
        true
      );
    }
  }

}