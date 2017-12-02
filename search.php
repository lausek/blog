<?php

require('src/Autoloader.php');

(new UIManager)->search( isset($_GET["q"]) ? $_GET["q"] : "" );

