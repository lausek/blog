<?php

require('Autoloader.php');

class Loader {

    const LOAD_PER_PAGE = 15;
    
    public function get_tag_count() {
      // Get table of tags with amount of occurrences in entries
      //SELECT name, COUNT(*)
      //FROM tagged, tags
      //WHERE tags.id = tagged.tags_id
      //GROUP BY tags_id
    }

    public function get_found_rows() {
        // Get amount of entries affected by the last SELECT (LIMIT is ignored)
        return (int) DBInterface::db()->query("SELECT found_rows()")->fetchColumn();
    }

    public function get_page_amount() {
        // Get amount of pages for the entries affected by the last SELECT
        return (int) ceil( $this->get_found_rows() / self::LOAD_PER_PAGE );
    }

	private function get_next_id() {
        // Get next ID from table
		$statement = DBInterface::db()->prepare("SHOW TABLE STATUS LIKE `entries`");
		$res = $statement->execute(PDO::FETCH_ASSOC);
		return $res["Auto_increment"];
	}

    public function group_by_key($key, $arr, $callback=NULL) {

      $result_arr = array();
      foreach($arr as $sub_arr) {
          $key_val = $sub_arr[$key];
    			if(is_callable($callback)){
    				$key_val = $callback($key_val);
    			}
          if(!array_key_exists($key_val, $result_arr)){
              $result_arr[$key_val] = array();
          }
          array_push($result_arr[$key_val], $sub_arr);
      }
      return $result_arr;

    }

    private function get_history($id) {
        // Get update history for table
        try {
            return DBInterface::exec_with_all("SELECT date, time, reason FROM editHistory WHERE entries_id = ?",
                                        [$id]);
        }catch(Exception $e) {
            return [];
        }
    }

    private function get_tags($id) {
        // Get tags for entry
        $stat = DBInterface::db()->prepare("SELECT t.name
        	                           FROM tags AS t, tagged AS a
                                       WHERE a.entries_id = :id
                                         AND t.id = a.tags_id");
        $stat->bindParam(":id", $id);

        $stat->execute();

        return $stat->fetchAll(PDO::FETCH_COLUMN);
    }

    public function get_meta($id) {
        // Get data for <meta>-tags
        return DBInterface::exec_with_single("SELECT name AS author, description, keywords
                                          FROM entries, user
                                          WHERE entries.id = ?
                                            AND entries.user_id = user.id",
                                        [$id]);
    }

    public function get_available_langs($id) {
        // Get languages available for entry-id `$id`
        return DBInterface::exec_with_all("SELECT l.*
                                    FROM entry_has_languages AS e, languages AS l
                                    WHERE e.entries_id = ?
                                      AND e.languages_id = l.id",
                                  [$id]);
    }

    public function get_editable_info($id) {
        $info = DBInterface::exec_with_single("SELECT title, description, keywords FROM entries WHERE id = ?", [$id]);

        $info['tags'] = implode(',', $this->get_tags($id));

        foreach($this->get_available_langs($id) as $lang) {
            $lang = $lang['id'];
            $info['entries'][$lang] = ltrim(file_get_contents("library/$id/$lang.txt"), "\xEF\xBB\xBF");
        }

        return $info;
    }

    public function view($id, $language) {

        $result = DBInterface::exec_with_single("SELECT entries.*
                                            FROM entries, entry_has_languages
                                            WHERE id = ?
                                            AND entries_id = entries.id
                                            AND languages_id = ?",
                                        [$id, $language]);

        $result["language"] = $language;
        $result["updates"] = $this->get_history($id);
        $result["tags"] = $this->get_tags($id);
        $result["available"] = $this->get_available_langs($id);

        return $result;

    }

    public function search($with, $page = 1, &$max_page = NULL) {

        // If $with contains # -> search in tags | else in keywords
        $words = [];
        $tags = [];

        $str = strtolower(preg_replace('/\s{2,}/', " ", trim($with))); // condense spaces

        if(empty($str)) return [];

        foreach(explode(" ", $str) as $val) {

          if(preg_match('/^#/', $val)){
            $tags[] = substr($val, 1);
          }else{
            $words[] = "%$val%"; // prepare for LIKE
          }

        }

        function repeat($times, $seq, $join = ",") {
            switch($times) {
                case 0: return "";
                case 1: return $seq;
                default: return implode($join, array_fill(0, $times, $seq));;
            }
        }

        // off, amount
        $param_array = array_merge($tags, $words, [($page-1) * self::LOAD_PER_PAGE, self::LOAD_PER_PAGE]);

        if(!empty($words) && !empty($tags)) {

            $data = DBInterface::exec_with_all("SELECT SQL_CALC_FOUND_ROWS v_overview.*
                                            FROM tags, tagged, v_overview
                                            WHERE tags.name IN (". repeat(count($tags), "?") .")
                                            AND tagged.tags_id = tags.id
                                            AND v_overview.id = tagged.entries_id
                                            AND (". repeat(count($words), "keywords LIKE ?", " AND ") .")
                                            LIMIT ?, ?",
                                        $param_array);

        }elseif(!empty($words)) {

            $data = DBInterface::exec_with_all("SELECT SQL_CALC_FOUND_ROWS v_overview.*
                                                FROM v_overview
                                                WHERE ". repeat(count($words), "keywords LIKE ?", " AND ") ."
                                                LIMIT ?, ?",
                                        $param_array);

        }elseif(!empty($tags)) {

            $data = DBInterface::exec_with_all("SELECT SQL_CALC_FOUND_ROWS v_overview.*
                                            FROM tags, tagged, v_overview
                                            WHERE tags.name IN (". repeat(count($tags), "?") .")
                                            AND tagged.tags_id = tags.id
                                            AND v_overview.id = tagged.entries_id
                                            LIMIT ?, ?",
                                        $param_array);

        }

        if($max_page !== NULL){
          $max_page = $this->get_page_amount();
        }

        return $data;

    }

    public function load($page, &$max_page = NULL) {

        if($page-1 < 0){
            throw new Exception("Invalid page: $page", 1);
        }

        $amount = self::LOAD_PER_PAGE;
        $off = ($page-1) * $amount;

        //SELECT entries.*, (SELECT MAX(date) FROM edithistory WHERE Entries_id = entries.id) AS `last_edit`
        //FROM entries
        //ORDER BY entries.date DESC, entries.id DESC
        $statement = DBInterface::db()->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM v_overview LIMIT :off, :amount");
        $statement->bindParam(":amount", $amount, PDO::PARAM_INT);
        $statement->bindParam(":off", $off, PDO::PARAM_INT);

        $statement->execute();

        if($max_page !== NULL){
          $max_page = $this->get_page_amount();
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

}
