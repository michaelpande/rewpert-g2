<?php
class DateParserTest {
	
	public static function runAllTests() {
		$successful = 0;
		
		if(self::testGMT()) $successful++;
		if(self::testNonGMT()) $successful++;
		
		return $successful;
	}
	
	private static function readableDate($date){
		return $date->format('Y-m-d H:i:s');
	}
	
	// Tests GMT method and returns a boolean 
	private static function testGMT(){

		echo "<h4>Test GMT</h4>";
		// Arrange
		$date1 = "2015-01-28";				// Only date
		$correct1 = self::readableDate(new DateTime("2015-01-28 00:00:00")); 
		
		$date2 = "2015-01-28T11:38:30+00:00"; // Plus zero to UTC
		$correct2 = self::readableDate(new DateTime("2015-01-28 11:38:30"));
		
		$date3 = "2015-01-28T16:14:49-01:30";  // Subtract to get UTC
		$correct3 = self::readableDate(new DateTime("2015-01-28 17:44:49"));
		
		$date4 = "2015-01-28T18:00:00-02:30"; // Subtract to get UTC
		$correct4 = self::readableDate(new DateTime("2015-01-28 20:30:00"));
		
		$date5 = "2013-08-21T16:38:18+02:00"; // From NML-G2 Document
		$correct5 = self::readableDate(new DateTime("2013-08-21 14:38:18"));
		
		$date6 = "2013-08-25T18:11:07.000Z"; // Timezone added
		$correct6 = self::readableDate(new DateTime("2013-08-25 18:11:07"));
		
		$date7 = "-0004-01-27";				// Negative date (4 BCE)
		$correct7 = self::readableDate(new DateTime("-0004-01-27 00:00:00")); // Correct?
		
		$date8 = "+0004-01-27";				// Positive date
		$correct8 = self::readableDate(new DateTime("0004-01-27 00:00:00")); // Correct?
		
		
		$date9 = "2014-11-21T16:25:32-05:00"; // From NML-G2 Document
		$correct9 = self::readableDate(new DateTime("2014-11-21 21:25:32"));
		
		
		
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
	private static function testNonGMT(){
		
		echo "<h4>Test Non-GMT</h4>";
		// Arrange
		$date1 = "2015-01-28";				// Only date
		$correct1 = self::readableDate(new DateTime("2015-01-28 00:00:00")); 
		
		$date2 = "2015-01-28T11:38:30+00:00"; // Plus zero from UTC
		$correct2 = self::readableDate(new DateTime("2015-01-28 11:38:30"));
		
		$date3 = "2015-01-28T16:14:49-01:30";  // Plus from UTC
		$correct3 = self::readableDate(new DateTime("2015-01-28 16:14:49"));
		
		$date4 = "2015-01-28T18:00:00-02:30"; // Minus from UTC
		$correct4 = self::readableDate(new DateTime("2015-01-28 18:00:00"));
		
		$date5 = "2013-08-21T16:38:18+02:00"; // From NML-G2 Document
		$correct5 = self::readableDate(new DateTime("2013-08-21 16:38:18"));
		
		$date6 = "2014-08-25T18:11:07.000Z"; // Timezone added
		$correct6 = self::readableDate(new DateTime("2014-08-25 18:11:07"));
		
		$date7 = "-0004-01-27";				// Negative date (4 BCE)
		$correct7 = self::readableDate(new DateTime("-0004-01-27 00:00:00")); // Correct?
		
		$date8 = "+0004-01-27";				// Positive date
		$correct8 = self::readableDate(new DateTime("0004-01-27 00:00:00")); // Correct?
		
		
		$date9 = "2014-11-22T16:25:32-05:00"; // From NML-G2 Document
		$correct9 = self::readableDate(new DateTime("2014-11-22 16:25:32"));
		
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
}
?>