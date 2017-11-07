<?php

require('Autoloader.php');

if(!isset($_GET["e"])) {

    header("Location: /");

}else{

    try {
        (new UIManager)->error($_GET["e"]);
    }catch(Exception $e) {
        (new UIManager)->error("missing");
    }

}
