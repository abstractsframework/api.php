<?php
namespace Abstracts\Helpers;

use \Abstracts\Helpers\Initialize;
use \Abstracts\Helpers\Translation;

use Exception;

class Database {

  public static $comparisons = array(
    "=", 
    "!=", 
    ">", 
    "<", 
    ">=", 
    "<=", 
    "<>", 
    "LIKE", 
    "NOT LIKE", 
    "BETWEEN", 
    "NOT BETWEEN"
  );

  /* core */
  private $config = null;
  private $session = null;
  private $controls = array(
    "view" => false,
    "create" => false,
    "update" => false,
    "delete" => false,
  );

  /* helpers */
  private $translation = null;

  function __construct(
    $session = null,
    $controls = null,
    $credential = null
  ) {

    /* initialize: core */
    $this->config = Initialize::config();

    if (!empty($credential)) {
      if (isset($credential["host"])) {
        $this->config["database_host"] = $credential["host"];
      }
      if (isset($credential["name"])) {
        $this->config["database_name"] = $credential["name"];
      }
      if (isset($credential["login"])) {
        $this->config["database_login"] = $credential["login"];
      }
      if (isset($credential["password"])) {
        $this->config["database_password"] = $credential["password"];
      }
      if (isset($credential["encoding"])) {
        $this->config["database_encoding"] = $credential["encoding"];
      }
    }

    $this->session = $session;
    if (isset($controls) && !empty($controls)) {
      if (isset($controls["view"])) {
        $this->controls["view"] = $controls["view"];
      }
      if (isset($controls["create"])) {
        $this->controls["create"] = $controls["create"];
      }
      if (isset($controls["update"])) {
        $this->controls["update"] = $controls["update"];
      }
      if (isset($controls["delete"])) {
        $this->controls["delete"] = $controls["delete"];
      }
    }

    /* initialize: helpers */
    $this->translation = new Translation();

  }

  function connect($credential = null, $charset = null) {

    $host = $this->config["database_host"];
    $name = $this->config["database_name"];
    $login = $this->config["database_login"];
    $password = $this->config["database_password"];
    $encoding = $this->config["database_encoding"];
    if (!empty($credential)) {
      if (isset($credential["host"])) {
        $host = $credential["host"];
      }
      if (isset($credential["name"])) {
        $name = $credential["name"];
      }
      if (isset($credential["login"])) {
        $login = $credential["login"];
      }
      if (isset($credential["password"])) {
        $password = $credential["password"];
      }
      if (isset($credential["encoding"])) {
        $encoding = $credential["encoding"];
      }
    }
    
    $connection = mysqli_connect($host, $login, $password, $name);
    if ($connection) {
      if (!mysqli_select_db($connection, $name)) {
        $connection = false;
      } else {
        if ($charset == null) {
          $charset = strtolower($encoding);
        }
        if (!mysqli_set_charset($connection, str_replace("-", "", $charset))) {
          $connection = false;
        }
      }
    } else {
      $connection = false;
    }
    
    return $connection;
    
  }

  function disconnect($connection) {
    if (isset($connection) && !empty($connection)) {
      mysqli_close($connection);
    }
  }

  function select(
    $table, 
    $keys = "*", 
    $filters = array(), 
    $extensions = array(),
    $controls_view = false,
    $clean_keys = array(),
    $fetch_type = "assoc"
  ) {
    
    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $fetch_type = !empty($fetch_type) ? $fetch_type : "assoc";
    $controls_view = !empty($controls_view) ? $controls_view : false;
    $controls = $this->controls["view"];
    if ($controls_view || !empty($controls_view) || (is_array($controls_view) && count($controls_view))) {
      $controls = $controls_view;
    }
    if ($clean_keys !== false) {
      $clean_keys = is_array($clean_keys) ? $clean_keys : array();
      if (!empty($filters) || !empty($extensions) || !empty($controls)) {
        if (empty($clean_keys)) {
          $columns = $this->columns($table);
          $clean_keys = array_map(function($value) { return $value["COLUMN_NAME"]; }, $columns);
        }
        if (!empty($filters)) {
          $filters = $this->clean_filters($filters, $clean_keys);
        }
        if (!empty($extensions)) {
          $extensions = $this->clean_extensions($extensions, $clean_keys);
        }
        if (!empty($controls)) {
          $controls = $this->clean_controls($controls, $clean_keys);
        }
      }
    }
    
    $data = null;
    
    if (!empty($controls)) {

      $connection = $this->connect();
      if ($connection) {

        $error = false;
      
        $conditions = $this->condition(
          $this->escape_string($filters, $connection), 
          $this->escape_string($extensions, $connection), 
          $this->escape_string($controls, $connection),
          $table
        );

        $keys = $this->keys($keys, $table);

        $query = "SELECT " . $keys . " FROM `" . $table . "` " . $conditions . " LIMIT 1;";
        
        if ($result = mysqli_query($connection, $query)) {
          if ($fetch_type == "assoc") {
            if ($row = $result->fetch_assoc()) {
              $data = (object) $row;
            } else {
              $data = null;
            }
          } else {
            if ($row = $result->fetch_array()) {
              $data = (object) $row;
            } else {
              $data = null;
            }
          }
          mysqli_free_result($result);
        } else {
          $error = true;
        }

        $this->disconnect($connection);
  
        if ($error) {
          throw new Exception($this->translation->translate("Database encountered error"), 409);
        }

      } else {
        throw new Exception($this->translation->translate("Unable to connect to database"), 500);
      }

    } else {
      throw new Exception($this->translation->translate("Permission denied"), 403);
    }
    
    return $data;

  }

