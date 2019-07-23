<?php
namespace Bookly\Lib\Slots;

/**
 * Class Staff
 * @package Bookly\Lib\Slots
 */
class Staff
{
    /** @var Schedule */
    protected $schedule;
    /** @var Booking[] */
    protected $bookings;
    /** @var Service[] */
    protected $services;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->schedule = new Schedule();
        $this->bookings = array();
        $this->services = array();
    }

    /**
     * Get schedule.
     *
     * @return Schedule
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * Add booking.
     *
     * @param Booking $booking
     * @return $this
     */
    public function addBooking( Booking $booking )
    {
        $this->bookings[] = $booking;

        return $this;
    }

    /**
     * Get bookings.
     *
     * @return Booking[]
     */
    public function getBookings()
    {
        return $this->bookings;
    }

    /**
     * Add service.
     *
     * @param int $service_id
     * @param double $price
     * @param int $capacity_min
     * @param int $capacity_max
     * @return $this
     */
    public function addService( $service_id, $price, $capacity_min, $capacity_max )
    {
        $this->services[ $service_id ] = new Service( $price, $capacity_min, $capacity_max );

        return $this;
    }

    /**
     * Tells whether staff provides given service.
     *
     * @param int $service_id
     * @return bool
     */
    public function providesService( $service_id )
    {
        return isset ( $this->services[ $service_id ] );
    }

    /**
     * Get service by ID.
     *
     * @param int $service_id
     * @return Service
     */
    public function getService( $service_id )
    {
        return $this->services[ $service_id ];
    }
}