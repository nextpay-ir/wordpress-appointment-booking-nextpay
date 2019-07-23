<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$codes = array(
    array( 'code' => 'appointment_date', 'description' => __( 'date of appointment', 'bookly' ) ),
    array( 'code' => 'appointment_time', 'description' => __( 'time of appointment', 'bookly' ) ),
    array( 'code' => 'booking_number',   'description' => __( 'booking number', 'bookly' ) ),
    array( 'code' => 'category_name',    'description' => __( 'name of category', 'bookly' ) ),
    array( 'code' => 'company_address',  'description' => __( 'address of company', 'bookly' ) ),
    array( 'code' => 'company_name',     'description' => __( 'name of company', 'bookly' ) ),
    array( 'code' => 'company_phone',    'description' => __( 'company phone', 'bookly' ) ),
    array( 'code' => 'company_website',  'description' => __( 'company web-site address', 'bookly' ) ),
    array( 'code' => 'service_capacity', 'description' => __( 'capacity of service', 'bookly' ) ),
    array( 'code' => 'service_info',     'description' => __( 'info of service', 'bookly' ) ),
    array( 'code' => 'service_name',     'description' => __( 'name of service', 'bookly' ) ),
    array( 'code' => 'service_price',    'description' => __( 'price of service', 'bookly' ) ),
    array( 'code' => 'signed_up',        'description' => __( 'number of persons already in the list', 'bookly' ) ),
    array( 'code' => 'staff_email',      'description' => __( 'email of staff', 'bookly' ) ),
    array( 'code' => 'staff_info',       'description' => __( 'info of staff', 'bookly' ) ),
    array( 'code' => 'staff_name',       'description' => __( 'name of staff', 'bookly' ) ),
    array( 'code' => 'staff_phone',      'description' => __( 'phone of staff', 'bookly' ) ),
);
if ( $participant == 'one' ) {
    $codes[] = array( 'code' => 'client_email',      'description' => __( 'email of client', 'bookly' ) );
    $codes[] = array( 'code' => 'client_name',       'description' => __( 'name of client', 'bookly' ) );
    $codes[] = array( 'code' => 'client_phone',      'description' => __( 'phone of client', 'bookly' ) );
    $codes[] = array( 'code' => 'custom_fields',     'description' => __( 'combined values of all custom fields', 'bookly' ) );
    $codes[] = array( 'code' => 'payment_status',    'description' => __( 'status of payment', 'bookly' ) );
    $codes[] = array( 'code' => 'payment_type',      'description' => __( 'payment type', 'bookly' ) );
    $codes[] = array( 'code' => 'status',            'description' => __( 'status of appointment', 'bookly' ) );
    $codes[] = array( 'code' => 'total_price',       'description' => __( 'total price of booking (sum of all cart items after applying coupon)', 'bookly' ) );

    $codes = Bookly\Lib\Proxy\Shared::prepareNotificationShortCodes( $codes );
}
Bookly\Lib\Utils\Common::codes( $codes );