  function select_multiple(
    $table, 
    $keys = "*", 
    $filters = array(), 
    $extensions = array(), 
    $start = null, 
    $limit = null, 
    $sort_by = null, 
    $sort_direction = null,
    $controls_view = false,
    $clean_keys = array(),
    $fetch_type = "assoc"
  ) {
    
    $start = !empty($start) ? $start : null;
    $limit = !empty($limit) ? $limit : null;
    $sort_by = !empty($sort_by) ? $sort_by : "id";
    $sort_direction = !empty($sort_direction) ? $sort_direction : "desc";
    $active = !empty($active) ? $active : null;
    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $fetch_type = !empty($fetch_type) ? $fetch_type : "assoc";
    $controls_view = !empty($controls_view) ? $controls_view : false;
    $controls = $this->controls["view"];
    if ($controls_view || !empty($controls_view) || (is_array($controls_view) && count($controls_view))) {
      $controls = $controls_view;
    }
    if ($clean_keys !== false) {
      $clean_keys = is_array($clean_keys) ? $clean_keys : array();
      if (!empty($filters) || !empty($extensions) || !empty($controls)) {
        if (empty($clean_keys)) {
          $columns = $this->columns($table);
          $clean_keys = array_map(function($value) { return $value["COLUMN_NAME"]; }, $columns);
        }
        if (!empty($filters)) {
          $filters = $this->clean_filters($filters, $clean_keys);
        }
        if (!empty($extensions)) {
          $extensions = $this->clean_extensions($extensions, $clean_keys);
        }
        if (!empty($controls)) {
          $controls = $this->clean_controls($controls, $clean_keys);
        }
      }
    }
    
    $data = null;
    
    if (!empty($controls)) {

      $connection = $this->connect();
      if ($connection) {
        
        $error = false;

        $start = $this->escape_string($start, $connection);
        $limit = $this->escape_string($limit, $connection);
        $sort_by = $this->escape_string($sort_by, $connection);
        $sort_direction = $this->escape_string($sort_direction, $connection);

        $conditions = $this->condition(
          $this->escape_string($filters, $connection), 
          $this->escape_string($extensions, $connection), 
          $this->escape_string($controls, $connection),
          $table
        );

        $keys = $this->keys($keys, $table);

        $query = 
        "SELECT " . $keys . " FROM `" . $table . "` " . $conditions . " " 
        . $this->order($sort_by, $sort_direction) . " " . $this->limit($start, $limit) . ";";
        if ($result = mysqli_query($connection, $query)) {
          $rows = array();
          if ($fetch_type == "assoc") {
            while($row = $result->fetch_assoc()) {
              array_push($rows, (object) $row);
            }
          } else {
            while($row = $result->fetch_array()) {
              array_push($rows, (object) $row);
            }
          }
          $data = $rows;
          mysqli_free_result($result);
        } else {
          $error = true;
        }

        $this->disconnect($connection);

        if ($error) {
          throw new Exception($this->translation->translate("Database encountered error"), 409);
        }

      } else {
        throw new Exception($this->translation->translate("Unable to connect to database"), 500);
      }

    } else {
      throw new Exception($this->translation->translate("Permission denied"), 403);
    }
    
    return $data;

  }

  function count(
    $table,
    $filters = array(), 
    $extensions = array(), 
    $start = null,
    $limit = null,
    $controls_view = false,
    $clean_keys = array()
  ) {
    
    $start = !empty($start) ? $start : null;
    $limit = !empty($limit) ? $limit : null;
    $active = !empty($active) ? $active : null;
    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $fetch_type = !empty($fetch_type) ? $fetch_type : "assoc";
    $controls_view = !empty($controls_view) ? $controls_view : false;
    $controls = $this->controls["view"];
    if ($controls_view || !empty($controls_view) || (is_array($controls_view) && count($controls_view))) {
      $controls = $controls_view;
    }
    if ($clean_keys !== false) {
      $clean_keys = is_array($clean_keys) ? $clean_keys : array();
      if (!empty($filters) || !empty($extensions) || !empty($controls)) {
        if (empty($clean_keys)) {
          $columns = $this->columns($table);
          $clean_keys = array_map(function($value) { return $value["COLUMN_NAME"]; }, $columns);
        }
        if (!empty($filters)) {
          $filters = $this->clean_filters($filters, $clean_keys);
        }
        if (!empty($extensions)) {
          $extensions = $this->clean_extensions($extensions, $clean_keys);
        }
        if (!empty($controls)) {
          $controls = $this->clean_controls($controls, $clean_keys);
        }
      }
    }
    
    $data = 0;

    if (!empty($controls)) {

      $connection = $this->connect();
      if ($connection) {
        
        $error = false;

        $start = $this->escape_string($start, $connection);
        $limit = $this->escape_string($limit, $connection);

        $conditions = $this->condition(
          $this->escape_string($filters, $connection), 
          $this->escape_string($extensions, $connection), 
          $this->escape_string($controls, $connection)
        );
        
        $query = "SELECT NULL FROM `" . $table . "` " . $conditions . " " . $this->limit($start, $limit) . ";";
        if (empty(trim($conditions))) {
          $query = "EXPLAIN SELECT NULL FROM `" . $table . "` " . $this->limit($start, $limit) . ";";
        }

        if ($result = mysqli_query($connection, $query)) {
          if (!empty(trim($conditions))) {
            $data = mysqli_num_rows($result);
          } else {
            $data = 0;
            $row = mysqli_fetch_assoc($result);
            if (isset($row["rows"]) && !empty($row["rows"])) {
              $data = (int)($row["rows"]);
            }
          }
          mysqli_free_result($result);
        } else {
          $error = true;
        }

        $this->disconnect($connection);
        
        if ($error) {
          throw new Exception($this->translation->translate("Database encountered error"), 409);
        }

      } else {
        throw new Exception($this->translation->translate("Unable to connect to database"), 500);
      }

    } else {
      throw new Exception($this->translation->translate("Permission denied"), 403);
    }
    
    return $data;

  }

