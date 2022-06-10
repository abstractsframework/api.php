<?php
namespace Abstracts;

use \Abstracts\Database;
use \Abstracts\Validation;
use \Abstracts\Translation;
use \Abstracts\Utilities;
use \Abstracts\Abstracts;
use \Abstracts\API;
use \Abstracts\User;

use Exception;

class Built {

  /* configuration */
  private $id = null;
  private $public_functions = array(
	);

  /* initialization */
  public $module = null;
  private $class = null;
  private $config = null;
  private $session = null;
  private $controls = null;
  private $identifier = null;
  private $abstracts = null;
  private $file_types = array(
    "input-file",
    "input-file-multiple",
    "input-file-multiple-drop",
    "image-upload"
  );
  private $serialize_types = array(
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

  /* helpers */
  private $database = null;
  private $validation = null;
  private $translation = null;
  private $utilities = null;

  /* services */
  private $api = null;

  function __construct(
    $config,
    $session = null,
    $controls = null,
    $identifier = null
  ) {

    $this->config = $config;
    $this->session = $session;
    $this->identifier = $identifier;
    $this->module = Utilities::sync_module($config, $identifier);
    $this->id = (!empty($this->module) ? $this->module->id : null);
    $this->class = Utilities::get_class_name($identifier);
    $this->controls = Utilities::sync_control(
      $this->id, 
      $session, 
      $controls,
      $this->module
    );
    
    $this->database = new Database($this->config, $this->session, $this->controls);
    $this->validation = new Validation($this->config);
    $this->translation = new Translation();
    $this->utilities = new Utilities();

    $this->api = new API($this->config, $this->session, $this->controls);
    
    $this->abstracts = $this->initialize($this->id);
    
  }

  function initialize($id) {
    $abstracts_data = null;
    if (!empty($id)) {
      $abstracts = new Abstracts(
        $this->config, 
        $this->session, 
        array(
          "view" => true,
          "create" => false,
          "update" => false,
          "delete" => false
        )
      );
      $abstracts_data = $abstracts->get($id);
    }
    return $abstracts_data;
  }

  function request($function, $parameters) {
    $result = null;
    if ($this->api->authorize($this->id, $function, $this->public_functions)) {
      if (!empty($this->module)) {
        if ($function == "get") {
          $result = $this->$function(
            (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
            (isset($parameters["get"]["activate"]) ? $parameters["get"]["activate"] : null)
          );
        } else if ($function == "list") {
          $result = $this->$function(
            (isset($parameters["get"]["start"]) ? $parameters["get"]["start"] : null), 
            (isset($parameters["get"]["limit"]) ? $parameters["get"]["limit"] : null), 
            (isset($parameters["get"]["sort_by"]) ? $parameters["get"]["sort_by"] : null), 
            (isset($parameters["get"]["sort_direction"]) ? $parameters["get"]["sort_direction"] : null), 
            (isset($parameters["get"]["activate"]) ? $parameters["get"]["activate"] : null), 
            (isset($parameters["post"]["filters"]) ? $parameters["post"]["filters"] : null), 
            (isset($parameters["post"]["extensions"]) ? $parameters["post"]["extensions"] : null), 
            (isset($parameters["get"]["key"]) ? $parameters["get"]["key"] : 
              (isset($parameters["post"]["key"]) ? $parameters["post"]["key"] : null)
            ),
            (isset($parameters["get"]["value"]) ? $parameters["get"]["value"] : 
              (isset($parameters["post"]["value"]) ? $parameters["post"]["value"] : null)
            )
          );
        } else if ($function == "count") {
          $result = $this->$function(
            (isset($parameters["get"]["start"]) ? $parameters["get"]["start"] : null), 
            (isset($parameters["get"]["limit"]) ? $parameters["get"]["limit"] : null), 
            (isset($parameters["get"]["activate"]) ? $parameters["get"]["activate"] : null), 
            (isset($parameters["post"]["filters"]) ? $parameters["post"]["filters"] : null), 
            (isset($parameters["post"]["extensions"]) ? $parameters["post"]["extensions"] : null)
          );
        } else if ($function == "create") {
          $result = $this->$function($parameters["post"]);
        } else if ($function == "update") {
          $result = $this->$function(
            (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
            (isset($parameters["put"]) ? $parameters["put"] : null)
          );
        } else if ($function == "delete") {
          $result = $this->$function(
            (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null)
          );
        } else if ($function == "patch") {
          $result = $this->$function(
            (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
            (isset($parameters["patch"]) ? $parameters["patch"] : null)
          );
        } else if ($function == "upload") {
          $result = $this->$function(
            (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
            $_FILES
          );
        } else if ($function == "remove_file") {
          $result = $this->$function(
            (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
            (isset($parameters["patch"]) ? $parameters["patch"] : null)
          );
        } else if ($function == "data") {
          $result = $this->$function(
            (isset($parameters["get"]["key"]) ? $parameters["get"]["key"] : null),
            (isset($parameters["get"]["value"]) ? $parameters["get"]["value"] : null)
          );
        } else if ($function == "list_option") {
          $result = $this->$function(
            (isset($parameters["get"]["key"]) ? $parameters["get"]["key"] : null),
            (isset($parameters["get"]["start"]) ? $parameters["get"]["start"] : null), 
            (isset($parameters["get"]["limit"]) ? $parameters["get"]["limit"] : null), 
            (isset($parameters["get"]["sort_by"]) ? $parameters["get"]["sort_by"] : null), 
            (isset($parameters["get"]["sort_direction"]) ? $parameters["get"]["sort_direction"] : null), 
            (isset($parameters["get"]["activate"]) ? $parameters["get"]["activate"] : null), 
            (isset($parameters["post"]["filters"]) ? $parameters["post"]["filters"] : null), 
            (isset($parameters["post"]["extensions"]) ? $parameters["post"]["extensions"] : null)
          );
        } else {
          throw new Exception($this->translation->translate("Function not supported"), 421);
        }
      } else {
        throw new Exception($this->translation->translate("Module not found"), 500);
      }
    } else {
      throw new Exception($this->translation->translate("Permission denied"), 403);
    }
    return $result;
  }

  function get($id, $activate = null) {
    if ($this->validation->require($id, "ID")) {
      $filters = array("id" => $id);
      if ($activate) {
        $filters["activate"] = "1";
      }
      $data = $this->database->select(
        ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
        "*", 
        $filters, 
        null, 
        true
      );
      if (!empty($data)) {
        return $this->callback(__METHOD__, func_get_args(), $this->format($data));
      } else {
        throw new Exception($this->translation->translate("Not found"), 404);
        return null;
      }
    } else {
      return null;
    }
  }

  function list(
    $start = 0, 
    $limit = "", 
    $sort_by = "id", 
    $sort_direction = "desc", 
    $activate = false, 
    $filters = array(), 
    $extensions = array(),
    $key = null, 
    $value = null
  ) {
    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      if (!empty($activate)) {
        array_push($filters, array("activate" => true));
      }
      if (!empty($key) && !empty($value)) {
        array_push($filters, array($key => $value));
      }
      $list = $this->database->select_multiple(
        ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
        "*", 
        $filters, 
        $start, 
        $extensions, 
        $limit, 
        $sort_by, 
        $sort_direction, 
        $this->controls["view"]
      );
      if (!empty($list)) {
        $data = array();
        foreach($list as $value) {
          array_push($data, $this->format($value));
        }
        return $this->callback(__METHOD__, func_get_args(), $data);
      } else {
        return array();
      }
    } else {
      return null;
    }
  }

  function count(
    $start = 0, 
    $limit = "", 
    $activate = false, 
    $filters = array(), 
    $extensions = array()
  ) {
    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      if (!empty($activate)) {
        array_push($filters, array("activate" => true));
      }
      if (!empty($key) && !empty($value)) {
        array_push($filters, array($key => $value));
      }
      if (
        $data = $this->database->count(
          ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
          "*", 
          $filters, 
          $start, 
          $extensions, 
          $limit, 
          $this->controls["view"]
        )
      ) {
        return $data;
      } else {
        return null;
      }
    } else {
      return null;
    }
  }

  function create($parameters, $user_id = 0) {
      
    /* initialize: parameters */
    $parameters = $this->inform($parameters);
    $parameters["id"] = (isset($parameters["id"]) ? $parameters["id"] : null);
    $parameters["activate"] = (isset($parameters["activate"]) ? $parameters["activate"] : true);
    $parameters["user_id"] = (!empty($user_id) ? $user_id : (!empty($this->session) ? $this->session->id : 0));
    $parameters["date_created"] = gmdate("Y-m-d H:i:s");

    if ($this->validate($parameters)) {
      return $this->callback(
        __METHOD__, 
        func_get_args(), 
        $this->format(
          $this->database->insert(
            ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
            $this->purify($parameters), 
            $this->controls["create"]
          )
        )
      );
    } else {
      return null;
    }

  }

  function update($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters);
    
    if ($this->validation->require($id, "ID")) {

      if ($this->validate($parameters, $id)) {
        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $this->format(
            $this->database->update(
              ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
              $this->purify($parameters), 
              array("id" => $id), 
              null, 
              $this->controls["update"]
            )
          )
        );
      } else {
        return null;
      }

    } else {
      return null;
    }

  }

  function patch($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters);
    
    if ($this->validation->require($id, "ID")) {

      if ($this->validate($parameters, $id, true)) {
        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $this->format(
            $this->database->update(
              ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
              $this->purify($parameters), 
              array("id" => $id), 
              null, 
              $this->controls["update"]
            )
          )
        );
      } else {
        return null;
      }

    } else {
      return null;
    }

  }

