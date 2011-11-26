<?php

require_once "inc/init.php";

$post = new Post(isset($_REQUEST["post"]) ? $_REQUEST["post"] : array());

if(is_idempotent_request()) {
    render("submit", array("post" => $post, "submit_page" => true));
} else {
    $post->user = current_user();
    if($post->save()) {
        redirect_to("post", array("id" => $post->id));
    } else {
        render("submit", array("post" => $post, "errors" => $post->errors->full_messages(), "submit_page" => true));
    }
}

