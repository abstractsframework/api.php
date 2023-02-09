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

use Exception;
use DateTime;

class Built {

  /* configuration */
  public $id = null;
  public $public_functions = array();
  public $module = null;
  public $abstracts = null;

  /* core */
  private $class = null;
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
    $this->class = $initialize->class;
    
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
          $result = $this->$function(
            $parameters,
            null,
            $_FILES
          );
        } else if ($function == "update") {
          $result = $this->$function(
            (isset($parameters["id"]) ? $parameters["id"] : null),
            (isset($parameters) ? $parameters : null),
            $_FILES
          );
        } else if ($function == "patch") {
          $result = $this->$function(
            (isset($parameters["id"]) ? $parameters["id"] : null),
            (isset($parameters) ? $parameters : null),
            $_FILES
          );
        } else if ($function == "delete") {
          $result = $this->$function(
            (isset($parameters["id"]) ? $parameters["id"] : null)
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

  function get($id, $active = null, $return_references = false) {

    if ($this->validation->require($id, "ID")) {

      $active = Initialize::active($active);
      $return_references = Initialize::return_references($return_references);

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
        $this->log->log(
          __FUNCTION__,
          __METHOD__,
          "low",
          func_get_args(),
          (!empty($this->module) && isset($this->module->id) ? $this->module->id : ""),
          "id",
          $data->id
        );
        $referers = $this->refer($return_references);
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

  function create($parameters, $user_id = 0, $files = null) {
      
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
          return $this->callback(
            __METHOD__, 
            func_get_args(), 
            $this->format($data)
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

  function update($id, $parameters, $files = null) {
    
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
          return $this->callback(
            __METHOD__, 
            func_get_args(), 
            $this->format($data)
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

  function patch($id, $parameters, $files = null) {

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
          return $this->callback(
            __METHOD__, 
            func_get_args(), 
            $this->format($data)
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

  function delete($id) {
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

            $delete = function($reference, $file) {
              try {
                $file_old = Utilities::backtrace() . trim($file, "/");
                if (!empty($file) && file_exists($file_old)) {
                  chmod($file_old, 0777);
                  unlink($file_old);
                }
                if ($reference->type == "image-upload" || $reference->file_type == "image") {
                  $thumbnail_old = Utilities::get_thumbnail($file_old);
                  if (file_exists($thumbnail_old) && !is_dir($thumbnail_old)) {
                    chmod($thumbnail_old, 0777);
                    unlink($thumbnail_old);
                  }
                  $large_old = Utilities::get_large($file_old);
                  if (file_exists($large_old) && !is_dir($large_old)) {
                    chmod($large_old, 0777);
                    unlink($large_old);
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
                  $delete($reference, $file);
                }
              }
            } else {
              $delete($reference, $data->$key);
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
                  chmod($destination, 0777);
                  unlink($destination);
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
              if (isset($files[$key]) && isset($files[$key]["name"])) {
                for ($i = 0; $i < count($files[$key]["name"]); $i++) {
                  if (isset($files[$key]["name"][$i])) {
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
              if (isset($files[$key]) && isset($files[$key]["name"])) {
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
            return $this->callback(__METHOD__, func_get_args(), $successes);
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

          $delete = function($reference, $file) {
            try {
              $file_old = Utilities::backtrace() . trim($file, "/");
              if (!empty($file) && file_exists($file_old)) {
                chmod($file_old, 0777);
                unlink($file_old);
              }
              if ($reference->type == "image-upload" || $reference->file_type == "image") {
                $thumbnail_old = Utilities::get_thumbnail($file_old);
                if (file_exists($thumbnail_old) && !is_dir($thumbnail_old)) {
                  chmod($thumbnail_old, 0777);
                  unlink($thumbnail_old);
                }
                $large_old = Utilities::get_large($file_old);
                if (file_exists($large_old) && !is_dir($large_old)) {
                  chmod($large_old, 0777);
                  unlink($large_old);
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
                    if ($delete($reference, $file)) {
                      array_push($successes, $file);
                    } else {
                      array_push($errors, $file);
                    }
                  }
                } else {
                  if ($delete($reference, $parameters[$key])) {
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
            return $this->callback(__METHOD__, func_get_args(), $successes);
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
    $return_references = false
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
        $data = array();
        foreach ($list as $value) {
          array_push($data, $this->format($value, $return_references));
        }
        return $this->callback(__METHOD__, func_get_args(), $data);
      } else {
        return array();
      }
    } else {
      return false;
    }
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
      foreach ($this->abstracts->references as $reference) {
        $key = $reference->key;
        if (array_key_exists($key, $parameters)) {
          $inform_single = function($reference, $value) {
            if (in_array($reference->type, $this->multiple_types)) {
              if (
                empty($reference->input_multiple_format)
                || $reference->input_multiple_format == "serialize"
              ) {
                if (!empty($value)) {
                  if (serialize($value)) {
                    return serialize($value);
                  } else {
                    return serialize(array());
                  }
                } else {
                  return serialize(array());
                }
              } else {
                if (!empty($value) && is_array($value)) {
                  return implode(",", $value);
                } else {
                  return implode(",", array());
                }
              }
            } else {
              return $value;
            }
          };
          if ($reference->type != "input-multiple") {
            if (in_array($reference->type, $this->file_types)) {
              if (empty($update)) {
                $parameters[$key] = "";
              } else {
                if (empty($parameters[$key])) {
                  if (!empty(
                    $data_current = $this->database->select(
                      (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
                      array($key), 
                      array("id" => $update), 
                      null, 
                      $this->controls["update"]
                    )
                  )) {
                    $this->remove($update, array(
                      $key => $data_current->$key
                    ));
                  }
                  $parameters[$key] = $inform_single($reference, $parameters[$key]);
                } else {
                  unset($parameters[$key]);
                }
              }
            } else {
              $parameters[$key] = $inform_single($reference, $parameters[$key]);
            }
          } else {
            $parameters[$key] = serialize($parameters[$key]);
            foreach ($reference->references as $reference_multiple) {
              if (array_key_exists($reference_multiple->key, $parameters[$key])) {
                if (in_array($reference_multiple->type, $this->file_types)) {
                  unset($parameters[$key][$reference_multiple->key]);
                  if (empty($update)) {
                    $parameters[$key][$reference_multiple->key] = "";
                  } else {
                    if (!empty(
                      $data_current = $this->database->select(
                        (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : ""), 
                        array($key), 
                        array("id" => $update), 
                        null, 
                        $this->controls["update"]
                      )
                    )) {
                      if (!empty($data_current->$key)) {
                        foreach (unserialize($data_current->$key) as $file) {
                          if (!in_array($file, $parameters[$key])) {
                            $this->remove($update, array(
                              $key => $file
                            ));
                          }
                        }
                      }
                    }
                    if (empty($parameters[$key][$reference_multiple->key])) {
                      $parameters[$key][$reference_multiple->key] 
                      = $inform_single($reference_multiple, $parameters[$key][$reference_multiple->key]);
                    } else {
                      unset($parameters[$key][$reference_multiple->key]);
                    }
                  }
                } else {
                  $parameters[$key][$reference_multiple->key] 
                  = $inform_single($reference_multiple, $parameters[$key][$reference_multiple->key]);
                }
              }
            }
          }
        }
      }
    }
    return $this->callback(__METHOD__, func_get_args(), $parameters);
  }

  function refer($return_references = false, $abstracts_override = null) {

    $data = array();
    
    if (!empty($return_references)) {

      if ($return_references === true || (is_array($return_references) && in_array("module_id", $return_references))) {
        if (!empty($this->abstracts->component_module)) {
          $data["module_id"] = new Module($this->session, Utilities::override_controls(true, true, true, true));
        }
      }
      if ($return_references === true || (is_array($return_references) && in_array("user_id", $return_references))) {
        if (!empty($this->abstracts->component_user)) {
          $data["user_id"] = new User($this->session, Utilities::override_controls(true, true, true, true));
        }
      }
      if ($return_references === true || (is_array($return_references) && in_array("group_id", $return_references))) {
        if (!empty($this->abstracts->component_group)) {
          $data["group_id"] = new Group($this->session, Utilities::override_controls(true, true, true, true));
        }
      }
      if ($return_references === true || (is_array($return_references) && in_array("language_id", $return_references))) {
        if (!empty($this->abstracts->component_language)) {
          $data["language_id"] = new Language($this->session, Utilities::override_controls(true, true, true, true));
        }
      }
      if ($return_references === true || (is_array($return_references) && in_array("page_id", $return_references))) {
        if (!empty($this->abstracts->component_page)) {
          $data["page_id"] = new Page($this->session, Utilities::override_controls(true, true, true, true));
        }
      }
      if ($return_references === true || (is_array($return_references) && in_array("media_id", $return_references))) {
        if (!empty($this->abstracts->component_media)) {
          $data["media_id"] = new Media($this->session, Utilities::override_controls(true, true, true, true));
        }
      }
      foreach ($this->abstracts->references as $reference) {
        $reference_key = $reference->key;
        if ($return_references === true || (is_array($return_references) && in_array($reference_key, $return_references))) {
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

    return $this->callback(__METHOD__, func_get_args(), $data);

  }

  function format($data, $return_references = false, $referers = null) {
    if (!empty($data)) {
      
      if ($data->active === "1") {
        $data->active = true;
      } else if ($data->active === "0" || empty($data->active)) {
        $data->active = false;
      }

      if (
        $return_references === true || (
          is_array($return_references) 
          && is_array($return_references) && in_array("module_id", $return_references)
          && is_array($referers) && !empty($referers) && isset($referers["module_id"]) && !empty($referers["module_id"])
        )
      ) {
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
      if (
        $return_references === true || (
          is_array($return_references) 
          && is_array($return_references) && in_array("user_id", $return_references)
          && is_array($referers) && !empty($referers) && isset($referers["user_id"]) && !empty($referers["user_id"])
        )
      ) {
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
      if (
        $return_references === true || (
          is_array($return_references) 
          && is_array($return_references) && in_array("group_id", $return_references)
          && is_array($referers) && !empty($referers) && isset($referers["group_id"]) && !empty($referers["group_id"])
        )
      ) {
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
      if (
        $return_references === true || (
          is_array($return_references) 
          && is_array($return_references) && in_array("language_id", $return_references)
          && is_array($referers) && !empty($referers) && isset($referers["language_id"]) && !empty($referers["language_id"])
        )
      ) {
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
      if (
        $return_references === true || (
          is_array($return_references) 
          && is_array($return_references) && in_array("page_id", $return_references)
          && is_array($referers) && !empty($referers) && isset($referers["page_id"]) && !empty($referers["page_id"])
        )
      ) {
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
      if (
        $return_references === true || (
          is_array($return_references) 
          && is_array($return_references) && in_array("media_id", $return_references)
          && is_array($referers) && !empty($referers) && isset($referers["media_id"]) && !empty($referers["media_id"])
        )
      ) {
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

      foreach ($this->abstracts->references as $reference) {
        $key = $reference->key;
        $reference_key = $reference->key . "_reference";
        $data->$reference_key = null;
        if (isset($data->$key)) {

          if (in_array($reference->type, $this->multiple_types)) {
            if (
              empty($reference->input_multiple_format)
              || $reference->input_multiple_format == "serialize" 
            ) {
              if (!empty($data->$key)) {
                if (unserialize($data->$key)) {
                  $data->$key = unserialize($data->$key);
                } else {
                  $data->$key = array();
                }
              } else {
                $data->$key = array();
              }
            } else {
              if (!empty($data->$key)) {
                $data->$key = explode(",", $data->$key);
              } else {
                $data->$key = array();
              }
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

          if (is_array($referers) && !empty($referers) && isset($referers[$key])) {
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

    return $this->callback(__METHOD__, func_get_args(), $data);

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
    return $this->callback(__METHOD__, func_get_args(), $result);
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
          "file_lock" => "",
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

  function callback($function, $arguments, $result) {
    $names = explode("::", $function);
    $classes = explode("\\", $names[0]);
    $namespace = "\\" . $classes[0] . "\\" . "Callback" . "\\" . $this->class;
    if (class_exists($namespace)) {
      if (method_exists($namespace, $names[1])) {
        $callback = new $namespace($this->session, $this->controls, $this->identifier);
        try {
          $function_name = $names[1];
          return $callback->$function_name($arguments, $result);
        } catch(Exception $e) {
          throw new Exception($e->getMessage(), $e->getCode());
        }
        return $result;
      } else {
        return $result;
      }
    } else {
      return $result;
    }
  }

}