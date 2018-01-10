<?php

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

delete_option('wp_link_list_installed');
delete_option('wpll-setting');
