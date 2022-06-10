<?php
namespace Abstracts;

use \Abstracts\Translation;

use Exception;

class Database {

  /* initialization */
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
    $fetch_type = "assoc"
  ) {
    
    $data = null;
    
    if ($this->controls["view"] || $controls_view) {

      $controls = $this->controls["view"];
      if ($controls_view || !empty($controls_view) || (is_array($controls_view) && count($controls_view))) {
        $controls = $controls_view;
      }

      $conditions = $this->condition($filters, $extensions, $controls);

      if (!empty($keys) && is_array($keys) && count($keys)) {
        $keys = "`" . implode("`, `", $keys) . "`";
      }

      $sql = "
      SELECT " . $keys . " 
      FROM `" . $table . "` 
      " . $conditions . "
      LIMIT 1;";
      
      $error = false;

      $connection = $this->connect();
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
        throw new Exception($this->translation->translate("Database encountered error"), 500);
      }

    } else {
      throw new Exception($this->translation->translate("Permisson denied"), 403);
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
    $fetch_type = "assoc"
  ) {
    
    $data = null;

    if ($this->controls["view"] || $controls_view) {

      $controls = $this->controls["view"];
      if ($controls_view || !empty($controls_view) || (is_array($controls_view) && count($controls_view))) {
        $controls = $controls_view;
      }

      $conditions = $this->condition($filters, $extensions, $controls);

      if (!empty($keys) && is_array($keys) && count($keys)) {
        $keys = "`" . implode("`, `", $keys) . "`";
      }

      $sql = "
      SELECT " . $keys . " 
      FROM `" . $table . "` 
      " . $conditions . "
      " . $this->limit($start, $limit) . "
      " . $this->order($sort_by, $sort_direction) . ";";

      $error = false;

      $connection = $this->connect();
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
      throw new Exception($this->translation->translate("Permisson denied"), 403);
    }
    
    return $data;

  }

  function count(
    $table,
    $filters = array(), 
    $extensions = array(), 
    $start = null,
    $limit = null,
    $controls_view = false
  ) {
    
    $data = 0;

    if ($this->controls["view"] || $controls_view) {

      $controls = $this->controls["view"];
      if ($controls_view || !empty($controls_view) || (is_array($controls_view) && count($controls_view))) {
        $controls = $controls_view;
      }

      $conditions = $this->condition($filters, $extensions, $controls);

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

      $connection = $this->connect();
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
      throw new Exception($this->translation->translate("Permisson denied"), 403);
    }
    
    return $data;

  }

  function insert(
    $table, 
    $parameters,
    $controls_create = false
  ) {
   
    $data = null;

    if ($this->controls["create"] || $controls_create) {

      $controls = $this->controls["create"];
      if ($controls_create || !empty($controls_create) || (is_array($controls_create) && count($controls_create))) {
        $controls = $controls_create;
      }

      if ($this->validate_parameters($controls, $parameters)) {
  
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
  
        $connection = $this->connect();
        if ($connection) {
          if (mysqli_query($connection, $sql)) {
            $id = mysqli_insert_id($connection);
            $data = $this->select($table, "*", array("id" => $id), null, $controls);
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
        throw new Exception($this->translation->translate("Permisson denied"), 403);
      }

    } else {
      throw new Exception($this->translation->translate("Permisson denied"), 403);
    }
      
    return $data;

  }

  function insert_multiple(
    $table, 
    $multiple_parameters,
    $controls_create = false
  ) {
   
    $data = null;

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

        foreach($multiple_parameters as $parameters) {

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
  
        $connection = $this->connect();
        if ($connection) {
          if (mysqli_multi_query($connection, $sql)) {
            $id = mysqli_insert_id($connection);
            $data = $this->select($table, "*", array("id" => $id), null, $controls);
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
        throw new Exception($this->translation->translate("Permisson denied"), 403);
      }

    } else {
      throw new Exception($this->translation->translate("Permisson denied"), 403);
    }
      
    return $data;

  }

  function update(
    $table, 
    $parameters,
    $filters = array(), 
    $extensions = array(), 
    $controls_update = false
  ) {
    
    $data = null;
    
    if ($this->controls["update"] || $controls_update) {

      $controls = $this->controls["update"];
      if ($controls_update || !empty($controls_update) || (is_array($controls_update) && count($controls_update))) {
        $controls = $controls_update;
      }
      
      $conditions = $this->condition($filters, $extensions, $controls);

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
      
      $data_current = $this->select_multiple($table, "*", $filters, $extensions, null, null, null, null, $controls);
      
      $sql = "
      UPDATE `" . $table . "`
      SET " . implode(", ", $sets) . "
      " . $conditions . ";";
      
      $error = false;

      $connection = $this->connect();
      if ($connection) {
        if (mysqli_query($connection, $sql)) {
          $data = $this->select_multiple($table, "*", $filters, $extensions, $controls, null, null, null, $controls);
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
      throw new Exception($this->translation->translate("Permisson denied"), 403);
    }
    
    return $data;

  }

  function update_multiple(
    $table, 
    $parameters,
    $multiple_filters = array(), 
    $extensions = array(), 
    $controls_update = false
  ) {
    
    $data = null;
    
    if ($this->controls["update"] || $controls_update) {

      $controls = $this->controls["update"];
      if ($controls_update || !empty($controls_update) || (is_array($controls_update) && count($controls_update))) {
        $controls = $controls_update;
      }

      $data_current = array();

      $sql = "";

      foreach($multiple_filters as $filters) {
      
        $conditions = $this->condition($filters, $extensions, $controls);

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
        
        array_push($this->select_multiple($data_current, $table, "*", $filters, $extensions, null, null, null, null, $controls));
        
        $sql .= "
        UPDATE `" . $table . "`
        SET " . implode(", ", $sets) . "
        " . $conditions . ";";

      }
      
      $error = false;
  
      $connection = $this->connect();
      if ($connection) {
        if (mysqli_multi_query($connection, $sql)) {
          $data = array();
          foreach($multiple_filters as $filters) {
            array_push($data, $this->select_multiple($table, "*", $filters, $extensions, $controls, null, null, null, $controls));
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
      throw new Exception($this->translation->translate("Permisson denied"), 403);
    }
    
    return $data;

  }

  function delete(
    $table, 
    $filters = array(), 
    $extensions = array(), 
    $controls_delete = false
  ) {
    
    $data = null;

    if ($this->controls["delete"] || $controls_delete) {

      $controls = $this->controls["delete"];
      if ($controls_delete || !empty($controls_delete) || (is_array($controls_delete) && count($controls_delete))) {
        $controls = $controls_delete;
      }
      
      $conditions = $this->condition($filters, $extensions, $controls);

      $data_current = $this->select_multiple($table, "*", $filters, $extensions, null, null, null, null, $controls);
      if (!empty($data_current) && count($data_current)) {

        $sql = "
        DELETE FROM `" . $table . "`
        " . $conditions . ";";
  
        $error = false;
  
        $connection = $this->connect();
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
      throw new Exception($this->translation->translate("Permisson denied"), 403);
    }
    
    return $data;

  }

  function delete_multiple(
    $table, 
    $multiple_filters = array(), 
    $extensions = array(), 
    $controls_delete = false
  ) {
    
    $data = null;

    if ($this->controls["delete"] || $controls_delete) {

      $controls = $this->controls["delete"];
      if ($controls_delete || !empty($controls_delete) || (is_array($controls_delete) && count($controls_delete))) {
        $controls = $controls_delete;
      }

      $data_current = array();

      $sql = "";

      foreach($multiple_filters as $filters) {
      
        $conditions = $this->condition($filters, $extensions, $controls);

        array_push($data_current, $this->select_multiple($table, "*", $filters, $extensions, null, null, null, null, $controls));

        $sql .= "
        DELETE FROM `" . $table . "`
        " . $conditions . ";";

      }

      $error = false;

      $connection = $this->connect();
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
      throw new Exception($this->translation->translate("Permisson denied"), 403);
    }
    
    return $data;

  }

  function limit($start = null, $limit = null) {
    $data = "";
    if (isset($limit) && $limit != "") {
      if (isset($start) && $start != "" && !empty($limit)) {
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

  function condition($filters = array(), $extensions = array(), $controls = array()) {

    $data = "";

    $filters_is_set = !empty($filters) && is_array($filters) && count($filters);
    $extensions_is_set = !empty($extensions) && is_array($extensions) && count($extensions);
    $controls_is_set = !empty($controls) && is_array($controls) && count($controls);
    $filters_arranged = array();
    if ($filters_is_set) {
      $filters_arranged = $this->arrange_filters($filters);
    }
    $extensions_arranged = array();
    if ($extensions_is_set) {
      $extensions_arranged = $this->arrange_extensions($extensions);
    }
    $controls_arranged = array();
    if ($controls_is_set) {
      $controls_arranged = $this->arrange_controls($controls);
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

  private function arrange_filters($filters = array()) {
    $data = array();
    if (isset($filters) && !empty($filters) && is_array($filters) && count($filters)) {
      foreach($filters as $key => $value) {
        if (isset($value)) {
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

  private function arrange_extensions($extensions = array()) {

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
            $trimmed = trim($extensions[$i]["value"], "\'");
            if (strlen($trimmed) < strlen($extensions[$i]["value"])) {
              $value = "'" . $trimmed . "'";
            } else {
              $value = $trimmed;
            }
            array_push($data, 
              (trim($extensions[$i]["conjunction"]) ? trim($extensions[$i]["conjunction"]) . " " : "")
              . $extensions[$i]["key"] . " " 
              . $extensions[$i]["operator"] . " " 
              . $value
            );
          }
        }
      }
      return array(
        "(" . implode(" ", $data) . ")"
      );
    } else {
      return array();
    }

  }

  private function arrange_controls($controls) {
    
    $data = array();

    if (isset($controls) && !empty($controls) && is_array($controls) && count($controls)) {
      for ($i = 0; $i < count($controls); $i++) {
        $control = "";
        $rules = explode(",", $controls[$i]);
        for ($j = 0; $j < count($rules); $j++) {
          $operator = null;
          if (strpos($rules[$j], "=") !== false) {
            $operator = "=";
          } else if (strpos($rules[$j], "!=") !== false) {
            $operator = "!=";
          } else if (strpos($rules[$j], ">") !== false) {
            $operator = ">";
          } else if (strpos($rules[$j], "<") !== false) {
            $operator = "<";
          } else if (strpos($rules[$j], ">=") !== false) {
            $operator = ">=";
          } else if (strpos($rules[$j], "<=") !== false) {
            $operator = "<=";
          } else if (strpos($rules[$j], "<>") !== false) {
            $operator = "<>";
          } else if (strpos($rules[$j], "LIKE") !== false) {
            $operator = "LIKE";
          }
          if ($operator) {
            $prefix = " AND ";
            if ($j == 0) {
              $prefix = "";
            }
            $rule_parts = explode($operator, $rules[$j]);
            $control .= $prefix . "`" . trim(trim($rule_parts[0], "["), "]") . "`" . $operator;
            if (
              $rule_parts[1] == "<session>"
            ) {
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
        array_push($data, $control);
      }
      return array(
        "(" . implode(" OR ", $data) . ")"
      );
    } else {
      return array();
    }
    
  }

  private function validate_parameters($controls, $parameters = null) {
    $result = false;
    if (isset($parameters) && !empty($parameters)) {
      if ($controls !== true) {
        if (is_array($controls)) {
          $controls_validated = 0;
          for ($i = 0; $i < count($controls); $i++) {
            $rules = explode(",", $controls[$i]);
            for ($j = 0; $j < count($rules); $j++) {
              $operator = null;
              if (strpos($rules[$j], "=") !== false) {
                $operator = "=";
              } else if (strpos($rules[$j], "!=") !== false) {
                $operator = "!=";
              } else if (strpos($rules[$j], ">") !== false) {
                $operator = ">";
              } else if (strpos($rules[$j], "<") !== false) {
                $operator = "<";
              } else if (strpos($rules[$j], ">=") !== false) {
                $operator = ">=";
              } else if (strpos($rules[$j], "<=") !== false) {
                $operator = "<=";
              } else if (strpos($rules[$j], "<>") !== false) {
                $operator = "<>";
              } else if (strpos($rules[$j], "LIKE") !== false) {
                $operator = "LIKE";
              }
              if ($operator) {
                $rule_parts = explode($operator, $rules[$j]);
                if ($parameters[$rule_parts[0]]) {
                  $part_a = $parameters[$rule_parts[0]];
                  if (
                      $rule_parts[1] == "<session>"
                      && isset($session)
                      && !is_null($session)
                      && isset($session["id"])
                  ) {
                    $part_b = $session["id"];
                  } else {
                    if (strpos($rule_parts[1], "[") == 0 && strpos($rule_parts[1], "]") == (strlen($rule_parts[1]) - 1)) {
                      $part_b = $parameters[trim(trim($rule_parts[1], "["), "]")];
                    } else if (strpos($rule_parts[1], "'") == 0 && strpos($rule_parts[1], "'") == (strlen($rule_parts[1]) - 1)) {
                      $part_b = trim($rule_parts[1], "'");
                    } else {
                      $part_b = $rule_parts[1];
                    }
                  }
                  if (
                    ($operator == "=" && $part_a == $part_b)
                    || ($operator == "!=" && $part_a != $part_b)
                    || ($operator == ">" && $part_a > $part_b)
                    || ($operator == "<" && $part_a < $part_b)
                    || ($operator == ">=" && $part_a >= $part_b)
                    || ($operator == "<=" && $part_a <= $part_b)
                    || ($operator == "<>" && $part_a <> $part_b)
                    || ($operator == "LIKE" && strpos($part_a, $part_b) !== false)
                  ) {
                    $controls_validated = $controls_validated + 1;
                  }
                }
              }
            }
          }
          if ($controls_validated <= 0) {
            $result = true;
          } else {
            $result = false;
          }
        } else {
          $result = false;
        }
      } else {
        $result = true;
      }
    } else {
      $result = true;
    }
    $result = true;
    if (!$result) {
      throw new Exception($this->translation->translate("Some parameters is not allowed"), 403);
    }
    return $result;
  }

}