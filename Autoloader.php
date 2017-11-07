<?php

spl_autoload_register(function($name) {
    if(strpos($name, "Twig") !== false) return;
    if(file_exists("$name.class.php")) {
        require("$name.class.php");
    } elseif(file_exists("../$name.class.php")){
        require("../$name.class.php");
    }
});
