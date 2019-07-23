<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class Stat
 * @package Bookly\Lib\Entities
 */
class Stat extends Lib\Base\Entity
{
    protected static $table = 'ab_stats';

    protected static $schema = array(
        'id'       => array( 'format' => '%d' ),
        'name'     => array( 'format' => '%s' ),
        'value'    => array( 'format' => '%s' ),
        'created'  => array( 'format' => '%s' ),
    );

    protected static $cache = array();

    /**
     * @param string $variable
     * @param int    $affected
     */
    public static function record( $variable, $affected )
    {
        if ( $affected > 0 ) {
            $parameters = array(
                'name'     => $variable,
                'created'  => substr( current_time( 'mysql' ), 0, 10 ),
            );
            $stat       = new Stat();
            $stat->loadBy( $parameters );
            if ( ! $stat->isLoaded() ) {
                $stat->setFields( $parameters );
            }
            $stat
                ->set( 'value', ( (int) $stat->get( 'value' ) ) + $affected )
                ->save();
        }
    }
}