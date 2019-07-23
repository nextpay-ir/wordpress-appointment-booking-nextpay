<?php
namespace Bookly\Lib\Slots;

/**
 * Class Generator
 * @package Bookly\Lib\Slots
 */
class Generator implements \Iterator
{
    /** @var Staff[] */
    protected $staff_members;
    /** @var Schedule[] */
    protected $staff_schedule;
    /** @var int */
    protected $slot_length;
    /** @var DatePoint */
    protected $dp;
    /** @var int */
    protected $srv_id;
    /** @var int */
    protected $srv_duration;
    /** @var int */
    protected $srv_duration_days;
    /** @var int */
    protected $srv_padding_left;
    /** @var int */
    protected $srv_padding_right;
    /** @var int */
    protected $nop;
    /** @var int */
    protected $extras_duration;
    /** @var Range  Requested time range */
    protected $time_limit;
    /** @var static */
    protected $next_generator;
    /** @var RangeCollection */
    protected $next_slots;
    /** @var RangeCollection */
    protected $past_slots;

    /**
     * Constructor.
     *
     * @param Staff[] $staff_members  Array of Staff objects indexed by staff ID
     * @param Schedule[] $service_schedule  Array of Schedule objects indexed by service ID
     * @param int $slot_length
     * @param int $service_id
     * @param int $service_duration
     * @param int $service_padding_left
     * @param int $service_padding_right
     * @param int $nop  Number of persons
     * @param int $extras_duration
     * @param DatePoint $start_dp
     * @param string $time_from  Limit results by start time
     * @param string $time_to  Limit results by end time
     * @param self|null $next_generator
     */
    public function __construct(
        array $staff_members,
        array $service_schedule,
        $slot_length,
        $service_id,
        $service_duration,
        $service_padding_left,
        $service_padding_right,
        $nop,
        $extras_duration,
        DatePoint $start_dp,
        $time_from,
        $time_to,
        $next_generator
    )
    {
        $this->staff_members      = array();
        $this->staff_schedule     = array();
        $this->dp                 = $start_dp->modify( 'today' );
        $this->srv_id             = (int) $service_id;
        $this->srv_duration       = (int) min( $service_duration, DAY_IN_SECONDS );
        $this->srv_duration_days  = (int) ( $service_duration / DAY_IN_SECONDS );
        $this->srv_padding_left   = (int) $service_padding_left;
        $this->srv_padding_right  = (int) $service_padding_right;
        $this->slot_length        = (int) ( $this->srv_duration_days ? DAY_IN_SECONDS : min( $slot_length, DAY_IN_SECONDS ) );
        $this->nop                = (int) $nop;
        $this->extras_duration    = (int) ( $this->srv_duration_days < 1 ? $extras_duration : 0 );
        $this->time_limit         = Range::fromTimes( $time_from, $time_to );
        $this->next_generator     = $next_generator;

        // Pick only those staff members who provides the service
        // and who can serve the requested number of persons.
        foreach ( $staff_members as $staff_id => $staff ) {
            // Check that staff provides the service.
            if ( $staff->providesService( $this->srv_id ) ) {
                // Check that requested number of persons meets service capacity.
                $service = $staff->getService( $this->srv_id );
                if ( $service->capacityMax() >= $this->nop && $service->capacityMin() <= $this->nop ) {
                    $this->staff_members[ $staff_id ] = $staff;
                    // Prepare staff schedule.
                    $schedule = $staff->getSchedule();
                    if ( isset ( $service_schedule[ $service_id ] ) ) {
                        $schedule = $schedule->intersect( $service_schedule[ $service_id ] );
                    }
                    $this->staff_schedule[ $staff_id ] = $schedule;
                }
            }
        }

        // Init next generator.
        if ( $this->next_generator ) {
            $this->next_slots = new RangeCollection();
            $this->next_generator->rewind();
        }

        // Init slots collection for multi-day services.
        if ( $this->srv_duration_days > 1 ) {
            $this->past_slots = new RangeCollection();
        }
    }

