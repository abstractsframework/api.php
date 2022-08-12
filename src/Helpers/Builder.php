<?php 
namespace Abstracts\Helpers;

use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Utilities;

use \Abstracts\Module;
use \Abstracts\Control;
use \Abstracts\API;
use \Abstracts\Group;

use Exception;

class Builder {

  public $core_modules_key = array(
    "abstracts",
    "reference",
    "config",
    "module",
    "user",
    "group",
    "language",
    "page",
    "media",
    "log",
    "api",
    "lock",
    "control",
    "process",
    "connect",
    "notification",
    "mail",
    "device",
    "audience",
    "payment",
    "purchase",
    "transaction",
    "inad"
  );
  private $types = array(
    "input-text",
    "input-number",
    "input-decimal",
    "input-password",
    "input-date",
    "input-datetime",
    "input-time",
    "input-tags",
    "input-file",
    "input-file-multiple",
    "input-file-multiple-drop",
    "textarea",
    "text-editor",
    "select",
    "select-select2",
    "select-multiple",
    "select-multiple-select2",
    "file-selector",
    "image-upload",
    "switch",
    "checkbox",
    "checkbox-inline",
    "checkbox-radio",
    "radio",
    "radio-inline",
    "input-color",
    "range-slider",
    "range-slider-double",
    "input-multiple"
  );
  private $text_types = array(
    "input-text",
    "input-password",
    "input-tags",
    "input-file",
    "input-file-multiple",
    "input-file-multiple-drop",
    "textarea",
    "text-editor",
    "select",
    "select-select2",
    "select-multiple",
    "select-multiple-select2",
    "file-selector",
    "image-upload",
    "switch",
    "checkbox",
    "checkbox-inline",
    "checkbox-radio",
    "radio",
    "radio-inline",
    "input-color",
    "range-slider",
    "range-slider-double",
    "input-multiple"
  );
  private $number_types = array(
    "input-number"
  );
  private $decimal_types = array(
    "input-decimal"
  );
  private $switch_types = array(
    "switch",
    "checkbox-radio"
  );
  private $enum_types = array(
    "select",
    "select-select2",
    "select-multiple",
    "select-multiple-select2",
    "switch",
    "radio",
    "radio-inline"
  );
  private $date_types = array(
    "input-date"
  );
  private $datetime_types = array(
    "input-datetime",
    "input-time"
  );
  private $file_types = array(
    "input-file",
    "input-file-multiple",
    "input-file-multiple-drop",
    "image-upload"
  );
  private $multiple_types = array(
    "select-multiple",
    "select-multiple-select2",
    "input-file-multiple",
    "input-file-multiple-drop",
    "file-selector-multiple",
    "input-multiple",
    "input-tags",
    "checkbox",
    "checkbox-inline"
  );

  /* core */
  private $config = null;
  private $session = null;

  /* helpers */
  private $translation = null;

  function __construct(
    $session = null
  ) {

    /* initialize: core */
    $this->config = Initialize::config();
    $this->session = $session;

    /* initialize: helpers */
    $this->translation = new Translation();

  }

