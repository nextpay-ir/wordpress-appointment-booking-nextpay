<?php
namespace Bookly\Lib\Slots;

/**
 * Class Service
 * @package Bookly\Lib\Slots
 */
class Service
{
    /** @var double */
    protected $price;
    /** @var int */
    protected $capacity_min;
    /** @var int */
    protected $capacity_max;

    /**
     * Constructor.
     *
     * @param double $price
     * @param int $capacity_min
     * @param int $capacity_max
     */
    public function __construct( $price, $capacity_min, $capacity_max )
    {
        $this->price        = (double) $price;
        $this->capacity_min = (int) $capacity_min;
        $this->capacity_max = (int) $capacity_max;
    }

    /**
     * Get price.
     *
     * @return float
     */
    public function price()
    {
        return $this->price;
    }

    /**
     * Get capacity min.
     *
     * @return int
     */
    public function capacityMin()
    {
        return $this->capacity_min;
    }

    /**
     * Get capacity max.
     *
     * @return int
     */
    public function capacityMax()
    {
        return $this->capacity_max;
    }
}