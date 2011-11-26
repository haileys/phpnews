<?php

require_once("inc/init.php");
$post = Post::find($_REQUEST["id"]);
$comment = new Comment;
$comment->post = $post;
render("post", array("post" => $post, "comment" => $comment, "top_level_comments" => $post->top_level_comments));