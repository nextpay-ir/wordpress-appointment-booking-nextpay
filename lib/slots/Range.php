<?php
namespace Bookly\Lib\Slots;

/**
 * Class Range
 * @package Bookly\Lib\Slots
 */
class Range
{
    const AVAILABLE        = 1;
    const PARTIALLY_BOOKED = 2;
    const FULLY_BOOKED     = 3;

    /** @var IPoint */
    protected $start;

    /** @var IPoint */
    protected $end;

    /** @var RangeData */
    protected $data;

    /**
     * Constructor.
     *
     * @param IPoint $start
     * @param IPoint $end
     * @param RangeData $data
     */
    public function __construct( IPoint $start, IPoint $end, RangeData $data = null )
    {
        $this->start = $start;
        $this->end   = $end;
        $this->data  = $data;
    }

    /**
     * Create Range object from date strings.
     *
     * @param string $start  Format Y-m-d H:i[:s]
     * @param string $end    Format Y-m-d H:i[:s]
     * @param RangeData $data
     * @return static
     */
    public static function fromDates( $start, $end, RangeData $data = null )
    {
        return new static( DatePoint::fromStr( $start ), DatePoint::fromStr( $end ), $data );
    }

    /**
     * Create Range object from time strings.
     *
     * @param string $start  Format H:i[:s]
     * @param string $end    Format H:i[:s]
     * @param RangeData $data
     * @return static
     */
    public static function fromTimes( $start, $end, RangeData $data = null )
    {
        return new static( TimePoint::fromStr( $start ), TimePoint::fromStr( $end ), $data );
    }

    /**
     * Get range start.
     *
     * @return IPoint
     */
    public function start()
    {
        return $this->start;
    }

    /**
     * Ger range end.
     *
     * @return IPoint
     */
    public function end()
    {
        return $this->end;
    }

    /**
     * Get range data.
     *
     * @return RangeData
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Get range length.
     *
     * @return int
     */
    public function length()
    {
        return $this->end->diff( $this->start );
    }

    /**
     * Tells whether range is valid (start point is less then end point).
     *
     * @return bool
     */
    public function valid()
    {
        return $this->start->lte( $this->end );
    }

    /**
     * Tells whether range contains specific point.
     *
     * @param IPoint $point
     * @return bool
     */
    public function contains( IPoint $point )
    {
        return $this->start->lte( $point ) && $this->end->gte( $point );
    }

    /**
     * Tells whether two ranges are equal.
     *
     * @param self $range
     * @return bool
     */
    public function equals( self $range )
    {
        return $this->start->eq( $range->start() ) && $this->end->eq( $range->end() );
    }

    /**
     * Tells whether two ranges overlap.
     *
     * @param self $range
     * @return bool
     */
    public function overlaps( self $range )
    {
        return $this->start->lt( $range->end() ) && $this->end->gt( $range->start() );
    }

    /**
     * Tells whether range contains all points of another range.
     *
     * @param Range $range
     * @return bool
     */
    public function wraps( self $range )
    {
        return $this->start->lte( $range->start() ) && $this->end->gte( $range->end() );
    }

    /**
     * Computes the intersection between two ranges.
     *
     * @param self $range
     * @return static|null
     */
    public function intersect( self $range )
    {
        return $this->overlaps( $range )
            ? new static( self::_max( $this->start, $range->start() ), self::_min( $this->end, $range->end() ), $this->data )
            : null;
    }

    /**
     * Computes the result of subtraction of two ranges.
     *
     * @param self $range
     * @param self $removed
     * @return RangeCollection
     */
    public function subtract( self $range, self &$removed = null )
    {
        $collection = new RangeCollection();

        $removed = $this->intersect( $range );

        if ( $this->start->lt( $range->start() ) ) {
            $collection->push( new static( $this->start, self::_min( $this->end, $range->start() ), $this->data ) );
        }

        if ( $range->end()->lt( $this->end ) ) {
            $collection->push( new static( self::_max( $this->start, $range->end() ), $this->end, $this->data ) );
        }

        return $collection;
    }

