<?php

require_once("inc/init.php");
$user_session = new UserSession();
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_session = new UserSession($_REQUEST["user_session"]);
    if($user_session->save()) {
        redirect_to("index");
    }
}
render("login", array("user_session" => $user_session));