  function delete($id) {
    if ($this->validation->require($id, "ID")) {
      if (
        $data = $this->format(
          $this->database->delete(
            ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
            array("id" => $id), 
            null, 
            $this->controls["delete"]
          )
        )
      ) {

        foreach($this->abstracts->references as $reference) {
          if (in_array($reference->type, $this->file_types)) {
            $key = $reference->key;
            if ($reference->type == "image-upload" || $reference->file_type == "image") {
              if (
                $reference->type == "input-file-multiple"
                || $reference->type == "input-file-multiple-drop"
              ) {
                foreach($data as $value) {
                  foreach($value->$key as $file) {
                    $file_old = ".." . $file;
                    if (!empty($file) && file_exists($file_old)) {
                      chmod($file_old, 0777);
                      unlink($file_old);
                    }
                  }
                }
              } else {
                foreach($data as $value) {
                  $file_old = ".." . $value->$key;
                  if (!empty($value->$key) && file_exists($file_old)) {
                    chmod($file_old, 0777);
                    unlink($file_old);
                  }
                }
              }
            }
          }
        }

        return $this->callback(
          __METHOD__, 
          func_get_args(), 
          $data
        );

      } else {
        return null;
      }
    } else {
      return null;
    }
  }

  function upload($id, $files) {
    if ($this->validation->require($id, "ID")) {
      
      if (!empty(
        $data_current = $this->database->select(
          ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
          "*", 
          array("id" => $id), 
          null, 
          true
        )
      )) {
        
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

          $image_options = array();
          $image_options["quality"] = 75;
          if (!empty($this->module)) {
            $image_options["quality"] = (
              !empty($reference->image_quality) ? $reference->image_quality 
              : (isset($this->config["image_quality"]) ? $this->config["image_quality"] : $image_options["quality"])
            );
          }
          $image_options["thumbnail"] = false;
          if (!empty($this->module)) {
            $image_options["thumbnail"] = (
              !empty($reference->image_thumbnail) ? $reference->image_thumbnail 
              : (isset($this->config["image_thumbnail"]) ? $this->config["image_thumbnail"] : $image_options["thumbnail"])
            );
          }
          $image_options["thumbnail_aspectratio"] = 75;
          if (!empty($this->module)) {
            $image_options["thumbnail_aspectratio"] = (
              !empty($reference->image_thumbnail_aspectratio) ? $reference->image_thumbnail_aspectratio 
              : (isset($this->config["image_thumbnail_aspectratio"]) ? $this->config["image_thumbnail_aspectratio"] : $image_options["thumbnail_aspectratio"])
            );
          }
          $image_options["thumbnail_quality"] = 75;
          if (!empty($this->module)) {
            $image_options["thumbnail_quality"] = (
              !empty($reference->image_thumbnail_quality) ? $reference->image_thumbnail_quality 
              : (isset($this->config["image_thumbnail_quality"]) ? $this->config["image_thumbnail_quality"] : $image_options["thumbnail_quality"])
            );
          }
          $image_options["thumbnail_width"] = 200;
          if (!empty($this->module)) {
            $image_options["thumbnail_width"] = (
              !empty($reference->image_thumbnail_width) ? $reference->image_thumbnail_width 
              : (isset($this->config["image_thumbnail_width"]) ? $this->config["image_thumbnail_width"] : $image_options["thumbnail_width"])
            );
          }
          $image_options["thumbnail_height"] = 200;
          if (!empty($this->module)) {
            $image_options["thumbnail_height"] = (
              !empty($reference->image_thumbnail_height) ? $reference->image_thumbnail_height 
              : (isset($this->config["image_thumbnail_height"]) ? $this->config["image_thumbnail_height"] : $image_options["thumbnail_height"])
            );
          }
          $image_options["large"] = false;
          if (!empty($this->module)) {
            $image_options["large"] = (
              !empty($reference->image_large) ? $reference->image_large 
              : (isset($this->config["image_large"]) ? $this->config["image_large"] : $image_options["large"])
            );
          }
          $image_options["large_aspectratio"] = 75;
          if (!empty($this->module)) {
            $image_options["large_aspectratio"] = (
              !empty($reference->image_large_aspectratio) ? $reference->image_large_aspectratio 
              : (isset($this->config["image_large_aspectratio"]) ? $this->config["image_large_aspectratio"] : $image_options["large_aspectratio"])
            );
          }
          $image_options["large_quality"] = 75;
          if (!empty($this->module)) {
            $image_options["large_quality"] = (
              !empty($reference->image_large_quality) ? $reference->image_large_quality 
              : (isset($this->config["image_large_quality"]) ? $this->config["image_large_quality"] : $image_options["large_quality"])
            );
          }
          $image_options["large_width"] = 200;
          if (!empty($this->module)) {
            $image_options["large_width"] = (
              !empty($reference->image_large_width) ? $reference->image_large_width 
              : (isset($this->config["image_large_width"]) ? $this->config["image_large_width"] : $image_options["large_width"])
            );
          }
          $image_options["large_height"] = 200;
          if (!empty($this->module)) {
            $image_options["large_height"] = (
              !empty($reference->image_large_height) ? $reference->image_large_height 
              : (isset($this->config["image_large_height"]) ? $this->config["image_large_height"] : $image_options["large_height"])
            );
          }
          
          $info = pathinfo($name);
          $extension = strtolower($info["extension"]);
          $basename = $info["basename"];
          $name = $info["filename"];
          $name_encrypted = gmdate("YmdHis") . "_" . $data_target->id . "_" . uniqid();
          $name_save = $name_encrypted . "." . $extension;
    
          $media_directory = "/" . trim((isset($this->config->media_path) ? $this->config->media_path : "media"), "/") . "/";
          $media_directory_path = ".." . $media_directory;

          $upload_directory = $media_directory . trim($destination, "/") . "/";
          $upload_directory_path = $media_directory_path . trim($destination, "/") . "/";
          if (!file_exists($media_directory_path)) {
            mkdir($media_directory_path, 0777, true);
          }
          if (!file_exists($upload_directory_path)) {
            mkdir($upload_directory_path, 0777, true);
          }
          $path = $upload_directory_path . $name_save;
          $path_save = $upload_directory . $name_save;
    
          if (file_exists($upload_directory_path)) {
            $upload_result = false;
            if ($reference->type == "image-upload" || $reference->file_type == "image") {
              try {
                $image = null;
                $image_info = getimagesize($tmp_name);
                if ($image_info["mime"] == 'image/jpeg') {
                  $image = imagecreatefromjpeg($tmp_name);
                } else if ($image_info["mime"] == 'image/gif') {
                  $image = imagecreatefromgif($tmp_name);
                } else if ($image_info["mime"] == 'image/png') {
                  $image = imagecreatefrompng($tmp_name);
                }
                if (is_null($image)) {
                  $upload_result = move_uploaded_file($tmp_name, $path);
                } else {
                  $upload_result = imagejpeg($image, $path, $image_options["quality"]);
                }
              } catch(Exception $e) {
                $upload_result = move_uploaded_file($tmp_name, $path);
              }
            } else {
              $upload_result = move_uploaded_file($tmp_name, $path);
            }
            if ($upload_result) {
    
              $parameter = $path_save;
              if (
                $reference->type == "input-file-multiple"
                || $reference->type == "input-file-multiple-drop"
              ) {
                $key = $reference->key;
                $parameter = unserialize($data_target->$key);
                array_push($parameter, $path_save);
                $parameter = serialize($parameter);
              }
              $parameters = array(
                $reference->key => $parameter
              );
              if (
                $this->database->update(
                  ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
                  $this->purify($parameters), 
                  array("id" => $data_target->id), 
                  null, 
                  $this->controls["update"]
                )
              ) {
                if ($reference->type == "image-upload" || $reference->file_type == "image") {
                  if ($image_options["thumbnail"] == true) {
                    $thumbnail_directory_path	= $upload_directory_path . "thumbnail/";
                    if (!file_exists($thumbnail_directory_path)) {
                      mkdir($thumbnail_directory_path, 0777, true);
                    }
                    $thumbnail = $thumbnail_directory_path . $name_save;
                    Utilities::generate_thumbnail(
                      $path, 
                      $thumbnail, 
                      $image_options["thumbnail_width"], 
                      $image_options["thumbnail_height"], 
                      $image_options["thumbnail_aspectratio"], 
                      $image_options["thumbnail_quality"]
                    );
                  }
                  if ($image_options["large"] == true) {
                    $large_directory_path	= $upload_directory_path . "large/";
                    if (!file_exists($large_directory_path)) {
                      mkdir($large_directory_path, 0777, true);
                    }
                    $large = $large_directory_path . $name_save;
                    Utilities::generate_thumbnail(
                      $path, 
                      $large, 
                      $image_options["large_width"], 
                      $image_options["large_height"], 
                      $image_options["large_aspectratio"], 
                      $image_options["large_quality"]
                    );
                  }
                }

                if (
                  $reference->type == "input-file"
                  || $reference->type == "image-upload"
                ) {
                  if (isset($data_target->logo) && !empty($data_target->logo)) {
                    $file_old = str_replace($media_directory, $media_directory_path, $data_target->logo);
                    if (!empty($file_old) && file_exists($file_old)) {
                      chmod($file_old, 0777);
                      unlink($file_old);
                    }
                  }
                }
                
                return true;

              } else {
                if (file_exists($path) && !is_dir($path)) {
                  chmod($path, 0777);
                  unlink($path);
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

        $success = array();
        $errors = array();
        foreach($this->abstracts->references as $reference) {
          if (in_array($reference->type, $this->file_types)) {
            $key = $reference->key;
            if (
              $reference->type == "input-file-multiple"
              || $reference->type == "input-file-multiple-drop"
            ) {
              for ($i = 0; $i < count($files[$key]["name"]); $i++) {
                if (isset($files[$key]) && isset($files[$key]["name"][$i]) && !empty($files[$key]["name"][$i])) {
                  if (
                    $upload(
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
                    array_push($success, $files[$key]["name"][$i]);
                  } else {
                    array_push($errors, $files[$key]["name"][$i]);
                  }
                }
              }
            } else {
              if (isset($files[$key]) && isset($files[$key]["name"]) && !empty($files[$key]["name"])) {
                if (
                  $upload(
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
                  array_push($success, $files[$key]["name"]);
                } else {
                  array_push($errors, $files[$key]["name"]);
                }
              }
            }
          }
        }

        if (empty(count($errors))) {
          return $this->callback(__METHOD__, func_get_args(), $success);
        } else {
          throw new Exception($this->translation->translate("Unable to upload") . " '" . implode("', '", $reference->key) . "'", 409);
        }

      } else {
        return null;
      }

    } else {
      return null;
    }
  }

  function remove_file($id, $parameters) {
    if ($this->validation->require($id, "ID")) {
      if (!empty($parameters)) {
        
        if (!empty(
          $data_current = $this->database->select(
            ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
            "*", 
            array("id" => $id), 
            null, 
            true
          )
        )) {

          $delete = function($reference, $file) {
            try {
              $file_old = ".." . $file;
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

          $success = array();
          $errors = array();
          foreach($this->abstracts->references as $reference) {
            if (in_array($reference->type, $this->file_types)) {
              $key = $reference->key;
              if (isset($parameters[$key])) {

                if (is_array($parameters[$key])) {
                  foreach($parameters[$key] as $file) {
                    if ($delete($reference, $file)) {
                      array_push($success, $file);
                    } else {
                      array_push($errors, $file);
                    }
                  }
                } else {
                  if ($delete($reference, $parameters[$key])) {
                    array_push($success, $parameters[$key]);
                  } else {
                    array_push($errors, $parameters[$key]);
                  }
                }

                if (count($success)) {
                  $parameter = $success[0];
                  if (
                    $reference->type == "input-file-multiple"
                    || $reference->type == "input-file-multiple-drop"
                  ) {
                    $key = $reference->key;
                    $parameter = unserialize($data_current->$key);
                    for ($i = 0; $i < count($success); $i++) {
                      unset($parameter[$i]);
                    }
                    $parameter = serialize($parameter);
                  }
                  $parameters = array(
                    $reference->key => $parameter
                  );
                  if (
                    $this->database->update(
                      ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
                      $parameters, 
                      array("id" => $data_current->id), 
                      null, 
                      $this->controls["update"]
                    )
                  ) {
                    
                  }
                  
                }

              }
            }
          }

          if (empty(count($errors))) {
            return $this->callback(__METHOD__, func_get_args(), $success);
          } else {
            throw new Exception($this->translation->translate("Unable to delete") . " '" . implode("', '", $reference->key) . "'", 409);
          }

        } else {
          return null;
        }

      } else {
        throw new Exception($this->translation->translate("File(s) not found"), 400);
        return null;
      }
    } else {
      return null;
    }
  }

  function list_option(
    $key, 
    $start = 0, 
    $limit = "", 
    $sort_by = "id", 
    $sort_direction = "desc", 
    $activate = false, 
    $filters = array(), 
    $extensions = array()
  ) {
    if (
      $this->validation->filters($filters) 
      && $this->validation->extensions($extensions)
    ) {
      array_push($filters, array("activate" => true));
      if (!empty($activate)) {
        array_push($filters, array("activate" => true));
      }
      if (!empty($key) && !empty($value)) {
        array_push($filters, array($key => $value));
      }
      $list = $this->database->select_multiple(
        $key, 
        "*", 
        $filters, 
        $start, 
        $extensions, 
        $limit, 
        $sort_by, 
        $sort_direction, 
        $this->controls["view"]
      );
      if (!empty($list)) {
        $data = array();
        foreach($list as $value) {
          array_push($data, $this->format($value));
        }
        return $this->callback(__METHOD__, func_get_args(), $data);
      } else {
        return array();
      }
    } else {
      return null;
    }
  }

  function inform($parameters) {
    if (!empty($parameters)) {
      foreach($this->abstracts->references as $reference) {
        if (isset($parameters[$reference->key])) {
          if (in_array($reference->type, $this->file_types)) {
            unset($parameters[$reference->key]);
            $parameters[$reference->key] = "";
          } else {
            if (in_array($reference->type, $this->serialize_types)) {
              if ($reference->type == "serialize") {
                $parameters[$reference->key] = serialize($parameters[$reference->key]);
              } else {
                $parameters[$reference->key] = implode(",", $parameters[$reference->key]);
              }
            }
          }
        }
      }
    }
    return $parameters;
  }

  function format($data, $prevent_data = false, $abstracts_override = null) {
    if (!empty($data)) {
      $abstracts = $this->abstracts;
      if (!empty($abstracts_override)) {
        $abstracts = $abstracts_override;
      }
      if (!empty($this->abstracts->component_module)) {
        $data->module_id_data = $this->database->select(
          "module", 
          "*", 
          array("id" => $data->module_id), 
          null, 
          true
        );
      }
      if (!empty($this->abstracts->component_user)) {
        $user = new User($this->config, $this->session, $this->controls);
        $data->user_id_data = $user->format(
          $this->database->select(
            "user", 
            "*", 
            array("id" => $data->user_id), 
            null, 
            true
          ),
          true
        );
      }
      if (!empty($this->abstracts->component_group)) {
        $data->group_id_data = $this->database->select(
          "group", 
          "*", 
          array("id" => $data->group_id), 
          null, 
          true
        );
      }
      if (!empty($this->abstracts->component_page)) {
        $data->page_id_data = $this->database->select(
          "page", 
          "*", 
          array("id" => $data->page_id), 
          null, 
          true
        );
      }
      foreach($abstracts->references as $reference) {
        $key = $reference->key;
        if (isset($data->$key)) {

          if (empty($abstracts_override) || $prevent_data) {
            if (!is_null($key_data = $this->data(null, $reference->key, $data))) {
              $data_key = $reference->key . "_data";
              $data->$data_key = $key_data;
            }
          }

          if (in_array($reference->type, $this->serialize_types)) {
            if ($reference->type == "serialize") {
              $data->$key = unserialize($data->$key);
            } else {
              $data->$key = explode(",", $data->$key);
            }
          }

          if (
            $reference->type == "image-upload" || $reference->file_type == "image"
            || in_array($reference->type, $this->file_types)
          ) {
            $path_key = $reference->key . "_path";
            $format_path = function($reference, $value) {
              $path = (object) array(
                "name" => basename($value),
                "original" => null,
                "thumbnail" => null,
                "large" => null,
              );
              if ($reference->type == "image-upload" || $reference->file_type == "image") {
                $path->original = $value;
                if (strpos($value, "http://") !== 0 || strpos($value, "https://") !== 0) {
                  $path->original = $this->config["base_url"] . $value;
                }
                $path->thumbnail= Utilities::get_thumbnail($path->original);
                $path->large = Utilities::get_large($path->original);
              } else if (in_array($reference->type, $this->file_types)) {
                $path = $value;
                if (strpos($value, "http://") !== 0 || strpos($value, "https://") !== 0) {
                  $path = $this->config["base_url"] . $value;
                }
              }
              return $path;
            };
            if (is_array($data->$key)) {
              $data->$path_key = array();
              foreach($data->$key as $value) {
                array_push($data->$path_key, $format_path($reference, $value));
              }
            } else {
              $data->$path_key = $format_path($reference, $data->$key);
            }
            $data->$key = basename($data->$key);
          }

        }
      }
    }
    return $data;
  }

  function data($id = null, $key, $data_override = null) {
    if ($this->validation->require($key, "Key")) {
      if (empty($data_override)) {
        if ($this->validation->require($id, "ID")  && $this->validation->require($key, "Key")) {
          $filters = array("id" => $id);
          $data_override = $this->database->select(
            ($this->module && isset($this->module->database_table) ? $this->module->database_table : ""), 
            "`" . $key . "`", 
            $filters, 
            null, 
            true
          );
        } else {
          return null;
        }
      }
      if (!empty($data_override) && isset($data_override->$key)) {
  
        $result = null;
  
        foreach($this->abstracts->references as $reference) {
          if ($reference->key == $key && $reference->input_option == "dynamic") {
  
            if (in_array($reference->type, $this->serialize_types)) {
              if ($reference->type == "serialize") {
                $data_override->$key = unserialize($data_override->$key);
              } else {
                $data_override->$key = explode(",", $data_override->$key);
              }
            }
  
            $get_data = function($reference, $value) {
              $data = $value;
              if (!empty($reference->input_option_dynamic_module) && !empty($reference->input_option_dynamic_value_key)) {
                if ($value !== "" && !is_null($value)) {
                  $abstracts = new Abstracts(
                    $this->config, 
                    $this->session, 
                    array(
                      "view" => true,
                      "create" => false,
                      "update" => false,
                      "delete" => false
                    )
                  );
                  $key_abstracts = $abstracts->get($reference->input_option_dynamic_value_key);
                  if (!empty($key_abstracts)) {
                    
                    if (intval($reference->input_option_dynamic_module) > 100) {
                      $data = $this->format(
                        $this->database->select(
                          $reference->input_option_dynamic_module, 
                          "*", 
                          array($reference->input_option_dynamic_value_key => $value), 
                          null, 
                          true
                        ),
                        true,
                        $key_abstracts
                      );
                    } else {
                      if ($reference->input_option_dynamic_module == "6") {
                        $user = new User($this->config, $this->session, $this->controls);
                        $data = $user->format($data, true);
                      }
                    }
                  }
                }
              }
              return $data;
            };
            
            if (is_array($data_override->$key)) {
              $result = array();
              foreach($data_override->$key as $value) {
                array_push($result, $get_data($reference, $value));
              }
            } else {
              $result = $get_data($reference, $data_override->$key);
            }
            
          }
        }
  
        return $this->callback(__METHOD__, func_get_args(), $result);
  
      } else {
        throw new Exception($this->translation->translate("Not found"), 404);
        return null;
      }
    } else {
      return null;
    }
  }

  function validate($parameters, $target_id = null, $patch = false) {
    $result = false;
    foreach($this->abstracts->references as $reference) {
      if (in_array($reference->type, $this->file_types)) {
        $result = true;
      } else {
        if (!empty($parameters)) {
          if (!empty($parameters) && ($this->validation->set($parameters, $reference->key) || $patch)) {
            if (
              (
                empty($reference->require) 
                || $this->validation->require($parameters[$reference->key], $reference->label)
              ) || (
                empty($reference->validate_number) 
                || $this->validation->number($parameters[$reference->key], $reference->label)
              ) || (
                empty($reference->validate_number_min) 
                || $this->validation->number_min($parameters[$reference->key], $reference->label, $reference->validate_number_min)
              ) || (
                empty($reference->validate_number_max) 
                || $this->validation->number_max($parameters[$reference->key], $reference->label, $reference->validate_number_max)
              ) || (
                empty($reference->validate_decimal) 
                || $this->validation->decimal($parameters[$reference->key], $reference->label)
              ) || (
                empty($reference->validate_decimal_min) 
                || $this->validation->decimal_min($parameters[$reference->key], $reference->label, $reference->validate_decimal_min)
              ) || (
                empty($reference->validate_decimal_max) 
                || $this->validation->decimal_max($parameters[$reference->key], $reference->label, $reference->validate_decimal_max)
              ) || (
                empty($reference->validate_datetime) 
                || $this->validation->datetime($parameters[$reference->key], $reference->label, $reference->validate_date)
              ) || (
                empty($reference->validate_datetime_min) 
                || $this->validation->datetime_min($parameters[$reference->key], $reference->label, $reference->validate_datetime_min)
              ) || (
                empty($reference->validate_datetime_max) 
                || $this->validation->datetime_max($parameters[$reference->key], $reference->label, $reference->validate_datetime_max)
              ) || (
                empty($reference->validate_string_min) 
                || $this->validation->string_min($parameters[$reference->key], $reference->label, $reference->validate_string_min)
              ) || (
                empty($reference->validate_string_max) 
                || $this->validation->string_max($parameters[$reference->key], $reference->label, $reference->validate_string_max)
              ) || (
                empty($reference->validate_email) 
                || $this->validation->email($parameters[$reference->key], $reference->label)
              ) || (
                empty($reference->validate_password) 
                || $this->validation->password($parameters[$reference->key], $reference->label)
              ) || (
                empty($reference->validate_password_equal_to) 
                || $this->validation->password_equal_to($parameters[$reference->key], $reference->label, $parameters[$reference->validate_password_equal_to])
              ) || (
                empty($reference->validate_url) 
                || $this->validation->url($parameters[$reference->key], $reference->label)
              ) || (
                empty($reference->validate_unique) 
                || $this->validation->unique($parameters[$reference->key], $reference->label, $reference->key, $this->module->database_table, $reference->validate_unique, $target_id)
              ) || (
                empty($reference->validate_no_spaces) 
                || $this->validation->no_spaces($parameters[$reference->key], $reference->label)
              ) || (
                empty($reference->validate_no_special_characters) 
                || $this->validation->no_special_characters($parameters[$reference->key], $reference->validate_no_special_characters, $reference->validate_no_special_characters)
              ) || (
                empty($reference->validate_no_digit) 
                || $this->validation->no_digit($parameters[$reference->key], $reference->label)
              ) || (
                empty($reference->validate_uppercase_only) 
                || $this->validation->uppercase_only($parameters[$reference->key], $reference->label)
              ) || (
                empty($reference->validate_lowercase_only)
                || $this->validation->lowercase_only($parameters[$reference->key], $reference->label)
              ) || (
                empty($reference->validate_key) 
                || $this->validation->key($parameters[$reference->key], $reference->label)
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
    return $result;
  }

  function purify($parameters) {
    $allowed_keys = array();
    foreach($this->abstracts->references as $reference) {
      array_push($allowed_keys, $reference->key);
    }
    foreach($parameters as $key => $parameter) {
      if (!in_array($key, $allowed_keys)) {
        unset($parameters[$key]);
      }
    }
    return $parameters;
  }

  function callback($function, $arguments, $result) {
    $names = explode("::", $function);
    $classes = explode("\\", $names[0]);
    $namespace = "\\" . $classes[0] . "\\" . "Callback" . "\\" . $this->class;
    if (class_exists($namespace)) {
      if (method_exists($namespace, $names[1])) {
        $callback = new $namespace($this->config, $this->session, $this->controls, $this->request);
        try {
          $function_name = $names[1];
          return $callback->$function_name($arguments, $result);
        } catch(Exception $e) {
          throw new Exception($e->getMessage(), $e->getCode());
          return false;
        }
      } else {
        return $result;
      }
    } else {
      return $result;
    }
  }

}