  function insert(
    $table, 
    $parameters,
    $controls_create = false,
    $clean_keys = array()
  ) {

    $controls_create = !empty($controls_create) ? $controls_create : false;
    $controls = $this->controls["create"];
    if ($controls_create || !empty($controls_create) || (is_array($controls_create) && count($controls_create))) {
      $controls = $controls_create;
    }
    if ($clean_keys !== false) {
      $clean_keys = is_array($clean_keys) ? $clean_keys : array();
      if (empty($clean_keys)) {
        $columns = $this->columns($table);
        $clean_keys = array_map(function($value) { return $value["COLUMN_NAME"]; }, $columns);
      }
      if (!empty($controls)) {
        $controls = $this->clean_controls($controls, $clean_keys);
      }
      $parameters = $this->clean_parameters($parameters, $clean_keys);
    }
   
    $data = null;

    if (!empty($parameters)) {
      if (!empty($controls)) {

        if ($this->validate_parameters($controls, $parameters)) {

          $connection = $this->connect();
          if ($connection) {
            
            $error = false;

            $parameters = $this->escape_string($parameters, $connection);
            
            $keys = array();
            $values = array();
            foreach ($parameters as $key => $value) {
              array_push($keys, "`" . $key . "`");
              if (is_string($value) || $value === "") {
                $value = "'" . $value . "'";
              } else if (is_null($value)) {
                $value = "NULL";
              } else if (is_bool($value)) {
                $value = (!empty($value) ? "true" : "false");
              }
              array_push($values, $value);
            }
            
            $query = "INSERT INTO `" . $table . "` (" . implode(", ", $keys) . ") VALUES (" . implode(", ", $values) . ");";
    
            if (mysqli_query($connection, $query)) {
              $id = mysqli_insert_id($connection);
              $data = $this->select($table, "*", array("id" => $id), null, true, $clean_keys);
            } else {
              $error = true;
            }
    
            $this->disconnect($connection);

            if ($error) {
              throw new Exception($this->translation->translate("Database encountered error"), 409);
            }
    
          } else {
            throw new Exception($this->translation->translate("Unable to connect to database"), 500);
          }

        } else {
          throw new Exception($this->translation->translate("Values not allowed"), 403);
        }

      } else {
        throw new Exception($this->translation->translate("Permission denied"), 403);
      }
    } else {
      throw new Exception($this->translation->translate("Empty parameter"), 400);
    }
      
    return $data;

  }

  function insert_multiple(
    $table, 
    $multiple_parameters,
    $controls_create = false,
    $clean_keys = array()
  ) {

    $controls_create = !empty($controls_create) ? $controls_create : false;
    $controls = $this->controls["create"];
    if ($controls_create || !empty($controls_create) || (is_array($controls_create) && count($controls_create))) {
      $controls = $controls_create;
    }
    if ($clean_keys !== false) {
      $clean_keys = is_array($clean_keys) ? $clean_keys : array();
      if (empty($clean_keys)) {
        $columns = $this->columns($table);
        $clean_keys = array_map(function($value) { return $value["COLUMN_NAME"]; }, $columns);
      }
      if (!empty($controls)) {
        $controls = $this->clean_controls($controls, $clean_keys);
      }
      for ($i = 0; $i < count($multiple_parameters); $i++) {
        $multiple_parameters[$i] = $this->clean_parameters($multiple_parameters[$i], $clean_keys);
      }
    }
   
    $data = array();

    if (!empty($multiple_parameters)) {

      if (!empty($controls)) {
  
        $error = false;
  
        foreach ($multiple_parameters as $parameters) {
          if (!$this->validate_parameters($controls, $parameters)) {
            $error = true;
          }
        }
  
        if (!$error) {

          $errors = array();
  
          $connection = $this->connect();
          if ($connection) {
  
            $multiple_parameters = $this->escape_string($multiple_parameters, $connection);
    
            foreach ($multiple_parameters as $parameters) {
    
              $keys = array();
              $values = array();
              foreach ($parameters as $key => $value) {
                array_push($keys, "`" . $key . "`");
                if (is_string($value) || $value === "") {
                  $value = "'" . $value . "'";
                } else if (is_null($value)) {
                  $value = "NULL";
                } else if (is_bool($value)) {
                  $value = (!empty($value) ? "true" : "false");
                }
                array_push($values, $value);
              }

              $query = "INSERT INTO `" . $table . "` (" . implode(", ", $keys) . ") VALUES (" . implode(", ", $values) . ")";
              if (mysqli_query($connection, $query)) {
                $id = mysqli_insert_id($connection);
                array_push($data, $this->select($table, "*", array("id" => $id), null, true, $clean_keys));
              } else {
                array_push($errors, $parameters);
              }
    
            }

            $this->disconnect($connection);
  
            if (!empty($errors)) {
              throw new Exception($this->translation->translate("Database encountered error"), 409);
            }

          } else {
            throw new Exception($this->translation->translate("Unable to connect to database"), 500);
          }
  
        } else {
          throw new Exception($this->translation->translate("Values not allowed"), 403);
        }
  
      } else {
        throw new Exception($this->translation->translate("Permission denied"), 403);
      }
    } else {
      throw new Exception($this->translation->translate("Empty parameter"), 400);
    }
      
    return $data;

  }

  function update(
    $table, 
    $parameters,
    $filters = array(), 
    $extensions = array(), 
    $controls_update = false,
    $clean_keys = array()
  ) {

    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $controls_update = !empty($controls_update) ? $controls_update : false;
    $controls = $this->controls["update"];
    if ($controls_update || !empty($controls_update) || (is_array($controls_update) && count($controls_update))) {
      $controls = $controls_update;
    }
    if ($clean_keys !== false) {
      $clean_keys = is_array($clean_keys) ? $clean_keys : array();
      if (empty($clean_keys)) {
        $columns = $this->columns($table);
        $clean_keys = array_map(function($value) { return $value["COLUMN_NAME"]; }, $columns);
      }
      if (!empty($filters)) {
        $filters = $this->clean_filters($filters, $clean_keys);
      }
      if (!empty($extensions)) {
        $extensions = $this->clean_extensions($extensions, $clean_keys);
      }
      if (!empty($controls)) {
        $controls = $this->clean_controls($controls, $clean_keys);
      }
      $parameters = $this->clean_parameters($parameters, $clean_keys);
    }
    
    $data = null;
    
    if (!empty($parameters)) {
      if (!empty($controls)) {

        if ($this->validate_parameters($controls, $parameters)) {

          $connection = $this->connect();
          if ($connection) {
            
            $error = false;

            $parameters = $this->escape_string($parameters, $connection);

            $conditions = $this->condition(
              $this->escape_string($filters, $connection), 
              $this->escape_string($extensions, $connection), 
              $this->escape_string($controls, $connection)
            );

            $sets = array();
            foreach ($parameters as $key => $value) {
              if (is_string($value) || $value === "") {
                $value = "'" . $value . "'";
              } else if (is_null($value)) {
                $value = "NULL";
              } else if (is_bool($value)) {
                $value = (!empty($value) ? "true" : "false");
              }
              array_push($sets, "`" . $key . "` = " . $value);
            }
            
            // $data_current = $this->select_multiple($table, "*", $filters, $extensions, null, null, null, null, true, $clean_keys);
            
            $query = "UPDATE `" . $table . "` SET " . implode(", ", $sets) . " " . $conditions . ";";

            if (mysqli_query($connection, $query)) {
              $data = $this->select_multiple($table, "*", $filters, $extensions, $controls, null, null, null, true, $clean_keys);
            } else {
              $error = true;
            }

            $this->disconnect($connection);
            
            if ($error) {
              throw new Exception($this->translation->translate("Database encountered error"), 409);
            }

          } else {
            throw new Exception($this->translation->translate("Unable to connect to database"), 500);
          }

        } else {
          throw new Exception($this->translation->translate("Values not allowed"), 403);
        }

      } else {
        throw new Exception($this->translation->translate("Permission denied"), 403);
      }
    } else {
      throw new Exception($this->translation->translate("Empty parameter"), 400);
    }
    
    return $data;

  }

