<?php

// (c) MIT License - See LICENSE for details
 
namespace Report;

/**
* @author Robbie Denton - rjdenton93@gmail.com
*/

trait DateConversionTrait {
	
  	function convertDateToTimeStamp($date)
    {

        $seconds_diff = 11644473600;
        $nano_convert = 10000000;

        return number_format((($date->getTimestamp() + $seconds_diff) * $nano_convert), 0, '.', '');

    }

    function convertTimestampToDate($wintime){
        return $wintime / 10000000 - 11644477200;   
    }

    function convertLdapTimeToDate($timestamp)

    {

     preg_match("/^(\d+).?0?(([+-]\d\d)(\d\d)|Z)$/i", $timestamp, $matches);

        if (!isset($matches[1]) || !isset($matches[2])) {
            throw new \RuntimeException(sprintf('Invalid timestamp encountered: %s', $timestamp));
        }

        $tz = (strtoupper($matches[2]) == 'Z') ? 'UTC' : $matches[3].':'.$matches[4];

        return new \DateTime($matches[1], new \DateTimeZone($tz));

    }

}


?>