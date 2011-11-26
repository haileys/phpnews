<?php

session_start();
error_reporting(-1);

require_once("inc/config.php");
require_once("inc/db.php");

$db = new DB($config["db"]["dsn"], $config["db"]["user"], $config["db"]["pass"]);

require_once("inc/model.php");
foreach(glob("inc/models/*.php") as $file) {
    require_once $file;
}

if(!isset($_SESSION["_authenticity_token"])) {
    $_SESSION["_authenticity_token"] = uniqid("", true);
}

require_once("inc/helpers.php");

if(!is_idempotent_request()) {
    protect_from_forgery();
}