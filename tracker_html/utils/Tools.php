<?php

class Tools
{
    public static function visitCode($time)
    {
        return sprintf('%03s%02s', date('z', $time), substr(date('Y', $time), 2, 2));
    }

    public static function visitDate($time)
    {
        return date('Y-m-d H:i:s', $time);
    }
}
