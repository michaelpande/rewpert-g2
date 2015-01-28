<?php
/*

	Converts date from ISO 8601 to the WordPress Standard (MySQL DateTime)
	Supports PHP 5.3 and up. (DateTime::Add) else 5.2 (DateTime requires 5.2)
	
	Some of the tested formats: 
		YYYY-MM-DDThh:mm:ss
		±YYYY-MM-DDThh:mm:ss±hh:mm (Supports dates before the common era (BCE/BC)

	Important notes:
		The GMT time is always the same as UTC
		
*/

//DateParser::testGMT();
//DateParser::testNonGMT();

class DateParser{


	// Tests GMT method and returns a boolean 
	public static function testGMT(){

		// Arrange
		$date1 = "2015-01-28";				// Only date
		$correct1 = DateParser::readableDate(new DateTime("2015-01-28 00:00:00")); 
		
		$date2 = "2015-01-28T11:38:30+00:00"; // Plus zero from UTC
		$correct2 = DateParser::readableDate(new DateTime("2015-01-28 11:38:30"));
		
		$date3 = "2015-01-28T16:14:49-01:30";  // Plus from UTC
		$correct3 = DateParser::readableDate(new DateTime("2015-01-28 14:44:49"));
		
		$date4 = "2015-01-28T18:00:00-02:30"; // Minus from UTC
		$correct4 = DateParser::readableDate(new DateTime("2015-01-28 15:30:00"));
		
		$date5 = "2013-08-21T16:38:18+02:00"; // From NML-G2 Document
		$correct5 = DateParser::readableDate(new DateTime("2013-08-21 18:38:18"));
		
		$date6 = "2013-08-25T18:11:07.000Z"; // Timezone added
		$correct6 = DateParser::readableDate(new DateTime("2013-08-25 18:11:07"));
		
		$date7 = "-0004-01-27";				// Negative date (4 BCE)
		$correct7 = DateParser::readableDate(new DateTime("-0004-01-27 00:00:00")); // Correct?
		
		$date8 = "+0004-01-27";				// Positive date
		$correct8 = DateParser::readableDate(new DateTime("0004-01-27 00:00:00")); // Correct?
		
		
		$date9 = "2014-11-21T16:25:32-05:00"; // From NML-G2 Document
		$correct9 = DateParser::readableDate(new DateTime("2014-11-21 11:25:32"));
		
		// Test for endring av dag basert på minus og pluss
		
		
		// Act
		$date1 = DateParser::getGMTDateTime($date1);
		$date2 = DateParser::getGMTDateTime($date2);
		$date3 = DateParser::getGMTDateTime($date3);
		$date4 = DateParser::getGMTDateTime($date4);
		$date5 = DateParser::getGMTDateTime($date5);
		$date6 = DateParser::getGMTDateTime($date6);
		$date7 = DateParser::getGMTDateTime($date7);
		$date8 = DateParser::getGMTDateTime($date8);
		$date9 = DateParser::getGMTDateTime($date9);
		
		// Assert
		$success = true;
		
		if($date1 != $correct1){
			echo "<br>Date 1 failed<br> ". $date1 ." != ". $correct1."<br>";
			$success = false;
		}
		
		
		if($date2 != $correct2){
			echo "<br>Date 2 failed<br> ". $date2 ." != ". $correct2."<br>";
			$success = false;
		}

		
		if($date3!= $correct3){
			echo "<br>Date 3 failed<br> ". $date3 ." != ". $correct3."<br>";
			$success = false;
		}

		if($date4!= $correct4){
			echo "<br>Date 4 failed<br> ". $date4 ." != ". $correct4."<br>";
			$success = false;
		}

		
		if($date5 != $correct5){
			echo "<br>Date 5 failed<br> ". $date5 ." != ". $correct5."<br>";
			$success = false;
		}

		if($date6 != $correct6){
			echo "<br>Date 6 failed<br> ". $date6 ." != ". $correct6."<br>";
			$success = false;
		}

		if($date7 != $correct7){
			echo "<br>Date 7 failed<br> ". $date7 ." != ". $correct7."<br>";
			$success = false;
		}
		
		if($date8 != $correct8){
			echo "<br>Date 8 failed<br> ". $date8 ." != ". $correct8."<br>";
			$success = false;
		}
		
		if($date9 != $correct9){
			echo "<br>Date 9 failed<br> ". $date9 ." != ". $correct9."<br>";
			$success = false;
		}
		

		var_dump($success);
		return $success;
		
		
	}
	
