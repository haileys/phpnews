<?php

class UserSession {
    private static $current;
    public $username;
    public $password;
    
    public static function current() {
        if(isset(self::$current)) {
            return self::$current;
        }
        if(isset($_SESSION["user_id"])) {
            return self::$current = User::find($_SESSION["user_id"]);
        }
        return self::$current = null;
    }
    
    public function __construct($data = array()) {
        $this->errors = new ModelErrors;
        foreach($data as $k => $v) {
            $this->$k = $v;
        }
    }
    
    public function save() {
        $u = User::find_by_username($this->username);
        if(!$u) {
            $this->errors->add("base", "Incorrect username or password");
            return false;
        }
        if(!$u->verify_password($this->password)) {
            $this->errors->add("base", "Incorrect username or password");
            return false;
        }
        global $_current_user;
        $_current_user = $u;
        $_SESSION["user_id"] = $u->id;
        return true;
    }
    
    public static function table_name() {
        return "user_session";
    }
    
    public function destroy() {
        session_destroy();
    }
}