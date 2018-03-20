<?php
/**
 *  Class for calculating how long ago threads/posts were made in a friendly format.
 */

/**
 *  Class for calculating how long ago threads/posts were made in a friendly format.
 *  
 *  @author Jacob Landowski
 */
abstract class TimeCalculate 
{
    public static function getTimeSinceCreation($dateTime)
    {
        $dateTime = new DateTime($dateTime);
        $timeZone = new DateTimeZone('America/Los_Angeles');
        $dateTime->setTimeZone($timeZone);
        $secondsSince = strtotime(date('Y-m-d H:i:s', time())) - strtotime($dateTime->format('Y-m-d H:i:s'));
        $minutesSince = (int) ($secondsSince / 60);
        $hoursSince   = (int) ($minutesSince / 60);
        $daysSince    = (int) ($hoursSince / 24);
        $monthsSince  = (int) ($daysSince / 30.436875);
        $yearsSince   = (int) ($monthsSince / 12);

        if($yearsSince   > 0) return TimeCalculate::_buildMessage($yearsSince,   'year');
        if($monthsSince  > 0) return TimeCalculate::_buildMessage($monthsSince,  'month');
        if($daysSince    > 0) return TimeCalculate::_buildMessage($daysSince,    'day');
        if($hoursSince   > 0) return TimeCalculate::_buildMessage($hoursSince,   'hour');
        if($minutesSince > 0) return TimeCalculate::_buildMessage($minutesSince, 'minute');
        if($secondsSince > 0) return TimeCalculate::_buildMessage($secondsSince, 'second');
        else return TimeCalculate::_buildMessage(0, 'second');
    }

    private static function _buildMessage($amt, $unit)
    {
        return "$amt ${unit}" . ($amt == 1 ? '' : 's') . ' ago';
    }
}