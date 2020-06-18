<?php

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

class SWCFPC_Backend
{

    private $main_instance = null;
    
    private $objects;

    private $debug_enabled = false;
    private $debug_msg     = "";

    private $swcfpc_cloudflare_super_page_cache_obj = false;
    private $sw_cloudflare_pagecache = false;

    function __construct( $main_instance )
    {

        $this->main_instance = $main_instance;

        $this->actions();

    }


    function actions() {

        add_action( 'admin_enqueue_scripts', array($this, 'load_custom_wp_admin_styles_and_script') );

        add_action( 'admin_menu', array($this, 'add_admin_menu_pages') );

        // Admin toolbar options
        add_action( 'admin_bar_menu', array($this, 'add_toolbar_items'), PHP_INT_MAX );

        // Action rows
        add_filter( 'post_row_actions', array($this, 'add_post_row_actions'), PHP_INT_MAX, 2 );
        add_filter( 'page_row_actions', array($this, 'add_post_row_actions'), PHP_INT_MAX, 2 );

        // Ajax nonce
        add_action('admin_footer', array($this, 'add_ajax_nonce_everywhere'));

    }


    function load_custom_wp_admin_styles_and_script() {

        $css_version = "1.4.2";
        $js_version = "1.2.3";

        $wp_scripts = wp_scripts();

        wp_register_style( 'swcfpc_jquery_ui_css', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css', false );
        wp_register_style( 'swcfpc_admin_css', SWCFPC_PLUGIN_URL. 'assets/css/style.css', array('swcfpc_jquery_ui_css'), $css_version );

        wp_register_script( 'swcfpc_admin_js', SWCFPC_PLUGIN_URL. 'assets/js/backend.js', array('jquery', 'jquery-ui-dialog'), $js_version, true );
        wp_add_inline_script( 'swcfpc_admin_js', 'var swcfpc_ajax_url = "'.admin_url('admin-ajax.php').'";', 'before' );

        wp_enqueue_style( 'swcfpc_admin_css' );
        wp_enqueue_script( 'swcfpc_admin_js' );

    }


    function add_ajax_nonce_everywhere() {

        ?>

        <div id="swcfpc-ajax-nonce" style="display:none;"><?php echo wp_create_nonce("ajax-nonce-string"); ?></div>

        <?php

    }


    function add_debug_string($title, $content) {

        $this->debug_msg .= "<hr>";
        $this->debug_msg .= "<br><h2>$title</h2><div>$content</div>";

    }


    function add_toolbar_items( $admin_bar ) {

        $this->objects = $this->main_instance->get_objects();

        if( $this->main_instance->get_single_config("cf_remove_purge_option_toolbar", 0) == 0 ) {

            if ($this->main_instance->get_single_config("cf_cache_enabled", 0) > 0) {

                global $post;

                $admin_bar->add_menu(array(
                    'id' => 'wp-cloudflare-super-page-cache-toolbar-container',
                    'title' => __('Purge CF Cache', 'wp-cloudflare-page-cache'),
                    'href' => '#',
                ));

                $admin_bar->add_menu(array(
                    'id' => 'wp-cloudflare-super-page-cache-toolbar-purge-all',
                    'parent' => 'wp-cloudflare-super-page-cache-toolbar-container',
                    'title' => __('Purge whole Cloudflare Cache', 'wp-cloudflare-page-cache'),
                    //'href' => add_query_arg(array("page" => "wp-cloudflare-super-page-cache-index", $this->objects["cache_controller"]->get_cache_buster() => 1, "swcfpc-purge-cache" => 1), admin_url("options-general.php")),
                    'href' => '#'
                ));

                if (is_object($post)) {

                    $admin_bar->add_menu(array(
                        'id' => 'wp-cloudflare-super-page-cache-toolbar-purge-single',
                        'parent' => 'wp-cloudflare-super-page-cache-toolbar-container',
                        'title' => __('Purge only current page cache', 'wp-cloudflare-page-cache'),
                        'href' => "#" . $post->ID,
                    ));

                }

            }

        }

    }


    function add_post_row_actions( $actions, $post ) {

        $this->objects = $this->main_instance->get_objects();

        $actions['swcfpc_single_purge'] = '<a class="swcfpc_action_row_single_post_cache_purge" data-post_id="'.$post->ID.'" href="#" target="_blank">'.__('Purge CF Cache', 'wp-cloudflare-page-cache').'</a>';

        return $actions;

    }


    function add_admin_menu_pages() {

        add_submenu_page(
            'options-general.php',
            __( 'WP Cloudflare Super Page Cache', 'wp-cloudflare-page-cache' ),
            __( 'WP Cloudflare Super Page Cache', 'wp-cloudflare-page-cache' ),
            'manage_options',
            'wp-cloudflare-super-page-cache-index',
            array($this, 'admin_menu_page_index')
            //"data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg0KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDE5LjAuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPg0KPHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJMYXllcl8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCINCgkgdmlld0JveD0iMCAwIDUxMi4wMTYgNTEyLjAxNiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNTEyLjAxNiA1MTIuMDE2OyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+DQo8cGF0aCBzdHlsZT0iZmlsbDojRkZDRTU0OyIgZD0iTTE3LjI1LDQ5My4xMzJjMy42MjUtMTAuMTg4LDguMzQ0LTIzLjE0MSwxMy42MjUtMzYuNTYzYzE5Ljg3NS01MC42NDIsMzAuNDA3LTY1Ljc4MiwzNC45MzgtNzAuMjk4DQoJYzYuNzgxLTYuNzk3LDE1LjE4OC0xMS4zNzUsMjQuMzEzLTEzLjI2NmwzLjE1Ni0wLjY1NmwzNS4zNDQtMzUuNzVsNDIuMzEyLDQ4Ljg3NWwtMzIuOTA2LDMxLjUxNmwtMC42ODgsMy4yMzUNCgljLTEuODc1LDkuMTI1LTYuNDY5LDE3LjUzMS0xMy4yNSwyNC4zNDRjLTQuNTMxLDQuNS0xOS42NTYsMTUuMDYyLTcwLjI4MiwzNC45MjNDNDAuMzc2LDQ4NC43NTcsMjcuNDA2LDQ4OS41MDcsMTcuMjUsNDkzLjEzMnoiLz4NCjxwYXRoIHN0eWxlPSJmaWxsOiNGNkJCNDI7IiBkPSJNMTI5LjE1OCwzMjAuOTQzTDg3Ljk3LDM2Mi41ODRjLTEwLjcxOSwyLjIxOS0yMS4xMjYsNy42MDktMjkuNjg4LDE2LjE3Mg0KCUMzNi40MDcsNDAwLjYzLDAsNTEwLjM2NiwwLDUxMC4zNjZzMTA5LjcyLTM2LjM5MSwxMzEuNjI2LTU4LjI4MmM4LjUzMS04LjU0NywxMy45MzgtMTguOTY5LDE2LjE1Ni0yOS43MDNsMzcuODEyLTM2LjIyDQoJTDEyOS4xNTgsMzIwLjk0M3ogTTEzMy4wNjQsNDA3LjAwNWwtNC43ODEsNC41OTRsLTEuMzQ0LDYuNDg0Yy0xLjQ2OSw3LjA3OS01LjA2MiwxMy42NDItMTAuMzc1LDE4Ljk1NA0KCWMtMS43NSwxLjc1LTEzLjIxOSwxMS41NzgtNjYuNTYzLDMyLjUxN2MtNS4wOTQsMS45ODQtMTAuMDk0LDMuOTA2LTE0LjkwNiw1LjcwM2MxLjgxMi00LjgxMiwzLjcxOS05LjgxMiw1LjcxOS0xNC44NzYNCgljMjAuOTM4LTUzLjM2LDMwLjc1LTY0LjgyOSwzMi41MzEtNjYuNTc5YzUuMzEzLTUuMzI4LDExLjg3Ni04LjkwNiwxOC45MzgtMTAuMzU5bDYuMzEyLTEuMzEybDQuNTMxLTQuNTc4bDI0Ljk2OS0yNS4yODENCglsMjguMTU2LDMyLjUxNkwxMzMuMDY0LDQwNy4wMDV6Ii8+DQo8Zz4NCgk8cGF0aCBzdHlsZT0iZmlsbDojREE0NDUzOyIgZD0iTTE5OS45MDksNDIzLjM5N2M1Ljk2OS0yLjc5NywxMS45MzgtNS43NjcsMTcuODc1LTguODc2bDEyMS41MDEtODYuNzgxDQoJCWM0Ljk2OS00LjY0MSw5Ljg3NS05LjM5MSwxNC43MTktMTQuMjAzYzIuNzgxLTIuODEyLDUuNTYzLTUuNjI1LDguMjgyLTguNDY5Yy0wLjQ2OSw1NS4zNTktMjUuODQ1LDExNS45MjMtNzQuMDMyLDE2NC4xMjcNCgkJYy0xNi4wNjIsMTYuMDQ3LTMzLjQ2OSwyOS41NjItNTEuNjI1LDQwLjQ4NGMtMC4xMjUsMC4wNzgtMC44NDUsMC41LTAuODQ1LDAuNWMtNC4wMzEsMi4xODgtOS4xODgsMS41NzgtMTIuNTk0LTEuODI4DQoJCWMtMS4xMjUtMS4xNDEtMS45MzgtMi40NjktMi40MzgtMy44NzVjMCwwLTAuMzc1LTEuMTA5LTAuNDY5LTEuNTk0bC0yMS45MzgtNzguNzY3DQoJCUMxOTguODc4LDQyMy44ODEsMTk5LjM3OCw0MjMuNjMxLDE5OS45MDksNDIzLjM5N3oiLz4NCgk8cGF0aCBzdHlsZT0iZmlsbDojREE0NDUzOyIgZD0iTTIwNy41MzQsMTUwLjI2OWMtMi44NDQsMi43MzQtNS42NTYsNS41MTYtOC40NjksOC4zMTJjLTQuODEzLDQuODI4LTkuNTYzLDkuNzM0LTE0LjE4OCwxNC43MDMNCgkJYy0yMS4yODEsMy04Ni44MTIsMTIxLjUxNy04Ni44MTIsMTIxLjUxN2MtMy4wOTQsNS45MzgtNi4wNjIsMTEuODkyLTguODc1LDE3Ljg3NmMtMC4yNSwwLjUxNi0wLjQ2OSwxLjAzMS0wLjcxOSwxLjU0Nw0KCQlMOS42ODgsMjkyLjI4NWMtMC40NjktMC4wOTQtMS41OTQtMC40NjktMS41OTQtMC40NjljLTEuNDA2LTAuNS0yLjcxOS0xLjMxMi0zLjg3NS0yLjQ1M2MtMy40MDYtMy40MDYtNC04LjU0Ny0xLjgxMi0xMi41OTQNCgkJYzAsMCwwLjQwNi0wLjcwMywwLjUtMC44MjhjMTAuOTA2LTE4LjE1NywyNC40MDYtMzUuNTYzLDQwLjQ2OS01MS42MjVDOTEuNTk1LDE3Ni4wOTcsMTUyLjE1OCwxNTAuNzIyLDIwNy41MzQsMTUwLjI2OXoiLz4NCjwvZz4NCjxwYXRoIHN0eWxlPSJmaWxsOiNFNkU5RUQ7IiBkPSJNMTk3LjAwMywxNTEuMDVjLTYwLjQwOCw2MC40MjItMTAzLjk3LDEyOS40MzgtMTI4LjI1MiwxOTYuMjk5DQoJYy0xLjI4MSwzLjc1LTAuNDY5LDguMDMxLDIuNTMxLDExLjAxNmw4Mi45MDcsODIuOTM4YzMsMi45NjksNy4yODEsMy43OTcsMTEuMDMxLDIuNTE2DQoJYzY2Ljg3Ni0yNC4yODIsMTM1Ljg3Ny02Ny44MjksMTk2LjI4NS0xMjguMjUxYzkzLjg3Ni05My44NDUsMTQ2LjU2My0yMDcuMDgxLDE1MC41MDEtMzAzLjY0NWMwLjEyNS0yLjg3NS0wLjkwNi02LjA0Ny0zLjA5NC04LjI1DQoJYy0yLjIxOS0yLjIwMy01LjM3NS0zLjIzNC04LjI4MS0zLjEwOUM0MDQuMDY5LDQuNTAxLDI5MC44NDgsNTcuMjA1LDE5Ny4wMDMsMTUxLjA1eiIvPg0KPGc+DQoJPHBhdGggc3R5bGU9ImZpbGw6IzQzNEE1NDsiIGQ9Ik0zMTcuNTk4LDIzNy41MzVjLTExLjM3NSwwLTIyLjA2Mi00LjQzOC0zMC4wOTQtMTIuNDY5Yy04LjAzMS04LjA0Ny0xMi40NjktMTguNzM1LTEyLjQ2OS0zMC4xMQ0KCQlzNC40MzgtMjIuMDYzLDEyLjQ2OS0zMC4xMWM4LjAzMS04LjAzMSwxOC43NS0xMi40NjksMzAuMDk0LTEyLjQ2OWMxMS4zNzUsMCwyMi4wNjIsNC40MzgsMzAuMTI1LDEyLjQ2OQ0KCQljMTYuNTk1LDE2LjYxLDE2LjU5NSw0My42MjUsMCw2MC4yMmMtOC4wNjIsOC4wMzEtMTguNzUsMTIuNDY5LTMwLjA5NCwxMi40NjlDMzE3LjU5OCwyMzcuNTM1LDMxNy41OTgsMjM3LjUzNSwzMTcuNTk4LDIzNy41MzV6Ig0KCQkvPg0KCTxwYXRoIHN0eWxlPSJmaWxsOiM0MzRBNTQ7IiBkPSJNMjI3LjI4NCwzMjcuODQ5Yy0xMS4zNzUsMC0yMi4wNjItNC40MjItMzAuMDk0LTEyLjQ2OWMtOC4wMzItOC4wMzEtMTIuNDctMTguNzM1LTEyLjQ3LTMwLjA5NQ0KCQljMC0xMS4zNzUsNC40MzgtMjIuMDc4LDEyLjQ3LTMwLjEyNWM4LjAzMS04LjAzMSwxOC43MTktMTIuNDY5LDMwLjA5NC0xMi40NjljMTEuMzc2LDAsMjIuMDYzLDQuNDM4LDMwLjEyNiwxMi40NjkNCgkJYzE2LjU5NCwxNi42MSwxNi41OTQsNDMuNjI2LDAsNjAuMjJDMjQ5LjM0NywzMjMuNDI3LDIzOC42NiwzMjcuODQ5LDIyNy4yODQsMzI3Ljg0OUwyMjcuMjg0LDMyNy44NDl6Ii8+DQo8L2c+DQo8Zz4NCgk8cGF0aCBzdHlsZT0iZmlsbDojQ0NEMUQ5OyIgZD0iTTM1NS4yNTQsMTU3LjMzMWMtMTAuMDYyLTEwLjA0Ny0yMy40MzgtMTUuNTk0LTM3LjY1Ni0xNS41OTRjLTE0LjE4OCwwLTI3LjU2Miw1LjU0Ny0zNy42MjUsMTUuNTk0DQoJCWMtMTAuMDMxLDEwLjA0Ny0xNS41OTQsMjMuNDIyLTE1LjU5NCwzNy42MjVjMCwxNC4yMTksNS41NjIsMjcuNTc5LDE1LjU5NCwzNy42NDFjMTAuMDYyLDEwLjA0NiwyMy40MzgsMTUuNTc4LDM3LjYyNSwxNS41NzgNCgkJYzE0LjIxOSwwLDI3LjU5NC01LjUzMSwzNy42NTYtMTUuNTc4QzM3Ni4wMDUsMjExLjg0NywzNzYuMDA1LDE3OC4wODIsMzU1LjI1NCwxNTcuMzMxeiBNMzQwLjE5MiwyMTcuNTM1DQoJCWMtNi4yNSw2LjIzNC0xNC40MDYsOS4zNTktMjIuNTk0LDkuMzU5Yy04LjE1NiwwLTE2LjM0NC0zLjEyNS0yMi41NjItOS4zNTljLTEyLjQ2OS0xMi40NjktMTIuNDY5LTMyLjY4OCwwLTQ1LjE1Nw0KCQljNi4yMTktNi4yMzQsMTQuNDA2LTkuMzQ0LDIyLjU2Mi05LjM0NGM4LjE4OCwwLDE2LjM0NCwzLjEwOSwyMi41OTQsOS4zNDRDMzUyLjY2LDE4NC44NDcsMzUyLjY2LDIwNS4wNjYsMzQwLjE5MiwyMTcuNTM1eiIvPg0KCTxwYXRoIHN0eWxlPSJmaWxsOiNDQ0QxRDk7IiBkPSJNMjI3LjI4NCwyMzIuMDY3Yy0xNC4yMTksMC0yNy41NjIsNS41MzEtMzcuNjI2LDE1LjU3OGMtMTAuMDYyLDEwLjA0Ni0xNS41OTQsMjMuNDIyLTE1LjU5NCwzNy42NDENCgkJYzAsMTQuMjA0LDUuNTMxLDI3LjU2MywxNS41OTQsMzcuNjI2YzEwLjA2MywxMC4wNDcsMjMuNDA3LDE1LjU5NCwzNy42MjYsMTUuNTk0YzE0LjIyLDAsMjcuNTk1LTUuNTQ3LDM3LjY1OC0xNS41OTQNCgkJYzIwLjc1LTIwLjc1LDIwLjc1LTU0LjUxNywwLTc1LjI2N0MyNTQuODc5LDIzNy41OTgsMjQxLjUwNCwyMzIuMDY3LDIyNy4yODQsMjMyLjA2N3ogTTI0OS44NzksMzA3Ljg0OQ0KCQljLTYuMjUsNi4yNS0xNC40MDcsOS4zNTktMjIuNTk1LDkuMzU5Yy04LjE1NiwwLTE2LjM0NC0zLjEwOS0yMi41NjItOS4zNTljLTEyLjQ3LTEyLjQ3LTEyLjQ3LTMyLjY4OCwwLTQ1LjE1Nw0KCQljNi4yMTktNi4yMzUsMTQuNDA2LTkuMzQ0LDIyLjU2Mi05LjM0NGM4LjE4OCwwLDE2LjM0NSwzLjEwOSwyMi41OTUsOS4zNDRDMjYyLjM0OCwyNzUuMTYsMjYyLjM0OCwyOTUuMzc5LDI0OS44NzksMzA3Ljg0OXoiLz4NCjwvZz4NCjxwYXRoIHN0eWxlPSJmaWxsOiNEQTQ0NTM7IiBkPSJNNDc5LjIyNSwxNDUuODE2TDM2Ni43NTUsMzMuMzYxYzQ1LjgxMy0xOS45MjIsOTEuNDctMzEuMDYzLDEzMy44NzYtMzIuNzk3DQoJYzIuOTA2LTAuMTI1LDYuMDYyLDAuOTA2LDguMjgxLDMuMTA5YzIuMTg4LDIuMjAzLDMuMjE5LDUuMzc1LDMuMDk0LDguMjVDNTEwLjI4Nyw1NC4zNjEsNDk5LjEzMSwxMDAuMDAzLDQ3OS4yMjUsMTQ1LjgxNnoiLz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjwvc3ZnPg0K"
        );

        add_submenu_page(
            'wp-cloudflare-super-page-cache-index',
            __( 'Settings', 'wp-cloudflare-page-cache' ),
            __( 'Settings', 'wp-cloudflare-page-cache' ),
            'manage_options',
            'wp-cloudflare-super-page-cache-index',
            array($this, 'admin_menu_page_index')
        );

        add_submenu_page(
            null,
            __( 'WP Cloudflare Super Page Cache Nginx Settings', 'wp-cloudflare-page-cache' ),
            __( 'WP Cloudflare Super Page Cache Nginx Settings', 'wp-cloudflare-page-cache' ),
            'manage_options',
            'wp-cloudflare-super-page-cache-nginx-settings',
            array($this, 'admin_menu_page_nginx_settings')
        );

    }


    function admin_menu_page_index() {

        if( !current_user_can("manage_options") ) {
            die( __("Permission denied", 'wp-cloudflare-page-cache') );
        }

        $this->objects = $this->main_instance->get_objects();

        $error_msg      = "";
        $success_msg    = "";
        $domain_found   = false;
        $domain_zone_id = "";
        $wizard_active  = true;

        if( $this->main_instance->get_cloudflare_api_zone_id() != "" && $this->objects["cache_controller"]->is_cache_enabled() )
            $wizard_active = false;

        $nginx_instructions_page_url = add_query_arg( array("page" => "wp-cloudflare-super-page-cache-nginx-settings"), admin_url("options-general.php") );


        // Save settings
        if( isset($_POST['swcfpc_submit_general']) ) {

            $this->main_instance->set_single_config("cf_auth_mode", intval($_POST['swcfpc_cf_auth_mode']));
            $this->main_instance->set_single_config("cf_email", sanitize_email($_POST['swcfpc_cf_email']));
            $this->main_instance->set_single_config("cf_apikey", $_POST['swcfpc_cf_apikey']);
            $this->main_instance->set_single_config("cf_apitoken", $_POST['swcfpc_cf_apitoken']);
            $this->main_instance->set_single_config("cf_apitoken_domain", $_POST['swcfpc_cf_apitoken_domain']);

            // Force refresh on Cloudflare api class
            $this->objects["cloudflare"]->set_auth_mode( intval($_POST['swcfpc_cf_auth_mode']) );
            $this->objects["cloudflare"]->set_api_key( $_POST['swcfpc_cf_apikey'] );
            $this->objects["cloudflare"]->set_api_email( $_POST['swcfpc_cf_email'] );
            $this->objects["cloudflare"]->set_api_token( $_POST['swcfpc_cf_apitoken'] );

            if( isset($_POST['swcfpc_cf_apitoken_domain']) && strlen(trim($_POST['swcfpc_cf_apitoken_domain'])) > 0 )
                $this->objects["cloudflare"]->set_api_token_domain( $_POST['swcfpc_cf_apitoken_domain'] );

            // Logs
            $this->main_instance->set_single_config("log_enabled", intval($_POST['swcfpc_log_enabled']));

            if( isset($_POST['swcfpc_log_expiration']) ) {
                $this->main_instance->set_single_config("log_expiration", intval($_POST['swcfpc_log_expiration']));
            }

            if( $this->main_instance->get_single_config("log_enabled", 0) > 0 )
                $this->objects["logs"]->enable_logging();
            else
                $this->objects["logs"]->disable_logging();

            // Salvataggio immediato per consentire di applicare subito i settaggi di connessione
            $this->main_instance->update_config();

            if( isset($_POST['swcfpc_post_per_page']) && intval($_POST['swcfpc_post_per_page']) >= 0 ) {
                $this->main_instance->set_single_config("cf_post_per_page", intval($_POST['swcfpc_post_per_page']));
            }

            if( isset($_POST['swcfpc_maxage']) && intval($_POST['swcfpc_maxage']) >= 0 ) {
                $this->main_instance->set_single_config("cf_maxage", intval($_POST['swcfpc_maxage']));
            }

            if( isset($_POST['swcfpc_browser_maxage']) && intval($_POST['swcfpc_browser_maxage']) >= 0 ) {
                $this->main_instance->set_single_config("cf_browser_maxage", intval($_POST['swcfpc_browser_maxage']));
            }

            if( isset($_POST['swcfpc_cf_zoneid']) ) {
                $this->main_instance->set_single_config("cf_zoneid", trim($_POST['swcfpc_cf_zoneid']));
            }

            if( isset($_POST['swcfpc_cf_subdomain']) ) {
                $this->main_instance->set_single_config("cf_subdomain", trim($_POST['swcfpc_cf_subdomain']));
            }

            if( isset($_POST['swcfpc_cf_auto_purge']) ) {
                $this->main_instance->set_single_config("cf_auto_purge", intval($_POST['swcfpc_cf_auto_purge']));
            }

            if( isset($_POST['swcfpc_cf_auto_purge_all']) ) {
                $this->main_instance->set_single_config("cf_auto_purge_all", intval($_POST['swcfpc_cf_auto_purge_all']));
            }

            if( isset($_POST['swcfpc_cf_bypass_404']) ) {
                $this->main_instance->set_single_config("cf_bypass_404", intval($_POST['swcfpc_cf_bypass_404']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_404", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_single_post']) ) {
                $this->main_instance->set_single_config("cf_bypass_single_post", intval($_POST['swcfpc_cf_bypass_single_post']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_single_post", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_author_pages']) ) {
                $this->main_instance->set_single_config("cf_bypass_author_pages", intval($_POST['swcfpc_cf_bypass_author_pages']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_author_pages", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_search_pages']) ) {
                $this->main_instance->set_single_config("cf_bypass_search_pages", intval($_POST['swcfpc_cf_bypass_search_pages']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_search_pages", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_feeds']) ) {
                $this->main_instance->set_single_config("cf_bypass_feeds", intval($_POST['swcfpc_cf_bypass_feeds']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_feeds", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_category']) ) {
                $this->main_instance->set_single_config("cf_bypass_category", intval($_POST['swcfpc_cf_bypass_category']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_category", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_tags']) ) {
                $this->main_instance->set_single_config("cf_bypass_tags", intval($_POST['swcfpc_cf_bypass_tags']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_tags", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_archives']) ) {
                $this->main_instance->set_single_config("cf_bypass_archives", intval($_POST['swcfpc_cf_bypass_archives']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_archives", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_home']) ) {
                $this->main_instance->set_single_config("cf_bypass_home", intval($_POST['swcfpc_cf_bypass_home']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_home", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_front_page']) ) {
                $this->main_instance->set_single_config("cf_bypass_front_page", intval($_POST['swcfpc_cf_bypass_front_page']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_front_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_pages']) ) {
                $this->main_instance->set_single_config("cf_bypass_pages", intval($_POST['swcfpc_cf_bypass_pages']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_pages", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_amp']) ) {
                $this->main_instance->set_single_config("cf_bypass_amp", intval($_POST['swcfpc_cf_bypass_amp']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_amp", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_logged_in']) ) {
                $this->main_instance->set_single_config("cf_bypass_logged_in", intval($_POST['swcfpc_cf_bypass_logged_in']));
            }

            if( isset($_POST['swcfpc_cf_bypass_sitemap']) ) {
                $this->main_instance->set_single_config("cf_bypass_sitemap", intval($_POST['swcfpc_cf_bypass_sitemap']));
            }

            if( isset($_POST['swcfpc_cf_bypass_file_robots']) ) {
                $this->main_instance->set_single_config("cf_bypass_file_robots", intval($_POST['swcfpc_cf_bypass_file_robots']));
            }

            if( isset($_POST['swcfpc_cf_bypass_ajax']) ) {
                $this->main_instance->set_single_config("cf_bypass_ajax", intval($_POST['swcfpc_cf_bypass_ajax']));
            }

            if( isset($_POST['swcfpc_cf_bypass_post']) ) {
                $this->main_instance->set_single_config("cf_bypass_post", intval($_POST['swcfpc_cf_bypass_post']));
            }

            if( isset($_POST['swcfpc_cf_bypass_query_var']) ) {
                $this->main_instance->set_single_config("cf_bypass_query_var", intval($_POST['swcfpc_cf_bypass_query_var']));
            }

            // WooCommerce
            if( isset($_POST['swcfpc_cf_auto_purge_woo_product_page']) ) {
                $this->main_instance->set_single_config("cf_auto_purge_woo_product_page", intval($_POST['swcfpc_cf_auto_purge_woo_product_page']));
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_cart_page']) ) {
                $this->main_instance->set_single_config("cf_bypass_woo_cart_page", intval($_POST['swcfpc_cf_bypass_woo_cart_page']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_woo_cart_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_checkout_page']) ) {
                $this->main_instance->set_single_config("cf_bypass_woo_checkout_page", intval($_POST['swcfpc_cf_bypass_woo_checkout_page']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_woo_checkout_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_checkout_pay_page']) ) {
                $this->main_instance->set_single_config("cf_bypass_woo_checkout_pay_page", intval($_POST['swcfpc_cf_bypass_woo_checkout_pay_page']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_woo_checkout_pay_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_shop_page']) ) {
                $this->main_instance->set_single_config("cf_bypass_woo_shop_page", intval($_POST['swcfpc_cf_bypass_woo_shop_page']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_woo_shop_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_pages']) ) {
                $this->main_instance->set_single_config("cf_bypass_woo_pages", intval($_POST['swcfpc_cf_bypass_woo_pages']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_woo_pages", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_product_tax_page']) ) {
                $this->main_instance->set_single_config("cf_bypass_woo_product_tax_page", intval($_POST['swcfpc_cf_bypass_woo_product_tax_page']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_woo_product_tax_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_product_tag_page']) ) {
                $this->main_instance->set_single_config("cf_bypass_woo_product_tag_page", intval($_POST['swcfpc_cf_bypass_woo_product_tag_page']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_woo_product_tag_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_product_cat_page']) ) {
                $this->main_instance->set_single_config("cf_bypass_woo_product_cat_page", intval($_POST['swcfpc_cf_bypass_woo_product_cat_page']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_woo_product_cat_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_product_page']) ) {
                $this->main_instance->set_single_config("cf_bypass_woo_product_page", intval($_POST['swcfpc_cf_bypass_woo_product_page']));
            }
            else {
                $this->main_instance->set_single_config("cf_bypass_woo_product_page", 0);
            }

            // W3TC
            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_minfy']) ) {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_minfy", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_minfy']));
            }
            else {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_minfy", 0);
            }

            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_posts']) ) {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_posts", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_posts']));
            }
            else {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_posts", 0);
            }

            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_objectcache']) ) {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_objectcache", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_objectcache']));
            }
            else {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_objectcache", 0);
            }

            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_fragmentcache']) ) {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_fragmentcache", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_fragmentcache']));
            }
            else {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_fragmentcache", 0);
            }

            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_dbcache']) ) {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_dbcache", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_dbcache']));
            }
            else {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_dbcache", 0);
            }

            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_all']) ) {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_all", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_all']));
            }
            else {
                $this->main_instance->set_single_config("cf_w3tc_purge_on_flush_all", 0);
            }

            // LITESPEED CACHE
            if( isset($_POST['swcfpc_cf_litespeed_purge_on_cache_flush']) ) {
                $this->main_instance->set_single_config("cf_litespeed_purge_on_cache_flush", intval($_POST['swcfpc_cf_litespeed_purge_on_cache_flush']));
            }

            // WP FASTEST CACHE
            if( isset($_POST['swcfpc_cf_wp_fastest_cache_purge_on_cache_flush']) ) {
                $this->main_instance->set_single_config("cf_wp_fastest_cache_purge_on_cache_flush", intval($_POST['swcfpc_cf_wp_fastest_cache_purge_on_cache_flush']));
            }

            // HUMMINGBIRD
            if( isset($_POST['swcfpc_cf_hummingbird_purge_on_cache_flush']) ) {
                $this->main_instance->set_single_config("cf_hummingbird_purge_on_cache_flush", intval($_POST['swcfpc_cf_hummingbird_purge_on_cache_flush']));
            }

            // WP ROCKET
            if( isset($_POST['swcfpc_cf_wp_rocket_purge_on_post_flush']) ) {
                $this->main_instance->set_single_config("cf_wp_rocket_purge_on_post_flush", intval($_POST['swcfpc_cf_wp_rocket_purge_on_post_flush']));
            }
            else {
                $this->main_instance->set_single_config("cf_wp_rocket_purge_on_post_flush", 0);
            }

            if( isset($_POST['swcfpc_cf_wp_rocket_purge_on_domain_flush']) ) {
                $this->main_instance->set_single_config("cf_wp_rocket_purge_on_domain_flush", intval($_POST['swcfpc_cf_wp_rocket_purge_on_domain_flush']));
            }
            else {
                $this->main_instance->set_single_config("cf_wp_rocket_purge_on_domain_flush", 0);
            }

            // WP Super Cache
            if( isset($_POST['swcfpc_cf_wp_super_cache_on_cache_flush']) ) {
                $this->main_instance->set_single_config("cf_wp_super_cache_on_cache_flush", intval($_POST['swcfpc_cf_wp_super_cache_on_cache_flush']));
            }
            else {
                $this->main_instance->set_single_config("cf_wp_super_cache_on_cache_flush", 0);
            }

            // Strip cookies
            if( isset($_POST['swcfpc_cf_strip_cookies']) ) {
                $this->main_instance->set_single_config("cf_strip_cookies", intval($_POST['swcfpc_cf_strip_cookies']));
            }

            // Htaccess
            if( isset($_POST['swcfpc_cf_cache_control_htaccess']) ) {
                $this->main_instance->set_single_config("cf_cache_control_htaccess", intval($_POST['swcfpc_cf_cache_control_htaccess']));
            }

            if( isset($_POST['swcfpc_cf_browser_caching_htaccess']) ) {
                $this->main_instance->set_single_config("cf_browser_caching_htaccess", intval($_POST['swcfpc_cf_browser_caching_htaccess']));
            }

            // Comments
            if( isset($_POST['swcfpc_cf_auto_purge_on_comments']) ) {
                $this->main_instance->set_single_config("cf_auto_purge_on_comments", intval($_POST['swcfpc_cf_auto_purge_on_comments']));
            }

            // URLs to exclude from cache
            if( isset($_POST['swcfpc_cf_excluded_urls']) ) {

                $excluded_urls = array();

                //$excluded_urls = str_replace( array("http:", "https:", "ftp:"), "", $_POST['swcfpc_cf_excluded_urls']);
                $parsed_excluded_urls = explode("\n", $_POST['swcfpc_cf_excluded_urls']);

                foreach($parsed_excluded_urls as $single_url) {

                    $parsed_url = parse_url( str_replace(array("\r", "\n"), '', $single_url) );

                    if( $parsed_url && isset($parsed_url["path"]) ) {

                        $uri = $parsed_url["path"];

                        // Force trailing slash
                        if( strlen($uri) > 1 && $uri[ strlen($uri)-1 ] != "/" && $uri[ strlen($uri)-1 ] != "*" )
                            $uri .= "/";

                        if( isset($parsed_url["query"]) ) {
                            $uri .= "?".$parsed_url["query"];
                        }

                        $excluded_urls[] = $uri;

                    }

                }

                if( count($excluded_urls) > 0 )
                    $this->main_instance->set_single_config("cf_excluded_urls", $excluded_urls);
                else
                    $this->main_instance->set_single_config("cf_excluded_urls", "");

            }

            // Purge cache URL secret key
            if( isset($_POST['swcfpc_cf_purge_url_secret_key']) ) {
                $this->main_instance->set_single_config("cf_purge_url_secret_key", trim($_POST['swcfpc_cf_purge_url_secret_key']));
            }

            // Remove purge option from toolbar
            if( isset($_POST['swcfpc_cf_remove_purge_option_toolbar']) ) {
                $this->main_instance->set_single_config("cf_remove_purge_option_toolbar", intval($_POST['swcfpc_cf_remove_purge_option_toolbar']));
            }

            // Disable metabox from single post/page
            if( isset($_POST['swcfpc_cf_disable_single_metabox']) ) {
                $this->main_instance->set_single_config("cf_disable_single_metabox", intval($_POST['swcfpc_cf_disable_single_metabox']));
            }

            if( ($zone_id_list = $this->objects["cloudflare"]->get_zone_id_list( $error_msg )) ) {

                $this->main_instance->set_single_config("cf_zoneid_list", $zone_id_list);

                if( $this->main_instance->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) == SWCFPC_AUTH_MODE_API_TOKEN && isset($_POST['swcfpc_cf_apitoken_domain']) && strlen(trim($_POST['swcfpc_cf_apitoken_domain'])) > 0 ) {
                    $this->main_instance->set_single_config("cf_zoneid", $zone_id_list[$this->main_instance->get_single_config("cf_apitoken_domain", "")]);
                }

            }

            // Aggiornamento htaccess
            $this->objects["cache_controller"]->write_htaccess( $error_msg );

            // Salvataggio configurazioni
            $this->main_instance->update_config();
            $success_msg = __("Settings updated successfully", 'wp-cloudflare-page-cache');

        }


        $zone_id_list = $this->main_instance->get_single_config("cf_zoneid_list", "");

        if( is_array( $zone_id_list ) ) {

            // If the domain name is found in the zone list, I will show it only instead of full domains list
            $current_domain = str_replace( array("/", "http:", "https:", "www."), "", site_url() );

            foreach($zone_id_list as $zone_id_name => $zone_id) {

                if( $zone_id_name == $current_domain ) {
                    $domain_found = true;
                    $domain_zone_id = $zone_id;
                    break;
                }

            }


        }
        else {
            $zone_id_list = array();
        }


        if( $this->debug_enabled ) {

            $debug  = "";

            if( $this->main_instance->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) == SWCFPC_AUTH_MODE_API_TOKEN )
                $debug .= "<p><b>Auth mode:</b> API Token</p>";
            else
                $debug .= "<p><b>Auth mode:</b> API Key</p>";

            $debug .= "<p><b>Email:</b> ".$this->main_instance->get_cloudflare_api_email()."</p>";
            $debug .= "<p><b>API Key:</b> ".$this->main_instance->get_cloudflare_api_key()."</p>";
            $debug .= "<p><b>API Token:</b> ".$this->main_instance->get_cloudflare_api_token()."</p>";
            $debug .= "<p><b>Zone ID:</b> ".$this->main_instance->get_cloudflare_api_zone_id()."</p>";
            $debug .= "<p><b>Subdomain:</b> ".$this->main_instance->get_cloudflare_api_subdomain()."</p>";
            $debug .= "<p><b>Page rule ID:</b> ".$this->main_instance->get_single_config("cf_page_rule_id", "")."</p>";
            $debug .= "<p><b>Old TTL:</b> ".$this->main_instance->get_single_config("cf_old_bc_ttl", "")."</p>";

            $this->add_debug_string( __("General Settings", "wp-cloudflare-page-cache"), $debug );

            $debug = "<p><b>Config:</b> <pre>".print_r($this->main_instance->get_config(), true)."</pre></p>";
            $this->add_debug_string( __("Config", "wp-cloudflare-page-cache"), $debug );

        }

        $cronjob_url = add_query_arg( array(
            $this->objects["cache_controller"]->get_cache_buster() => '1',
            'swcfpc-purge-all' => '1',
            'swcfpc-sec-key' => $this->main_instance->get_single_config("cf_purge_url_secret_key", wp_generate_password(20, false, false)),
        ), site_url() );


        require_once SWCFPC_PLUGIN_PATH . 'libs/views/settings.php';

    }


    function admin_menu_page_nginx_settings() {

        if( !current_user_can("manage_options") ) {
            die( __("Permission denied", 'wp-cloudflare-page-cache') );
        }

        $this->objects = $this->main_instance->get_objects();
        $nginx_lines = $this->objects["cache_controller"]->get_nginx_rules();

        require_once SWCFPC_PLUGIN_PATH . 'libs/views/nginx.php';

    }


}