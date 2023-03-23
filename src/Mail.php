<?php
namespace Abstracts;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Database;
use \Abstracts\Helpers\Validation;
use \Abstracts\Helpers\Translation;
use \Abstracts\Helpers\Utilities;

use \Abstracts\API;
use \Abstracts\Log;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

use Exception;

class Mail {

  /* configuration */
  public $id = "18";
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
      if ($function == "send") {
        $result = $this->$function(
          (isset($parameters["sender_mail"]) ? $parameters["sender_mail"] : null),
          (isset($parameters["sender_name"]) ? $parameters["sender_name"] : null),
          (isset($parameters["recipients"]) ? $parameters["recipients"] : null),
          (isset($parameters["ccs"]) ? $parameters["ccs"] : null),
          (isset($parameters["bccs"]) ? $parameters["bccs"] : null),
          (isset($parameters["subject"]) ? $parameters["subject"] : null),
          (isset($parameters["body"]) ? $parameters["body"] : null),
          (isset($parameters["body_is_html"]) ? $parameters["body_is_html"] : false),
          (isset($parameters["smpt"]) ? $parameters["smpt"] : null),
          (isset($parameters["save"]) ? $parameters["save"] : false),
          $_FILES
        );
      } else if ($function == "template") {
        $result = $this->$function(
          (isset($parameters["file"]) ? $parameters["file"] : null)
        );
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
      } else {
        throw new Exception($this->translation->translate("Function not supported"), 421);
      }
    }
    return $result;
  }
  
  function send(
    $sender_mail, 
    $sender_name, 
    $recipients, 
    $ccs = "", 
    $bccs = "", 
    $subject, 
    $body, 
    $body_is_html = true, 
    $smpt = null,
    $save = false,
    $attachments = ""
  ) {

    if (empty($body)) {
      $body = "";
    }
    if (empty($sender_name)) {
      $sender_name = "";
    }

    if (
      $this->validation->require($recipients, "Recipients")
      && $this->validation->require($sender_mail, "Sender Mail")
      && $this->validation->require($subject, "Subject")
    ) {
    
      try {
      
        $mail = new PHPMailer();
    
        if (!empty($smpt)) {
    
          $mail->isSMTP();
          //Enable SMTP debugging
          // 0 = off (for production use)
          // 1 = client messages
          // 2 = client and server messages
          $mail->SMTPDebug = 0;
          //Ask for HTML-friendly debug output
          $mail->Debugoutput = '';
          //Set the hostname of the mail server
          $mail->Host = $smpt["host"] || ($this->config["smtp_host"] || null);
          //Set the SMTP port number - likely to be 25, 465 or 587
          $mail->Port = $smpt["port"] || ($this->config["smtp_port"] || null);
          //Set the encryption system to use - ssl (deprecated) or tls
          $mail->SMTPSecure = $smpt["secure"] || ($this->config["smtp_secure"] || null);
    
          //Whether to use SMTP authentication
          $mail->SMTPAuth = $smpt["auth"] || ($this->config["smtp_auth"] || false);

          if ($smpt["auth"] || ($this->config["smtp_auth"] || false)) {
            //Username to use for SMTP authentication
            $mail->Username = $smpt["username"] || ($this->config["smtp_username"] || "");
            //Password to use for SMTP authentication
            $mail->Password = $smpt["password"] || ($this->config["smtp_password"] || "");
          }
          
          $mail->SMTPOptions = array(
            'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
            )
          );
    
        }

        if (!empty($save)) {
          if (!empty($files)) {
            // try {
            //   $this->upload($data->id, $files);
            //   $mail->AddAttachment($attachments, basename($attachments));
            // } catch (Exception $e) {
            //   $error = true;
            // }
          }
        }
    
        $mail->CharSet = $this->config["encoding"] || "UTF-8";
        $mail->From = $sender_mail;
        $mail->FromName = $sender_name;
        $mail->Subject = $subject;
        if (!is_array($recipients)) {
          $recipients = explode(",", $recipients);
        }
        foreach ($recipients as $recipient) {
          $mail->AddAddress($recipient);
        }
        if (!empty($ccs)) {
          if (!is_array($ccs)) {
            $ccs = explode(",", $ccs);
          }
          foreach ($ccs as $cc) {
            $mail->addCC($cc);
          }
        }
        if (!empty($bccs)) {
          if (!is_array($bccs)) {
            $bccs = explode(",", $bccs);
          }
          foreach ($bccs as $bcc) {
            $mail->addBCC($bcc);
          }
        }
        if (!empty($body_is_html)) {
          $mail->IsHTML(true); 
        } else {
          $body = stripslashes(str_replace("\r\n", "\n", $body));
          $body = preg_replace("/[\n\t]+/", "", $body);
        }
        $mail->Body = $body;
        
        $result = $mail->Send();
        if (!empty($save)) {

        }

        return $result;
        
      } catch (PHPMailerException $e) {
        throw new Exception($this->translation->translate($e->getMessage()), $e->getCode());
      }
      
    }
    
  }

  function template($file_name = null) {
    if (!empty($file_name)) {
      $template_file = $this->config["template_path"] . "mails/" . $file_name;
      if (file_exists($template_file) && !is_dir($template_file)) {
        $content = file_get_contents(
          $template_file, 
          false, 
          stream_context_create(
            array(
              "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
              )
            )
          )
        );
        return $content;
      } else {
        return false;
      }
    } else {
      return false;
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
        "mail", 
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
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data, $return_references),
          $this->session,
          $this->controls,
          $this->id
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
        "mail", 
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
          $this->format($list, $return_references),
          $this->session,
          $this->controls,
          $this->id
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
          "mail", 
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
        "mail", 
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
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data),
          $this->session,
          $this->controls,
          $this->id
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
        "mail", 
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
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data),
          $this->session,
          $this->controls,
          $this->id
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
        "mail", 
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
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data),
          $this->session,
          $this->controls,
          $this->id
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
        "mail", 
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
        return Utilities::callback(
          __METHOD__, 
          func_get_args(), 
          $this->format($data),
          $this->session,
          $this->controls,
          $this->id
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
                (isset($this->config["image_quality"]) ? $this->config["image_quality"] : 75)
              ),
              "thumbnail" => (
                (isset($this->config["image_thumbnail"]) ? $this->config["image_thumbnail"] : true)
              ),
              "thumbnail_aspectratio" => (
                (isset($this->config["image_thumbnail_aspectratio"]) ? $this->config["image_thumbnail_aspectratio"] : 75)
              ),
              "thumbnail_quality" => (
                (isset($this->config["image_thumbnail_quality"]) ? $this->config["image_thumbnail_quality"] : 75)
              ),
              "thumbnail_width" => (
                (isset($this->config["image_thumbnail_width"]) ? $this->config["image_thumbnail_width"] : 200)
              ),
              "thumbnail_height" => (
                (isset($this->config["image_thumbnail_height"]) ? $this->config["image_thumbnail_height"] : 200)
              ),
              "large" => (
                (isset($this->config["image_large"]) ? $this->config["image_large"] : true)
              ),
              "large_aspectratio" => (
                (isset($this->config["image_large_aspectratio"]) ? $this->config["image_large_aspectratio"] : 75)
              ),
              "large_quality" => (
                (isset($this->config["image_large_quality"]) ? $this->config["image_large_quality"] : 75)
              ),
              "large_width" => (
                (isset($this->config["image_large_width"]) ? $this->config["image_large_width"] : 400)
              ),
              "large_height" => (
                (isset($this->config["image_large_height"]) ? $this->config["image_large_height"] : 400)
              )
            );
            
            $info = pathinfo($name);
            $extension = strtolower(isset($info["extension"]) ? $info["extension"] : null);
            if (empty($extension)) {
              if (strpos($type, "mail/") === 0) {
                $extension = str_replace("mail/", "", $type);
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
              try {
                $upload_result = Utilities::create_image(
                  $tmp_name, 
                  $destination, 
                  true,
                  640, 
                  640, 
                  null, 
                  $image_options["quality"]
                );
              } catch(Exception $e) {
                if ($e->getCode() == 415 || $e->getCode() == 500) {
                  $upload_result = move_uploaded_file($tmp_name, $destination);
                  $unsupported_image = true;
                }
              }
              if ($upload_result) {
                
                $parameter = $path;
                $parameters = array(
                  "attachments" => $parameter
                );
                if (empty($input_multiple)) {
                  $update_data = $this->database->update(
                    (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : "mail"), 
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
    
          if (isset($files["attachments"]) && isset($files["attachments"]["name"])) {
            if (isset($data_current->attachments) && !empty($data_current->attachments)) {
              try {
                $this->remove($data_current->id, array("attachments" => $data_current->attachments));
              } catch (Exception $e) {
                
              };
            }
            if (
              $path_id = $upload(
                $data_current,
                "mail",
                $files["attachments"]["name"],
                $files["attachments"]["type"],
                $files["attachments"]["tmp_name"],
                $files["attachments"]["error"],
                $files["attachments"]["size"]
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
                "source" => $files["attachments"]["name"],
                "destination" => $path_id
              ));
            } else {
              array_push($errors, $files["attachments"]["name"]);
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
                $this->id
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

          $delete = function($file) {
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
              return true;
            } catch(Exception $e) {
              return false;
            }
          };

          $successes = array();
          $errors = array();

          if (isset($parameters["attachments"])) {

            if (is_array($parameters["attachments"])) {
              foreach ($parameters["attachments"] as $file) {
                if ($delete($file)) {
                  array_push($successes, $file);
                } else {
                  array_push($errors, $file);
                }
              }
            } else {
              if ($delete($parameters["attachments"])) {
                array_push($successes, $parameters["attachments"]);
              } else {
                array_push($errors, $parameters["attachments"]);
              }
            }

            if (count($successes)) {
              $parameter = "";
              $parameters = array(
                "attachments" => $parameter
              );
              $this->database->update(
                (!empty($this->module) && isset($this->module->database_table) ? $this->module->database_table : "mail"), 
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

          if (empty($errors)) {
            return Utilities::callback(
              __METHOD__, 
              func_get_args(), 
              $successes,
              $this->session,
              $this->controls,
              $this->id
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

  function inform($parameters, $update = false, $user_id = 0) {
    if (!empty($parameters)) {
      if (empty($update)) {
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

  function format($data, $return_references = false) {

    /* function: create referers before format (better performance for list) */
    $refer = function ($return_references = false, $abstracts_override = null) {

      $data = array();
    
      if (!empty($return_references)) {
        if (Utilities::in_references("user_id", $return_references)) {
          $data["user_id"] = new User($this->session, Utilities::override_controls(true, true, true, true));
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
  
        if (Utilities::in_referers("user_id", $referers)) {
          $data->user_id_reference = $referers["user_id"]->format(
            $this->database->get_reference(
              $data->user_id,
              "user",
              "id"
            )
          );
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
      $this->id
    );

  }

  function validate($parameters, $target_id = null, $patch = false) {
    if (!empty($parameters)) {
      return true;
    } else {
      throw new Exception($this->translation->translate("Bad request"), 400);
    }
  }

}