<?php
namespace BooklyServiceExtras\Lib;

/**
 * Class Filters
 * @package BooklyServiceExtras\Lib
 */
class Filters
{
    /**
     * Return sum durations of Extras
     *
     * @param double $default
     * @param array  $extras
     * @return mixed
     */
    public static function getTotalDuration( $default, array $extras )
    {
        $items = Entities\ServiceExtra::query()
            ->select( 'id,duration' )
            ->whereIn( 'id', array_keys( $extras ) )
            ->indexBy( 'id' )
            ->fetchArray();
        foreach ( $items as $extra_id => $values ) {
            $default += $extras[ $extra_id ] * $values['duration'];
        }

        return $default;
    }

    /**
     * Add extras titles for chain item
     *
     * @param                       $data
     * @param \Bookly\Lib\ChainItem $chain_item
     * @return mixed
     */
    public static function prepareChainItemInfoText( $data, \Bookly\Lib\ChainItem $chain_item )
    {
        $titles = array();
        $extras = $chain_item->get( 'extras' );
        if ( ! empty( $extras ) ) {
            foreach ( self::findByIds( array(), array_keys( $extras ) ) as $extra ) {
                $titles[] = self::niceTitle( $extra->getTitle(), $extras[ $extra->get( 'id' ) ] );
            }
        }
        $data['extras'][] = implode( ', ', $titles );

        return $data;
    }

    /**
     * Add extras titles for cart item
     *
     * @param                      $data
     * @param \Bookly\Lib\CartItem $cart_item
     * @return mixed
     */
    public static function prepareCartItemInfoText( $data, \Bookly\Lib\CartItem $cart_item )
    {
        $titles = array();
        $extras = $cart_item->get( 'extras' );
        if ( ! empty( $extras ) ) {
            foreach ( self::findByIds( array(), array_keys( $extras ) ) as $extra ) {
                $titles[] = self::niceTitle( $extra->getTitle(), $extras[ $extra->get( 'id' ) ] );
            }
        }
        $data['extras'][] = implode( ', ', $titles );

        return $data;
    }

    /**
     * Add {EXTRAS} code to booking
     * @param $info_text_codes
     * @param $data
     * @return array
     */
    public static function prepareInfoTextCode( $info_text_codes, $data )
    {
        $info_text_codes['{extras}'] = '<b>' . implode( ', ', $data['extras'] ) . '</b>';
        return $info_text_codes;
    }

    /**
     * Add {extras} code in Bookly Appearance.
     *
     * @param array $codes
     * @return array
     */
    public static function appearanceShortCodes( array $codes )
    {
        $codes[] = array( 'code' => 'extras', 'description' => __( 'extras titles', 'bookly-service-extras' ), );

        return $codes;
    }

    /**
     * @param array $codes
     * @return array
     */
    public static function notificationShortCodes( array $codes )
    {
        $codes = self::appearanceShortCodes( $codes );
        $codes[] = array( 'code' => 'extras_total_price', 'description' => __( 'extras total price', 'bookly-service-extras' ), );

        return $codes;
    }

    /**
     * Add on calender info about extras.
     *
     * @param $description
     * @param  \Bookly\Lib\Entities\CustomerAppointment | array $appointment_data
     * @return string
     */
    public static function calendarAppointmentDescription( $description, $appointment_data )
    {
        $ca_extras = '[]';
        if ( $appointment_data instanceof \Bookly\Lib\Entities\CustomerAppointment ) {
            $ca_extras = $appointment_data->get( 'extras' );
        } elseif ( is_array( $appointment_data ) && isset( $appointment_data['extras'] ) ) {
            $ca_extras = $appointment_data['extras'];
        }
        if ( $ca_extras != '[]' ) {
            $extras = (array) json_decode( $ca_extras, true );
            $items = self::findByIds( array(), array_keys( $extras ) );
            if ( ! empty( $items ) ) {
                $description .= sprintf(
                    '<br/><div>%s: %s</div>',
                    __( 'Extras', 'bookly-service-extras' ),
                    implode( ', ', array_map( function ( $extra ) use ( $extras ) {
                        /** @var \BooklyServiceExtras\Lib\Entities\ServiceExtra $extra */
                        $id    = $extra->get( 'id' );
                        $title = $extra->get( 'title' );
                        if ( $extras[ $id ] > 1 ) {
                            $title = $extras[ $id ] . '&nbsp;&times;&nbsp;' . $title;
                        }

                        return $title;
                    }, $items ) )
                );
            }
        }

        return $description;
    }

