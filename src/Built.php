<?php
namespace Abstracts;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use \Abstracts\Abstracts;
use \Abstracts\API;
use \Abstracts\Module;
use \Abstracts\User;
use \Abstracts\Group;
use \Abstracts\Page;
use \Abstracts\Log;
use \Abstracts\Language;

use Exception;
use DateTime;
use finfo;

class Built {

  /* configuration */
  public $id = null;
  public $public_functions = array();
  public $module = null;
  public $abstracts = null;

  /* core */
  private $config = null;
  private $session = null;
  private $controls = null;
  private $identifier = null;

  /* helpers */
  private $database = null;
  private $validation = null;
  private $translation = null;

  /* services */
  private $api = null;
  private $log = null;

  /* instances */
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
  private $number_types = array(
    "input-number"
  );
  private $decimal_types = array(
    "input-decimal"
  );
  private $date_types = array(
    "input-date"
  );
  private $datetime_types = array(
    "input-datetime",
    "input-time"
  );

  function __construct(
    $session = null,
    $controls = null,
    $identifier = null
  ) {

    /* initialize: core */
    $initialize = new Initialize($session, $controls, $identifier, false);
    $this->identifier = $identifier;
    $this->config = $initialize->config;
    $this->session = $initialize->session;
    $this->controls = $initialize->controls;
    $this->id = $initialize->id;
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
    $this->abstracts = $this->initialize($this->id);
    
  }

  function initialize($module_id) {
    $abstracts_data = null;
    if (!empty($module_id)) {
      $abstracts = new Abstracts(
        $this->session, 
        Utilities::override_controls(true)
      );
      $abstracts_data = $abstracts->get($module_id);
      if (empty($abstracts_data)) {
        $abstracts_data = $this->simulate($module_id, $this->module);
      }
    }
    return $abstracts_data;
  }

  function request($function, $parameters) {
    $result = null;
    if ($this->api->authorize($this->id, $function, $this->public_functions)) {
      if (!empty($this->module)) {
        if ($function == "get") {
          $result = $this->$function(
            (isset($parameters["id"]) ? $parameters["id"] : null),
            (isset($parameters["active"]) ? $parameters["active"] : null),
            (isset($parameters["return_references"]) ? $parameters["return_references"] : false),
            (isset($parameters["translation"]) ? $parameters["translation"] : false)
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
            (isset($parameters["return_references"]) ? $parameters["return_references"] : false),
            (isset($parameters["translation"]) ? $parameters["translation"] : false)
          );
        } else if ($function == "count") {
          $result = $this->$function(
            (isset($parameters["start"]) ? $parameters["start"] : null), 
            (isset($parameters["limit"]) ? $parameters["limit"] : null), 
            (isset($parameters["active"]) ? $parameters["active"] : null), 
            (isset($parameters) ? $parameters : null), 
            (isset($parameters["extensions"]) ? $parameters["extensions"] : null),
            (isset($parameters["translation"]) ? $parameters["translation"] : false)
          );
        } else if ($function == "create") {
          $result = $this->$function(
            $parameters,
            null,
            $_FILES, 
            (isset($parameters["return_references"]) ? $parameters["return_references"] : false)
          );
        } else if ($function == "update") {
          $result = $this->$function(
            (isset($parameters["id"]) ? $parameters["id"] : null),
            (isset($parameters) ? $parameters : null),
            $_FILES, 
            (isset($parameters["return_references"]) ? $parameters["return_references"] : false)
          );
        } else if ($function == "patch") {
          $result = $this->$function(
            (isset($parameters["id"]) ? $parameters["id"] : null),
            (isset($parameters) ? $parameters : null),
            $_FILES, 
            (isset($parameters["return_references"]) ? $parameters["return_references"] : false)
          );
        } else if ($function == "delete") {
          $result = $this->$function(
            (isset($parameters["id"]) ? $parameters["id"] : null), 
            (isset($parameters["return_references"]) ? $parameters["return_references"] : false)
          );
        } else if ($function == "upload") {
          $result = $this->$function(
            (isset($parameters["id"]) ? $parameters["id"] : null),
            $_FILES
          );
        } else if ($function == "remove") {
          $result = $this->$function(
            (isset($parameters["id"]) ? $parameters["id"] : null),
            (isset($parameters) ? $parameters : null)
          );
        } else if ($function == "data") {
          $result = $this->$function(
            (isset($parameters["key"]) ? $parameters["key"] : null),
            (isset($parameters["value"]) ? $parameters["value"] : null)
          );
        } else if ($function == "options") {
          $result = $this->$function(
            (isset($parameters["key"]) ? $parameters["key"] : null),
            (isset($parameters["start"]) ? $parameters["start"] : null), 
            (isset($parameters["limit"]) ? $parameters["limit"] : null), 
            (isset($parameters["sort_by"]) ? $parameters["sort_by"] : null), 
            (isset($parameters["sort_direction"]) ? $parameters["sort_direction"] : null), 
            (isset($parameters["active"]) ? $parameters["active"] : null), 
            (isset($parameters["filters"]) ? $parameters["filters"] : null), 
            (isset($parameters["extensions"]) ? $parameters["extensions"] : null),
            (isset($parameters["return_references"]) ? $parameters["return_references"] : false)
          );
        } else {
          throw new Exception($this->translation->translate("Function not supported"), 421);
        }
      } else {
        throw new Exception($this->translation->translate("Module not found"), 500);
      }
    }
    return $result;
  }

