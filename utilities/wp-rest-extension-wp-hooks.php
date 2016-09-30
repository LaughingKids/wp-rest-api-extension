<?php
define('ROUTER_SLUG_KEY','react-post-slug');
global $exclude_post_types;
$exclude_post_types = array('attachment','revision','nav_menu_item','acf-field-group','acf-field','wpcf7_contact_form');
/* change rest-api endpoint */
function api_endpoint_modifer($slug) {
    return 'json-api';
}
add_filter('rest_url_prefix',api_endpoint_modifer);

// define the name_save_pre callback
function filter_name_save_pre( $array ) {
    $array = 'temp-slug';
    return $array;
};
// add the filter
add_filter( 'name_save_pre', 'filter_name_save_pre', 10, 1 );

// define the wp_insert_post_data callback
add_filter( 'wp_insert_post_data' , 'modify_post_name' , '99', 2 );
function modify_post_name( $post ,$postarr )
{
    global $exclude_post_types;
    if(!in_array($post['post_type'], $exclude_post_types)) {
        switch($post['post_status']) {
            case 'publish':
            case 'inherit':
                $customize_slug = get_post_meta($postarr['ID'],ROUTER_SLUG_KEY,true);
                if(! empty($customize_slug)) {
                    $post['post_name'] = $customize_slug;
                } else {
                    wp_die('You have to provide meta value for post-slug');
                }
                return $post;
            default:
                return $post;
        }
    } else {
        return $post;
    }
}

// define save_post callback
add_action( 'save_post', 'add_customize_slug_meta', 10, 1 );
function add_customize_slug_meta($post_id){
    $post  = get_post($post_id);
    global $exclude_post_types;
    if(!in_array($post->post_type, $exclude_post_types)) {
        switch($post->post_status) {
            case 'auto-draft': /* initial create the post */
                add_post_meta($post_id, ROUTER_SLUG_KEY, '', true);
                break;
            default:
                break;
        }
    }
}

/* register_customize_post_type_to_rest_api */
add_action('init','register_customize_post_type_to_rest_api',25);
function register_customize_post_type_to_rest_api() {
    global $wp_post_types;
    $post_types = get_post_types();
    global $exclude_post_types;
    //be sure to set this to the name of your post type!
    if(!empty($post_types)) {
        foreach($post_types as $post_type_name) {
            if( isset( $wp_post_types[ $post_type_name ] )  && !in_array($post_type_name,$exclude_post_types)) {
                $wp_post_types[$post_type_name]->show_in_rest = true;
                $wp_post_types[$post_type_name]->rest_base = $post_type_name;
                $wp_post_types[$post_type_name]->rest_controller_class = 'WP_REST_Posts_Controller';
            }
        }
    }
}

/* register_customize_taxonomy_to_rest_api */
add_action('init','register_customize_taxonomy_to_rest_api',25);
function register_customize_taxonomy_to_rest_api() {
    global $wp_taxonomies;
    $taxonomies = get_taxonomies();
    //be sure to set this to the name of your taxonomy!
    if(!empty($taxonomies)) {
        foreach($taxonomies as $taxonomy_name) {
            if ( isset( $wp_taxonomies[ $taxonomy_name ] ) ) {
                $wp_taxonomies[ $taxonomy_name ]->show_in_rest = true;
                $wp_taxonomies[ $taxonomy_name ]->rest_base = $taxonomy_name;
                $wp_taxonomies[ $taxonomy_name ]->rest_controller_class = 'WP_REST_Terms_Controller';
            }
        }
    }
}
?>
