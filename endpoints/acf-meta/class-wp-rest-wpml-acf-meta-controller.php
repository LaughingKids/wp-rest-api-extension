<?php
/**
 *
 * Enter description here ...
 * @author Thomas Laughing Wong
 * This interface is written for acf plugin
 * how to change page: json-api/restml/en/acf/fields/{object_name}
 *
 */
class WP_REST_WPML_ACF_Meta_Controller extends WP_REST_Posts_Controller{

    protected $namespaces;

    function __construct() {
        global $wp_rest_helper;
        $this->namespaces = $wp_rest_helper->get_all_restml_namespaces();
        $this->rest_base = 'acf';
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        foreach($this->namespaces as $namespace) {
            $this->namespace = $namespace;
            /* without fields keys */
            register_rest_route($this->namespace,'/'.$this->rest_base . '/(?P<id>[\d\w]+)/',array(
                array (
                    'methods'         => WP_REST_Server::READABLE,
                    'callback'        => array( $this, 'wpml_get_acf_fields' ),
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                    'args'            => $this->get_collection_params()
                ),
            ));
            /* with fields keys */
            register_rest_route($this->namespace,'/'.$this->rest_base . '/(?P<id>[\d\w]+)/(?P<key>[\w]+)',array(
                array (
                    'methods'         => WP_REST_Server::READABLE,
                    'callback'        => array( $this, 'wpml_get_field' ),
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                    'args'            => $this->get_collection_params()
                ),
            ));
        }
    }

    /**
     * Get posts based on language tag in namespace
     * recall WP_REST_Posts_Controller->get_items
     * just change wpml language before call that
     */
    public function wpml_get_acf_fields($request){
        if(class_exists('acf_pro')) {
            $fields = get_field_objects($request['id']);
            if($fields) {
                foreach($fields as $field_key => $field) {
                    $data[$field_key] = $field['value'];
                }
            }
        } else {
            $data['error'] = 'ACF Pro Needed';
        }
        return $data;
    }

    /**
     * Get post base on field key
     * recall WP_REST_Posts_Controller->get_item
     * just check target post id is in that target language.
     */
    public function wpml_get_field($request) {
        $option = get_field($request['key'],$request['id']);
        if($option) {
            $optObj = new stdClass();
            $optObj->key = $request['key'];
            $optObj->value = $option;
            return $optObj;
        }
        return null;
    }
}

?>