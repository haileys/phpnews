<?php

class LengthValidator {
    public function __construct($args) {
        $this->min = isset($args["min"]) ? $args["min"] : null;
        $this->max = isset($args["max"]) ? $args["max"] : null;
    }
    public function validate($value, $column, $model) {
        if($this->min != null && strlen($value) < $this->min) {
            $model->errors->add($column, "too short (minimum $this->min characters)");
        }
        if($this->max != null && strlen($value) > $this->max) {
            $model->errors->add($column, "too long (maximum $this->max characters)");
        }
    }
}

class InValidator {
    public function __construct($allowed) {
        $this->allowed = $allowed;
        if(isset($this->allowed["message"])) {
            $this->message = $this->allowed["message"];
            unset($this->allowed["message"]);
        }
    }
    public function validate($value, $column, $model) {
        if(!in_array($value, $this->allowed)) {
            $model->errors->add($column, isset($this->message) ? $this->message : "is invalid");
        }
    }
}

class ConfirmationValidator {
    public function validate($value, $column, $model) {
        $confirmation_column = "${column}_confirmation";
        if($value != $model->$confirmation_column) {
            $model->errors->add($confirmation_column, "does not match " . str_replace("_", " ", $column));
        }
    }
}

class UniquenessValidator {
    public function __construct($args) {
        if(is_array($args)) {
            $this->with = $args;
        } else {
            $this->with = array();
        }
    }
    
    public function validate($value, $column, $model) {
        $constraint = "`$column` = ?";
        $params = array($value);
        foreach($this->with as $with) {
            $constraint .= " AND `$with` = ?";
            $params[] = $model->$with;
        }
        $rows = $model->where($constraint, $params);
        if(!empty($rows)) {
            $model->errors->add($column, "is already taken");
        }
    }
}

class FormatValidator {
    private static $patterns = array(
        "alpha" => array(
            "regexp"    => "/^[a-z]*$/i",
            "message"   => "may only contain letters"
        ),
        "alphanum" => array(
            "regexp"    => "/^[a-z0-9]*$/i",
            "message"   => "may only contain letters or numbers"
        ),
        "ident" => array(
            "regexp"    => "/^[a-z0-9_]*$/i",
            "message"   => "may only contain letters, numbers or underscores"
        ),
    );
    
    public function __construct($args) {
        if(is_string($args)) {
            $this->regexp = self::$patterns[$args]["regexp"];
            $this->message = self::$patterns[$args]["message"];
        } else {
            $this->regexp = $args[0];
            $this->message = $args[1];
        }
    }
    
    public function validate($value, $column, $model) {
        if(!preg_match($this->regexp, $value)) {
            $model->errors->add($column, $this->message);
        }
    }
}

class UrlValidator {
    public function __construct($args) {
        if(empty($args)) {
            $args = array("http://", "https://");
        }
        $this->prefixes = $args;
    }
    public function validate($value, $column, $model) {
        foreach($this->prefixes as $prefix) {
            if(strpos($value, $prefix) === 0) {
                return;
            }
        }
        $model->errors->add($column, "doesn't look like a valid URL");
    }
}