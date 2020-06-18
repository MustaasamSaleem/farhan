<?php

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

class SWCFPC_Preloader
{

    private $main_instance = null;

    private $objects;

    function __construct( $main_instance )
    {

        $this->main_instance = $main_instance;

        $this->actions();

    }

    function actions() {

        // Ajax preloader start
        add_action( 'wp_ajax_swcfpc_preloader_start', array($this, 'ajax_preloader_start') );

    }

    function ajax_preloader_start() {

        check_ajax_referer( 'ajax-nonce-string', 'security' );

        $this->objects = $this->main_instance->get_objects();

        $return_array = array("status" => "ok");
        $urls = array();

        if( !current_user_can('manage_options') ) {
            $return_array["status"] = "error";
            $return_array["error"] = __("Permission denied", "wp-cloudflare-page-cache");
            die(json_encode($return_array));
        }

        if( !class_exists('WP_Background_Process') ) {
            $return_array["status"] = "error";
            $return_array["error"] = __("Unable to start background processes: WP_Background_Process does not exists.", "wp-cloudflare-page-cache");
            die(json_encode($return_array));
        }

        if( !class_exists('SWCFPC_Preloader_Process') ) {
            $return_array["status"] = "error";
            $return_array["error"] = __("Unable to start background processes: SWCFPC_Preloader_Process does not exists.", "wp-cloudflare-page-cache");
            die(json_encode($return_array));
        }

        if( ! $this->objects["cache_controller"]->is_cache_enabled() ) {
            $return_array["status"] = "error";
            $return_array["error"] = __("You cannot start the preloader while the page cache is disabled.", "wp-cloudflare-page-cache");
            die(json_encode($return_array));
        }

        // Get public post types.
        $post_types = get_post_types( array( 'public' => true ) );

        $args = array(
            'fields'         => 'ids',
            'numberposts'    => SWCFPC_PRELOADER_MAX_POST_NUMBER,
            'posts_per_page' => -1,
            'post_type'      => $post_types,
        );

        $all_posts = get_posts( $args );

        foreach ( $all_posts as $post ) {

            $permalink = get_permalink( $post );

            if ( $permalink !== false ) {
                $urls[] = $permalink;
            }

        }

        if( count($urls) <= 0 ) {
            $return_array["status"] = "error";
            $return_array["error"] = __( 'No URLs available. Nothing to preload.', 'wp-cloudflare-page-cache');
            die(json_encode($return_array));
        }

        $num_url = count($urls);
        $preloader = new SWCFPC_Preloader_Process();

        // Add URLs to preloader
        for($i=0; $i<$num_url; $i++) {
            $preloader->push_to_queue( $num_url[$i] );
        }

        // Start background preloader
        $preloader->save()->dispatch();

        $return_array["success_msg"] = __("Preloader started successfully", "wp-cloudflare-page-cache");

        die(json_encode($return_array));

    }


}

if( class_exists('WP_Background_Process') ) {

    class SWCFPC_Preloader_Process extends WP_Background_Process
    {

        protected $action = 'swcfpc_cache_preloader_background_process';

        protected function task($item)
        {

            $objects = $this->main_instance->get_objects();
            $objects["logs"]->add_log("preloader::task", "Preloading ".esc_url_raw( $item ) );

            $args = array(
                'timeout'    => 0.01,
                'blocking'   => false,
                'user-agent' => 'WP Cloudflare Super Page Cache Preloader',
                'sslverify'  => false,
            );

            wp_remote_get( esc_url_raw( $item ), $args );

            usleep( absint( 500000 ) );

            return false;

        }

        protected function complete()
        {
            parent::complete();
        }

        public function is_process_running()
        {
            return parent::is_process_running();
        }

    }

}