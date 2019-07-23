<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
Plugin Name: Bookly Service Extras (Add-on)
Plugin URI: http://booking-wp-plugin.com
Description: Bookly Service Extras introduces new booking step in Bookly. At this step your clients can choose extra items to be added to selected service.
Version: 1.8
Author: Ladela Interactive
Author URI: http://booking-wp-plugin.com
Text Domain: bookly-service-extras
Domain Path: /languages
License: Commercial
*/

include 'autoload.php';

if ( class_exists( '\\Bookly\\Lib\\Plugin' ) && version_compare( Bookly\Lib\Plugin::getVersion(), '11.0', '>=' ) ) {
    BooklyServiceExtras\Lib\Plugin::run();
} else {
    add_action( 'init', function () {
        if ( current_user_can( 'activate_plugins' ) ) {
            add_action( 'admin_init', function () {
                deactivate_plugins( 'bookly-addon-service-extras/main.php', false, is_network_admin() );
            } );
            add_action( is_network_admin() ? 'network_admin_notices' : 'admin_notices', function () {
                printf( '<div class="updated"><h3>Bookly Service Extras (Add-on)</h3><p>The plugin has been <strong>deactivated</strong>.</p><p><strong>Bookly v%s</strong> is required.</p></div>',
                    '11.0'
                );
            } );
            unset ( $_GET['activate'], $_GET['activate-multi'] );
        }
    } );
}