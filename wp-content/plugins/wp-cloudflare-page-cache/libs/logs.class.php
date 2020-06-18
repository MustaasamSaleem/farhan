<?php

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

class SWCFPC_Logs
{

    private $main_instance = null;

    private $objects;

    private $is_logging_enabled  = false;
    private $log_expiration_days = false;
    private $log_file_path       = false;
    private $log_file_url        = false;

    function __construct($log_file_path, $log_file_url, $log_expiration_days, $logging_enabled, $main_instance)
    {

        $this->log_file_path       = $log_file_path;
        $this->log_file_url        = $log_file_url;
        $this->log_expiration_days = $log_expiration_days;
        $this->is_logging_enabled  = $logging_enabled;
        $this->main_instance       = $main_instance;

        $this->delete_logs_older_than_days( $this->log_expiration_days );

        $this->actions();

    }


    function actions() {

        // Ajax clear logs
        add_action( 'wp_ajax_swcfpc_clear_logs', array($this, 'ajax_clear_logs') );

        // Ajax download logs
        add_action( 'wp_ajax_swcfpc_download_logs', array($this, 'ajax_download_logs') );

    }


    function enable_logging() {
        $this->is_logging_enabled = true;
    }


    function disable_logging() {
        $this->is_logging_enabled = false;
    }


    function add_log($identifier, $message) {

        if( $this->is_logging_enabled ) {

            global $wpdb;

            $wpdb->insert( "{$wpdb->prefix}swcfpc_logs", array(
                "date" => date("Y-m-d H:i:s"),
                "log_identifier" => $identifier,
                "log_msg" => $message
            ) );

        }

    }


    function get_logs() {

        global $wpdb;

        $logs = $wpdb->get_results("SELECT date, log_identifier, log_msg FROM {$wpdb->prefix}swcfpc_logs ORDER BY date", ARRAY_A);

        return $logs;

    }


    function reset_log() {

        global $wpdb;

        $wpdb->query("DELETE FROM {$wpdb->prefix}swcfpc_logs");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}swcfpc_logs AUTO_INCREMENT = 1");

    }


    function delete_logs_older_than_days( $days ) {

        global $wpdb;

        $days = intval($days);

        if( $days <= 0 ) {
            $this->reset_log();
            return true;
        }

        $query = $wpdb->prepare("DELETE FROM {$wpdb->prefix}swcfpc_logs WHERE date < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))", $days);
        $wpdb->query( $query );

        return true;

    }


    function ajax_clear_logs() {

        check_ajax_referer( 'ajax-nonce-string', 'security' );

        $return_array = array("status" => "ok");

        if( !current_user_can('manage_options') ) {
            $return_array["status"] = "error";
            $return_array["error"] = __("Permission denied", "wp-cloudflare-page-cache");
            die(json_encode($return_array));
        }

        if( file_exists($this->log_file_path) ) {

            $this->objects = $this->main_instance->get_objects();
            $error = "";
            $urls = array();

            $urls[] = get_permalink($this->log_file_url."*");
            $this->objects["cloudflare"]->purge_cache_urls($urls, $error);

            @file_put_contents( $this->log_file_path, "" );
            @unlink( $this->log_file_path );

        }

        $this->reset_log();

        $return_array["success_msg"] = __("Log cleaned successfully", "wp-cloudflare-page-cache");

        die(json_encode($return_array));

    }


    function ajax_download_logs() {

        check_ajax_referer( 'ajax-nonce-string', 'security' );

        $return_array = array("status" => "ok");

        if( !current_user_can('manage_options') ) {
            $return_array["status"] = "error";
            $return_array["error"] = __("Permission denied", "wp-cloudflare-page-cache");
            die(json_encode($return_array));
        }

        if( !file_exists($this->log_file_path) )
            @file_put_contents( $this->log_file_path, "" );

        if( !file_exists($this->log_file_path) ) {
            $return_array["status"] = "error";
            $return_array["error"] = sprintf( __("Unable export logs to %s", "wp-cloudflare-page-cache"), $this->log_file_url);
            die(json_encode($return_array));
        }

        require_once(ABSPATH . 'wp-admin/includes/plugin.php');

        $plugins = get_plugins();
        $cf_logs = $this->get_logs();
        $logs    = "";

        $logs .= "\n==================================================";
        $logs .= "\nCurrent active plugins";
        $logs .= "\n==================================================";
        $logs .= "\n\n";
        $logs .= print_r($plugins, true);


        $logs .= "\n\n";
        $logs .= "\n==================================================";
        $logs .= "\nPlugin config";
        $logs .= "\n==================================================";
        $logs .= "\n\n";

        $logs .= print_r($this->main_instance->get_config(), true);

        $logs .= "\n\n";
        $logs .= "\n==================================================";
        $logs .= "\nPlugin logs";
        $logs .= "\n==================================================";
        $logs .= "\n\n";
        $logs .= print_r($cf_logs, true);

        file_put_contents( $this->log_file_path, $logs );

        $return_array["logs_url"]    = $this->log_file_url."?cache_buster=".wp_generate_password(20, false, false);
        $return_array["success_msg"] = __("Click here to download logs", "wp-cloudflare-page-cache");

        die(json_encode($return_array));

    }

}