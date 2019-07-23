<?php
namespace Bookly\Lib;

/**
 * Class UserBookingData
 * @package Bookly\Frontend\Modules\Booking\Lib
 */
class UserBookingData
{
    private $form_id = null;

    /**
     * Data provided by user at booking steps
     * and stored in PHP session.
     * @var array
     */
    private $data = array(
        // Step 0
        'time_zone'        => null,
        'time_zone_offset' => null,
        // Step service
        'date_from'        => null,
        'days'             => null,
        'time_from'        => null,
        'time_to'          => null,
        // Step time
        'slots'            => array(),
        // Step details
        'name'             => null,
        'email'            => null,
        'phone'            => null,
        // Step payment
        'coupon'           => null,
        // Cart item keys being edited
        'edit_cart_keys'   => array(),
        'repeated'         => 0,
        'repeat_data'      => array(),
    );

    /**
     * @var Cart
     */
    public $cart = null;

    /**
     * @var Chain
     */
    public $chain = null;

    /**
     * @var Entities\Coupon|null
     */
    private $coupon = null;

    /**
     * @var array
     */
    private $booking_numbers = array();

    /**
     * @var integer|null
     */
    private $payment_id = null;

    /**
     * Constructor.
     *
     * @param $form_id
     */
    public function __construct( $form_id )
    {
        $this->form_id = $form_id;
        $this->cart    = new Cart( $this );
        $this->chain   = new Chain();

        // If logged in then set name, email and if existing customer then also phone.
        $current_user = wp_get_current_user();
        if ( $current_user && $current_user->ID ) {
            $customer = new Entities\Customer();
            if ( $customer->loadBy( array( 'wp_user_id' => $current_user->ID ) ) ) {
                $this->set( 'name',  $customer->get( 'name' ) );
                $this->set( 'email', $customer->get( 'email' ) );
                $this->set( 'phone', $customer->get( 'phone' ) );
            } else {
                $this->set( 'name',  $current_user->display_name );
                $this->set( 'email', $current_user->user_email );
            }
        } elseif ( get_option( 'bookly_cst_remember_in_cookie' ) && isset( $_COOKIE['bookly-cst-name'] ) ) {
            $this->set( 'name',  $_COOKIE['bookly-cst-name'] );
            $this->set( 'email', $_COOKIE['bookly-cst-email'] );
            $this->set( 'phone', $_COOKIE['bookly-cst-phone'] );
        }

        // Register destructor (should work in cases when regular __destruct() does not work).
        register_shutdown_function( array( $this, 'destruct' ) );
    }

