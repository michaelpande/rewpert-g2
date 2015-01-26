<?php

class errorLogger{
	
	
	
	
		//This is to inform the user on the web of errors.
		//It has not yet been decided if the statusIn is int or string
		public static function headerStatus($event) {
			
			if(headers_sent() || $event == null || !is_numeric($event)){
				;
			}
			switch($event){
				case 100 : $message = "Continue"; break; 
				case 201 : $message = "Created"; break;
				case 204 : $message = "No Content"; break;
				case 304 : $message = "Not modified"; break;
				case 400 : $message = "Bad Request"; break;
				case 401 : $message = "Unauthorized"; break;
				case 403 : $message = "Forbidden"; break;
				case 408 : $message = "Request Timeout"; break;
				case 409 : $message = "Conflict"; break;
				case 500 : $message = "Internal Server Error"; break;
				default: $message = "OK"; break;
			}
		
			$HTTP = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
			header($HTTP . ' ' . $event . ' ' . $message);
	}
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


}