    /**
     * @inheritdoc
     * @return RangeCollection
     */
    public function current()
    {
        $result = new RangeCollection();

        // Loop through all staff members.
        foreach ( $this->staff_members as $staff_id => $staff ) {
            $schedule = $this->staff_schedule[ $staff_id ];
            // Check that staff is not off.
            if ( ! $schedule->isDayOff( $this->dp ) ) {
                // Create ranges from staff schedule.
                $ranges = $this->srv_duration_days
                    ? $schedule->getAllDayRange( $this->dp, $this->srv_id, $staff_id )
                    : $schedule->getRanges( $this->dp, $this->srv_id, $staff_id, $this->time_limit );

                // Create booked ranges from staff bookings.
                $ranges = $this->_mapStaffBookings( $ranges, $staff );

                // Find slots.
                foreach ( $ranges->all() as $range ) {
                    // With available ranges we need to adjust their length.
                    if ( $range->state() == Range::AVAILABLE ) {
                        // Shorten range by service and extras duration.
                        $range = $range->transform( null, - $this->srv_duration - $this->extras_duration );
                        if ( ! $range->valid() ) {
                            // If range is not valid skip it.
                            continue;
                        }
                        // Enlarge range by slot length.
                        $range = $range->transform( null, $this->slot_length );
                    }
                    // Split range into slots.
                    foreach ( $range->split( $this->slot_length )->all() as $slot ) {
                        if ( $slot->length() < $this->slot_length ) {
                            // Skip slots with not enough length.
                            continue;
                        }
                        $timestamp = $slot->start()->value()->getTimestamp();
                        // Decide whether to add slot or skip it.
                        if ( $result->has( $timestamp ) ) {
                            // If result already has this timestamp...
                            if ( $slot->fullyBooked() ) {
                                // Skip the slot if it is fully booked.
                                continue;
                            } else {
                                $ex_slot = $result->get( $timestamp );
                                if ( $ex_slot->notFullyBooked() ) {
                                    // If existing slot is not fully booked...
                                    $staff1 = $this->staff_members[ $ex_slot->staffId() ];
                                    $staff2 = $this->staff_members[ $slot->staffId() ];
                                    if ( $staff2->getService( $this->srv_id )->price() < $staff1->getService( $this->srv_id )->price() ) {
                                        // Skip the slot if its price less then existing price.
                                        continue;
                                    } else {
                                        // Otherwise replace staff ID in the existing slot.
                                        $slot = $ex_slot->replaceStaffId( $staff_id );
                                    }
                                }
                            }
                        }
                        // For consecutive bookings try to find a next slot.
                        if ( $this->next_generator && ! $slot->nextSlot() && $slot->notFullyBooked() ) {
                            if ( ( $slot = $this->_tryFindNextSlot( $slot ) ) == false ) {
                                // Skip it if no next slot was found.
                                continue;
                            }
                        }
                        // For multi-day services try to find available day in the past.
                        if ( $this->srv_duration_days > 1 && $slot->state() == Range::AVAILABLE ) {
                            if ( ( $slot = $this->_tryFindPastSlot( $timestamp, $slot ) ) == false ) {
                                // Skip it if no past slot was found.
                                continue;
                            }
                        }
                        // Add slot to result.
                        $result->put( $timestamp, $slot );
                    }
                }
            }
        }

        return $result->ksort();
    }

    /**
     * Create fully/partially booked ranges from staff bookings.
     *
     * @param RangeCollection $ranges
     * @param Staff $staff
     * @return RangeCollection
     */
    private function _mapStaffBookings( RangeCollection $ranges, $staff )
    {
        $max_capacity = $staff->getService( $this->srv_id )->capacityMax();

        foreach ( $staff->getBookings() as $booking ) {
            // Take in account booking and service padding.
            $range_to_remove = $booking->getRangeWithPadding()->transform( - $this->srv_padding_right, $this->srv_padding_left );
            // Remove booking from ranges.
            $new_ranges = new RangeCollection();
            $removed    = new RangeCollection();
            foreach ( $ranges->all() as $r ) {
                if ( $r->overlaps( $range_to_remove ) ) {
                    // Make sure that removed range will have length of a multiple of slot length.
                    $new_ranges = $new_ranges->merge( $r->subtract(
                        ( $extra_length = $range_to_remove->end()->diff( $r->start() ) % $this->slot_length )
                            ? $range_to_remove->transform( null, $this->slot_length - $extra_length )
                            : $range_to_remove,
                        $removed_range
                    ) );
                    /** @var Range $removed_range */
                    if ( $removed_range ) {
                        $removed->push( $removed_range );
                    }
                } else {
                    $new_ranges->push( $r );
                }
            }
            $ranges = $new_ranges;
            // If some ranges were removed add them back with appropriate state.
            if ( $removed->isNotEmpty() ) {
                $data = $removed->get( 0 )->data()->replaceState( Range::FULLY_BOOKED );
                foreach ( $removed->all() as $range ) {
                    $ranges->push( $range->replaceData( $data ) );
                }
                // Handle partially booked appointments (when number of persons is less than max capacity).
                if (
                    $booking->getServiceId() == $this->srv_id &&
                    $booking->getNop() <= $max_capacity - $this->nop &&
                    $booking->getExtrasDuration() >= $this->extras_duration
                ) {
                    $booking_range = $booking->getRange();
                    foreach ( $removed->all() as $range ) {
                        // Find range which contains booking start point.
                        if ( $range->contains( $booking_range->start() ) ) {
                            // Create partially booked range and add it to collection.
                            $ranges->push(
                                $booking_range
                                    ->resize( $this->slot_length )
                                    ->replaceData( $range->data()->replaceState( Range::PARTIALLY_BOOKED ) )
                            );
                            break;
                        }
                    }
                }
            }
        }

        return $ranges;
    }

