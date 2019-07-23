<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class StaffService
 * @package Bookly\Lib\Entities
 */
class StaffService extends Lib\Base\Entity
{
    protected static $table = 'ab_staff_services';

    protected static $schema = array(
        'id'            => array( 'format' => '%d' ),
        'staff_id'      => array( 'format' => '%d', 'reference' => array( 'entity' => 'Staff' ) ),
        'service_id'    => array( 'format' => '%d', 'reference' => array( 'entity' => 'Service' ) ),
        'price'         => array( 'format' => '%f', 'default' => '0' ),
        'capacity_min'  => array( 'format' => '%d', 'default' => '1' ),
        'capacity_max'  => array( 'format' => '%d', 'default' => '1' ),
        'deposit'       => array( 'format' => '%s', 'default' => '100%' ),
    );

    protected static $cache = array();

    /** @var Service */
    public $service = null;

}