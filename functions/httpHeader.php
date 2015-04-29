<?php

class httpHeader{

    /**
     * @param $statusCode Sets the HTTP header information.
     * @author Michael Pande, Diego Alonso Pasten Bahamondes
     */
		public static function setHeader($statusCode) {


			if(headers_sent() || $statusCode == null || !is_numeric($statusCode)){
                return "f";
			}
			switch($statusCode){
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
			header($HTTP . ' ' . $statusCode . ' ' . $message);
	}



}