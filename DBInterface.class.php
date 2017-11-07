<?php

abstract class DBInterface {

    // Don't use passwords on GitHub :-)
    const PDO_HOST = "localhost";
    const PDO_USER = "root";
    const PDO_PW = "";
    const PDO_DB = "lausek";

    protected static $pdo;

    public static function db() {
        if(self::$pdo === null) {
            try{
                self::$pdo = new PDO("mysql:host=".self::PDO_HOST.";dbname=".self::PDO_DB, self::PDO_USER, self::PDO_PW);
            }catch(PDOException $e){
                echo "Connection failed...";
                exit;
            }
        }
        return self::$pdo;
    }

    public function __wakeup() {
        self::db();
    }

    public static function exec_with_all($query, $binds) {

        return self::select($query, $binds)->fetchAll(PDO::FETCH_ASSOC);

    }

    public static function exec_with_single($query, $binds) {

        $res = self::select($query, $binds);

        return $res->fetch(PDO::FETCH_ASSOC);

    }

    private static function select($query, $binds) {

        $stat = self::db()->prepare($query);

        foreach($binds as $key => $val) {
            $stat->bindValue($key+1, $val, gettype($val) === "integer" ? PDO::PARAM_INT : PDO::PARAM_STR );
        }

        if(!$stat->execute() || $stat->rowCount() === 0) {
            throw new Exception("No results!", 1);
        }

        return $stat;

    }

    public static function get_user_object() {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(isset($_SESSION["user"])) {
            return unserialize($_SESSION["user"]);
        }

        if(isset($_POST["user"]) && isset($_POST["password"])) {

            try {

                $user = new User($_POST["user"], $_POST["password"]);

                session_destroy();
                session_start();

                $_SESSION["user"] = serialize($user);

                return $user;

            }catch(Exception $e) {

                // Password throttling

                if(!isset($_SESSION["tries"])){
                    $_SESSION["tries"] = 0;
                }

                $_SESSION["tries"] += 1;

                if($_SESSION["tries"] >= 5){
                  $_SESSION["blocked"] = time() + 20 * ( $_SESSION["tries"] - 5 );
                  echo "Blocked";
                  exit;
                }

            }

        }

        return null;

    }

}
