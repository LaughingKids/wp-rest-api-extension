<?php
/**
 *
 * Enter description here ...
 * @author Thomas Laughing Wong
 * how to change page: json-api/restml/all/wpcf?form_id=3
 *
 */

class WP_REST_WPML_WPCF7_Controller extends WP_REST_Posts_Controller {
    protected $namespaces;
    function __construct()
    {
        global $wp_rest_helper;
        $this->namespaces = $wp_rest_helper->get_all_restml_namespaces();
        $this->rest_base = 'wpcf';
    }
    /**
     * Restister the routes for contact form 7
     */
    public function register_routes()
    {
        foreach ($this->namespaces as $ns) {
            $this->namespace = $ns;
            $rest_url = '/'.$this->rest_base.'/(?<id>[\d]+)';
            $dispatchers = array();
            $get_req_dispatcher = array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this,'get_form'),
                'permission_callback' => array($this,'get_items_permissions_check'),
                'args' => $this->get_collection_params()
            );
            $post_req_dispatcher = array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this,'form_callback'),
                'permission_callback'=> array($this,'create_item_permissions_check'),
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE)

            );
            $dispatchers[] = $get_req_dispatcher;
            $dispatchers[] = $post_req_dispatcher;
            $dispatchers['schema'] = array( $this, 'get_public_item_schema' );
            register_rest_route($this->namespace,$rest_url,$dispatchers);
        }
    }

    public function create_item_permissions_check ($request) {
        $body = $request->get_body_params();
        return wpcf7_verify_nonce( $body['authority'], $request['id'] );
    }

    public function form_callback($request){
        $post_data = stripslashes($request['form_content']);
        $post_array = json_decode($post_data);
        $the_form = get_post($request['id']);
        if( class_exists( 'WPCF7_ContactForm' ) ) {
            $contact_form = WPCF7_ContactForm::get_instance($request['id']);
            return($contact_form->submit(true));
        } else {
            return new WP_Error( 'call_undefined_class', __( 'This route is based on contact-form plugin, please install it.' ), array( 'status' => '500' ) );
        }
    }

    public function get_form($request) {
        $fields = $this->filter_form_html($request);
        $json_fields = array();
        foreach($fields as $field) {
            unset($field->attr['class']);
            $json_fields[] = $field->attr;
        }
        $the_form = array(
            'id' => $request['id'],
            'fields' => $json_fields
        );
        $form_json = rest_ensure_response( $the_form );
        return $form_json;
    }

    private function filter_form_html($request) {
        $contact_form = WPCF7_ContactForm::get_instance($request['id']);
        $htmlString = $contact_form->form_html();
        $html = str_get_html($htmlString);
        $inputs = $html->find('input');
        return $inputs;
    }
}