  function update_multiple(
    $table, 
    $parameters,
    $multiple_filters = array(), 
    $extensions = array(), 
    $controls_update = false,
    $clean_keys = array()
  ) {

    $multiple_filters = is_array($multiple_filters) ? $multiple_filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $controls_update = !empty($controls_update) ? $controls_update : false;
    $controls = $this->controls["update"];
    if ($controls_update || !empty($controls_update) || (is_array($controls_update) && count($controls_update))) {
      $controls = $controls_update;
    }
    if ($clean_keys !== false) {
      $clean_keys = is_array($clean_keys) ? $clean_keys : array();
      if (empty($clean_keys)) {
        $columns = $this->columns($table);
        $clean_keys = array_map(function($value) { return $value["COLUMN_NAME"]; }, $columns);
      }
      if (!empty($extensions)) {
        $extensions = $this->clean_extensions($extensions, $clean_keys);
      }
      if (!empty($controls)) {
        $controls = $this->clean_controls($controls, $clean_keys);
      }
      for ($i = 0; $i < count($multiple_filters); $i++) {
        $multiple_filters[$i] = $this->clean_filters($multiple_filters[$i], $clean_keys);
      }
      $parameters = $this->clean_parameters($parameters, $clean_keys);
    }
    
    $data = array();

    if (!empty($parameters)) {
      if (!empty($controls)) {

        if ($this->validate_parameters($controls, $parameters)) {

          // $data_current = array();

          $queries = array();

          $connection = $this->connect();
          if ($connection) {

            $error = false;

            $parameters = $this->escape_string($multiple_filters, $parameters);
            $multiple_filters = $this->escape_string($multiple_filters, $connection);

            foreach ($multiple_filters as $filters) {
        
              $conditions = $this->condition(
                $this->escape_string($filters, $connection), 
                $this->escape_string($extensions, $connection), 
                $this->escape_string($controls, $connection)
              );

              $sets = array();
              foreach ($parameters as $key => $value) {
                if (is_string($value) || $value === "") {
                  $value = "'" . $value . "'";
                } else if (is_null($value)) {
                  $value = "NULL";
                } else if (is_bool($value)) {
                  $value = (!empty($value) ? "true" : "false");
                }
                array_push($sets, "`" . $key . "` = " . $value);
              }

              // array_push($data_current, $this->select_multiple($table, "*", $filters, $extensions, null, null, null, null, true, $clean_keys));
              
              array_push($queries, "UPDATE `" . $table . "` SET " . implode(", ", $sets) . " " . $conditions);

            }
          
            if (mysqli_multi_query($connection, (implode(";", $queries) . ";"))) {
              foreach ($multiple_filters as $filters) {
                array_push($data, $this->select_multiple($table, "*", $filters, $extensions, $controls, null, null, null, true, $clean_keys));
              }
            } else {
              $error = true;
            }

            $this->disconnect($connection);

            if ($error) {
              throw new Exception($this->translation->translate("Database encountered error"), 409);
            }

          } else {
            throw new Exception($this->translation->translate("Unable to connect to database"), 500);
          }

        } else {
          throw new Exception($this->translation->translate("Values not allowed"), 403);
        }

      } else {
        throw new Exception($this->translation->translate("Permission denied"), 403);
      }
    } else {
      throw new Exception($this->translation->translate("Empty parameter"), 400);
    }
    
    return $data;

  }

  function delete(
    $table, 
    $filters = array(), 
    $extensions = array(), 
    $controls_delete = false,
    $clean_keys = array()
  ) {

    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $controls_delete = !empty($controls_delete) ? $controls_delete : false;
    $controls = $this->controls["delete"];
    if ($controls_delete || !empty($controls_delete) || (is_array($controls_delete) && count($controls_delete))) {
      $controls = $controls_delete;
    }
    if ($clean_keys !== false) {
      $clean_keys = is_array($clean_keys) ? $clean_keys : array();
      if (empty($clean_keys)) {
        $columns = $this->columns($table);
        $clean_keys = array_map(function($value) { return $value["COLUMN_NAME"]; }, $columns);
      }
      if (!empty($filters)) {
        $filters = $this->clean_filters($filters, $clean_keys);
      }
      if (!empty($extensions)) {
        $extensions = $this->clean_extensions($extensions, $clean_keys);
      }
      if (!empty($controls)) {
        $controls = $this->clean_controls($controls, $clean_keys);
      }
    }
    
    $data = null;

    if (!empty($controls)) {

      $data_current = $this->select_multiple($table, "*", $filters, $extensions, null, null, null, null, true, $clean_keys);
      if (!empty($data_current)) {

        $connection = $this->connect();
        if ($connection) {
    
          $error = false;
  
          $conditions = $this->condition(
            $this->escape_string($filters, $connection), 
            $this->escape_string($extensions, $connection), 
            $this->escape_string($controls, $connection)
          );

          $query = "DELETE FROM `" . $table . "` " . $conditions . ";";
  
          if (mysqli_query($connection, $query)) {
            $data = $data_current;
          } else {
            $error = true;
          }

          $this->disconnect($connection);
  
          if ($error) {
            throw new Exception($this->translation->translate("Database encountered error"), 409);
          }

        } else {
          throw new Exception($this->translation->translate("Unable to connect to database"), 500);
        }
        
      } else {
        throw new Exception($this->translation->translate("Not found"), 404);
      }

    } else {
      throw new Exception($this->translation->translate("Permission denied"), 403);
    }
    
    return $data;

  }

