<?php

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

class SWCFPC_Cache_Controller
{

    private $main_instance = null;

    private $objects;
    
    private $skip_cache = false;
    private $cache_buster = "swcfpc";
    private $htaccess_path = "";


    function __construct( $cache_buster, $main_instance )
    {

        $this->cache_buster  = $cache_buster;
        $this->main_instance = $main_instance;

        if( !function_exists('get_home_path') )
            require_once ABSPATH . 'wp-admin/includes/file.php';

        $this->htaccess_path = get_home_path().".htaccess";

        $this->actions();
        
    }


    function actions() {

        add_action( 'wp_footer',    array($this, 'inject_js_code'), PHP_INT_MAX );
        add_action( 'admin_footer', array($this, 'inject_js_code'), PHP_INT_MAX );

        // Ajax clear whole cache
        add_action( 'wp_ajax_swcfpc_purge_whole_cache', array($this, 'ajax_purge_whole_cache') );

        // Ajax clear single post cache
        add_action( 'wp_ajax_swcfpc_purge_whole_cache', array($this, 'ajax_purge_single_post_cache') );

        // Ajax reset all
        add_action( 'wp_ajax_swcfpc_reset_all', array($this, 'ajax_reset_all') );

        add_action( 'init', array( $this, 'force_bypass_for_logged_in_users' ), PHP_INT_MAX );

        // This fires on both backend and frontend and it's used to check for URLs to bypass
        add_action( 'init', array($this, 'bypass_cache_on_init'), PHP_INT_MAX );

        // This fires on frontend
        add_action( 'template_redirect', array($this, 'apply_cache'), PHP_INT_MAX );

        // This fires on backend
        add_action( 'admin_init', array($this, 'apply_cache'), PHP_INT_MAX );

        // Purge on cronjob
        add_action( 'init', array($this, 'cronjob_purge_cache') );

        // WP Super Cache actions
        add_action( 'wp_cache_cleared', array($this, 'wp_super_cache_hooks'), PHP_INT_MAX );

        // W3TC actions
        add_action( 'w3tc_flush_dbcache',       array($this, 'w3tc_hooks'), PHP_INT_MAX );
        add_action( 'w3tc_flush_all',           array($this, 'w3tc_hooks'), PHP_INT_MAX );
        add_action( 'w3tc_flush_fragmentcache', array($this, 'w3tc_hooks'), PHP_INT_MAX );
        add_action( 'w3tc_flush_objectcache',   array($this, 'w3tc_hooks'), PHP_INT_MAX );
        add_action( 'w3tc_flush_posts',         array($this, 'w3tc_hooks'), PHP_INT_MAX );
        add_action( 'w3tc_flush_post',          array($this, 'w3tc_hooks'), PHP_INT_MAX );
        add_action( 'w3tc_flush_minify',        array($this, 'w3tc_hooks'), PHP_INT_MAX );

        // WP Rocket actions
        add_action( 'after_rocket_clean_post',   array($this, 'wp_rocket_hooks'), PHP_INT_MAX );
        add_action( 'after_rocket_clean_domain', array($this, 'wp_rocket_hooks'), PHP_INT_MAX );

        // LiteSpeed actions
        add_action( 'litespeed_purged_all',   array($this, 'litespeed_hooks'), PHP_INT_MAX );

        // WP Fastest Cache actions
        add_action( 'wpfc_delete_cache', array($this, 'wp_fastest_cache_hooks'), PHP_INT_MAX );

        // Hummingbird actions
        add_action( 'wphb_clear_cache_url', array($this, 'hummingbird_hooks'), PHP_INT_MAX );

        // Woocommerce actions
        add_action( 'woocommerce_updated_product_stock', array($this, 'woocommerce_purge_product_page_on_stock_change'), PHP_INT_MAX, 1 );

        // Purge cache on comments
        add_action( 'transition_comment_status', array($this, 'purge_cache_when_comment_is_approved'), PHP_INT_MAX, 3 );
        add_action( 'comment_post',              array($this, 'purge_cache_when_new_comment_is_added'), PHP_INT_MAX, 3 );
        add_action( 'delete_comment',            array($this, 'purge_cache_when_comment_is_deleted'), PHP_INT_MAX, 2 );


        $purge_actions = array(
            'wp_update_nav_menu',                                     // When a custom menu is updated
            'update_option_theme_mods_' . get_option( 'stylesheet' ), // When any theme modifications are updated
            'avada_clear_dynamic_css_cache',                          // When Avada theme purge its own cache
            'autoptimize_action_cachepurged',                         // Compat with https://wordpress.org/plugins/autoptimize
            'switch_theme',                                           // When user changes the theme
            'customize_save_after',                                   // Edit theme
            'permalink_structure_changed',                            // When permalink structure is update
        );

        foreach ($purge_actions as $action) {
            add_action( $action, array($this, 'purge_cache_on_theme_edit'), PHP_INT_MAX );
        }

        $purge_actions = array(
            'deleted_post',                     // Delete a post
            'wp_trash_post',                    // Before a post is sent to the Trash
            'clean_post_cache',                 // After a post’s cache is cleaned
            'edit_post',                        // Edit a post - includes leaving comments
            'delete_attachment',                // Delete an attachment - includes re-uploading
            'elementor/editor/after_save',      // Elementor edit
            'elementor/core/files/clear_cache', // Elementor clear cache
        );

        foreach ($purge_actions as $action) {
            add_action( $action, array($this, 'purge_cache_on_post_edit'), PHP_INT_MAX, 2 );
        }

        add_action( 'transition_post_status', array($this, 'purge_cache_when_post_is_published'), PHP_INT_MAX, 3 );

        // Metabox
        if( $this->main_instance->get_single_config("cf_disable_single_metabox", 0) == 0 ) {
            add_action('add_meta_boxes', array($this, 'add_metaboxes'), PHP_INT_MAX);
            add_action('save_post', array($this, 'swcfpc_cache_mbox_save_values'), PHP_INT_MAX);
        }

    }


