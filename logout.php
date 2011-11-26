<?php

require_once("inc/init.php");
protect_from_forgery();
UserSession::destroy();
redirect_to("index");