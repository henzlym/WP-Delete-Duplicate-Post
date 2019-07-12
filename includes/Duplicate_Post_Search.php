<?php
class Duplicate_Post_Search
{
    public function __construct()
    {
        add_action('init', array($this, 'set_session'), 1);
        add_action("wp_ajax_search_duplicate_posts", array($this, 'search_duplicate_posts'));
        add_action("wp_ajax_scan_duplicate_posts", array($this, 'scan_duplicate_posts'));
    }

    public function set_session()
    {

        if (!session_id()) {
            session_start();
            // now you can load your library that use $_SESSION
        }
    }
    public function total_post_count()
    {
        global $wpdb;
        $sql = "SELECT
            COUNT(post_content) post_count
        FROM
        {$wpdb->prefix}posts
        WHERE
            post_type = 'post' AND post_status = 'publish'
        GROUP BY
            post_content,
            post_title
        HAVING
            COUNT(post_content) > 1";

        //error_log(print_r($sql, true));

        $results = $wpdb->get_results($sql);
        // error_log(print_r($results[0]->totalRows, true));
        return $results;
    }

    public function delete_duplicate_posts($posts, $force_delete,$limit)
    {
        global $wpdb;

        $delete_post_contents = array();
        $delete_ids = array();
        $delete_post_title = array();

        if (isset($posts[0]) && is_array($posts[0])) {
            foreach ($posts as $key => $post) {
                if (is_array($post)) {
                    $delete_post_contents[] = $post['post_content'];
                    $delete_ids[] = $post['ID'];
                    $delete_post_title[] = $post['post_title'];
                }
            }
        }

        if (!empty($delete_ids) && !empty($delete_post_contents)) {
            $placeholder_post_contents = implode(', ', array_fill(0, count($delete_post_contents), '%s'));
            $placeholder_post_title = implode(', ', array_fill(0, count($delete_post_title), '%s'));
            $placeholder_ids = implode(', ', array_fill(0, count($delete_ids), '%d'));
            $params = array_merge($delete_post_contents, $delete_post_title, $delete_ids,array($limit));
        }

        $sql = "SELECT a.ID
            FROM {$wpdb->prefix}posts a
            WHERE a.post_type = 'post' 
            AND a.post_status = 'publish'
            AND a.post_content IN ($placeholder_post_contents) 
            AND a.post_title IN ($placeholder_post_title) 
            AND a.ID NOT IN ($placeholder_ids) LIMIT %d";


        $sql = $wpdb->prepare($sql, $params);

        // error_log(print_r($sql, true));

        $posts_to_delete = $wpdb->get_results($sql);
        
        $post_delete_count = 0;

		if ( ! empty( $posts_to_delete ) ){
            error_log(print_r("Started deletion of posts", true));
			foreach ($posts_to_delete as $key => $post_to_delete ) {

                //if(($key + 1) == $limit) break;

                $deleted = isset($post_to_delete->ID) ? true : false;
                $deleted = wp_delete_post( $post_to_delete->ID, $force_delete );
                if($deleted){
                    error_log(print_r("$key: Deleted Post: $post_to_delete->ID", true));
                }
			}
			$post_delete_count = count( $posts_to_delete );

        }

		return $post_delete_count;
    }
    public function find_duplicate($posts, $group = true, $limit = 20)
    {
        
        global $wpdb;

        $wpdb->flush();

        $post_contents = "";
        $post_ids = "";
        $post_title = "";

        $delete_post_contents = array();
        $delete_ids = array();
        $delete_post_title = array();

        if (isset($posts[0]) && is_array($posts[0])) {
            foreach ($posts as $key => $post) {
                if (is_array($post)) {
                    $post_contents .= "'" . $post['post_content'] . "',";
                    $post_ids .= $post['ID'] . ",";
                    $post_title .=  "'" . $post['post_title'] . "',";

                    $delete_post_contents[] = $post['post_content'];
                    $delete_ids[] = $post['ID'];
                    $delete_post_title[] = $post['post_title'];
                }
            }

            $post_contents = rtrim($post_contents, ",");
            $post_ids = rtrim($post_ids, ",");
            $post_title = rtrim($post_title, ",");
        } else {
            $post_contents = $posts['post_content'];
            $post_ids = $posts['ID'];
            $post_title = $posts['post_title'];
        }

        if (!empty($delete_ids) && !empty($delete_post_contents)) {
            $placeholder_post_contents = implode(', ', array_fill(0, count($delete_post_contents), '%s'));
            $placeholder_post_title = implode(', ', array_fill(0, count($delete_post_title), '%s'));
            $placeholder_ids = implode(', ', array_fill(0, count($delete_ids), '%d'));
            $params = array_merge($delete_post_contents, $delete_post_title, $delete_ids);
        } else {
            $placeholder_post_contents = '%s';
            $placeholder_ids = '%d';
            $params = array($post_contents, $post_title, $post_ids);
        }

        $sql = "SELECT a.*
            FROM {$wpdb->prefix}posts a
            WHERE a.post_type = 'post' 
            AND a.post_status = 'publish'
            AND a.post_content IN ($placeholder_post_contents) 
            AND a.post_title IN ($placeholder_post_title) 
            AND a.ID NOT IN ($placeholder_ids)";


        $sql = $wpdb->prepare($sql, $params);

        //error_log(print_r($sql, true));
        // exit;

        $results = $wpdb->get_results($sql);
        $number_of_posts = count($results);

        //error_log(print_r($number_of_posts, true));

        if (is_array($results) && count($results) > 0) {

            // $sql = "SELECT a.*,b.*,c.*
            // FROM {$wpdb->prefix}posts a
            // LEFT JOIN {$wpdb->prefix}term_relationships b ON ( a.ID = b.object_id )
            // LEFT JOIN {$wpdb->prefix}postmeta c ON ( a.ID = c.post_id )
            // WHERE a.post_type = 'post' 
            // AND a.post_status = 'publish' 
            // AND a.post_content IN ($placeholder_post_contents) 
            // AND a.post_title IN ($placeholder_post_title) 
            // AND a.ID NOT IN ($placeholder_ids)";

            // $sql = "SELECT a.*
            // FROM {$wpdb->prefix}posts a
            // -- LEFT JOIN {$wpdb->prefix}term_relationships b ON ( a.ID = b.object_id )
            // -- LEFT JOIN {$wpdb->prefix}postmeta c ON ( a.ID = c.post_id )
            // WHERE a.post_type = 'post' 
            // AND a.post_status = 'publish' 
            // AND a.post_content IN ($placeholder_post_contents) 
            // AND a.post_title IN ($placeholder_post_title) 
            // AND a.ID NOT IN ($placeholder_ids)";


            $sql = "DELETE a
            FROM {$wpdb->prefix}posts a
            -- LEFT JOIN {$wpdb->prefix}term_relationships b ON ( a.ID = b.object_id )
            -- LEFT JOIN {$wpdb->prefix}postmeta c ON ( a.ID = c.post_id )
            WHERE a.post_type = 'post' 
            AND a.post_status = 'publish'
            AND a.post_content IN ($placeholder_post_contents) 
            AND a.post_title IN ($placeholder_post_title) 
            AND a.ID NOT IN ($placeholder_ids)";

            $sql = $wpdb->prepare($sql, $params);

            //error_log(print_r($sql, true));

            $results = $wpdb->query($sql);

            if ($results) {
                error_log(print_r("Deleted Posts", true));
                return $number_of_posts;
            }
            error_log(print_r("NO Deleted Posts", true));
            return false;
        } else {
            error_log(print_r("NO Rows Found", true));
            return false;
        }
    }

