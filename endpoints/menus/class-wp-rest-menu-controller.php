<?php
/**
 * WP REST API Menu routes
 *
 * @package WP_REST_WPML_MENU_Controller
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_REST_Menus_Controller' ) ) :

    /**
     * WP REST Menus class.
     *
     * WP API Menus support for WP API v2.
     *
     * @package WP_API_Menus
     * @since 1.2.0
     */
    class WP_REST_WPML_MENU_Controller {

        protected $namespaces;
        protected $language_slug;
        /**
         * Get WP API namespace.
         *
         * @since 1.2.0
         * @return string
         */
        public static function get_api_namespace() {
            return 'wp/v2';
        }


        /**
         * Get WP API Menus namespace.
         *
         * @since 1.2.1
         * @return string
         */
        public static function get_plugin_namespace() {
            return 'rest/v1';
        }

        function __construct() {
            global $wp_rest_helper;
            $this->namespaces = $wp_rest_helper->get_all_restml_namespaces();
        }

        /**
         * Register menu routes for WP API v2.
         *
         * @since  1.2.0
         * @return array
         */
        public function register_routes() {
            foreach($this->namespaces as $namespace) {
                register_rest_route( $namespace, '/menus', array(
                    array(
                        'methods'  => WP_REST_Server::READABLE,
                        'callback' => array( $this, 'get_menus' ),
                    )
                ) );

                register_rest_route( $namespace, '/menus/(?P<id>\d+)', array(
                    array(
                        'methods'  => WP_REST_Server::READABLE,
                        'callback' => array( $this, 'get_menu' ),
                        'args'     => array(
                            'context' => array(
                                'default' => 'view',
                            ),
                        ),
                    )
                ) );

                register_rest_route( $namespace, '/menus/(?P<title>.*)', array(
                    array(
                        'methods'  => WP_REST_Server::READABLE,
                        'callback' => array( $this, 'get_menu' ),
                        'args'     => array(
                            'context' => array(
                                'default' => 'view',
                            ),
                        ),
                    )
                ) );

                register_rest_route( $namespace, '/menu-locations', array(
                    array(
                        'methods'  => WP_REST_Server::READABLE,
                        'callback' => array( $this, 'get_menu_locations' ),
                    )
                ) );

                register_rest_route( $namespace, '/menu-locations/(?P<location>[a-zA-Z0-9_-]+)', array(
                    array(
                        'methods'  => WP_REST_Server::READABLE,
                        'callback' => array( $this, 'get_menu_location' ),
                    )
                ) );
            }
        }


        /**
         * Get menus.
         *
         * @since  1.2.0
         * @return array All registered menus
         */
        public static function get_menus($request) {
            global $wp_rest_helper;
            $target_language = $wp_rest_helper->get_target_language($request->get_route());
            $name_space = $wp_rest_helper->get_namespace_prefix().'/'.$target_language;
//            $rest_url = trailingslashit( get_rest_url() . $name_space . '/menus/' );
            $wp_rest_helper->rest_api_language_switcher($request);
            $wp_menus = wp_get_nav_menus();
            $i = 0;
            $rest_menus = array();
            foreach ( $wp_menus as $wp_menu ) :
                $menu = (array) $wp_menu;
                $rest_menus[ $i ]                = $menu;
                $rest_menus[ $i ]['ID']          = $menu['term_id'];
                $rest_menus[ $i ]['name']        = $menu['name'];
                $rest_menus[ $i ]['slug']        = $menu['slug'];
                $rest_menus[ $i ]['description'] = $menu['description'];
                $rest_menus[ $i ]['count']       = $menu['count'];
//                $rest_menus[ $i ]['meta']['links']['collection'] = $rest_url;
//                $rest_menus[ $i ]['meta']['links']['self']       = $rest_url . $menu['term_id'];
                $i ++;
            endforeach;
            return apply_filters( 'rest_menus_format_menus', $rest_menus );
        }

        /**
         * Get a menu.
         *
         * @since  1.2.0
         * @param  $request
         * @return array Menu data
         */
        public function get_menu( $request ) {
            global $wp_rest_helper;
            $target_language = $wp_rest_helper->get_target_language($request->get_route());
            $this->language_slug = $target_language;
            $wp_rest_helper->rest_api_language_switcher($request);
//            $name_space = $wp_rest_helper->get_namespace_prefix().'/'.$target_language;
//            $rest_url       = get_rest_url() . $name_space . '/menus/';
            if($request['id'] != null) {
                $identifier = (int) $request['id'];
            } else{
                $identifier = $request['title'];
            }
            $wp_menu_object = $identifier ? wp_get_nav_menu_object( $identifier ) : array();
            $wp_menu_items  = $identifier ? wp_get_nav_menu_items( $identifier ) : array();
            if ( $wp_menu_object ) :
                $menu = (array) $wp_menu_object;
                $rest_menu['ID']          = abs( $menu['term_id'] );
                $rest_menu['name']        = $menu['name'];
                $rest_menu['slug']        = $menu['slug'];
                $rest_menu['description'] = $menu['description'];
                $rest_menu['count']       = abs( $menu['count'] );
                $rest_menu_items = array();
                //print_r($wp_menu_items); die();
                foreach ( $wp_menu_items as $item_object ) {
                    $rest_menu_items[] = $this->format_menu_item( $item_object );
                }
                $rest_menu_items = $this->nested_menu_items($rest_menu_items, 0);
                $rest_menu['items']                       = $rest_menu_items;
//                $rest_menu['meta']['links']['collection'] = $rest_url;
//                $rest_menu['meta']['links']['self']       = $rest_url . $identifier;
            endif;
            return apply_filters( 'rest_menus_format_menu', $rest_menu );
        }

        /**
         * Handle nested menu items.
         *
         * Given a flat array of menu items, split them into parent/child items
         * and recurse over them to return children nested in their parent.
         *
         * @since  1.2.0
         * @param  $menu_items
         * @param  $parent
         * @return array
         */
        private function nested_menu_items( &$menu_items, $parent = null ) {

            $parents = array();
            $children = array();

            // Separate menu_items into parents & children.
            array_map( function( $i ) use ( $parent, &$children, &$parents ){
                if ( $i['id'] != $parent && $i['parent'] == $parent ) {
                    $parents[] = $i;
                } else {
                    $children[] = $i;
                }
            }, $menu_items );

            foreach ( $parents as &$parent ) {

                if ( $this->has_children( $children, $parent['id'] ) ) {
                    $parent['children'] = $this->nested_menu_items( $children, $parent['id'] );
                }
            }

            return $parents;
        }


        /**
         * Check if a collection of menu items contains an item that is the parent id of 'id'.
         *
         * @since  1.2.0
         * @param  array $items
         * @param  int $id
         * @return array
         */
        private function has_children( $items, $id ) {
            return array_filter( $items, function( $i ) use ( $id ) {
                return $i['parent'] == $id;
            } );
        }


        /**
         * Get menu locations.
         *
         * @since 1.2.0
         * @param  $request
         * @return array All registered menus locations
         */
        public static function get_menu_locations( $request ) {

            $locations        = get_nav_menu_locations();
            $registered_menus = get_registered_nav_menus();
            $rest_url         = get_rest_url() . self::get_api_namespace() . '/menu-locations/';
            $rest_menus       = array();

            if ( $locations && $registered_menus ) :

                foreach ( $registered_menus as $slug => $label ) :

                    // Sanity check
                    if ( ! isset( $locations[ $slug ] ) ) {
                        continue;
                    }

                    $rest_menus[ $slug ]['ID']                          = $locations[ $slug ];
                    $rest_menus[ $slug ]['label']                       = $label;
                    $rest_menus[ $slug ]['meta']['links']['collection'] = $rest_url;
                    $rest_menus[ $slug ]['meta']['links']['self']       = $rest_url . $slug;

                endforeach;

            endif;

            return $rest_menus;
        }


        /**
         * Get menu for location.
         *
         * @since 1.2.0
         * @param  $request
         * @return array The menu for the corresponding location
         */
        public function get_menu_location( $request ) {

            $params     = $request->get_params();
            $location   = $params['location'];
            $locations  = get_nav_menu_locations();

            if ( ! isset( $locations[ $location ] ) ) {
                return array();
            }

            $wp_menu = wp_get_nav_menu_object( $locations[ $location ] );
            $menu_items = wp_get_nav_menu_items( $wp_menu->term_id );

            /**
             * wp_get_nav_menu_items() outputs a list that's already sequenced correctly.
             * So the easiest thing to do is to reverse the list and then build our tree
             * from the ground up
             */
            $rev_items = array_reverse ( $menu_items );
            $rev_menu = array();
            $cache = array();
            foreach ( $rev_items as $item ) :
                $formatted = array(
                    'ID'          => abs( $item->ID ),
                    'order'       => (int) $item->menu_order,
                    'parent'      => abs( $item->menu_item_parent ),
                    'title'       => $item->title,
                    'url'         => $item->url,
                    'attr'        => $item->attr_title,
                    'target'      => $item->target,
                    'classes'     => implode( ' ', $item->classes ),
                    'xfn'         => $item->xfn,
                    'description' => $item->description,
                    'object_id'   => abs( $item->object_id ),
                    'object'      => $item->object,
                    'type'        => $item->type,
                    'type_label'  => $item->type_label,
                    'children'    => array(),
                );
                // Pickup my children
                if ( array_key_exists ( $item->ID , $cache ) ) {
                    $formatted['children'] = array_reverse ( $cache[ $item->ID ] );
                }

                $formatted = apply_filters( 'rest_menus_format_menu_item', $formatted );

                if ( $item->menu_item_parent != 0 ) {
                    // Wait for parent to pick me up
                    if ( array_key_exists ( $item->menu_item_parent , $cache ) ) {
                        array_push( $cache[ $item->menu_item_parent ], $formatted );
                    } else {
                        $cache[ $item->menu_item_parent ] = array( $formatted, );
                    }
                } else {
                    array_push( $rev_menu, $formatted );
                }
            endforeach;
            return array_reverse ( $rev_menu );
        }


        /**
         * Returns all child nav_menu_items under a specific parent.
         *
         * @since   1.2.0
         * @param int   $parent_id      The parent nav_menu_item ID
         * @param array $nav_menu_items Navigation menu items
         * @param bool  $depth          Gives all children or direct children only
         * @return  array   returns filtered array of nav_menu_items
         */
        public function get_nav_menu_item_children( $parent_id, $nav_menu_items, $depth = true ) {

            $nav_menu_item_list = array();

            foreach ( (array) $nav_menu_items as $nav_menu_item ) :

                if ( $nav_menu_item->menu_item_parent == $parent_id ) :

                    $nav_menu_item_list[] = $this->format_menu_item( $nav_menu_item, true, $nav_menu_items );

                    if ( $depth ) {
                        if ( $children = $this->get_nav_menu_item_children( $nav_menu_item->ID, $nav_menu_items ) ) {
                            $nav_menu_item_list = array_merge( $nav_menu_item_list, $children );
                        }
                    }

                endif;

            endforeach;

            return $nav_menu_item_list;
        }


        /**
         * Format a menu item for REST API consumption.
         *
         * @since  1.2.0
         * @param  object|array $menu_item  The menu item
         * @param  bool         $children   Get menu item children (default false)
         * @param  array        $menu       The menu the item belongs to (used when $children is set to true)
         * @return array   a formatted menu item for REST
         */
        public function format_menu_item( $menu_item, $children = false, $menu = array() ) {
            $item = (array) $menu_item;
            $item = $this->format_menu_item_helper($item);
            $menu_item = array(
                'id'          => abs( $item['ID'] ),
                'order'       => (int) $item['menu_order'],
                'parent'      => abs( $item['menu_item_parent'] ),
                'title'       => $item['title'],
                'url'         => $item['url'],
                'attr'        => $item['attr_title'],
                'target'      => $item['target'],
                'classes'     => implode( ' ', $item['classes'] ),
                'xfn'         => $item['xfn'],
                'description' => $item['description'],
                'object_id'   => abs( $item['object_id'] ),
                'object'      => $item['object'],
                'type'        => $item['type'],
                'type_label'  => $item['type_label'],
                'slug'        => $item['slug']
            );

            if ( $children === true && ! empty( $menu ) ) {
                $menu_item['children'] = $this->get_nav_menu_item_children( $item['ID'], $menu );
            }

            return apply_filters( 'rest_menus_format_menu_item', $menu_item );
        }

        /**
         * Change backend url path to its frontend one, all paths are relative path not the absolute one.
         * url structure:
         *      page: /[$langFlag]/[$pageSlug]
         *      post: /[$langFlag]/[$postTypeSlug]/[$postSlug]
         *      taxonomy: /[$langFlag]/[$taxSlug]/[$termSlug]
         * @param $item
         * @return $item(modified)
         */
        public function format_menu_item_helper($item) {
            switch($item['type']) {
                case 'custom':
                    $item['slug'] = null;
                    break;
                case 'post_type':
                    $post = get_post($item['object_id']);
                    $item['slug'] = $post->post_name;
                    if($item['object'] != 'page') {
                        $item['url'] = '/'.$this->language_slug.'/'.$item['object'].'/'.$post->post_name;
                    } else {
                        $item['url'] = '/'.$this->language_slug.'/'.$post->post_name;
                    }
                    break;
                case 'taxonomy':
                    $item['slug'] = null;
                    $url_items = explode('/',$item['url']);
                    $i = 0;
                    while($i !== 3) {
                        array_shift($url_items);
                        $i++;
                    }
                    array_pop($url_items);
                    $item['url'] = '/'.join("/",$url_items);
                    break;
                default:
                    break;
            }
            return $item;
        }
    }


endif;




