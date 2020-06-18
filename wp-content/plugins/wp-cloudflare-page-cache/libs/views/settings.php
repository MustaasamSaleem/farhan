<?php $switch_counter  = 0; ?>

<div class="wrap">

    <div id="swcfpc_main_content" class="width_sidebar">

        <h1><?php _e('WP Cloudflare Super Page Cache', 'wp-cloudflare-page-cache'); ?></h1>

        <?php if( strlen($error_msg) > 0 ): ?>

            <div class="notice is-dismissible notice-error"><p><?php echo sprintf( __("Error: %s", 'wp-cloudflare-page-cache'), $error_msg ); ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e("Hide this notice", 'wp-cloudflare-page-cache'); ?></span></button></div>

        <?php endif; ?>

        <?php if( !$wizard_active && strlen($success_msg) > 0 ): ?>

            <div class="notice is-dismissible notice-success"><p><?php echo $success_msg; ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e("Hide this notice", 'wp-cloudflare-page-cache'); ?></span></button></div>

        <?php endif; ?>

        <?php if( count($zone_id_list) == 0 ): ?>

            <div class="step">

                <div class="step_counter">
                    <div class="step_number step_active"><span>1</span></div>
                    <div class="step_number"><span>2</span></div>
                    <div class="step_number"><span>3</span></div>
                    <div class="clear"></div>
                </div>

                <div class="api_key_method <?php if( $this->main_instance->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) != SWCFPC_AUTH_MODE_API_KEY ) echo 'swcfpc_hide'; ?>">

                    <h2><?php echo __( 'Enter your Cloudflare\'s API key and e-mail', 'wp-cloudflare-page-cache' ); ?></h2>

                    <p><?php _e('You don\'t know how to do it? Follow these simple four steps:', 'wp-cloudflare-page-cache'); ?></p>

                    <ol>
                        <li><a href="https://dash.cloudflare.com/login" target="_blank"><?php _e('Log in to your Cloudflare account', 'wp-cloudflare-page-cache'); ?></a> <?php _e('and click on My Profile', 'wp-cloudflare-page-cache'); ?></li>
                        <li><?php _e('Click on API tokens, scroll to API Keys and click on View beside Global API Key', 'wp-cloudflare-page-cache'); ?></li>
                        <li><?php _e('Enter your Cloudflare login password and click on View', 'wp-cloudflare-page-cache'); ?></li>
                        <li><?php _e('Enter both API key and e-mail address into the form below and click on Update settings', 'wp-cloudflare-page-cache'); ?></li>
                    </ol>

                </div>

                <div class="api_token_method <?php if( $this->main_instance->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) != SWCFPC_AUTH_MODE_API_TOKEN ) echo 'swcfpc_hide'; ?>">

                    <h2><?php echo __( 'Enter your Cloudflare\'s API token', 'wp-cloudflare-page-cache' ); ?></h2>

                    <p><?php _e('You don\'t know how to do it? Follow these simple steps:', 'wp-cloudflare-page-cache'); ?></p>

                    <ol>
                        <li><a href="https://dash.cloudflare.com/login" target="_blank"><?php _e('Log in to your Cloudflare account', 'wp-cloudflare-page-cache'); ?></a> <?php _e('and click on My Profile', 'wp-cloudflare-page-cache'); ?></li>
                        <li><?php _e('Click on API tokens > Create Token > Custom Token > Get started', 'wp-cloudflare-page-cache'); ?></li>
                        <li><?php _e('Enter a Token name (example: token for example.com)', 'wp-cloudflare-page-cache'); ?></li>
                        <li><strong><?php _e('Permissions:', 'wp-cloudflare-page-cache'); ?></strong></li>
                        <ul>
                            <li>Account - Account Settings - Read</li>
                            <li>Zone - Cache Purge - Purge</li>
                            <li>Zone - Page Rules - Edit</li>
                            <li>Zone - Zone Settings - Edit</li>
                            <li>Zone - Zone - Edit</li>
                        </ul>

                        <li><strong><?php _e('Account resources:', 'wp-cloudflare-page-cache'); ?></strong></li>
                        <ul>
                            <li>Include - All accounts</li>
                        </ul>

                        <li><strong><?php _e('Zone resources:', 'wp-cloudflare-page-cache'); ?></strong></li>
                        <ul>
                            <li>Include - Specific zone - your domain name</li>
                        </ul>

                        <li><?php _e('Click on Continue to summary and then on Create token', 'wp-cloudflare-page-cache'); ?></li>
                        <li><?php _e('Enter the generated token into the form below and click on Update settings', 'wp-cloudflare-page-cache'); ?></li>
                    </ol>

                </div>

            </div>

        <?php endif; ?>

        <?php if( $this->main_instance->get_cloudflare_api_zone_id() == "" && count($zone_id_list) > 0 ): ?>

            <div class="step">

                <div class="step_counter">
                    <div class="step_number"><span>1</span></div>
                    <div class="step_number step_active"><span>2</span></div>
                    <div class="step_number"><span>3</span></div>
                    <div class="clear"></div>
                </div>

                <h2><?php echo __( 'Select the domain', 'wp-cloudflare-page-cache' ); ?></h2>

                <p style="text-align: center;"><?php _e('Select from the dropdown menu the domain for which you want to enable the cache', 'wp-cloudflare-page-cache'); ?></p>

            </div>

        <?php endif; ?>

        <?php if( $this->main_instance->get_cloudflare_api_zone_id() != "" ): ?>

            <?php if( ! $this->objects["cache_controller"]->is_cache_enabled() ): ?>

                <div class="step">

                    <div class="step_counter">
                        <div class="step_number"><span>1</span></div>
                        <div class="step_number"><span>2</span></div>
                        <div class="step_number step_active"><span>3</span></div>
                        <div class="clear"></div>
                    </div>

                    <h2><?php _e('Enable Page Caching', 'wp-cloudflare-page-cache'); ?></h2>

                    <p style="text-align: center;"><?php _e('Now you can configure and enable the page cache to speed up this website', 'wp-cloudflare-page-cache'); ?></p>

                    <form action="" method="post" id="swcfpc_form_enable_cache">
                        <p class="submit"><input type="submit" name="swcfpc_submit_enable_page_cache" class="button button-primary green_button" value="<?php _e('Enable Page Caching Now', 'wp-cloudflare-page-cache'); ?>"></p>
                    </form>

                </div>

            <?php else: ?>

                <div id="swcfpc_actions">

                    <h2><?php echo __( 'Cache Actions', 'wp-cloudflare-page-cache' ); ?></h2>

                    <form action="" method="post" id="swcfpc_form_disable_cache">
                        <p class="submit"><input type="submit" name="swcfpc_submit_disable_page_cache" class="button button-primary" value="<?php _e('Disable Page Cache', 'wp-cloudflare-page-cache'); ?>"></p>
                    </form>

                    <form action="" method="post" id="swcfpc_form_purge_cache">
                        <p class="submit"><input type="submit" name="swcfpc_submit_purge_cache" class="button button-secondary" value="<?php _e('Purge Cache', 'wp-cloudflare-page-cache'); ?>"></p>
                    </form>

                    <form action="" method="post" id="swcfpc_form_test_cache">
                        <p class="submit"><input type="submit" name="swcfpc_submit_test_cache" class="button button-secondary" value="<?php _e('Test Cache', 'wp-cloudflare-page-cache'); ?>"></p>
                    </form>

                    <form id="swcfpc_form_reset_all" action="" method="post" onsubmit="return confirm('<?php _e("Are you sure you want reset all?", 'wp-cloudflare-page-cache'); ?>');">
                        <p class="submit"><input type="submit" name="swcfpc_submit_reset_all" class="button button-secondary" value="<?php _e('Reset All', 'wp-cloudflare-page-cache'); ?>"></p>
                    </form>

                </div>

            <?php endif; ?>

        <?php endif; ?>

        <form method="post" action="">

            <div class="blocco_dati_header">
                <h3><?php echo __( 'Cloudflare General Settings', 'wp-cloudflare-page-cache' ); ?></h3>
            </div>

            <div class="blocco_dati">
                <div class="blocco_sinistra">
                    <label><?php _e('Authentication mode', 'wp-cloudflare-page-cache'); ?></label>
                    <div class="descrizione"><?php _e('Authentication mode to use to connect to your Cloudflare account.', 'wp-cloudflare-page-cache'); ?></div>
                </div>
                <div class="blocco_destra">
                    <select name="swcfpc_cf_auth_mode">
                        <option value="<?php echo SWCFPC_AUTH_MODE_API_TOKEN; ?>" <?php if( $this->main_instance->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) == SWCFPC_AUTH_MODE_API_TOKEN ) echo "selected"; ?>><?php _e('API Token', 'wp-cloudflare-page-cache'); ?></option>
                        <option value="<?php echo SWCFPC_AUTH_MODE_API_KEY; ?>" <?php if( $this->main_instance->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) == SWCFPC_AUTH_MODE_API_KEY ) echo "selected"; ?>><?php _e('API Key', 'wp-cloudflare-page-cache'); ?></option>
                    </select>
                </div>
                <div class="clear"></div>
            </div>

            <div class="blocco_dati api_key_method <?php if( $this->main_instance->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) != SWCFPC_AUTH_MODE_API_KEY ) echo 'swcfpc_hide'; ?>">
                <div class="blocco_sinistra">
                    <label><?php _e('Cloudflare e-mail', 'wp-cloudflare-page-cache'); ?></label>
                    <div class="descrizione"><?php _e('The email address you use to log in to Cloudflare.', 'wp-cloudflare-page-cache'); ?></div>
                </div>
                <div class="blocco_destra">
                    <input type="text" name="swcfpc_cf_email"  value="<?php echo $this->main_instance->get_cloudflare_api_email(); ?>" />
                </div>
                <div class="clear"></div>
            </div>

            <div class="blocco_dati api_key_method <?php if( $this->main_instance->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) != SWCFPC_AUTH_MODE_API_KEY ) echo 'swcfpc_hide'; ?>">
                <div class="blocco_sinistra">
                    <label><?php _e('Cloudflare API Key', 'wp-cloudflare-page-cache'); ?></label>
                    <div class="descrizione"><?php _e('The Global API Key extrapolated from your Cloudflare account.', 'wp-cloudflare-page-cache'); ?></div>
                </div>
                <div class="blocco_destra">
                    <input type="password" name="swcfpc_cf_apikey"  value="<?php echo $this->main_instance->get_cloudflare_api_key(); ?>" />
                </div>
                <div class="clear"></div>
            </div>

            <div class="blocco_dati api_token_method <?php if( $this->main_instance->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) != SWCFPC_AUTH_MODE_API_TOKEN ) echo 'swcfpc_hide'; ?>">
                <div class="blocco_sinistra">
                    <label><?php _e('Cloudflare API Token', 'wp-cloudflare-page-cache'); ?></label>
                    <div class="descrizione"><?php _e('The API Token extrapolated from your Cloudflare account.', 'wp-cloudflare-page-cache'); ?></div>
                </div>
                <div class="blocco_destra">
                    <input type="password" name="swcfpc_cf_apitoken"  value="<?php echo $this->main_instance->get_cloudflare_api_token(); ?>" />
                </div>
                <div class="clear"></div>
            </div>

            <div class="blocco_dati api_token_method <?php if( $this->main_instance->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) != SWCFPC_AUTH_MODE_API_TOKEN ) echo 'swcfpc_hide'; ?>">
                <div class="blocco_sinistra">
                    <label><?php _e('Cloudflare Domain Name', 'wp-cloudflare-page-cache'); ?></label>
                    <div class="descrizione"><?php _e('Select the domain for which you want to enable the cache and click on Update settings.', 'wp-cloudflare-page-cache'); ?></div>
                </div>
                <div class="blocco_destra">
                    <input type="text" name="swcfpc_cf_apitoken_domain"  value="<?php echo $this->main_instance->get_single_config("cf_apitoken_domain", ""); ?>" />
                </div>
                <div class="clear"></div>
            </div>

            <div class="blocco_dati">
                <div class="blocco_sinistra">
                    <label><?php _e('Log mode', 'wp-cloudflare-page-cache'); ?></label>
                    <div class="descrizione"><?php _e('If enabled, all communications between Cloudflare and WP Cloudflare Super Page Cache will be logged.', 'wp-cloudflare-page-cache'); ?></div>
                </div>
                <div class="blocco_destra">
                    <div class="switch-field">
                        <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_log_enabled" value="1" <?php if( $this->main_instance->get_single_config("log_enabled", 0) > 0 ) echo "checked";  ?>/>
                        <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Enabled", 'wp-cloudflare-page-cache'); ?></label>
                        <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_log_enabled" value="0" <?php if( $this->main_instance->get_single_config("log_enabled", 0) <= 0 ) echo "checked";  ?> />
                        <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("Disabled", 'wp-cloudflare-page-cache'); ?></label>
                    </div>
                </div>
                <div class="clear"></div>
            </div>

            <?php if( count($zone_id_list) > 0 ): ?>


                <?php if( $this->main_instance->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) == SWCFPC_AUTH_MODE_API_KEY ): ?>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Cloudflare Domain Name', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('Select the domain for which you want to enable the cache and click on Update settings.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">

                            <select name="swcfpc_cf_zoneid">

                                <option value=""><?php _e('Select a Domain Name', 'wp-cloudflare-page-cache'); ?></option>

                                <?php if( $domain_found ): ?>

                                    <option value="<?php echo $domain_zone_id; ?>" <?php if( $domain_zone_id == $this->main_instance->get_cloudflare_api_zone_id() ) echo "selected"; ?>><?php echo $current_domain; ?></option>

                                <?php else: foreach($zone_id_list as $zone_id_name => $zone_id): ?>

                                    <option value="<?php echo $zone_id; ?>" <?php if( $zone_id == $this->main_instance->get_cloudflare_api_zone_id() ) echo "selected"; ?>><?php echo $zone_id_name; ?></option>

                                <?php endforeach; endif; ?>

                            </select>

                        </div>
                        <div class="clear"></div>
                    </div>

                <?php else: ?>

                    <input type="hidden" name="swcfpc_cf_zoneid" value="<?php echo $this->main_instance->get_cloudflare_api_zone_id(); ?>" />

                <?php endif; ?>

                <div class="blocco_dati">
                    <div class="blocco_sinistra">
                        <label><?php _e('Subdomain', 'wp-cloudflare-page-cache'); ?></label>
                        <div class="descrizione"><?php _e('If you want to enable the cache for a subdomain of the selected domain, enter it here. For example, if you selected the domain example.com from the drop-down menu and you want to enable the cache for subdomain.example.com, enter subdomain.example.com here.', 'wp-cloudflare-page-cache'); ?></div>
                    </div>
                    <div class="blocco_destra">
                        <input type="text" name="swcfpc_cf_subdomain"  value="<?php echo $this->main_instance->get_cloudflare_api_subdomain(); ?>" placeholder="sub.example.com" />
                    </div>
                    <div class="clear"></div>
                </div>

                <?php if( $this->main_instance->get_cloudflare_api_zone_id() != "" ): ?>

                    <div class="blocco_dati_header">
                        <h3><?php echo __( 'Cache lifetime settings', 'wp-cloudflare-page-cache' ); ?></h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Cloudflare Cache-Control max-age', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('Don\'t touch if you don\'t know what is it. Must be grater than zero. Recommended 604800', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <input type="text" name="swcfpc_maxage"  value="<?php echo $this->main_instance->get_single_config("cf_maxage", ""); ?>" />
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Browser Cache-Control max-age', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('Don\'t touch if you don\'t know what is it. Must be grater than zero. Recommended a value between 60 and 600', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <input type="text" name="swcfpc_browser_maxage"  value="<?php echo $this->main_instance->get_single_config("cf_browser_maxage", ""); ?>" />
                        </div>
                        <div class="clear"></div>
                    </div>


                    <div class="blocco_dati_header">
                        <h3><?php echo __( 'Cache behavior settings', 'wp-cloudflare-page-cache' ); ?></h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Posts per page', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('Enter how many posts per page (or category) the theme shows to your users. It will be use to clean up the pagination on cache purge.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <input type="text" name="swcfpc_post_per_page"  value="<?php echo $this->main_instance->get_single_config("cf_post_per_page", ""); ?>" />
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Overwrite the cache-control header for Wordpress\'s pages using web server rules', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('This option is useful if you use WP Cloudflare Super Page Cache together with other performance plugins that could affect the Cloudflare cache with their cache-control headers. It works automatically if you are using Apache as web server or as backend web server.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">

                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_cache_control_htaccess" value="1" <?php if( $this->main_instance->get_single_config("cf_cache_control_htaccess", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_cache_control_htaccess" value="0" <?php if( $this->main_instance->get_single_config("cf_cache_control_htaccess", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>

                            <br/>
                            <div class="descrizione evidenziata"><?php _e('This option is not essential. In most cases this plugin works out of the box. If the page cache does not work after a considerable number of attempts, activate this option.', 'wp-cloudflare-page-cache'); ?></div>
                            <br/>

                            <?php if( $this->main_instance->is_litespeed_webserver() ): ?>
                                <div class="descrizione evidenziata"><?php _e('Seems you are using LiteSpeed Web Server. Due to its limitation in handling conditional response HTTP headers, you cannot use this option.', 'wp-cloudflare-page-cache'); ?></div>
                                <br/>
                            <?php endif; ?>

                            <div class="descrizione"><strong><?php _e('Read here if you use Apache (htaccess)', 'wp-cloudflare-page-cache'); ?></strong>: <?php _e('for overwriting to work, make sure that the rules added by WP Cloudflare Super Page Cache are placed at the bottom of the htaccess file. If they are present BEFORE other caching rules of other plugins, move them to the bottom manually.', 'wp-cloudflare-page-cache'); ?></div>
                            <br/>
                            <div class="descrizione"><strong><?php _e('Read here if you only use Nginx', 'wp-cloudflare-page-cache'); ?></strong>: <?php _e( 'it is not possible for WP Cloudflare Super Page Cache to automatically change the settings to allow this option to work immediately. For it to work, update these settings and then follow the instructions', 'wp-cloudflare-page-cache'); ?> <a href="<?php echo $nginx_instructions_page_url; ?>" target="_blank"><?php _e('on this page', 'wp-cloudflare-page-cache'); ?>.</a></div>


                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Strip response cookies on pages that should be cached', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('If the page is not cached due to response cookies and you are sure that these cookies are not essential for the website to function, enable this option.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">

                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_strip_cookies" value="1" <?php if( $this->main_instance->get_single_config("cf_strip_cookies", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_strip_cookies" value="0" <?php if( $this->main_instance->get_single_config("cf_strip_cookies", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>

                            <?php if( $this->main_instance->is_litespeed_webserver() ): ?>
                                <br/>
                                <div class="descrizione evidenziata"><?php _e('Seems you are using LiteSpeed Web Server. Due to its limitation in the management of conditional response HTTP headers, the removal of cookies will take place using only PHP, without the support of the web server through htaccess rules.', 'wp-cloudflare-page-cache'); ?></div>
                                <br/>
                            <?php endif; ?>

                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Automatically purge the Cloudflare cache on website changes (posts, pages, themes, attachments, etc..)', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('If enabled, WP Cloudflare Super Page Cache tries to purge the cache for related pages only.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_auto_purge" value="1" <?php if( $this->main_instance->get_single_config("cf_auto_purge", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_auto_purge" value="0" <?php if( $this->main_instance->get_single_config("cf_auto_purge", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Automatically purge the whole Cloudflare cache on website changes (posts, pages, themes, attachments, etc..)', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('If enabled, WP Cloudflare Super Page Cache will purge the whole Cloudflare cache.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_auto_purge_all" value="1" <?php if( $this->main_instance->get_single_config("cf_auto_purge_all", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_auto_purge_all" value="0" <?php if( $this->main_instance->get_single_config("cf_auto_purge_all", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Don\'t cache the following page types', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div><input type="checkbox" name="swcfpc_cf_bypass_404" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_404", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Page 404 (is_404)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_single_post" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_single_post", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Single Posts (is_single)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_pages" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_pages", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Pages (is_page)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_front_page" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_front_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Front Page (is_front_page)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_home" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_home", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Home (is_home)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_archives" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_archives", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Archives (is_archive)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_tags" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_tags", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Tags (is_tag)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_category" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_category", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Categories (is_category)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_feeds" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_feeds", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Feeds (is_feed)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_search_pages" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_search_pages", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Search Pages (is_search)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_author_pages" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_author_pages", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Author Pages (is_author)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_amp" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_amp", 0) > 0 ? "checked" : ""; ?> /> <?php _e('AMP Pages', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Prevent the following URIs to be cached', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('One URI per line. You can use the * for wildcard URLs.', 'wp-cloudflare-page-cache'); ?></div>
                            <div class="descrizione"><?php _e('Example', 'wp-cloudflare-page-cache'); ?>: /my-page<br/>/my-main-page/my-sub-page<br/>/my-main-page*</div>
                        </div>
                        <div class="blocco_destra">
                            <textarea name="swcfpc_cf_excluded_urls"><?php echo is_array( $this->main_instance->get_single_config("cf_excluded_urls", "") ) ? implode("\n", $this->main_instance->get_single_config("cf_excluded_urls", "") ) : ""; ?></textarea>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Prevent XML sitemaps to be cached', 'wp-cloudflare-page-cache'); ?></label>
                            <br/><br/>
                            <div class="descrizione"><strong><?php _e('If you only use Nginx', 'wp-cloudflare-page-cache'); ?></strong>: <?php _e( 'it is recommended to add the browser caching rules that you find', 'wp-cloudflare-page-cache'); ?> <a href="<?php echo $nginx_instructions_page_url; ?>" target="_blank"><?php _e('on this page', 'wp-cloudflare-page-cache'); ?></a> <?php _e('after saving these settings', 'wp-cloudflare-page-cache'); ?>.</div>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_bypass_sitemap" value="1" <?php if( $this->main_instance->get_single_config("cf_bypass_sitemap", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_bypass_sitemap" value="0" <?php if( $this->main_instance->get_single_config("cf_bypass_sitemap", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Prevent robots.txt to be cached', 'wp-cloudflare-page-cache'); ?></label>
                            <br/><br/>
                            <div class="descrizione"><strong><?php _e('If you only use Nginx', 'wp-cloudflare-page-cache'); ?></strong>: <?php _e( 'it is recommended to add the browser caching rules that you find', 'wp-cloudflare-page-cache'); ?> <a href="<?php echo $nginx_instructions_page_url; ?>" target="_blank"><?php _e('on this page', 'wp-cloudflare-page-cache'); ?></a> <?php _e('after saving these settings', 'wp-cloudflare-page-cache'); ?>.</div>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_bypass_file_robots" value="1" <?php if( $this->main_instance->get_single_config("cf_bypass_file_robots", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_bypass_file_robots" value="0" <?php if( $this->main_instance->get_single_config("cf_bypass_file_robots", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Bypass the cache for logged-in users', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_bypass_logged_in" value="1" <?php if( $this->main_instance->get_single_config("cf_bypass_logged_in", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_bypass_logged_in" value="0" <?php if( $this->main_instance->get_single_config("cf_bypass_logged_in", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Bypass the cache for AJAX requests', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_bypass_ajax" value="1" <?php if( $this->main_instance->get_single_config("cf_bypass_ajax", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_bypass_ajax" value="0" <?php if( $this->main_instance->get_single_config("cf_bypass_ajax", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Bypass the cache for POST requests', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_bypass_post" value="1" <?php if( $this->main_instance->get_single_config("cf_bypass_post", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_bypass_post" value="0" <?php if( $this->main_instance->get_single_config("cf_bypass_post", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Bypass the cache for requests with GET variables', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_bypass_query_var" value="1" <?php if( $this->main_instance->get_single_config("cf_bypass_query_var", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_bypass_query_var" value="0" <?php if( $this->main_instance->get_single_config("cf_bypass_query_var", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <!-- Preloader -->
                    <div class="blocco_dati_header">
                        <h3><?php echo __( 'Preloader', 'wp-cloudflare-page-cache' ); ?></h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Start preloader', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('Start preloading the pages of your website to speed up their inclusion in the Cloudflare cache. Make sure the cache is working first.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <button type="button" id="swcfpc_start_preloader" class="button button-primary"><?php _e('Start preloader', 'wp-cloudflare-page-cache'); ?></button>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <!-- Logs -->
                    <div class="blocco_dati_header">
                        <h3><?php echo __( 'Logs', 'wp-cloudflare-page-cache' ); ?></h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Logs expiration', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('Automatically delete logs older than X days.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">

                            <select name="swcfpc_log_expiration">
                                <option value="7" <?php if( $this->main_instance->get_single_config("log_expiration", 7) == 7 ) echo "selected"; ?>><?php _e('7 days', 'wp-cloudflare-page-cache'); ?></option>
                                <option value="15" <?php if( $this->main_instance->get_single_config("log_expiration", 7) == 15 ) echo "selected"; ?>><?php _e('15 days', 'wp-cloudflare-page-cache'); ?></option>
                                <option value="1" <?php if( $this->main_instance->get_single_config("log_expiration", 7) == 1 ) echo "selected"; ?>><?php _e('1 day', 'wp-cloudflare-page-cache'); ?></option>
                            </select>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Clean logs manually', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('Delete all the logs currently stored and optimize the log table.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <button type="button" id="swcfpc_clear_logs" class="button button-primary"><?php _e('Clear logs now', 'wp-cloudflare-page-cache'); ?></button>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Download logs', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('Generate a fresh download link to logs.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <button type="button" id="swcfpc_download_logs" class="button button-primary"><?php _e('Download log file', 'wp-cloudflare-page-cache'); ?></button>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <!-- Browser caching -->
                    <div class="blocco_dati_header">
                        <h3><?php echo __( 'Browser caching', 'wp-cloudflare-page-cache' ); ?></h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Add browser caching rules for assets', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('This option is useful if you want to use WP Cloudflare Super Page Cache to enable browser caching rules for assets such like images, CSS, scripts, etc. It works automatically if you use Apache as web server or as backend web server.', 'wp-cloudflare-page-cache'); ?></div>
                            <br/>
                            <div class="descrizione"><strong><?php _e('Read here if you only use Nginx', 'wp-cloudflare-page-cache'); ?></strong>: <?php _e( 'it is not possible for WP Cloudflare Super Page Cache to automatically change the settings to allow this option to work immediately. For it to work, update these settings and then follow the instructions', 'wp-cloudflare-page-cache'); ?> <a href="<?php echo $nginx_instructions_page_url; ?>" target="_blank"><?php _e('on this page', 'wp-cloudflare-page-cache'); ?>.</a></div>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_browser_caching_htaccess" value="1" <?php if( $this->main_instance->get_single_config("cf_browser_caching_htaccess", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_browser_caching_htaccess" value="0" <?php if( $this->main_instance->get_single_config("cf_browser_caching_htaccess", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Automatically purge single post cache when a new comment is inserted into the database or when a comment is approved or deleted', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_auto_purge_on_comments" value="1" <?php if( $this->main_instance->get_single_config("cf_auto_purge_on_comments", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_auto_purge_on_comments" value="0" <?php if( $this->main_instance->get_single_config("cf_auto_purge_on_comments", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <!-- WooCommerce Options -->
                    <div class="blocco_dati_header">
                        <h3>
                            <?php echo __( 'WooCommerce settings', 'wp-cloudflare-page-cache' ); ?>

                            <?php if( is_plugin_active( 'woocommerce/woocommerce.php' ) ): ?>
                                <span class="swcfpc_plugin_active"><?php _e('Active plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php else: ?>
                                <span class="swcfpc_plugin_inactive"><?php _e('Inactive plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Don\'t cache the following WooCommerce page types', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div><input type="checkbox" name="swcfpc_cf_bypass_woo_cart_page" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_woo_cart_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Cart (is_cart)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_woo_checkout_page" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_woo_checkout_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Checkout (is_checkout)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_woo_checkout_pay_page" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_woo_checkout_pay_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Checkout\'s pay page (is_checkout_pay_page)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_woo_product_page" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_woo_product_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Product (is_product)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_woo_shop_page" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_woo_shop_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Shop (is_shop)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_woo_product_tax_page" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_woo_product_tax_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Product taxonomy (is_product_taxonomy)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_woo_product_tag_page" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_woo_product_tag_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Product tag (is_product_tag)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_woo_product_cat_page" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_woo_product_cat_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Product category (is_product_category)', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_bypass_woo_pages" value="1" <?php echo $this->main_instance->get_single_config("cf_bypass_woo_pages", 0) > 0 ? "checked" : ""; ?> /> <?php _e('WooCommerce page (is_woocommerce)', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Automatically purge cache for product page and related categories when stock quantity changes', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_auto_purge_woo_product_page" value="1" <?php if( $this->main_instance->get_single_config("cf_auto_purge_woo_product_page", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_auto_purge_woo_product_page" value="0" <?php if( $this->main_instance->get_single_config("cf_auto_purge_woo_product_page", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <!-- W3TC Options -->
                    <div class="blocco_dati_header">
                        <h3>
                            <?php echo __( 'W3 Total Cache settings', 'wp-cloudflare-page-cache' ); ?>
                            <?php if( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) ): ?>
                                <span class="swcfpc_plugin_active"><?php _e('Active plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php else: ?>
                                <span class="swcfpc_plugin_inactive"><?php _e('Inactive plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Automatically purge the cache when', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_all" value="1" <?php echo $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_all", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs all caches', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                            <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_dbcache" value="1" <?php echo $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_dbcache", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs database cache', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_fragmentcache" value="1" <?php echo $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_fragmentcache", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs fragment cache', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_objectcache" value="1" <?php echo $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_objectcache", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs object cache', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_posts" value="1" <?php echo $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_posts", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs posts cache', 'wp-cloudflare-page-cache'); ?></div>
                            <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_minfy" value="1" <?php echo $this->main_instance->get_single_config("cf_w3tc_purge_on_flush_minfy", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs minify cache', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <!-- LiteSpeed Options -->
                    <div class="blocco_dati_header">
                        <h3>
                            <?php echo __( 'LiteSpeed Cache settings', 'wp-cloudflare-page-cache' ); ?>
                            <?php if( is_plugin_active( 'litespeed-cache/litespeed-cache.php' ) ): ?>
                                <span class="swcfpc_plugin_active"><?php _e('Active plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php else: ?>
                                <span class="swcfpc_plugin_inactive"><?php _e('Inactive plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Automatically purge the cache when LiteSpeed Cache flushs all caches', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_litespeed_purge_on_cache_flush" value="1" <?php if( $this->main_instance->get_single_config("cf_litespeed_purge_on_cache_flush", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_litespeed_purge_on_cache_flush" value="0" <?php if( $this->main_instance->get_single_config("cf_litespeed_purge_on_cache_flush", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <!-- WP Fastest Cache Options -->
                    <div class="blocco_dati_header">
                        <h3>
                            <?php echo __( 'WP Fastest Cache settings', 'wp-cloudflare-page-cache' ); ?>
                            <?php if( is_plugin_active( 'wp-fastest-cache/wpFastestCache.php' ) ): ?>
                                <span class="swcfpc_plugin_active"><?php _e('Active plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php else: ?>
                                <span class="swcfpc_plugin_inactive"><?php _e('Inactive plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Automatically purge the cache when WP Fastest Cache flushs all caches', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_wp_fastest_cache_purge_on_cache_flush" value="1" <?php if( $this->main_instance->get_single_config("cf_wp_fastest_cache_purge_on_cache_flush", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_wp_fastest_cache_purge_on_cache_flush" value="0" <?php if( $this->main_instance->get_single_config("cf_wp_fastest_cache_purge_on_cache_flush", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <!-- Hummingbird Options -->
                    <div class="blocco_dati_header">
                        <h3>
                            <?php echo __( 'Hummingbird settings', 'wp-cloudflare-page-cache' ); ?>
                            <?php if( is_plugin_active( 'hummingbird-performance/wp-hummingbird.php' ) ): ?>
                                <span class="swcfpc_plugin_active"><?php _e('Active plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php else: ?>
                                <span class="swcfpc_plugin_inactive"><?php _e('Inactive plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Automatically purge the cache when Hummingbird flushs page cache', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_hummingbird_purge_on_cache_flush" value="1" <?php if( $this->main_instance->get_single_config("cf_hummingbird_purge_on_cache_flush", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_hummingbird_purge_on_cache_flush" value="0" <?php if( $this->main_instance->get_single_config("cf_hummingbird_purge_on_cache_flush", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <!-- WP Rocket Options -->
                    <div class="blocco_dati_header">
                        <h3>
                            <?php echo __( 'WP Rocket settings', 'wp-cloudflare-page-cache' ); ?>
                            <?php if( is_plugin_active( 'wp-rocket/wp-rocket.php' ) ): ?>
                                <span class="swcfpc_plugin_active"><?php _e('Active plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php else: ?>
                                <span class="swcfpc_plugin_inactive"><?php _e('Inactive plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Automatically purge the cache when', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div><input type="checkbox" name="swcfpc_cf_wp_rocket_purge_on_post_flush" value="1" <?php echo $this->main_instance->get_single_config("cf_wp_rocket_purge_on_post_flush", 0) > 0 ? "checked" : ""; ?> /> <?php _e('WP Rocket flushs all caches', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                            <div><input type="checkbox" name="swcfpc_cf_wp_rocket_purge_on_domain_flush" value="1" <?php echo $this->main_instance->get_single_config("cf_wp_rocket_purge_on_domain_flush", 0) > 0 ? "checked" : ""; ?> /> <?php _e('WP Rocket flushs single post cache', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <!-- WP Super Cache Options -->
                    <div class="blocco_dati_header">
                        <h3>
                            <?php echo __( 'WP Super Cache settings', 'wp-cloudflare-page-cache' ); ?>
                            <?php if( is_plugin_active( 'wp-super-cache/wp-cache.php' ) ): ?>
                                <span class="swcfpc_plugin_active"><?php _e('Active plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php else: ?>
                                <span class="swcfpc_plugin_inactive"><?php _e('Inactive plugin', 'wp-cloudflare-page-cache'); ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>
                
                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Automatically purge the cache when', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div><input type="checkbox" name="swcfpc_cf_wp_super_cache_on_cache_flush" value="1" <?php echo $this->main_instance->get_single_config("cf_wp_super_cache_on_cache_flush", 0) > 0 ? "checked" : ""; ?> /> <?php _e('WP Super Cache flushs all caches', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                        </div>
                        <div class="clear"></div>
                    </div>


                    <div class="blocco_dati_header">
                        <h3><?php echo __( 'Other settings', 'wp-cloudflare-page-cache' ); ?></h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Purge the whole Cloudflare cache with a Cronjob', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <p><?php _e('If you want purge the whole Cloudflare cache at specific intervals decided by you, you can create a cronjob that hits the following URL', 'wp-cloudflare-page-cache'); ?>:</p>
                            <p><b><?php echo $cronjob_url; ?></b></p>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Purge cache URL secret key', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('Secret key to use to purge the whole Cloudflare cache via URL. Don\'t touch if you don\'t know how to use it.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <input type="text" name="swcfpc_cf_purge_url_secret_key"  value="<?php echo $this->main_instance->get_single_config("cf_purge_url_secret_key", wp_generate_password(20, false, false)); ?>" />
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Remove purge option from toolbar', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_remove_purge_option_toolbar" value="1" <?php if( $this->main_instance->get_single_config("cf_remove_purge_option_toolbar", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_remove_purge_option_toolbar" value="0" <?php if( $this->main_instance->get_single_config("cf_remove_purge_option_toolbar", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Disable metaboxes on single pages and posts', 'wp-cloudflare-page-cache'); ?></label>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_disable_single_metabox" value="1" <?php if( $this->main_instance->get_single_config("cf_disable_single_metabox", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_disable_single_metabox" value="0" <?php if( $this->main_instance->get_single_config("cf_disable_single_metabox", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>

                <?php endif; ?>

            <?php endif; ?>

            <p class="submit"><input type="submit" name="swcfpc_submit_general" class="button button-primary" value="<?php _e('Update settings', 'wp-cloudflare-page-cache'); ?>"></p>

        </form>

    </div>

    <?php require_once SWCFPC_PLUGIN_PATH . 'libs/views/sidebar.php'; ?>

</div>