    function get_cache_buster() {

        return $this->cache_buster;

    }


    function add_metaboxes() {

        add_meta_box(
            'swcfpc_cache_mbox',
            __('Cloudflare Page Cache Settings', 'wp-cloudflare-page-cache'),
            array($this, 'swcfpc_cache_mbox_callback'),
            array("post", "page"),
            'side'
        );

    }


    function swcfpc_cache_mbox_callback($post) {

        $bypass_cache = intval( get_post_meta( $post->ID, "swcfpc_bypass_cache", true ) );

        ?>

        <label for="swcfpc_bypass_cache"><?php _e('Bypass the cache for this page', 'wp-cloudflare-page-cache'); ?></label>
        <select name="swcfpc_bypass_cache">
            <option value="0" <?php if($bypass_cache == 0) echo "selected"; ?>><?php _e('No', 'wp-cloudflare-page-cache'); ?></option>
            <option value="1" <?php if($bypass_cache == 1) echo "selected"; ?>><?php _e('Yes', 'wp-cloudflare-page-cache'); ?></option>
        </select>

        <?php

    }


    function swcfpc_cache_mbox_save_values($post_id) {

        if( array_key_exists('swcfpc_bypass_cache', $_POST) ) {
            update_post_meta( $post_id, 'swcfpc_bypass_cache', $_POST['swcfpc_bypass_cache'] );
        }

    }


    function force_bypass_for_logged_in_users() {

        if( !function_exists('is_user_logged_in') ) {
            include_once( ABSPATH . "wp-includes/pluggable.php" );
        }

        if ( is_user_logged_in() && $this->is_cache_enabled() ) {
            add_action( 'wp_footer', array( $this, 'inject_js_code' ), 100 );
            add_action( 'admin_footer', array( $this, 'inject_js_code' ), 100 );
        }

    }


    function bypass_cache_on_init() {

        if( ! $this->is_cache_enabled() ) {
            header("X-WP-CF-Super-Cache: disabled");
            return;
        }

        if( $this->skip_cache )
            return;

        if( $this->is_url_to_bypass() ) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("X-WP-CF-Super-Cache: no-cache");
            header('X-WP-CF-Super-Cache-Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            $this->skip_cache = true;
            return;
        }

    }