  function delete_multiple(
    $table, 
    $multiple_filters = array(), 
    $extensions = array(), 
    $controls_delete = false,
    $clean_keys = array()
  ) {

    $multiple_filters = is_array($multiple_filters) ? $multiple_filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $controls_delete = !empty($controls_delete) ? $controls_delete : false;
    $controls = $this->controls["delete"];
    if ($controls_delete || !empty($controls_delete) || (is_array($controls_delete) && count($controls_delete))) {
      $controls = $controls_delete;
    }
    if ($clean_keys !== false) {
      $clean_keys = is_array($clean_keys) ? $clean_keys : array();
      if (empty($clean_keys)) {
        $columns = $this->columns($table);
        $clean_keys = array_map(function($value) { return $value["COLUMN_NAME"]; }, $columns);
      }
      if (!empty($extensions)) {
        $extensions = $this->clean_extensions($extensions, $clean_keys);
      }
      if (!empty($controls)) {
        $controls = $this->clean_controls($controls, $clean_keys);
      }
      for ($i = 0; $i < count($multiple_filters); $i++) {
        $multiple_filters[$i] = $this->clean_filters($multiple_filters[$i], $clean_keys);
      }
    }
    
    $data = null;

    if (!empty($controls)) {

      $data_current = array();

      $queries = array();

      $connection = $this->connect();
      if ($connection) {

        $error = false;

        foreach ($multiple_filters as $filters) {
    
          $conditions = $this->condition(
            $this->escape_string($filters, $connection), 
            $this->escape_string($extensions, $connection), 
            $this->escape_string($controls, $connection)
          );

          array_push($data_current, $this->select_multiple($table, "*", $filters, $extensions, null, null, null, null, true, $clean_keys));

          array_push($queries, "DELETE FROM `" . $table . "` " . $conditions);

        }

        if (mysqli_multi_query($connection, (implode(";", $queries) . ";"))) {
          $data = $data_current;
        } else {
          $error = true;
        }

        $this->disconnect($connection);

        if ($error) {
          throw new Exception($this->translation->translate("Database encountered error"), 409);
        }

      } else {
        throw new Exception($this->translation->translate("Unable to connect to database"), 500);
      }

    } else {
      throw new Exception($this->translation->translate("Permission denied"), 403);
    }
    
    return $data;

  }

  function limit($start = null, $limit = null) {
    $data = "";
    if (isset($limit) && !empty($limit)) {
      if (isset($start) && !empty($limit)) {
        $data .= "LIMIT " . $start . ", " . $limit;
      } else {
        $data .= "LIMIT " . $limit;
      }
    }
    return $data;
  }

  function order($sort_by = array(), $sort_direction = array()) {

    $sort_by = is_array($sort_by) ? $sort_by : (!empty($sort_by) ? array($sort_by) : array());
    $sort_direction = is_array($sort_direction) ? $sort_direction : (!empty($sort_direction) ? array($sort_direction) : array());

    if (count($sort_by) > count($sort_direction)) {
      if (!empty($sort_direction)) {
        for ($i = 0; $i < count($sort_by); $i++) {
          if ($i > count($sort_direction) - 1) {
            $sort_direction[$i] = $sort_direction[$i - 1];
          }
        }
      }
    }

    $sorts = array_map(function($by, $direction) {
      $data = "";
      if (isset($by) && !empty($by)) {
        $data .= implode(".", array_map(function($value) { return "`" . $value . "`"; }, explode(".", trim(trim($by, "[]'`"), "\'")))) . " ";
        if (!isset($direction) || empty($direction)) {
          $direction = "asc";
        }
        $data .= strtoupper($direction);
      }
      return $data;
    }, $sort_by, $sort_direction);
    
    $data = "";
    if (!empty($sorts) && isset($sorts[0]) && !empty($sorts[0])) {
      $data = "ORDER BY " . implode(", ", $sorts);
    }
    
    return $data;

  }

  function condition(
    $filters = array(), 
    $extensions = array(), 
    $controls = array(),
    $table = null
  ) {

    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $controls = is_array($controls) ? $controls : array();

    $data = "";

    $filters_is_set = !empty($filters) && is_array($filters);
    $extensions_is_set = !empty($extensions) && is_array($extensions);
    $controls_is_set = !empty($controls) && is_array($controls);
    $filters_arranged = array();
    if ($filters_is_set) {
      $filters_arranged = $this->arrange_filters($filters, $table);
    }
    $extensions_arranged = array();
    if ($extensions_is_set) {
      $extensions_arranged = $this->arrange_extensions($extensions, $table);
    }
    $controls_arranged = array();
    if ($controls_is_set) {
      $controls_arranged = $this->arrange_controls($controls, $table);
    }

    $conditions = array_merge(
      $filters_arranged, 
      $extensions_arranged, 
      $controls_arranged
    );
    if (!empty($conditions)) {
      $data = "WHERE " . implode(" AND ", $conditions);
    }

    return $data;

  }

  function keys($keys = array(), $table = null) {

    $data = array();
    if (isset($keys) && $keys != "*" && !empty($keys)) {

      $keys = is_array($keys) ? array_map('trim', $keys) : array_map('trim', explode(",", $keys));

      foreach ($keys as $key) {
        $key = trim(trim($key, "[]'`"), "\'");
        $key_parts = array_map(function($value) { 
          return "`" . $value . "`"; 
        }, explode(".", $key));
        if (
          (function_exists("str_contains") && str_contains($key, "."))
          || (strpos($key, ".") >= 0 && strpos($key, ".") !== false)
        ) {
          $key = implode(".", $key_parts);
        } else {
          if (!empty($table) && !in_array("`" . trim($table, "`") . "`", $key_parts)) {
            $key = "`" . trim($table, "`") . "`." . implode(".", $key_parts);
          } else {
            $key = implode(".", $key_parts);
          }
        }
        array_push($data, $key);
      }

      if (!empty($data)) {
        return implode(",", $data);
      } else {
        return "";
      }

    } else {
        return "`" . trim($table, "`") . "`.*";
    }

  }

