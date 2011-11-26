<?php

class Comment extends Model {
    protected $accessible = array("parent_id", "text", "post_id");
    protected $read_only = array("parent_id", "post_id");
    protected $belongs_to = array("user", "post", "parent" => "comment");
    protected $has_many = array("children" => array("comment", "order" => "points DESC", "foreign_key" => "parent_id"));
    
    protected $validates = array(
        "text"      => array("length" => array("min" => 10)),
        "post_id"   => array("validate_story"),
        "base"      => array("validate_parent"),
        "user"      => array("validate_user")
    );
    
    public function validate_user() {
        if($this->user === NULL) {
            $this->errors->add("user", "cannot be blank");
        }
    }
    
    public function validate_story() {
        if($this->post === NULL) {
            $this->errors->add("post", "is invalid");
        }
    }
    
    public function validate_parent() {
        if($this->parent_id === NULL) {
            return;
        }
        if($this->parent === NULL) {
            $this->errors->add("parent", "is invalid");
        }
        if($this->parent->post->id != $this->post->id) {
            $this->errors->add("parent", "must be in the same post");
        }
    }
}