<?php

function config() {
    global $config;
    $args = func_get_args();
    $c = $config;
    foreach($args as $arg) {
        $c = $c[$arg];
    }
    return $c;
}

function current_user() {
    global $_current_user;
    if(isset($_current_user)) {
        return $_current_user;
    }
    return $_current_user = UserSession::current();
}

if(!isset($_SESSION["token"])) {
    $_SESSION["token"] = uniqid("", true);
}

function yield() {
    global $_subview;
    global $_view_data;
    if(isset($_subview)) {
        extract($_view_data);
        include "views/$_subview.phtml";
    }
}

function render($view, $view_data = array()) {
    header("Content-Type: text/html; charset=utf-8");
    global $_subview;
    global $_view_data;
    $_view_data = $view_data;
    if(file_exists("views/layout.phtml")) {
        $_subview = $view;
        extract($view_data);
        include "views/layout.phtml";
    } else {    
        extract($view_data);
        include "views/$view.phtml";
    }
}

function url_for($action, $opts = array(), $protect_from_forgery = false) {
    if($protect_from_forgery) {
        $opts["_authenticity_token"] = $_SESSION["_authenticity_token"];
    }
    $action .= ".php?";
    if(!empty($opts)) {
        foreach($opts as $k=>$v) {
            if($k == "#") continue;
            if(is_array($v)) {
                foreach($v as $vk=>$vv) {
                    $action .= urlencode($k) . "[" . urlencode($vk) . "]=" . urlencode($vv) . "&";
                }
            } else {
                $action .= urlencode($k) . "=" . urlencode($v) . "&";
            }
        }
    }
    $url = trim($action, "&?");
    if(isset($opts["#"])) {
        $url .= "#" . urlencode($opts["#"]);
    }
    return $url;
}

function plural($count, $singular, $plural = NULL) {
    if($plural === NULL) {
        $plural = "${singular}s";
    }
    if($count == 1) {
        return "$count $singular";
    } else {
        return "$count $plural";
    }
}

function time_ago_in_words($time) {
    $delta = time() - $time;
    if($delta < 60) {
        return plural($delta, "second");
    }
    if($delta < 60 * 60) {
        return plural(floor($delta / 60), "minute");
    }
    if($delta < 60 * 60 * 24) {
        return plural(floor($delta / 60 / 60), "hour");
    }
    return plural(floor($delta / 60 / 60 / 24), "day");
}

function tag($name, $opts, $self_closing = false) {
    echo "<";
    h($name);
    h(" ");
    foreach($opts as $k=>$v) {
        h($k);
        echo "='";
        h($v);
        echo "' ";
    }
    if($self_closing) {
        echo "/";
    }
    echo ">";
}

class FormHelper {
    public function __construct($object) {
        $this->object = $object;
        $this->model = $object->table_name();
    }
    public function label($name, $text = null) {
        if(!$text) {
            $text = ucwords(str_replace("_", " ", $name));
        }
        $model = $this->model;
        echo "<label for='${model}_$name'>$text</label>";
    }
    public function field($type, $name, $opts = array()) {
        tag("input", array_merge(array(
            "type"  => $type,
            "name"  => $this->model . "[" . $name . "]",
            "id"    => $this->model . "_" . $name,
            "value" => $this->object->$name), $opts), true);
    }
    public function text_field($name, $opts = array()) {
        $this->field("text", $name, $opts);
    }
    public function password_field($name, $opts = array()) {
        $this->field("password", $name, $opts);
    }
    public function hidden_field($name, $opts = array()) {
        $this->field("hidden", $name, $opts);
    }
    public function submit($text) {
        tag("input", array(
            "type"  => "submit",
            "value" => $text ? $text : "Submit"
        ), true);
    }
    public function text_area($name, $opts = array()) {
        tag("textarea", array_merge(array(
            "name"  => $this->model . "[" . $name . "]",
            "id"    => $this->model . "_" . $name), $opts), false);
        echo $this->object->$name;
        echo "</textarea>";
    }
    public function end() {
        echo "</form>";
    }
}

function form_for($object, $method, $url, &$form, $opts = array()) {
    tag("form", array_merge(array(
        "action" => url_for($url),
        "method" => $method), $opts), false);
    if(!is_idempotent_method($method)) {
        tag("input", array(
            "type"  => "hidden",
            "name"  => "_authenticity_token",
            "value" => $_SESSION["_authenticity_token"]
        ), true);
    }
    $form = new FormHelper($object);
}

function redirect_to($action, $opts = array(), $protect_from_forgery = false) {
    header("Location: " . url_for($action, $opts, $protect_from_forgery));
    exit;
}

function protect_from_forgery() {
    if( !isset($_REQUEST["_authenticity_token"])
        || empty($_REQUEST["_authenticity_token"])
        || $_REQUEST["_authenticity_token"] !== $_SESSION["_authenticity_token"]) {
        // bad csrf token
        redirect_to("index");
    }
}

function is_idempotent_method($method) {
    return strtoupper($method) == "GET" || strtoupper($method) == "HEAD";
}

function is_idempotent_request() {
    return is_idempotent_method($_SERVER["REQUEST_METHOD"]);
}

function h($str) {
    echo htmlspecialchars($str);
}

function link_to($text, $url, $opts = array(), $protect_from_forgery = false) {
    if(isset($opts["class"])) {
        $class = htmlspecialchars($opts["class"]);
    }
    unset($opts["class"]);
    echo "<a href='" . htmlspecialchars(url_for($url, $opts, $protect_from_forgery)) . "'";
    if(isset($class)) {
        echo " class='$class'";
    }
    echo ">";
    h($text);
    echo "</a>";
}