  function database($abstracts, $clean = false) {
    
    if (!empty($abstracts)) {

      $abstracts_key = $abstracts->key;
      if (in_array($abstracts->key, $this->core_modules_key)) {
        $abstracts_key = $abstracts->key . "_" . $abstracts->id;
      }

      $query = "";
      $exists = false;
    
      $database = new Database($this->session, Utilities::override_controls(true, true, true, true));

      $columns = $database->columns($abstracts->key);
      $reference_exist = array_map(function($value) { return $value["COLUMN_NAME"]; }, $columns);

      $query_columns = array();
      $query_indexes = array();
      $query_drops = array();
      $previous_reference_key = "id";

      if (!empty($abstracts->component_page)) {
        if (!in_array("title", $reference_exist)) {
          array_push($query_columns, "ADD `title` TEXT NULL DEFAULT '' AFTER `" . $previous_reference_key ."`");
          array_push($query_indexes, "ADD INDEX(`title`)");
          $previous_reference_key = "title";
        }
      }

      $reference_save = array_map(function($value) { return $value->key; }, $abstracts->references);
      foreach ($reference_exist as $reference) {
        $core_modules_id_key = array_map(function($value) { return $value . "_id"; }, $this->core_modules_key);
        array_push($core_modules_id_key, "id");
        array_push($core_modules_id_key, "active");
        array_push($core_modules_id_key, "create_at");
        array_push($core_modules_id_key, "title");
        array_push($core_modules_id_key, "translate");
        array_push($core_modules_id_key, "order");
        array_push($core_modules_id_key, "module_key");
        array_push($core_modules_id_key, "module_value");
        if (
          !in_array($reference, $reference_save)
          && !in_array($reference, $core_modules_id_key)
        ) {
          array_push($query_drops, "DROP `" . $reference . "`");
        }
      }

      foreach ($abstracts->references as $reference) {
        if (in_array($reference->type, $this->types)) {

          $data_type = "TEXT";
          $data_default_value = "";
          if (!empty($reference->validate_string_max)) {
            $data_type = "VARCHAR(" . $reference->validate_string_max . ")";
            $data_default_value = "NULL DEFAULT NULL";
          }
          if (!in_array($reference->type, $this->text_types)) {
            $data_default_value = "NULL DEFAULT NULL";
          }
          if (in_array($reference->type, $this->number_types)) {
            $data_type = "INT";
            $data_default_value = "NULL DEFAULT '0'";
          } else if (in_array($reference->type, $this->decimal_types)) {
            $data_type = "DECIMAL";
            $data_default_value = "NULL DEFAULT '0'";
          } else if (in_array($reference->type, $this->date_types)) {
            $data_type = "DATE";
            $data_default_value = "NULL DEFAULT NULL";
          } else if (in_array($reference->type, $this->datetime_types)) {
            $data_type = "DATETIME";
            $data_default_value = "NULL DEFAULT NULL";
          } else if (in_array($reference->type, $this->switch_types)) {
            if (empty($reference->default_value)) {
              $data_type = "TINYINT(1)";
              $data_default_value = "NULL DEFAULT '0'";
            }
          }
          if (
            !empty($reference->default_value)
            || $reference->default_value === 0
            || $reference->default_value === "0"
          ) {
            $data_default_value = "NULL DEFAULT '" . $reference->default_value . "'";
          }
          if (!empty($reference->require)) {
            $data_default_value = "NOT NULL";
          }

          $position = "";
          if (!empty($previous_reference_key)) {
            $position = "AFTER `" . $previous_reference_key . "`";
          }

          if (in_array($reference->key, $reference_exist)) {
            array_push($query_columns, "CHANGE `" . $reference->key . "` `" . $reference->key . "` " . $data_type . " " . $data_default_value);
          } else {
            array_push($query_columns, "ADD `" . $reference->key . "` " . $data_type . " " . $data_default_value . " " . $position);
          }

          if (!empty($reference->validate_unique)) {
            array_push($query_indexes, "ADD UNIQUE(`" . $reference->key . "`)");
          }

        }
        $previous_reference_key = $reference->key;
      }

      if (!in_array("create_at", $reference_exist)) {
        array_push($query_columns, "ADD `create_at` DATETIME NULL DEFAULT NOW() AFTER `" . $previous_reference_key . "`");
        array_push($query_indexes, "ADD INDEX(`create_at`)");
        $previous_reference_key = "create_at";
      }
      if (!in_array("active", $reference_exist)) {
        array_push($query_columns, "ADD `active` TINYINT(1) NULL DEFAULT '0' AFTER `" . $previous_reference_key . "`");
        array_push($query_indexes, "ADD INDEX(`active`)");
        $previous_reference_key = "active";
      }
      if (!empty($abstracts->data_sortable)) {
        if (!in_array("order", $reference_exist)) {
          array_push($query_columns, "ADD `order` INT NULL DEFAULT '0' AFTER `" . $previous_reference_key . "`");
          array_push($query_indexes, "ADD INDEX(`order`)");
          $previous_reference_key = "order";
        }
      }
      if (!empty($abstracts->component_module)) {
        if (!in_array("module_id", $reference_exist)) {
          array_push($query_columns, "ADD `module_id` INT NULL DEFAULT NULL AFTER `" . $previous_reference_key . "`");
          array_push($query_indexes, "ADD INDEX(`module_id`)");
          $previous_reference_key = "module_id";
        }
        if (!in_array("module_key", $reference_exist)) {
          array_push($query_columns, "ADD `module_key` TEXT NULL DEFAULT NULL AFTER `" . $previous_reference_key . "`");
          array_push($query_indexes, "ADD INDEX(`module_key`)");
          $previous_reference_key = "module_key";
        }
        if (!in_array("module_value", $reference_exist)) {
          array_push($query_columns, "ADD `module_value` TEXT NULL DEFAULT NULL AFTER `" . $previous_reference_key . "`");
          array_push($query_indexes, "ADD INDEX(`module_value`)");
          $previous_reference_key = "module_value";
        }
      }
      if (!empty($abstracts->component_group)) {
        if (!in_array("group_id", $reference_exist)) {
          array_push($query_columns, "ADD `group_id` INT NULL DEFAULT NULL AFTER `" . $previous_reference_key . "`");
          array_push($query_indexes, "ADD INDEX(`group_id`)");
          $previous_reference_key = "group_id";
        }
      }
      if (!empty($abstracts->component_user)) {
        if (!in_array("user_id", $reference_exist)) {
          array_push($query_columns, "ADD `user_id` INT NULL DEFAULT NULL AFTER `" . $previous_reference_key . "`");
          array_push($query_indexes, "ADD INDEX(`user_id`)");
          $previous_reference_key = "user_id";
        }
      }
      if (!empty($abstracts->component_language)) {
        if (!in_array("language_id", $reference_exist)) {
          array_push($query_columns, "ADD `language_id` INT NULL DEFAULT NULL AFTER `" . $previous_reference_key . "`");
          array_push($query_indexes, "ADD INDEX(`language_id`)");
          $previous_reference_key = "language_id";
        }
        if (!in_array("translate", $reference_exist)) {
          array_push($query_columns, "ADD `translate` INT NULL DEFAULT NULL AFTER `" . $previous_reference_key . "`");
          array_push($query_indexes, "ADD INDEX(`translate`)");
          $previous_reference_key = "translate";
        }
      }
      if (!empty($abstracts->component_page)) {
        if (!in_array("page_id", $reference_exist)) {
          array_push($query_columns, "ADD `page_id` INT NULL DEFAULT NULL AFTER `" . $previous_reference_key . "`");
          array_push($query_indexes, "ADD INDEX(`page_id`)");
          $previous_reference_key = "page_id";
        }
      }
      if (!empty($abstracts->component_media)) {
        if (!in_array("media_id", $reference_exist)) {
          array_push($query_columns, "ADD `media_id` INT NULL DEFAULT NULL AFTER `" . $previous_reference_key . "`");
          array_push($query_indexes, "ADD INDEX(`media_id`)");
          $previous_reference_key = "media_id";
        }
      }

      foreach ($reference_exist as $reference) {
        if (
          (empty($abstracts->data_sortable) && $reference == "order") 
          || (
            empty($abstracts->component_module) 
            && ($reference == "module_id" || $reference == "module_key" || $reference == "module_value")
          )
          || (empty($abstracts->component_group) && $reference == "group_id")
          || (empty($abstracts->component_user) && $reference == "user_id")
          || (empty($abstracts->component_language) && ($reference == "language_id" || $reference == "translate"))
          || (empty($abstracts->component_page) && ($reference == "page_id" || $reference == "title"))
          || (empty($abstracts->component_media) && $reference == "media_id")
        ) {
          if (!in_array($reference, $reference_save)) {
            array_push($query_drops, "DROP `" . $reference . "`");
            array_push($query_drops, "DROP INDEX(`" . $reference . "`)");
          }
        }
      }

      $query_column = "";
      if (!empty($query_columns)) {
        $query_column = "ALTER TABLE `" . $abstracts_key . "` " . implode(", ", $query_columns) . ";";
      }
      $query_index = "";
      if (!empty($query_indexes)) {
        $query_index = "ALTER TABLE `" . $abstracts_key . "` " . implode(", ", $query_indexes) . ";";
      }
      $query_drop = "";
      if (!empty($query_drops)) {
        $query_drop = "ALTER TABLE `" . $abstracts_key . "` " . implode(", ", $query_drops) . ";";
      }

      $connection = $database->connect();
      $result = mysqli_query($connection, "SELECT 1 FROM `" . $abstracts_key . "` LIMIT 1;");
      if (!$result || $clean === true) {
        if ($clean === true) {
          $query .= "DROP TABLE `" . $abstracts_key . "`;";
        }
        $collation = str_replace("-", "", strtolower($this->config["encoding"])) . "_general_ci";
        $charset = str_replace("-", "", strtolower($this->config["encoding"]));
        if (!empty($abstracts->database_collation)) {
          $collation = strtolower($abstracts->database_collation);
          $collation_parts = explode("_", strtolower($abstracts->database_collation));
          $charset = strtolower($collation_parts[0]);
        }
        $query .= "
        CREATE TABLE `" . $abstracts_key . "` (`id` INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`)) 
        ENGINE = " . (!empty($abstracts->database_engine) ? $abstracts->database_engine : $this->config["database_engine"]) . " 
        CHARACTER SET " . $charset . "
        COLLATE " . $collation . " 
        COMMENT = 'Module " . $abstracts->name . "';";
        $query .= $query_column . $query_index. $query_drop;
      } else {
        $query .= $query_column . $query_index. $query_drop;
      }
      
      if ($result) {
        $exists = true;
        mysqli_free_result($result);
      }

      $sync_control = function($module_id) {

        $sync_api = function($module_id, $user_id) {
          $api = new API($this->session, Utilities::override_controls(true, true, true, true));
          $filters = array(
            "user_id" => $user_id
          );
          $api_list = $api->list(null, null, null, null, "1", $filters, null, true);
          if (!empty($api_list)) {
            foreach ($api_list as $api_data) {
              if (!in_array($module_id, $api_data->scope)) {
                $scope = $api_data->scope;
                array_push($scope, $module_id);
                $api_parameters = array(
                  "scope" => $scope
                );
                $api->patch($api_data->id, $api_parameters);
              }
            }
          }
        };

        $group = new Group($this->session, Utilities::override_controls(true, true, true, true));
        $group_data = $group->get("1", null, true);
        if (!empty($group_data) && !empty($group_data->members)) {
          $members = array_map(function($value) { return $value->id; }, $group_data->members);
          $control = new Control($this->session, Utilities::override_controls(true, true, true, true));
          $extensions = array(
            array(
              "conjunction" => "",
              "key" => "behaviors",
              "operator" => "LIKE",
              "value" => "'%view%'"
            ),
            array(
              "conjunction" => "AND",
              "key" => "behaviors",
              "operator" => "LIKE",
              "value" => "'%create%'"
            ),
            array(
              "conjunction" => "AND",
              "key" => "behaviors",
              "operator" => "LIKE",
              "value" => "'%update%'"
            ),
            array(
              "conjunction" => "AND",
              "key" => "behaviors",
              "operator" => "LIKE",
              "value" => "'%delete%'"
            )
          );
          foreach ($members as $member) {
            $filters = array(
              "module_id" => $module_id,
              "group_id" => "1",
              "user_id" => $member
            );
            if (empty(
              $control->list(null, null, null, null, "1", $filters, $extensions)
            )) {
              $control_parameters = array(
                "user" => $member,
                "rule" => "",
                "behaviors" => array("view", "create", "update", "delete"),
                "active" => true,
                "module_id" => $module_id,
                "group_id" => "1"
              );
              $control->create($control_parameters, $this->session->id);
              $sync_api($module_id, $member);
            }
          }
          if (!in_array($this->session->id, $members)) {
            $filters = array(
              "module_id" => $module_id
            );
            if (empty(
              $control->list(null, null, null, null, "1", $filters, $extensions)
            )) {
              $control_parameters = array(
                "user" => $this->session->id,
                "rule" => "[user_id]=<session>",
                "behaviors" => array("view", "create", "update", "delete"),
                "active" => true,
                "module_id" => $module_id,
                "group_id" => "1"
              );
              $control->create($control_parameters, $this->session->id);
              $sync_api($module_id, $this->session->id);
            }
          }
        }
      };

      if (!empty($query)) {
        if ($result = mysqli_multi_query($connection, $query)) {
          $module = new Module($this->session, Utilities::override_controls(true, true, true, true));
          $module_data = $module->get($abstracts->id);
          if (empty($module_data) || $clean === true) {
            if ($clean === true) {
              $module->delete($module_data->id);
            }
            $module_parameters = array(
              "id" => $abstracts->id,
              "name" => $abstracts->name,
              "link" => $abstracts->key,
              "description" => $abstracts->description,
              "icon" => $abstracts->icon,
              "category" => $abstracts->category,
              "subject" => $abstracts->subject,
              "subject_icon" => $abstracts->subject_icon,
              "key" => $abstracts->key,
              "database_table" => $abstracts_key,
              "service" => Utilities::create_link($abstracts->key),
              "page_template" => $abstracts->template,
              "page_template_settings" => "",
              "page_parent_link_key" => "",
              "individual_page_parent_link" => null,
              "default_controls" => "",
              "table_columns" => "",
              "order" => $abstracts->order,
              "active" => true,
              "page_id" => null
            );
            try {
              if (!empty($module_data = $module->create($module_parameters, $this->session->id))) {
                $sync_control($module_data->id);
                return true;
              } else {
                return false;
              }
            } catch (Exception $e) {
              throw new Exception($e->getMessage(), $e->getCode());
            }
          } else {
            $module_parameters = array(
              "id" => $abstracts->id,
              "name" => (empty($module_data->name) ? $abstracts->name : $module_data->name),
              "link" => $abstracts->key,
              "description" => (empty($module_data->description) ? $abstracts->description : $module_data->description),
              "icon" => (empty($module_data->icon) ? $abstracts->icon : $module_data->icon),
              "category" => (empty($module_data->category) ? $abstracts->category : $module_data->category),
              "subject" => (empty($module_data->subject) ? $abstracts->subject : $module_data->subject),
              "subject_icon" => (empty($module_data->subject_icon) ? $abstracts->subject_icon : $module_data->subject_icon),
              "key" => $abstracts->key,
              "database_table" => $abstracts_key,
              "service" => Utilities::create_link($abstracts->key),
              "page_template" => (empty($module_data->page_template) ? $abstracts->template : $module_data->page_template),
              "order" => (empty($module_data->order) ? $abstracts->order : $module_data->order)
            );
            try {
              $module->patch($abstracts->id, $module_parameters);
              $sync_control($module_data->id);
              return true;
            } catch (Exception $e) {
              throw new Exception($e->getMessage(), $e->getCode());
            }
          }
        } else {
          throw new Exception($this->translation->translate("Database encountered error"), 409);
        }
      } else {
        if ($exists === true) {
          return true;
        } else {
          throw new Exception($this->translation->translate("Unable to build"), 409);
        }
      }

    } else {
      throw new Exception($this->translation->translate("Not found"), 404);
    }
    
  }

  function generate() {

  }

}