<?php

require('Autoloader.php');

$ui = new UIManager;

if(isset($_GET["id"])) {
    $ui->assign("info", (new Loader)->get_editable_info($_GET['id']));
}

$ui->render_authorized("interface/editor.html");
