<?php

class errorLogger{
	
	
	
	
		//This is to inform the user on the web of errors.
		//It has not yet been decided if the statusIn is int or string
		public static function headerStatus($event) {
			
			if(headers_sent() || $event == null || !is_numeric($event)){
				return;
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



}