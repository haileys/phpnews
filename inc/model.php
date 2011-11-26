<?php

require_once("inc/validators.php");

class Model {
    private static $columns = array();
    private static $object_cache = array();
    private $data = array();
    public $errors;
    
    public function __construct($data = array()) {
        $this->data = array();
        $this->errors = new ModelErrors();
        $this->run_hook("before_initialize");
        if(!isset(self::$columns[get_called_class()])) {
            self::flush_column_cache();
        }
        foreach($data as $k => $v) {
            if(!isset($this->accessible) || in_array($k, $this->accessible)) {
                $this->data[$k] = $v;
            }
        }
        $this->run_hook("after_initialize");
    }
    
	public static function load($data) {
		$self = get_called_class();
		$obj = new $self;
        foreach($data as $k => $v) {
        	$obj->data[$k] = $v;
        }
		return $obj;
	}

    /* static methods */
    
    public static function flush_column_cache() {
        self::$columns[get_called_class()] = array();
        global $db;
        $table = self::table_name();
        foreach($db->query("SHOW COLUMNS FROM `$table`") as $column) {
            self::$columns[get_called_class()][] = $column["Field"];
        }
    }
    
    public static function class_name($table_name) {
        return str_replace(" ", "", ucwords(str_replace("_", " ", $table_name)));
    }
    
    public static function table_name() {
        return strtolower(preg_replace("/([a-z])([A-Z])/", '${1}_${2}', get_called_class()));
    }
    
    public static function execute_sql($sql, $params = array()) {
        global $db;
        $db->non_query($sql, $params);
    }
    
    public static function mass_initialize($rows) {
        $class = get_called_class();
        $objects = array();
        foreach($rows as $row) {
            $objects[] = $class::load($row);
        }
        return $objects;
    }
    
    public static function where($constraints, $params = array()) {
        global $db;
        if(!is_array($params)) {
            $params = func_get_args();
            array_shift($params);
        }
        $table = self::table_name();
        return self::mass_initialize($db->query("SELECT * FROM `$table` WHERE $constraints", $params));
    }
    
    public static function count($constraints = null, $params = array()) {
        global $db;
        if(!is_array($params)) {
            $params = func_get_args();
            array_shift($params);
        }
        $table = self::table_name();
        $where = $constraints ? "WHERE $constraints" : "";
        $result = $db->query("SELECT COUNT(id) as n FROM `$table` $where", $params);
        return $result[0]["n"];
    }
    
    public static function count_all($opts = array()) {
        $opts["select"] = "COUNT(*) AS n";
        $all = self::all($opts);
        return $all[0]->n;
    }
    
    public static function single($opts = array()) {
        $arr = self::all(array_merge($opts, array("limit" => 1)));
        return $arr[0];
    }
    
    public static function all($opts = array()) {
        global $db;
        $table = self::table_name();
        $params = array();
        
        $where_clause = "";
        $order_clause = "";
        $select_clause = "SELECT `$table`.*";
        $join_clause = "";
        
        foreach(array("select" => "SELECT", "join" => "", "where" => "WHERE", "order" => "ORDER BY", "limit" => "LIMIT") as $k => $clause) {
            if(isset($opts[$k])) {
                if(!is_array($opts[$k])) {
                    $opts[$k] = array($opts[$k]);
                }
                $c = "${k}_clause";
                $$c = "$clause " . array_shift($opts[$k]);
                $params = array_merge($params, $opts[$k]);
            }
        }
        
        return self::mass_initialize($db->query("$select_clause FROM `$table` $join_clause $where_clause $order_clause", $params));
    }
    
    public static function find($id) {
        if(!isset(self::$object_cache[get_called_class()])) {
            self::$object_cache[get_called_class()] = array();
        }
        if(isset(self::$object_cache[get_called_class()][$id])) {
            return self::$object_cache[get_called_class()][$id];
        }
        $results = self::where("id = ?", $id);
        return self::$object_cache[get_called_class()][$id] = (count($results) > 0 ? $results[0] : NULL);
    }
    
    public static function __callStatic($name, $args) {
        if(preg_match("/^find_by_([a-z_]+)$/", $name, $matches)) {
            $columns = explode("_and_", $matches[1]);
            $constraints = array();
            $params = array();
            foreach($columns as $column) {
                $param = array_shift($args);
                if($param === null) {
                    $constraints[] = "(`$column` IS NULL)";
                } else {
                    $constraints[] = "(`$column` = ?)";
                    $params[] = $param;
                }
            }
            $results = self::where(implode(" AND ", $constraints), $params);
            return count($results) > 0 ? $results[0] : NULL;
        }
        throw new Exception("nope");
    }
    
    /* instance methods */
    
    public function run_hook($hook) {
        if(!isset($this->$hook)) {
            return;
        }
        foreach($this->$hook as $h) {
            $this->$h();
        }
    }
    
    public function destroy() {
        if(!isset($this->data["id"])) {
            return;
        }
        $this->run_hook("before_destroy");
        global $db;
        $table = self::table_name();
        $db->non_query("DELETE FROM `$table` WHERE `id` = ?", array($this->data["id"]));
        $this->run_hook("after_destroy");
    }
    
