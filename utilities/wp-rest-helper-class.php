<?php
class WP_REST_Helper {
    protected $namespace_prefix = 'restml';
    /*
     * fun: get_helper_prefix
     *      GETTER
     * @ param: null
     * @ return: String();
     */
    public function get_namespace_prefix(){
        return $this->namespace_prefix;
    }

    public function get_all_restml_namespaces(){
        $all_namespace = array();
        $languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );
        foreach($languages as $lang=>$values) {
            $language = $values['tag'];
            $all_namespace[] = $this->namespace_prefix.'/'.$language;
        }
        return $all_namespace;
    }
    /**
     * Initial all customize routes
     */
    public function init_all_routes() {
        // all post type
        foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $post_type ) {
            //get post itself
            $class = 'WP_REST_WPML_Posts_Controller';
            if ( ! class_exists( $class ) ) {
                continue;
            }
            $controller = new $class( $post_type->name );
            if ( ! is_subclass_of( $controller, 'WP_REST_Controller' ) ) {
                continue;
            }
            $controller->register_routes();
            // get post meta data
            $class="WP_REST_WPML_Meta_Controller";
            if ( ! class_exists( $class ) ) {
                continue;
            }
            $controller = new $class( $post_type->name );
            if ( ! is_subclass_of( $controller, 'WP_REST_Controller' ) ) {
                continue;
            }
            $controller->register_routes();
            if ( post_type_supports( $post_type->name, 'revisions' ) ) {
                $revisions_controller = new WP_REST_Revisions_Controller( $post_type->name );
                $revisions_controller->register_routes();
            }
        }

        /* post_types */
        $controller_class = 'WP_REST_WPML_Post_Types_Controller';
        $controller = new $controller_class();
        $controller->register_routes();

        /* menus */
        $controller_class = 'WP_REST_WPML_MENU_Controller';
        $controller = new $controller_class();
        $controller->register_routes();

        /* wordpress contact form 7 */
        $controller_class = 'WP_REST_WPML_WPCF7_Controller';
        $controller = new $controller_class();
        $controller->register_routes();

        /* wordpress ACF PRO */
        $controller_class = 'WP_REST_WPML_ACF_Meta_Controller';
        $controller = new $controller_class();
        $controller->register_routes();
//        flush_rewrite_rules();
    }


    /**
     * @param $request_route
     * @return $language
     */
    public function get_target_language($request_route) {
        $route_items = explode('/',$request_route);
        $language = null;
        foreach($route_items as $index=>$item) {
            /* typical request url /restml/en/[more_req] */
            if($item === $this->namespace_prefix)
                $language = $route_items[$index + 1];
        }
        return $language;
    }

    /**
     * @param $restReq -> This is an object of WP_REST_Request
     * @return WP_Error
     */
    public function rest_api_language_switcher($restReq) {
        $target_language = $this->get_target_language($restReq->get_route());
        if($target_language != null) {
            global $sitepress;
            $sitepress->switch_lang($target_language);
        } else {
            return new WP_Error( 'bad_request', __( 'Request without language in namespace' ), array( 'status' => 404 ) );
        }
    }

    public function get_post_acf_metas ($postId) {
        if(class_exists('acf_pro')) {
            $fields = get_field_objects($postId);
            if($fields) {
                foreach($fields as $field_key => $field) {
                    $data[$field_key] = get_field($field_key);
                }
            }
        } else {
            wp_die('Sorry, but this plugin requires the ACF_pro to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
        }
        return $data;
    }

    public function feature_image_helper($postId){
        $url = wp_get_attachment_url((int) get_post_thumbnail_id($postId));
        $url_items = explode('/',$url);
        /* http://backend.example.com/wp-content/uploads/2016/09/Horizon.jpg*/
        $return_url_items = array_slice($url_items,-3,3,true);
        /* $return_url_items = {2016,09,Horizon.jpg} */
        return FRONT_END_STATIC_FILE_REQ_PREFIX.join($return_url_items,'/');
    }

    public function hidden_backend_information($original_response) {
        unset($original_response->data['guid']);
        foreach($original_response->get_links() as $key=>$value) {
            $original_response->remove_link($key);
        }
        return $original_response;
    }
}