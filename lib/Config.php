<?php
namespace Bookly\Lib;

/**
 * Class Config
 * @package Bookly\Lib
 *
 * @method static bool chainAppointmentsActive()       Check whether Chain Appointment add-on is active or not.
 * @method static bool compoundServicesActive()        Check whether Compound Services add-on is active or not.
 * @method static bool depositPaymentsActive()         Check whether Deposit Payments add-on is active or not.
 * @method static bool locationsActive()               Check whether Locations add-on is active or not.
 * @method static bool multiplyAppointmentsActive()    Check whether Multiply Appointments add-on is active or not.
 * @method static bool recurringAppointmentsActive()   Check whether Recurring Appointments add-on is active or not.
 * @method static bool serviceExtrasActive()           Check whether Extras add-on is active or not.
 * @method static bool serviceScheduleActive()         Check whether Service Schedule add-on is active or not.
 * @method static bool specialDaysActive()             Check whether Special Days add-on is active or not.
 * @method static bool specialHoursActive()            Check whether Special Hours add-on is active or not.
 * @method static bool staffCabinetActive()            Check whether Staff Cabinet add-on is active or not.
 * @method static bool chainAppointmentsEnabled()      Check whether Chain Appointment add-on is enabled or not.
 * @method static bool compoundServicesEnabled()       Check whether Compound Services add-on is enabled or not.
 * @method static bool depositPaymentsEnabled()        Check whether Deposit Payments add-on is enabled or not.
 * @method static bool locationsEnabled()              Check whether Locations add-on is enabled or not.
 * @method static bool multiplyAppointmentsEnabled()   Check whether Multiply Appointments add-on is enabled or not.
 * @method static bool recurringAppointmentsEnabled()  Check whether Recurring Appointments add-on is enabled or not.
 * @method static bool serviceExtrasEnabled()          Check whether Extras add-on is enabled or not.
 * @method static bool serviceScheduleEnabled()        Check whether Service Schedule add-on is enabled or not.
 * @method static bool specialDaysEnabled()            Check whether Special Days add-on is enabled or not.
 * @method static bool specialHoursEnabled()           Check whether Special Hours add-on is enabled or not.
 * @method static bool staffCabinetEnabled()           Check whether Staff Cabinet add-on is enabled or not.
 */
abstract class Config
{
    /** @var string */
    private static $wp_timezone = null;

    /**
     * Get categories, services and staff members for drop down selects
     * for the 1st step of booking wizard.
     *
     * @return array
     */
    public static function getCaSeSt()
    {
        $result = array(
            'locations'  => array(),
            'categories' => array(),
            'services'   => array(),
            'staff'      => array(),
        );

        // Categories.
        $rows = Entities\Category::query()->fetchArray();
        foreach ( $rows as $row ) {
            $result['categories'][ $row['id'] ] = array(
                'id'   => (int) $row['id'],
                'name' => Utils\Common::getTranslatedString( 'category_' . $row['id'], $row['name'] ),
                'pos'  => (int) $row['position'],
            );
        }

        // Services.
        $rows = Entities\Service::query( 's' )
            ->select( 's.id, s.category_id, s.title, s.position, s.duration, MIN(ss.capacity_min) AS min_capacity, MAX(ss.capacity_max) AS max_capacity' )
            ->innerJoin( 'StaffService', 'ss', 'ss.service_id = s.id' )
            ->where( 's.type',  Entities\Service::TYPE_SIMPLE )
            ->whereNot( 's.visibility', 'private' )
            ->groupBy( 's.id' )
            ->fetchArray();
        foreach ( $rows as $row ) {
            $result['services'][ $row['id'] ] = array(
                'id'          => (int) $row['id'],
                'category_id' => (int) $row['category_id'],
                'name'        => $row['title'] == ''
                    ? __( 'Untitled', 'bookly' )
                    : Utils\Common::getTranslatedString( 'service_' . $row['id'], $row['title'] ),
                'duration'     => \Bookly\Lib\Utils\DateTime::secondsToInterval( $row['duration'] ),
                'min_capacity' => (int) $row['min_capacity'],
                'max_capacity' => (int) $row['max_capacity'],
                'has_extras'   => (int) ( \Bookly\Lib\Proxy\ServiceExtras::findByServiceId( $row['id'] ) ),
                'pos'          => (int) $row['position'],
            );

            if ( ! $row['category_id'] && ! isset ( $result['categories'][0] ) ) {
                $result['categories'][0] = array(
                    'id'   => 0,
                    'name' => __( 'Uncategorized', 'bookly' ),
                    'pos'  => 99999,
                );
            }
        }

        // Staff.
        $rows = Entities\Staff::query( 'st' )
            ->select( 'st.id, st.full_name, st.position, ss.service_id, ss.capacity_min, ss.capacity_max, ss.price' )
            ->innerJoin( 'StaffService', 'ss', 'ss.staff_id = st.id' )
            ->leftJoin( 'Service', 's', 's.id = ss.service_id' )
            ->whereNot( 'st.visibility', 'private' )
            ->whereNot( 's.visibility', 'private' )
            ->fetchArray();
        foreach ( $rows as $row ) {
            if ( ! isset ( $result['staff'][ $row['id'] ] ) ) {
                $result['staff'][ $row['id'] ] = array(
                    'id'       => (int) $row['id'],
                    'name'     => Utils\Common::getTranslatedString( 'staff_' . $row['id'], $row['full_name'] ),
                    'services' => array(),
                    'pos'      => (int) $row['position'],
                );
            }
            $result['staff'][ $row['id'] ]['services'][ $row['service_id'] ] = array(
                'min_capacity' => (int) $row['capacity_min'],
                'max_capacity' => (int) $row['capacity_max'],
                'price'        => get_option( 'bookly_app_staff_name_with_price' )
                    ? html_entity_decode( Utils\Price::format( $row['price'] ) )
                    : null,
            );
        }

        return Proxy\Shared::prepareCaSeSt( $result );
    }