	// Tests GMT method and returns a boolean 
	public static function testNonGMT(){

		// Arrange
		$date1 = "2015-01-28";				// Only date
		$correct1 = DateParser::readableDate(new DateTime("2015-01-28 00:00:00")); 
		
		$date2 = "2015-01-28T11:38:30+00:00"; // Plus zero from UTC
		$correct2 = DateParser::readableDate(new DateTime("2015-01-28 11:38:30"));
		
		$date3 = "2015-01-28T16:14:49-01:30";  // Plus from UTC
		$correct3 = DateParser::readableDate(new DateTime("2015-01-28 16:14:49"));
		
		$date4 = "2015-01-28T18:00:00-02:30"; // Minus from UTC
		$correct4 = DateParser::readableDate(new DateTime("2015-01-28 18:00:00"));
		
		$date5 = "2013-08-21T16:38:18+02:00"; // From NML-G2 Document
		$correct5 = DateParser::readableDate(new DateTime("2013-08-21 16:38:18"));
		
		$date6 = "2014-08-25T18:11:07.000Z"; // Timezone added
		$correct6 = DateParser::readableDate(new DateTime("2014-08-25 18:11:07"));
		
		$date7 = "-0004-01-27";				// Negative date (4 BCE)
		$correct7 = DateParser::readableDate(new DateTime("-0004-01-27 00:00:00")); // Correct?
		
		$date8 = "+0004-01-27";				// Positive date
		$correct8 = DateParser::readableDate(new DateTime("0004-01-27 00:00:00")); // Correct?
		
		
		$date9 = "2014-11-22T16:25:32-05:00"; // From NML-G2 Document
		$correct9 = DateParser::readableDate(new DateTime("2014-11-22 16:25:32"));
		
		// Test for endring av dag basert på minus og pluss
		
		
		// Act
		$date1 = DateParser::getNonGMT($date1);
		$date2 = DateParser::getNonGMT($date2);
		$date3 = DateParser::getNonGMT($date3);
		$date4 = DateParser::getNonGMT($date4);
		$date5 = DateParser::getNonGMT($date5);
		$date6 = DateParser::getNonGMT($date6);
		$date7 = DateParser::getNonGMT($date7);
		$date8 = DateParser::getNonGMT($date8);
		$date9 = DateParser::getNonGMT($date9);
		
		// Assert
		$success = true;
		
		if($date1 != $correct1){
			echo "<br>Date 1 failed<br> ". $date1 ." != ". $correct1."<br>";
			$success = false;
		}
		
		
		if($date2 != $correct2){
			echo "<br>Date 2 failed<br> ". $date2 ." != ". $correct2."<br>";
			$success = false;
		}

		
		if($date3!= $correct3){
			echo "<br>Date 3 failed<br> ". $date3 ." != ". $correct3."<br>";
			$success = false;
		}

		if($date4!= $correct4){
			echo "<br>Date 4 failed<br> ". $date4 ." != ". $correct4."<br>";
			$success = false;
		}

		
		if($date5 != $correct5){
			echo "<br>Date 5 failed<br> ". $date5 ." != ". $correct5."<br>";
			$success = false;
		}

		if($date6 != $correct6){
			echo "<br>Date 6 failed<br> ". $date6 ." != ". $correct6."<br>";
			$success = false;
		}

		if($date7 != $correct7){
			echo "<br>Date 7 failed<br> ". $date7 ." != ". $correct7."<br>";
			$success = false;
		}
		
		if($date8 != $correct8){
			echo "<br>Date 8 failed<br> ". $date8 ." != ". $correct8."<br>";
			$success = false;
		}
		
		if($date9 != $correct9){
			echo "<br>Date 9 failed<br> ". $date9 ." != ". $correct9."<br>";
			$success = false;
		}
		

		var_dump($success);
		return $success;
		
		
	}
	
	


	public static function getGMTDateTime($date){
	
	
		
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
				date_add($date, date_interval_create_from_date_string("$hours[0] hours"));
			if($operation == "-")	
				date_sub($date, date_interval_create_from_date_string("$hours[0] hours"));
			
			// Add minutes
			$minutes = $offset[0];
			if($operation == "+")	
				date_add($date, date_interval_create_from_date_string("$minutes[1] minutes"));
			if($operation == "-")	
				date_sub($date, date_interval_create_from_date_string("$minutes[1] minutes"));
				
			return DateParser::readableDate($date);
		}
		
		
		$date  = new DateTime($date);


		return DateParser::readableDate($date);

	}
	
	
	// Removes offset and returns a simple datetime 
	public static function getNonGMT($date){
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


?>