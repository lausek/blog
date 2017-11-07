<?php

require('Autoloader.php');

(new UIManager)->search( isset($_GET["q"]) ? $_GET["q"] : "" );

