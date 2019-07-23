<?php
namespace BooklyServiceExtras\Lib\Entities;

use Bookly\Lib;

/**
 * Class ServiceExtra
 *
 * @package BooklyServiceExtras\Lib\Entities
 */
class ServiceExtra extends Lib\Base\Entity
{
    protected static $table = 'ab_service_extras';

    protected static $schema = array(
        'id'            => array( 'format' => '%d' ),
        'service_id'    => array( 'format' => '%d' ),
        'attachment_id' => array( 'format' => '%d' ),
        'title'         => array( 'format' => '%s' ),
        'duration'      => array( 'format' => '%d', 'default' => 900 ),
        'price'         => array( 'format' => '%.2f', 'default' => '0' ),
        'position'      => array( 'format' => '%d', 'default' => 9999 ),
        'max_quantity'  => array( 'format' => '%d', 'default' => 1 ),
    );

    protected static $cache = array();

    /**
     * Get title (if empty returns "Untitled").
     *
     * @return string
     */
    public function getTitle()
    {
        return Lib\Utils\Common::getTranslatedString( 'service_extra_' . $this->get( 'id' ), $this->get( 'title' ) != '' ? $this->get( 'title' ) : __( 'Untitled', 'bookly' ) );
    }

    public function save()
    {
        $return = parent::save();
        if ( $this->isLoaded() ) {
            // Register string for translate in WPML.
            do_action( 'wpml_register_single_string', 'bookly', 'service_extra_' . $this->get( 'id' ), $this->get( 'title' ) );
        }

        return $return;
    }

}