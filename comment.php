<?php

require_once "inc/init.php";

if(is_idempotent_request()) {
    $comment = Comment::find($_REQUEST["id"]);
    $reply = new Comment;
    $reply->story = $comment->story;
    $reply->parent = $comment;
    render("post", array("post" => $comment->post, "reply" => $reply, "top_level_comments" => array($comment), "single_thread" => true));
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $comment = new Comment($_REQUEST["comment"]);
    $comment->user = current_user();
    if($comment->save()) {
        redirect_to("post", array("id" => $comment->post->id, "#" => "comment_$comment->id"));
    } else {
        $errors = $comment->errors->full_messages();
        if($comment->parent === NULL) {
            render("post", array("post" => $comment->post, "comment" => $comment, "top_level_comments" => $comment->post->top_level_comments, "errors" => $errors));
        } else {
            render("post", array("post" => $comment->post, "reply" => $comment, "top_level_comments" => $comment->post->top_level_comments, "single_thread" => true, "errors" => $errors));
        }
    }
}