    /**
     * Try to find next slot for consecutive bookings.
     *
     * @param Range $slot
     * @return Range|false
     */
    private function _tryFindNextSlot( Range $slot )
    {
        $next_start = $slot->start()->modify( $this->srv_duration + $this->extras_duration );
        $padding = $this->srv_padding_right + $this->next_generator->srv_padding_left;
        // There are 2 possible options:
        // 1. next service is done by another staff, then do not take into account padding
        // 2. next service is done by the same staff, then count padding
        $next_slot = $this->_findNextSlot( $next_start );
        if (
            $next_slot == false ||
            $next_slot->fullyBooked() ||
            $padding != 0 && $next_slot->staffId() == $slot->staffId()
        ) {
            $next_slot = $this->_findNextSlot( $next_start->modify( $padding ) );
            if (
                $next_slot == false ||
                $next_slot->fullyBooked() ||
                $next_slot->staffId() != $slot->staffId()
            ) {
                $next_slot = null;
            }
        }
        if ( $next_slot ) {
            // Connect slots with each other.
            $slot = $slot->replaceNextSlot( $next_slot );
        } else {
            // If no next slot was found then return false.
            return false;
        }

        return $slot;
    }

    /**
     * Try to find a valid slot in the past for multi-day services.
     *
     * @param int $timestamp
     * @param Range $slot
     * @return Range|bool
     */
    private function _tryFindPastSlot( &$timestamp, Range $slot )
    {
        // Store slot for further reference.
        // @todo In theory we can hold just $this->srv_duration_days slots in the past.
        $this->past_slots->put( $timestamp, $slot );
        // Check if there are enough valid days for service duration in the past.
        for ( $d = 1; $d < $this->srv_duration_days; ++ $d ) {
            $timestamp -= DAY_IN_SECONDS;
            if (
                ! $this->past_slots->has( $timestamp ) ||
                $this->past_slots->get( $timestamp )->staffId() != $slot->staffId()
            ) {
                return false;
            }
        }
        // Replace slot with one from the day when service starts.
        $slot = $this->past_slots->get( $timestamp )->replaceNextSlot( $slot->nextSlot() );

        return $slot;
    }

    /**
     * Find next slot for consecutive bookings.
     *
     * @param IPoint $start
     * @return Range|false
     */
    private function _findNextSlot( IPoint $start )
    {
        while (
            $this->next_generator->valid() &&
            // Do search only while next generator is producing slots earlier than the requested point.
            $start->modify( $this->next_generator->srv_duration_days . ' days' )->gt( $this->next_generator->key() )
        ) {
            $this->next_slots = $this->next_slots->union( $this->next_generator->current() );
            $this->next_generator->next();
        }

        return $this->next_slots->get( $start->value()->getTimestamp() );
    }

    /**
     * Get service duration in days.
     *
     * @return int
     */
    public function serviceDurationInDays()
    {
        return $this->srv_duration_days;
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        // Start one day earlier to cover night shifts.
        $this->dp = $this->dp->modify( '-1 day' );
    }

    /**
     * @inheritdoc
     * @return DatePoint
     */
    public function key()
    {
        return $this->dp;
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $this->dp = $this->dp->modify( '+1 day' );
    }

    /**
     * Infinite search.
     *
     * @return bool
     */
    public function valid()
    {
        return true;
    }
}