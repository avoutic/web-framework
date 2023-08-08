<?php

namespace WebFramework\Core;

class InstrumentationWrapper
{
    protected static ?InstrumentationService $service = null;

    public static function setService(InstrumentationService $service): void
    {
        self::$service = $service;
    }

    public static function get(): InstrumentationService
    {
        if (self::$service === null)
        {
            self::$service = new NullInstrumentationService();
        }

        return self::$service;
    }
}