    /**
     * Split range into smaller ranges.
     *
     * @param mixed $length
     * @return RangeCollection
     */
    public function split( $length )
    {
        $collection = new RangeCollection();

        $frame = $this->resize( $length );

        while ( $range = $this->intersect( $frame ) ) {
            $collection->push( $range );
            $frame = $frame->transform( $length, $length );
        };

        return $collection;
    }

    /**
     * Computes the result of modifying the edge points according to given values.
     *
     * @param mixed $modify_start
     * @param mixed $modify_end
     * @return static
     */
    public function transform( $modify_start, $modify_end )
    {
        return new static( $this->start->modify( $modify_start ), $this->end->modify( $modify_end ), $this->data );
    }

    /**
     * Computes the result of moving the end point to given length from the start point.
     *
     * @param mixed $length
     * @return static
     */
    public function resize( $length )
    {
        return new static( $this->start, $this->start->modify( $length ), $this->data );
    }

    /**
     * Create a copy of the range with new data.
     *
     * @param RangeData $new_data
     * @return static
     */
    public function replaceData( RangeData $new_data )
    {
        return new static( $this->start, $this->end, $new_data );
    }

    /**
     * Get max point.
     *
     * @param IPoint $x
     * @param IPoint $y
     * @return IPoint
     */
    private static function _max( IPoint $x, IPoint $y )
    {
        return $x->gte( $y ) ? $x : $y;
    }

    /**
     * Get min point.
     *
     * @param IPoint $x
     * @param IPoint $y
     * @return IPoint
     */
    private static function _min( IPoint $x, IPoint $y )
    {
        return $x->lte( $y ) ? $x : $y;
    }

    /******************************************************************************************************************
     * RangeData related methods.                                                                                     *
     ******************************************************************************************************************/

    /**
     * Get service ID.
     *
     * @return int
     */
    public function serviceId()
    {
        return $this->data->serviceId();
    }

    /**
     * Get staff ID.
     *
     * @return int
     */
    public function staffId()
    {
        return $this->data->staffId();
    }

    /**
     * Get state.
     *
     * @return int
     */
    public function state()
    {
        return $this->data->state();
    }

    /**
     * Get next slot.
     *
     * @return static
     */
    public function nextSlot()
    {
        return $this->data->nextSlot();
    }

    /**
     * Create a copy of the data with new staff ID.
     *
     * @param int $new_staff_id
     * @return static
     */
    public function replaceStaffId( $new_staff_id )
    {
        return $this->replaceData( $this->data->replaceStaffId( $new_staff_id ) );
    }

    /**
     * Create a copy of the data with new state.
     *
     * @param int $new_state
     * @return static
     */
    public function replaceState( $new_state )
    {
        return $this->replaceData( $this->data->replaceState( $new_state ) );
    }

    /**
     * Create a copy of the data with new next slot.
     *
     * @param Range|null $new_next_slot
     * @return static
     */
    public function replaceNextSlot( $new_next_slot )
    {
        return $this->replaceData( $this->data->replaceNextSlot( $new_next_slot ) );
    }

    /**
     * Tells whether range's state is fully booked
     *
     * @return bool
     */
    public function fullyBooked()
    {
        return $this->data->state() == self::FULLY_BOOKED;
    }

    /**
     * Tells whether range's state is not fully booked.
     *
     * @return bool
     */
    public function notFullyBooked()
    {
        return $this->data->state() != self::FULLY_BOOKED;
    }

    /**
     * Build slot data.
     *
     * @return array
     */
    public function buildSlotData()
    {
        $result = array( array( $this->serviceId(), $this->staffId(), $this->start->value()->format( 'Y-m-d H:i:s' ) ) );

        if ( $this->data()->hasNextSlot() ) {
            $result = array_merge( $result, $this->nextSlot()->buildSlotData() );
        }

        return $result;
    }
}