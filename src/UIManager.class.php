<?php

require('Twig/Autoloader.php');
require('Autoloader.php');

class UIManager {

    private $twig, $params = [];
    private static $loader, $own;

    public function __construct() {
        Twig_Autoloader::register();

        $fs = new Twig_Loader_Filesystem("templates");
        $this->twig = new Twig_Environment($fs, array(
            //'cache' => "{$_SERVER['DOCUMENT_ROOT']}/cache"
        ));

        $this->twig->addGlobal('app', [
            'cookie' => $_COOKIE,
            'get' => $_GET
        ]);

        $read_extension = new Twig_SimpleFilter('readfile', function($path) {
        	return ltrim(file_get_contents($path), "\xEF\xBB\xBF");
        });

        $this->twig->addFilter($read_extension);
    }

    public function assign($token, $var) {
        $this->params[$token] = $var;
    }

    public static function get_self() {
        return self::$own === null ? (self::$own = new self) : self::$own;
    }

    public static function get_loader() {
        return self::$loader === null ? (self::$loader = new Loader) : self::$loader;
    }

    public static function require_login() {

        self::get_self()->error("login");

    }

    public function load() {

        $this->render_overview("index", function($page, &$max_pages) {
            return self::get_loader()->load($page, $max_pages);
        });

    }

    public function search($query) {

        $query = trim($query);

        if(strlen($query) >= 2) {

            $this->render_overview("search", function($page, &$max_pages) use ($query) {
                return self::get_loader()->search($query, $page, $max_pages);
            },
            "error/research.html");

        }else{

            $this->error("research");

        }

    }

    public function view($id, $language) {

        function file_available($id, $language) {
            return file_exists("./library/$id/$language.txt");
        }

        if(!empty($id)) {

            try{

                $data = self::get_loader()->view($id, $language);

                $this->assign("post", $data);
                $this->assign("meta", self::get_loader()->get_meta($id));

                $this->render("view.html");

            }catch(Exception $e) {

                if(file_available($id, "en")) {
                    $this->error("maybe", ["exists" => "English", "link" => "view?id=$id&l=en" ]);
                }

            }

        }

        $this->error("missing");

    }

    public function render_overview($jump, $fun, $error_template = "base.html") {

        $max_pages = 0;
        $page = isset($_GET["p"]) && $_GET["p"] > 0
                    ? $_GET["p"]
                    : 1;

        try {

            $data = $fun($page, $max_pages);

            if(!empty($data)) {

                $data = self::get_loader()->group_by_key("date", $data);

                $this->assign("overview", $data);
                $this->assign("overview_page", $page);
                $this->assign("overview_max_page", $max_pages);
                $this->assign("overview_jump", $jump);

                $this->render("overview.html");

            }

        }catch(Exception $e) {
            // If an error occurs
        }

        $this->render($error_template);

    }

    public function about() {

        if(isset($_GET["about"]) && !empty($_GET["about"])){

            $about = strtolower($_GET["about"]);

            if(in_array($about, ["me", "projects", "faq", "contact", "imprint"])){
                self::render("fix/$about.html");
            }

        }

        self::error("missing");

    }

    public function error($err, $with = NULL) {

        if($with !== NULL) {
            $this->assign("$err", $with);
        }

        $this->render("error/$err.html");

    }

    public function render_authorized($template) {

        $user = DBInterface::get_user_object();

        if($user !== NULL) {

            $this->render($template);

        }else{

            $this->require_login();

        }

    }

    public function render($template, $content_type = "Content-Type: text/html; charset=utf-8") {

        header($content_type);

        /*
        $this->assign("content", $template);
        $this->loadFilter("output", "trimwhitespace");
        $this->display("../templates/main.tpl");
        */

        $view = $this->twig->load($template);

        echo $view->render($this->params);
        exit;

    }

}