  private function arrange_filters($filters = array(), $table = null) {

    $filters = is_array($filters) ? $filters : array();

    $data = array();
    if (isset($filters) && !empty($filters) && is_array($filters)) {
      foreach ($filters as $key => $value) {
        if (isset($value)) {
          if (is_string($value) || $value === "") {
            $value = "'" . $value . "'";
          } else if (is_null($value)) {
            $value = "NULL";
          } else if (is_bool($value)) {
            $value = (!empty($value) ? "true" : "false");
          }
          $key = trim(trim($key, "[]'`"), "\'");
          $key_parts = array_map(function($value) { 
            return "`" . $value . "`"; 
          }, explode(".", $key));
          if (
            (function_exists("str_contains") && str_contains($key, "."))
            || (strpos($key, ".") >= 0 && strpos($key, ".") !== false)
          ) {
            $key = implode(".", $key_parts);
          } else {
            if (!empty($table) && !in_array("`" . trim($table, "`") . "`", $key_parts)) {
              $key = "`" . trim($table, "`") . "`." . implode(".", $key_parts);
            } else {
              $key = implode(".", $key_parts);
            }
          }
          array_push($data, $key . " = " . $value);
        }
      }
    }
    return $data;
  }

  private function arrange_extensions($extensions = array(), $table = null) {
    
    $extensions = is_array($extensions) ? $extensions : array();

    $data = array();

    if (isset($extensions) && !empty($extensions) && is_array($extensions)) {
      for ($i = 0; $i < count($extensions); $i++) {
        if (isset($extensions[$i])) {
          if (
            array_key_exists("extensions", $extensions[$i]) 
            && is_array($extensions[$i]["extensions"])
          ) {
            $extension_arranged = $this->arrange_extensions($extensions[$i]["extensions"], $table);
            if (count($extension_arranged)) {
              array_push($data, 
                (trim($extensions[$i]["conjunction"]) ? trim($extensions[$i]["conjunction"]) . " " : "")
                . $extension_arranged[0]
              );
            }
          } else {
            $value = $extensions[$i]["value"];
            if (Utilities::length(trim(trim($extensions[$i]["value"], "[]\'`"), "\'")) < Utilities::length($extensions[$i]["value"])) {
              $value = "'" . trim(trim($extensions[$i]["value"], "[]\'`"), "\'") . "'";
            }
            $key = trim(trim($extensions[$i]["key"], "[]'`"), "\'");
            $key_parts = array_map(function($value) { 
              return "`" . $value . "`"; 
            }, explode(".", $key));
            if (
              (function_exists("str_contains") && str_contains($key, "."))
              || (strpos($key, ".") >= 0 && strpos($key, ".") !== false)
            ) {
              $key = implode(".", $key_parts);
            } else {
              if (!empty($table) && !in_array("`" . trim($table, "`") . "`", $key_parts)) {
                $key = "`" . trim($table, "`") . "`." . implode(".", $key_parts);
              } else {
                $key = implode(".", $key_parts);
              }
            }
            array_push($data, 
              (trim($extensions[$i]["conjunction"]) ? trim($extensions[$i]["conjunction"]) . " " : "") 
              . $key . " " . $extensions[$i]["operator"] . " " . $value
            );
          }
        }
      }
      if (!empty($data)) {
        return array(
          "(" . implode(" ", $data) . ")"
        );
      } else {
        return array();
      }
    } else {
      return $data;
    }
  }

  private function arrange_controls($controls, $table = null) {
    
    $data = array();

    if (!empty($controls) && is_array($controls)) {
      foreach ($controls as $rules) {

        $consolidate = function($rule, $table) {

          $control = "";
          $key = "";
          $value = "";
          $operator = "";
          $rule_pattern = "/([A-Za-z_]+|\[[A-Za-z_]+\])(" . implode("|", $this::$comparisons) . ")(.*)/";
          if (preg_match($rule_pattern, $rule, $matches)) {
            if (count($matches) === 4) {
              $key = $matches[1];
              $value = $matches[3];
              $operator = $matches[2];
            }
          }
          if (!empty($key) && !empty($value) && !empty($operator) && in_array($operator, $this::$comparisons)) {
            if ($value == "<session>") {
              if (isset($this->session) && !empty($this->session) && isset($this->session->id)) {
                $value = "'" . $this->session->id . "'";
              } else {
                $value = "'0'";
              }
            } else {
              if (Utilities::length(trim(trim($value, "[]\'`"), "\'")) < Utilities::length($value)) {
                $value = "'" . trim(trim($value, "[]\'`"), "\'") . "'";
              }
            }
            $key = trim(trim($key, "[]'`"), "\'");
            $key_parts = array_map(function($value) { 
              return "`" . $value . "`"; 
            }, explode(".", $key));
            if (
              (function_exists("str_contains") && str_contains($key, "."))
              || (strpos($key, ".") >= 0 && strpos($key, ".") !== false)
            ) {
              $key = implode(".", $key_parts);
            } else {
              if (!empty($table) && !in_array("`" . trim($table, "`") . "`", $key_parts)) {
                $key = "`" . trim($table, "`") . "`." . implode(".", $key_parts);
              } else {
                $key = implode(".", $key_parts);
              }
            }
            $control = $key . " " . $operator . " " . $value;
          }

          return $control;

        };

        if (is_array($rules)) {
          foreach ($rules as $rule) {
            $consolidated = $consolidate($rule, $table);
            if (!empty($consolidated)) {
              array_push($data, $consolidated);
            }
          }
        } else {
          $rules = explode(",", $rules);
          foreach ($rules as $rule) {
            $consolidated = $consolidate($rule, $table);
            if (!empty($consolidated)) {
              array_push($data, $consolidated);
            }
          }
        }
        
      }
      if (!empty($data)) {
        return array(
          "(" . implode(" OR ", $data) . ")"
        );
      } else {
        return array();
      }
    } else {
      return array();
    }
    
  }

