<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
Plugin Name: افزونه نوبت دهی
Plugin URI: http://proje724.ir/?p=3572
Description: افزونه نوبت دهی وردپرس یک سیستم رزرواسیون کامل برای تمامی سیستم های در حال انتظار می باشد که توسط این افزونه می توانید به سادگی محیط را از حال سنتی به مکانیزه با مدیریت بالا ارتقاع دهید.
Version: 14.1
Author: سایت پروژه 724
Author URI: http://www.proje724.ir
Text Domain: bookly
Domain Path: /languages
License: Commercial
*/

if ( version_compare( PHP_VERSION, '5.3.7', '<' ) ) {
    add_action( is_network_admin() ? 'network_admin_notices' : 'admin_notices', create_function( '', 'echo \'<div class="updated"><h3>Bookly</h3><p>To install the plugin - <strong>PHP 5.3.7</strong> or higher is required.</p></div>\';' ) );
} else {
    include_once __DIR__ . '/autoload.php';

    // Fix possible errors (appearing if "Nextgen Gallery" Plugin is installed) when Bookly is being updated.
    add_filter( 'http_request_args', create_function( '$args', '$args[\'reject_unsafe_urls\'] = false; return $args;' ) );

    call_user_func( array( '\Bookly\Lib\Plugin', 'run' ) );
    $app = is_admin() ? '\Bookly\Backend\Backend' : '\Bookly\Frontend\Frontend';
    new $app();
}