    /**
     * Add Extras info to appointment data.
     *
     * @param $row
     * @param $translate
     * @return mixed
     */
    public static function appointmentData( $row, $translate  )
    {
        $extras = (array) json_decode( $row['extras'], true );
        $row['extras'] = array();
        foreach ( self::findByIds( array(), array_keys( $extras ) ) as $extra ) {
            $quantity = $extras[ $extra->get( 'id' ) ];
            $title    = $translate ? $extra->getTitle() : $extra->get( 'title' ) ?: __( 'Untitled', 'bookly-service-extras' );
            $row['extras'][] = array(
                'title' => self::niceTitle( $title, $quantity ),
                'price' => $extra->get( 'price' ) * $quantity,
            );
        }

        return $row;
    }

    /**
     * Find extras by given ids.
     *
     * @param $default
     * @param array $extras_ids
     * @return Entities\ServiceExtra[]
     */
    public static function findByIds( $default, array $extras_ids )
    {
        $extras = Entities\ServiceExtra::query()->whereIn( 'id', $extras_ids )->find();
        if ( ! empty( $extras ) ) {
            $default = $extras;
        }

        return $default;
    }

    /**
     * Find extras by service id.
     *
     * @param mixed $default
     * @param integer $service_id
     * @return Entities\ServiceExtra[]
     */
    public static function findByServiceId( $default, $service_id )
    {
        $extras = Entities\ServiceExtra::query()->where( 'service_id', $service_id )->sortBy( 'position' )->find();
        if ( ! empty( $extras ) ) {
            $default = $extras;
        }

        return $default;
    }

    /**
     * Find all extras.
     *
     * @param mixed $default
     * @return Entities\ServiceExtra[]
     */
    public static function findAll( $default )
    {
        $extras = Entities\ServiceExtra::query()->sortBy( 'title' )->find();
        if ( ! empty( $extras ) ) {
            $default = $extras;
        }

        return $default;
    }

    /**
     * Render step Extras on frontend.
     *
     * @param string                        $default
     * @param \Bookly\Lib\UserBookingData   $userData
     * @param bool                          $show_cart_btn
     * @param string                        $info_text
     * @param string                        $progress_tracker
     * @return string
     */
    public static function renderBookingStep( $default, \Bookly\Lib\UserBookingData $userData, $show_cart_btn, $info_text, $progress_tracker )
    {
        $chain = array();
        $chain_price = null;
        foreach ( $userData->chain->getItems() as $chain_item ) {
            /** @var \Bookly\Lib\Entities\Service $service */
            $service = \Bookly\Lib\Entities\Service::query()->where( 'id', $chain_item->get( 'service_id' ) )->findOne();
            if ( $service->get( 'type' ) == \Bookly\Lib\Entities\Service::TYPE_COMPOUND ) {
                $service_id = current( json_decode( $service->get( 'sub_services' ), true ) );
                $chain_price += $service->get( 'price' );
            } else {
                $service_id = $service->get( 'id' );
                if ( count( $chain_item->get( 'staff_ids' ) ) == 1 ) {
                    $staff_service = \Bookly\Lib\Entities\StaffService::query()
                        ->select( 'price' )
                        ->where( 'service_id', $service->get( 'id' ) )
                        ->where( 'staff_id', current( $chain_item->get( 'staff_ids' ) ) )
                        ->fetchRow();
                    $chain_price += $staff_service['price'];
                }
            }
            $chain[] = array(
                'category_service' => $service->getCategoryName() . ' - ' . $service->getTitle(),
                'service_id'       => $service_id,
                'extras'           => Filters::findByServiceId( array(), $service_id ),
                'checked_extras'   => $chain_item->get( 'extras' ),
            );
        }
        $show = get_option( 'bookly_service_extras_show' );
        return Render::render( '2_extras', compact( 'chain', 'show', 'show_cart_btn', 'info_text', 'progress_tracker', 'chain_price' ), false );
    }