    function apply_cache() {

        $this->objects = $this->main_instance->get_objects();

        if( ! $this->is_cache_enabled() ) {
            header("X-WP-CF-Super-Cache: disabled");
            header('X-WP-CF-Super-Cache-Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            return;
        }

        if( $this->skip_cache ) {
            return;
        }

        if ( $this->can_i_bypass_cache() ) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("X-WP-CF-Super-Cache: no-cache");
            header('X-WP-CF-Super-Cache-Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            return;
        }

        if( $this->main_instance->get_single_config("cf_strip_cookies", 0) > 0 ) {
            header_remove('Set-Cookie');
        }

        header_remove('Pragma');
        header_remove('Expires');
        header_remove('Cache-Control');
        header('Cache-Control: '.$this->get_cache_control_value());
        header("X-WP-CF-Super-Cache: cache");
        header("X-WP-CF-Super-Cache-Active: 1");
        header('X-WP-CF-Super-Cache-Cache-Control: '.$this->get_cache_control_value());

    }


    function cronjob_purge_cache() {

        if( isset($_GET[$this->cache_buster]) && isset($_GET['swcfpc-purge-all']) && $_GET['swcfpc-purge-all'] == $this->main_instance->get_single_config("cf_purge_url_secret_key", wp_generate_password(20, false, false)) ) {

            $this->objects = $this->main_instance->get_objects();
            $error = "";

            $this->objects["cloudflare"]->purge_cache($error);
            $this->objects["logs"]->add_log("cache_controller::cronjob_purge_cache", "Purge whole Cloudflare cache" );

        }

    }


    function purge_cache_when_comment_is_approved($new_status, $old_status, $comment) {

        if( $this->main_instance->get_single_config("cf_auto_purge_on_comments", 0) > 0 ) {

            if ($old_status != $new_status && $new_status == 'approved') {

                

                $this->objects = $this->main_instance->get_objects();

                $error = "";
                $urls = array();

                $urls[] = get_permalink($comment->comment_post_ID);
                $this->objects["cloudflare"]->purge_cache_urls($urls, $error);
                $this->objects["logs"]->add_log("cache_controller::purge_cache_when_comment_is_approved", "Purge Cloudflare cache for only post ".$comment->comment_post_ID );

            }

        }

    }


    function purge_cache_when_new_comment_is_added( $comment_ID, $comment_approved, $commentdata ) {

        if( $this->main_instance->get_single_config("cf_auto_purge_on_comments", 0) > 0 ) {

            if (isset($commentdata['comment_post_ID'])) {

                

                $this->objects = $this->main_instance->get_objects();

                $error = "";
                $urls = array();

                $urls[] = get_permalink($commentdata['comment_post_ID']);
                $this->objects["cloudflare"]->purge_cache_urls($urls, $error);
                $this->objects["logs"]->add_log("cache_controller::purge_cache_when_new_comment_is_added", "Purge Cloudflare cache for only post ".$commentdata['comment_post_ID'] );

            }

        }

    }


    function purge_cache_when_comment_is_deleted( $comment_ID, $comment ) {

        if( $this->main_instance->get_single_config("cf_auto_purge_on_comments", 0) > 0 ) {

            $this->objects = $this->main_instance->get_objects();

            $error = "";
            $urls = array();

            $urls[] = get_permalink($comment->comment_post_ID);
            $this->objects["cloudflare"]->purge_cache_urls($urls, $error);
            $this->objects["logs"]->add_log("cache_controller::purge_cache_when_comment_is_deleted", "Purge Cloudflare cache for only post $comment->comment_post_ID" );

        }

    }


    function purge_cache_when_post_is_published( $new_status, $old_status, $post ) {

        if( $old_status != 'publish' && $new_status == 'publish' ) {

            $this->objects = $this->main_instance->get_objects();

            $error = "";

            if( $this->main_instance->get_single_config("cf_auto_purge_all", 0) > 0 ) {
                $this->objects["cloudflare"]->purge_cache( $error );
                $this->objects["logs"]->add_log("cache_controller::purge_cache_when_post_is_published", "Purge whole Cloudflare cache" );
            }
            else {
                $urls = $this->get_post_related_links( $post->ID );
                $this->objects["cloudflare"]->purge_cache_urls( $urls, $error );
                $this->objects["logs"]->add_log("cache_controller::purge_cache_when_post_is_published", "Purge Cloudflare cache for only post id $post->ID and related contents" );
            }

        }

    }


    function purge_cache_on_post_edit( $postId ) {

        $this->objects = $this->main_instance->get_objects();

        $error = "";

        $validPostStatus = array('publish', 'trash');
        $thisPostStatus = get_post_status($postId);

        if (get_permalink($postId) != true || !in_array($thisPostStatus, $validPostStatus)) {
            return;
        }

        if (is_int(wp_is_post_autosave($postId)) ||  is_int(wp_is_post_revision($postId))) {
            return;
        }

        if( $this->main_instance->get_single_config("cf_auto_purge_all", 0) > 0 ) {
            $error = "";
            $this->objects["cloudflare"]->purge_cache( $error );
            return;
        }

        $savedPost = get_post($postId);

        if (is_a($savedPost, 'WP_Post') == false) {
            return;
        }

        $urls = $this->get_post_related_links($postId);

        $this->objects["cloudflare"]->purge_cache_urls( $urls, $error );
        $this->objects["logs"]->add_log("cache_controller::purge_cache_on_post_edit", "Purge Cloudflare cache for only post id $postId and related contents" );

    }


    function purge_cache_on_theme_edit() {

        if( ($this->main_instance->get_single_config("cf_auto_purge", 0) > 0 || $this->main_instance->get_single_config("cf_auto_purge_all", 0) > 0) && $this->is_cache_enabled() ) {

            $this->objects = $this->main_instance->get_objects();

            $error = "";
            $this->objects["cloudflare"]->purge_cache($error);
            $this->objects["logs"]->add_log("cache_controller::purge_cache_on_theme_edit", "Purge whole Cloudflare cache" );

        }

    }


    function get_post_related_links($postId) {

        $this->objects = $this->main_instance->get_objects();

        $listofurls = array();
        $postType = get_post_type($postId);

        //Purge taxonomies terms URLs
        $postTypeTaxonomies = get_object_taxonomies($postType);

        foreach ($postTypeTaxonomies as $taxonomy) {
            $terms = get_the_terms($postId, $taxonomy);

            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }

            foreach ($terms as $term) {

                $termLink = get_term_link($term);

                if (!is_wp_error($termLink)) {

                    array_push($listofurls, $termLink);

                    if( $this->main_instance->get_single_config("cf_post_per_page", 0) > 0 ) {

                        // Thanks to Davide Prevosto for the suggest
                        $term_count   = $term->count;
                        $pages_number = ceil($term_count / $this->main_instance->get_single_config("cf_post_per_page", 0) );
                        $max_pages    = $pages_number > 10 ? 10 : $pages_number; // Purge max 10 pages

                        for ($i=2; $i<=$max_pages; $i++) {
                            $paginated_url = $termLink . 'page/' . user_trailingslashit($i);
                            array_push($listofurls, $paginated_url);
                        }

                    }

                }

            }

        }

        // Author URL
        array_push(
            $listofurls,
            get_author_posts_url(get_post_field('post_author', $postId)),
            get_author_feed_link(get_post_field('post_author', $postId))
        );

        // Archives and their feeds
        if (get_post_type_archive_link($postType) == true) {
            array_push(
                $listofurls,
                get_post_type_archive_link($postType),
                get_post_type_archive_feed_link($postType)
            );
        }

        // Post URL
        array_push($listofurls, get_permalink($postId));

        // Also clean URL for trashed post.
        if (get_post_status($postId) == 'trash') {
            $trashPost = get_permalink($postId);
            $trashPost = str_replace('__trashed', '', $trashPost);
            array_push($listofurls, $trashPost, $trashPost.'feed/');
        }

        // Feeds
        array_push(
            $listofurls,
            get_bloginfo_rss('rdf_url'),
            get_bloginfo_rss('rss_url'),
            get_bloginfo_rss('rss2_url'),
            get_bloginfo_rss('atom_url'),
            get_bloginfo_rss('comments_rss2_url'),
            get_post_comments_feed_link($postId)
        );

        // Home Page and (if used) posts page
        array_push($listofurls, home_url('/'));
        $pageLink = get_permalink(get_option('page_for_posts'));
        if (is_string($pageLink) && !empty($pageLink) && get_option('show_on_front') == 'page') {
            array_push($listofurls, $pageLink);
        }

        // Purge https and http URLs
        if (function_exists('force_ssl_admin') && force_ssl_admin()) {
            $listofurls = array_merge($listofurls, str_replace('https://', 'http://', $listofurls));
        } elseif (!is_ssl() && function_exists('force_ssl_content') && force_ssl_content()) {
            $listofurls = array_merge($listofurls, str_replace('http://', 'https://', $listofurls));
        }

        return $listofurls;
    }


