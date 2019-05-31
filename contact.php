<?php

/**
 * Plugin Name: PerfectMy.co.uk Contact Form
 * Plugin URI: https://perfectmy.co.uk
 * Description: A simple to use contact form backend for use with <a href="https://twitter.com/mxrxdxn">@mxrxdxn</a> themes.
 * Version: 1.0.0
 * Author: Ron Marsden
 * Author URI: https://perfectmy.co.uk
 * License: GPL2
 */

if (! class_exists('PerfectContact_Install')) {
    require_once(plugin_dir_path(__FILE__) . 'libs/PerfectContact_Install.php');
}

$installer = new PerfectContact_Install();
register_activation_hook(__FILE__, [$installer, 'activate']);

if (! class_exists('PerfectMy_Contact')) {
    require_once(plugin_dir_path(__FILE__) . 'libs/PerfectContact_Plugin.php');
}

add_action('plugins_loaded', function () {
    PerfectMy_Contact::get_instance();
});