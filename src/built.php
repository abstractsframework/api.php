<?php
namespace Abstracts;

use \Abstracts\Database;
use \Abstracts\Validation;
use \Abstracts\Translation;
use \Abstracts\Utilities;
use \Abstracts\API;

use Exception;

class Built {

  /* configuration */
  private $id = "6";
  private $public_functions = array(
	);

  /* initialization */
  public $module = null;
  private $class = null;
  private $config = null;
  private $session = null;
  private $controls = null;
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
    $request = null
  ) {

    if ($request) {
      if (isset($request->module)) {
        $this->module = $request->module;
        if (!empty($this->module)) {
          $this->id = $this->module->id;
        }
      }
      if (isset($request->class)) {
        $this->class = $request->class;
      }
    }
    $this->config = $config;
    $this->session = $session;
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

    $this->api = new API($this->config);

    $this->initialize();

  }

  function initialize() {
    if (!empty($this->id)) {
      $abstract_data = $this->database->select(
        "abstract", 
        "*", 
        array("id" => $this->id), 
        null, 
        true
      );
      if (!empty($abstract_data)) {
        $reference_data = $this->database->select_multiple(
          "reference", 
          "*", 
          array("module" => $abstract_data->id), 
          null, 
          null, 
          null, 
          null, 
          null, 
          true
        );
        if (!empty($reference_data)) {
          for ($i = 0; $i < count($reference_data); $i++) {
            $reference_multiple_data = $this->database->select_multiple(
              "reference", 
              "*", 
              array("reference" => $reference_data[$i]->id), 
              null, 
              null, 
              null, 
              null, 
              null, 
              true
            );
            $reference_data[$i]->multiples = $reference_multiple_data;
          }
        }
        $abstract_data->references = $reference_data;
      }
      $this->abstracts = $abstract_data;
    }
  }

  function request($function, $parameters) {
    $result = null;
    if ($this->api->validate_request($this->id, $function, $this->public_functions)) {
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
            (isset($parameters["post"]["extensions"]) ? $parameters["post"]["extensions"] : null)
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
        } else if ($function == "file") {
          $result = $this->$function(
            (isset($parameters["get"]["id"]) ? $parameters["get"]["id"] : null),
            (isset($parameters["delete"]) ? $parameters["delete"] : null)
          );
        } else {
          throw new Exception($this->translation->translate("Not found"), 404);
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
    if ($this->validation->requires($id, "ID")) {
      $filters = array("id" => $id);
      if ($activate) {
        $filters["activate"] = "1";
      }
      $data = $this->database->select(
        ($this->module && isset($this->module->db_name) ? $this->module->db_name : ""), 
        "*", 
        array("id" => $id), 
        null, 
        true
      );
      if (!empty($data)) {
        if ($this->callback("get", $data)) {
          return $this->format($data);
        } else {
          return null;
        }
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
    $extensions = array()
  ) {
    if (!empty($activate)) {
      array_push($filters, array("activate" => "1"));
    }
    $list = $this->database->select_multiple(
      ($this->module && isset($this->module->db_name) ? $this->module->db_name : ""), 
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
      if ($this->callback("list", $data)) {
        return $data;
      } else {
        return null;
      }
    } else {
      return array();
    }
  }

  function count(
    $start = 0, 
    $limit = "", 
    $activate = false, 
    $filters = array(), 
    $extensions = array()
  ) {
    if (!empty($activate)) {
      array_push($filters, array("activate" => "1"));
    }
    if (
      $data = $this->database->count(
        ($this->module && isset($this->module->db_name) ? $this->module->db_name : ""), 
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
  }

  function create($parameters, $user_id = 0) {
      
    /* initialize: parameters */
    $parameters = $this->inform($parameters);
    $parameters["id"] = (isset($parameters["id"]) ? $parameters["id"] : null);
    $parameters["activate"] = (isset($parameters["activate"]) ? $parameters["activate"] : true);
    $parameters["user_id"] = (!empty($user_id) ? $user_id : (!empty($this->session) ? $this->session->id : 0));
    $parameters["date_created"] = gmdate("Y-m-d H:i:s");

    if ($this->validate($parameters)) {
      if (
        $this->callback(
          "create", 
          $data = $this->format(
            $this->database->insert(
              ($this->module && isset($this->module->db_name) ? $this->module->db_name : ""), 
              $parameters, 
              $this->controls["create"]
            )
          )
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

  function update($id, $parameters) {

    /* initialize: parameters */
    $parameters = $this->inform($parameters);
    
    if ($this->validation->requires($id, "ID")) {

      if ($this->validate($parameters, $id)) {
        if (
          $this->callback(
            "update", 
            $data = $this->format(
              $this->database->update(
                ($this->module && isset($this->module->db_name) ? $this->module->db_name : ""), 
                $parameters, 
                array("id" => $id), 
                null, 
                $this->controls["update"]
              )
            )
          )
        ) {
          return $data;
        } else {
          return null;
        }
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
    
    if ($this->validation->requires($id, "ID")) {

      if ($this->validate($parameters, $id, true)) {
        if (
          $this->callback(
            "patch", 
            $data = $this->format(
              $this->database->update(
                ($this->module && isset($this->module->db_name) ? $this->module->db_name : ""), 
                $parameters, 
                array("id" => $id), 
                null, 
                $this->controls["update"]
              )
            )
          )
        ) {
          return $data;
        } else {
          return null;
        }
      } else {
        return null;
      }

    } else {
      return null;
    }

  }

  function delete($id) {
    if ($this->validation->requires($id, "ID")) {
      if (
        $data = $this->format(
          $this->database->delete(
            ($this->module && isset($this->module->db_name) ? $this->module->db_name : ""), 
            array("id" => $id), 
            null, 
            $this->controls["delete"]
          )
        )
      ) {

        foreach($this->abstracts->references as $reference) {
          if (in_array($reference->type, $this->file_types)) {
            $varname = $reference->varname;
            if ($reference->type == "image-upload" || $reference->file_type == "image") {
              if (
                $reference->type == "input-file-multiple"
                || $reference->type == "input-file-multiple-drop"
              ) {
                foreach($data as $value) {
                  foreach($value->$varname as $file) {
                    $file_old = ".." . $file;
                    if (!empty($file) && file_exists($file_old)) {
                      chmod($file_old, 0777);
                      unlink($file_old);
                    }
                  }
                }
              } else {
                foreach($data as $value) {
                  $file_old = ".." . $value->$varname;
                  if (!empty($value->$varname) && file_exists($file_old)) {
                    chmod($file_old, 0777);
                    unlink($file_old);
                  }
                }
              }
            }
          }
        }

        if (
          $this->callback(
            "delete", 
            $data
          )
        ) {
          return $data;
        } else {
          return null;
        }

      } else {
        return null;
      }
    } else {
      return null;
    }
  }

  function upload($id, $files) {
    if ($this->validation->requires($id, "ID")) {

      /* configurations: file path */
      $directory_options = array(
        "images" => array(
          "jpg", "jpeg", "jpe", "jif", "jfif", "jfi", "jp2", "j2k", "jpf", "jpx", 
          "jpm", "mj2", "tif", "tiff", "png", "gif", "bmp", "dip", "pbm", "pgm", 
          "ppm", "pnm", "svg", "webp", "heic", "heif", "raw", "bpg", "apng", "avif"
        ),
        "videos" => array(
          "mov", "qt", "mpg", "mpeg", "mpe", "mpv", "mp2", "m2v", "m4v", "mp4", 
          "m4v", "avi", "mpg", "wma", "flv", "f4v", "webm", "mkv", "vob", "ogv", 
          "rm", "rmvb", "asf", "amv", "3gp", "3g2", "yuv", "mng", "gifv", "drc", 
          "svi", "nsv", "mpkg"
        ),
        "audio" => array(
          "mp3", "m4a", "ac3", "aiff", "mid", "ogg", "oga", "wav", "aa", "aac", 
          "act", "aiff", "amr", "m4a", "m4b", "mmf", "mpc", "tta", "fla", "cda",
          "opus", "weba"
        ),
        "texts" => array(
          "doc", "docx", "rtf", "ppt", "pptx", "xls", "xlsx", "csv", "pdf", "txt", 
          "psd", "ai", "log", "ade", "adp", "mdb", "accdb", "odt", "ots", "ott", 
          "odb", "odg", "otp", "otg", "odf", "ods", "odp", "html", "md", "keynote",
          "abw", "azw", "epub", "ics", "vsd", "ai", "psd"
        ),
        "fonts" => array("eot", "otf", "ttf", "woff", "woff2"),
        "models" => array(
          "3mf", "e57", "iges", "mesh", "mtl", "obj", "prc", "obj", "u3d", "max"
        ),
        "codes" => array(
          "xhtml", "xml", "css", "sql", "js", "ts", "jsx", "tsx", "json", "php",
          "sass", "scss", "py", "asp", "aspx", "mjs", "wasm", "jar"
        ),
        "applications" => array(
          "exe", "apk", "csh", "sh", "swf"
        ),
        "archives" => array(
          "zip", "rar", "gz", "tar", "iso", "dmg", "arc", "bz", "bz2", "7z"
        )
      );

      $image_options = array();
      $image_options["quality"] = 75;
      if (!empty($this->module)) {
        $image_options["quality"] = (
          !empty($this->module->image_crop_quality) ? $this->module->image_crop_quality 
          : (isset($this->config["image_crop_quality"]) ? $this->config["image_crop_quality"] : $image_options["quality"])
        );
      }
      $image_options["thumbnail"] = false;
      if (!empty($this->module)) {
        $image_options["thumbnail"] = (
          !empty($this->module->image_crop_thumbnail) ? $this->module->image_crop_thumbnail 
          : (isset($this->config["image_crop_thumbnail"]) ? $this->config["image_crop_thumbnail"] : $image_options["thumbnail"])
        );
      }
      $image_options["thumbnail_aspectratio"] = 75;
      if (!empty($this->module)) {
        $image_options["thumbnail_aspectratio"] = (
          !empty($this->module->image_crop_thumbnail_aspectratio) ? $this->module->image_crop_thumbnail_aspectratio 
          : (isset($this->config["image_crop_thumbnail_aspectratio"]) ? $this->config["image_crop_thumbnail_aspectratio"] : $image_options["thumbnail_aspectratio"])
        );
      }
      $image_options["thumbnail_quality"] = 75;
      if (!empty($this->module)) {
        $image_options["thumbnail_quality"] = (
          !empty($this->module->image_crop_thumbnail_quality) ? $this->module->image_crop_thumbnail_quality 
          : (isset($this->config["image_crop_thumbnail_quality"]) ? $this->config["image_crop_thumbnail_quality"] : $image_options["thumbnail_quality"])
        );
      }
      $image_options["thumbnail_width"] = 200;
      if (!empty($this->module)) {
        $image_options["thumbnail_width"] = (
          !empty($this->module->image_crop_thumbnail_width) ? $this->module->image_crop_thumbnail_width 
          : (isset($this->config["image_crop_thumbnail_width"]) ? $this->config["image_crop_thumbnail_width"] : $image_options["thumbnail_width"])
        );
      }
      $image_options["thumbnail_height"] = 200;
      if (!empty($this->module)) {
        $image_options["thumbnail_height"] = (
          !empty($this->module->image_crop_thumbnail_height) ? $this->module->image_crop_thumbnail_height 
          : (isset($this->config["image_crop_thumbnail_height"]) ? $this->config["image_crop_thumbnail_height"] : $image_options["thumbnail_height"])
        );
      }
      $image_options["large"] = false;
      if (!empty($this->module)) {
        $image_options["large"] = (
          !empty($this->module->image_crop_large) ? $this->module->image_crop_large 
          : (isset($this->config["image_crop_large"]) ? $this->config["image_crop_large"] : $image_options["large"])
        );
      }
      $image_options["large_aspectratio"] = 75;
      if (!empty($this->module)) {
        $image_options["large_aspectratio"] = (
          !empty($this->module->image_crop_large_aspectratio) ? $this->module->image_crop_large_aspectratio 
          : (isset($this->config["image_crop_large_aspectratio"]) ? $this->config["image_crop_large_aspectratio"] : $image_options["large_aspectratio"])
        );
      }
      $image_options["large_quality"] = 75;
      if (!empty($this->module)) {
        $image_options["large_quality"] = (
          !empty($this->module->image_crop_large_quality) ? $this->module->image_crop_large_quality 
          : (isset($this->config["image_crop_large_quality"]) ? $this->config["image_crop_large_quality"] : $image_options["large_quality"])
        );
      }
      $image_options["large_width"] = 200;
      if (!empty($this->module)) {
        $image_options["large_width"] = (
          !empty($this->module->image_crop_large_width) ? $this->module->image_crop_large_width 
          : (isset($this->config["image_crop_large_width"]) ? $this->config["image_crop_large_width"] : $image_options["large_width"])
        );
      }
      $image_options["large_height"] = 200;
      if (!empty($this->module)) {
        $image_options["large_height"] = (
          !empty($this->module->image_crop_large_height) ? $this->module->image_crop_large_height 
          : (isset($this->config["image_crop_large_height"]) ? $this->config["image_crop_large_height"] : $image_options["large_height"])
        );
      }

      if (!empty(
        $data_current = $this->database->select(
          ($this->module && isset($this->module->db_name) ? $this->module->db_name : ""), 
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
          $size, 
          $directory_options, 
          $image_options
        ) {
          
          $info = pathinfo($name);
          $extension = strtolower($info["extension"]);
          $basename = $info["basename"];
          $name = $info["filename"];
          $name_encrypted = gmdate("YmdHis") . "_" . $data_target->id . "_" . uniqid();
          $name_save = $name_encrypted . "." . $extension;
    
          $media_directory = "/" . trim((isset($this->config->media_path) ? $this->config->media_path : "media"), "/") . "/";
          $media_directory_path = ".." . $media_directory;

          $directory_path = "miscellaneous/";
          foreach($directory_options as $key => $value) {
            if (in_array($extension, $directory_options[$key])) {
              $directory_path = $key;
            }
          }
          if ($reference->type == "image-upload" || $reference->file_type == "image") {
            $directory_path = "images/";
          } else if ($reference->file_type == "video") {
            $directory_path = "videos/";
          } else if ($reference->file_type == "audio") {
            $directory_path = "audio/";
          } else if ($reference->file_type == "text") {
            $directory_path = "texts/";
          } else if ($reference->file_type == "font") {
            $directory_path = "fonts/";
          } else if ($reference->file_type == "model") {
            $directory_path = "models/";
          } else if ($reference->file_type == "application") {
            $directory_path = "applications/";
          }

          $category_directory_path = $media_directory_path . $directory_path;
          $upload_directory = $media_directory . $directory_path . $destination . "/";
          $upload_directory_path = $category_directory_path . $destination . "/";
          if (!file_exists($media_directory_path)) {
            mkdir($media_directory_path, 0777, true);
          }
          if (!file_exists($category_directory_path)) {
            mkdir($category_directory_path, 0777, true);
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
                $varname = $reference->varname;
                $parameter = unserialize($data_target->$varname);
                array_push($parameter, $path_save);
                $parameter = serialize($parameter);
              }
              $parameters = array(
                $reference->varname => $parameter
              );
              if (
                $this->database->update(
                  ($this->module && isset($this->module->db_name) ? $this->module->db_name : ""), 
                  $parameters, 
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
            $varname = $reference->varname;
            if (
              $reference->type == "input-file-multiple"
              || $reference->type == "input-file-multiple-drop"
            ) {
              for ($i = 0; $i < count($files[$varname]["name"]); $i++) {
                if (isset($files[$varname]) && isset($files[$varname]["name"][$i]) && !empty($files[$varname]["name"][$i])) {
                  if (
                    $upload(
                      $reference,
                      $data_current,
                      $reference->upload_folder,
                      $files[$varname]["name"][$i],
                      $files[$varname]["type"][$i],
                      $files[$varname]["tmp_name"][$i],
                      $files[$varname]["error"][$i],
                      $files[$varname]["size"][$i],
                      $directory_options,
                      $image_options
                    )
                  ) {
                    array_push($success, $files[$varname]["name"][$i]);
                  } else {
                    array_push($errors, $files[$varname]["name"][$i]);
                  }
                }
              }
            } else {
              if (isset($files[$varname]) && isset($files[$varname]["name"]) && !empty($files[$varname]["name"])) {
                if (
                  $upload(
                    $reference,
                    $data_current,
                    $reference->upload_folder,
                    $files[$varname]["name"],
                    $files[$varname]["type"],
                    $files[$varname]["tmp_name"],
                    $files[$varname]["error"],
                    $files[$varname]["size"],
                    $directory_options,
                    $image_options
                  )
                ) {
                  array_push($success, $files[$varname]["name"]);
                } else {
                  array_push($errors, $files[$varname]["name"]);
                }
              }
            }
          }
        }

        if (empty(count($errors))) {
          if ($this->callback("upload", $success)) {
            return $success;
          } else {
            return null;
          }
        } else {
          throw new Exception($this->translation->translate("Unable to upload") . " '" . implode("', '", $reference->varname) . "'", 409);
        }

      } else {
        return null;
      }

    } else {
      return null;
    }
  }

  function file($id, $parameters) {
    if ($this->validation->requires($id, "ID")) {
      if (!empty($parameters)) {
        
        if (!empty(
          $data_current = $this->database->select(
            ($this->module && isset($this->module->db_name) ? $this->module->db_name : ""), 
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
              $varname = $reference->varname;
              if (isset($parameters[$varname])) {

                if (is_array($parameters[$varname])) {
                  foreach($parameters[$varname] as $file) {
                    if ($delete($reference, $file)) {
                      array_push($success, $file);
                    } else {
                      array_push($errors, $file);
                    }
                  }
                } else {
                  if ($delete($reference, $parameters[$varname])) {
                    array_push($success, $parameters[$varname]);
                  } else {
                    array_push($errors, $parameters[$varname]);
                  }
                }

                if (count($success)) {
                  $parameter = $success[0];
                  if (
                    $reference->type == "input-file-multiple"
                    || $reference->type == "input-file-multiple-drop"
                  ) {
                    $varname = $reference->varname;
                    $parameter = unserialize($data_current->$varname);
                    for ($i = 0; $i < count($success); $i++) {
                      unset($parameter[$i]);
                    }
                    $parameter = serialize($parameter);
                  }
                  $parameters = array(
                    $reference->varname => $parameter
                  );
                  if (
                    $this->database->update(
                      ($this->module && isset($this->module->db_name) ? $this->module->db_name : ""), 
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
            if ($this->callback("file", $success)) {
              return $success;
            } else {
              return null;
            }
          } else {
            throw new Exception($this->translation->translate("Unable to delete") . " '" . implode("', '", $reference->varname) . "'", 409);
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

  function inform($parameters) {
    if (!empty($parameters)) {
      foreach($this->abstracts->references as $reference) {
        if (isset($parameters[$reference->varname])) {
          if (in_array($reference->type, $this->file_types)) {
            unset($parameters[$reference->varname]);
            $parameters[$reference->varname] = "";
          } else {
            if (in_array($reference->type, $this->serialize_types)) {
              if ($reference->type == "serialize") {
                $parameters[$reference->varname] = serialize($parameters[$reference->varname]);
              } else {
                $parameters[$reference->varname] = implode(",", $parameters[$reference->varname]);
              }
            }
          }
        }
      }
    }
    return $parameters;
  }

  function format($data) {
    if (!empty($data)) {
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
        $data->user_id_data = $this->database->select(
          "user", 
          "*", 
          array("id" => $data->user_id), 
          null, 
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
      foreach($this->abstracts->references as $reference) {
        $varname = $reference->varname;
        if (isset($data->$varname)) {
          $thumbnail_varname = $reference->varname . "_thumbnail";
          $large_varname = $reference->varname . "_large";
          $path_varname = $reference->varname . "_path";
          if (in_array($reference->type, $this->serialize_types)) {
            if ($reference->type == "serialize") {
              $data->$varname = unserialize($data->$varname);
            } else {
              $data->$varname = explode(",", $data->$varname);
            }
            if ($reference->type == "image-upload" || $reference->file_type == "image") {
              for ($i = 0; $i < count($data->$varname); $i++) {
                $data->$path_varname[$i] = $data->$varname[$i];
                if (strpos($data->$varname[$i], "http://") !== 0 || strpos($data->$varname[$i], "https://") !== 0) {
                  $data->$path_varname[$i] = $this->config["base_url"] . $data->$varname[$i];
                }
                $data->$thumbnail_varname[$i] = Utilities::get_thumbnail($data->$varname[$i]);
                $data->$large_varname[$i] = Utilities::get_large($data->$varname[$i]);
              }
            } else if (in_array($reference->type, $this->file_types)) {
              for ($i = 0; $i < count($data->$varname); $i++) {
                $data->$path_varname[$i] = $data->$varname[$i];
                if (strpos($data->$varname[$i], "http://") !== 0 || strpos($data->$varname[$i], "https://") !== 0) {
                  $data->$path_varname[$i] = $this->config["base_url"] . $data->$varname[$i];
                }
              }
            }
          } else {
            if ($reference->type == "image-upload" || $reference->file_type == "image") {
              $data->$path_varname = $data->$varname;
              if (strpos($data->$varname, "http://") !== 0 || strpos($data->$varname, "https://") !== 0) {
                $data->$path_varname = $this->config["base_url"] . $data->$varname;
              }
              $data->$thumbnail_varname = Utilities::get_thumbnail($data->$path_varname);
              $data->$large_varname = Utilities::get_large($data->$path_varname);
            } else if (in_array($reference->type, $this->file_types)) {
              $data->$path_varname = $data->$varname;
              if (strpos($data->$varname, "http://") !== 0 || strpos($data->$varname, "https://") !== 0) {
                $data->$path_varname = $this->config["base_url"] . $data->$varname;
              }
            }
          }
        }
      }
    }
    return $data;
  }

  function validate($parameters, $target_id = null, $patch = false) {
    $result = false;
    foreach($this->abstracts->references as $reference) {
      if (in_array($reference->type, $this->file_types)) {
        $result = true;
      } else {
        if (!empty($parameters)) {
          if (!empty($parameters) && ($this->validation->set($parameters, $reference->varname) || $patch)) {
            if (
              (
                empty($reference->require) 
                || $this->validation->requires($parameters[$reference->varname], $reference->label)
              ) || (
                empty($reference->validate_string_min) 
                || $this->validation->string_min($parameters[$reference->varname], $reference->label, $reference->validate_string_min)
              ) || (
                empty($reference->validate_string_max) 
                || $this->validation->string_max($parameters[$reference->varname], $reference->label, $reference->validate_string_max)
              ) || (
                empty($reference->validate_number_min) 
                || $this->validation->number_min($parameters[$reference->varname], $reference->label, $reference->validate_number_min)
              ) || (
                empty($reference->validate_number_max) 
                || $this->validation->number_max($parameters[$reference->varname], $reference->label, $reference->validate_number_max)
              ) || (
                empty($reference->validate_date_min) 
                || $this->validation->date_min($parameters[$reference->varname], $reference->label, $reference->validate_date_min)
              ) || (
                empty($reference->validate_date_max) 
                || $this->validation->date_max($parameters[$reference->varname], $reference->label, $reference->validate_date_max)
              ) || (
                empty($reference->validate_datetime_min) 
                || $this->validation->datetime_min($parameters[$reference->varname], $reference->label, $reference->validate_datetime_min)
              ) || (
                empty($reference->validate_datetime_max) 
                || $this->validation->datetime_max($parameters[$reference->varname], $reference->label, $reference->validate_datetime_max)
              ) || (
                empty($reference->validate_password_equal_to) 
                || $this->validation->password_equal_to($parameters[$reference->varname], $reference->label, $parameters[$reference->validate_password_equal_to])
              ) || (
                empty($reference->validate_email) 
                || $this->validation->email($parameters[$reference->varname], $reference->label)
              ) || (
                empty($reference->validate_password) 
                || $this->validation->password($parameters[$reference->varname], $reference->label)
              ) || (
                empty($reference->validate_website) 
                || $this->validation->website($parameters[$reference->varname], $reference->label)
              ) || (
                empty($reference->validate_no_space) 
                || $this->validation->no_spaces($parameters[$reference->varname], $reference->label)
              ) || (
                empty($reference->validate_no_specialchar_soft) 
                || $this->validation->no_special_characters($parameters[$reference->varname], $reference->label, $reference->validate_no_specialchar_soft)
              ) || (
                empty($reference->validate_no_specialchar_hard) 
                || $this->validation->no_special_characters($parameters[$reference->varname], $reference->label, $reference->validate_no_specialchar_hard, "strict")
              ) || (
                empty($reference->validate_upper) 
                || $this->validation->uppercase_only($parameters[$reference->varname], $reference->label)
              ) || (
                empty($reference->validate_lower)
                 || $this->validation->lowercase_only($parameters[$reference->varname], $reference->label)
              ) || (
                empty($reference->validate_number) 
                || $this->validation->number($parameters[$reference->varname], $reference->label)
              ) || (
                empty($reference->validate_digit) 
                || $this->validation->decimal($parameters[$reference->varname], $reference->label)
              ) || (
                empty($reference->validate_unique) 
                || $this->validation->unique($parameters[$reference->varname], $reference->label, $reference->varname, $this->module->db_name, $reference->validate_unique, $target_id)
              ) || (
                empty($reference->prefix) 
                || $this->validation->contains_prefix($parameters[$reference->varname], $reference->label, $reference->prefix)
              ) || (
                empty($reference->suffix) 
                || $this->validation->contains_suffix($parameters[$reference->varname], $reference->label, $reference->suffix)
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

  private function callback($function, $parameters) {
    $namespace = "\\Abstracts\\Callback\\" . $this->class;
    if (class_exists($namespace)) {
      if (method_exists($namespace, $function)) {
        $callback = new $namespace($this->config, $this->session, $this->controls);
        try {
          $callback->$function($parameters);
          return true;
        } catch(Exception $e) {
          throw new Exception($e->getMessage() . " ". $this->translation->translate("on callback"), $e->getCode());
          return false;
        }
      } else {
        return true;
      }
    } else {
      return true;
    }
  }

}