    function reset_all() {

        $this->objects = $this->main_instance->get_objects();
        $error = "";

        $this->objects["logs"]->add_log("cache_controller::reset_all", "Reset start" );

        // Reset old browser cache TTL
        $this->objects["cloudflare"]->change_browser_cache_ttl( $this->main_instance->get_single_config("cf_old_bc_ttl", 0), $error );

        // Delete the page rule
        $this->objects["cloudflare"]->delete_page_rule( $this->main_instance->get_single_config("cf_page_rule_id", ""), $error );

        $this->main_instance->set_config( $this->main_instance->get_default_config() );
        $this->main_instance->update_config();

        $this->reset_htaccess();

        $this->objects["logs"]->add_log("cache_controller::reset_all", "Reset complete" );

    }


    function inject_js_code() {

        if( !$this->is_cache_enabled() )
            return;

        if( !is_user_logged_in() )
            return;

        $selectors = "a";

        if( is_admin() )
            $selectors = "#wp-admin-bar-my-sites-list a, #wp-admin-bar-site-name a, #wp-admin-bar-view-site a, #wp-admin-bar-view a, .row-actions a, .preview, #sample-permalink a, #message a, #editor .is-link, #editor .editor-post-preview, #editor .editor-post-permalink__link";

        ?>

        <script id="swcfpc" data-cfasync="false">

            function swcfpc_adjust_internal_links( selectors_txt ) {

                var comp = new RegExp(location.host);

                [].forEach.call(document.querySelectorAll( selectors_txt ), function(el) {

                    if( comp.test( el.href ) && !el.href.includes("<?php echo $this->cache_buster; ?>=1") ) {

                        if( el.href.indexOf('#') != -1 ) {

                            var link_split = el.href.split("#");
                            el.href = link_split[0];
                            el.href += (el.href.indexOf('?') != -1 ? "&<?php echo $this->cache_buster; ?>=1" : "?<?php echo $this->cache_buster; ?>=1");
                            el.href += "#"+link_split[1];

                        }
                        else {
                            el.href += (el.href.indexOf('?') != -1 ? "&<?php echo $this->cache_buster; ?>=1" : "?<?php echo $this->cache_buster; ?>=1");
                        }

                    }

                });

            }

            document.addEventListener("DOMContentLoaded", function() {

                swcfpc_adjust_internal_links("<?php echo $selectors; ?>");

            });

            window.addEventListener("load", function() {

                swcfpc_adjust_internal_links("<?php echo $selectors; ?>");

            });

            setInterval(function(){ swcfpc_adjust_internal_links("<?php echo $selectors; ?>"); }, 3000);


            // Looking for dynamic link added after clicking on Pusblish/Update button
            var swcfpc_wordpress_btn_publish = document.querySelector(".editor-post-publish-button__button");

            if( swcfpc_wordpress_btn_publish !== undefined && swcfpc_wordpress_btn_publish !== null ) {

                swcfpc_wordpress_btn_publish.addEventListener('click', function() {

                    var swcfpc_wordpress_edited_post_interval = setInterval(function() {

                        var swcfpc_wordpress_edited_post_link = document.querySelector(".components-snackbar__action");

                        if( swcfpc_wordpress_edited_post_link !== undefined ) {
                            swcfpc_adjust_internal_links(".components-snackbar__action");
                            clearInterval(swcfpc_wordpress_edited_post_link);
                        }

                    }, 100);

                }, false);

            }

        </script>

        <?php

    }


