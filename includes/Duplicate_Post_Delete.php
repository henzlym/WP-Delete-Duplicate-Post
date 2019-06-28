<?php
class Duplicate_Post_Delete
{
    public function __construct()
    {

        add_action("wp_ajax_delete_duplicate_posts", array($this, 'delete_duplicate_posts'));
    }

    public function delete_posts($posts,$start,$end)
    {
        

        global $wpdb;

        $placeholder = implode( ', ', array_fill( 0, count( $posts ), '%d' ) );
        
        $sql = "DELETE a,b,c
        FROM X6x3g_posts a
        LEFT JOIN X6x3g_term_relationships b ON ( a.ID = b.object_id )
        LEFT JOIN X6x3g_postmeta c ON ( a.ID = c.post_id )
        WHERE a.ID IN ($placeholder)";

        $prepared_stmt = $wpdb->prepare($sql, $posts);
        
        error_log(print_r("Deleted Posts: ".$prepared_stmt, true));
        
        $result =  $wpdb->get_results($prepared_stmt);

        if($result){

            return true;
        }

        return false;

        
    }
    public function delete_duplicate_posts()
    {
        if(!isset($_SESSION['duplicate_posts_to_delete'])){
            echo json_encode('No Post Found');
            wp_die();
        }

        $duplicate_posts_to_delete = $_SESSION['duplicate_posts_to_delete'];
        $duplicate_posts_count = 0;

        if(is_array($duplicate_posts_to_delete)){

            foreach ($duplicate_posts_to_delete as $key => $posts) {
                $duplicate_posts_count = count($posts) + $duplicate_posts_count;
            }
        }

        $totalRows = (int)$duplicate_posts_count;
        $chunks = isset($_GET['chunks']) && $_GET['chunks'] != false ? (int)$_GET['chunks'] : $totalRows;
        $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
        $end = isset($_GET['end']) ? (int)$_GET['end'] : $chunks;
        $current_key = $start . $end;
        $posts_to_delete = array();

        $wp_results = $this->delete_posts($duplicate_posts_to_delete[$current_key], $chunks, $start);

        if ($wp_results == false) {
            
            $results['alerts']['completed'] = true;
            $results['alerts']['action'] = 'warning';
            $results['alerts']['message'] = "Error in process!";
            $results['deletePosts'] = "<button id='delete-duplicates'>Delete Dupiclates</button>";
            error_log(print_r($results['alerts']['message'], true));

            echo json_encode($results);
            wp_die();
        }

        $dupes_deleted = count($duplicate_posts_to_delete[$current_key]);

        $results['totalRows'] = $totalRows;
        $results['chunks'] = $chunks;
        $results['start'] = $start + $chunks;
        $results['end'] = $end + $chunks;

        if($wp_results){
            unset($_SESSION['duplicate_posts_to_delete'][$current_key]);
            error_log(print_r($_SESSION['duplicate_posts_to_delete'], true));
        }

        if ($totalRows == $end) {
            
            $results['alerts']['completed'] = true;
            $results['alerts']['action'] = 'success';
            $results['alerts']['message'] = "Process Completed! $dupes_deleted duplicated posts have been deleted.";
            $results['deletePosts'] = "<button id='delete-duplicates'>Delete Dupiclates</button>";
            $results['posts'] = $wp_results;
            error_log(print_r($results['alerts']['message'], true));
        }

        if ($totalRows > $end) {
            $start = $start + $chunks;
            $results['alerts']['completed'] = false;
            $results['alerts']['action'] = 'info';
            $results['alerts']['message'] = "Deleted $start / $totalRows. \n Deleted $dupes_deleted duplicated posts";
            error_log(print_r($results['alerts']['message'], true));
        }

        if ($chunks > $totalRows) {
            $results['alerts']['completed'] = true;
            $results['alerts']['action'] = 'success';
            $results['alerts']['message'] = "Process Completed! Found $dupes_deleted duplicated posts";
            $results['deletePosts'] = "<button id='delete-duplicates'>Delete Dupiclates</button>";
   
        }

        echo json_encode($results);
        wp_die();
    }
}
