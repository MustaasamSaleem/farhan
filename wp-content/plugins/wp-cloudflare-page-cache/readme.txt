=== WP Cloudflare Super Page Cache ===
Tags: cloudflare cache, improve speed, improve performance, page caching
Requires at least: 3.0.1
Tested up to: 5.4
Stable tag: 4.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Speed up a Wordpress website by enabling and managing a page cache on a Cloudflare free plan.

== Description ==

The free Cloudflare plan allows you to enable a page cache by entering the *Cache Everything* rule, greatly improving response times. 

However for dynamic websites such as Wordpress, it is not possible to use this rule without running into problems as it is not possible to exclude critical web pages from the cache, the sessions for logged in users, ajax requests and much more.

**Thanks to this plugin all of this becomes possible.**

You will be able to significantly **improve the response times of your Wordpress website** by taking advantage of the very fast Cloudflare cache also for PHP pages. The alternative to this plugin is to purchase and configure the Enterprise plan.

**This plugin is compatible with all versions of Wordpress and all Wordpress themes.** It can also be used in conjunction with other performance plugins as long as their rules do not interfere with the Cloudflare cache.

== Installation ==

FROM YOUR WORDPRESS DASHBOARD

1. Visit "Plugins" > Add New
2. Search for WP Cloudflare Super Page Cache
3. Activate WP Cloudflare Super Page Cache from your Plugins page.

FROM WORDPRESS.ORG

1. Download WP Cloudflare Super Page Cache
2. Upload the "wp-cloudflare-super-page-cache" directory to your "/wp-content/plugins/" directory, using ftp, sftp, scp etc.
3. Activate WP Cloudflare Super Page Cache from your Plugins page.

== Frequently Asked Questions ==

= How do I know if everything is working properly? =

To verify that everything is working properly, I invite you to check the HTTP response headers of the displayed page in Incognito mode (browse in private). WP Cloudflare Super Page Cache returns two headers:

**x-wp-cf-super-cache**

If its value is **cache**, WP Cloudflare Super Page Cache is active on the displayed page and the page cache is enabled. If **no-cache**, WP Cloudflare Super Page Cache is active but the page cache is disabled for the displayed page.

**x-wp-cf-super-cache-active**

This header is present only if the previous header has the value **cache**.

If its value is **1**, the displayed page should have been placed in the Cloudflare cache.

To find out if the page is returned from the cache, Cloudflare sets its header called **cf-cache-status**. 

If its value is **HIT**, the page has been returned from cache. 

If **MISS**, the page was not found in cache. Refresh the page.

If **BYPASS**, the page was excluded from WP Cloudflare Super Page Cache. 

If **EXPIRED**, the page was cached but the cache has expired.

= Error: Actor 'com.cloudflare.api.token.' requires permission 'com.cloudflare.api.account.zone.list' to list zones (err code: 0 ) =

If you are using an API Token, check that you entered the domain name exactly as on Cloudflare

= Error: Page Rule validation failed: See messages for details. (err code: 1004 ) =

Login to Cloudflare, click on your domain and go to Page Rules section. Check if a *Cache Everything* page rule already exists for your domain. If yes, delete it. Now from the settings page of WP Cloudflare Super Page Cache, disable and re-enable the cache

= Do you allow to bypass the cache for logged in users even on free plan? =

Yes. It is the main purpose of this plugin.

= What is the swcfpc query variabile I see to every internal links when I'm logged in? =

It is a cache buster. Allows you, while logged in, to bypass the Cloudflare cache for pages that could be cached.

= Do you automatically clean up the cache on website changes? =

Yes, you can enable this option from the settings page.

= Can I restore all Cloudflare settings as before the plugin activation? =

Yes, there is a reset button. Anyway if you deactivate the plugin, all the changes made on Cloudflare will be restored.

= What happens if I delete the plugin? =

