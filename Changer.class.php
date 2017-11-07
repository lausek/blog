<?php

require('Autoloader.php');

class Changer {

    private static function is_complete($name) {
        if(isset($_POST[$name]) && !empty($_POST[$name])) {
            return true;
        }
        throw new Exception("`$name` is not set or empty");
    }

    private static function delete_dir($dir) {

        if($handle = opendir($dir)) {

            while(false !== ($file = readdir($handle))) {

                $relative = $dir."/".$file;
                // . und .. überspringen
                if(preg_match('/^\.+$/', $file) == 0){

                    if(is_dir($relative)){

                      self::delete_dir($relative);

                    }else{

                      if(unlink($relative) === false){
                        throw new Exception("ERROR;".$e->getMessage());
                      }

                    }

                }

            }

            closedir($handle);
            rmdir($dir);

        } 
        
    }

    public static function fetch_set() {

        $info = [];

        self::is_complete("title");
        self::is_complete("description");
        self::is_complete("tags");
        self::is_complete("entry_en");

        // Put keyfields into object
        foreach($_POST as $key => $val){
            if(in_array($key, ["title", "description", "keywords", "reason"])) {

                $info[$key] = $val;

            } elseif($key === "tags") {

                $info["tags"] = explode(",", $val);

            } elseif(strpos($key, "entry_") === 0) {

                $lang = mb_substr($key, 6);
                $info["text"][$lang] = $val;

            }
        }

        // Move attachments to object
        foreach($_FILES as $file) {

            if($file["tmp_name"] !== ""){

                $info["assets"][$file["name"]] = $file["tmp_name"];

            }
        }

        $info["user_id"] = DBInterface::get_user_object()->id;

        return $info;

    }

    private function add_head($set) {

        // Insert Title, Date, Description and User into `Entries`
        $statement = DBInterface::db()->prepare("INSERT INTO entries (`date`,`title`,`keywords`,`description`,`user_id`) VALUES (CURDATE(), :title, :keywords, :descr, :uid)");
        $statement->bindParam(":title", $set["title"]);
        $statement->bindParam(":keywords", $set["keywords"]);
        $statement->bindParam(":descr", $set["description"]);
        $statement->bindParam(":uid", $set["user_id"]);

        if(!$statement->execute()){

            throw new Exception("INSERT on table `entries` failed (Query: {$statement->queryString})");

        }else{

            $statement = null;
            return DBInterface::db()->lastInsertId();

        }

    }

    private function add_tags($set) {

        // Insert Tags
        $tags = array_map('strtolower', $set["tags"]);
        $tag_filler = implode(',', array_fill(0, count($tags), '?'));

        $insert = DBInterface::db()->prepare("INSERT INTO tagged (`entries_id`, `tags_id`) SELECT ?, id FROM tags WHERE name IN ($tag_filler)");
        $insert->bindParam(1, $set["id"]);
        
        $i = 2;
        foreach($set["tags"] as $tag) {
            $insert->bindValue($i, $tag);
            $i++;
        }

        //TODO: Tags abgleichen

        if(!$insert->execute()) {
            throw new Exception("Couldn't insert into `tagged`");
        }

    }

    private function add_language($id, $lang) {
        $statement = DBInterface::db()->prepare("INSERT INTO `entry_has_languages` (`entries_id`, `languages_id`) VALUES (:id, :lang)");
        $statement->bindParam(":id", $id);
        $statement->bindParam(":lang", $lang);
        return $statement->execute();
    }

    private function write_dir($dir, $set, $zip) {

        foreach($set["text"] as $lang_key => $content){

            $path = "$dir$lang_key.txt";

            if(empty($content)){
              throw new Exception("Text for language `$lang_key` wasn't filled");
            }

            if(file_put_contents($path, $content) === false){
              throw new Exception("Couldn't write file `$path`");
            }

            if(!$this->add_language($set["id"], $lang_key)){
              throw new Exception("Couldn't add language `$lang_key` for entry");
            }

            $zip->addFile($path);

        }

    }   

    private function move_assets($dir, $assets, $zip) {

        if(mkdir($dir) === false){
            throw new Exception("Can't create directory `$dir`");
        }

        foreach($assets as $name => $path) {
            $libpath = "$dir$name";
            if(move_uploaded_file($path, $libpath) === false){
                throw new Exception("Cannot move file `$path` to directory `$dir`");
            }
            $zip->addFile($libpath); // -> path in archive
        }

    }

    private function add($set) {

        DBInterface::db()->beginTransaction();

        $set['id'] = $this->add_head($set);

        $this->add_tags($set);

        // Write files into Library
        $directory = "{$_SERVER['DOCUMENT_ROOT']}/library/{$set['id']}/";
        try {

            $zip = new ZipArchive();

            if(mkdir($directory) === false){
              throw new Exception("Directory `$directory` couldn't be created");
            }

            if($zip->open("{$_SERVER['DOCUMENT_ROOT']}/archive/{$set['id']}.zip", ZipArchive::CREATE) !== true) {
              throw new Exception("Couldn't create archive file for `$path`");
            }

            $this->write_dir($directory, $set, $zip);

            if(isset($set["assets"]) && !empty($set["assets"])) {

                $this->move_assets($directory."assets/", $set["assets"], $zip);
            
            }

            if($zip->close() === false){
                throw new Exception("Couldn't close archive file for `archive/$id.zip`");
            }

            DBInterface::db()->commit();

        } catch(Exception $e) {

            $zip = null;

            self::delete_dir($directory);

            DBInterface::db()->rollBack();

        }

        echo "SUCCESS;Entry was added with id `{$set['id']}`";

    }

    private function update($id, $set) {

    }

    public function change($info, $id = null) {

        /* TODO: Implement */
        if($id === null) {
            $this->add($info);
        } else {
            $this->update($id, $info);
        }

    }

}
