<?php
namespace Abstracts;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;
use \Abstracts\Helpers\Builder;

use \Abstracts\API;
use \Abstracts\Module;
use \Abstracts\Log;

use Exception;

class Abstracts {

  /* configuration */
  public $id = "1";
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
  private $builder = null;

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
    $this->builder = new Builder($this->session);

    /* initialize: services */
    $this->api = new API($this->session, 
      Utilities::override_controls(true, true, true, true)
    );
    $this->log = new Log($this->session, 
      Utilities::override_controls(true, true, true, true)
    );

  }

  function request($function, $parameters) {
    $result = null;
    if ($this->api->authorize($this->id, $function, $this->public_functions)) {
      if ($function == "build") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters["clean"]) ? $parameters["clean"] : false)
        );
      } else if ($function == "import") {
        $result = $this->$function($_FILES);
      } else if ($function == "export") {
        $result = $this->$function();
      } else if ($function == "sync") {
        $result = $this->$function();
      } else if ($function == "get") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters["active"]) ? $parameters["active"] : null),
          (isset($parameters["return_references"]) ? $parameters["return_references"] : false)
        );
      } else if ($function == "list") {
        $result = $this->$function(
          (isset($parameters["start"]) ? $parameters["start"] : null), 
          (isset($parameters["limit"]) ? $parameters["limit"] : null), 
          (isset($parameters["sort_by"]) ? $parameters["sort_by"] : null), 
          (isset($parameters["sort_direction"]) ? $parameters["sort_direction"] : null), 
          (isset($parameters["active"]) ? $parameters["active"] : null), 
          (isset($parameters) ? $parameters : null), 
          (isset($parameters["extensions"]) ? $parameters["extensions"] : null), 
          (isset($parameters["return_references"]) ? $parameters["return_references"] : false)
        );
      } else if ($function == "count") {
        $result = $this->$function(
          (isset($parameters["start"]) ? $parameters["start"] : null), 
          (isset($parameters["limit"]) ? $parameters["limit"] : null), 
          (isset($parameters["active"]) ? $parameters["active"] : null), 
          (isset($parameters) ? $parameters : null), 
          (isset($parameters["extensions"]) ? $parameters["extensions"] : null)
        );
      } else if ($function == "create") {
        $result = $this->$function($parameters);
      } else if ($function == "update") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters) ? $parameters : null)
        );
      } else if ($function == "patch") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null),
          (isset($parameters) ? $parameters : null)
        );
      } else if ($function == "delete") {
        $result = $this->$function(
          (isset($parameters["id"]) ? $parameters["id"] : null)
        );
      } else {
        throw new Exception($this->translation->translate("Function not supported"), 421);
      }
    }
    return $result;
  }

  function build($id) {
    $abstracts_data = $this->get($id, true);
    try {
      $this->builder->database($abstracts_data);
      return true;
    } catch (Exception $e) {
      throw new Exception($this->translation->translate($e->getMessage()), $e->getCode());
    }
  }

  function get($id, $active = null, $return_references = false) {
    if ($this->validation->require($id, "ID")) {

      $active = Initialize::active($active);
      $return_references = Initialize::return_references($return_references);
      
      $filters = array("id" => $id);
      if (isset($active)) {
        $filters["active"] = $active;
      }
      $data = $this->database->select(
        "abstracts", 
        "*", 
        array("id" => $id), 
        null, 
        $this->controls["view"]
      );
      if (!empty($data)) {
        $referers = $this->refer($return_references);
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "low",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        return $this->callback(__METHOD__, func_get_args(), $this->format($data, $return_references, $referers));
      } else {
        return null;
      }
    } else {
      return false;
    }
  }

  function list(
    $start = null, 
    $limit = null, 
    $sort_by = "id", 
    $sort_direction = "desc", 
    $active = null, 
    $filters = array(), 
    $extensions = array(),
    $return_references = false
  ) {
    
    $start = Initialize::start($start);
    $limit = Initialize::limit($limit);
    $sort_by = Initialize::sort_by($sort_by);
    $sort_direction = Initialize::sort_direction($sort_direction);
    $active = Initialize::active($active);
    $filters = Initialize::filters($filters);
    $extensions = Initialize::extensions($extensions);
    $return_references = Initialize::return_references($return_references);
    
    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      if (isset($active)) {
        $filters["active"] = $active;
      }
      $list = $this->database->select_multiple(
        "abstracts", 
        "*", 
        $filters, 
        $extensions, 
        $start, 
        $limit, 
        $sort_by, 
        $sort_direction, 
        $this->controls["view"]
      );
      if (!empty($list)) {
        $referers = $this->refer($return_references);
        $data = array();
        foreach ($list as $value) {
          array_push($data, $this->format($value, $return_references, $referers));
        }
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "low",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          null,
          null
        );
        return $this->callback(__METHOD__, func_get_args(), $data);
      } else {
        return array();
      }
    } else {
      return false;
    }
  }

  function count(
    $start = null, 
    $limit = null, 
    $active = null, 
    $filters = array(), 
    $extensions = array()
  ) {

    $start = Initialize::start($start);
    $limit = Initialize::limit($limit);
    $active = Initialize::active($active);
    $filters = Initialize::filters($filters);
    $extensions = Initialize::extensions($extensions);

    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      if (isset($active)) {
        $filters["active"] = $active;
      }
      if (
        $data = $this->database->count(
          "abstracts", 
          $filters, 
          $extensions, 
          $start, 
          $limit, 
          $this->controls["view"]
        )
      ) {
        return $data;
      } else {
        return 0;
      }
    } else {
      return false;
    }
  }

  function create($parameters, $user_id = 0) {
    
    /* initialize: parameters */
    $parameters = $this->inform($parameters, false, $user_id);

    if ($this->validate($parameters)) {

      $data = $this->database->insert(
        "abstracts", 
        $parameters, 
        $this->controls["create"]
      );
      if (!empty($data)) {
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "normal",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data)
        );
      } else {
        return $data;
      }

    } else {
      return false;
    }

  }

  function update($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters, true);

    if (
      $this->validation->require($id, "ID")
      && $this->validate($parameters, $id)
    ) {
      $data = $this->database->update(
        "abstracts", 
        $parameters, 
        array("id" => $id), 
        null, 
        $this->controls["update"]
      );
      if (!empty($data)) {
        $data = $data[0];
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "normal",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data)
        );
      } else {
        return $data;
      }
    } else {
      return false;
    }

  }

  function patch($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters, true);
    
    if (
      $this->validation->require($id, "ID")
      && $this->validate($parameters, $id, true)
    ) {
      $data = $this->database->update(
        "abstracts", 
        $parameters, 
        array("id" => $id), 
        null, 
        $this->controls["update"]
      );
      if (!empty($data)) {
        $data = $data[0];
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "normal",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data)
        );
      } else {
        return $data;
      }
    } else {
      return false;
    }

  }

  function delete($id) {
    if ($this->validation->require($id, "ID")) {
      $data = $this->database->delete(
        "abstracts", 
        array("id" => $id), 
        null, 
        $this->controls["delete"]
      );
      if (!empty($data)) {
        $data = $data[0];
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "risk",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data)
        );
      } else {
        return $data;
      }
    } else {
      return false;
    }
  }

  function sync() {
    $module = new Module($this->session, Utilities::override_controls(true, true, true, true));
    $module_list = $module->list();
    if (!empty($module_list)) {
      foreach ($module_list as $module_data) {
        $parameters = array(
          "name" => $module_data->name,
          "description" => $module_data->description,
          "template" => $module_data->page_template,
          "icon" => $module_data->icon,
          "category" => $module_data->category,
          "subject" => $module_data->subject,
          "subject_icon" => $module_data->subject_icon,
          "order" => $module_data->order,
        );
        $this->patch($module_data->id, $parameters);
      }
      return true;
    } else {
      return false;
    }
  }

  function import($files) {

    $error = false;

    $connection = $this->database->connect();
    if (!empty($connection)) {
      if (isset($files["abstracts"]) && isset($files["abstracts"]["name"])) {
        $file = $files["abstracts"];
        $file_info = pathinfo($file["name"]);
        $file_extension = strtolower($file_info["extension"]);
        if ($file_extension == "abstracts") {
          $context_options = array(
            "ssl" => array(
              "verify_peer" => false,
              "verify_peer_name" => false,
            )
          );
          $query = file_get_contents(
            $file["tmp_name"], 
            false, 
            stream_context_create($context_options)
          );
          $this->log->log(
            __FUNCTION__,
            __METHOD__,
            "normal",
            func_get_args(),
            (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
            null,
            null
          );
          $result = mysqli_multi_query($connection, $query);
          if (!$result) {
            $error = true;
          }
        } else {
          $error = true;
          throw new Exception($this->translation->translate("Unsupported file"), 415);
        }
      } else {
        $error = true;
        throw new Exception($this->translation->translate("File not found"), 400);
      }
      $this->database->disconnect($connection);
    }

    if ($error === false) {
      return true;
    } else {
      return false;
    }

  }

  function export() {

    $data = null;

    $connection = $this->database->connect();
    if (!empty($connection)) {

      $query_file = "
      TRUNCATE `abstracts`;";

      $query = "
      SELECT * FROM `abstracts`;";
      $result = mysqli_query($connection, $query);
      if ($result) {
        while($row = mysqli_fetch_assoc($result)) {
          $query_file .= "

      INSERT INTO `abstracts` (
        `id`,
        `name`,
        `key`,
        `description`,
        `component_module`,
        `component_group`,
        `component_user`,
        `component_language`,
        `component_page`,
        `component_media`,
        `database_engine`,
        `database_collation`,
        `data_sortable`,
        `template`,
        `icon`,
        `category`,
        `subject`,
        `subject_icon`,
        `order`,
        `create_at`,
        `active`,
        `user_id`
      ) VALUES (
        '" . $row["id"] . "',
        '" . $row["name"] . "',
        '" . $row["key"] . "',
        '" . $row["description"] . "',
        '" . $row["component_module"] . "',
        '" . $row["component_group"] . "',
        '" . $row["component_user"] . "',
        '" . $row["component_language"] . "',
        '" . $row["component_page"] . "',
        '" . $row["component_media"] . "',
        '" . $row["database_engine"] . "',
        '" . $row["database_collation"] . "',
        '" . $row["data_sortable"] . "',
        '" . $row["template"] . "',
        '" . $row["icon"] . "',
        '" . $row["category"] . "',
        '" . $row["subject"] . "',
        '" . $row["subject_icon"] . "',
        '" . $row["order"] . "',
        '" . $row["create_at"] . "',
        '" . $row["active"] . "',
        '" . $row["user_id"] . "'
      );";
      }
      mysqli_free_result($result);

      $query_file .= "

      TRUNCATE `reference`;";

      $query = "
      SELECT * FROM `reference`;";
      $result = mysqli_query($connection, $query);
      if ($result) {
        while($row = mysqli_fetch_assoc($result)) {
          $query_file .= "

      INSERT INTO `reference` (
        `id`,
        `label`,
        `key`,
        `type`,
        `module`,
        `reference`,
        `placeholder`,
        `help`,
        `require`,
        `readonly`,
        `disable`,
        `hidden`,
        `validate_string_min`,
        `validate_string_max`,
        `validate_number_min`,
        `validate_number_max`,
        `validate_decimal_min`,
        `validate_decimal_max`,
        `validate_datetime_min`,
        `validate_datetime_max`,
        `validate_password_equal_to`,
        `validate_email`,
        `validate_password`,
        `validate_url`,
        `validate_no_spaces`,
        `validate_no_special_characters`,
        `validate_no_digit`,
        `validate_uppercase_only`,
        `validate_lowercase_only`,
        `validate_number`,
        `validate_decimal`,
        `validate_unique`,
        `validate_datetime`,
        `validate_key`,
        `default_value`,
        `default_switch`,
        `input_option`,
        `input_option_static_value`,
        `input_option_dynamic_module`,
        `input_option_dynamic_value_key`,
        `input_option_dynamic_label_key`,
        `input_multiple_type`,
        `file_type`,
        `file_lock`,
        `date_format`,
        `color_format`,
        `input_multiple_format`,
        `upload_folder`,
        `image_width`,
        `image_height`,
        `image_width_ratio`,
        `image_height_ratio`,
        `image_quality`,
        `image_thumbnail`,
        `image_thumbnail_aspectratio`,
        `image_thumbnail_quality`,
        `image_thumbnail_width`,
        `image_thumbnail_height`,
        `image_large`,
        `image_large_aspectratio`,
        `image_large_quality`,
        `image_large_width`,
        `image_large_height`,
        `grid_width`,
        `alignment`,
        `order`,
        `create_at`,
        `active`,
        `user_id`
      ) VALUES (
        '" . $row["id"] . "',
        '" . $row["label"] . "',
        '" . $row["key"] . "',
        '" . $row["type"] . "',
        '" . $row["module"] . "',
        '" . $row["reference"] . "',
        '" . $row["placeholder"] . "',
        '" . $row["help"] . "',
        '" . $row["require"] . "',
        '" . $row["readonly"] . "',
        '" . $row["disable"] . "',
        '" . $row["hidden"] . "',
        " . ($row["validate_string_min"] === NULL ? "NULL" :  "'" . $row["validate_string_min"] . "'") . ",
        " . ($row["validate_string_max"] === NULL ? "NULL" :  "'" . $row["validate_string_max"] . "'") . ",
        " . ($row["validate_number_min"] === NULL ? "NULL" :  "'" . $row["validate_number_min"] . "'") . ",
        " . ($row["validate_number_max"] === NULL ? "NULL" :  "'" . $row["validate_number_max"] . "'") . ",
        " . ($row["validate_decimal_min"] === NULL ? "NULL" :  "'" . $row["validate_decimal_min"] . "'") . ",
        " . ($row["validate_decimal_max"] === NULL ? "NULL" :  "'" . $row["validate_decimal_max"] . "'") . ",
        " . ($row["validate_datetime_min"] === NULL ? "NULL" :  "'" . $row["validate_datetime_min"] . "'") . ",
        " . ($row["validate_datetime_max"] === NULL ? "NULL" :  "'" . $row["validate_datetime_max"] . "'") . ",
        '" . $row["validate_password_equal_to"] . "',
        '" . $row["validate_email"] . "',
        '" . $row["validate_password"] . "',
        '" . $row["validate_url"] . "',
        '" . $row["validate_no_spaces"] . "',
        '" . $row["validate_no_special_characters"] . "',
        '" . $row["validate_no_digit"] . "',
        '" . $row["validate_uppercase_only"] . "',
        '" . $row["validate_lowercase_only"] . "',
        '" . $row["validate_number"] . "',
        '" . $row["validate_decimal"] . "',
        '" . $row["validate_unique"] . "',
        '" . $row["validate_datetime"] . "',
        '" . $row["validate_key"] . "',
        '" . $row["default_value"] . "',
        '" . $row["default_switch"] . "',
        '" . $row["input_option"] . "',
        '" . $row["input_option_static_value"] . "',
        '" . $row["input_option_dynamic_module"] . "',
        '" . $row["input_option_dynamic_value_key"] . "',
        '" . $row["input_option_dynamic_label_key"] . "',
        '" . $row["input_multiple_type"] . "',
        '" . $row["file_type"] . "',
        '" . $row["file_lock"] . "',
        '" . $row["date_format"] . "',
        '" . $row["color_format"] . "',
        '" . $row["input_multiple_format"] . "',
        '" . $row["upload_folder"] . "',
        " . ($row["image_width"] === NULL ? "NULL" :  "'" . $row["image_width"] . "'") . ",
        " . ($row["image_height"] === NULL ? "NULL" :  "'" . $row["image_height"] . "'") . ",
        " . ($row["image_width_ratio"] === NULL ? "NULL" :  "'" . $row["image_width_ratio"] . "'") . ",
        " . ($row["image_height_ratio"] === NULL ? "NULL" :  "'" . $row["image_height_ratio"] . "'") . ",
        '" . $row["image_quality"] . "',
        " . ($row["image_thumbnail"] === NULL ? "NULL" :  "'" . $row["image_thumbnail"] . "'") . ",
        '" . $row["image_thumbnail_aspectratio"] . "',
        '" . $row["image_thumbnail_quality"] . "',
        '" . $row["image_thumbnail_width"] . "',
        '" . $row["image_thumbnail_height"] . "',
        " . ($row["image_large"] === NULL ? "NULL" :  "'" . $row["image_large"] . "'") . ",
        '" . $row["image_large_aspectratio"] . "',
        '" . $row["image_large_quality"] . "',
        '" . $row["image_large_width"] . "',
        '" . $row["image_large_height"] . "',
        '" . $row["grid_width"] . "',
        '" . $row["alignment"] . "',
        '" . $row["order"] . "',
        '" . $row["create_at"] . "',
        '" . $row["active"] . "',
        '" . $row["user_id"] . "'
      );";
          }
          $data = $query_file;
          mysqli_free_result($result);
        }
      }

      $this->database->disconnect($connection);

    }

    return $data;

  }

  function inform($parameters, $update = false, $user_id = 0) {
    if (!empty($parameters)) {
      if (!$update) {
        if (isset($parameters["id"])) {
          $parameters["id"] = $parameters["id"];
        } else {
          $parameters["id"] = null;
        }
        $parameters["active"] = (isset($parameters["active"]) ? $parameters["active"] : true);
        $parameters["user_id"] = (!empty($user_id) ? $user_id : (!empty($this->session) ? $this->session->id : 0));
        $parameters["create_at"] = gmdate("Y-m-d H:i:s");
      } else {
        unset($parameters["id"]);
        unset($parameters["create_at"]);
      }
    }
    return $parameters;
  }

  function refer($return_references = false, $abstracts_override = null) {

    $data = array();
    
    if (!empty($return_references)) {
      if ($return_references === true || (is_array($return_references) && in_array("user_id", $return_references))) {
        $data["user_id"] = new User($this->session, Utilities::override_controls(true, true, true, true));
      }
    }

    return $this->callback(__METHOD__, func_get_args(), $data);

  }

  function format($data, $return_references = false, $referers = null) {
    if (!empty($data)) {
      
      if ($data->active === "1") {
        $data->active = true;
      } else if ($data->active === "0" || empty($data->active)) {
        $data->active = false;
      }

      $extensions = array(
        array(
          "conjunction" => "",
          "key" => "reference",
          "operator" => "=",
          "value" => "''"
        ),
        array(
          "conjunction" => "OR",
          "key" => "reference",
          "operator" => "=",
          "value" => "NULL"
        )
      );
      $reference_list = $this->database->select_multiple(
        "reference", 
        "*", 
        array("module" => $data->id), 
        $extensions, 
        null, 
        null, 
        "order", 
        "asc", 
        true,
        false
      );
      if (!empty($reference_list)) {
        for ($i = 0; $i < count($reference_list); $i++) {
          if ($reference_list[$i]->active === "1") {
            $reference_list[$i]->active = true;
          } else if ($reference_list[$i]->active === "0" || empty($reference_list[$i]->active)) {
            $reference_list[$i]->active = false;
          }
          if (isset($reference_list[$i]->input_option_static_value) && !empty($reference_list[$i]->input_option_static_value)) {
            $reference_list[$i]->input_option_static_value = explode(",", $reference_list[$i]->input_option_static_value);
          }
          $reference_multiple_list = $this->database->select_multiple(
            "reference", 
            "*", 
            array("reference" => $reference_list[$i]->id), 
            null, 
            null, 
            null, 
            "order", 
            "asc", 
            true,
            false
          );
          for ($j = 0; $j < count($reference_multiple_list); $j++) {
            if ($reference_multiple_list[$j]->active === "1") {
              $reference_multiple_list[$j]->active = true;
            } else if ($reference_multiple_list[$j]->active === "0" || empty($reference_multiple_list[$j]->active)) {
              $reference_multiple_list[$j]->active = false;
            }
            if (isset($reference_multiple_list[$j]->input_option_static_value) && !empty($reference_multiple_list[$j]->input_option_static_value)) {
              $reference_multiple_list[$j]->input_option_static_value = explode(",", $reference_multiple_list[$j]->input_option_static_value);
            }
          }
          $reference_list[$i]->references = $reference_multiple_list;
        }
      }
      $data->references = $reference_list;

      if (is_array($referers) && !empty($referers)) {
        if ($return_references === true || (is_array($return_references) && in_array("user_id", $return_references))) {
          if (isset($referers["user_id"])) {
            $data->user_id_reference = $referers["user_id"]->format(
              $this->database->get_reference(
                $data->user_id,
                "user",
                "id"
              )
            );
          }
        }
      }

    }
		return $data;
  }

  function validate($parameters, $target_id = null, $patch = false) {
    if (!empty($parameters)) {
      return true;
    } else {
      throw new Exception($this->translation->translate("Bad request"), 400);
    }
  }

  function callback($function, $arguments, $result) {

    $names = explode("::", $function);
    $classes = explode("\\", $names[0]);
    $namespace = "\\" . $classes[0] . "\\" . "Callback" . "\\" . $classes[1];
    if (class_exists($namespace)) {
      if (method_exists($namespace, $names[1])) {
        $callback = new $namespace($this->session, $this->controls, $this->id);
        try {
          $function_name = $names[1];
          return $callback->$function_name($arguments, $result);
        } catch(Exception $e) {
          throw new Exception($e->getMessage(), $e->getCode());
        }
      } else {
        return $result;
      }
    } else {
      return $result;
    }

  }

}