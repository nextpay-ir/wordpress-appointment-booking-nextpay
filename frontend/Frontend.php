<?php
namespace Bookly\Frontend;

use Bookly\Lib;

/**
 * Class Frontend
 * @package Bookly\Frontend
 */
class Frontend
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action( 'wp_loaded', array( $this, 'init' ) );
        add_action( get_option( 'bookly_gen_link_assets_method' ) == 'enqueue' ? 'wp_enqueue_scripts' : 'wp_loaded', array( $this, 'linkAssets' ) );

        // Init controllers.
        $this->bookingController         = Modules\Booking\Controller::getInstance();
        $this->customerProfileController = Modules\CustomerProfile\Controller::getInstance();
        $this->wooCommerceController     = Modules\WooCommerce\Controller::getInstance();
        if ( Lib\Config::paymentTypeEnabled( Lib\Entities\Payment::TYPE_MOLLIE ) ) {
            $this->mollieController = Modules\Mollie\Controller::getInstance();
        }
        if ( Lib\Config::paymentTypeEnabled( Lib\Entities\Payment::TYPE_PAYPAL ) ) {
            $this->paypalController = Modules\Paypal\Controller::getInstance();
        }
		if ( Lib\Config::paymentTypeEnabled( Lib\Entities\Payment::TYPE_NEXTPAY ) ) {
            $this->nextpayController = Modules\Nextpay\Controller::getInstance();
        }
		if ( Lib\Config::paymentTypeEnabled( Lib\Entities\Payment::TYPE_ZARIN ) ) {
            $this->zarinController = Modules\Zarinpal\Controller::getInstance();
        }
        if ( Lib\Config::paymentTypeEnabled( Lib\Entities\Payment::TYPE_MELLAT ) ) {
            $this->mellatController = Modules\Mellat\Controller::getInstance();
        }
        if ( Lib\Config::paymentTypeEnabled( Lib\Entities\Payment::TYPE_PAYSON ) ) {
            $this->paysonController = Modules\Payson\Controller::getInstance();
        }
        if ( Lib\Config::paymentTypeEnabled( Lib\Entities\Payment::TYPE_PAYULATAM ) ) {
            $this->payulatamController = Modules\PayuLatam\Controller::getInstance();
        }
        if ( Lib\Config::paymentTypeEnabled( Lib\Entities\Payment::TYPE_2CHECKOUT ) ) {
            $this->twocheckoutController = Modules\TwoCheckout\Controller::getInstance();
        }
        // Register shortcodes.
        add_shortcode( 'bookly-form', array( $this->bookingController, 'renderShortCode' ) );
        /** @deprecated [ap-booking] */
        add_shortcode( 'ap-booking', array( $this->bookingController, 'renderShortCode' ) );
        add_shortcode( 'bookly-appointments-list', array( $this->customerProfileController, 'renderShortCode' ) );
    }

    /**
     * Link assets.
     */
    public function linkAssets()
    {
        /** @var \WP_Locale $wp_locale */
        global $wp_locale;

        $link_style  = get_option( 'bookly_gen_link_assets_method' ) == 'enqueue' ? 'wp_enqueue_style'  : 'wp_register_style';
        $link_script = get_option( 'bookly_gen_link_assets_method' ) == 'enqueue' ? 'wp_enqueue_script' : 'wp_register_script';
        $version     = Lib\Plugin::getVersion();
        $resources   = plugins_url( 'resources', __FILE__ );

        // Assets for [bookly-form].
        if ( get_option( 'bookly_cst_phone_default_country' ) != 'disabled' ) {
            call_user_func( $link_style, 'bookly-intlTelInput', $resources . '/css/intlTelInput.css', array(), $version );
        }
        call_user_func( $link_style, 'bookly-ladda-min',    $resources . '/css/ladda.min.css',       array(), $version );
        //call_user_func( $link_style, 'bookly-picker',       $resources . '/css/picker.classic.css',  array(), $version );
        call_user_func( $link_style, 'bookly-picker-date',  $resources . '/css/picker.classic.date.css', array(), $version );
		call_user_func( $link_style, 'datepicker',  $resources . '/css/jquery-ui-1.8.14.css', array(), $version );
        call_user_func( $link_style, 'bookly-main',         $resources . '/css/bookly-main.css',     get_option( 'bookly_cst_phone_default_country' ) != 'disabled' ? array( 'bookly-intlTelInput', 'bookly-picker-date' ) : array( 'bookly-picker-date' ), $version );
        if ( is_rtl() ) {
            call_user_func( $link_style, 'bookly-rtl',      $resources . '/css/bookly-rtl.css',      array(), $version );
        }
        call_user_func( $link_script, 'bookly-spin',        $resources . '/js/spin.min.js',          array(), $version );
        call_user_func( $link_script, 'bookly-ladda',       $resources . '/js/ladda.min.js',         array( 'bookly-spin' ), $version );
        call_user_func( $link_script, 'bookly-hammer',      $resources . '/js/hammer.min.js',        array( 'jquery' ), $version );
        call_user_func( $link_script, 'bookly-jq-hammer',   $resources . '/js/jquery.hammer.min.js', array( 'jquery' ), $version );
        //call_user_func( $link_script, 'bookly-picker',      $resources . '/js/picker.js',            array( 'jquery' ), $version );
        //call_user_func( $link_script, 'bookly-picker-date', $resources . '/js/picker.date.js',       array( 'bookly-picker' ), $version );
        if ( get_option( 'bookly_cst_phone_default_country' ) != 'disabled' ) {
            call_user_func( $link_script, 'bookly-intlTelInput', $resources . '/js/intlTelInput.min.js', array( 'jquery' ), $version );
        }
		call_user_func( $link_script, 'datepicker',             $resources . '/js/jquery.ui.datepicker-cc.all.min.js',            array( 'jquery'), $version );
        call_user_func( $link_script, 'bookly',             $resources . '/js/bookly.js',            array( 'bookly-ladda', 'bookly-hammer', 'datepicker' ), $version );

        // Assets for [bookly-appointments-list].
        call_user_func( $link_style,  'bookly-customer-profile', plugins_url( 'modules/customer_profile/resources/css/customer_profile.css', __FILE__ ), array(), $version );
        call_user_func( $link_script, 'bookly-customer-profile', plugins_url( 'modules/customer_profile/resources/js/customer_profile.js', __FILE__ ), array( 'jquery' ), $version );

        Lib\Proxy\Shared::enqueueBookingAssets();

        wp_localize_script( 'bookly', 'BooklyL10n', array(
            'csrf_token' => Lib\Utils\Common::getCsrfToken(),
            'today'      => __( 'Today', 'bookly' ),
            'months'     => array_values( $wp_locale->month ),
            'days'       => array_values( $wp_locale->weekday ),
            'daysShort'  => array_values( $wp_locale->weekday_abbrev ),
            'nextMonth'  => __( 'Next month', 'bookly' ),
            'prevMonth'  => __( 'Previous month', 'bookly' ),
            'show_more'  => __( 'Show more', 'bookly' ),
        ) );

        // Android animation.
        if ( array_key_exists( 'HTTP_USER_AGENT', $_SERVER ) && stripos( strtolower( $_SERVER['HTTP_USER_AGENT'] ), 'android' ) !== false ) {
            call_user_func( $link_script, 'bookly-jquery-animate-enhanced', $resources . '/js/jquery.animate-enhanced.min.js', array( 'jquery' ), Lib\Plugin::getVersion() );
        }
    }

    /**
     * Init.
     */
    public function init()
    {
        if ( ! session_id() ) {
            @session_start();
        }

        // Payments ( PayPal Express Checkout, 2Checkout, PayU Latam, Payson, Mollie )
        if ( isset( $_REQUEST['bookly_action'] ) ) {
            // Disable caching.
            Lib\Utils\Common::noCache();

            switch ( $_REQUEST['bookly_action'] ) {
                // PayPal Express Checkout.
                case 'paypal-ec-init':
                    $this->paypalController->ecInit();
                    break;
                case 'paypal-ec-return':
                    $this->paypalController->ecReturn();
                    break;
                case 'paypal-ec-cancel':
                    $this->paypalController->ecCancel();
                    break;
                case 'paypal-ec-error':
                    $this->paypalController->ecError();
                    break;
                // NextPay.
                case 'nextpay-ec-init':
                    $this->nextpayController->ecInit();
                    break;
                case 'nextpay-ec-return':
                    $this->nextpayController->ecReturn();
                    break;
                case 'nextpay-ec-cancel':
                    $this->nextpayController->ecCancel();
                    break;
                case 'nextpay-ec-error':
                    $this->nextpayController->ecError();
                    break;
				// ZarinPal.
                case 'zarin-ec-init':
                    $this->zarinController->ecInit();
                    break;
                case 'zarin-ec-return':
                    $this->zarinController->ecReturn();
                    break;
                case 'zarin-ec-cancel':
                    $this->zarinController->ecCancel();
                    break;
                case 'zarin-ec-error':
                    $this->zarinController->ecError();
                    break;
                // Mellat.
                case 'mellat-ec-init':
                    $this->mellatController->ecInit();
                    break;
                case 'mellat-ec-return':
                    $this->mellatController->ecReturn();
                    break;
                case 'mellat-ec-cancel':
                    $this->mellatController->ecCancel();
                    break;
                case 'mellat-ec-error':
                    $this->mellatController->ecError();
                    break;
                // 2Checkout.
                case '2checkout-approved':
                    $this->twocheckoutController->approved();
                    break;
                case '2checkout-error':
                    $this->twocheckoutController->error();
                    break;
                // PayU Latam.
                case 'payu_latam-checkout':
                    $this->payulatamController->checkout();
                    break;
                case 'payu_latam-ipn':
                    Lib\Payment\PayuLatam::ipn();
                    break;
                case 'payu_latam-error':
                    $this->payulatamController->error();
                    break;
                // Payson.
                case 'payson-checkout':
                    $this->paysonController->checkout();
                    break;
                case 'payson-ipn':
                    Lib\Payment\Payson::ipn();
                    break;
                case 'payson-cancel':
                    $this->paysonController->cancel();
                    break;
                case 'payson-response':
                    $this->paysonController->response();
                    break;
                case 'payson-error':
                    $this->paysonController->error();
                    break;
                // Mollie.
                case 'mollie-checkout':
                    $this->mollieController->checkout();
                    break;
                case 'mollie-response':
                    $this->mollieController->response();
                    break;
                case 'mollie-ipn':
                    Lib\Payment\Mollie::ipn();
                    break;
                default:
                    Lib\Proxy\Shared::handleRequestAction( $_REQUEST['bookly_action'] );
            }
        }
    }

}