I advise you to disable the plugin before deleting it, to allow you to restore all the information on Cloudflare. Then you can proceed with the elimination. This plugin will delete all the data stored into the database so that your Wordpress installation remains clean.

= What happens to the browser caching settings on Cloudflare? =

You will not be able to use them anymore. You will need to enter the browser caching settings on your htaccess file or, if you use Nginx, in your server block's configuration file.

= Does it work with multisite? =

Yes but it must be installed separately for each website in the network as each site requires an ad-hoc configuration and may also be part of different Cloudflare accounts.

= Can I use this plugin together with other performance plugins such like WP Rocket or W3 Total Cache? =

Yes, you can. I recommend you the following stack:

1. WP Fastest Cache as fallback page caching system (for page caching only)
2. Litespeed Cache for on-page optimizations (CSS, Javascript, Lazy loading, etc.)
3. Shortpixel for image optimizations
4. WP Cloudflare Super Page Cache as page caching system on Cloudflare and bandwidth saver

If you use WP Rocket, install the free WP Rocket Disable Page Caching add on and enable the option to overwrite Cache-Control using web server rules (htaccess or Nginx).

= Something is not working, what can I do? =

Enable the debug mode and send me all the information you see at the bottom of the settings page so I can help you. Use the email you see on the sidebar.

= Can I bypass the cache using a filter? =

Yes you can. Example:

`function bypass_cache_custom( $cache_bypass ) {
    
    // Bypass cache on front page
    if( is_front_page() ) $cache_bypass = true;

    return $cache_bypass;

}

add_filter( 'swcfpc_cache_bypass', 'bypass_cache_custom', 1 );`


= Can I purge the cache programmatically? =

Yes you can. You can purge whole cache using the following code:

`global $sw_cloudflare_pagecache;

$error_msg = "";

if( $sw_cloudflare_pagecache->cloudflare_purge_cache( $error_msg ) ) {
    // Cache purged
}
else {
    // Cache not purged. Error on $error_msg
}`

Or purge cache by URLs using the following code:


`global $sw_cloudflare_pagecache;

$error_msg = "";
$urls = array("first url here", "second url here");

if( $sw_cloudflare_pagecache->cloudflare_purge_cache_urls( $urls, $error_msg ) ) {
    // Cache purged
}
else {
    // Cache not purged. Error on $error_msg
}`

= Can I setup this plugin with PHP constants? =

Yes you can define the following PHP constants

`SWCFPC_CACHE_BUSTER // Cache buster name. Default: swcfpc
SWCFPC_CF_API_SUBDOMAIN
SWCFPC_CF_API_ZONE_ID
SWCFPC_CF_API_KEY
SWCFPC_CF_API_EMAIL
SWCFPC_CF_API_TOKEN
SWCFPC_PRELOADER_MAX_POST_NUMBER // Max pages to preload. Default: 1000`


== Changelog ==

= Version 4.1.4 =
* Added an option to automatically purge the whole cache when WP Fastest Cache purges its own cache
* Added an option to automatically purge the whole cache when Hummingbird purges its own cache
* Move the menu inside Settings page

= Version 4.1.3 =
* Cloudflare has finally solved a bug that allows you to use access tokens with permissions limited to the domain being configured only.
* Added an option to remove purge options from toolbar
* Added an option to disable metaboxes from single pages and posts

= Version 4.1.2 =
* Added an option to automatically purge cache for WooCommerce product page and related categories when stock quantity changes
* Added an option to automatically purge the whole cache when LiteSpeed Cache purges its own cache

= Version 4.1.1 =
* Fix javascript error Uncaught TypeError: Cannot read property 'addEventListener' of null

= Version 4.1 =
* Fix ajax url for Wordpress multisite
* Fix other minor bugs

= Version 4.0.6 =
* Fixed error Call to undefined function wp_generate_password()

= Version 4.0.5 =
* Fix other minor bugs

= Version 4.0.4 =
* Fix bug (cache buster also for not logged in users). Thanks to Tim Marringa

