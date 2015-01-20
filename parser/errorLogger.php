<?php

class errorLogger{
/*
	//Idea is to call this function when an error happens
	public static function createAnErrorEntry($errno, $errstr) {
		$filename = 'errorLogFile.log';
		
		//If file does not exist create it
		//r+ means read and write and pointer set to start of file.
		if (!file_exists(filename)) {
			fopen(filename, "r+")
		}
		else {
			echo "<b>Error: </b> [$errno] $errstr<br>";
			echo "Logfile for plugin has been updated.";
			error_log("[$errno] $errstr<br>\n", 3, "errorLogFile.log");
		}
	


	//Call this function to list out errors
	//NOT YET COMPLETE
	//As it stands it will read the whole file, which can be problematic with
	//huge logfiles. 
	public static function fetchErrorsFromFile() {
		$filename = 'errorLogFile.log';
		$fetched = fopen($filename, 'r')
		while(! feof($filename))
		{
			echo fgets($filename). "<br/>"
		}
		fclose($filename);
	}
*/
	//This is to inform the user on the web of errors.
	//It has not yet been decided if the statusIn is int or string
	public static function headerStatus($statusIn) {
		$event = $statusIn;

		if($event == 404){
			header("404 Not Found");
		}
		elseif ($event == 403) {
			header("403 Forbidden");
		}
		elseif ($event == 401){
			header("401 Unauthorized");
		}
		elseif($event == 408){
			header("408 Request Timeout");
		}
		elseif($event == 500){
			header("500 Internal Server Error");
		}
		elseif($event == 503){
			header("503 Service Unavailable");
		}
		elseif($event == 200){
			header("OK!");
		}
	}
}