  function validate_parameters($controls, $parameters = null) {
    $result = true;
    $key = "";
    if (isset($parameters) && !empty($parameters)) {
      if (!empty($controls) && is_array($controls)) {
        foreach ($controls as $rules) {

          $consolidate = function($rule) {

            $result = true;

            $key = "";
            $value = "";
            $operator = "";
            $rule_pattern = "/([A-Za-z_]+|\[[A-Za-z_]+\])(" . implode("|", $this::$comparisons) . ")(.*)/";

            if (preg_match($rule_pattern, $rule, $matches)) {
              if (count($matches) === 4) {
                $key = $matches[1];
                $value = $matches[3];
                $operator = $matches[2];
              }
            }
            if (!empty($key) && !empty($value) && !empty($operator) && in_array($operator, $this::$comparisons)) {
              $key = trim(trim($key, "[]'`"), "\'");
              if ($value == "<session>") {
                if (isset($this->session) && !empty($this->session) && isset($this->session->id)) {
                  $value = "'" . $this->session->id . "'";
                } else {
                  $value = "'0'";
                }
              } else {
                if (Utilities::length(trim(trim($value, "[]\'`"), "\'")) < Utilities::length($value)) {
                  $value = "'" . trim(trim($value, "[]\'`"), "\'") . "'";
                }
              }
              $value = trim($value, "'");
              if (isset($parameters[$key])) {
                $parameter = $parameters[$key];
                if (
                  (
                    $operator == "=" && !($parameter == $value)
                  ) || (
                    $operator == "!=" && !($parameter != $value)
                  ) || (
                    $operator == ">" && !($parameter > $value)
                  ) || (
                    $operator == "<" && !($parameter < $value)
                  ) || (
                    $operator == ">=" && !($parameter >= $value)
                  ) || (
                    $operator == "<=" && !($parameter <= $value)
                  ) || (
                    $operator == "<>" && !($parameter <> $value)
                  )
                ) {
                  $result = false;
                } else if ($operator == "LIKE" || $operator == "NOT LIKE") {
                  $comparer = str_replace("%", "", $value);
                  $siginificant = str_replace(
                    strtolower($comparer), "", strtolower($parameter)
                  );
                  $not_like_condition = (
                    strpos(strtolower($parameter), strtolower($value)) === false
                    || (
                      stristr($parameter, "%") === false
                      && !(strtolower($parameter) == strtolower($value))
                    ) || (
                      strpos($parameter, "%") === 0
                      && strpos($parameter, "%") === Utilities::length($comparer) - 1
                      && stristr(strtolower($parameter), $comparer) === false
                    ) || (
                      strpos($parameter, "%") === 0
                      && (!(strpos(strtolower($parameter), $siginificant) === 0)) && !empty($siginificant)
                    ) || (
                      strpos($parameter, "%") === Utilities::length($comparer) - 1
                      && (strpos(strtolower($parameter), $siginificant) === 0)
                      && (!(strpos(strtolower($parameter), $siginificant) === 0)) && !empty($siginificant)
                    )
                  );
                  if ($operator == "LIKE") {
                    if ($not_like_condition) {
                      $result = false;
                    }
                  } else {
                    if (!$not_like_condition) {
                      $result = false;
                    }
                  }
                } else if ($operator == "BETWEEN" || $operator == "NOT BETWEEN") {
                  $value_parts = explode(":", $value);
                  $minimum = trim($value_parts[0], "'");
                  $maximum = trim($value_parts[1], "'");
                  if (
                    (strtotime($minimum) || strtotime($minimum . " 00:00:00"))
                    && (strtotime($maximum) || strtotime($maximum . " 00:00:00"))
                    && (strtotime($parameter) || strtotime($parameter . " 00:00:00"))
                  ) {
                    $datetime = strtotime($parameter . " 00:00:00");
                    if (strtotime($parameter)) {
                      $datetime = strtotime($parameter);
                    }
                    $minimum_datetime = strtotime($minimum . " 00:00:00");
                    if (strtotime($minimum)) {
                      $minimum_datetime = strtotime($minimum);
                    }
                    $maximum_datetime = strtotime($maximum . " 00:00:00");
                    if (strtotime($maximum)) {
                      $maximum_datetime = strtotime($maximum);
                    }
                    if ($operator == "BETWEEN") {
                      if ($datetime < $minimum_datetime || $datetime > $maximum_datetime) {
                        $result = false;
                      }
                    } else if ($operator == "NOT BETWEEN") {
                      if ($datetime < $minimum_datetime && $datetime > $maximum_datetime) {
                        $result = false;
                      }
                    }
                  } else if (
                    is_numeric($minimum) && is_numeric($minimum)
                    && is_numeric($maximum) && is_numeric($maximum)
                    && is_numeric($parameter) && is_numeric($parameter)
                  ) {
                    if ($operator == "BETWEEN") {
                      if (floatval($parameter) < floatval($minimum) || floatval($parameter) > floatval($maximum)) {
                        $result = false;
                      }
                    } else if ($operator == "NOT BETWEEN") {
                      if (floatval($parameter) < floatval($minimum) && floatval($parameter) > floatval($maximum)) {
                        $result = false;
                      }
                    }
                  } else if (
                    is_numeric(str_replace(",", "", $minimum)) && is_numeric(str_replace(",", "", $minimum))
                    && is_numeric(str_replace(",", "", $maximum)) && is_numeric(str_replace(",", "", $maximum))
                    && is_numeric(str_replace(",", "", $parameter)) && is_numeric(str_replace(",", "", $parameter))
                  ) {
                    if ($operator == "BETWEEN") {
                      if (
                        floatval(str_replace(",", "", $parameter)) < floatval(str_replace(",", "", $minimum)) 
                        || floatval(str_replace(",", "", $parameter)) > floatval(str_replace(",", "", $maximum))
                      ) {
                        $result = false;
                      }
                    } else if ($operator == "NOT BETWEEN") {
                      if (
                        floatval(str_replace(",", "", $parameter)) < floatval(str_replace(",", "", $minimum)) 
                        && floatval(str_replace(",", "", $parameter)) > floatval(str_replace(",", "", $maximum))
                      ) {
                        $result = false;
                      }
                    }
                  }
                }
              }
            }

            return $result;

          };

          if (is_array($rules)) {
            foreach ($rules as $rule) {
              $result = $consolidate($rule);
            }
          } else {
            $result = $consolidate($rules);
          }

        }
      }
    }
    if (!$result) {
      if (!empty($key)) {
        throw new Exception(
          $this->translation->translate("Value of") . " " .
          $this->translation->translate("some parameter") . " " .
          $this->translation->translate("is not allowed")
          , 403
        );
      } else {
        throw new Exception(
          $this->translation->translate("Value of") . " '" . $key . "' " . 
          $this->translation->translate("is not allowed")
          , 403
        );
      }
    }
    return $result;
  }

