<?php
class WP_REST_WPML_Post_Types_Controller extends WP_REST_Post_Types_Controller {

    protected $namespaces;

    public function __construct() {
        global $wp_rest_helper;
        $this->namespaces = $wp_rest_helper->get_all_restml_namespaces();
        $this->rest_base = 'types';
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        foreach ($this->namespaces as $ns) {
            $this->namespace = $ns;
            register_rest_route($this->namespace, '/' . $this->rest_base, array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                    'args' => $this->get_collection_params(),
                ),
                'schema' => array($this, 'get_public_item_schema'),
            ));

            register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<type>[\w-]+)', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_item'),
                    'args' => array(
                        'context' => $this->get_context_param(array('default' => 'view')),
                    ),
                ),
                'schema' => array($this, 'get_public_item_schema'),
            ));
        }
    }
}
?>