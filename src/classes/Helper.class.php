<?php

class Helper
{

    public function __construct()
    {
    }

    public function returnNumber( $value )
    {
        return is_numeric($value) ? intval($value) : 0;
    }

    public function returnCountable( $value )
    {
        return is_countable($value) ? $value : [];
    }
}