    /**
     * Get available days and available time ranges
     * for the 1st step of booking wizard.
     *
     * @return array
     */
    public static function getDaysAndTimes()
    {
        /** @var \WP_Locale $wp_locale */
        global $wp_locale;

        $result = array(
            'days'  => array(),
            'times' => array(),
        );

        $res = array_merge(
            Entities\StaffScheduleItem::query()
                ->select( '`r`.`day_index`, MIN(`r`.`start_time`) AS `start_time`, MAX(`r`.`end_time`) AS `end_time`' )
                ->leftJoin( 'Staff', 's', '`s`.`id` = `r`.`staff_id`' )
                ->whereNot( 'r.start_time', null )
                ->whereNot( 's.visibility', 'private' )
                ->groupBy( 'day_index' )
                ->fetchArray(),
            (array) Proxy\SpecialDays::getDaysAndTimes()
        );

        /** @var Slots\TimePoint $min_start_time */
        /** @var Slots\TimePoint $max_end_time */
        $min_start_time = null;
        $max_end_time   = null;
        $days           = array();

        foreach ( $res as $row ) {
            $start_time = Slots\TimePoint::fromStr( $row['start_time'] );
            $end_time   = Slots\TimePoint::fromStr( $row['end_time'] );

            if ( $min_start_time === null || $min_start_time->gt( $start_time ) ) {
                $min_start_time = $start_time;
            }
            if ( $max_end_time === null || $max_end_time->lt( $end_time ) ) {
                $max_end_time = $end_time;
            }

            // Convert to client time zone.
            $start_time = $start_time->toClientTz();
            $end_time   = $end_time->toClientTz();

            // Add day(s).
            if ( $start_time->value() < 0 ) {
                $prev_day = $row['day_index'] - 1;
                if ( $prev_day < 1 ) {
                    $prev_day = 7;
                }
                $days[ $prev_day ] = true;
            }
            if ( $start_time->value() < HOUR_IN_SECONDS * 24 && $end_time->value() > 0 ) {
                $days[ $row['day_index'] ] = true;
            }
            if ( $end_time->value() > HOUR_IN_SECONDS * 24 ) {
                $next_day = $row['day_index'] + 1;
                if ( $next_day > 7 ) {
                    $next_day = 1;
                }
                $days[ $next_day ] = true;
            }
        }

        $start_of_week = get_option( 'start_of_week' );
        $week_days     = array_values( $wp_locale->weekday_abbrev );

        // Sort days considering start_of_week;
        uksort( $days, function ( $a, $b ) use ( $start_of_week ) {
            $a -= $start_of_week;
            $b -= $start_of_week;
            if ( $a < 1 ) {
                $a += 7;
            }
            if ( $b < 1 ) {
                $b += 7;
            }

            return $a - $b;
        } );

        // Fill days.
        foreach ( array_keys( $days ) as $day_id ) {
            $result['days'][ $day_id ] = $week_days[ $day_id - 1 ];
        }

        if ( $min_start_time && $max_end_time ) {
            $start        = $min_start_time;
            $end          = $max_end_time;
            $client_start = $start->toClientTz();
            $client_end   = $end->toClientTz();

            while ( $start->lte( $end ) ) {
                $result['times'][ Utils\DateTime::buildTimeString( $start->value(), false ) ] = $client_start->formatI18nTime();
                // The next value will be rounded to integer number of hours, i.e. e.g. 8:00, 9:00, 10:00 and so on.
                $start        = $start->modify( HOUR_IN_SECONDS - ( $start->value() % HOUR_IN_SECONDS ) );
                $client_start = $client_start->modify( HOUR_IN_SECONDS - ( $client_start->value() % HOUR_IN_SECONDS ) );
            }
            // The last value should always be the end time.
            $result['times'][ Utils\DateTime::buildTimeString( $end->value(), false ) ] = $client_end->formatI18nTime();
        }

        return $result;
    }

