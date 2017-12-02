<?php

spl_autoload_register(function($name) {
    if(strpos($name, "Twig") !== false) return;
    require(dirname(__FILE__)."$name.class.php");
});
