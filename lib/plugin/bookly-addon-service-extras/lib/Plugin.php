<?php
namespace BooklyServiceExtras\Lib;

/**
 * Class Plugin
 * @package BooklyServiceExtras\Lib
 */
abstract class Plugin extends \Bookly\Lib\Base\Plugin
{
    protected static $prefix;
    protected static $title;
    protected static $version;
    protected static $slug;
    protected static $directory;
    protected static $main_file;
    protected static $basename;
    protected static $text_domain;
    protected static $root_namespace;

    /**
     * Register hooks.
     */
    public static function registerHooks()
    {
        parent::registerHooks();

        /** @var \BooklyServiceExtras\Lib\Actions $actions */
        /** @var \BooklyServiceExtras\Lib\Filters $filters */
        $actions = __NAMESPACE__ . '\Actions';
        $filters = __NAMESPACE__ . '\Filters';

        // Register Service Extras Add-on filters.
        add_filter( 'bookly_extras_find_all',              array( $filters, 'findAll' ), 10, 1 );
        add_filter( 'bookly_extras_find_by_ids',           array( $filters, 'findByIds' ), 10, 2 );
        add_filter( 'bookly_extras_find_by_service_id',    array( $filters, 'findByServiceId' ), 10, 2 );
        add_filter( 'bookly_extras_get_total_duration',    array( $filters, 'getTotalDuration' ), 10, 2 );
        add_filter( 'bookly_extras_render_booking_step',   array( $filters, 'renderBookingStep' ), 10, 7 );

        // Add handlers to Bookly filters.
        add_filter( 'bookly_appointment_data',             array( $filters, 'appointmentData' ), 10, 2 );
        add_filter( 'bookly_prepare_cart_item_info_text',  array( $filters, 'prepareCartItemInfoText' ), 10, 2 );
        add_filter( 'bookly_prepare_chain_item_info_text', array( $filters, 'prepareChainItemInfoText' ), 10, 2 );
        add_filter( 'bookly_prepare_info_text_code',       array( $filters, 'prepareInfoTextCode' ), 10, 2 );
        add_filter( 'bookly_prepare_notification_codes',   array( $filters, 'prepareNotificationCodes' ), 10, 2 );
        add_filter( 'bookly_replace_notification_codes',   array( $filters, 'replaceNotificationCodes' ), 10, 2 );

        if ( is_admin() ) {
            // Register Service Extras Add-on actions.
            add_action( 'bookly_extras_render_appearance_tab', array( $actions, 'renderAppearanceTab' ), 10, 1 );
            add_action( 'bookly_extras_reorder',           array( $actions, 'reorder' ), 10, 1 );

            // Add handlers to Bookly actions & filters.
            add_action( 'bookly_render_after_service_list', array( $actions, 'renderAfterServiceList' ), 10, 0 );
            add_action( 'bookly_render_settings_form',     array( $actions, 'renderSettingsForm' ), 10, 0 );
            add_action( 'bookly_render_settings_menu',     array( $actions, 'renderSettingsMenu' ), 10, 0 );
            add_action( 'bookly_update_service',           array( $actions, 'updateService' ), 10, 2 );

            add_filter( 'bookly_appearance_short_codes',   array( $filters, 'appearanceShortCodes' ), 10, 1 );
            add_filter( 'bookly_calendar_appointment_description', array( $filters, 'calendarAppointmentDescription' ), 11, 2 );
            add_filter( 'bookly_notification_short_codes', array( $filters, 'notificationShortCodes' ), 10, 1 );
            add_filter( 'bookly_save_settings',            array( $filters, 'saveSettings' ), 10, 3 );
            add_filter( 'bookly_tables',                   array( $filters, 'pluginTables' ), 10, 1 );
            add_filter( 'bookly_woocommerce_short_codes',  array( $filters, 'notificationShortCodes' ), 10, 1 );

            $actions::registerWpActions( 'wp_ajax_bookly_service_extras_' );
        }
    }

}