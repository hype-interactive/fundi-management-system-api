<?php

namespace App\Traits;

trait DateTimeTrait
{
    private static $time_start = ' 00:00:00';

    private static $time_end = ' 23:59:59';

    public static function todayFrom()
    {
        $date = date('Y-m-d');

        return $date.self::$time_start;
    }

    public static function todayTo()
    {
        $date = date('Y-m-d');

        return $date.self::$time_end;
    }

    public static function minusTimeFrom($duration)
    {
        $date = date('Y-m-d', strtotime($duration));

        return $date.self::$time_start;
    }

    public static function minusTimeTo($duration)
    {
        $date = date('Y-m-d', strtotime($duration));

        return $date.self::$time_end;
    }

    public static function getDateFrom($duration)
    {
        return date('Y-m-d', strtotime($duration)).self::$time_start;
    }

    public static function getDateTo($duration)
    {
        return date('Y-m-d', strtotime($duration)).self::$time_end;
    }
}
