<?php
//require plugin_dir_path(__FILE__) . 'init.php';

define("DUPLICATE_POST_NAME", 'duplicate_posts');
define("DUPLICATE_POST_SLUG", 'duplicate-posts');
define("DUPLICATE_POST_ROOT", plugin_dir_path(__FILE__));
define("DUPLICATE_POST_URL", plugin_dir_url(__FILE__));



class Duplicate_Posts
{
    public function __construct()
    {
        $this->load_classes();
    }

    public function load_classes()
    {
        $dir = DUPLICATE_POST_ROOT . '/includes/';

        $classes = array_diff(scandir($dir), array('..', '.'));

        foreach ($classes as $key => $class_name) {
            $class = DUPLICATE_POST_ROOT . '/includes/' . $class_name;

            if (file_exists($class)) {
                require_once $class;
                $obj = str_replace('.php', '', $class_name);

                if (class_exists($obj)) {

                    $class = new $obj;
                }
            }
        }
    }
}

new Duplicate_Posts;
