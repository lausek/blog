<?php

require('Autoloader.php');

(new UIManager)->view( isset($_GET["id"]) ? $_GET["id"] : "",
                        isset($_GET["l"]) ? $_GET["l"] : "en" );
