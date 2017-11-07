<?php

require('../Autoloader.php');

$user = DBInterface::get_user_object();

if($user !== null) {

    $info = Changer::fetch_set();

    try {

        /*
    	if(isset($_GET['id'])) {

    		(new Changer)->update($_GET['id'], $info);

    	} else { 

    		(new Changer)->add($info);

    	}
        */     

        (new Changer)->change($info, isset($_GET['id']) ? $_GET['id'] : null);

    } catch(Exception $e) {

        http_response_code(422);
        echo "ERROR;{$e->getMessage()}";
        exit;

    }
    

}
