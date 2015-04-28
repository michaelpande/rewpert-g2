<?php

/**
 * 	Converts date from ISO 8601 to the WordPress Standard (MySQL DateTime)
 *  Supports PHP 5.3 and up. (DateTime::Add) else 5.2 (DateTime requires 5.2)
 *
 * Notes:
 *      GMT and UTC is the same
 *
 * @author Michael Pande <michaelpande@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 *
 */

class DateParser{

    public static $NOT_SUPPORTED_ERROR = "The string is not a ISO8601 compliant date";

	public static function getGMTDateTime($date){
	
	    if($date == null){
            return null;
        }
		
		// Get offset
		preg_match('((\+|-)\w{2}:\w{2})', $date, $offset);
		
		
		if(isset($offset[0])){
			$date = str_replace($offset[0], "", $date); // Removes offset from date;
			$date  = new DateTime($date);
			
			
			$offset = $offset[0];
		
			$operation = substr($offset,0,1);
			preg_match_all('(\w{2})', $offset, $offset);
			
			// Add hours
			$hours = $offset[0];
			
			if($operation == "+")	
				date_sub($date, date_interval_create_from_date_string("$hours[0] hours"));
			if($operation == "-")	
				date_add($date, date_interval_create_from_date_string("$hours[0] hours"));
			
			// Add minutes
			$minutes = $offset[0];
			if($operation == "+")	
				date_sub($date, date_interval_create_from_date_string("$minutes[1] minutes"));
			if($operation == "-")	
				date_add($date, date_interval_create_from_date_string("$minutes[1] minutes"));
				
			return DateParser::readableDate($date);
		}
		
		try{
		    $date  = new DateTime($date);
        }catch(Exception $e){
            throw new InvalidArgumentException(DateParser::$NOT_SUPPORTED_ERROR);
        }

		return DateParser::readableDate($date);

	}
	
	
	// Removes offset and returns a simple datetime, will just return the time.
	public static function getNonGMT($date){

        if($date == null){
            return null;
        }

		// Get offset
		preg_match('((\+|-)\w{2}:\w{2})', $date, $offset);
		if(isset($offset[0])){
			$date = str_replace($offset[0], "", $date); // Removes offset from date;
		}
		return DateParser::getGMTDateTime($date);
	}
	
	
	
	private static function readableDate($date){
		return $date->format('Y-m-d H:i:s');
	}

}

