<?php

require_once("inc/init.php");
$user = new User();
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = new User($_REQUEST["user"]);
    if($user->save()) {
        $user_session = new UserSession($user->username, $user->password);
        $user_session->save();
        redirect_to("index");
    }
}
render("register", array("user" => $user));

