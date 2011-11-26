<?php

require_once("inc/init.php");

$sort = isset($_REQUEST["s"]) ? $_REQUEST["s"] : "";
if(!in_array($sort, array("hot", "latest", "best"))) {
    $sort = "hot";
}
$posts = Post::$sort();
render("index", array("posts" => $posts, "start" => 1, "sort_order" => $sort));