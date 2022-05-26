<?php
class Duplicate_Posts_Admin
{

    public function __construct()
    {
        add_action('init', array($this, 'register_taxonomy'), 0);
        add_action('init', array($this, 'plugin_activation') );
        register_activation_hook( __FILE__, array($this, 'plugin_activation') );
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_filter('body_class', array($this, 'admin_body_class'));
    }
    public function plugin_activation()
    {
        $tags = array(
            array(
                'slug' => 'has-' . DUPLICATE_POST_SLUG,
                'name' => 'Has Duplicate Posts'
            ),
            array(
                'slug' => 'is-' . DUPLICATE_POST_SLUG,
                'name' => 'Is Duplicate Posts'
            ),
            
        );

        foreach ($tags as $key => $tag) {
            $term_has_duplicate = term_exists( $tag['slug'], 'post_tag' );
 
            if ( ! $term_has_duplicate ) {
                $term_has_duplicate = wp_insert_term( $tag['name'], 'post_tag', array( 'slug' => $tag['slug'] ) );
            }
        }

    }
    public function admin_body_class($classes)
    {
        global $hook_suffix;

        if (!is_admin()) return $classes;

        if (
            (function_exists('get_current_screen') && get_current_screen()->parent_base == 'duplicate-posts') ||
            $hook_suffix == 'toplevel_page_duplicate-posts'
        ) {
            return array_merge($classes, array('bca-duplicate-posts'));
        }

        return $classes;
    }
    /**
     * Enqueue admin js/css files.
     */
    public function enqueue_admin_scripts()
    {
        global $pagenow;

        $allowed_pages = array('duplicate-posts');
        if (isset($_GET['page']) && in_array($_GET['page'], $allowed_pages)) {
            $asset = include_once DUPLICATE_POST_ROOT . '/build/index.asset.php';

            wp_enqueue_script(DUPLICATE_POST_SLUG . '-script', DUPLICATE_POST_URL . '/build/index.js', $asset['dependencies'], $asset['version'], true);
            wp_add_inline_script(DUPLICATE_POST_SLUG . '-script', $this->stringify_settings(), 'before');
            wp_enqueue_style(DUPLICATE_POST_SLUG . '-style', DUPLICATE_POST_URL . '/build/style-index.css', array('wp-components'), $asset['version'], 'all');
        }
    }
    public function stringify_settings()
    {
        return "const bcaDuplicatePosts = " . wp_json_encode(get_option(DUPLICATE_POST_SLUG)) . ";";
    }

    // Register Custom Taxonomy
    public function register_taxonomy()
    {

        $labels = array(
            'name'                       => _x('Duplicate Posts', 'Taxonomy General Name', 'bca'),
            'singular_name'              => _x('Duplicate Post', 'Taxonomy Singular Name', 'bca'),
            'menu_name'                  => __('Duplicate Posts', 'bca'),
            'all_items'                  => __('Duplicate Posts', 'bca'),
            'parent_item'                => __('Parent Item', 'bca'),
            'parent_item_colon'          => __('Parent Duplicate Post:', 'bca'),
            'new_item_name'              => __('New Duplicate Post Name', 'bca'),
            'add_new_item'               => __('Add New Duplicate Post', 'bca'),
            'edit_item'                  => __('Edit Duplicate Post', 'bca'),
            'update_item'                => __('Update Duplicate Post', 'bca'),
            'view_item'                  => __('View Duplicate Post', 'bca'),
            'separate_items_with_commas' => __('Separate Duplicate Post with commas', 'bca'),
            'add_or_remove_items'        => __('Add or remove Duplicate Posts', 'bca'),
            'choose_from_most_used'      => __('Choose from the most used', 'bca'),
            'popular_items'              => __('Popular Duplicate Posts', 'bca'),
            'search_items'               => __('Search Duplicate Posts', 'bca'),
            'not_found'                  => __('Not Found', 'bca'),
            'no_terms'                   => __('No Duplicate Posts', 'bca'),
            'items_list'                 => __('Duplicate Posts list', 'bca'),
            'items_list_navigation'      => __('Duplicate Posts list navigation', 'bca'),
        );
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
        );
        register_taxonomy(DUPLICATE_POST_SLUG, array('post'), $args);
    }

    /**
     * Register a custom menu page.
     */
    public function add_admin_page()
    {

        add_menu_page(
            __('Delete Duplicate Posts', 'bca'),
            'Delete Duplicate Posts',
            'manage_options',
            DUPLICATE_POST_SLUG,
            array($this, 'render_admin_page'),
            'dashicons-trash',
            90
        );
    }
    /**
     * Display a Admin Home page
     */
    public function render_admin_page()
    {

        require DUPLICATE_POST_ROOT . '/admin/index.php';
    }
}