  function get($id, $active = null, $return_references = false, $translation = false, $format = true) {

    if ($this->validation->require($id, "ID")) {

      $active = Initialize::active($active);
      $return_references = Initialize::return_references($return_references);
      $translation = Initialize::translation($translation);
      
      $filters = array("id" => $id);
      if (isset($active)) {
        $filters["active"] = $active;
      }
      
      $data = $this->database->select(
        (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
        "*", 
        $filters, 
        null, 
        $this->controls["view"]
      );
      if (!empty($data)) {
        if (!empty($translation) && !empty($this->abstracts->component_language)) {
          $translation_language = null;
          if (is_numeric($translation)) {
            $translation_language = $translation;
          } else {
            $language = new Language(
              $this->session, 
              Utilities::override_controls(true)
            );
            $language_list = $language->list(0, 1, null, null, null, array(
              "short_name" => $translation
            ));
            if (!empty($language_list)) {
              $translation_language = $language_list[0]->id;
            }
          }
          if (!empty($translation_language)) {
            $data_translate = $this->database->select(
              (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
              "*", 
              array(
                "active" => $active,
                "translate" => $id,
                "language_id" => $translation_language
              ), 
              null, 
              $this->controls["view"]
            );
            if (!empty($data_translate)) {
              $data_translate = $this->database->select(
                (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
                "*", 
                array(
                  "active" => $active,
                  "id" => $data->translate,
                  "language_id" => $translation_language
                ), 
                null, 
                $this->controls["view"]
              );
            }
            if (!empty($data_translate)) {
  
              $create_at = $data->create_at;
              $user_id = null;
              if (isset($data->user_id)) {
                $user_id = $data->user_id;
              }
              $module_id = null;
              if (isset($data->module_id)) {
                $module_id = $data->module_id;
              }
              $group_id = null;
              if (isset($data->group_id)) {
                $group_id = $data->group_id;
              }
              $page_id = null;
              if (isset($data->page_id)) {
                $page_id = $data->page_id;
              }
              $media_id = null;
              if (isset($data->media_id)) {
                $media_id = $data->media_id;
              }
              $translate = null;
              if (isset($data->translate)) {
                $translate = $data->translate;
              }
  
              $data = $data_translate;
  
              $data->id = $id;
              $data->create_at = $create_at;
              if (!empty($user_id)) {
                $data->user_id = $user_id;
              }
              if (!empty($module_id)) {
                $data->module_id = $module_id;
              }
              if (!empty($group_id)) {
                $data->group_id = $group_id;
              }
              if (!empty($page_id)) {
                $data->page_id = $page_id;
              }
              if (!empty($media_id)) {
                $data->media_id = $media_id;
              }
              if (!empty($translate)) {
                $data->translate = $translate;
              }
  
            }
          }
        }
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "low",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $format === true ? $this->format($data, $return_references) : $data,
          $this->session,
          $this->controls,
          $this->identifier
        );
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
    $return_references = false,
    $translation = false,
    $format = true
  ) {
    
    $start = Initialize::start($start);
    $limit = Initialize::limit($limit);
    $sort_by = Initialize::sort_by($sort_by);
    $sort_direction = Initialize::sort_direction($sort_direction);
    $active = Initialize::active($active);
    $filters = Initialize::filters($filters);
    $extensions = Initialize::extensions($extensions);
    $return_references = Initialize::return_references($return_references);
    $translation = Initialize::translation($translation);
    
    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      
      if (isset($active)) {
        $filters["active"] = $active;
      }
      
      $translation_language = null;
      if (!empty($this->abstracts->component_language)) {
        if (empty($translation)) {
          if (!isset($filters["translate"]) || empty($filters["translate"])) {
            $filters["translate"] = "0";
          }
        } else {
          if ($translation !== true) {
            $filters["translate"] = "0";
            if (is_numeric($translation)) {
              $translation_language = $translation;
            } else {
              $language = new Language(
                $this->session, 
                Utilities::override_controls(true)
              );
              $language_list = $language->list(0, 1, null, null, null, array(
                "short_name" => $translation
              ));
              if (!empty($language_list)) {
                $translation_language = $language_list[0]->id;
              }
            }
          }
        }
      }
      
      $list = $this->database->select_multiple(
        (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
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
        if (!empty($this->abstracts->component_language) && !empty($translation_language)) {
          $list = array_map(function ($value, $translation_language) {
            $translation_data = $this->get($value->id, true, false, $translation_language, false);
            if (!empty($translation_data)) {
              return $translation_data;
            } else {
              return $value;
            }
          }, $list, array_fill(0, count($list), $translation_language));
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
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $format === true ? $this->format($list, $return_references) : $list,
          $this->session,
          $this->controls,
          $this->identifier
        );
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
    $extensions = array(),
    $translation = false
  ) {

    $start = Initialize::start($start);
    $limit = Initialize::limit($limit);
    $active = Initialize::active($active);
    $filters = Initialize::filters($filters);
    $extensions = Initialize::extensions($extensions);
    $translation = Initialize::translation($translation);
    
    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {

      if (isset($active)) {
        $filters["active"] = $active;
      }
      
      if (!empty($this->abstracts->component_language)) {
        if (empty($translation)) {
          if (!isset($filters["translate"]) || empty($filters["translate"])) {
            $filters["translate"] = "0";
          }
        } else {
          if ($translation !== true) {
            $filters["translate"] = "0";
          }
        }
      }

      $data = $this->database->count(
        (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
        $filters, 
        $extensions, 
        $start, 
        $limit, 
        $this->controls["view"]
      );

      return $data;

    } else {
      return false;
    }
  }

  function create($parameters, $user_id = 0, $files = null, $return_references = null, $format = true) {
      
    /* initialize: parameters */
    $parameters = $this->inform($parameters, false, $user_id);

    if ($this->validate($parameters)) {
      $data = $this->database->insert(
        (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
        $parameters, 
        $this->controls["create"]
      );
      if (!empty($data)) {
        $error = false;
        if (!empty($files)) {
          try {
            $this->upload($data->id, $files);
          } catch (Exception $e) {
            $error = true;
          }
        }
        if (!$error || empty($files)) {

          $this->log->log(
            __FUNCTION__,
            __METHOD__,
            "normal",
            func_get_args(),
            (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
            "id",
            $data->id
          );

          if (isset($parameters["translations"]) && !empty(isset($parameters["translations"]))) {
            $this->translate($data->id, $parameters["translations"]);
          }

          return Utilities::callback(
            __METHOD__, 
            func_get_args(), 
            $format === true ? $this->format($data, $return_references) : $data,
            $this->session,
            $this->controls,
            $this->identifier
          );

        } else {
          throw new Exception($this->translation->translate("Unable to upload"), 409);
        }
      } else {
        return $data;
      }
    } else {
      return false;
    }

  }

  function update($id, $parameters, $files = null, $return_references = null, $format = true) {
    
    /* initialize: parameters */
    $parameters = $this->inform($parameters, $id);
    
    if (
      $this->validation->require($id, "ID")
      && $this->validate($parameters, $id)
    ) {
      $data = $this->database->update(
        (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
        $parameters, 
        array("id" => $id), 
        null, 
        $this->controls["update"]
      );
      if (!empty($data)) {
        $data = $data[0];
        $error = false;
        if (!empty($files)) {
          try {
            $this->upload($data->id, $files);
          } catch (Exception $e) {
            $error = true;
          }
        }
        if (!$error || empty($files)) {

          $this->log->log(
            __FUNCTION__,
            __METHOD__,
            "normal",
            func_get_args(),
            (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
            "id",
            $data->id
          );

          if (isset($parameters["translations"]) && !empty(isset($parameters["translations"]))) {
            $this->translate($data->id, $parameters["translations"]);
          }

          return Utilities::callback(
            __METHOD__, 
            func_get_args(), 
            $format === true ? $this->format($data, $return_references) : $data,
            $this->session,
            $this->controls,
            $this->identifier
          );

        } else {
          throw new Exception($this->translation->translate("Unable to upload"), 409);
        }
      } else {
        return $data;
      }

    } else {
      return false;
    }

  }

  function patch($id, $parameters, $files = null, $return_references = null, $format = true) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters, $id);
    
    if (
      $this->validation->require($id, "ID")
      && $this->validate($parameters, $id, true)
    ) {
      $data = $this->database->update(
        (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
        $parameters, 
        array("id" => $id), 
        null, 
        $this->controls["update"]
      );
      if (!empty($data)) {
        $data = $data[0];
        $error = false;
        if (!empty($files)) {
          try {
            $this->upload($data->id, $files);
          } catch (Exception $e) {
            $error = true;
          }
        }
        if (!$error || empty($files)) {

          $this->log->log(
            __FUNCTION__,
            __METHOD__,
            "normal",
            func_get_args(),
            (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
            "id",
            $data->id
          );

          if (isset($parameters["translations"]) && !empty(isset($parameters["translations"]))) {
            $this->translate($data->id, $parameters["translations"]);
          }

          return Utilities::callback(
            __METHOD__, 
            func_get_args(), 
            $format === true ? $this->format($data, $return_references) : $data,
            $this->session,
            $this->controls,
            $this->identifier
          );

        } else {
          throw new Exception($this->translation->translate("Unable to upload"), 409);
        }
      } else {
        return $data;
      }
    } else {
      return false;
    }

  }

  function delete($id, $return_references = null, $format = true) {
    if ($this->validation->require($id, "ID")) {
      $data = $this->database->delete(
        (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
        array("id" => $id), 
        null, 
        $this->controls["delete"]
      );
      if (!empty($data)) {
        $data = $data[0];
        foreach ($this->abstracts->references as $reference) {
          if (in_array($reference->type, $this->file_types)) {

            $key = $reference->key;

            $remove = function($reference, $file) {
              try {
                $file_old = Utilities::backtrace() . trim($file, "/");
                if (!empty($file) && file_exists($file_old)) {
                  try {
                    chmod($file_old, 0777);
                  } catch (Exception $e) {}
                  try {
                    unlink($file_old);
                  } catch (Exception $e) {}
                }
                if ($reference->type == "image-upload" || $reference->file_type == "image") {
                  $thumbnail_old = Utilities::get_thumbnail($file_old);
                  if (file_exists($thumbnail_old) && !is_dir($thumbnail_old)) {
                    try {
                      chmod($thumbnail_old, 0777);
                    } catch (Exception $e) {}
                    try {
                      unlink($thumbnail_old);
                    } catch (Exception $e) {}
                  }
                  $large_old = Utilities::get_large($file_old);
                  if (file_exists($large_old) && !is_dir($large_old)) {
                    try {
                      chmod($large_old, 0777);
                    } catch (Exception $e) {}
                    try {
                      unlink($large_old);
                    } catch (Exception $e) {}
                  }
                }
                return true;
              } catch(Exception $e) {
                return false;
              }
            };

            if (
              $reference->type == "input-file-multiple"
              || $reference->type == "input-file-multiple-drop"
            ) {
              if (!empty($data->$key)) {
                foreach (unserialize($data->$key) as $file) {
                  $remove($reference, $file);
                }
              }
            } else {
              $remove($reference, $data->$key);
            }

          }

        }

        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "risk",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );

        $this->distranslate($data->id);
        
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $format === true ? $this->format($data, $return_references) : $data,
          $this->session,
          $this->controls,
          $this->identifier
        );

      } else {
        return $data;
      }
    } else {
      return false;
    }
  }

  function upload($id, $files, $input_multiple = null) {
    if ($this->validation->require($id, "ID")) {
      
      $data_current = $this->database->select(
        (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
        "*", 
        array("id" => $id), 
        null, 
        (
          ($this->controls["create"] === true || $this->controls["update"] === true) ?
          true : 
          array_merge($this->controls["create"], $this->controls["update"])
        )
      );
      if (!empty($data_current)) {
        
        $upload = function(
          $reference, 
          $data_target, 
          $destination = "", 
          $name, 
          $type, 
          $tmp_name, 
          $error, 
          $size
        ) {
          
          $image_options = array(
            "quality" => (
              !empty($reference->image_quality) ? $reference->image_quality 
              : (isset($this->config["image_quality"]) ? $this->config["image_quality"] : 75)
            ),
            "thumbnail" => (
              !empty($reference->image_thumbnail) ? $reference->image_thumbnail 
              : (isset($this->config["image_thumbnail"]) ? $this->config["image_thumbnail"] : false)
            ),
            "thumbnail_aspectratio" => (
              !empty($reference->image_thumbnail_aspectratio) ? $reference->image_thumbnail_aspectratio 
              : (isset($this->config["image_thumbnail_aspectratio"]) ? $this->config["image_thumbnail_aspectratio"] : 75)
            ),
            "thumbnail_quality" => (
              !empty($reference->image_thumbnail_quality) ? $reference->image_thumbnail_quality 
              : (isset($this->config["image_thumbnail_quality"]) ? $this->config["image_thumbnail_quality"] : 75)
            ),
            "thumbnail_width" => (
              !empty($reference->image_thumbnail_width) ? $reference->image_thumbnail_width 
              : (isset($this->config["image_thumbnail_width"]) ? $this->config["image_thumbnail_width"] : 200)
            ),
            "thumbnail_height" => (
              !empty($reference->image_thumbnail_height) ? $reference->image_thumbnail_height 
              : (isset($this->config["image_thumbnail_height"]) ? $this->config["image_thumbnail_height"] : 200)
            ),
            "large" => (
              !empty($reference->image_large) ? $reference->image_large 
              : (isset($this->config["image_large"]) ? $this->config["image_large"] : false)
            ),
            "large_aspectratio" => (
              !empty($reference->image_large_aspectratio) ? $reference->image_large_aspectratio 
              : (isset($this->config["image_large_aspectratio"]) ? $this->config["image_large_aspectratio"] : 75)
            ),
            "large_quality" => (
              !empty($reference->image_large_quality) ? $reference->image_large_quality 
              : (isset($this->config["image_large_quality"]) ? $this->config["image_large_quality"] : 75)
            ),
            "large_width" => (
              !empty($reference->image_large_width) ? $reference->image_large_width 
              : (isset($this->config["image_large_width"]) ? $this->config["image_large_width"] : 400)
            ),
            "large_height" => (
              !empty($reference->image_large_height) ? $reference->image_large_height 
              : (isset($this->config["image_large_height"]) ? $this->config["image_large_height"] : 400)
            )
          );
          
          $info = pathinfo($name);
          $extension = strtolower(isset($info["extension"]) ? $info["extension"] : null);
          if (empty($extension)) {
            if (strpos($type, "image/") === 0) {
              $extension = str_replace("image/", "", $type);
            }
          }
          $basename = $info["basename"];
          $file_name = $basename . "." . $extension;
          $name_encrypted = gmdate("YmdHis") . "_" . $data_target->id . "_" . uniqid();
          $file = $name_encrypted . "." . $extension;
    
          $media_directory = "/" . trim((isset($this->config["media_path"]) ? $this->config["media_path"] : "media"), "/") . "/";
          $media_directory_path = Utilities::backtrace() . trim($media_directory, "/") . "/";

          $upload_directory = $media_directory . trim($destination, "/") . "/";
          $upload_directory_path = $media_directory_path . trim($destination, "/") . "/";
          if (!file_exists($media_directory_path)) {
            mkdir($media_directory_path, 0777, true);
          }
          if (!file_exists($upload_directory_path)) {
            mkdir($upload_directory_path, 0777, true);
          }
          $destination = $upload_directory_path . $file;
          $path = $upload_directory . $file;
    
          if (file_exists($upload_directory_path)) {
            $upload_result = false;
            $unsupported_image = false;
            if ($reference->type == "image-upload" || $reference->file_type == "image") {
              try {
                $resize = false;
                if ($reference->image_width && $reference->image_height) {
                  $resize = true;
                }
                $upload_result = Utilities::create_image(
                  $tmp_name, 
                  $destination, 
                  $resize,
                  $reference->image_width, 
                  $reference->image_height, 
                  null, 
                  $image_options["quality"]
                );
              } catch(Exception $e) {
                if ($e->getCode() == 415 || $e->getCode() == 500) {
                  $upload_result = move_uploaded_file($tmp_name, $destination);
                  $unsupported_image = true;
                }
              }
            } else {
              $upload_result = move_uploaded_file($tmp_name, $destination);
            }
            if ($upload_result) {
    
              $key = $reference->key;
              $parameter = $path;
              if (
                $reference->type == "input-file-multiple"
                || $reference->type == "input-file-multiple-drop"
              ) {
                $parameter = array();
                if (
                  $uploaded_data = $this->database->select(
                    (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
                    array($key), 
                    array("id" => $data_target->id), 
                    null, 
                    true
                  )
                ) {
                  if (!empty($uploaded_data->$key)) {
                    $parameter = unserialize($uploaded_data->$key);
                  }
                  array_push($parameter, $path);
                  $parameter = serialize($parameter);
                }
              }
              $parameters = array(
                $reference->key => $parameter
              );
              if (empty($input_multiple)) {
                $update_data = $this->database->update(
                  (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
                  $parameters, 
                  array("id" => $data_target->id), 
                  null, 
                  (
                    ($this->controls["create"] === true || $this->controls["update"] === true) ?
                    true : 
                    array_merge($this->controls["create"], $this->controls["update"])
                  )
                );
              }
              if (!empty($update_data) || !empty($input_multiple)) {
                if ($reference->type == "image-upload" || $reference->file_type == "image") {
                  if (!empty($image_options["thumbnail"]) && !$unsupported_image) {
                    $thumbnail_directory_path	= $upload_directory_path . "thumbnail/";
                    if (!file_exists($thumbnail_directory_path)) {
                      mkdir($thumbnail_directory_path, 0777, true);
                    }
                    $thumbnail = $thumbnail_directory_path . $file;
                    Utilities::create_image(
                      $destination, 
                      $thumbnail, 
                      true,
                      $image_options["thumbnail_width"], 
                      $image_options["thumbnail_height"], 
                      $image_options["thumbnail_aspectratio"], 
                      $image_options["thumbnail_quality"]
                    );
                  }
                  if (!empty($image_options["large"]) && !$unsupported_image) {
                    $large_directory_path	= $upload_directory_path . "large/";
                    if (!file_exists($large_directory_path)) {
                      mkdir($large_directory_path, 0777, true);
                    }
                    $large = $large_directory_path . $file;
                    Utilities::create_image(
                      $destination, 
                      $large, 
                      true,
                      $image_options["large_width"], 
                      $image_options["large_height"], 
                      $image_options["large_aspectratio"], 
                      $image_options["large_quality"]
                    );
                  }
                }
                
                return $path;

              } else {
                if (file_exists($destination) && !is_dir($destination)) {
                  try {
                    chmod($destination, 0777);
                  } catch (Exception $e) {}
                  try {
                    unlink($destination);
                  } catch (Exception $e) {}
                }
                return false;
              }

            } else {
              return false;
            }
          } else {
            return false;
          }
        };

        $successes = array();
        $errors = array();

        $references = $this->abstracts->references;
        if (!empty($input_multiple)) {
          $references = $input_multiple;
        }
        foreach ($references as $reference) {
          if (
            in_array($reference->type, $this->file_types)
            || (
              $reference->type == "input-multiple"
              && !empty($input_multiple)
            )
          ) {
            $key = $reference->key;
            if ($reference->type == "input-multiple") {
              if ($reference->references) {
                try {
                  $subreference_successes = $this->upload($id, $files[$key], $reference->references);
                  array_push($successes, $subreference_successes);
                } catch (Exception $e) {
                  array_push($errors, $files[$key]);
                };
              }
            } else if (
              $reference->type == "input-file-multiple"
              || $reference->type == "input-file-multiple-drop"
            ) {
              if (isset($files[$key]) && isset($files[$key]["name"]) && !empty($files[$key]["name"])) {
                for ($i = 0; $i < count($files[$key]["name"]); $i++) {
                  if (isset($files[$key]["name"][$i]) && !empty($files[$key]["name"][$i])) {
                    if (
                      $path_id = $upload(
                        $reference,
                        $data_current,
                        $reference->upload_folder,
                        $files[$key]["name"][$i],
                        $files[$key]["type"][$i],
                        $files[$key]["tmp_name"][$i],
                        $files[$key]["error"][$i],
                        $files[$key]["size"][$i]
                      )
                    ) {
                      $path = (object) array(
                        "id" => $path_id,
                        "name" => basename($path_id),
                        "path" => null
                      );
                      if (strpos($path_id, "http://") !== 0 || strpos($path_id, "https://") !== 0) {
                        $path->path = $this->config["base_url"] . $path_id;
                      }
                      array_push($successes, array(
                        "source" => $files[$key]["name"][$i],
                        "destination" => $path
                      ));
                    } else {
                      array_push($errors, $files[$key]["name"][$i]);
                    }
                  }
                }
              }
            } else {
              if (isset($files[$key]) && isset($files[$key]["name"]) && !empty($files[$key]["name"])) {
                if (
                  $reference->type == "input-file"
                  || $reference->type == "image-upload"
                ) {
                  if (isset($data_current->$key) && !empty($data_current->$key)) {
                    try {
                      $this->remove($data_current->id, array($key => $data_current->$key));
                    } catch (Exception $e) {
                      
                    };
                  }
                }
                if (
                  $path_id = $upload(
                    $reference,
                    $data_current,
                    $reference->upload_folder,
                    $files[$key]["name"],
                    $files[$key]["type"],
                    $files[$key]["tmp_name"],
                    $files[$key]["error"],
                    $files[$key]["size"]
                  )
                ) {
                  $path = (object) array(
                    "id" => $path_id,
                    "name" => basename($path_id),
                    "path" => null
                  );
                  if (strpos($path_id, "http://") !== 0 || strpos($path_id, "https://") !== 0) {
                    $path->path = $this->config["base_url"] . $path_id;
                  }
                  array_push($successes, array(
                    "source" => $files[$key]["name"],
                    "destination" => $path_id
                  ));
                } else {
                  array_push($errors, $files[$key]["name"]);
                }
              }
            }
          }
        }
        
        if (empty($errors)) {
          if (!empty($successes)) {
            return Utilities::callback(
              __METHOD__, 
              func_get_args(), 
              $successes,
              $this->session,
              $this->controls,
              $this->identifier
            );
          } else {
            throw new Exception($this->translation->translate("No file has been uploaded"), 409);
          }
        } else {
          throw new Exception($this->translation->translate("Unable to upload") . " '" . implode("', '", $errors) . "'", 409);
        }

      } else {
        return $data_current;
      }

    } else {
      return false;
    }
  }

  function remove($id, $parameters) {
    if ($this->validation->require($id, "ID")) {
      if (!empty($parameters)) {
        
        if (!empty(
          $data_current = $this->database->select(
            (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
            "*", 
            array("id" => $id), 
            null, 
            (
              ($this->controls["create"] === true || $this->controls["update"] === true || $this->controls["delete"] === true) ?
              true : 
              array_merge($this->controls["create"], $this->controls["update"], $this->controls["delete"])
            )
          )
        )) {

          $remove = function($reference, $file) {
            try {
              $file_old = Utilities::backtrace() . trim($file, "/");
              if (!empty($file) && file_exists($file_old)) {
                try {
                  chmod($file_old, 0777);
                } catch (Exception $e) {}
                try {
                  unlink($file_old);
                } catch (Exception $e) {}
              }
              if ($reference->type == "image-upload" || $reference->file_type == "image") {
                $thumbnail_old = Utilities::get_thumbnail($file_old);
                if (file_exists($thumbnail_old) && !is_dir($thumbnail_old)) {
                  try {
                    chmod($thumbnail_old, 0777);
                  } catch (Exception $e) {}
                  try {
                    unlink($thumbnail_old);
                  } catch (Exception $e) {}
                }
                $large_old = Utilities::get_large($file_old);
                if (file_exists($large_old) && !is_dir($large_old)) {
                  try {
                    chmod($large_old, 0777);
                  } catch (Exception $e) {}
                  try {
                    unlink($large_old);
                  } catch (Exception $e) {}
                }
              }
              return true;
            } catch(Exception $e) {
              return false;
            }
          };

          $successes = array();
          $errors = array();
          foreach ($this->abstracts->references as $reference) {
            if (in_array($reference->type, $this->file_types)) {
              $key = $reference->key;
              if (isset($parameters[$key])) {

                if (is_array($parameters[$key])) {
                  foreach ($parameters[$key] as $file) {
                    if ($remove($reference, $file)) {
                      array_push($successes, $file);
                    } else {
                      array_push($errors, $file);
                    }
                  }
                } else {
                  if ($remove($reference, $parameters[$key])) {
                    array_push($successes, $parameters[$key]);
                  } else {
                    array_push($errors, $parameters[$key]);
                  }
                }

                if (count($successes)) {
                  $parameter = "";
                  if (
                    $reference->type == "input-file-multiple"
                    || $reference->type == "input-file-multiple-drop"
                  ) {
                    $key = $reference->key;
                    $parameter = unserialize($data_current->$key);
                    if (!empty($parameter)) {
                      for ($i = 0; $i < count($successes); $i++) {
                        for ($j = 0; $j < count($parameter); $j++) {
                          if ($successes[$i] == $parameter[$j]) {
                            array_splice($parameter, $j, 1);
                          }
                        }
                      }
                      $parameter = serialize($parameter);
                    } else {
                      $parameter = serialize(array());
                    }
                  }
                  $parameters = array(
                    $reference->key => $parameter
                  );
                  $this->database->update(
                    (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
                    $parameters, 
                    array("id" => $data_current->id), 
                    null, 
                    (
                      ($this->controls["create"] === true || $this->controls["update"] === true || $this->controls["delete"] === true) ?
                      true : 
                      array_merge($this->controls["create"], $this->controls["update"], $this->controls["delete"])
                    )
                  );
                  
                }

              }
            }
          }

          if (empty($errors)) {
            return Utilities::callback(
              __METHOD__, 
              func_get_args(), 
              $successes,
              $this->session,
              $this->controls,
              $this->identifier
            );
          } else {
            throw new Exception($this->translation->translate("Unable to delete") . " '" . implode("', '", $errors) . "'", 409);
          }

        } else {
          throw new Exception($this->translation->translate("Not exist or gone"), 410);
        }

      } else {
        throw new Exception($this->translation->translate("File(s) not found"), 400);
      }
    } else {
      return false;
    }
  }

  function options(
    $key, 
    $start = null, 
    $limit = null, 
    $sort_by = "id", 
    $sort_direction = "desc", 
    $active = null, 
    $filters = array(), 
    $extensions = array(), 
    $return_references = false,
    $format = true
  ) {

    $start = Initialize::start($start);
    $limit = Initialize::limit($limit);
    $sort_by = Initialize::sort_by($sort_by);
    $sort_direction = Initialize::sort_direction($sort_direction);
    $active = Initialize::active($active);
    $filters = is_array($filters) ? $filters : array();
    $extensions = Initialize::extensions($extensions);
    $return_references = Initialize::return_references($return_references);

    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      $filters["active"] = true;
      if (isset($active)) {
        $filters["active"] = $active;
      }
      $list = $this->database->select_multiple(
        $key, 
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
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $format === true ? $this->format($list, $return_references) : $list,
          $this->session,
          $this->controls,
          $this->identifier
        );
      } else {
        return array();
      }
    } else {
      return false;
    }
  }

  function inform($parameters, $update = false, $user_id = 0) {
    if (!empty($parameters)) {
      
      if (empty($update)) {
        if (isset($parameters["id"])) {
          $parameters["id"] = $parameters["id"];
        } else {
          $parameters["id"] = null;
        }
        $parameters["active"] = (isset($parameters["active"]) ? (Initialize::active($parameters["active"])) : true);
        $parameters["user_id"] = (!empty($user_id) ? $user_id : (!empty($this->session) ? $this->session->id : 0));
        $parameters["create_at"] = gmdate("Y-m-d H:i:s");
      } else {
        unset($parameters["id"]);
        unset($parameters["create_at"]);
        if (isset($parameters["active"])) {
          $parameters["active"] = Initialize::active($parameters["active"]);
        }
      }
      if (isset($parameters["translation"])) {
        unset($parameters["translation"]);
      }

      foreach ($this->abstracts->references as $reference) {
        $key = $reference->key;
        if (array_key_exists($key, $parameters)) {
          
          $inform = function ($parameters, $reference) {
            $key = $reference->key;
            /* 
            check if reference is array type but data is not array 
            then convert data to array 
            */
            if (in_array($reference->type, $this->multiple_types)) {
              if (!is_array($parameters[$key])) {
                if (
                  empty($reference->input_multiple_format)
                  || $reference->input_multiple_format == "serialize"
                ) {
                  try {
                    if (is_string($parameters[$key])) {
                      $parameters[$key] = unserialize($parameters[$key]);
                    }
                  } catch (Exception $e) {}
                }
                if (!is_array($parameters[$key])) {
                  if (is_string($parameters[$key])) {
                    $parameters[$key] = explode(",", $parameters[$key]);
                  } else {
                    $parameters[$key] = array();
                  }
                }
              }
            }

            /* check if reference is file type */
            if (in_array($reference->type, $this->file_types)) {

              $create_tmp_file = function ($base64, $name) {
                $base64_data = substr($base64, strpos($base64, ',') + 1);
                $base64_data_decoded = base64_decode($base64_data);
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->buffer($base64_data_decoded);
                $extension = strtolower(pathinfo($mime, PATHINFO_EXTENSION));
                $base64_decoded = base64_decode($base64);
                $type = finfo_buffer(finfo_open(), $base64_decoded, FILEINFO_MIME_TYPE);
                $tmp_file = tmpfile();
                fwrite($tmp_file, $base64_decoded);
                $uploaded_file = [
                  "name" => $name . "." . $extension,
                  "type" => $type,
                  "size" => strlen($base64_decoded),
                  "tmp_name" => stream_get_meta_data($tmp_file)["uri"],
                  "error" => UPLOAD_ERR_OK
                ];
                return $uploaded_file;
              };

              /* clean parameter if value is file data */
              if (in_array($reference->type, $this->multiple_types)) {
                $tmp_files = array();
                for ($i = 0; $i < count($parameters[$key]); $i++) {
                  if (
                    is_string($parameters[$key][$i])
                    && base64_decode($parameters[$key][$i], true) !== false 
                    && base64_encode(base64_decode($parameters[$key][$i], true)) === $parameters[$key]
                  ) {
                    array_push($tmp_files, $create_tmp_file($parameters[$key][$i], $key . "_" . $i));
                    unset($parameters[$key][$i]);
                  } else {
                    if (!is_string($parameters[$key][$i])) {
                      unset($parameters[$key][$i]);
                    }
                  }
                }
                if (!empty($tmp_files)) {
                  $_FILES[$key . "[]"] = array(
                    "name" => "",
                    "type" => "",
                    "size" => "",
                    "tmp_name" => "",
                    "error" => ""
                  );
                  foreach ($tmp_files as $tmp_file) {
                    $_FILES[$key . "[]"]["image"] .= "," . $tmp_file["name"];
                    $_FILES[$key . "[]"]["type"] .= "," . $tmp_file["type"];
                    $_FILES[$key . "[]"]["size"] .= "," . $tmp_file["size"];
                    $_FILES[$key . "[]"]["tmp_name"] .= "," . $tmp_file["tmp_name"];
                    $_FILES[$key . "[]"]["error"] .= "," . $tmp_file["error"];
                  }
                }
              } else {
                if (
                  is_string($parameters[$key])
                  && base64_decode($parameters[$key], true) !== false 
                  && base64_encode(base64_decode($parameters[$key], true)) === $parameters[$key]
                ) {
                  try {
                    $_FILES[$key] = $create_tmp_file($parameters[$key], $key);
                  } catch (Exception $e) {}
                  if (empty($update)) {
                    $parameters[$key] = "";
                  } else {
                    unset($parameters[$key]);
                  }
                } else {
                  if (!is_string($parameters[$key])) {
                    if (empty($update)) {
                      $parameters[$key] = "";
                    } else {
                      unset($parameters[$key]);
                    }
                  }
                }
              }

              /* clean uploaded file before update */
              $data_current = null;
              if (!empty($update)) {
                $data_current = $this->database->select(
                  (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
                  array($key), 
                  array("id" => $update), 
                  null, 
                  $this->controls["update"]
                );
                if (!empty($data_current)) {
                  if (in_array($reference->type, $this->multiple_types)) {
                    if (isset($data_current->$key) && !is_array($data_current->$key)) {
                      if (
                        empty($reference->input_multiple_format)
                        || $reference->input_multiple_format == "serialize"
                      ) {
                        try {
                          if (is_string($data_current->$key)) {
                            $data_current->$key = unserialize($data_current->$key);
                          }
                        } catch (Exception $e) {}
                      }
                      if (!is_array($data_current->$key)) {
                        if (is_string($data_current->$key)) {
                          $data_current->$key = explode(",", $data_current->$key);
                        } else {
                          $data_current->$key = array();
                        }
                      }
                    }
                    foreach ($data_current->$key as $value) {
                      if (!in_array($value, $parameters[$key])) {
                        try {
                          $this->remove($update, array(
                            $key => $value
                          ));
                        } catch (Exception $e) {}
                      }
                    }
                  } else {
                    if (!empty($data_current->$key)) {
                      if (isset($parameters[$key]) && $parameters[$key] != $data_current->$key) {
                        try {
                          $this->remove($update, array(
                            $key => $data_current->$key
                          ));
                        } catch (Exception $e) {}
                      }
                    }
                  }
                }
              }

            }

            $result = $parameters[$key];
            if (isset($parameters[$key])) {
              if (in_array($reference->type, $this->multiple_types)) {
                if (
                  empty($reference->input_multiple_format)
                  || $reference->input_multiple_format == "serialize"
                ) {
                  if (!empty($parameters[$key])) {
                    if (serialize($parameters[$key])) {
                      $result = serialize($parameters[$key]);
                    } else {
                      $result = serialize(array());
                    }
                  } else {
                    $result = serialize(array());
                  }
                } else {
                  if (!empty($parameters[$key]) && is_array($parameters[$key])) {
                    $result = implode(",", $parameters[$key]);
                  } else {
                    $result = implode(",", array());
                  }
                }
              } else {
                $result = $parameters[$key];
              }
            }

            return $result;

          };

          if ($reference->type != "input-multiple") {
            $parameters[$key] = $inform($parameters, $reference);
          } else {
            foreach ($reference->references as $reference_multiple) {
              if (array_key_exists($reference_multiple->key, $parameters[$key])) {
                $parameters[$key][$reference_multiple->key] = $inform(
                  $parameters[$key], 
                  $reference_multiple
                );
              }
            }
          }

        } else {
          if (empty($update)) {
            $parameters[$key] = null;
            if (in_array($reference->type, $this->multiple_types)) {
              if (
                empty($reference->input_multiple_format)
                || $reference->input_multiple_format == "serialize"
              ) {
                $parameters[$key] = serialize(array());
              } else {
                $parameters[$key] = null;
              }
            }
          }
        }
      }
      
    }
    return Utilities::callback(
      __METHOD__, 
      func_get_args(), 
      $parameters,
      $this->session,
      $this->controls,
      $this->identifier
    );
  }

  function format($data, $return_references = false) {

    /* function: create referers before format (better performance for list) */
    $refer = function ($return_references = false, $abstracts_override = null) {
      $data = array();
      if (!empty($return_references)) {
        if (Utilities::in_references("module_id", $return_references)) {
          if (!empty($this->abstracts->component_module)) {
            $data["module_id"] = new Module($this->session, Utilities::override_controls(true, true, true, true));
          }
        }
        if (Utilities::in_references("user_id", $return_references)) {
          if (!empty($this->abstracts->component_user)) {
            $data["user_id"] = new User($this->session, Utilities::override_controls(true, true, true, true));
          }
        }
        if (Utilities::in_references("group_id", $return_references)) {
          if (!empty($this->abstracts->component_group)) {
            $data["group_id"] = new Group($this->session, Utilities::override_controls(true, true, true, true));
          }
        }
        if (Utilities::in_references("language_id", $return_references)) {
          if (!empty($this->abstracts->component_language)) {
            $data["language_id"] = new Language($this->session, Utilities::override_controls(true, true, true, true));
          }
        }
        if (Utilities::in_references("page_id", $return_references)) {
          if (!empty($this->abstracts->component_page)) {
            $data["page_id"] = new Page($this->session, Utilities::override_controls(true, true, true, true));
          }
        }
        if (Utilities::in_references("media_id", $return_references)) {
          if (!empty($this->abstracts->component_media)) {
            $data["media_id"] = new Media($this->session, Utilities::override_controls(true, true, true, true));
          }
        }
        foreach ($this->abstracts->references as $reference) {
          $reference_key = $reference->key;
          if (Utilities::in_references($reference_key, $return_references)) {
            if (
              $reference->input_option == "dynamic"
              && !empty($reference->input_option_dynamic_module)
              && !empty($reference->input_option_dynamic_value_key)
            ) {
              if (!empty(
                $module_data = $this->database->select(
                  "module", 
                  "*", 
                  array("id" => $reference->input_option_dynamic_module), 
                  null, 
                  true,
                  false
                )
              )) {
                $classes = explode("\\", get_class());
                $namespace = "\\" . $classes[0] . "\\" . Utilities::create_class_name($module_data->key);
                if (class_exists($namespace)) {
                  if (method_exists($namespace, "get")) {
                    $built = new $namespace($this->session, Utilities::override_controls(true, true, true, true));
                  } else {
                    $built = new Built($this->session, Utilities::override_controls(true, true, true, true), $module_data->key);
                  }
                } else {
                  $built = new Built($this->session, Utilities::override_controls(true, true, true, true), $module_data->key);
                }
                if (!empty($built)) {
                  $data[$reference_key] = $built;
                }
              }
            }
          }
        }
      }
  
      return $data;

    };

    /* function: format single data */
    $format = function ($data, $return_references = false, $referers = null) {
      if (!empty($data)) {
        
        if ($data->active === "1") {
          $data->active = true;
        } else if ($data->active === "0" || empty($data->active)) {
          $data->active = false;
        }
  
        if (Utilities::in_referers("module_id", $referers)) {
          if (isset($data->module_id)) {
            $data->module_id_reference = null;
            if (!empty($data->module_id)) {
              $data->module_id_reference = $referers["module_id"]->format(
                $this->database->get_reference(
                  $data->module_id,
                  "module",
                  "id"
                )
              );
            }
          }
        }
        if (Utilities::in_referers("user_id", $referers)) {
          if (isset($data->user_id)) {
            $data->user_id_reference = null;
            if (!empty($data->user_id)) {
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
        if (Utilities::in_referers("group_id", $referers)) {
          if (isset($data->group_id)) {
            $data->group_id_reference = null;
            if (!empty($data->group_id)) {
              $data->group_id_reference = $referers["group_id"]->format(
                $this->database->get_reference(
                  $data->group_id,
                  "group",
                  "id"
                )
              );
            }
          }
        }
        if (Utilities::in_referers("language_id", $referers)) {
          if (isset($data->language_id)) {
            $data->language_id_reference = null;
            if (!empty($data->language_id)) {
              $data->language_id_reference = $referers["language_id"]->format(
                $this->database->get_reference(
                  $data->language_id,
                  "language",
                  "id"
                )
              );
            }
          }
        }
        if (Utilities::in_referers("page_id", $referers)) {
          if (isset($data->page_id)) {
            $data->page_id_reference = null;
            if (!empty($data->page_id)) {
              $data->page_id_reference = $referers["page_id"]->format(
                $this->database->get_reference(
                  $data->page_id,
                  "page",
                  "id"
                )
              );
            }
          }
        }
        if (Utilities::in_referers("media_id", $referers)) {
          if (isset($data->media_id)) {
            $data->media_id_reference = null;
            if (!empty($data->media_id)) {
              $data->media_id_reference = $referers["media_id"]->format(
                $this->database->get_reference(
                  $data->media_id,
                  "media",
                  "id"
                )
              );
            }
          }
        }
        if (Utilities::in_referers("translations", $referers)) {
          $translations = $this->list(
            null, 
            null, 
            null, 
            null, 
            null, 
            array("translate" => $data->id), 
            null, 
            array("language_id")
          );
          $data->translations = $translations;
          $data->translations_key = (object) array();
          foreach ($translations as $translation) {
            $key = $translation->language_id;
            $data->translations_key->$key = $translation;
          }
          foreach ($translations as $translation) {
            if (!empty($translation->language_id_reference)) {
              $key = strtolower($translation->language_id_reference->short_name);
              $data->translations_key->$key = $translation;
            }
          }
        }
  
        foreach ($this->abstracts->references as $reference) {
          $key = $reference->key;
          $reference_key = $reference->key . "_reference";
          if (isset($data->$key)) {
  
            if (in_array($reference->type, $this->multiple_types)) {
              if (
                empty($reference->input_multiple_format)
                || $reference->input_multiple_format == "serialize" 
              ) {
                if (!empty($data->$key)) {
                  if (!is_array($data->$key)) {
                    try {
                      $data->$key = unserialize($data->$key);
                    } catch (Exception $e) {
                      $data->$key = array();
                    }
                  }
                } else {
                  $data->$key = array();
                }
              } else {
                if (!empty($data->$key)) {
                  if (!is_array($data->$key)) {
                    $data->$key = explode(",", $data->$key);
                  }
                } else {
                  $data->$key = array();
                }
              }
            }

            if (in_array($reference->type, $this->number_types)) {
              if (is_array($data->$key)) {
                for ($i = 0; $i < count($data->$key); $i++) {
                  $data->$key[$i] = intval($data->$key);
                }
              } else {
                $data->$key = intval($data->$key);
              }
            } else if (in_array($reference->type, $this->decimal_types)) {
              if (is_array($data->$key)) {
                for ($i = 0; $i < count($data->$key); $i++) {
                  $data->$key[$i] = floatval($data->$key);
                }
              } else {
                $data->$key = floatval($data->$key);
              }
            }
  
            if (
              $reference->type == "image-upload" || $reference->file_type == "image"
              || in_array($reference->type, $this->file_types)
            ) {
              $format_path = function($reference, $value) {
                $path = (object) array(
                  "id" => $value,
                  "name" => basename($value),
                  "original" => null,
                  "thumbnail" => null,
                  "large" => null
                );
                if ($reference->type == "image-upload" || $reference->file_type == "image") {
                  $path->original = $value;
                  if (strpos($value, "http://") !== 0 || strpos($value, "https://") !== 0) {
                    $path->original = $this->config["base_url"] . $value;
                  }
                  $path_thumnail = "";
                  if (file_exists(Utilities::get_thumbnail(Utilities::backtrace() . $value))) {
                    $path_thumnail = Utilities::get_thumbnail($path->original);
                  }
                  $path->thumbnail = $path_thumnail;
                  $path_large = "";
                  if (file_exists(Utilities::get_large(Utilities::backtrace() . $value))) {
                    $path_large = Utilities::get_large($path->original);
                  }
                  $path->large = $path_large;
                } else if (in_array($reference->type, $this->file_types)) {
                  $path = (object) array(
                    "id" => $value,
                    "name" => basename($value),
                    "path" => null
                  );
                  if (strpos($value, "http://") !== 0 || strpos($value, "https://") !== 0) {
                    $path->path = $this->config["base_url"] . $value;
                  }
                }
                return $path;
              };
              if (is_array($data->$key)) {
                $data->$reference_key = array();
                foreach ($data->$key as $key_value) {
                  array_push($data->$reference_key, $format_path($reference, $key_value));
                }
              } else {
                $data->$reference_key = $format_path($reference, $data->$key);
              }
            }
            
            if (Utilities::in_referers($key, $referers)) {
              if (is_array($data->$key)) {
                $data->$reference_key = array_map(
                  function ($value, $referer, $reference) {
                    return $referer->format(
                      $this->database->get_reference(
                        $value,
                        $referer->module->database_table,
                        $reference->input_option_dynamic_value_key
                      )
                    );
                  }, 
                  $data->$key, 
                  array_fill(0, count($data->$key), $referers[$key]), 
                  array_fill(0, count($data->$key), $reference)
                );
              } else {
                $data->$reference_key = $referers[$key]->format(
                  $this->database->get_reference(
                    $data->$key,
                    $referers[$key]->module->database_table,
                    $reference->input_option_dynamic_value_key
                  )
                );
              }
            }
  
          }
        }
  
      }
      return $data;
    };

    /* create referers */
    $referers = $refer($return_references);
    if (!is_array($data)) {
      /* format single data */
      $data = $format($data, $return_references, $referers);
    } else {
      /* format array data */
      $data = array_map(
        function($value, $return_references, $referers, $format) { 
          return $format($value, $return_references, $referers); 
        }, 
        $data, 
        array_fill(0, count($data), $return_references), 
        array_fill(0, count($data), $referers), 
        array_fill(0, count($data), $format)
      );
    }

    return Utilities::callback(
      __METHOD__, 
      func_get_args(), 
      $data,
      $this->session,
      $this->controls,
      $this->identifier
    );

  }

  function validate($parameters, $target_id = null, $patch = false) {
    $result = false;
    foreach ($this->abstracts->references as $reference) {
      if (in_array($reference->type, $this->file_types)) {
        $result = true;
      } else {
        if (!empty($parameters)) {
          if ($this->validation->set($parameters, $reference->key, $patch)) {
            if (
              (
                empty($reference->require) 
                || $this->validation->require(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $patch)
              ) || (
                empty($reference->validate_number) 
                || $this->validation->number(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label)
              ) || (
                empty($reference->validate_number_min) 
                || $this->validation->number_min(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $reference->validate_number_min)
              ) || (
                empty($reference->validate_number_max) 
                || $this->validation->number_max(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $reference->validate_number_max)
              ) || (
                empty($reference->validate_decimal) 
                || $this->validation->decimal(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label)
              ) || (
                empty($reference->validate_decimal_min) 
                || $this->validation->decimal_min(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $reference->validate_decimal_min)
              ) || (
                empty($reference->validate_decimal_max) 
                || $this->validation->decimal_max(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $reference->validate_decimal_max)
              ) || (
                empty($reference->validate_datetime) 
                || $this->validation->datetime(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $reference->validate_date)
              ) || (
                empty($reference->validate_datetime_min) 
                || $this->validation->datetime_min(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $reference->validate_datetime_min)
              ) || (
                empty($reference->validate_datetime_max) 
                || $this->validation->datetime_max(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $reference->validate_datetime_max)
              ) || (
                empty($reference->validate_string_min) 
                || $this->validation->string_min(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $reference->validate_string_min)
              ) || (
                empty($reference->validate_string_max) 
                || $this->validation->string_max(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $reference->validate_string_max)
              ) || (
                empty($reference->validate_email) 
                || $this->validation->email(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label)
              ) || (
                empty($reference->validate_password) 
                || $this->validation->password(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label)
              ) || (
                empty($reference->validate_password_equal_to) 
                || $this->validation->password_equal_to(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $parameters[$reference->validate_password_equal_to])
              ) || (
                empty($reference->validate_url) 
                || $this->validation->url(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label)
              ) || (
                empty($reference->validate_unique) 
                || $this->validation->unique(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label, $reference->key, $this->module->database_table, $reference->validate_unique, $target_id)
              ) || (
                empty($reference->validate_no_spaces) 
                || $this->validation->no_spaces(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label)
              ) || (
                empty($reference->validate_no_special_characters) 
                || $this->validation->no_special_characters(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->validate_no_special_characters, $reference->validate_no_special_characters)
              ) || (
                empty($reference->validate_no_digit) 
                || $this->validation->no_digit(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label)
              ) || (
                empty($reference->validate_uppercase_only) 
                || $this->validation->uppercase_only(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label)
              ) || (
                empty($reference->validate_lowercase_only)
                || $this->validation->lowercase_only(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label)
              ) || (
                empty($reference->validate_key) 
                || $this->validation->key(isset($parameters[$reference->key]) ? $parameters[$reference->key] : null, $reference->label)
              )
            ) {
              $result = true;
            }
          }
        } else {
          throw new Exception($this->translation->translate("Bad request"), 400);
        }
      }
    }
    return Utilities::callback(
      __METHOD__, 
      func_get_args(), 
      $result,
      $this->session,
      $this->controls,
      $this->identifier
    );
  }

  function simulate($module_id = null, $override_module = null) {
    $module_data = $override_module;
    if (empty($override_module)) {
      $module_data = $this->database->select(
        "module", 
        "*", 
        array("id" => $module_id), 
        null, 
        true,
        false
      );
    }
    if (!empty($module_data)) {
      $references = array();
      $columns = $this->database->columns($module_data->key);
      $simulate_reference = function($module_data, $column) {
        $validate_number = null;
        $validate_datetime = null;
        $type = "input-text";
        if ($column["DATA_TYPE"] == "int") {
          $type = "input-number";
          $validate_number = true;
        } else if ($column["DATA_TYPE"] == "date") {
          $type = "input-date";
          $validate_datetime = true;
        } else if ($column["DATA_TYPE"] == "datetime") {
          $type = "input-datetime";
          $validate_datetime = true;
        } else if ($column["DATA_TYPE"] == "tinyint") {
          $type = "switch";
        }
        $reference_data = (object) array(
          "label" => str_replace("_", " ", ucwords($column["COLUMN_NAME"])),
          "key" => $column["COLUMN_NAME"],
          "type" => $type,
          "module" => $module_data->id,
          "reference" => "",
          "placeholder" => $column["COLUMN_COMMENT"],
          "help" => "",
          "require" => "",
          "readonly" => "",
          "disable" => "",
          "hidden" => "",
          "validate_number" => $validate_number,
          "validate_number_min" => "",
          "validate_number_max" => "",
          "validate_decimal" => "",
          "validate_decimal_min" => "",
          "validate_decimal_max" => "",
          "validate_datetime" => $validate_datetime,
          "validate_datetime_min" => "",
          "validate_datetime_max" => "",
          "validate_string_max" => $column["CHARACTER_MAXIMUM_LENGTH"],
          "validate_string_min" => "",
          "validate_password" => "",
          "validate_password_equal_to" => "",
          "validate_email" => "",
          "validate_url" => "",
          "validate_no_spaces" => "",
          "validate_no_special_characters" => "",
          "validate_no_digit" => "",
          "validate_uppercase_only" => "",
          "validate_lowercase_only" => "",
          "validate_unique" => "",
          "validate_key" => "",
          "default_value" => $column["COLUMN_DEFAULT"],
          "default_switch" => "",
          "input_option" => "",
          "input_option_static_value" => "",
          "input_option_dynamic_module" => "",
          "input_option_dynamic_value_key" => "",
          "input_option_dynamic_label_key" => "",
          "input_multiple_type" => "",
          "file_type" => "",
          "file_hash" => "",
          "date_format" => "",
          "color_format" => "",
          "input_multiple_format" => "",
          "upload_folder" => "",
          "image_width" => "",
          "image_height" => "",
          "image_width_ratio" => "",
          "image_height_ratio" => "",
          "image_quality" => "",
          "image_thumbnail" => "",
          "image_thumbnail_aspectratio" => "",
          "image_thumbnail_quality" => "",
          "image_thumbnail_width" => "",
          "image_thumbnail_height" => "",
          "image_large" => "",
          "image_large_aspectratio" => "",
          "image_large_quality" => "",
          "image_large_width" => "",
          "image_large_height" => "",
          "grid_width" => "",
          "alignment" => "",
          "active" => false,
          "order" => $column["ORDINAL_POSITION"]
        );
        return $reference_data;
      };
      $database_collation = null;
      $data_sortable = false;
      $component_module = false;
      $component_group = false;
      $component_user = false;
      $component_language = false;
      $component_page = false;
      $component_media = false;
      $component_commerce = false;
      if (!empty($columns)) {
        foreach ($columns as $column) {
          array_push($references, $simulate_reference($module_data, $column));
          if (!is_null($column["COLLATION_NAME"])) {
            $database_collation = $column["COLLATION_NAME"];
          } else if ($column["COLUMN_NAME"] == "order") {
            $data_sortable = true;
          } else if ($column["COLUMN_NAME"] == "module_id") {
            $component_module = true;
          } else if ($column["COLUMN_NAME"] == "group_id") {
            $component_group = true;
          } else if ($column["COLUMN_NAME"] == "user_id") {
            $component_user = true;
          } else if ($column["COLUMN_NAME"] == "language_id") {
            $component_language = true;
          } else if ($column["COLUMN_NAME"] == "page_id") {
            $component_page = true;
          } else if ($column["COLUMN_NAME"] == "media_id") {
            $component_media = true;
          } else if ($column["COLUMN_NAME"] == "price" && $column["COLUMN_NAME"] == "currency") {
            $component_commerce = true;
          }
        }
      }
      $abstracts_data = (object) array(
        "id" => $module_data->id,
        "key" => $module_data->key,
        "description" => $module_data->description,
        "component_module" => $component_module,
        "component_group" => $component_group,
        "component_user" => $component_user,
        "component_language" => $component_language,
        "component_page" => $component_page,
        "component_media" => $component_media,
        "component_commerce" => $component_commerce,
        "database_collation" => $database_collation,
        "data_sortable" => $data_sortable,
        "template" => $module_data->page_template,
        "icon" => $module_data->icon,
        "category" => $module_data->category,
        "subject" => $module_data->subject,
        "subject_icon" => $module_data->subject_icon,
        "order" => $module_data->order,
        "references" => $references
      );
      return $abstracts_data;
    } else {
      return $module_data;
    }
  }

  private function translate($id, $translations = null) {
    if (!empty($translations) && !empty($this->abstracts->component_language)) {
      $translations_current_list = $this->list(
        null, 
        null, 
        null, 
        null, 
        null, 
        array(
          "translate" => $id
        )
      );
      foreach ($translations_current_list as $translations_current) {
        if (
          !in_array(
            $translations_current->language_id, 
            array_map(function($value) {
              return isset($value["language_id"]) ? $value["language_id"] : null;
            }, $translations)
          )
        ) {
          $this->delete($translations_current->id);
        }
      }
      foreach ($translations as $translation) {
        $translation["translate"] = $id;
        if (
          in_array(
            $translation["language_id"], 
            array_map(function($value) {
              return $value->language_id;
            }, $translations_current_list)
          )
        ) {
          $this->patch($translations_current->id, $translation);
        } else {
          $this->create($translation);
        }
      }
      return true;
    } else {
      return false;
    }
  }

  private function distranslate($id) {
    if (!empty($this->abstracts->component_language)) {
      $translations_current_list = $this->list(
        null, 
        null, 
        null, 
        null, 
        null, 
        array(
          "translate" => $id
        )
      );
      foreach ($translations_current_list as $translations_current) {
        $this->patch($translations_current->id, array("translate" => "0"));
      }
    }
    return true;
  }

}