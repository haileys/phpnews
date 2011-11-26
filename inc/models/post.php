<?php

class Post extends Model {
    const RECENT_STORIES = 1000;
    const PER_PAGE = 20;
    const GRAVITY = 1.8;
    
    protected $belongs_to = array("user");
    protected $has_many = array(
        "top_level_comments" => array("comment", "where" => "parent_id IS NULL", "order" => "points DESC"),
        "comments" => "comment"
    );
    
    protected $validates = array(
        "title" => array("length" => array("min" => 10, "max" => 200)),
        "either_url_or_text",
        "url" => array("url" => array(), "if" => '$this->url'),
        "text" => array("length" => array("min" => 10), "if" => '$this->text')
    );
    
    public function validate_user() {
        if($this->user === NULL) {
            $this->errors->add("user", "cannot be blank");
        }
    }
    
    public function either_url_or_text() {
        if(!$this->url && !$this->text) {
            $this->errors->add("base", "Either url or text must be filled in");
        }
    }
    
    protected $accessible = array("title", "url", "text");
    
    public static function hot($limit = Post::PER_PAGE) {
        $count = Post::count();
        return Post::all(array(
            "where" => array("id > ?", $count - Post::RECENT_STORIES),
            "order" => "hotness DESC",
            "limit" => $limit
        ));
    }
    
    public static function best($limit = Post::PER_PAGE) {
        return Post::all(array(
            "order" => "points DESC",
            "limit" => $limit
        ));
    }
    
    public static function latest($limit = Post::PER_PAGE) {
        return Post::all(array(
            "order" => "created_at DESC",
            "limit" => $limit
        ));
    }
    
    public static function recalculate_hotness() {
        $time = time();
        $hour_age = "(($time) - created_at) / 3600";
        $gravity = Post::GRAVITY;
        $min =  Post::count() - Post::RECENT_STORIES;
        Post::execute_sql("UPDATE `post` SET hotness = ((points - 1)/pow($hour_age, $gravity)) WHERE id > $min");
    }

    public function recalculate_points() {
        $this->points = Vote::single(array("select" => "SUM(value) AS sum", "where" => array("post_id = ?", $this->id)))->sum;
        $this->save();
    }

    public function link_for() {
        if($this->url) {
            return $this->url;
        } else {
            return url_for("post", array("id" => $this->id));
        }
    }
    
    public function domain() {
        if($this->url) {
            preg_match('=://(www\\.)?([^/]+)=', $this->url, $matches);
            return "($matches[2])";
        }
        return "";
    }
}