<?php
class Duplicate_Posts_Admin
{

    public function __construct()
    {
        add_action('init', array($this, 'reset_sessions'), 1);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_menu', array($this, 'add_admin_page'));
    }

    public function reset_sessions()
    {
        if (!wp_doing_ajax() && isset($_SESSION['duplicate_posts_to_delete'])) {
            unset($_SESSION['duplicate_posts_to_delete']);
            unset($_SESSION['duplicate_posts_deleted_count']);
            unset($_SESSION['duplicate_posts_total_rows']);
            unset($_SESSION['totalDuplicates']);
        }
        if (!wp_doing_ajax() && isset($_SESSION['is_scanning_dupes'])) {
            unset($_SESSION['is_scanning_dupes']);
        }
    }
    /**
     * Enqueue admin js/css files.
     */
    public function enqueue_admin_scripts()
    {
        global $pagenow;

        $allowed_pages = array('duplicate-posts');
        if (isset($_GET['page']) && in_array($_GET['page'], $allowed_pages)) {
            wp_enqueue_style(DUPLICATE_POST_SLUG . '-bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css', array(), '4.3.1', 'all');
            wp_enqueue_script(DUPLICATE_POST_SLUG . '-popper', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js', array('jquery'), '1.14.7', true);
            wp_enqueue_script(DUPLICATE_POST_SLUG . '-bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js', array('jquery'), '4.3.1', true);
            wp_enqueue_script(DUPLICATE_POST_SLUG . '-script', DUPLICATE_POST_URL . '/assets/js/main.js', array('jquery'), rand(0, 999), true);

            wp_enqueue_style(DUPLICATE_POST_SLUG . '-style', DUPLICATE_POST_URL . '/assets/css/style.css', array(), rand(0, 999), 'all');

            $option = get_option(DUPLICATE_POST_SLUG) ? get_option(DUPLICATE_POST_SLUG) : false;

            if ($option == true) {
                $isScanned = true;
            } else {
                $isScanned = false;
            }

            wp_localize_script(
                DUPLICATE_POST_SLUG . '-script',
                'admin',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'isScanned' => $isScanned
                )
            );
        }
    }

    /**
     * Register a custom menu page.
     */
    public function add_admin_page()
    {

        add_menu_page(
            __('Duplicate Posts', 'textdomain'),
            'Duplicate Posts',
            'manage_options',
            DUPLICATE_POST_SLUG,
            array($this, 'render_admin_page'),
            'dashicons-buddicons-friends',
            90
        );
        // add_submenu_page(
        //     'csv',
        //     'Convert CSV',
        //     'Convert CSV',
        //     'manage_options',
        //     'csv-convert',
        //     'render_csv_convert_admin_page'
        // );
    }
    /**
     * Display a Admin Home page
     */
    public function render_admin_page()
    {

        $option = get_option(DUPLICATE_POST_SLUG) ? get_option(DUPLICATE_POST_SLUG) : false;

        if ($option == true) {
            $posts_duplicated = $option['posts_duplicated'];
            $duplicated_posts = $option['duplicated_posts'];
        } else {
            $posts_duplicated = 'n/a';
            $duplicated_posts = 'n/a';
        }
        require DUPLICATE_POST_ROOT . '/admin/index.php';
    }
}
