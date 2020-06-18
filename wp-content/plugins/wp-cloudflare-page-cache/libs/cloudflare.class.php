<?php

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

class SWCFPC_Cloudflare
{

    private $main_instance = null;
    
    private $objects   = false;
    private $api_key   = "";
    private $email     = "";
    private $api_token = "";
    private $auth_mode = 0;
    private $zone_id   = "";
    private $subdomain = "";
    private $api_token_domain = "";

    function __construct( $auth_mode, $api_key, $email, $api_token, $zone_id, $subdomain, $main_instance ) {

        $this->auth_mode     = $auth_mode;
        $this->api_key       = $api_key;
        $this->email         = $email;
        $this->api_token     = $api_token;
        $this->zone_id       = $zone_id;
        $this->subdomain     = $subdomain;
        $this->main_instance = $main_instance;

        $this->actions();

    }

    function actions() {

        // Ajax clear whole cache
        add_action( 'wp_ajax_swcfpc_test_page_cache', array($this, 'ajax_test_page_cache') );

        // Ajax enable page cache
        add_action( 'wp_ajax_swcfpc_enable_page_cache', array($this, 'ajax_enable_page_cache') );

        // Ajax disable page cache
        add_action( 'wp_ajax_swcfpc_disable_page_cache', array($this, 'ajax_disable_page_cache') );

    }

    function set_auth_mode( $auth_mode ) {
        $this->auth_mode = $auth_mode;
    }

    function set_api_key( $api_key ) {
        $this->api_key = $api_key;
    }

    function set_api_email( $email ) {
        $this->email = $email;
    }

    function set_api_token( $api_token ) {
        $this->api_token = $api_token;
    }

    function set_api_token_domain( $api_token_domain ) {
        $this->api_token_domain = $api_token_domain;
    }


    function get_api_headers() {

        $cf_headers = array();

        if( $this->auth_mode == SWCFPC_AUTH_MODE_API_TOKEN ) {

            $cf_headers = array(
                "headers" => array(
                    "Authorization" => "Bearer ".$this->api_token,
                    "Content-Type" => "application/json"
                )
            );

        }
        else {

            $cf_headers = array(
                "headers" => array(
                    "X-Auth-Email" => $this->email,
                    "X-Auth-Key"   => $this->api_key,
                    "Content-Type" => "application/json"
                )
            );

        }

        return $cf_headers;

    }


    function get_zone_id_list(&$error) {

        $this->objects = $this->main_instance->get_objects();

        $zone_id_list = array();
        $per_page     = 50;
        $current_page = 1;
        $pagination   = false;
        $cf_headers   = $this->get_api_headers();

        do {

            if( $this->auth_mode == SWCFPC_AUTH_MODE_API_TOKEN && $this->api_token_domain != "" ) {

                if( is_object($this->objects["logs"]) ) {
                    $this->objects["logs"]->add_log("cloudflare::cloudflare_get_zone_ids", "Request for page $current_page - URL: ".esc_url_raw( "https://api.cloudflare.com/client/v4/zones?name=".$this->api_token_domain ) );
                }

                $response = wp_remote_get(
                    esc_url_raw( "https://api.cloudflare.com/client/v4/zones?name=".$this->api_token_domain ),
                    $cf_headers
                );

            }
            else {

                if (is_object($this->objects["logs"])) {
                    $this->objects["logs"]->add_log("cloudflare::cloudflare_get_zone_ids", "Request for page $current_page - URL: " . esc_url_raw("https://api.cloudflare.com/client/v4/zones?page=$current_page&per_page=$per_page"));
                }

                $response = wp_remote_get(
                    esc_url_raw("https://api.cloudflare.com/client/v4/zones?page=$current_page&per_page=$per_page"),
                    $cf_headers
                );

            }

            if ( is_wp_error( $response ) ) {
                $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
                return false;
            }

            $response_body = wp_remote_retrieve_body($response);

            if( is_object($this->objects["logs"]) ) {
                $this->objects["logs"]->add_log("cloudflare::cloudflare_get_zone_ids", "Response for page $current_page: ".$response_body );
            }

            $json = json_decode( $response_body, true);

            if( $json["success"] == false ) {

                $error = array();

                foreach($json["errors"] as $single_error) {
                    $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
                }

                $error = implode(" - ", $error);

                return false;

            }

            if( isset($json["result_info"]) && is_array($json["result_info"]) ) {

                if( isset($json["result_info"]["total_pages"]) && intval($json["result_info"]["total_pages"]) > $current_page ) {
                    $pagination = true;
                    $current_page++;
                }
                else {
                    $pagination = false;
                }

            }
            else {

                if( $pagination )
                    $pagination = false;

            }

            if( isset($json["result"]) && is_array($json["result"]) ) {

                foreach( $json["result"] as $domain_data ) {

                    if( !isset($domain_data["name"]) || !isset($domain_data["id"]) ) {
                        $error = __("Unable to retrive zone id due to invalid response data", 'wp-cloudflare-page-cache');
                        return false;
                    }

                    $zone_id_list[$domain_data["name"]] = $domain_data["id"];

                }

            }


        } while( $pagination );


        if( !count($zone_id_list) ) {
            $error = __("Unable to find domains configured on Cloudflare", 'wp-cloudflare-page-cache');
            return false;
        }

        return $zone_id_list;

    }