    public function resetChain()
    {
        $this->chain->clear();
        $this->chain->add( new ChainItem() );

        // Set up default parameters.
        $prior_time = Config::getMinimumTimePriorBooking();
        $this->set( 'date_from', date( 'Y-m-d', current_time( 'timestamp' ) + $prior_time ) );
        $times = Entities\StaffScheduleItem::query( 'ss' )
            ->select( 'SUBSTRING_INDEX(MIN(ss.start_time), ":", 2) AS min_end_time,
                SUBSTRING_INDEX(MAX(ss.end_time), ":", 2) AS max_end_time' )
            ->leftJoin( 'Staff', 's', 's.id = ss.staff_id' )
            ->whereNot( 'start_time', null )
            // Only for visible staff get working hours.
            ->whereNot( 's.visibility', 'private' )
            ->fetchRow();
        $times = Proxy\Shared::adjustMinAndMaxTimes( $times );
        $this->set( 'time_from',      $times['min_end_time'] );
        $this->set( 'time_to',        $times['max_end_time'] );
        $this->set( 'slots',          array() );
        $this->set( 'edit_cart_keys', array() );
        $this->set( 'repeated',       0 );
        $this->set( 'repeat_data',    array() );
    }

    /**
     * Destructor used in register_shutdown_function.
     */
    public function destruct()
    {
        Session::setFormVar( $this->form_id, 'data',            $this->data );
        Session::setFormVar( $this->form_id, 'cart',            $this->cart->getItemsData() );
        Session::setFormVar( $this->form_id, 'chain',           $this->chain->getItemsData() );
        Session::setFormVar( $this->form_id, 'booking_numbers', $this->booking_numbers );
        Session::setFormVar( $this->form_id, 'payment_id',      $this->payment_id );
        Session::setFormVar( $this->form_id, 'last_touched',    time() );
    }

    /**
     * Set data parameter.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function set( $name, $value )
    {
        $this->data[ $name ] = $value;
    }

    /**
     * Get data parameter.
     *
     * @param string $name
     * @return mixed
     */
    public function get( $name )
    {
        if ( array_key_exists( $name, $this->data ) ) {
            return $this->data[ $name ];
        }

        return false;
    }

    /**
     * Load data from session.
     *
     * @return bool
     */
    public function load()
    {
        $data = Session::getFormVar( $this->form_id, 'data' );
        if ( $data !== null ) {
            // Restore data.
            $this->data = $data;
            $this->chain->setItemsData( Session::getFormVar( $this->form_id, 'chain' ) );
            $this->cart->setItemsData( Session::getFormVar( $this->form_id, 'cart' ) );
            $this->booking_numbers = Session::getFormVar( $this->form_id, 'booking_numbers' );
            $this->payment_id = Session::getFormVar( $this->form_id, 'payment_id' );
            // Client time zone.
            if ( Config::useClientTimeZone() ) {
                $this->applyTimeZone();
            }

            return true;
        }

        return false;
    }

    /**
     * Partially update data in session.
     *
     * @param array $data
     */
    public function fillData( array $data )
    {
        foreach ( $data as $name => $value ) {
            if ( array_key_exists( $name, $this->data ) ) {
                /** Fill array @see UserBookingData::$data */
                $this->set( $name, $value );
            } elseif ( $name == 'chain' ) {
                $chain_items = $this->chain->getItems();
                $this->chain->clear();
                foreach ( $value as $key => $_data ) {
                    $item = isset ( $chain_items[ $key ] ) ? $chain_items[ $key ] : new ChainItem();
                    $this->chain->add( $item );
                    foreach ( $_data as $_name => $_value ) {
                        $item->set( $_name, $_value );
                    }
                }
            } elseif ( $name == 'cart' ) {
                foreach ( $value as $key => $_data ) {
                    $item = $this->cart->get( $key );
                    foreach ( $_data as $_name => $_value ) {
                        $item->set( $_name, $_value );
                    }
                }
            } elseif ($name === 'repeat') {
                $this->set( 'repeated', $value );
            } elseif ($name === 'unrepeat') {
                $this->set( 'repeated',    0 );
                $this->set( 'repeat_data', array() );
            }
        }
    }

    /**
     * Set chain from given cart item.
     *
     * @param integer $cart_key
     */
    public function setChainFromCartItem( $cart_key )
    {
        $cart_item = $this->cart->get( $cart_key );
        $this->set( 'date_from', $cart_item->get( 'date_from' ) );
        $this->set( 'days',      $cart_item->get( 'days' ) );
        $this->set( 'time_from', $cart_item->get( 'time_from' ) );
        $this->set( 'time_to',   $cart_item->get( 'time_to' ) );
        $this->set( 'slots',     $cart_item->get( 'slots' ) );
        $this->set( 'repeated',  0 );
        $this->set( 'repeat_data', array() );

        $chain_item = new ChainItem();
        $chain_item->set( 'service_id',        $cart_item->get( 'service_id' ) );
        $chain_item->set( 'staff_ids',         $cart_item->get( 'staff_ids' ) );
        $chain_item->set( 'number_of_persons', $cart_item->get( 'number_of_persons' ) );
        $chain_item->set( 'extras',            $cart_item->get( 'extras' ) );
        $chain_item->set( 'series_uniq_id',    $cart_item->get( 'series_uniq_id' ) );
        $chain_item->set( 'quantity',          1 );

        $this->chain->clear();
        $this->chain->add( $chain_item );
    }

    /**
     * Add chain items to cart.
     */
    public function addChainToCart()
    {
        $cart_items     = array();
        $edit_cart_keys = $this->get( 'edit_cart_keys' );
        $eck_idx        = 0;
        $slots          = $this->get( 'slots' );
        $slots_idx      = 0;
        $repeated       = $this->get( 'repeated' ) ?: 1;
        if ( $this->get( 'repeated' ) ) {
            $series_uniq_id = microtime() . rand( 0, PHP_INT_MAX );
        } else {
            $series_uniq_id = 0;
        }

        $cart_items_repeats = array();
        for ( $i = 0; $i < $repeated; $i++ ) {
            $items_in_repeat = array();
            foreach ( $this->chain->getItems() as $chain_item ) {
                for ( $q = 0; $q < $chain_item->get( 'quantity' ); ++ $q ) {
                    $cart_item_slots = array();

                    if ( $chain_item->getService()->get( 'type' ) == Entities\Service::TYPE_COMPOUND ) {
                        foreach ( $chain_item->getSubServices() as $sub_service ) {
                            $cart_item_slots[] = $slots[ $slots_idx ++ ];
                        }
                    } else {
                        $cart_item_slots[] = $slots[ $slots_idx ++ ];
                    }
                    $cart_item = new CartItem();

                    $cart_item->set( 'date_from', $this->get( 'date_from' ) );
                    $cart_item->set( 'days',      $this->get( 'days' ) );
                    $cart_item->set( 'time_from', $this->get( 'time_from' ) );
                    $cart_item->set( 'time_to',   $this->get( 'time_to' ) );

                    $cart_item->set( 'series_uniq_id',    $chain_item->get( 'series_uniq_id' ) ?: $series_uniq_id );
                    $cart_item->set( 'extras',            $chain_item->get( 'extras' ) );
                    $cart_item->set( 'location_id',       $chain_item->get( 'location_id' ) );
                    $cart_item->set( 'number_of_persons', $chain_item->get( 'number_of_persons' ) );
                    $cart_item->set( 'service_id',        $chain_item->get( 'service_id' ) );
                    $cart_item->set( 'slots',             $cart_item_slots );
                    $cart_item->set( 'staff_ids',         $chain_item->get( 'staff_ids' ) );
                    $cart_item->set( 'first_in_series',   false );
                    if ( isset ( $edit_cart_keys[ $eck_idx ] ) ) {
                        $cart_item->set( 'custom_fields', $this->cart->get( $edit_cart_keys[ $eck_idx ] )->get( 'custom_fields' ) );
                        ++ $eck_idx;
                    }

                    $items_in_repeat[] = $cart_item;
                }
            }
            $cart_items_repeats[] = $items_in_repeat;
        }

        /**
         * Searching for minimum time to find first client visiting
         */
        $first_visit_time = $slots[0][2];
        $first_visit_repeat = 0;
        foreach ( $cart_items_repeats as $repeat_id => $items_in_repeat ) {
            foreach ($items_in_repeat as $cart_item) {
                /** @var CartItem $cart_item */
                $slots = $cart_item->get( 'slots' );
                foreach ($slots as $slot) {
                    if ( $slot[2] < $first_visit_time ) {
                        $first_visit_time = $slots[2];
                        $first_visit_repeat = $repeat_id;
                    }
                }

            }

        }
        foreach ( $cart_items_repeats[ $first_visit_repeat ] as $cart_item ) {
            /** @var CartItem $cart_item */
            $cart_item->set( 'first_in_series', true );
        }

        foreach ( $cart_items_repeats as $items_in_repeat ) {
            $cart_items = array_merge( $cart_items, $items_in_repeat );
        }

        $count = count( $edit_cart_keys );
        $inserted_keys = array();

        if ( $count ) {
            for ( $i = $count - 1; $i > 0; -- $i ) {
                $this->cart->drop( $edit_cart_keys[ $i ] );
            }
            $inserted_keys = $this->cart->replace( $edit_cart_keys[0], $cart_items );
        } else {
            foreach ( $cart_items as $cart_item ) {
                $inserted_keys[] = $this->cart->add( $cart_item );
            }
        }

        $this->set( 'edit_cart_keys', $inserted_keys );
    }

    /**
     * Validate fields.
     *
     * @param $data
     * @return array
     */
    public function validate( $data )
    {
        $validator = new Validator();
        foreach ( $data as $field_name => $field_value ) {
            switch ( $field_name ) {
                case 'service_id':
                    $validator->validateNumber( $field_name, $field_value );
                    break;
                case 'date_from':
                case 'time_from':
                    $validator->validateDateTime( $field_name, $field_value, true );
                    break;
                case 'name':
                    $validator->validateString( $field_name, $field_value, 255, true, true, 3 );
                    break;
                case 'email':
                    $validator->validateEmail( $field_name, $data );
                    break;
                case 'phone':
                    $validator->validatePhone( $field_name, $field_value, Config::phoneRequired() );
                    break;
                case 'cart':
                    $validator->validateCart( $field_value, $data['form_id'] );
                    break;
                default:
            }
        }
        // Post validators.
        if ( isset ( $data['phone'] ) || isset ( $data['email'] ) ) {
            $validator->postValidateCustomer( $data );
        }

        return $validator->getErrors();
    }

    /**
     * Save all data and create appointment.
     *
     * @param int $payment_id
     * @return Entities\CustomerAppointment[]
     */
    public function save( $payment_id = null )
    {
        $this->payment_id = $payment_id;

        $user_id  = get_current_user_id();
        $customer = new Entities\Customer();
        if ( $user_id > 0 ) {
            // Try to find customer by WP user ID.
            $customer->loadBy( array( 'wp_user_id' => $user_id ) );
        }
        if ( ! $customer->isLoaded() ) {
            // Try to find customer by phone or email.
            $customer->loadBy(
                Config::phoneRequired()
                    ? array( 'phone' => $this->get( 'phone' ) )
                    : array( 'email' => $this->get( 'email' ) )
            );
            if ( ! $customer->isLoaded() ) {
                // Try to find customer by 'secondary' identifier, otherwise create new customer.
                $customer->loadBy(
                    Config::phoneRequired()
                        ? array( 'email' => $this->get( 'email' ), 'phone' => '' )
                        : array( 'phone' => $this->get( 'phone' ), 'email' => '' )
                );
            }
        }
        foreach ( array( 'name', 'phone', 'email' ) as $field ) {
            if ( $this->get( $field ) != '' ) {
                // Overwrite only if value is not empty.
                $customer->set( $field, $this->get( $field ) );
            }
        }
        if ( get_option( 'bookly_cst_create_account', 0 ) && ! $customer->get( 'wp_user_id' ) ) {
            // Create WP user and link it to customer.
            $customer->setWPUser( $user_id );
        }
        $customer->save();
        if ( get_option( 'bookly_cst_remember_in_cookie' ) ) {
            setcookie( 'bookly-cst-name',  $customer->get( 'name' ),  time() + YEAR_IN_SECONDS );
            setcookie( 'bookly-cst-phone', $customer->get( 'phone' ), time() + YEAR_IN_SECONDS );
            setcookie( 'bookly-cst-email', $customer->get( 'email' ), time() + YEAR_IN_SECONDS );
        }

        return $this->cart->save( $customer, $payment_id, $this->get( 'time_zone' ), $this->get( 'time_zone_offset' ), $this->booking_numbers );
    }

    /**
     * Get coupon.
     *
     * @return Entities\Coupon|false
     */
    public function getCoupon()
    {
        if ( $this->coupon === null ) {
            $coupon = new Entities\Coupon();
            $coupon->loadBy( array(
                'code' => $this->get( 'coupon' ),
            ) );
            if ( $coupon->isLoaded() && $coupon->get( 'used' ) < $coupon->get( 'usage_limit' ) ) {
                $this->coupon = $coupon;
            } else {
                $this->coupon = false;
            }
        }

        return $this->coupon;
    }

    /**
     * Set payment ( PayPal, 2Checkout, PayU Latam, Payson, Mollie ) transaction status.
     *
     * @param string $gateway
     * @param string $status
     * @param mixed  $data
     * @todo use $status as const
     */
    public function setPaymentStatus( $gateway, $status, $data = null )
    {
        Session::setFormVar( $this->form_id, 'payment', array(
            'gateway' => $gateway,
            'status'  => $status,
            'data'    => $data,
        ) );
    }

    /**
     * Get and clear ( PayPal, 2Checkout, PayU Latam, Payson ) transaction status.
     *
     * @return array|false
     */
    public function extractPaymentStatus()
    {
        if ( $status = Session::getFormVar( $this->form_id, 'payment' ) ) {
            Session::destroyFormVar( $this->form_id, 'payment' );

            return $status;
        }

        return false;
    }

    /**
     * Get booking numbers.
     *
     * @return array
     */
    public function getBookingNumbers()
    {
        return $this->booking_numbers;
    }

    /**
     * Get payment ID.
     *
     * @return int|null
     */
    public function getPaymentId()
    {
        return $this->payment_id;
    }

    /**
     * Apply client time zone.
     */
    public function applyTimeZone()
    {
        if ( $this->data['time_zone_offset'] !== null ) {
            Slots\TimePoint::$client_timezone_offset = - $this->data['time_zone_offset'] * MINUTE_IN_SECONDS;
            Slots\DatePoint::$client_timezone = $this->data['time_zone'] ?: Utils\DateTime::guessTimeZone( Slots\TimePoint::$client_timezone_offset );
        }
    }
}