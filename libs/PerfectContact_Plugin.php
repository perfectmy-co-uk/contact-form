<?php

if (! class_exists('PerfectContact_Message_List_Table')) {
    require_once(plugin_dir_path(__FILE__) . 'PerfectContact_Message_List_Table.php');
}

class PerfectMy_Contact
{
    // class instance
    static $instance;

    // WP_List_Table object
    public $data_obj;

    // class constructor
    public function __construct()
    {
        $this->checkForUpdates();

        add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
        add_action('admin_menu', [$this, 'plugin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function checkForUpdates()
    {
        // Handle automatic updates
        require_once(plugin_dir_path(__FILE__) . 'thirdparty/plugin-update-checker/plugin-update-checker.php');
        $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
            'https://github.com/perfectmy-co-uk/contact-form/',
            __FILE__,
            'perfectmy-contact-form'
        );
        $myUpdateChecker->getVcsApi()->enableReleaseAssets();
    }

    public static function set_screen($status, $option, $value)
    {
        return $value;
    }

    public function plugin_menu()
    {
        $hook = add_menu_page(
            'Contact Messages',
            'Contact Messages',
            'manage_options',
            'perfect_contact',
            [$this, 'plugin_settings_page']
        );

        add_action("load-$hook", [$this, 'screen_option']);
    }

    public function enqueue_scripts()
    {
        $path = plugin_dir_path(__FILE__) . '../mix-manifest.json';

        $files = json_decode(file_get_contents($path), true);

        $jsVersion  = explode('?id=', $files['/js/script.js'])[1];

        wp_enqueue_script('perfect-contact-form-script', get_theme_file_uri('/js/script.js'), [], $jsVersion, true);
    }

    /**
     * Plugin settings page
     */
    public function plugin_settings_page()
    {
        ?>
        <div class="wrap">
            <h2>Contact Messages</h2>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post">
                                <?php
                                $this->data_obj->prepare_items();
                                $this->data_obj->display(); ?>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>
        <?php
    }

    /**
     * Screen options
     */
    public function screen_option()
    {

        $option = 'per_page';
        $args   = [
            'label'   => 'Messages',
            'default' => 5,
            'option'  => 'records_per_page'
        ];

        add_screen_option($option, $args);

        $this->data_obj = new PerfectContact_Message_List_Table();
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}