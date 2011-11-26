<?php

require_once("inc/init.php");
protect_from_forgery();
$vote = new Vote($_REQUEST["vote"]);
$vote->user = current_user();
$vote->save();
print_r($vote->errors->full_messages());