<?php
class WP_REST_WPML_Posts_Controller extends WP_REST_Posts_Controller {
    protected $namespaces;
    protected $post_type;

    function __construct($post_type)
    {
        global $wp_rest_helper;
        $this->acf_meta_controller = new WP_REST_WPML_ACF_Meta_Controller();
        $this->namespaces = $wp_rest_helper->get_all_restml_namespaces();
        $this->post_type = $post_type;
        $obj = get_post_type_object($post_type);
        $this->rest_base = ! empty($obj->rest_base)? $obj->rest_base : $obj->name;
    }
    /**
     * Resigter the routes for the objects of the controller
     */
    public function register_routes()
    {
        /* iterate all active language namespaces
         * e.g. restwl/en, restwl/zh-hans
         */
        foreach ($this->namespaces as $ns) {
            $this->namespace = $ns;
            /* post_type list */
            register_rest_route (
                $this->namespace,
                '/'.$this->rest_base,
                array (
                    array (
                        'methods' => WP_REST_Server::READABLE,
                        'callback' => array($this,'wpml_get_itmes'),
                        'permission_callback' => array( $this, 'get_items_permissions_check' ),
                        'args'            => $this->get_collection_params()
                    )
                )
            );
            /**
             * get post single by id
             * sample_request: json-api/restml/[:lang]/[:post_type]/[:id]
             *                 json-api/restml/en/post/4
             */
            register_rest_route(
                $this->namespace,
                '/'.$this->rest_base.'/(?<id>[\d]+)',
                array (
                    array (
                        'methods' => WP_REST_Server::READABLE,
                        'callback' => array ($this, 'wpml_get_item'),
                        'permission_callback' => array( $this, 'get_item_permissions_check' ),
                        'args'            => array(
                            'context'          => $this->get_context_param( array( 'default' => 'view' ) ),
                        )
                    )
                )
            );
            /**
             * get post single by post_name
             */
            register_rest_route(
                $this->namespace,
                '/'.$this->rest_base.'/slug/(?P<postname>.*)',
                array (
                    array (
                        'methods' => WP_REST_Server::READABLE,
                        'callback' => array ($this, 'wpml_get_item_by_post_name'),
                        'permission_callback' => array( $this, 'get_item_permissions_check' ),
                        'args'            => array(
                            'context'          => $this->get_context_param( array( 'default' => 'view' ) ),
                        )
                    )
                )
            );
        }
    }

    /**
     * Get posts based on language tag in namespace
     * recall WP_REST_Posts_Controller->get_items
     * just change wpml language before call that
     */
    public function wpml_get_itmes($request) {
        global $wp_rest_helper;
        $wp_rest_helper->rest_api_language_switcher($request);
        return $this->get_items($request);
    }

    /**
     * Get post base on id
     * recall WP_REST_Posts_Controller->get_item
     * just check target post id is in that target language.
     */
    public function wpml_get_item($request) {
        global $wp_rest_helper;
        $id = (int) $request['id'];
        $target_language = $wp_rest_helper->get_target_language($request->get_route());
        $the_id = icl_object_id($id, $this->post_type , false ,$target_language);
        if($id !== $the_id) {
            return new WP_Error( 'rest_post_invalid_id_in_language', __( 'Invalid post id in current language.' ), array( 'status' => 404 ) );
        } else {
            $original_response = $this->get_item($request);
            $original_response = $this->prepare_data_with_metas($original_response,$request);
            $original_response = $this->prepare_data_with_attachement($request,$original_response);
            $response = $wp_rest_helper->hidden_backend_information($original_response);
            return $response;
        }
    }

    /**
     * Get a single post by title with wpml selector
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function wpml_get_item_by_post_name($request) {
        global $wp_rest_helper;
        $wp_rest_helper->rest_api_language_switcher($request);
        $post_name = $request['postname'];
        $args = array (
            'name' => $post_name,
            'post_type' => array($this->post_type)
        );
        $query = new WP_Query( $args );
        if ( $query->have_posts() ) {
            $query->the_post();
            $post = get_post();
            $data = $this->prepare_item_for_response( $post, $request );
            $original_response = rest_ensure_response( $data );
            if ( is_post_type_viewable( get_post_type_object( $post->post_type ) ) ) {
                $original_response->link_header( 'alternate',  get_permalink( $post->ID ), array( 'type' => 'text/html' ) );
            }
            wp_reset_postdata();
            $original_response = $this->prepare_data_with_metas($original_response,$request);
            $original_response = $this->prepare_data_with_attachement($request,$original_response);
            $original_response = $wp_rest_helper->hidden_backend_information($original_response);
            return $original_response;
        } else {
            wp_reset_postdata();
            return new WP_Error( 'no_'.$this->post_type.'_found', __( 'No exist Contents.'), array( 'status' => 404 ) );
        }
    }

    public function prepare_data_with_metas($original_response,$request){
        $query_param_array = $request->get_query_params();
        if($query_param_array[REST_EXTENTED_FILTER][REST_EXTENTED_FILTER_ACF]) {
            $options = $this->acf_meta_controller->wpml_get_acf_fields($request);
            $original_response->data[REST_EXTENTED_FILTER_ACF] = $options;
        }
        return $original_response;
    }

    public function prepare_data_with_attachement($request,$original_response){
        global $wp_rest_helper;
        $query_param_array = $request->get_query_params();
        if($query_param_array[REST_EXTENTED_FILTER][REST_EXTENTED_FILTER_ATTACHMENT] && has_post_thumbnail($request['id'])) {
            $original_response->data['featured_media_origin_url'] = $wp_rest_helper->feature_image_helper($request['id']);
        } else {
            $original_response->data['featured_media_origin_url'] = null;
        }
        return $original_response;
    }
}
?>