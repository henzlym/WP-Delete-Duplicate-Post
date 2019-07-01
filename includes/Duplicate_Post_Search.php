<?php
class Duplicate_Post_Search
{
    public function __construct()
    {
        add_action('init', array($this, 'set_session'), 1);
        add_action("wp_ajax_search_duplicate_posts", array($this, 'search_duplicate_posts'));
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
        $total_posts = wp_count_posts();
        return (int) $total_posts->publish;
    }

    public function delete_posts($posts)
    {


        global $wpdb;

        $placeholder = implode(', ', array_fill(0, count($posts), '%d'));

        $sql = "DELETE a,b,c
        FROM X6x3g_posts a
        LEFT JOIN X6x3g_term_relationships b ON ( a.ID = b.object_id )
        LEFT JOIN X6x3g_postmeta c ON ( a.ID = c.post_id )
        WHERE a.ID IN ($placeholder)";

        $prepared_stmt = $wpdb->prepare($sql, $posts);
        error_log(print_r($prepared_stmt, true));

        $result =  $wpdb->query($prepared_stmt);

        if ($result) {

            return $result;
        }

        return false;
    }

    public function search_db($limit)
    {
        global $wpdb;

        $posts_found = array();

        $sql = "SELECT a.ID, a.post_title,a.post_name
        FROM {$wpdb->prefix}posts AS a
           INNER JOIN (
              SELECT post_title, MIN( id ) AS min_id
              FROM {$wpdb->prefix}posts
              WHERE post_type = 'post'
              AND post_status = 'publish'
              GROUP BY post_title
              HAVING COUNT( * ) > 1
           ) AS b ON b.post_title = a.post_title
        AND b.min_id <> a.id
        AND a.post_type = 'post'
        AND a.post_status = 'publish'
        LIMIT %d";


        $prepared_stmt = $wpdb->prepare($sql, $limit);
        //error_log(print_r($prepared_stmt, true));
        $results = $wpdb->get_results($prepared_stmt);

        if ($results) {
            foreach ($results as $key => $result) {
                $posts_found[] = (int) $result->ID;
                // error_log(print_r($result->ID, true));
            }
        } else {
            $posts_found = array();
        }

        return $posts_found;
    }

    public function search_duplicate_posts()
    {

        $results = array();
        $totalRows = isset($_GET['number_of_rows']) && !empty($_GET['number_of_rows']) ? (int) $_GET['number_of_rows'] : $this->total_post_count();
        $chunks = isset($_GET['chunks']) && $_GET['chunks'] != false ? (int) $_GET['chunks'] : $totalRows;
        $start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
        $end = isset($_GET['end']) ? (int) $_GET['end'] : $chunks;
        $dupesCurrentCount = isset($_GET['dupes_current_count']) && !empty($_GET['number_of_rows']) ? (int) $_GET['dupes_current_count'] : null;

        if ($totalRows < $chunks) {
            $wp_results = $this->search_db($totalRows);
        } else {
            $wp_results = $this->search_db($chunks);
        }

        $results['dupesCurrentCount'] = count($wp_results) + $dupesCurrentCount;
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

        if (is_array($wp_results) && count($wp_results) > 0) {

            $duplicates_key = $start . $end;
            //$_SESSION['duplicate_posts_to_delete'][$duplicates_key] = $wp_results;
            $post_deleted = $this->delete_posts($wp_results, $chunks);

            if ($post_deleted) {
                if ($totalRows == $end) {
                    $dupesCurrentCount = $results['dupesCurrentCount'];
                    $results['alerts']['completed'] = true;
                    $results['alerts']['action'] = 'success';
                    $results['alerts']['message'] = "Process Completed! Deleted $dupesCurrentCount duplicated posts";
                    $results['deletePosts'] = "<button id='delete-duplicates'>Delete Dupiclates</button>";
                    // $results['posts'] = $wp_results;
                    error_log(print_r($_SESSION['duplicate_posts_to_delete'], true));
                    // error_log(print_r($results['alerts']['message'], true));
                }

                if ($totalRows > $end) {
                    $start = $start + $chunks;
                    $dupesCurrentCount = $results['dupesCurrentCount'];
                    $results['alerts']['completed'] = false;
                    $results['alerts']['action'] = 'info';
                    $results['alerts']['message'] = "Scanned $start / $totalRows. \n Deleted $dupesCurrentCount duplicated posts";
                    // error_log(print_r($results['alerts']['message'], true));
                }

                if ($chunks > $totalRows) {
                    $dupesCurrentCount = $results['dupesCurrentCount'];
                    $results['alerts']['completed'] = true;
                    $results['alerts']['action'] = 'success';
                    $results['alerts']['message'] = "Process Completed! Deleted $dupesCurrentCount duplicated posts";
                    $results['deletePosts'] = "<button id='delete-duplicates'>Delete Dupiclates</button>";
                }
            }
        }




        // if ($end == 1000) {
        //     $results['alerts']['completed'] = true;
        //     $results['alerts']['action'] = 'success';
        //     $results['alerts']['message'] = "Reached 1000 iteration benchmark! Found $dupesCurrentCount duplicated posts. \nPlease begin deletion process.";
        //     $results['deletePosts'] = "<button id='delete-duplicates'>Delete Dupiclates</button>";
        // }

        echo json_encode($results);
        wp_die();
    }
}
