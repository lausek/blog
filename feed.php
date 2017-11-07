<?php

require('Autoloader.php');

$view = new UIManager;
$view->assign("overview", (new Loader)->load(1));

header("Cache-Control: no-cache, must-revalidate");
//TODO: Add "expires"

if(isset($_GET["t"]) && $_GET["t"] === "atom"){

  $view->render("feed/atom.html", "Content-Type: application/atom+xml; charset=utf-8");

}else{

  $view->render("feed/rss.html", "Content-Type: application/rss+xml; charset=utf-8");

}
