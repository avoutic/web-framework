<?php
require_once($includes.'geoip/geoip.inc');
require_once($includes.'geoip/geoipcity.inc');

class LocationBase
{
    protected $geo;

    function __construct()
    {
        global $includes;

        $this->geo = geoip_open($includes.'geoip/GeoLiteCity.dat', GEOIP_STANDARD);
    }
        
    function get_location_by_ip($ip)
    {
        $record = geoip_record_by_addr($this->geo, $ip);

        if (empty($record))
            return FALSE;

        return $record;
    }
};
