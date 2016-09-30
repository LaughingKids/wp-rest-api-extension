<?php
/**
 * Plugin Name: WP REST API EXTENSION
 * Description: Based on WP REST API, extend more functionality for popular plugins
 * Author: Thomas Xiaojie Wang
 * Author URI: https://github.com/laughingw
 * Version: 1.0
 * Plugin URI: https://github.com/laughingw/wp-api-extension
 * License: MIT
 */
require_once dirname(__FILE__) . '/utilities/simple_html_dom.php';
require_once dirname(__FILE__) . '/utilities/wp-rest-extension-wp-hooks.php';

define('REST_API_DIR',ABSPATH.'wp-content/plugins/rest-api');
define('REST_API_ENDPOINT','json-api');
define('HELPER_CLASS','WP_REST_Helper');
define('RESTML_POST_CONTROLLER','WP_REST_WPML_Posts_Controller');
define('RESTML_POST_TYPE_CONTROLLER','WP_REST_WPML_Post_Types_Controller');
define('MENU_CONTROLLER_CLASS','WP_REST_WPML_MENU_Controller');
define('WPCF7_CONTROLLER_CLASS','WP_REST_WPML_WPCF7_Controller');
define('ACF_CONTROLLER','WP_REST_WPML_ACF_Meta_Controller');

register_activation_hook( __FILE__, 'rest_api_active' );
function rest_api_active(){
    // Require parent plugin
    if ( ! is_plugin_active( 'rest-api/plugin.php' ) and current_user_can( 'activate_plugins' ) ) {
        wp_die('Sorry, but this plugin requires the Rest-api to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }
    if ( ! is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) and current_user_can( 'activate_plugins' ) ) {
        wp_die('Sorry, but this plugin requires the WPML to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }
    if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) and current_user_can( 'activate_plugins' ) ) {
        wp_die('Sorry, but this plugin requires the Contact Form 7 to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }
}

/* initialize helpers */
if(!class_exists(HELPER_CLASS)) {
    require_once dirname(__FILE__) . '/utilities/wp-rest-helper-class.php';
}
global $wp_rest_helper;
if( empty($wp_rest_helper) ) {
    $wp_rest_helper_class = HELPER_CLASS;
    $wp_rest_helper = new $wp_rest_helper_class();
}

if( !class_exists(RESTML_POST_CONTROLLER) ) {
    require_once REST_API_DIR.'/lib/endpoints/class-wp-rest-controller.php';
    require_once REST_API_DIR.'/lib/endpoints/class-wp-rest-posts-controller.php';
    require_once dirname(__FILE__).'/endpoints/wpml/class-wp-rest-wpml-posts-controller.php';
}

if (! class_exists(RESTML_POST_TYPE_CONTROLLER)) {
    require_once REST_API_DIR.'/lib/endpoints/class-wp-rest-controller.php';
    require_once REST_API_DIR.'/lib/endpoints/class-wp-rest-post-types-controller.php';
    require_once dirname(__FILE__).'/endpoints/wpml/class-wp-rest-wpml-post-type-controller.php';
}

if( !class_exists( MENU_CONTROLLER_CLASS ) ) {
    require_once dirname(__FILE__).'/endpoints/menus/class-wp-rest-menu-controller.php';
}

if( !class_exists(WPCF7_CONTROLLER_CLASS) ) {
    require_once REST_API_DIR.'/lib/endpoints/class-wp-rest-controller.php';
    require_once REST_API_DIR.'/lib/endpoints/class-wp-rest-posts-controller.php';
    require_once dirname(__FILE__).'/endpoints/cf7/class-wp-rest-wpml-wpcf-controller.php';
}

if (! class_exists(ACF_CONTROLLER)) {
    require_once REST_API_DIR.'/lib/endpoints/class-wp-rest-controller.php';
    require_once REST_API_DIR.'/lib/endpoints/class-wp-rest-posts-controller.php';
    require_once dirname(__FILE__).'/endpoints/acf-meta/class-wp-rest-wpml-acf-meta-controller.php';
}
/* Register endpoints after basic routes registration */
add_action('rest_api_init',array($wp_rest_helper,init_all_routes),1);
?>