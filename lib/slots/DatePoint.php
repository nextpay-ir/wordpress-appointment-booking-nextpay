<?php
namespace Bookly\Lib\Slots;

use Bookly\Lib\Config;

/**
 * Class DatePoint
 * @package Bookly\Lib\Slots
 */
class DatePoint implements IPoint
{
    /** @var string */
    protected static $wp_timezone = null;
    /** @var string */
    public static $client_timezone = null;

    /** @var \DateTime */
    protected $datetime;

    /**
     * Constructor.
     * @param \DateTime $datetime
     */
    public function __construct( \DateTime $datetime )
    {
        $this->datetime = $datetime;
    }

    /**
     * Create DatePoint with the current time WP time zone.
     *
     * @return static
     */
    public static function now()
    {
        return new static( date_timestamp_set( date_create( static::_wpTz() ), time() ) );
    }

    /**
     * Create DatePoint from string in WP time zone.
     *
     * @param string $date_str  Format Y-m-d H:i[:s]
     * @return static
     */
    public static function fromStr( $date_str )
    {
        return new static( date_create( $date_str . ' ' . static::_wpTz() ) );
    }

    /**
     * Create DatePoint from string in client time zone.
     *
     * @param string $date_str  Format Y-m-d H:i[:s]
     * @return static
     */
    public static function fromStrInClientTz( $date_str )
    {
        return new static( date_create( $date_str . ' ' . static::_clientTz() ) );
    }

    /**
     * Get value.
     *
     * @return \DateTime
     */
    public function value()
    {
        return $this->datetime;
    }

    /**
     * Tells whether two points are equal.
     *
     * @param IPoint $point
     * @return bool
     */
    public function eq( IPoint $point )
    {
        return $this->datetime == $point->value();
    }

    /**
     * Tells whether two points are not equal.
     *
     * @param IPoint $point
     * @return bool
     */
    public function neq( IPoint $point )
    {
        return $this->datetime != $point->value();
    }

    /**
     * Tells whether one point is less than another.
     *
     * @param IPoint $point
     * @return bool
     */
    public function lt( IPoint $point )
    {
        return $this->datetime < $point->value();
    }

    /**
     * Tells whether one point is less or equal than another.
     *
     * @param IPoint $point
     * @return bool
     */
    public function lte( IPoint $point )
    {
        return $this->datetime <= $point->value();
    }

    /**
     * Tells whether one point is greater than another.
     *
     * @param IPoint $point
     * @return bool
     */
    public function gt( IPoint $point )
    {
        return $this->datetime > $point->value();
    }

    /**
     * Tells whether one point is greater or equal than another.
     *
     * @param IPoint $point
     * @return bool
     */
    public function gte( IPoint $point )
    {
        return $this->datetime >= $point->value();
    }

    /**
     * Computes difference between two points.
     *
     * @param IPoint $point
     * @return int
     */
    public function diff( IPoint $point )
    {
        return $this->datetime->getTimestamp() - $point->value()->getTimestamp();
    }

    /**
     * Modify point.
     *
     * @param mixed $value
     * @return static
     */
    public function modify( $value )
    {
        if ( is_numeric( $value ) ) {
            if ( $value ) {
                return new static( date_modify( clone $this->datetime, (int) $value . ' seconds' ) );
            }
        } elseif ( is_string( $value ) ) {
            return new static( date_modify( clone $this->datetime, $value ) );
        }

        return $this;
    }

    /**
     * Returns date formatted according to given format.
     *
     * @param string $format
     * @return string
     */
    public function format( $format )
    {
        return $this->datetime->format( $format );
    }

    /**
     * Returns date formatted with date_i18n according to given format.
     *
     * @param string $format
     * @return string
     */
    public function formatI18n( $format )
    {
        return date_i18n( $format, $this->datetime->getTimestamp() + $this->datetime->getOffset());
    }

    /**
     * Returns date formatted with date_i18n.
     *
     * @return string
     */
    public function formatI18nDate()
    {
        return $this->formatI18n( get_option( 'date_format' ) );
    }

    /**
     * Returns time formatted with date_i18n.
     *
     * @return string
     */
    public function formatI18nTime()
    {
        return $this->formatI18n( get_option( 'time_format' ) );
    }

    /**
     * Convert point to WP time zone.
     *
     * @return static
     */
    public function toWpTz()
    {
        return new static( date_timestamp_set( date_create( static::_wpTz() ), $this->datetime->getTimestamp() ) );
    }

    /**
     * Convert point to client time zone.
     *
     * @return static
     */
    public function toClientTz()
    {
        return new static( date_timestamp_set( date_create( static::_clientTz() ), $this->datetime->getTimestamp() ) );
    }

    /**
     * Get WP time zone.
     *
     * @return string
     */
    protected static function _wpTz()
    {
        if ( static::$wp_timezone === null ) {
            static::$wp_timezone = Config::getWPTimeZone();
        }

        return static::$wp_timezone;
    }

    /**
     * Get client time zone.
     *
     * @return string
     */
    protected static function _clientTz()
    {
        if ( static::$client_timezone === null ) {
            static::$client_timezone = static::_wpTz();
        }

        return static::$client_timezone;
    }
}