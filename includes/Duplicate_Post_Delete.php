<?php
class Duplicate_Post_Delete
{
    public function __construct()
    {

        add_action("wp_ajax_delete_duplicate_posts", array($this, 'delete_duplicate_posts'));
    }

    public function delete_posts($posts, $start, $end)
    {


        global $wpdb;

        $placeholder = implode(', ', array_fill(0, count($posts), '%d'));

        $sql = "DELETE a,b,c
        FROM X6x3g_posts a
        LEFT JOIN X6x3g_term_relationships b ON ( a.ID = b.object_id )
        LEFT JOIN X6x3g_postmeta c ON ( a.ID = c.post_id )
        WHERE a.ID IN ($placeholder)";

        $prepared_stmt = $wpdb->prepare($sql, $posts);

        $result =  $wpdb->query($prepared_stmt);

        if ($result) {

            return $result;
        }

        return false;
    }

    public function delete_duplicate_posts()
    {
        if (!isset($_SESSION['duplicate_posts_to_delete']) && !isset($_SESSION['duplicate_posts_deleted_count'])) {
            echo json_encode('No Post Found');
            wp_die();
        }


        $start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
        $duplicate_posts_to_delete = $_SESSION['duplicate_posts_to_delete'];

        $dupes_deleted = $_SESSION['duplicate_posts_deleted_count'];

        if (is_array($duplicate_posts_to_delete) && $start == 0) {
            $duplicate_posts_count = 0;

            foreach ($duplicate_posts_to_delete as $key => $posts) {
                $duplicate_posts_count = count($posts) + $duplicate_posts_count;
            }
            $totalRows = (int) $duplicate_posts_count;
        } else {
            $duplicate_posts_count = isset($_GET['number_of_rows']) && !empty($_GET['number_of_rows']) ? (int) $_GET['number_of_rows'] : false;
        }


        $totalRows = (int) $duplicate_posts_count;
        $chunks = isset($_GET['chunks']) && $_GET['chunks'] != false ? (int) $_GET['chunks'] : $totalRows;
        $end = isset($_GET['end']) ? (int) $_GET['end'] : $chunks;
        $current_key = $start . $end;
        $posts_to_delete = array();

        $wp_results = $this->delete_posts($duplicate_posts_to_delete[$current_key], $chunks, $start);
        // $wp_results = true;

        // if ($start == 40) {
        //     $wp_results = false;
        // }
        if ($wp_results == false) {

            $results['alerts']['completed'] = true;
            $results['alerts']['action'] = 'danger';
            $results['alerts']['message'] = "Error deleting post between $start / $end";
            $results['deletePosts'] = "<button id='delete-duplicates'>Delete Dupiclates</button>";
            error_log(print_r($results['alerts']['message'], true));

            echo json_encode($results);
            wp_die();
        }


        $dupes_deleted = $dupes_deleted + count($duplicate_posts_to_delete[$current_key]);

        $_SESSION['duplicate_posts_deleted_count'] = $dupes_deleted;

        $_SESSION['duplicate_posts_total_rows'] = $totalRows;
        $results['totalRows'] = $totalRows;
        $results['chunks'] = $chunks;
        $results['start'] = $start + $chunks;
        $results['end'] = $end + $chunks;

        if ($wp_results) {
            unset($_SESSION['duplicate_posts_to_delete'][$current_key]);
            //error_log(print_r($_SESSION['duplicate_posts_to_delete'], true));
        }

        if ($totalRows == $end) {

            $results['alerts']['completed'] = true;
            $results['alerts']['action'] = 'success';
            $results['alerts']['message'] = "Process Completed! $dupes_deleted duplicated posts have been deleted.";
            $results['deletePosts'] = "<button id='delete-duplicates'>Delete Dupiclates</button>";
            $results['posts'] = $wp_results;
            //error_log(print_r($results['alerts']['message'], true));
        }

        if ($totalRows > $end) {
            $start = $start + $chunks;
            $results['alerts']['completed'] = false;
            $results['alerts']['action'] = 'info';
            $results['alerts']['message'] = "Deleted $start / $totalRows. \n Deleted $dupes_deleted duplicated posts";
            //error_log(print_r($results['alerts']['message'], true));
        }

        if ($chunks > $totalRows) {
            $results['alerts']['completed'] = true;
            $results['alerts']['action'] = 'success';
            $results['alerts']['message'] = "Process Completed! $dupes_deleted duplicated posts have been deleted.";
            $results['deletePosts'] = "<button id='delete-duplicates'>Delete Dupiclates</button>";
        }

        echo json_encode($results);
        wp_die();
    }
}