    public function validate() {
        $this->errors = new ModelErrors();
        $this->run_hook("before_validate");
        if(!isset($this->validates)) {
            return;
        }
        foreach($this->validates as $column => $validators) {
            if(is_array($validators)) {
                if(isset($validators["if"])) {
                    if(!eval(sprintf("return (%s);", $validators["if"]))) {
                        continue;
                    }
                }
                if($column != "base" && !isset($this->data[$column])) {
                    $this->errors->add($column, "cannot be blank");
                } else {
                    foreach($validators as $k => $v) {
                        if($k == "if") continue;
                        if(is_numeric($k)) {
                            $this->$v();
                        } else {
                            $validator_name = str_replace(" ", "", ucwords(str_replace("_", " ", $k))) . "Validator";
                            $validator = new $validator_name($v);
                            $validator->validate($column != "base" ? $this->data[$column] : $this, $column, $this);
                        }
                    }
                }
            }
        }
        $this->run_hook("after_validate");
    }
    
    public function is_valid() {
        $this->validate();
        return $this->errors->is_empty();
    }
    
    public function save() {
        if(!$this->is_valid()) {
            return false;
        }
        
        $this->run_hook("before_save");
        $this->data["updated_at"] = time();
        
        global $db;
        $table = self::table_name();
        if(isset($this->data["id"]) && is_numeric($this->data["id"])) {
            $sets = array();
            $values = array();
            foreach(self::$columns[get_called_class()] as $column) {
                if($column != "id") {
                    $sets[] = "`$column` = ?";
                    $values[] = $this->data[$column];
                }
            }
            $sets = implode(", ", $sets);
            $values[] = $this->data["id"];
            $db->non_query("UPDATE `$table` SET $sets WHERE id = ?", $values);
        } else {
            $this->run_hook("before_create");
            $this->data["created_at"] = time();
            
            $columns = array();
            $placeholders = array();
            $values = array();
            foreach(self::$columns[get_called_class()] as $column) {
                if($column != "id" && isset($this->data[$column])) {
                    $columns[] = "`$column`";
                    $placeholders[] = "?";
                    $values[] = $this->data[$column];
                }
            }
            $columns = implode(", ", $columns);
            $placeholders = implode(", ", $placeholders);
            $db->non_query("INSERT INTO `$table` ($columns) VALUES ($placeholders)", $values);
            $this->data["id"] = $db->last_id();
            
            $this->run_hook("after_create");
        }
        
        $this->run_hook("after_save");
        
        return true;
    }
    
    public function __get($name) {
        if(isset($this->belongs_to)) {
            if(isset($this->belongs_to[$name])) {
                $other_model = self::class_name($this->belongs_to[$name]);
                $id_column = $name . "_id";
                if(!in_array($id_column, self::$columns[get_called_class()]) || !isset($this->data[$id_column])) {
                    return NULL;
                }
                return $other_model::find($this->data[$id_column]);
            }
            if(in_array($name, $this->belongs_to)) {
                $other_model = self::class_name($name);
                $id_column = "${name}_id";
                return $other_model::find($this->data[$id_column]);
            }
        }
        if(isset($this->has_many)) {
            $fetch_count = false;
            if(preg_match("/^(.*)_count$/", $name, $matches)) {
                $fetch_count = true;
                $name = $matches[1];
            }
            if(isset($this->has_many[$name])) {
                if(is_array($this->has_many[$name])) {
                    $other_model = self::class_name($this->has_many[$name][0]);
                    $opts = $this->has_many[$name];
                } else {
                    $other_model = self::class_name($this->has_many[$name]);
                    $opts = array();
                }
                $foreign_key = self::table_name() . "_id";
                if(isset($opts["foreign_key"])) {
                    $foreign_key = $opts["foreign_key"];
                }
                if(isset($opts["where"])) {
                    $where = $opts["where"];
                    if(!is_array($where)) {
                        $where = array($where);
                    }
                    $existing_sql = array_shift($where);
                    $sql = "`$foreign_key` = ? AND ($existing_sql)";
                    array_unshift($where, $this->data["id"]);
                    array_unshift($where, $sql);
                    $opts["where"] = $where;
                } else {
                    $opts["where"] = array("`$foreign_key` = ?", $this->data["id"]);
                }
                if($fetch_count) {
                    $name .= "_count";
                    return $other_model::count_all($opts);
                }
                return $other_model::all($opts);
            }
        }
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function __set($name, $value) {
        if(isset($this->belongs_to)) {
            if(isset($this->belongs_to[$name])) {
                $id_column = $this->belongs_to[$name] . "_id";
                if(is_object($value)) {
                    $this->data[$id_column] = $value->id;
                }
                return;
            }
            if(in_array($name, $this->belongs_to)) {
                $id_column = "${name}_id";
                if(is_object($value)) {
                    $this->data[$id_column] = $value->id;
                }
                return;
            }
        }
        $this->data[$name] = $value;
    }
}

class ModelErrors {
    public function __construct() {
        $this->errors = array();
    }
    public function add($k, $v) {
        if(!isset($this->errors[$k])) {
            $this->errors[$k] = array();
        }
        $this->errors[$k][] = $v;
    }
    public function is_empty() {
        return empty($this->errors);
    }
    public function full_messages() {
        $messages = array();
        foreach($this->errors as $k=>$a) {
            $k = ucwords(str_replace("_", " ", $k));
            foreach($a as $v) {
                if($k == "Base") {
                    $messages[] = $v;
                } else {
                    $messages[] = "$k $v";
                }
            }
        }
        return $messages;
    }
}