    /**
     * Save Extras settings.
     *
     * @param $alert
     * @param $tab
     * @param $_post
     * @return mixed
     */
    public static function saveSettings( $alert, $tab, $_post )
    {
        if ( $tab == 'service_extras' && ! empty( $_post ) ) {
            if ( ! array_key_exists( 'bookly_service_extras_show', $_post ) ) {
                $_post['bookly_service_extras_show'] = array();
            }
            $options = array( 'bookly_service_extras_enabled', 'bookly_service_extras_show' );
            foreach ( $options as $option_name ) {
                if ( array_key_exists( $option_name, $_post ) ) {
                    update_option( $option_name, $_post[ $option_name ] );
                }
            }
            $alert['success'][] = __( 'Settings saved.', 'bookly' );
        }

        return $alert;
    }

    /**
     * Register entity for schema control.
     *
     * @param array $default
     * @return array
     */
    public static function pluginTables( array $default )
    {
        foreach ( scandir( Plugin::getDirectory() . '/lib/entities' ) as $filename ) {
            if ( $filename == '.' || $filename == '..' ) {
                continue;
            }
            $default[] = '\\BooklyServiceExtras\\Lib\\Entities\\' . basename( $filename, '.php' );
        }

        return $default;
    }

    /**
     * Prepare data for notification codes.
     *
     * @param \Bookly\Lib\NotificationCodes            $codes
     * @param \Bookly\Lib\Entities\CustomerAppointment $ca
     * @return \Bookly\Lib\NotificationCodes
     */
    public static function prepareNotificationCodes( \Bookly\Lib\NotificationCodes $codes, \Bookly\Lib\Entities\CustomerAppointment $ca )
    {
        $extras = (array) json_decode( $ca->get( 'extras' ), true );
        $titles = array();
        $price  = 0.0;
        /** @var Entities\ServiceExtra $extra */
        foreach ( self::findByIds( array(), array_keys( $extras ) ) as $extra ) {
            $quantity = $extras[ $extra->get( 'id' ) ];
            $titles[] = self::niceTitle( $extra->getTitle(), $quantity );
            $price   += $extra->get( 'price' ) * $quantity;
        }
        $codes->set( 'extras', implode( ', ', $titles ) );
        $codes->set( 'extras_total_price', $price );

        return $codes;
    }

    /**
     * Prepare replacements for notification codes.
     *
     * @param array                         $replace
     * @param \Bookly\Lib\NotificationCodes $codes
     * @return array
     */
    public static function replaceNotificationCodes( array $replace, \Bookly\Lib\NotificationCodes $codes )
    {
        $extras = $codes->get( 'extras' );
        if ( $codes->getContentType() == 'plain' ) {
            /** @see \BooklyServiceExtras\Lib\Filters::niceTitle */
            $extras = str_replace( '&nbsp;&times;&nbsp;', ' x ', $extras );
        }
        $replace['{extras}'] = $extras;
        $replace['{extras_total_price}'] = \Bookly\Lib\Utils\Common::formatPrice( $codes->get( 'extras_total_price' ) );

        return $replace;
    }

    private static function niceTitle( $title, $quantity )
    {
        return ( $quantity > 1 ) ? $quantity . '&nbsp;&times;&nbsp;' . $title : $title;
    }

}