= Version 4.0.3 =
* Show page actions only if page cache is enabled

= Version 4.0.2 =
* Fixing default page number for preloader

= Version 4.0.1 =
* Fast fix for page testing function

= Version 4.0 =
* Added pages to top-level menu
* New logs page
* Added ability to define some values (API Key, API Token, API Email, API Zone ID, API Subdomain, Cache buster) using PHP constants
* Added a cache preloader
* Added an option to strip response cookies from pages that should be cached
* Now the cache purging is doing via Ajax
* Improved the page cache testing system
* New UX
* Added an option to bypass the cache for POST requests
* Added an option to bypass the cache for requests with query variables (query string)
* Added metabox to exclude single page/post from the cache

= Version 3.8 =
* Added the ability to use the API tokens instead of the API keys to authenticate with Cloudflare
* Added in the admin toolbar the option to purge the cache for the current page/post only
* Added more debug details
* Added page/post action links to purge the cache for the selected page/post only

= Version 3.7.2 =
* Fixed a sentence for italian language

= Version 3.7.1 =
* Added option for automatically purge single post cache when a new comment is inserted into the database or when a comment is approved or deleted

= Version 3.7 =
* Added options for WP Rocket users
* Added options for W3 Total Cache users
* Added options for WP Super Cache users
* Improve some internal hooks

= Version 3.6.1 =
* Added options for WooCommerce

= Version 3.6 =
* Added Nginx support for "Overwrite the cache-control header" option

= Version 3.5 =
* Added Nginx support
* Italian translation

= Version 3.4 =
* Fixed notice Undefined index: HTTP_X_REQUESTED_WITH

= Older versions =
Version 1.5   - Added support for WooCommerce, filters and actions
Version 1.6   - Added support for scheduled posts, cronjobs, robots.txt and Yoast sitemaps
Version 1.7   - Little bugs fix
Version 1.7.1 - Fixed little incompatibilities due to swcfpc parameter
Version 1.7.2 - Added other cache exclusion options
Version 1.7.3 - Add support for AMP pages
Version 1.7.6 - Fixed little bugs
Version 1.7.8 - Added support for robots.txt and sitemaps generated by Yoast. Added a link to admin toolbar to purge cache fastly. Added custom header "Wp-cf-super-cache" for debug purposes
Version 1.8 - Solved some incompatibility with WP SES - Thanks to Davide Prevosto
Version 1.8.1 - Added support for other WooCommerce page types and AJAX requests
Version 1.8.4 - Fixed little bugs
Version 1.8.5 - Added support for subdomains
Version 1.8.7 - Prevent 304 response code
Version 2.0 - Database optimization and added support for browser cache-control max-age
Version 2.1 - Fixed warning on line 1200
Version 2.3 - Added support for wildcard URLs
Version 2.4 - Added support for pagination (thanks to Davide Prevosto)
Version 2.5 - Fixed little bugs and added support for Gutenberg editor
Version 2.6 - Auto-purge cache when edit posts/pages using Elementor and fix the warning on purge_cache_on_post_published
Version 2.7 - Fixed a little bug when calling purge_cache_on_post_published
Version 2.8 - Fixed the last warning
Version 3.0 - Improved the UX interface, added browser caching option and added support for htaccess so that it is possible to improve the coexistence of this plugin with other performance plugins.
Version 3.1 - Fixed PHP warning implode() for option Prevent the following urls to be cached
Version 3.2 - Improved cache-control flow via htaccess
Version 3.3 - Fixed missing checks in backend


== Upgrade Notice ==

= Version 3.6.1 =

* New update is available.


== Screenshots ==

1. This screen shot description corresponds to screenshot-1.jpg
Step 1 - Enter your Cloudflare's API Key and e-mail 
2. This screen shot description corresponds to screenshot-2.jpg
Step 2 - Select the domain
3. This screen shot description corresponds to screenshot-3.jpg
Step 3 - Enable the page Cache
