<?php
namespace Abstracts\Helpers;

use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use Exception;

class Validation {

  /* core */
  private $config = null;

  /* helpers */
  private $database = null;
  private $translation = null;
  private $utilities = null;

  function __construct($config) {

    /* initialize: core */
    $this->config = $config;

    /* initialize: helpers */
    $this->database = new Database($this->config);
    $this->translation = new Translation();
    $this->utilities = new Utilities();

  }

  function set($parameters, $key) {
    $result = true;
    $result = true;
    $message = $this->translation->translate("Parameters must contain") . " '" . $key . "'";
    if (!isset($parameters[$key])) {
      $result = false;
      throw new Exception($message, 400);
    }
    return $result;
  }

  function require($value, $name) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is required");
    if ($this->is_empty($value)) {
      $result = false;
      throw new Exception($message, 400);
    }
    return $result;
  }

  function number($value, $name) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("allows only numbers");
    if (!$this->is_empty($value)) {
      if (!is_numeric($value)) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function number_min($value, $name, $minimum) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is less than minimum number at") . " '" . $minimum . "'";
    if (!$this->is_empty($value)) {
      if ($this->number($value, $name)) {
        if ($this->number($minimum, $name)) {
          if (intval($value) < intval($minimum)) {
            $result = false;
            throw new Exception($message, 400);
          }
        } else {
          $result = false;
          throw new Exception($this->translation->translate("Criteria") . " " . $this->translation->translate("allows only numbers"), 500);
        }
      } else {
        $result = false;
      }
    }
    return $result;
  }

  function number_max($value, $name, $maximum) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is more than maximum number at") . " '" . $maximum . "'";
    if (!$this->is_empty($value)) {
      if ($this->number($value, $name)) {
        if ($this->number($maximum, $name)) {
          if (intval($value) > intval($maximum)) {
            $result = false;
            throw new Exception($message, 400);
          }
        } else {
          $result = false;
          throw new Exception($this->translation->translate("Criteria") . " " . $this->translation->translate("allows only numbers"), 500);
        }
      } else {
        $result = false;
      }
    }
    return $result;
  }

  function decimal($value, $name) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("allows only decimal");
    if (!$this->is_empty($value)) {
      if (!is_numeric(str_replace(",", "", $value))) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function decimal_min($value, $name, $minimum) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is less than minimum number at") . " '" . $minimum . "'";
    if (!$this->is_empty($value)) {
      if ($this->decimal($value, $name)) {
        if ($this->decimal($minimum, $name)) {
          if (floatval(str_replace(",", "", $value)) < floatval(str_replace(",", "", $minimum))) {
            $result = false;
            throw new Exception($message, 400);
          }
        } else {
          $result = false;
          throw new Exception($this->translation->translate("Criteria") . " " . $this->translation->translate("allows only decimal"), 500);
        }
      } else {
        $result = false;
      }
    }
    return $result;
  }

  function decimal_max($value, $name, $maximum) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is more than maximum number at") . " '" . $maximum . "'";
    if (!$this->is_empty($value)) {
      if ($this->decimal($value, $name)) {
        if ($this->decimal($maximum, $name)) {
          if (floatval(str_replace(",", "", $value)) > floatval(str_replace(",", "", $maximum))) {
            $result = false;
            throw new Exception($message, 400);
          }
        } else {
          $result = false;
        }
      } else {
        $result = false;
      }
    }
    return $result;
  }

  function datetime($value, $name) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("allows only date/time");
    if (!$this->is_empty($value)) {
      if (!strtotime($value) && !strtotime($value . " 00:00:00")) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function datetime_min($value, $name, $minimum) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is before minimum date and time at") . " '" . $minimum . "'";
    if (!$this->is_empty($value)) {
      if ($this->datetime($value, $name)) {
        if ($this->datetime($minimum, $name)) {
          $datetime = strtotime($value . " 00:00:00");
          if (strtotime($value)) {
            $datetime = strtotime($value);
          }
          $minimum_datetime = strtotime($minimum . " 00:00:00");
          if (strtotime($value)) {
            $minimum_datetime = strtotime($minimum);
          }
          if ($datetime < $minimum_datetime) {
            $result = false;
            throw new Exception($message, 400);
          }
        } else {
          $result = false;
          throw new Exception($this->translation->translate("Criteria") . " " . $this->translation->translate("allows only date/time"), 500);
        }
      } else {
        $result = false;
      }
    }
    return $result;
  }

  function datetime_max($value, $name, $maximum) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is after maximum date and time at") . " '" . $maximum . "'";
    if (!$this->is_empty($value)) {
      if ($this->datetime($value, $name)) {
        if ($this->datetime($maximum, $name)) {
          $datetime = strtotime($value . " 00:00:00");
          if (strtotime($value)) {
            $datetime = strtotime($value);
          }
          $maximum_datetime = strtotime($maximum . " 00:00:00");
          if (strtotime($value)) {
            $maximum_datetime = strtotime($maximum);
          }
          if ($datetime > $maximum_datetime) {
            $result = false;
            throw new Exception($message, 400);
          }
        } else {
          $result = false;
          throw new Exception($this->translation->translate("Criteria") . " " . $this->translation->translate("allows only date/time"), 500);
        }
      } else {

      }
    }
    return $result;
  }

  function string_min($value, $name, $minimum) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is shorter than minimum length of strings at") . " '" . $minimum . "' " . $this->translation->translate("character(s)");
    if (!$this->is_empty($value)) {
      if (Utilities::length(strval($value)) < $minimum) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function string_max($value, $name, $maximum) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is longer than maximum length of strings at") . " '" . $maximum . "' " . $this->translation->translate("character(s)");
    if (!$this->is_empty($value)) {
      if (Utilities::length(strval($value)) > $maximum) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function email($value, $name) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is invalid email");
    $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
    if (!$this->is_empty($value)) {
      if (!preg_match($pattern, $value)) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function password($value, $name) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("must contain uppercases, lowercases, digits and special characters");
    $pattern = "/(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[\W_])^.*/";
    if (!$this->is_empty($value)) {
      if (!preg_match($pattern, $value)) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function password_equal_to($value, $name, $target) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is mismatched");
    if (!$this->is_empty($value)) {
      if ($value !== $target) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function url($value, $name) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("is invalid url");
    $pattern = "/^(?:[a-z](?:[-a-z0-9\+\.])*:(?:\/\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:])*@)?(?:\[(?:(?:(?:[0-9a-f]{1,4}:){6}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|::(?:[0-9a-f]{1,4}:){5}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){4}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,1}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){3}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,2}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){2}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,3}[0-9a-f]{1,4})?::[0-9a-f]{1,4}:(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,4}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,5}[0-9a-f]{1,4})?::[0-9a-f]{1,4}|(?:(?:[0-9a-f]{1,4}:){0,6}[0-9a-f]{1,4})?::)|v[0-9a-f]+\.[-a-z0-9\._~!\$&'\(\)\*\+,;=:]+)\]|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}|(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=])*)(?::[0-9]*)?(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@]))*)*|\/(?:(?:(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@]))+)(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@]))*)*)?|(?:(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@]))+)(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@]))*)*|(?!(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@])))(?:\?(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@])|[\x{E000}-\x{F8FF}\x{F0000}-\x{FFFFD}\x{100000}-\x{10FFFD}\/\?])*)?(?:\#(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@])|[\/\?])*)?|(?:\/\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:])*@)?(?:\[(?:(?:(?:[0-9a-f]{1,4}:){6}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|::(?:[0-9a-f]{1,4}:){5}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){4}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,1}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){3}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,2}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){2}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,3}[0-9a-f]{1,4})?::[0-9a-f]{1,4}:(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,4}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,5}[0-9a-f]{1,4})?::[0-9a-f]{1,4}|(?:(?:[0-9a-f]{1,4}:){0,6}[0-9a-f]{1,4})?::)|v[0-9a-f]+\.[-a-z0-9\._~!\$&'\(\)\*\+,;=:]+)\]|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}|(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=])*)(?::[0-9]*)?(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@]))*)*|\/(?:(?:(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@]))+)(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@]))*)*)?|(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=@])+)(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@]))*)*|(?!(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@])))(?:\?(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@])|[\x{E000}-\x{F8FF}\x{F0000}-\x{FFFFD}\x{100000}-\x{10FFFD}\/\?])*)?(?:\#(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&'\(\)\*\+,;=:@])|[\/\?])*)?)$/i";
    if (!$this->is_empty($value)) {
      if (!preg_match($pattern, $value)) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function unique($value, $name, $key, $table, $excluding_id = null) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("can not be duplicated");
    if (!$this->is_empty($value)) {
      $extensions = array(
        array(
          "conjunction" => "",
          "key" => $key,
          "operator" => "LIKE",
          "value" => "'" . $value . "'"
        )
      );
      if ($excluding_id) {
        array_push(
          $extensions,
          array(
            "conjunction" => "AND",
            "key" => "id",
            "operator" => "!=",
            "value" => "'" . $excluding_id . "'"
          )
        );
      }
      if (
        $this->database->select(
          $table, 
          array($key), 
          null, 
          $extensions, 
          true
        )
      ) {
        $result = false;
        throw new Exception($message, 409);
      }
    }
    return $result;
  }

  function no_spaces($value, $name) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("does not allow white spaces");
    if (!$this->is_empty($value)) {
      if (function_exists("str_contains")) {
        if (str_contains($value, " ")) {
          $result = false;
          throw new Exception($message, 400);
        }
      } else {
        if (strpos($value, " ") >= 0) {
          $result = false;
          throw new Exception($message, 400);
        }
      }
    }
    return $result;
  }

  function no_special_characters($value, $name, $level = "basic") {
    $result = true;
    $pattern = "/[^a-zA-Z\d\.\_]/";
    $message = $this->translation->translate($name) . " " . $this->translation->translate("does not allow special characters and white spaces excluding ., _");
    $pattern_strict = "/[^a-zA-Z\d]/";
    $message_strict = $this->translation->translate($name) . " " . $this->translation->translate("does not allow special characters and white spaces");
    if (!$this->is_empty($value)) {
      if ($level != "strict") {
        if (preg_match($pattern, $value)) {
          $result = false;
          throw new Exception($message, 400);
        }
      } else {
        if (preg_match($pattern_strict, $value)) {
          $result = false;
          throw new Exception($message_strict, 400);
        }
      }
    }
    return $result;
  }

  function no_digit($value, $name) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("does not allow digit");
    $pattern = "/^[0-9]+$/";
    if (!$this->is_empty($value)) {
      if (preg_match($pattern, $value)) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function uppercase_only($value, $name) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("allows only uppercase character(s)");
    $pattern = "/^[A-Z0-9]+$/";
    if (!$this->is_empty($value)) {
      if (!preg_match($pattern, $value)) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function lowercase_only($value, $name) {
    $result = true;
    $message = $this->translation->translate($name) . " " . $this->translation->translate("allows only lowercase character(s)");
    $pattern = "/^[a-z0-9]+$/";
    if (!$this->is_empty($value)) {
      if (!preg_match($pattern, $value)) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function key($value, $name) {
    $result = true;
    $pattern = "/^[a-zA-Z\d\.\_]+$/";
    $message = $this->translation->translate($name) . " " . $this->translation->translate("does not allow special characters and white spaces excluding ., _");
    if (!$this->is_empty($value)) {
      if (!preg_match($pattern, $value)) {
        $result = false;
        throw new Exception($message, 400);
      }
    }
    return $result;
  }

  function filters($values) {
    $result = true;
    $message = $this->translation->translate("Unsupported format for") . " 'filters'";
    if (!$this->is_empty($values)) {
      if (!is_array($values)) {
        $result = false;
        throw new Exception($message, 400);
      } else {
        $error = false;
        foreach($values as $key => $value) {
          if (!is_string($key) || is_array($value)) {
            $error = true;
          }
        }
        if ($error) {
          $result = false;
          throw new Exception($message, 400);
        }
      }
    }
    return $result;
  }

  function extensions($values) {
    $result = true;
    $message = $this->translation->translate("Unsupported format for") . " 'extensions'";
    if (!$this->is_empty($values)) {
      if (!is_array($values)) {
        $result = false;
        throw new Exception($message, 400);
      } else {
        $error = false;
        foreach($values as $key => $value) {
          if (is_array($value)) {
            foreach($value as $value_key => $value_value) {
              if ($value_key == "extensions") {
                $error = !$this->extensions($value_value);
              } else {
                if (
                  $value_key != "conjunction" 
                  && $value_key != "key"
                  && $value_key != "operator"
                  && $value_key != "value"
                  && $value_key != "extensions"
                ) {
                  $error = true;
                } else {
                  if (
                    $value_key == "operator" 
                    && !in_array(strtoupper($value_value), Database::$comparisons)
                  ) {
                    $error = true;
                  }
                }
              }
            }
          } else {
            $error = true;
          }
        }
        if ($error) {
          $result = false;
          throw new Exception($message, 400);
        }
      }
    }
    return $result;
  }

  private function is_empty($value) {
    $result = false;
    if (!isset($value) || (empty($value) && $value !== 0)) {
      $result = true;
    }
    return $result;
  }

}