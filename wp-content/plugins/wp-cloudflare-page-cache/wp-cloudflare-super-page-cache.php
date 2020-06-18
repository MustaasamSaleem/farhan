<?php
/**
 * Plugin Name:  WP Cloudflare Super Page Cache
 * Plugin URI:   https://www.speedywordpress.it/
 * Description:  Speed up your website by enabling page caching on Cloudflare on free plans.
 * Version:      4.1.4
 * Author:       Salvatore Fresta
 * Author URI:   https://www.salvatorefresta.net/
 * License:      GPLv2 or later
 * Text Domain: wp-cloudflare-page-cache
*/

if( !class_exists('SW_CLOUDFLARE_PAGECACHE') ) {

    define('SWCFPC_PLUGIN_PATH', plugin_dir_path(__FILE__));
    define('SWCFPC_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('SWCFPC_AUTH_MODE_API_KEY',   0);
    define('SWCFPC_AUTH_MODE_API_TOKEN', 1);

    if( !defined('SWCFPC_PRELOADER_MAX_POST_NUMBER') )
        define('SWCFPC_PRELOADER_MAX_POST_NUMBER', 1000);

    if( !defined('SWCFPC_CACHE_BUSTER') )
        define('SWCFPC_CACHE_BUSTER', 'swcfpc');

    class SW_CLOUDFLARE_PAGECACHE {

        private $config   = false;
        private $objects  = array();
        private $version  = '4.1.4';

        function __construct() {

            //add_action( 'plugins_loaded', array($this, 'update_plugin') );
            //register_activation_hook( __FILE__, array($this, 'update_plugin') );
            register_deactivation_hook( __FILE__, array($this, 'deactivate_plugin') );

            if( ! $this->init_config() ) {
                $this->config = $this->get_default_config();
                $this->update_config();
            }

            if( !file_exists( $this->get_upload_directory_path() ) )
                $this->create_upload_directory();

            $this->update_plugin();
            $this->include_libs();
            $this->actions();

        }


        function load_textdomain() {

            load_plugin_textdomain( 'wp-cloudflare-page-cache', false, basename( dirname( __FILE__ ) ) . '/languages/' );

        }


        function include_libs()
        {

            $this->objects = array();

            include_once(ABSPATH . 'wp-includes/pluggable.php');

            // Composer autoload.
            if ( file_exists( SWCFPC_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
                require SWCFPC_PLUGIN_PATH . 'vendor/autoload.php';
            }

            require_once SWCFPC_PLUGIN_PATH . 'libs/preloader.class.php';
            require_once SWCFPC_PLUGIN_PATH . 'libs/cloudflare.class.php';
            require_once SWCFPC_PLUGIN_PATH . 'libs/logs.class.php';
            require_once SWCFPC_PLUGIN_PATH . 'libs/cache_controller.class.php';
            require_once SWCFPC_PLUGIN_PATH . 'libs/backend.class.php';

            $log_file_path = $this->get_upload_directory_path()."logs.log";
            $log_file_url = $this->get_upload_directory_url()."logs.log";

            if( $this->get_single_config("log_enabled", 0) > 0 )
                $this->objects["logs"] = new SWCFPC_Logs( $log_file_path, $log_file_url, $this->get_single_config("log_expiration", 7), true, $this );
            else
                $this->objects["logs"] = new SWCFPC_Logs( $log_file_path, $log_file_url, $this->get_single_config("log_expiration", 7), false, $this );

            $this->objects["cloudflare"] = new SWCFPC_Cloudflare(
                $this->get_single_config("cf_auth_mode"),
                $this->get_cloudflare_api_key(),
                $this->get_cloudflare_api_email(),
                $this->get_cloudflare_api_token(),
                $this->get_cloudflare_api_zone_id(),
                $this->get_cloudflare_api_subdomain(),
                $this
            );

            $this->objects["cache_controller"] = new SWCFPC_Cache_Controller( SWCFPC_CACHE_BUSTER, $this );
            $this->objects["backend"] = new SWCFPC_Backend( $this );
            $this->objects["preloader"] = new SWCFPC_Preloader( $this );

        }


        function actions() {

            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links') );

            // Multilanguage
            add_action( 'plugins_loaded', array($this, 'load_textdomain') );

        }


        function get_default_config() {

            $config = array();

            // Cloudflare config
            $config["cf_zoneid"]                      = "";
            $config["cf_zoneid_list"]                 = array();
            $config["cf_email"]                       = "";
            $config["cf_apitoken"]                    = "";
            $config["cf_apikey"]                      = "";
            $config["cf_token"]                       = "";
            $config["cf_old_bc_ttl"]                  = "";
            $config["cf_page_rule_id"]                = "";
            $config["cf_subdomain"]                   = "";
            $config["cf_auto_purge"]                  = 1;
            $config["cf_auto_purge_all"]              = 0;
            $config["cf_auto_purge_on_comments"]      = 0;
            $config["cf_cache_enabled"]               = 0;
            $config["cf_maxage"]                      = 604800; // 1 week
            $config["cf_browser_maxage"]              = 60; // 1 minute
            $config["cf_post_per_page"]               = get_option( 'posts_per_page', 0);
            $config["cf_purge_url_secret_key"]        = $this->generate_password(20, false, false);
            $config["cf_strip_cookies"]               = 0;

            // Pages
            $config["cf_excluded_urls"]                 = array();
            $config["cf_bypass_front_page"]             = 0;
            $config["cf_bypass_pages"]                  = 0;
            $config["cf_bypass_home"]                   = 0;
            $config["cf_bypass_archives"]               = 0;
            $config["cf_bypass_tags"]                   = 0;
            $config["cf_bypass_category"]               = 0;
            $config["cf_bypass_author_pages"]           = 0;
            $config["cf_bypass_single_post"]            = 0;
            $config["cf_bypass_feeds"]                  = 1;
            $config["cf_bypass_search_pages"]           = 1;
            $config["cf_bypass_404"]                    = 1;
            $config["cf_bypass_logged_in"]              = 1;
            $config["cf_bypass_amp"]                    = 1;
            $config["cf_bypass_file_robots"]            = 1;
            $config["cf_bypass_sitemap"]                = 1;
            $config["cf_bypass_ajax"]                   = 1;
            $config["cf_cache_control_htaccess"]        = 1;
            $config["cf_browser_caching_htaccess"]      = 0;
            $config["cf_auth_mode"]                     = SWCFPC_AUTH_MODE_API_KEY;
            $config["cf_bypass_post"]                   = 0;
            $config["cf_bypass_query_var"]              = 0;

            // WooCommerce
            $config["cf_bypass_woo_shop_page"]          = 0;
            $config["cf_bypass_woo_pages"]              = 0;
            $config["cf_bypass_woo_product_tax_page"]   = 0;
            $config["cf_bypass_woo_product_tag_page"]   = 0;
            $config["cf_bypass_woo_product_cat_page"]   = 0;
            $config["cf_bypass_woo_product_page"]       = 0;
            $config["cf_bypass_woo_cart_page"]          = 1;
            $config["cf_bypass_woo_checkout_page"]      = 1;
            $config["cf_bypass_woo_checkout_pay_page"]  = 1;

            // W3TC
            $config["cf_w3tc_purge_on_flush_minfy"]         = 0;
            $config["cf_w3tc_purge_on_flush_posts"]         = 0;
            $config["cf_w3tc_purge_on_flush_objectcache"]   = 0;
            $config["cf_w3tc_purge_on_flush_fragmentcache"] = 0;
            $config["cf_w3tc_purge_on_flush_dbcache"]       = 0;
            $config["cf_w3tc_purge_on_flush_all"]           = 0;

            // WP Rocket
            $config["cf_wp_rocket_purge_on_post_flush"]     = 0;
            $config["cf_wp_rocket_purge_on_domain_flush"]   = 0;

            // WP Super Cache
            $config["cf_wp_super_cache_on_cache_flush"] = 0;

            // Litespeed Cache
            $config["cf_litespeed_purge_on_cache_flush"] = 0;

            // WP Fastest Cache
            $config["cf_wp_fastest_cache_purge_on_cache_flush"] = 0;

            // Hummingbird
            $config["cf_hummingbird_purge_on_cache_flush"] = 0;

            // Other
            $config["log_enabled"] = 0;
            $config["log_expiration"] = 7;
            $config["cf_remove_purge_option_toolbar"] = 0;
            $config["cf_disable_single_metabox"] = 0;

            return $config;

        }


        function get_single_config($name, $default=false) {

            if( !is_array($this->config) || !isset($this->config[$name]) )
                return $default;

            if( is_array($this->config[$name]))
                return $this->config[$name];

            return trim($this->config[$name]);

        }


        function set_single_config($name, $value) {

            if( !is_array($this->config) )
                $this->config = array();

            if( is_array($value) )
                $this->config[trim($name)] = $value;
            else
                $this->config[trim($name)] = trim($value);

        }


        function update_config() {

            update_option( 'swcfpc_config', serialize( $this->config ) );

        }


        function init_config() {

            $this->config = get_option( 'swcfpc_config', false );

            if( !$this->config )
                return false;

            $this->config = unserialize( $this->config );

            return true;

        }


        function set_config( $config ) {
            $this->config = $config;
        }


        function get_config() {
            return $this->config;
        }


        function update_plugin() {

            $current_version = get_option( 'swcfpc_version', false );

            if( $current_version === false || version_compare( $current_version, $this->version, "!=") ) {

                require_once SWCFPC_PLUGIN_PATH . 'libs/installer.class.php';

                if( $current_version === false ) {
                    $installer = new SWCFPC_Installer();
                    $installer->start();
                }

                if( version_compare( $current_version, "2.0", "<") ) {

                    $config = $this->get_default_config();

                    // Cloudflare config
                    $config["cf_zoneid"]         = get_option("swcfpc_cf_zoneid",        "");
                    $config["cf_zoneid_list"]    = get_option("swcfpc_cf_zoneid_list",   "");
                    $config["cf_email"]          = get_option("swcfpc_cf_email",         "");
                    $config["cf_apikey"]         = get_option("swcfpc_cf_apikey",        "");
                    $config["cf_old_bc_ttl"]     = get_option("swcfpc_cf_old_bc_ttl",    "");
                    $config["cf_page_rule_id"]   = get_option("swcfpc_cf_page_rule_id",  "");
                    $config["cf_subdomain"]      = get_option("swcfpc_cf_subdomain",     "");
                    $config["cf_auto_purge"]     = get_option("swcfpc_cf_auto_purge",     1);
                    $config["cf_cache_enabled"]  = get_option("swcfpc_cf_cache_enabled",  0);
                    $config["cf_maxage"]         = get_option("swcfpc_maxage", 604800); // 1 week
                    $config["cf_browser_maxage"]    = 60; // 1 minute

                    // Pages
                    $config["cf_excluded_urls"]       = get_option("swcfpc_cf_excluded_urls", 0);
                    $config["cf_bypass_front_page"]   = get_option("swcfpc_cf_bypass_front_page", 0);
                    $config["cf_bypass_pages"]        = get_option("swcfpc_cf_bypass_pages", 0);
                    $config["cf_bypass_home"]         = get_option("swcfpc_cf_bypass_home", 0);
                    $config["cf_bypass_archives"]     = get_option("swcfpc_cf_bypass_archives", 0);
                    $config["cf_bypass_tags"]         = get_option("swcfpc_cf_bypass_tags", 0);
                    $config["cf_bypass_category"]     = get_option("swcfpc_cf_bypass_category", 0);
                    $config["cf_bypass_author_pages"] = get_option("swcfpc_cf_bypass_author_pages", 0);
                    $config["cf_bypass_single_post"]  = get_option("swcfpc_cf_bypass_single_post", 0);
                    $config["cf_bypass_feeds"]        = get_option("swcfpc_cf_bypass_feeds", 1);
                    $config["cf_bypass_search_pages"] = get_option("swcfpc_cf_bypass_search_pages", 1);
                    $config["cf_bypass_404"]          = get_option("swcfpc_cf_bypass_404", 1);
                    $config["cf_bypass_logged_in"]    = get_option("swcfpc_cf_bypass_logged_in", 1);
                    $config["cf_bypass_amp"]          = get_option("swcfpc_cf_bypass_amp", 0);
                    $config["cf_bypass_file_robots"]  = get_option("swcfpc_cf_bypass_file_robots", 0);
                    $config["cf_bypass_sitemap"]      = get_option("swcfpc_cf_bypass_sitemap", 0);
                    $config["cf_bypass_ajax"]         = get_option("swcfpc_cf_bypass_ajax", 1);

                    // Other
                    $config["debug"] = get_option("swcfpc_debug", 0);

                    $this->config = $config;
                    $this->update_config();

                    delete_option("swcfpc_maxage");
                    delete_option("swcfpc_debug");
                    delete_option("swcfpc_cf_zoneid");
                    delete_option("swcfpc_cf_zoneid_list");
                    delete_option("swcfpc_cf_email");
                    delete_option("swcfpc_cf_apikey");
                    delete_option("swcfpc_cf_old_bc_ttl");
                    delete_option("swcfpc_cf_page_rule_id");
                    delete_option("swcfpc_cf_auto_purge");
                    delete_option("swcfpc_cf_cache_enabled");
                    delete_option("swcfpc_cf_excluded_urls");
                    delete_option("swcfpc_cf_bypass_front_page");
                    delete_option("swcfpc_cf_bypass_pages");
                    delete_option("swcfpc_cf_bypass_home");
                    delete_option("swcfpc_cf_bypass_archives");
                    delete_option("swcfpc_cf_bypass_tags");
                    delete_option("swcfpc_cf_bypass_category");
                    delete_option("swcfpc_cf_bypass_feeds");
                    delete_option("swcfpc_cf_bypass_search_pages");
                    delete_option("swcfpc_cf_bypass_author_pages");
                    delete_option("swcfpc_cf_bypass_single_post");
                    delete_option("swcfpc_cf_bypass_404");
                    delete_option("swcfpc_cf_bypass_logged_in");
                    delete_option("swcfpc_cf_bypass_amp");
                    delete_option("swcfpc_cf_bypass_file_robots");
                    delete_option("swcfpc_cf_bypass_sitemap");
                    delete_option("swcfpc_cf_bypass_ajax");
                    delete_option("swcfpc_cf_subdomain");

                }

                if( version_compare( $current_version, "3.6", "<") ) {

                    $nginx_file_path = $this->get_upload_directory_path()."/nginx.conf";

                    if( file_exists($nginx_file_path) )
                        @unlink( $nginx_file_path );

                }

                if( version_compare( $current_version, "3.8", "<") ) {

                    $this->set_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY);
                    $this->update_config();

                }

                if( version_compare( $current_version, "4.0", "<") ) {

                    $installer = new SWCFPC_Installer();
                    $installer->start();

                    $this->set_single_config("cf_purge_url_secret_key", $this->generate_password(20, false, false));
                    $this->set_single_config("cf_browser_maxage", $this->get_single_config("browser_maxage", 60) );
                    $this->set_single_config("cf_strip_cookies", 0);
                    $this->set_single_config("log_enabled", $this->get_single_config("debug", 0));
                    $this->set_single_config("log_expiration", 7);
                    $this->set_single_config("cf_bypass_post", 0);
                    $this->set_single_config("cf_bypass_query_var", 0);

                    $excluded_urls = $this->get_single_config("cf_excluded_urls", "");

                    if( is_array($excluded_urls) && count($excluded_urls) > 0 ) {

                        $new_uris_array = array();

                        foreach( $excluded_urls as $single_url ) {

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

                        $this->set_single_config("cf_excluded_urls", $new_uris_array);

                    }

                    $this->update_config();

                }


                if( version_compare( $current_version, "4.1.3", "<") ) {

                    $installer = new SWCFPC_Installer();
                    $installer->start();

                    $this->set_single_config("cf_remove_purge_option_toolbar", 0);
                    $this->set_single_config("cf_disable_single_metabox", 0);

                    $this->update_config();

                }

            }

            update_option("swcfpc_version", $this->version);

        }


        function deactivate_plugin() {

            global $wpdb;

            $this->objects["cache_controller"]->reset_all();

            $wpdb->query( "DROP TABLE {$wpdb->prefix}swcfpc_logs" );

        }


        function get_objects() {
            return $this->objects;
        }


        function add_plugin_action_links( $links ) {

            $mylinks = array(
                '<a href="' . admin_url( 'options-general.php?page=wp-cloudflare-super-page-cache-index' ) . '">'.__( 'Settings', 'wp-cloudflare-page-cache' ).'</a>',
            );

            return array_merge( $links, $mylinks );

        }


        function get_cloudflare_api_subdomain() {

            if( defined('SWCFPC_CF_API_SUBDOMAIN') )
                return SWCFPC_CF_API_SUBDOMAIN;

            return $this->get_single_config("cf_subdomain", "");

        }


        function get_cloudflare_api_zone_id() {

            if( defined('SWCFPC_CF_API_ZONE_ID') )
                return SWCFPC_CF_API_ZONE_ID;

            return $this->get_single_config("cf_zoneid", "");

        }


        function get_cloudflare_api_key() {

            if( defined('SWCFPC_CF_API_KEY') )
                return SWCFPC_CF_API_KEY;

            return $this->get_single_config("cf_apikey", "");

        }


        function get_cloudflare_api_email() {

            if( defined('SWCFPC_CF_API_EMAIL') )
                return SWCFPC_CF_API_EMAIL;

            return $this->get_single_config("cf_email", "");

        }


        function get_cloudflare_api_token() {

            if( defined('SWCFPC_CF_API_TOKEN') )
                return SWCFPC_CF_API_TOKEN;

            return $this->get_single_config("cf_apitoken", "");

        }


        function get_upload_directory_path() {

            $upload     = wp_upload_dir();
            $upload_dir = $upload['basedir'];

            return $upload_dir . '/wp-cloudflare-super-page-cache';

        }


        function get_upload_directory_url() {

            $upload     = wp_upload_dir();
            $upload_dir = $upload['baseurl'];

            return $upload_dir . '/wp-cloudflare-super-page-cache';

        }


        function create_upload_directory() {

            return wp_mkdir_p( $this->get_upload_directory_path(), 0755 );

        }


        function generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {

            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $password = '';

            if ( $special_chars ) {
                $chars .= '!@#$%^&*()';
            }
            if ( $extra_special_chars ) {
                $chars .= '-_ []{}<>~`+=,.;:/?|';
            }

            for ( $i = 0; $i < $length; $i++ ) {
                $password .= substr( $chars, rand( 0, strlen( $chars ) - 1 ), 1 );
            }

            return $password;

        }


        function is_litespeed_webserver() {

            if( isset($_SERVER['x-turbo-charged-by']) && stripos($_SERVER['x-turbo-charged-by'], "LiteSpeed") )
                return true;

            if( isset($_SERVER['server']) && stripos($_SERVER['server'], "LiteSpeed") )
                return true;

            return false;

        }

    }


    // Activate this plugin as last plugin
    add_action('plugins_loaded', function () {

        if( !isset( $GLOBALS['sw_cloudflare_pagecache'] ) || empty( $GLOBALS['sw_cloudflare_pagecache'] ) )
            $GLOBALS['sw_cloudflare_pagecache'] = new SW_CLOUDFLARE_PAGECACHE();

    }, PHP_INT_MAX);

}

//$sw_cloudflare_pagecache = new SW_CLOUDFLARE_PAGECACHE();


