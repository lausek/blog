<?php

require('Autoloader.php');

class User extends DBInterface {

    public $id, $name;

    public function __construct($user, $pw) {
        $this->load_user($user, $pw);
    }

    private function load_user($user, $pw) {

        $res = DBInterface::exec_with_single("SELECT id FROM user WHERE name = ? AND password = PASSWORD(?)",
                                            [$user, $pw]);

        $this->id = $res["id"];
        $this->user = $user;

    }

    public function __sleep() {
        return ["id", "name"];
    }

}