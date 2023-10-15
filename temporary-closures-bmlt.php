<?php

/*
Plugin Name: Temporary Closures BMLT
Plugin URI: https://wordpress.org/plugins/temporary-closures-bmlt/
Contributors: pjaudiomv, bmltenabled
Author: bmlt-enabled
Description: Temporary Closures BMLT is a plugin that displays a list of all meetings that have temporary closures. It can be used to view published or unpublished meetings.
Version: 1.4.0
Install: Drop this directory into the "wp-content/plugins/" directory and activate it.
*/
/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Sorry, but you cannot access this page directly.');
}

spl_autoload_register(function (string $class) {
    if (strpos($class, 'TemporaryClosures\\') === 0) {
        $class = str_replace('TemporaryClosures\\', '', $class);
        require __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    }
});

use TemporaryClosures\Settings;
use TemporaryClosures\Shortcode;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class TemporaryClosures
// phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private static $instance = null;

    public function __construct()
    {
        add_action('init', [$this, 'pluginSetup']);
    }

    public function pluginSetup()
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'optionsMenu']);
            add_action("admin_enqueue_scripts", [$this, "enqueueBackendFiles"], 500);
        } else {
            add_action("wp_enqueue_scripts", [$this, "enqueueFrontendFiles"]);
            add_shortcode('temporary_closures', [$this, 'showClosures']);
        }
    }

    public function optionsMenu()
    {
        $dashboard = new Settings();
        $dashboard->createMenu(plugin_basename(__FILE__));
    }

    public function showClosures($atts)
    {
        $shortcode = new Shortcode();
        return $shortcode->render($atts);
    }

    public function enqueueBackendFiles($hook)
    {
        if ($hook !== 'settings_page_temporary-closures-bmlt') {
            return;
        }
        $base_url = plugin_dir_url(__FILE__);
        wp_enqueue_style('temporary-closures-admin-ui-css', $base_url . 'css/redmond/jquery-ui.css', [], '1.11.4');
        wp_enqueue_style("chosen", $base_url . "css/chosen.min.css", [], '1.2', 'all');
        wp_enqueue_script('chosen', $base_url . 'js/chosen.jquery.min.js', ['jquery'], '1.2', true);
        wp_enqueue_script('temporary-closures-admin', $base_url . 'js/temporary_closures_admin.js', ['jquery'], filemtime(plugin_dir_path(__FILE__) . 'js/temporary_closures_admin.js'), false);
        wp_enqueue_script('common');
        wp_enqueue_script('jquery-ui-accordion');
    }

    public function enqueueFrontendFiles($hook)
    {
        wp_enqueue_style('temporary-closures', plugin_dir_url(__FILE__) . 'css/temporary_closures.css', false, '1.15', 'all');
        wp_enqueue_script('datatables', '//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js', array('jquery'));
        wp_enqueue_style('datatables-style', '//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css');
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

TemporaryClosures::getInstance();