    public function search_db($limit = false)
    {
        global $wpdb;

        $posts_found = array();

        $sql = "SELECT
            MIN(ID) AS post_id,
            post_title,
            post_content,
            COUNT(post_content) AS duplicate_count,
            MIN(post_date) AS post_date
        FROM
        {$wpdb->prefix}posts
        WHERE
            post_type = 'post' AND post_status = 'publish'
        GROUP BY
            post_content,post_title
        HAVING
            COUNT(post_content) > 1
        ORDER BY
            `post_date`
        DESC";

        if ($limit) {
            $sql .= "\n LIMIT %d";
            $sql = $wpdb->prepare($sql, $limit);
        }

        // error_log(print_r($sql, true));

        $results = $wpdb->get_results($sql);

        if ($results) {
            foreach ($results as $key => $result) {
                // $posts_found[] = (int) $result->ID;
                $posts_found[$key]['ID'] = (int) $result->post_id;
                $posts_found[$key]['post_title'] = $result->post_title;
                $posts_found[$key]['post_content'] = $result->post_content;
                $posts_found[$key]['duplicate_count'] = $result->duplicate_count;

                // error_log(print_r($result->ID, true));
            }
        } else {
            $posts_found = array();
        }
        //error_log(print_r($posts_found, true));
        return $posts_found;
    }

