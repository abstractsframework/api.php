<?php
namespace Abstracts\Helpers;

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
    $config,
    $session = null,
    $controls = null
  ) {

    /* initialize: core */
    $this->config = $config;
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

  function connect($charset = null) {
    
    $connection = mysqli_connect(
      $this->config["database_host"], 
      $this->config["database_login"], 
      $this->config["database_password"], 
      $this->config["database_name"]
    );
    if ($connection) {
      if (!mysqli_select_db($connection, $this->config["database_name"])) {
        $connection = false;
      } else {
        if ($charset == null) {
          $charset = strtolower($this->config["database_encoding"]);
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
    $allowed_keys = array(),
    $fetch_type = "assoc"
  ) {

    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();
    $fetch_type = !empty($fetch_type) ? $fetch_type : "assoc";
    $controls_view = !empty($controls_view) ? $controls_view : false;
    
    $data = null;
    
    if ($this->controls["view"] || $controls_view) {

      $controls = $this->controls["view"];
      if ($controls_view || !empty($controls_view) || (is_array($controls_view) && count($controls_view))) {
        $controls = $controls_view;
      }

      $connection = $this->connect();
      
      $conditions = $this->condition(
        $this->escape_string($filters, $connection), 
        $this->escape_string($extensions, $connection), 
        $this->escape_string($controls, $connection),
        $allowed_keys
      );
      
      if (!empty($keys) && is_array($keys) && count($keys)) {
        $keys = "`" . implode("`, `", $keys) . "`";
      }

      $sql = "
      SELECT " . $keys . " 
      FROM `" . $table . "` 
      " . $conditions . "
      LIMIT 1;";
      $error = false;
      if ($connection) {
        if ($result = mysqli_query($connection, $sql)) {
          if ($fetch_type == "assoc") {
            if ($query = $result->fetch_assoc()) {
              $data = (object) $query;
            } else {
              $data = null;
            }
          } else {
            if ($query = $result->fetch_array()) {
              $data = (object) $query;
            } else {
              $data = null;
            }
          }
          mysqli_free_result($result);
        } else {
          $error = true;
        }
        $this->disconnect($connection);
      } else {
        $error = true;
      }
  
      if ($error) {
        var_dump($sql);
        throw new Exception($this->translation->translate("Database encountered error"), 500);
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
    $allowed_keys = array(),
    $fetch_type = "assoc"
  ) {

    $start = !empty($start) ? $start : null;
    $limit = !empty($limit) ? $limit : null;
    $sort_by = !empty($sort_by) ? $sort_by : "id";
    $sort_direction = !empty($sort_direction) ? $sort_direction : "desc";
    $activate = !empty($activate) ? $activate : null;
    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();
    $fetch_type = !empty($fetch_type) ? $fetch_type : "assoc";
    $controls_view = !empty($controls_view) ? $controls_view : false;
    
    $data = null;

    if ($this->controls["view"] || $controls_view) {

      $controls = $this->controls["view"];
      if ($controls_view || !empty($controls_view) || (is_array($controls_view) && count($controls_view))) {
        $controls = $controls_view;
      }

      $connection = $this->connect();

      $start = $this->escape_string($start, $connection);
      $limit = $this->escape_string($limit, $connection);
      $sort_by = $this->escape_string($sort_by, $connection);
      $sort_direction = $this->escape_string($sort_direction, $connection);
      $conditions = $this->condition(
        $this->escape_string($filters, $connection), 
        $this->escape_string($extensions, $connection), 
        $this->escape_string($controls, $connection),
        $allowed_keys
      );

      if (!empty($keys) && is_array($keys) && count($keys)) {
        $keys = "`" . implode("`, `", $keys) . "`";
      }

      $sql = "
      SELECT " . $keys . " 
      FROM `" . $table . "` 
      " . $conditions . "
      " . $this->order($sort_by, $sort_direction) . "
      " . $this->limit($start, $limit) . ";";
      
      $error = false;

      if ($connection) {
        if ($result = mysqli_query($connection, $sql)) {
          $queries = array();
          if ($fetch_type == "assoc") {
            while($query = $result->fetch_assoc()) {
              array_push($queries, (object) $query);
            }
          } else {
            while($query = $result->fetch_array()) {
              array_push($queries, (object) $query);
            }
          }
          $data = $queries;
          mysqli_free_result($result);
        } else {
          $error = true;
        }
        $this->disconnect($connection);
      } else {
        $error = true;
      }

      if ($error) {
        throw new Exception($this->translation->translate("Database encountered error"), 500);
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
    $allowed_keys = array()
  ) {

    $start = !empty($start) ? $start : null;
    $limit = !empty($limit) ? $limit : null;
    $activate = !empty($activate) ? $activate : null;
    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();
    $controls_view = !empty($controls_view) ? $controls_view : false;
    
    $data = 0;

    if ($this->controls["view"] || $controls_view) {

      $controls = $this->controls["view"];
      if ($controls_view || !empty($controls_view) || (is_array($controls_view) && count($controls_view))) {
        $controls = $controls_view;
      }

      $connection = $this->connect();

      $conditions = $this->condition(
        $this->escape_string($filters, $connection), 
        $this->escape_string($extensions, $connection), 
        $this->escape_string($controls, $connection),
        $allowed_keys
      );

      $sql = "
      SELECT NULL
      FROM `" . $table . "` 
      " . $conditions . "
      " . $this->limit($start, $limit) . ";";
      if (empty(trim($conditions))) {
        $sql = "
        EXPLAIN SELECT NULL
        FROM `" . $table . "` 
        " . $this->limit($start, $limit) . ";";
      }
      
      $error = false;

      if ($connection) {
        if ($result = mysqli_query($connection, $sql)) {
          if (!empty(trim($conditions))) {
            $data = mysqli_num_rows($result);
          } else {
            $data = 0;
            $query = mysqli_fetch_assoc($result);
            if (isset($query["rows"]) && !empty($query["rows"])) {
              $data = (int)($query["rows"]);
            }
          }
          mysqli_free_result($result);
        } else {
          $error = true;
        }
        $this->disconnect($connection);
      } else {
        $error = true;
      }

      if ($error) {
        throw new Exception($this->translation->translate("Database encountered error"), 500);
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
    $allowed_keys = array()
  ) {

    $controls_create = !empty($controls_create) ? $controls_create : false;
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();
   
    $data = null;

    if (!empty($parameters)) {
      if ($this->controls["create"] || $controls_create) {

        $controls = $this->controls["create"];
        if ($controls_create || !empty($controls_create) || (is_array($controls_create) && count($controls_create))) {
          $controls = $controls_create;
        }

        if ($this->validate_parameters($controls, $parameters)) {

          $connection = $this->connect();

          $parameters = $this->clean($parameters, $allowed_keys);
          $parameters = $this->escape_string($parameters, $connection);
          
          $keys = array();
          $values = array();
          foreach($parameters as $key => $value) {
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

          $sql = "
          INSERT INTO `" . $table . "`
          (" . implode(", ", $keys) . ") 
          VALUES (" . implode(", ", $values) . ");";
          
          $error = false;
    
          if ($connection) {
            if (mysqli_query($connection, $sql)) {
              $id = mysqli_insert_id($connection);
              $data = $this->select($table, "*", array("id" => $id), null, true, $allowed_keys);
            } else {
              $error = true;
            }
    
            $this->disconnect($connection);
    
          } else {
            $error = true;
          }

          if ($error) {
            throw new Exception($this->translation->translate("Database encountered error"), 500);
          }

        } else {
          throw new Exception($this->translation->translate("Permission denied"), 403);
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
    $allowed_keys = array()
  ) {

    $controls_create = !empty($controls_create) ? $controls_create : false;
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();
   
    $data = null;

    if (!empty($multiple_parameters)) {

      if ($this->controls["create"] || $controls_create) {
  
        $controls = $this->controls["create"];
        if ($controls_create || !empty($controls_create) || (is_array($controls_create) && count($controls_create))) {
          $controls = $controls_create;
        }
  
        $error = false;
  
        foreach($multiple_parameters as $parameters) {
          if (!$this->validate_parameters($controls, $parameters)) {
            $error = true;
          }
        }
  
        if (!$error) {
  
          $sql = "";
  
          $connection = $this->connect();
  
          $multiple_parameters = $this->escape_string($multiple_parameters, $connection);
  
          foreach($multiple_parameters as $parameters) {
  
            $parameters = $this->clean($parameters, $allowed_keys);
  
            $keys = array();
            $values = array();
            foreach($parameters as $key => $value) {
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
    
            $sql .= "
            INSERT INTO `" . $table . "`
            (" . implode(", ", $keys) . ") 
            VALUES (" . implode(", ", $values) . ");";
  
          }
  
          $error = false;
    
          if ($connection) {
            if (mysqli_multi_query($connection, $sql)) {
              $id = mysqli_insert_id($connection);
              $data = $this->select($table, "*", array("id" => $id), null, true, $allowed_keys);
            } else {
              $error = true;
            }
            $this->disconnect($connection);
          } else {
            $error = true;
          }
  
          if ($error) {
            throw new Exception($this->translation->translate("Database encountered error"), 500);
          }
  
        } else {
          throw new Exception($this->translation->translate("Permission denied"), 403);
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
    $allowed_keys = array()
  ) {

    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();
    $controls_update = !empty($controls_update) ? $controls_update : false;
    
    $data = null;

    if (!empty($parameters)) {
      if ($this->controls["update"] || $controls_update) {

        $controls = $this->controls["update"];
        if ($controls_update || !empty($controls_update) || (is_array($controls_update) && count($controls_update))) {
          $controls = $controls_update;
        }

        $connection = $this->connect();

        $parameters = $this->clean($parameters, $allowed_keys);
        $parameters = $this->escape_string($parameters, $connection);
        $conditions = $this->condition(
          $this->escape_string($filters, $connection), 
          $this->escape_string($extensions, $connection), 
          $this->escape_string($controls, $connection),
          $allowed_keys
        );

        $sets = array();
        foreach($parameters as $key => $value) {
          if (is_string($value) || $value === "") {
            $value = "'" . $value . "'";
          } else if (is_null($value)) {
            $value = "NULL";
          } else if (is_bool($value)) {
            $value = (!empty($value) ? "true" : "false");
          }
          array_push($sets, "`" . $key . "` = " . $value);
        }
        
        $data_current = $this->select_multiple($table, "*", $filters, $extensions, null, null, null, null, true, $allowed_keys);
        
        $sql = "
        UPDATE `" . $table . "`
        SET " . implode(", ", $sets) . "
        " . $conditions . ";";
        
        $error = false;

        if ($connection) {
          if (mysqli_query($connection, $sql)) {
            $data = $this->select_multiple($table, "*", $filters, $extensions, $controls, null, null, null, true, $allowed_keys);
          } else {
            $error = true;
          }
          $this->disconnect($connection);
        } else {
          $error = true;
        }

        if ($error) {
          throw new Exception($this->translation->translate("Database encountered error"), 500);
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
    $allowed_keys = array()
  ) {

    $multiple_filters = is_array($multiple_filters) ? $multiple_filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();
    $controls_update = !empty($controls_update) ? $controls_update : false;
    
    $data = null;

    if (!empty($parameters)) {
      if ($this->controls["update"] || $controls_update) {

        $controls = $this->controls["update"];
        if ($controls_update || !empty($controls_update) || (is_array($controls_update) && count($controls_update))) {
          $controls = $controls_update;
        }

        $data_current = array();

        $sql = "";

        $connection = $this->connect();

        $parameters = $this->clean($parameters, $allowed_keys);
        $parameters = $this->escape_string($multiple_filters, $parameters);
        $multiple_filters = $this->escape_string($multiple_filters, $connection);

        foreach($multiple_filters as $filters) {
    
          $conditions = $this->condition(
            $this->escape_string($filters, $connection), 
            $this->escape_string($extensions, $connection), 
            $this->escape_string($controls, $connection),
            $allowed_keys
          );

          $sets = array();
          foreach($parameters as $key => $value) {
            if (is_string($value) || $value === "") {
              $value = "'" . $value . "'";
            } else if (is_null($value)) {
              $value = "NULL";
            } else if (is_bool($value)) {
              $value = (!empty($value) ? "true" : "false");
            }
            array_push($sets, "`" . $key . "` = " . $value);
          }
          
          array_push($this->select_multiple($data_current, $table, "*", $filters, $extensions, null, null, null, null, true, $allowed_keys));
          
          $sql .= "
          UPDATE `" . $table . "`
          SET " . implode(", ", $sets) . "
          " . $conditions . ";";

        }
        
        $error = false;
    
        if ($connection) {
          if (mysqli_multi_query($connection, $sql)) {
            $data = array();
            foreach($multiple_filters as $filters) {
              array_push($data, $this->select_multiple($table, "*", $filters, $extensions, $controls, null, null, null, true, $allowed_keys));
            }
          } else {
            $error = true;
          }
          $this->disconnect($connection);
        } else {
          $error = true;
        }

        if ($error) {
          throw new Exception($this->translation->translate("Database encountered error"), 500);
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
    $allowed_keys = array()
  ) {

    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();
    $controls_delete = !empty($controls_delete) ? $controls_delete : false;
    
    $data = null;

    if ($this->controls["delete"] || $controls_delete) {

      $controls = $this->controls["delete"];
      if ($controls_delete || !empty($controls_delete) || (is_array($controls_delete) && count($controls_delete))) {
        $controls = $controls_delete;
      }

      $data_current = $this->select_multiple($table, "*", $filters, $extensions, null, null, null, null, true, $allowed_keys);
      if (!empty($data_current) && count($data_current)) {

        $connection = $this->connect();
  
        $conditions = $this->condition(
          $this->escape_string($filters, $connection), 
          $this->escape_string($extensions, $connection), 
          $this->escape_string($controls, $connection),
          $allowed_keys
        );

        $sql = "
        DELETE FROM `" . $table . "`
        " . $conditions . ";";
  
        $error = false;
  
        if ($connection) {
          if (mysqli_query($connection, $sql)) {
            $data = $data_current;
          } else {
            $error = true;
          }
          $this->disconnect($connection);
        } else {
          $error = true;
        }
  
        if ($error) {
          throw new Exception($this->translation->translate("Database encountered error"), 500);
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
    $allowed_keys = array()
  ) {

    $multiple_filters = is_array($multiple_filters) ? $multiple_filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();
    $controls_delete = !empty($controls_delete) ? $controls_delete : false;
    
    $data = null;

    if ($this->controls["delete"] || $controls_delete) {

      $controls = $this->controls["delete"];
      if ($controls_delete || !empty($controls_delete) || (is_array($controls_delete) && count($controls_delete))) {
        $controls = $controls_delete;
      }

      $data_current = array();

      $sql = "";

      $connection = $this->connect();

      foreach($multiple_filters as $filters) {
  
        $conditions = $this->condition(
          $this->escape_string($filters, $connection), 
          $this->escape_string($extensions, $connection), 
          $this->escape_string($controls, $connection),
          $allowed_keys
        );

        array_push($data_current, $this->select_multiple($table, "*", $filters, $extensions, null, null, null, null, true, $allowed_keys));

        $sql .= "
        DELETE FROM `" . $table . "`
        " . $conditions . ";";

      }

      $error = false;

      if ($connection) {
        if (mysqli_multi_query($connection, $sql)) {
          $data = $data_current;
        } else {
          $error = true;
        }
        $this->disconnect($connection);
      } else {
        $error = true;
      }

      if ($error) {
        throw new Exception($this->translation->translate("Database encountered error"), 500);
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

  function order($sort_by = null, $sort_direction = null) {
    $data = "";
    if (isset($sort_by) && !empty($sort_by)) {
      $data .= "ORDER BY `" . $sort_by . "` ";
      if (isset($sort_direction) && !empty($sort_direction)) {
        $data .= $sort_direction . " ";
      } else {
        $data .= "asc ";
      }
    }
    return $data;
  }

  function condition(
    $filters = array(), 
    $extensions = array(), 
    $controls = array(), 
    $allowed_keys = array()
  ) {

    $filters = is_array($filters) ? $filters : array();
    $extensions = is_array($extensions) ? $extensions : array();
    $controls = is_array($controls) ? $controls : array();
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();

    $data = "";

    $filters_is_set = !empty($filters) && is_array($filters) && count($filters);
    $extensions_is_set = !empty($extensions) && is_array($extensions) && count($extensions);
    $controls_is_set = !empty($controls) && is_array($controls) && count($controls);
    $filters_arranged = array();
    if ($filters_is_set) {
      $filters_arranged = $this->arrange_filters($filters, $allowed_keys);
    }
    $extensions_arranged = array();
    if ($extensions_is_set) {
      $extensions_arranged = $this->arrange_extensions($extensions, $allowed_keys);
    }
    $controls_arranged = array();
    if ($controls_is_set) {
      $controls_arranged = $this->arrange_controls($controls, $allowed_keys);
    }

    $conditions = array_merge(
      $filters_arranged, 
      $extensions_arranged, 
      $controls_arranged
    );
    if (count($conditions)) {
      $data = "WHERE " . implode(" AND ", $conditions);
    }

    return $data;

  }

  private function arrange_filters($filters = array(), $allowed_keys = array()) {

    $filters = is_array($filters) ? $filters : array();
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();

    $data = array();
    if (isset($filters) && !empty($filters) && is_array($filters) && count($filters)) {
      foreach($filters as $key => $value) {
        if (isset($value) && (!count($allowed_keys) || in_array($key, $allowed_keys))) {
          if (is_string($value) || $value === "") {
            $value = "'" . $value . "'";
          } else if (is_null($value)) {
            $value = "NULL";
          } else if (is_bool($value)) {
            $value = (!empty($value) ? "true" : "false");
          }
          array_push($data, "`" . $key . "` = " . $value);
        }
      }
    }
    return $data;
  }

  private function arrange_extensions($extensions = array(), $allowed_keys = array()) {

    $extensions = is_array($extensions) ? $extensions : array();
    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();

    $data = array();
    if (isset($extensions) && !empty($extensions) && is_array($extensions) && count($extensions)) {
      for ($i = 0; $i < count($extensions); $i++) {
        if (isset($extensions[$i])) {
          if (isset($extensions[$i]["extensions"]) 
          && is_array($extensions[$i]["extensions"])) {
            array_push($data, 
              (trim($extensions[$i]["conjunction"]) ? trim($extensions[$i]["conjunction"]) . " " : "")
              . $this->arrange_extensions($extensions[$i]["extensions"])[0]
            );
          } else {
            $key = str_replace("`", "", $extensions[$i]["key"]);
            if (!count($allowed_keys) || in_array($key, $allowed_keys)) {
              $value = trim(str_replace("`", "", $extensions[$i]["value"]), "\'");
              if (Utilities::length($value) < Utilities::length($extensions[$i]["value"])) {
                $value = "'" . $value . "'";
              }
              array_push($data, 
                (trim($extensions[$i]["conjunction"]) ? trim($extensions[$i]["conjunction"]) . " " : "") . 
                "`" . $key . "` " . 
                $extensions[$i]["operator"] . " " . 
                $value
              );
            }
          }
        }
      }
      if (count($data)) {
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

  private function arrange_controls($controls, $allowed_keys = array()) {

    $allowed_keys = is_array($allowed_keys) ? $allowed_keys : array();
    
    $data = array();

    if (!empty($controls) && is_array($controls) && count($controls)) {
      $index = 0;
      foreach($controls as $rule) {
        $control = "";
        $operator = "";
        foreach($this::$comparisons as $comparison) {
          if (stristr(strtolower($rule), strtolower($comparison)) !== false) {
            $operator = $comparison;
          }
        }
        if (!empty($operator)) {
          $prefix = " AND ";
          if ($index == 0) {
            $prefix = "";
          }
          $rule_parts = explode($operator, $rule);
          $key = trim(trim($rule_parts[0], "["), "]");
          if (!count($allowed_keys) || in_array($key, $allowed_keys)) {
            $control .= $prefix . "`" . $key . "`" . $operator;
            if ($rule_parts[1] == "<session>") {
              if (isset($this->session) && !empty($this->session) && isset($this->session->id)) {
                $control .= "'" . $this->session->id . "'";
              } else {
                $control .= "'0'";
              }
            } else {
              if (strpos($rule_parts[1], "[") === 0 && strpos($rule_parts[1], "]") === (strlen($rule_parts[1]) - 1)) {
                $control .= "`" . trim(trim($rule_parts[1], "["), "]") . "`";
              } else {
                $control .= $rule_parts[1];
              }
            }
          }
        }
        if (!empty($control)) {
          array_push($data, $control);
        }
        $index += 1;
      }
      if (count($data)) {
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

  private function validate_parameters($controls, $parameters = null) {
    $result = true;
    $key = "";
    if (isset($parameters) && !empty($parameters)) {
      if (!empty($controls) && is_array($controls) && count($controls)) {
        foreach($controls as $rule) {
          $operator = "";
          foreach($this::$comparisons as $comparison) {
            if (stristr(strtolower($rule), strtolower($comparison)) !== false) {
              $operator = $comparison;
            }
          }
          if (!empty($operator)) {
            $rule_parts = explode($operator, $rule);
            if (isset($rule_parts[1])) {
              if ($rule_parts[1] == "<session>") {
                $rule_key = trim(trim($rule_parts[0], "["), "]");
                if (isset($parameters[$rule_key])) {
                  $parameter = $parameters[$rule_key];
                  $key = $rule_key;
                  if (isset($this->session) && isset($this->session->id)) {
                    $rule_parts[1] = $this->session->id;
                  }
                  $compare = trim($rule_parts[1], "'");
                  if (
                    (
                      $operator == "=" && !($parameter == $compare)
                    ) || (
                      $operator == "!=" && !($parameter != $compare)
                    ) || (
                      $operator == ">" && !($parameter > $compare)
                    ) || (
                      $operator == "<" && !($parameter < $compare)
                    ) || (
                      $operator == ">=" && !($parameter >= $compare)
                    ) || (
                      $operator == "<=" && !($parameter <= $compare)
                    ) || (
                      $operator == "<>" && !($parameter <> $compare)
                    )
                  ) {
                    $result = false;
                  } else if ($operator == "LIKE" || $operator == "NOT LIKE") {
                    $compare_string = str_replace("%", "", $compare);
                    $siginificant = str_replace(
                      strtolower($compare_string), "", strtolower($parameter)
                    );
                    $not_like_condition = (
                      strpos(strtolower($parameter), strtolower($compare)) === false
                      || (
                        stristr($parameter, "%") === false
                        && !(strtolower($parameter) == strtolower($compare))
                      ) || (
                        strpos($parameter, "%") === 0
                        && strpos($parameter, "%") === Utilities::length($compare_string) - 1
                        && stristr(strtolower($parameter), $compare_string) === false
                      ) || (
                        strpos($parameter, "%") === 0
                        && (!(strpos(strtolower($parameter), $siginificant) === 0)) && !empty($siginificant)
                      ) || (
                        strpos($parameter, "%") === Utilities::length($compare_string) - 1
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
                    $compare_parts = explode(":", $rule_parts[1]);
                    $minimum = trim($compare_parts[0], "'");
                    $maximum = trim($compare_parts[1], "'");
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
            }
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

  function columns($table, $override_connection = null, $override_allowed_keys = array()) {
    if (count($override_allowed_keys)) {
      return $override_allowed_keys;
    } else {
      if (!empty($table)) {
  
        $data = array();
    
        $connection = $override_connection;
        if (empty($override_connection)) {
          $connection = $this->connect();
        }
    
        $sql = "
        SELECT * from INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = '" . $this->config["database_name"] . "'
        AND TABLE_NAME = '" . $table . "';";
        
        $error = false;
        if ($connection) {
          if ($result = mysqli_query($connection, $sql)) {
            $queries = array();
            while($query = $result->fetch_assoc()) {
              array_push($queries, $query);
            }
            $data = $queries;
            mysqli_free_result($result);
          } else {
            $error = true;
          }
          if (empty($override_connection)) {
            $this->disconnect($connection);
          }
        } else {
          $error = true;
        }
      
        if ($error) {
          throw new Exception($this->translation->translate("Database encountered error"), 500);
        }
  
        return $data;
        
      } else {
        throw new Exception($this->translation->translate("Not found"), 404);
        return null;
      }
    }
  }

  function clean($parameters, $allowed_keys) {
    if (count($allowed_keys)) {
      foreach($parameters as $key => $parameter) {
        if (!in_array($key, $allowed_keys)) {
          unset($parameters[$key]);
        }
      }
    }
    return $parameters;
  }

  private function escape_string($parameters, $override_connection = null) {
    $connection = $override_connection;
    if (empty($override_connection)) {
      $connection = $this->connect();
    }
    if ($connection) {
      if (isset($parameters) && !empty($parameters)) {
        if (is_array($parameters)) {
          foreach($parameters as $key => $value) {
            if (!empty($value)) {
              $parameters[$key] = $this->escape_string($value, $connection);
            }
          }
        } else {
          $parameters = mysqli_real_escape_string($connection, $parameters);
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