    function is_url_to_bypass() {

        $this->objects = $this->main_instance->get_objects();

        // Bypass AMP
        if( $this->main_instance->get_single_config("cf_bypass_amp", 0) > 0 && preg_match("/(\/amp\/page\/[0-9]*)|(\/amp\/?)/", $_SERVER['REQUEST_URI']) ) {
            return true;
        }

        // Bypass sitemap
        if( $this->main_instance->get_single_config("cf_bypass_sitemap", 0) > 0 && strcasecmp($_SERVER['REQUEST_URI'], "/sitemap_index.xml") == 0 || preg_match("/[a-zA-Z0-9]-sitemap.xml$/", $_SERVER['REQUEST_URI']) ) {
            return true;
        }

        // Bypass robots.txt
        if( $this->main_instance->get_single_config("cf_bypass_file_robots", 0) > 0 && preg_match("/^\/robots.txt/", $_SERVER['REQUEST_URI']) ) {
            return true;
        }

        // Bypass the cache on excluded URLs
        $excluded_urls = $this->main_instance->get_single_config("cf_excluded_urls", "");

        if( is_array($excluded_urls) && count($excluded_urls) > 0 ) {

            $current_url = $_SERVER['REQUEST_URI'];

            if( isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING']) > 0 )
                $current_url .= "?".$_SERVER['QUERY_STRING'];

            foreach( $excluded_urls as $url_to_exclude ) {

                if( fnmatch($url_to_exclude, $current_url, FNM_CASEFOLD) ) {
                    return true;
                }

            }

        }

        if( isset($_GET[$this->cache_buster]) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') || (defined('DOING_AJAX') && DOING_AJAX) ) {
            return true;
        }

        return false;

    }


    function can_i_bypass_cache() {

        global $post;

        $this->objects = $this->main_instance->get_objects();

        // Bypass the cache using filter
        if( has_filter('swcfpc_cache_bypass') ) {

            $cache_bypass = apply_filters('swcfpc_cache_bypass', false);

            if( $cache_bypass === true )
                return true;

        }

        // Bypass single post by metabox
        if( $this->main_instance->get_single_config("cf_disable_single_metabox", 0) == 0 && is_object($post) && intval( get_post_meta( $post->ID, "swcfpc_bypass_cache", true ) ) > 0 ) {
            return true;
        }

        // Bypass requests with query var
        if( $this->main_instance->get_single_config("cf_bypass_query_var", 0) > 0 && isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING']) > 0 ) {
            return true;
        }

        // Bypass POST requests
        if( $this->main_instance->get_single_config("cf_bypass_post", 0) > 0 && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == "POST" ) {
            return true;
        }

        // Bypass AJAX requests
        if( $this->main_instance->get_single_config("cf_bypass_ajax", 0) > 0 ) {

            if( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
                return true;
            }

            if( function_exists( 'is_ajax' ) && is_ajax() ) {
                return true;
            }

            if( (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') || (defined('DOING_AJAX') && DOING_AJAX) ) {
                return true;
            }

        }

        // Bypass WooCommerce pages
        if( $this->main_instance->get_single_config("cf_bypass_woo_cart_page", 0) > 0 && function_exists( 'is_cart' ) && is_cart() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_woo_checkout_page", 0) > 0 && function_exists( 'is_checkout' ) && is_checkout() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_woo_checkout_pay_page", 0) > 0 && function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_woo_shop_page", 0) > 0 && function_exists( 'is_shop' ) && is_shop() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_woo_product_page", 0) > 0 && function_exists( 'is_product' ) && is_product() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_woo_product_cat_page", 0) > 0 && function_exists( 'is_product_category' ) && is_product_category() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_woo_product_tag_page", 0) > 0 && function_exists( 'is_product_tag' ) && is_product_tag() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_woo_product_tax_page", 0) > 0 && function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_woo_pages", 0) > 0 && function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
            return true;
        }


        // Bypass Wordpress pages
        if( $this->main_instance->get_single_config("cf_bypass_front_page", 0) > 0 && is_front_page() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_pages", 0) > 0 && is_page() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_home", 0) > 0 && is_home() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_archives", 0) > 0 && is_archive() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_tags", 0) > 0 && is_tag() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_category", 0) > 0 && is_category() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_feeds", 0) > 0 && is_feed() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_search_pages", 0) > 0 && is_search() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_author_pages", 0) > 0 && is_author() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_single_post", 0) > 0 && is_single() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_404", 0) > 0 && is_404() ) {
            return true;
        }


        if( $this->main_instance->get_single_config("cf_bypass_logged_in", 0) > 0 && is_user_logged_in() ) {
            return true;
        }


        // Bypass cache if the parameter swcfpc is setted or we are on backend
        if( isset($_GET[$this->cache_buster]) || is_admin() ) {
            return true;
        }

        return false;

    }


    function get_cache_control_value() {

        $this->objects = $this->main_instance->get_objects();

        $value = 's-max-age='.$this->main_instance->get_single_config("cf_maxage", 604800).', s-maxage='.$this->main_instance->get_single_config("cf_maxage", 604800).', max-age='.$this->main_instance->get_single_config("cf_browser_maxage", 60);

        return $value;

    }

    
    function is_cache_enabled() {

        $this->objects = $this->main_instance->get_objects();

        if( $this->main_instance->get_single_config("cf_cache_enabled", 0) > 0 )
            return true;

        return false;

    }


    function w3tc_hooks() {

        if( $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_minfy", 0) > 0 ||
            $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_posts", 0) > 0 ||
            $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_objectcache", 0) > 0 ||
            $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_fragmentcache", 0) > 0 ||
            $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_dbcache", 0) > 0 ||
            $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_all", 0) > 0
        ) {

            $this->objects = $this->main_instance->get_objects();

            $error = "";
            $this->objects["cloudflare"]->purge_cache( $error );

            $this->objects["logs"]->add_log("cache_controller::w3tc_hooks", "Purge whole Cloudflare cache" );

        }

    }


    function wp_rocket_hooks() {

        if( $this->main_instance->get_single_config("cf_wp_rocket_purge_on_post_flush", 0) > 0 || $this->main_instance->get_single_config("cf_wp_rocket_purge_on_domain_flush", 0) > 0 ) {

            $this->objects = $this->main_instance->get_objects();

            $error = "";
            $this->objects["cloudflare"]->purge_cache( $error );

            $this->objects["logs"]->add_log("cache_controller::wp_rocket_hooks", "Purge whole Cloudflare cache" );

        }

    }


    function litespeed_hooks() {

        if( $this->main_instance->get_single_config("cf_litespeed_purge_on_cache_flush", 0) > 0 ) {

            $this->objects = $this->main_instance->get_objects();

            $error = "";
            $this->objects["cloudflare"]->purge_cache( $error );

            $this->objects["logs"]->add_log("cache_controller::litespeed_hooks", "Purge whole Cloudflare cache" );

        }

    }


    function wp_fastest_cache_hooks() {

        if( $this->main_instance->get_single_config("cf_wp_fastest_cache_purge_on_cache_flush", 0) > 0 ) {

            $this->objects = $this->main_instance->get_objects();

            $error = "";
            $this->objects["cloudflare"]->purge_cache( $error );

            $this->objects["logs"]->add_log("cache_controller::wp_fastest_cache_hooks", "Purge whole Cloudflare cache" );

        }

    }


    function hummingbird_hooks() {

        if( $this->main_instance->get_single_config("cf_hummingbird_purge_on_cache_flush", 0) > 0 ) {

            $this->objects = $this->main_instance->get_objects();

            $error = "";
            $this->objects["cloudflare"]->purge_cache( $error );

            $this->objects["logs"]->add_log("cache_controller::hummingbird_hooks", "Purge whole Cloudflare cache" );

        }

    }


    function wp_super_cache_hooks() {

        if( $this->main_instance->get_single_config("cf_wp_super_cache_on_cache_flush", 0) > 0 ) {

            $this->objects = $this->main_instance->get_objects();

            $error = "";
            $this->objects["cloudflare"]->purge_cache( $error );

            $this->objects["logs"]->add_log("cache_controller::wp_super_cache_hooks", "Purge whole Cloudflare cache" );

        }


    }


    /*function woocommerce_purge_product_page_on_stock_change( $order ) {

        if( $this->main_instance->get_single_config("cf_auto_purge_woo_product_page", 0) > 0 && function_exists('wc_get_order') ) {

            $items = $order->get_items();
            $product_cats_ids = array();

            $this->objects = $this->main_instance->get_objects();
            $urls = array();
            $error = "";

            if( function_exists('wc_get_page_id') ) {
                $urls[] = get_permalink( wc_get_page_id('shop') );
            }

            foreach ( $items as $item ) {

                $product_id = $item->get_product_id();
                //$product_variation_id = $item->get_variation_id();

                $product_cats_ids[] = wc_get_product_cat_ids( $product_id );

                $urls = array_merge( $urls, $this->get_post_related_links( $product_id) );

            }

            $urls = array_unique( $urls );

            // Reduce the multidimensional array to a flat one and get rid of ducplicate product_cat IDS
            $product_cats_ids = call_user_func_array('array_merge', $product_cats_ids);
            $product_cats_ids = array_unique($product_cats_ids);

            foreach ( $product_cats_ids as $category_id ) {
                $urls[] = get_category_link( $category_id );
            }


            $this->objects["cloudflare"]->purge_cache_urls( $urls, $error );

            $this->objects["logs"]->add_log("cache_controller::woocommerce_purge_product_page_on_stock_change", "Purge product pages and categories for WooCommerce order" );

        }

    }*/


    function woocommerce_purge_product_page_on_stock_change( $product_id ) {

        if( $this->main_instance->get_single_config("cf_auto_purge_woo_product_page", 0) > 0 && function_exists('wc_get_order') ) {

            $this->objects = $this->main_instance->get_objects();
            $urls = array();
            $error = "";

            // Get shop page URL
            if( function_exists('wc_get_page_id') ) {
                $urls[] = get_permalink( wc_get_page_id('shop') );
            }

            // Get product categories URLs
            $product_cats_ids = wc_get_product_cat_ids( $product_id );

            foreach ( $product_cats_ids as $category_id ) {
                $urls[] = get_category_link( $category_id );
            }

            // GET other related URLs
            $urls = array_merge( $urls, $this->get_post_related_links( $product_id ) );
            $urls = array_unique( $urls );

            // Purge every single URL from Cloudflare
            if( ! $this->objects["cloudflare"]->purge_cache_urls( $urls, $error ) ) {
                $this->objects["logs"]->add_log("cache_controller::woocommerce_purge_product_page_on_stock_change", "Error: $error" );
            }
            else {
                $this->objects["logs"]->add_log("cache_controller::woocommerce_purge_product_page_on_stock_change", "Purge product pages and categories for WooCommerce order");
            }

        }

    }


    function reset_htaccess() {

        insert_with_markers( $this->htaccess_path, "WP Cloudflare Super Page Cache", array() );

    }


    function write_htaccess(&$error_msg) {

        $this->objects = $this->main_instance->get_objects();

        $htaccess_lines = array();

        if( $this->main_instance->get_single_config("cf_cache_control_htaccess", 0) > 0 && $this->is_cache_enabled() && !$this->main_instance->is_litespeed_webserver() ) {

            $htaccess_lines[] = "<IfModule mod_headers.c>";
            $htaccess_lines[] = "Header unset Pragma \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
            $htaccess_lines[] = "Header always unset Pragma \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
            $htaccess_lines[] = "Header unset Expires \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
            $htaccess_lines[] = "Header always unset Expires \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
            $htaccess_lines[] = "Header unset Cache-Control \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
            $htaccess_lines[] = "Header always unset Cache-Control \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
            $htaccess_lines[] = "Header always set Cache-Control \"" . $this->get_cache_control_value() . "\" \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";

            // Add a cache-control header with the value of x-wp-cf-super-cache-cache-control response header
            //$htaccess_lines[] = "Header always set Cache-Control \"expr=%{resp:x-wp-cf-super-cache-cache-control}\" \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
            $htaccess_lines[] = "</IfModule>";

        }

        if( $this->main_instance->get_single_config("cf_strip_cookies", 0) > 0 && !$this->main_instance->is_litespeed_webserver() ) {

            $htaccess_lines[] = "<IfModule mod_expires.c>";
            $htaccess_lines[] = "Header unset Set-Cookie \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
            $htaccess_lines[] = "Header always unset Set-Cookie \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
            $htaccess_lines[] = "</IfModule>";

        }

        if( $this->main_instance->get_single_config("cf_bypass_sitemap", 0) > 0 ) {

            $htaccess_lines[] = "<IfModule mod_expires.c>";
            $htaccess_lines[] = "ExpiresActive on";
            $htaccess_lines[] = 'ExpiresByType application/xml "access plus 0 seconds"';
            $htaccess_lines[] = "</IfModule>";

        }

        if( $this->main_instance->get_single_config("cf_bypass_file_robots", 0) > 0 ) {

            $htaccess_lines[] = '<FilesMatch "robots\.txt">';
            $htaccess_lines[] = "<IfModule mod_headers.c>";
            $htaccess_lines[] = 'Header set Cache-Control "max-age=0, public"';
            $htaccess_lines[] = "</IfModule>";
            $htaccess_lines[] = "</FilesMatch>";

        }

        if( $this->main_instance->get_single_config("cf_browser_caching_htaccess", 0) > 0 ) {

            $htaccess_lines[] = "<IfModule mod_expires.c>";
            $htaccess_lines[] = "ExpiresActive on";
            $htaccess_lines[] = 'ExpiresDefault                              "access plus 4 months"';

            // Data
            $htaccess_lines[] = 'ExpiresByType application/json              "access plus 0 seconds"';
            $htaccess_lines[] = 'ExpiresByType application/xml               "access plus 0 seconds"';

            // Feed
            $htaccess_lines[] = 'ExpiresByType application/rss+xml           "access plus 1 hour"';
            $htaccess_lines[] = 'ExpiresByType application/atom+xml          "access plus 1 hour"';
            $htaccess_lines[] = 'ExpiresByType image/x-icon                  "access plus 1 week"';

            // Media: images, video, audio
            $htaccess_lines[] = 'ExpiresByType image/gif                     "access plus 6 months"';
            $htaccess_lines[] = 'ExpiresByType image/png                     "access plus 6 months"';
            $htaccess_lines[] = 'ExpiresByType image/jpeg                    "access plus 6 months"';
            $htaccess_lines[] = 'ExpiresByType image/webp                    "access plus 6 months"';
            $htaccess_lines[] = 'ExpiresByType video/ogg                     "access plus 4 months"';
            $htaccess_lines[] = 'ExpiresByType audio/ogg                     "access plus 4 months"';
            $htaccess_lines[] = 'ExpiresByType video/mp4                     "access plus 4 months"';
            $htaccess_lines[] = 'ExpiresByType video/webm                    "access plus 4 months"';

            // HTC files  (css3pie)
            $htaccess_lines[] = 'ExpiresByType text/x-component              "access plus 1 month"';

            // Webfonts
            $htaccess_lines[] = 'ExpiresByType font/ttf                      "access plus 6 months"';
            $htaccess_lines[] = 'ExpiresByType font/otf                      "access plus 6 months"';
            $htaccess_lines[] = 'ExpiresByType font/woff                     "access plus 6 months"';
            $htaccess_lines[] = 'ExpiresByType font/woff2                    "access plus 6 months"';
            $htaccess_lines[] = 'ExpiresByType image/svg+xml                 "access plus 4 months"';
            $htaccess_lines[] = 'ExpiresByType application/vnd.ms-fontobject "access plus 4 months"';

            // CSS and JavaScript
            $htaccess_lines[] = 'ExpiresByType text/css                      "access plus 1 year"';
            $htaccess_lines[] = 'ExpiresByType application/javascript        "access plus 1 year"';

            $htaccess_lines[] = "</IfModule>";

        }

        if( !insert_with_markers( $this->htaccess_path, "WP Cloudflare Super Page Cache", $htaccess_lines ) ) {
            $error_msg = __( sprintf('The .htaccess file (%s) could not be edited. Check if the file has write permissions.', $this->htaccess_path), 'wp-cloudflare-page-cache');
            return false;
        }

        return true;

    }


    function get_nginx_rules() {

        $this->objects = $this->main_instance->get_objects();

        $nginx_lines = array();

        if( $this->main_instance->get_single_config("cf_bypass_sitemap", 0) > 0 ) {
            $nginx_lines[] = "location ~* \.(xml)$ { expires -1; }";
        }

        if( $this->main_instance->get_single_config("cf_bypass_file_robots", 0) > 0 ) {
            $nginx_lines[] = "location /robots.txt { expires -1; }";
        }

        if( $this->main_instance->get_single_config("cf_browser_caching_htaccess", 0) > 0 ) {

            $nginx_lines[] = "location ~* \.(css|js)$ { expires 365d; }";
            $nginx_lines[] = "location ~* \.(jpg|jpeg|png|gif|ico|svg|webp)$ { expires 180d; }";
            $nginx_lines[] = "location ~* \.(ogg|mp4|mpeg|avi|mkv|webm|mp3)$ { expires 30d; }";
            $nginx_lines[] = "location ~* \.(ttf|otf|woff|woff2)$ { expires 120d; }";
            $nginx_lines[] = "location ~* \.(pdf)$ { expires 30d; }";
            $nginx_lines[] = "location ~* \.(json)$ { expires -1; }";

            if( $this->main_instance->get_single_config("cf_bypass_sitemap", 0) == 0 )
                $nginx_lines[] = "location ~* \.(xml)$ { expires -1; }";

        }

        return $nginx_lines;

    }


    function ajax_purge_whole_cache() {

        check_ajax_referer( 'ajax-nonce-string', 'security' );

        $return_array = array("status" => "ok");

        $this->objects = $this->main_instance->get_objects();
        $error = "";

        if( ! $this->objects["cloudflare"]->purge_cache($error) ) {
            $return_array["status"] = "error";
            $return_array["error"] = $error;
            die(json_encode($return_array));
        }

        $this->objects["logs"]->add_log("cache_controller::ajax_purge_whole_cache", "Purge whole Cloudflare cache" );

        $return_array["success_msg"] = __("Cache purged successfully. It may take up to 30 seconds for the cache to be permanently cleaned by Cloudflare", 'wp-cloudflare-page-cache');

        die(json_encode($return_array));

    }


    function ajax_purge_single_post_cache() {

        check_ajax_referer( 'ajax-nonce-string', 'security' );

        $return_array = array("status" => "ok");

        $data = stripslashes($_POST['data']);
        $data = json_decode($data, true);

        $this->objects = $this->main_instance->get_objects();
        $error = "";

        $post_id = intval($data["post_id"]);

        $urls = $this->get_post_related_links( $post_id );

        if( ! $this->objects["cloudflare"]->purge_cache_urls( $urls, $error ) ) {
            $return_array["status"] = "error";
            $return_array["error"] = $error;
            die(json_encode($return_array));
        }

        $this->objects["logs"]->add_log("cache_controller::ajax_purge_single_post_cache", "Purge Cloudflare cache for only post id $post_id and related contents" );

        $return_array["success_msg"] = __("Cache purged successfully. It may take up to 30 seconds for the cache to be permanently cleaned by Cloudflare", 'wp-cloudflare-page-cache');

        die(json_encode($return_array));

    }


    function ajax_reset_all() {

        check_ajax_referer( 'ajax-nonce-string', 'security' );

        $return_array = array("status" => "ok");

        if( !current_user_can('manage_options') ) {
            $return_array["status"] = "error";
            $return_array["error"] = __("Permission denied", "wp-cloudflare-page-cache");
            die(json_encode($return_array));
        }

        $this->reset_all();

        $return_array["success_msg"] = __("Cloudflare and all configurations have been reset to the initial settings.", 'wp-cloudflare-page-cache');

        die(json_encode($return_array));

    }
    

}