    function get_current_browser_cache_ttl(&$error) {

        $this->objects = $this->main_instance->get_objects();
        $cf_headers = $this->get_api_headers();

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::cloudflare_get_browser_cache_ttl", "Request ".esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$this->zone_id/settings/browser_cache_ttl" ) );
        }

        $response = wp_remote_get(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$this->zone_id/settings/browser_cache_ttl" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::cloudflare_get_browser_cache_ttl", "Response ".$response_body );
        }

        $json = json_decode( $response_body, true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        if( isset($json["result"]) && is_array($json["result"]) && isset($json["result"]["value"]) ) {
            return $json["result"]["value"];
        }

        $error = __("Unable to find Browser Cache TTL settings ", 'wp-cloudflare-page-cache');
        return false;

    }


    function change_browser_cache_ttl($ttl, &$error) {

        $this->objects = $this->main_instance->get_objects();

        $cf_headers           = $this->get_api_headers();
        $cf_headers["method"] = "PATCH";
        $cf_headers["body"]   = json_encode( array("value" => $ttl) );

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::cloudflare_set_browser_cache_ttl", "Request URL: ".esc_url_raw("https://api.cloudflare.com/client/v4/zones/$this->zone_id/settings/browser_cache_ttl") );
            $this->objects["logs"]->add_log("cloudflare::cloudflare_set_browser_cache_ttl", "Request body: " . json_encode(array("value" => $ttl)) );
        }

        $response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$this->zone_id/settings/browser_cache_ttl" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::cloudflare_set_browser_cache_ttl", "Response: ".$response_body);
        }

        $json = json_decode( $response_body, true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        return true;

    }


    function delete_page_rule($page_rule_id, &$error) {

        $this->objects = $this->main_instance->get_objects();

        $cf_headers = $this->get_api_headers();
        $cf_headers["method"] = "DELETE";

        if( $page_rule_id == "" ) {
            $error = __("There is not page rule to delete", 'wp-cloudflare-page-cache');
            return false;
        }

        if( $this->zone_id == "" ) {
            $error = __("There is not zone id to use", 'wp-cloudflare-page-cache');
            return false;
        }

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::cloudflare_delete_page_rule", "Request: ".esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$this->zone_id/pagerules/$page_rule_id" ) );
        }

        $response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$this->zone_id/pagerules/$page_rule_id" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::cloudflare_delete_page_rule", "Response: ".wp_remote_retrieve_body($response));
        }

        $json = json_decode( $response_body, true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        return true;

    }


    function add_cache_everything_page_rule(&$error) {

        $this->objects = $this->main_instance->get_objects();

        $cf_headers = $this->get_api_headers();

        if( $this->subdomain != "" && preg_match( "/([a-zA-Z0-9\-]+)\.([a-zA-Z0-9\-]+)\.([a-zA-Z0-9])+/", $this->subdomain ) ) {
            $url = "$this->subdomain/*";
        }
        else {
            $url = site_url("/*");
        }

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::add_cache_everything_page_rule", "Request URL: ".esc_url_raw("https://api.cloudflare.com/client/v4/zones/$this->zone_id/pagerules") );
            $this->objects["logs"]->add_log("cloudflare::add_cache_everything_page_rule", "Request Body: ".json_encode(array("targets" => array(array("target" => "url", "constraint" => array("operator" => "matches", "value" => $url))), "actions" => array(array("id" => "cache_level", "value" => "cache_everything")), "priority" => 1, "status" => "active")) );
        }

        $cf_headers["method"] = "POST";
        $cf_headers["body"] = json_encode( array("targets" => array(array("target" => "url", "constraint" => array("operator" => "matches", "value" => $url))), "actions" => array(array("id" => "cache_level", "value" => "cache_everything")), "priority" => 1, "status" => "active") );

        $response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$this->zone_id/pagerules" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::add_cache_everything_page_rule", "Response: ".$response_body );
        }

        $json = json_decode( $response_body, true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        if( isset($json["result"]) && is_array($json["result"]) && isset($json["result"]["id"]) ) {
            return $json["result"]["id"];
        }

        return false;

    }


    function purge_cache(&$error) {

        $this->objects = $this->main_instance->get_objects();

        do_action("swcfpc_cf_purge_whole_cache_before");

        $cf_headers           = $this->get_api_headers();
        $cf_headers["method"] = "POST";
        $cf_headers["body"]   = json_encode( array( "purge_everything" => true ) );

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::purge_cache", "Request URL: ". esc_url_raw("https://api.cloudflare.com/client/v4/zones/$this->zone_id/purge_cache") );
            $this->objects["logs"]->add_log("cloudflare::purge_cache", "Request Body: ". json_encode(array("purge_everything" => true)) );
        }

        $response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$this->zone_id/purge_cache" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::purge_cache", "Response: ".$response_body);
        }

        $json = json_decode( $response_body, true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        do_action("swcfpc_cf_purge_whole_cache_after");

        return true;

    }


    function purge_cache_urls($urls, &$error) {

        $this->objects = $this->main_instance->get_objects();

        do_action("swcfpc_cf_purge_cache_by_urls_before");

        $cf_headers           = $this->get_api_headers();
        $cf_headers["method"] = "POST";
        $cf_headers["body"]   = json_encode( array( "files" => $urls ) );

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::purge_cache_urls", "Request URL: ".esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$this->zone_id/purge_cache" ) );
            $this->objects["logs"]->add_log("cloudflare::purge_cache_urls", "Request Body: ".json_encode( array( "files" => $urls ) ) );
        }

        $response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$this->zone_id/purge_cache" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::purge_cache_urls", "Response: ".$response_body );
        }

        $json = json_decode( $response_body, true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        do_action("swcfpc_cf_purge_cache_by_urls_after");

        return true;

    }


    function page_cache_test($url, &$error) {

        $this->objects = $this->main_instance->get_objects();

        // First test
        $response = wp_remote_get( esc_url_raw( $url ) );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        $headers = wp_remote_retrieve_headers( $response );

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::page_cache_test", "1 Request URL: $url" );
            $this->objects["logs"]->add_log("cloudflare::page_cache_test", "1 Response Headers: ".var_export($headers, true) );
        }

        // Second test
        if( isset($headers['Set-Cookie']) ) {

            $response = wp_remote_get( esc_url_raw( $url ), array("headers" => array( "Cookie" => $headers['Set-Cookie']) ) );

            if ( is_wp_error( $response ) ) {
                $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
                return false;
            }

            $headers = wp_remote_retrieve_headers( $response );

        }
        else {

            $response = wp_remote_get( esc_url_raw( $url ) );

            if ( is_wp_error( $response ) ) {
                $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
                return false;
            }

            $headers = wp_remote_retrieve_headers( $response );

        }

        if( is_object($this->objects["logs"]) ) {
            $this->objects["logs"]->add_log("cloudflare::page_cache_test", "2 Request URL: $url" );
            $this->objects["logs"]->add_log("cloudflare::page_cache_test", "2 Response Headers: ".var_export($headers, true) );
        }

        if( !isset($headers["CF-Cache-Status"]) ) {
            $error = __('The cache doesn\'t seem to work. If you have recently enabled the cache or it is your first test, wait about 30 seconds and try again because the changes take a few seconds for Cloudflare to propagate them on the web. If the error persists, request support for a detailed check.', 'wp-cloudflare-page-cache');
            return false;
        }

        if( strcasecmp($headers["CF-Cache-Status"], "HIT") != 0 ) {

            if( strcasecmp($headers["CF-Cache-Status"], "MISS") == 0 || strcasecmp($headers["CF-Cache-Status"], "EXPIRED") == 0 ) {
                $error = sprintf( __('Cache status: %s - Please try to test again', 'wp-cloudflare-page-cache'), $headers["CF-Cache-Status"]);
                return false;
            }

            if( strcasecmp($headers["CF-Cache-Status"], "DYNAMIC") == 0 ) {
                $error = sprintf( __('Cache status: %s - Something wrong in your Cloudflare setup. Please check that the page rule was created correctly and that Cloudflare is respecting the original HTTP response headers.', 'wp-cloudflare-page-cache'), $headers["CF-Cache-Status"]);
                return false;
            }

            $error = sprintf( __('Cache status: %s - Try again', 'wp-cloudflare-page-cache'), $headers["CF-Cache-Status"]);
            return false;
        }

        return $headers;

    }


    function ajax_test_page_cache() {

        check_ajax_referer( 'ajax-nonce-string', 'security' );

        $return_array = array("status" => "ok");
        $error = "";

        $url_static_resource = SWCFPC_PLUGIN_URL.'/assets/testcache.html';
        $headers_static_resource = $this->page_cache_test( $url_static_resource, $error );

        if( ! $headers_static_resource ) {
            $return_array["status"] = "error";
            $return_array["resource_type"] = "static";
            $return_array["resource_url"] = $url_static_resource;
            $return_array["error"] = $error;
            die(json_encode($return_array));
        }

        /*
        $url_dynamic_resource = site_url();
        $headers_dynamic_resource = $this->page_cache_test( $url_dynamic_resource, $error );

        if( ! $headers_dynamic_resource ) {
            $return_array["status"] = "error";
            $return_array["resource_type"] = "dynamic";
            $return_array["resource_url"] = $url_dynamic_resource;
            $return_array["error"] = $error;
            die(json_encode($return_array));
        }
        */

        $return_array["success_msg"] = __("Page caching is working properly", 'wp-cloudflare-page-cache');

        die(json_encode($return_array));

    }


    function ajax_enable_page_cache() {

        check_ajax_referer( 'ajax-nonce-string', 'security' );

        $return_array = array("status" => "ok");
        $error = "";

        $this->objects = $this->main_instance->get_objects();

        if( !current_user_can('manage_options') ) {
            $return_array["status"] = "error";
            $return_array["error"] = __("Permission denied", "wp-cloudflare-page-cache");
            die(json_encode($return_array));
        }

        $current_cf_browser_ttl = $this->get_current_browser_cache_ttl( $error );

        if( $current_cf_browser_ttl !== false ) {
            $this->main_instance->set_single_config("cf_old_bc_ttl", $current_cf_browser_ttl);
        }

        // Step 1 - set browser cache ttl to zero (Respect Existing Headers)
        if( !$this->change_browser_cache_ttl(0, $error) ) {

            $this->main_instance->set_single_config("cf_cache_enabled", 0);

            $return_array["status"] = "error";
            $return_array["error"] = $error;
            die(json_encode($return_array));

        }

        // Step 2 - delete old page rule, if exist
        if( $this->main_instance->get_single_config("cf_page_rule_id", "") != "" && $this->delete_page_rule( $this->main_instance->get_single_config("cf_page_rule_id", ""), $error_msg ) ) {
            $this->main_instance->set_single_config("cf_page_rule_id", "");
        }

        // Step 3 - create new page rule
        $cache_everything_page_rule_id = $this->add_cache_everything_page_rule($error);

        if( $cache_everything_page_rule_id == false ) {

            $this->main_instance->set_single_config("cf_cache_enabled", 0);

            $return_array["status"] = "error";
            $return_array["error"] = $error;
            die(json_encode($return_array));

        }

        $this->main_instance->set_single_config("cf_page_rule_id", $cache_everything_page_rule_id);
        $this->main_instance->update_config();

        // Step 4 - purge cache
        $this->purge_cache($error);

        $this->main_instance->set_single_config("cf_cache_enabled", 1);
        $this->main_instance->update_config();

        $this->objects["cache_controller"]->write_htaccess( $error );

        $return_array["success_msg"] = __("Page cache enabled successfully", 'wp-cloudflare-page-cache');

        die(json_encode($return_array));

    }


    function ajax_disable_page_cache() {

        check_ajax_referer( 'ajax-nonce-string', 'security' );

        $return_array = array("status" => "ok");
        $error = "";

        if( !current_user_can('manage_options') ) {
            $return_array["status"] = "error";
            $return_array["error"] = __("Permission denied", "wp-cloudflare-page-cache");
            die(json_encode($return_array));
        }

        $this->objects = $this->main_instance->get_objects();

        // Reset old browser cache TTL
        $this->change_browser_cache_ttl( $this->main_instance->get_single_config("cf_old_bc_ttl", 0), $error );

        // Delete the page rule
        if( ! $this->delete_page_rule( $this->main_instance->get_single_config("cf_page_rule_id", ""), $error ) ) {
            $return_array["status"] = "error";
            $return_array["error"] = $error;
            die(json_encode($return_array));
        }

        // Purge cache
        $this->purge_cache($error);

        // Reset htaccess
        $this->objects["cache_controller"]->reset_htaccess();

        $this->main_instance->set_single_config("cf_cache_enabled", 0);
        $this->main_instance->update_config();

        $return_array["success_msg"] = __("Page cache disabled successfully", 'wp-cloudflare-page-cache');

        die(json_encode($return_array));

    }

}