  function get_reference($value, $table, $table_key) {
    $result = $this->select(
      $table, 
      "*", 
      array($table_key => $value), 
      null, 
      true,
      false
    );
    return $result;
  }

  function columns($table, $override_connection = null) {
    if (!empty($table)) {

      $data = array();
  
      $connection = $override_connection;
      if (empty($override_connection)) {
        $connection = $this->connect();
      }
      if ($connection) {

        $error = false;
  
        $query = 
        "SELECT * from INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" 
        . $this->config["database_name"] . "' AND TABLE_NAME = '" . $table . "';";
        
        if ($result = mysqli_query($connection, $query)) {
          $rows = array();
          while($row = $result->fetch_assoc()) {
            array_push($rows, $row);
          }
          $data = $rows;
          mysqli_free_result($result);
        } else {
          $error = true;
        }

        if (empty($override_connection)) {
          $this->disconnect($connection);
        }
    
        if ($error) {
          throw new Exception($this->translation->translate("Database encountered error"), 409);
        }

      } else {
        throw new Exception($this->translation->translate("Unable to connect to database"), 500);
      }

      return $data;
      
    } else {
      throw new Exception($this->translation->translate("Not found"), 404);
      return null;
    }
  }

  function allow($table) {
    $columns = $this->columns($table);
    if (!empty($columns)) {

    }
  }

  function clean_parameters($parameters, $clean_keys) {
    if (!empty($clean_keys)) {
      foreach ($parameters as $key => $parameter) {
        if (!in_array($key, $clean_keys)) {
          unset($parameters[$key]);
        }
      }
    }
    return $parameters;
  }

  function clean_filters($filters, $clean_keys) {
    if (!empty($clean_keys)) {
      foreach ($filters as $key => $filter) {
        if (!in_array($key, $clean_keys)) {
          unset($filters[$key]);
        }
      }
    }
    return $filters;
  }

  function clean_extensions($extensions, $clean_keys) {
    if (!empty($clean_keys)) {
      for ($i = 0; $i < count($extensions); $i++) {
        if (isset($extensions[$i])) {
          if (
            isset($extensions[$i]["extensions"]) 
            && is_array($extensions[$i]["extensions"])
          ) {
            $extensions[$i]["extensions"] = $this->clean_extensions($extensions[$i]["extensions"], $clean_keys);
          } else {
            $key = trim(trim($extensions[$i]["key"], "[]'`"), "\'");
            if (
              (function_exists("str_contains") && str_contains($extensions[$i]["key"], "."))
              || (strpos($extensions[$i]["key"], ".") >= 0 && strpos($extensions[$i]["key"], ".") !== false)
            ) {
              $key_parts = explode(".", trim(trim($extensions[$i]["key"], "'`")));
              $key = trim(trim($key_parts[1], "[]'`"), "\'");
            }
            if (!in_array($key, $clean_keys)) {
              unset($extensions[$i]);
            }
          }
        }
      }
    }
    return $extensions;
  }

  function clean_controls($controls, $clean_keys) {
    if (!empty($clean_keys)) {
      if (!empty($controls) && is_array($controls)) {
        foreach ($controls as $rules) {

          $consolidate = function($rule, $clean_keys) {
            $rule_pattern = "/([A-Za-z_]+|\[[A-Za-z_]+\])(" . implode("|", $this::$comparisons) . ")(.*)/";
            if (preg_match($rule_pattern, $rule, $matches)) {
              $key = "";
              if (count($matches) === 4) {
                $key = trim(trim($matches[1], "[]'`"), "\'");
                if (!in_array($key, $clean_keys)) {
                  return $rule;
                }
              }
            }
          };

          if (is_array($rules)) {
            foreach ($rules as $rule) {
              if ($rule_check = $consolidate($rule, $clean_keys)) {
                for ($i = 0; $i < count($controls); $i++) {
                  if (is_array($controls[$i])) {
                    for ($j = 0; $j < count($controls[$i]); $j++) {
                      if ($controls[$i][$j] === $rule_check) {
                        unset($controls[$i][$j]);
                        break;
                      }
                    }
                  }
                }
              }
            }
          } else {
            if ($rule_check = $consolidate($rules, $clean_keys)) {
              for ($i = 0; $i < count($controls); $i++) {
                if (!is_array($controls[$i])) {
                  if ($controls[$i] === $rule_check) {
                    unset($controls[$i]);
                    break;
                  }
                }
              }
            }
          }

        }
      }
    }
    return $controls;
  }

  function escape_string($parameters, $override_connection = null) {
    $connection = $override_connection;
    if (empty($override_connection)) {
      $connection = $this->connect();
    }
    if ($connection) {
      if (isset($parameters) && !empty($parameters)) {
        if (is_array($parameters)) {
          foreach ($parameters as $key => $value) {
            if (!empty($value)) {
              $parameters[$key] = $this->escape_string($value, $connection);
            }
          }
        } else {
          if (is_string($parameters)) {
            $parameters = mysqli_real_escape_string($connection, $parameters);
          }
        }
        return $parameters;
      } else {
        return $parameters;
      }
      if (empty($override_connection)) {
        $this->disconnect($connection);
      }
    } else {
      return $parameters;
    }
  }

}