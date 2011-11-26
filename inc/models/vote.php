<?php

class Vote extends Model {
    protected $accessible = array("post_id", "comment_id", "value");
    protected $belongs_to = array("post", "comment", "user");
    
    protected $validates = array(
        "value" => array("in" => array(-1, 1)),
        "base"  => array("validate_user"),
        "either_post_or_comment"
    );
    protected $before_save = array("delete_existing_vote");
    protected $after_save = array("recalculate_points");
    
    public function validate_user() {
        print_r($this);
        if(!$this->user_id) {
            $this->errors->add("user", "cannot be blank");
        }
    }
    
    public function entity() {
        if($this->post) {
            return $this->post;
        } else {
            return $this->comment;
        }
    }
    
    public function delete_existing_vote() {
        $vote = Vote::find_by_user_id_and_post_id_and_comment_id($this->user_id, $this->post_id, $this->comment_id);
        if($vote) {
            $vote->destroy();
        }
    }
    
    public function recalculate_points() {
        $this->entity()->recalculate_points();
        $this->entity()->user->recalculate_karma();
        Post::recalculate_hotness();
    }
    
    public function either_post_or_comment() {
        if(!$this->post && !$this->comment) {
            $this->errors->add("base", "Vote must be on either a post or a comment");
        }
        if($this->post && $this->comment) {
            $this->errors->add("base", "Vote can't be for both a post and a comment");
        }
    }
}