    public function scan_duplicate_posts()
    {
        // delete_option( DUPLICATE_POST_SLUG );
        $option = get_option(DUPLICATE_POST_SLUG) ? get_option(DUPLICATE_POST_SLUG) : false;

        if ($option === true) {
            echo json_encode($option);
            wp_die();
        }

        $wp_results = $this->total_post_count();

        $counter = 0;

        if (is_array($wp_results) && count($wp_results) > 0) {
            foreach ($wp_results as $key => $duplicate) {
                $counter = $counter + ($duplicate->post_count - 1);
            }
        }

        $results['posts_duplicated'] = count($wp_results);
        $results['duplicated_posts'] = $counter;

        if (!empty($results['posts_duplicated']) && !empty($results['duplicated_posts'])) {
            update_option(DUPLICATE_POST_SLUG, $results);
        }

        echo json_encode($results);
        wp_die();
    }

    public function search_duplicate_posts()
    {

        if (!isset($_SESSION['is_scanning_dupes'])) {
            $_SESSION['is_scanning_dupes'] = true;
            delete_option(DUPLICATE_POST_SLUG);
        }

        $results = array();

        $totalRows = isset($_GET['number_of_rows']) && !empty($_GET['number_of_rows']) ? (int) $_GET['number_of_rows'] : count($this->total_post_count());
        $chunks = isset($_GET['chunks']) && !empty($_GET['chunks']) ? (int) $_GET['chunks'] : $totalRows;
        $start = isset($_GET['start']) && !empty($_GET['start']) ? (int) $_GET['start'] : 0;
        $end = isset($_GET['end']) && !empty($_GET['end']) ? (int) $_GET['end'] : $chunks;
        $dupesCurrentCount = isset($_GET['dupes_current_count']) && !empty($_GET['number_of_rows']) ? (int) $_GET['dupes_current_count'] : 0;

        if ($totalRows < $chunks) {
            $wp_results = $this->search_db($totalRows);
        } else {
            $wp_results = $this->search_db($chunks);
        }

        $wp_results = $this->delete_duplicate_posts($wp_results, false,$chunks);
        error_log(print_r($wp_results, true));

        $results['dupesCurrentCount'] = $wp_results + $dupesCurrentCount;
        $results['totalRows'] = $totalRows;
        $results['chunks'] = $chunks;
        $results['start'] = $start + $chunks;
        $results['end'] = $end + $chunks;

        if (!isset($_SESSION['duplicate_posts_to_delete'])) {
            $_SESSION['duplicate_posts_to_delete'] = array();
        }

        if (!isset($_SESSION['duplicate_posts_deleted_count'])) {
            $_SESSION['duplicate_posts_deleted_count'] = 0;
        }

        if ($wp_results) {

            $duplicates_key = $start . $end;

            if ($totalRows == $end) {
                $dupesCurrentCount = $results['dupesCurrentCount'];
                $results['alerts']['completed'] = true;
                $results['alerts']['action'] = 'success';
                $results['alerts']['message'] = "WP Delete Duplicate Posts successfully deleted <strong>$dupesCurrentCount</strong> duplicated posts from your database.";
            }

            if ($totalRows > $end) {
                $start = $start + $chunks;
                $dupesCurrentCount = $results['dupesCurrentCount'];
                $results['alerts']['completed'] = false;
                $results['alerts']['action'] = 'info';
                $results['alerts']['message'] = "<p>Scanned <strong>$start</strong> of <strong>$totalRows</strong> posts that were duplicated.</p> <p> Deleted <strong>$dupesCurrentCount</strong> duplicate posts</p>";
            }

            if ($chunks > $totalRows) {
                $dupesCurrentCount = $results['dupesCurrentCount'];
                $results['alerts']['completed'] = true;
                $results['alerts']['action'] = 'success';
                $results['alerts']['message'] = "WP Delete Duplicate Posts successfully deleted <strong>$dupesCurrentCount</strong>  duplicated posts from your database.";
            }
        }

        echo json_encode($results);
        wp_die();
    }
}
