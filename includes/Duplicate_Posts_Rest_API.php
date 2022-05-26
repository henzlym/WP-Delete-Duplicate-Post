<?php
class Duplicate_Posts_Rest_API
{
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_endpoints'));        
    }
    public function posts_groupby( $groupby )
    {
        $groupby = "post_content HAVING COUNT(post_content) > 1";
        return $groupby;
    }
    public function post_limits( $limits )
    {
        return $limits;
    }
    public function posts_fields( $fields )
    {
        $fields = "MIN(ID) as ID,
        MIN(post_name) AS post_name,
        MIN(post_title) AS post_title,
        post_content,
        COUNT(post_content) AS duplicate_count,
        GROUP_CONCAT( ID ) as duplicate_ids,
        MIN(post_date) AS post_date";
        return $fields;
    }
    public function register_endpoints()
    {

        register_rest_route('bca/delete-duplicates/v1', '/delete', array(
            'methods' => 'GET',
            'callback' => array($this, 'delete'),
            'permission_callback' => function () {
                return current_user_can('edit_others_posts');
            }
        ));
        register_rest_route('bca/delete-duplicates/v1', '/duplicates', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_duplicates'),
            'permission_callback' => function () {
                return current_user_can('edit_others_posts');
            }
        ));
        register_rest_route('bca/delete-duplicates/v1', '/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search'),
            'permission_callback' => function () {
                return current_user_can('edit_others_posts');
            }
        ));

        register_rest_field( 'post',
            'duplicated_post_ids', 
            array(
                'get_callback'    => array( $this, 'slug_get_meta_duplicated_post_ids' )
            )
        );

        register_rest_field( 'tag',
            'total_number_of_duplicates', 
            array(
                'get_callback'    => array( $this, 'slug_get_meta' )
            )
        );
    }
    public function slug_get_meta_duplicated_post_ids( $object, $field )
    {
        return get_post_meta( $object['id'], 'duplicated_post_ids', true );
    }
    public function slug_get_meta( $object, $field )
    {
        return get_term_meta( $object['id'], 'total_number_of_duplicates', true );
    }
    public function get_duplicated_posts( $post_count = false )
    {
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 10,
            'tax_query' => array(
                array(
                    'taxonomy' => 'post_tag',
                    'field'    => 'slug',
                    'terms'    => 'is-' . DUPLICATE_POST_SLUG
                )
            )
        );

        // The Query
        $query = new WP_Query( $args );
        $posts = $query->get_posts();

        if ($posts) {
            if ($post_count) {
                $results['max_num_pages'] = $query->max_num_pages;
                $results['found_posts'] = $query->found_posts;
                return $results;
            }
            return $posts;
        } else {
            $results['error'] = true;
            $results['message'] = 'No posts were found.';
            return false;
        }
    }
    public function get_posts_with_duplicates( $post_count = false )
    {
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 10,
            'tax_query' => array(
                array(
                    'taxonomy' => 'post_tag',
                    'field'    => 'slug',
                    'terms'    => 'has-' . DUPLICATE_POST_SLUG
                )
            )
        );

        // The Query
        $query = new WP_Query( $args );
        $posts = $query->get_posts();

        if ($posts) {
            if ($post_count) {
                $results['max_num_pages'] = $query->max_num_pages;
                $results['found_posts'] = $query->found_posts;
                return $results;
            }
            return $posts;
        } else {
            $results['error'] = true;
            $results['message'] = 'No posts were found.';
            return false;
        }
    }
    public function get_duplicates(WP_REST_Request $request)
    {
        $is_duplicate = $this->get_duplicated_posts( true );
        $has_duplicate = $this->get_posts_with_duplicates( true );

        $results = array(
            'has_duplicate' => $has_duplicate,
            'is_duplicate' => $is_duplicate
        );

        return $results;
    }
    public function delete(WP_REST_Request $request)
    {
        $results = array(
            'error' => false,
        );

        $current = $request->get_param('current');
        $limit = $request->get_param('limit');
        $total = $request->get_param('total');
        $force_delete = $request->get_param('force_delete');

        $results['current'] = (int) $current;
        $results['limit'] = (int) $limit;
        $results['total'] = (int) $total;

        // WP_Query arguments
        $args = array(
            'post_type' => 'post',
            'tax_query' => array(
                array(
                    'taxonomy' => 'post_tag',
                    'field'    => 'slug',
                    'terms'    => 'is-' . DUPLICATE_POST_SLUG
                )
            ),
            'posts_per_page' => $limit
        );

        // The Query
        $query = new WP_Query( $args );
        $posts = $query->get_posts();
        if ($posts) {
            foreach ($posts as $key => $post) {
                $data = wp_delete_post( $post->ID );
                if ($data) {
                    $results['posts'][$key] = $data;
                }
                
            }
            $results['max_num_pages'] = $query->max_num_pages;
            $results['found_posts'] = $query->found_posts;

            return $results;
            
        } else {
            $results['error'] = true;
            $results['message'] = 'No posts were found.';
            return $results;
        }


        return $results;
    }

    public function search(WP_REST_Request $request)
    {
        global $wpdb;

        $results = array(
            'error' => false,
            'wp_cache_set' => false
        );
        $page = $request->get_param('page');
        $limit = 10;
        $paged = ($page) ? $page : 0;
        $offset = $paged * $limit;
        $cached_results = wp_cache_get( DUPLICATE_POST_SLUG . '-query' );
        $cached_results = get_transient( DUPLICATE_POST_SLUG . '-query' );
        $max_num_pages = 0;
        $found_posts = 0;
        
        if ( false === $cached_results ) {
            error_log(print_r('Duplicate_Posts_Rest_API:search:cached_results', true ));
            error_log(print_r($cached_results, true ));
            add_filter( 'posts_groupby', array($this, 'posts_groupby') );
            add_filter( 'post_limits', array($this, 'post_limits') );
            add_filter( 'posts_fields', array($this, 'posts_fields') );

            $args = array(
                'post_type' => 'post',
                'posts_per_page' => -1,
                'paged' => $paged
            );
    
            // The Query
            $query = new WP_Query( $args );
            $posts = $query->get_posts();
            $found_posts = $query->found_posts;
            $results['wp_cache_set'] = true;
            $results['found_posts'] = $found_posts;
            $max_num_pages = ceil( $found_posts / 10 );
            $results['max_num_pages'] = $max_num_pages;
            $results['posts'] = $posts;

            set_transient( DUPLICATE_POST_SLUG . '-query', $results, 60*2 );
        } 

        if ( $cached_results ) {
            $posts = array_slice( $cached_results['posts'], $offset, $limit );
            foreach ($posts as $key => $post) {	
                wp_set_post_tags( $post->ID, 'has-' . DUPLICATE_POST_SLUG, true );
                $duplicate_ids = explode(',', $post->duplicate_ids);
                if (is_array($duplicate_ids) && !empty($duplicate_ids)) {
                    foreach ($duplicate_ids as $key => $id) {
                        wp_set_post_tags( $id, 'is-' . DUPLICATE_POST_SLUG, true );
                    }
                }  
            }

            $found_posts = $cached_results['found_posts'];
            $max_num_pages = $cached_results['max_num_pages'];
            

        }

        $has_duplicate = get_term_by( 'slug', 'has-' . DUPLICATE_POST_SLUG, 'post_tag' );
        $is_duplicate = get_term_by( 'slug', 'is-' . DUPLICATE_POST_SLUG, 'post_tag' );
        $results['has_duplicate'] = $has_duplicate;
        $results['is_duplicate'] = $is_duplicate;
        $results['max_num_pages'] = $max_num_pages;
        $results['posts'] = $posts;
        $results['found_posts'] = $found_posts;
        return $results;

        $limit = $request->get_param('limit');

        $sql = "SELECT
            MIN(ID) AS post_id,
            MIN(post_name) AS post_name,
            MIN(post_title) AS post_title,
            post_content,
            COUNT(post_content) AS duplicate_count,
            GROUP_CONCAT( ID ) as duplicate_ids,
            MIN(post_date) AS post_date
        FROM
        {$wpdb->prefix}posts
        WHERE
            post_type = 'post' AND post_status = 'publish'
        GROUP BY
            post_content
        HAVING
            COUNT(post_content) > 1
        ORDER BY
            `post_date`
        DESC";

        if ($limit) {
            $sql .= "\n LIMIT %d";
            $sql = $wpdb->prepare($sql, $limit);
            // error_log(print_r($sql, true));
        }

        $posts = $wpdb->get_results($sql);

        if ($posts) {
            $has_duplicate = get_term_by( 'slug', 'has-' . DUPLICATE_POST_SLUG, 'post_tag' );
            $is_duplicate = get_term_by( 'slug', 'is-' . DUPLICATE_POST_SLUG, 'post_tag' );

            foreach ($posts as $key => $post) {

                wp_set_post_terms( $post->post_id , array( (int) $has_duplicate->term_id ), 'post_tag' );
                update_post_meta( $post->post_id, 'duplicated_post_ids', $post->duplicate_ids );
                $duplicate_ids = explode(',', $post->duplicate_ids);
                if (is_array($duplicate_ids) && !empty($duplicate_ids)) {
                    foreach ($duplicate_ids as $key => $id) {
                        wp_set_post_terms( (int) $id, array( (int) $is_duplicate->term_id ), 'post_tag' );
                    }
                }                
            }

            $has_duplicate = get_term_by( 'slug', 'has-' . DUPLICATE_POST_SLUG, 'post_tag' );
            $is_duplicate = get_term_by( 'slug', 'is-' . DUPLICATE_POST_SLUG, 'post_tag' );
            
            $results = array(
                'error' => false,
                'has_duplicate' => $has_duplicate,
                'is_duplicate' => $is_duplicate,
                'posts' => $posts
            );

            return $results;

        }

        $results = array(
            'error' => false,
            'message' => 'No duplicate posts were found'
        );

        return $results;
    }
}
