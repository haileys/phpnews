<?php

class User extends Model {
    protected $accessible = array("username", "password", "password_confirmation");
    
    protected $has_many = array("post");
    
    protected $validates = array(
        "username" => array("format" => "ident", "uniqueness" => true),
        "password" => array("confirmation" => true, "length" => array("min" => 4), "if" => '$this->password || !$this->crypted_password')
    );
    
    protected $before_validate = array("downcase_username");
    function downcase_username() {
        $this->username = strtolower($this->username);
    }
    
    protected $before_save = array("create_salt", "crypt_password");
    function create_salt() {
        if(!isset($this->salt)) {
            $this->salt = uniqid("", true);
        }
    }
    
    public function recalculate_karma() {
        $this->karma = Post::single(array("select" => "SUM(points) AS sum", "where" => array("user_id = ?", $this->id)))->sum
                  + Comment::single(array("select" => "SUM(points) AS sum", "where" => array("user_id = ?", $this->id)))->sum;
        $this->save();
    }
    
    function crypt_password() {
        $this->crypted_password = self::hash($this->password, $this->salt);
    }
    
    public function verify_password($password) {
        return self::hash($password, $this->salt) == $this->crypted_password;
    }
    
    public static function hash($str, $salt) {
        for($i = 0; $i < 1000; $i++) {
            $str = hash_hmac("sha512", $str, $salt);
            $salt = hash_hmac("sha512", $salt, $str);
        }
        return $str;
    }
}