    /**
     * Get array with bounding days for Pickadate.
     *
     * @return array
     */
    public static function getBoundingDaysForPickadate()
    {
        $result = array();

        $dp = Slots\DatePoint::now()->modify( self::getMinimumTimePriorBooking() )->toClientTz();
        $result['date_min'] = array(
            (int) $dp->format( 'Y'),
            (int) $dp->format( 'n' ) - 1,
            (int) $dp->format( 'j' ),
        );
        $dp = $dp->modify( ( self::getMaximumAvailableDaysForBooking() - 1 ) . ' days' );
        $result['date_max'] = array(
            (int) $dp->format( 'Y' ),
            (int) $dp->format( 'n' ) - 1,
            (int) $dp->format( 'j' ),
        );

        return $result;
    }

    /**
     * Get value of option for given payment type.
     *
     * @param string $type
     * @return string
     */
    public static function getPaymentTypeOption( $type )
    {
        return get_option( 'bookly_pmt_' . $type, 'disabled' );
    }

    /**
     * Check whether given payment type is enabled.
     *
     * @param string $type
     * @return bool
     */
    public static function paymentTypeEnabled( $type )
    {
        return self::getPaymentTypeOption( $type ) != 'disabled';
    }

    /**
     * Check whether payment step is disabled.
     *
     * @return bool
     */
    public static function paymentStepDisabled()
    {
        $types = array(
            Entities\Payment::TYPE_2CHECKOUT,
            Entities\Payment::TYPE_AUTHORIZENET,
            Entities\Payment::TYPE_LOCAL,
            Entities\Payment::TYPE_MOLLIE,
            Entities\Payment::TYPE_PAYPAL,
			Entities\Payment::TYPE_ZARIN,
			Entities\Payment::TYPE_MELLAT,
            Entities\Payment::TYPE_PAYSON,
            Entities\Payment::TYPE_PAYULATAM,
            Entities\Payment::TYPE_STRIPE,
			Entities\Payment::TYPE_NEXTPAY,
        );

        foreach ( $types as $type ) {
            if ( self::paymentTypeEnabled( $type ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get time slot length in seconds.
     *
     * @return integer
     */
    public static function getTimeSlotLength()
    {
        return (int) get_option( 'bookly_gen_time_slot_length', 15 ) * MINUTE_IN_SECONDS;
    }

    /**
     * Check whether service duration should be used instead of slot length on the frontend.
     *
     * @return bool
     */
    public static function useServiceDurationAsSlotLength()
    {
        return (bool) get_option( 'bookly_gen_service_duration_as_slot_length', false );
    }

    /**
     * Check whether use client time zone.
     *
     * @return bool
     */
    public static function useClientTimeZone()
    {
        return (bool) get_option( 'bookly_gen_use_client_time_zone' );
    }

    /**
     * Get minimum time (in seconds) prior to booking.
     *
     * @return integer
     */
    public static function getMinimumTimePriorBooking()
    {
        return (int) ( get_option( 'bookly_gen_min_time_prior_booking' ) * 3600 );
    }

    /**
     * @return int
     */
    public static function getMaximumAvailableDaysForBooking()
    {
        return (int) get_option( 'bookly_gen_max_days_for_booking', 365 );
    }

    /**
     * Whether to show calendar in the second step of booking form.
     *
     * @return bool
     */
    public static function showCalendar()
    {
        return (bool) get_option( 'bookly_app_show_calendar', false );
    }

    /**
     * Whether to show fully booked time slots in the second step of booking form.
     *
     * @return bool
     */
    public static function showBlockedTimeSlots()
    {
        return (bool) get_option( 'bookly_app_show_blocked_timeslots', false );
    }

    /**
     * Whether to show days in the second step of booking form in separate columns or not.
     *
     * @return bool
     */
    public static function showDayPerColumn()
    {
        return (bool) get_option( 'bookly_app_show_day_one_column', false );
    }

    /**
     * Whether phone field is required at the Details step or not.
     *
     * @return bool
     */
    public static function phoneRequired()
    {
        return get_option( 'bookly_cst_required_phone' ) == 1;
    }

    /**
     * Whether custom fields attached to services or not.
     *
     * @return bool
     */
    public static function customFieldsPerService()
    {
        return get_option( 'bookly_custom_fields_per_service' ) == 1;
    }

    /**
     * Whether combined notifications for cart are enabled or not.
     *
     * @return bool
     */
    public static function combinedNotificationsEnabled()
    {
        return get_option( 'bookly_cst_combined_notifications' ) == 1;
    }

    /**
     * Whether step Cart is enabled or not.
     *
     * @return bool
     */
    public static function showStepCart()
    {
        return get_option( 'bookly_cart_enabled' ) == 1 && ! Config::wooCommerceEnabled();
    }

    /**
     * Check if emails are sent as HTML or plain text.
     *
     * @return bool
     */
    public static function sendEmailAsHtml()
    {
        return get_option( 'bookly_email_send_as' ) == 'html';
    }

    /**
     * Get WordPress time zone setting.
     *
     * @return string
     */
    public static function getWPTimeZone()
    {
        if ( self::$wp_timezone === null ) {
            if ( $timezone = get_option( 'timezone_string' ) ) {
                // If site timezone string exists, return it.
                self::$wp_timezone = $timezone;
            } else {
                // Otherwise return offset.
                $gmt_offset = get_option( 'gmt_offset' );
                self::$wp_timezone = sprintf( '%s%02d:%02d', $gmt_offset >= 0 ? '+' : '-', abs( $gmt_offset ), abs( $gmt_offset ) * 60 % 60 );
            }
        }

        return self::$wp_timezone;
    }

    /******************************************************************************************************************
     * Add-ons                                                                                                        *
     ******************************************************************************************************************/

    /**
     * WooCommerce Plugin enabled or not.
     *
     * @return bool
     */
    public static function wooCommerceEnabled()
    {
        return ( get_option( 'bookly_wc_enabled' ) && get_option( 'bookly_wc_product' ) && class_exists( 'WooCommerce', false ) && ( WC()->cart->get_cart_url() !== false ) );
    }

    /**
     * Check whether Bookly's purchase code was not provided and its grace period has expired.
     *
     * @return bool
     */
    public static function booklyExpired()
    {
        $states = self::getPluginVerificationStates();

        return $states['bookly'] === 'expired';
    }

    /**
     * Get information about plugin verification states
     * (i.e. whether a plugin has a valid purchase code or whether it is in a grace period or the grace period has expired).
     *
     * @return array
     */
    public static function getPluginVerificationStates()
    {
        static $result = null;

        if ( $result === null ) {
            $result = array(
                'bookly'  => null,
                'add-ons' => array(
                    'verified' => array(),
                    'in_grace' => array(),
                    'expired'  => array(),
                ),
                'grace_remaining_days' => null,
            );

            // Add-ons.
            $bookly_plugins = apply_filters( 'bookly_plugins', array() );
            unset ( $bookly_plugins[ Plugin::getSlug() ] );

            $grace_period_days = 14;
            $unix_day = (int) ( current_time( 'timestamp' ) / DAY_IN_SECONDS );
            $server_error_period = (int) ( get_option( 'bookly_api_server_error_time' ) / DAY_IN_SECONDS ) - $unix_day;
            /** @var Base\Plugin $plugin_class */
            if ( $server_error_period > 7 ) {
                $state = $server_error_period >= $grace_period_days ? 'expired' : 'in_grace';
                $result['bookly'] = $state;
                $result['add-ons'][ $state ] = $bookly_plugins;
                if ( $state === 'in_grace' ) {
                    $result['grace_remaining_days'] = $grace_period_days - $server_error_period;
                }
            } else {
                // Bookly.


                foreach ( $bookly_plugins as $plugin_slug => $plugin_class ) {
                    if ( $plugin_class::getPurchaseCode() == '' && $plugin_class::enabled() ) {
                        $grace_start = get_option( $plugin_class::getPrefix() . 'grace_start' );
                        $expiration_day = (int) ( $grace_start / DAY_IN_SECONDS ) + $grace_period_days;
                        if ( $unix_day >= $expiration_day ) {
                            $result['bookly'] = 'expired';
                            $result['add-ons']['expired'][ $plugin_slug ] = $plugin_class;
                        } elseif ( ( $expiration_day - $unix_day ) <= $grace_period_days ) {
                            $result['add-ons']['in_grace'][ $plugin_slug ] = $plugin_class;
                            $remaining_days = $expiration_day - $unix_day;
                            if ( $result['grace_remaining_days'] === null || $remaining_days < $result['grace_remaining_days'] ) {
                                $result['grace_remaining_days'] = $remaining_days;
                            }
                        }
                    } else {
                        $result['add-ons']['verified'][ $plugin_slug ] = $plugin_class;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Call magic functions.
     *
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic( $name , array $arguments )
    {
        // <add-on>Active
        // <add-on>Enabled
        if ( preg_match( '/^(\w+)(Active|Enabled)/', $name, $match ) ) {
            /** @var Base\Plugin $plugin_class */
            $plugin_class = sprintf( '\Bookly%s\Lib\Plugin', ucfirst( $match[1] ) );

            return class_exists( $plugin_class, false ) && ( $match[2] == 'Active' || $plugin_class::enabled() );
        }

        return null;
    }
}