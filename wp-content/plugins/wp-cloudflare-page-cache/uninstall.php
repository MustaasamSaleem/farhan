<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

delete_option("swcfpc_config");
delete_option("swcfpc_version");

$wpdb->query( "DROP TABLE {$wpdb->prefix}swcfpc_logs" );