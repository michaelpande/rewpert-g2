<?php

class errorLogger{

	//Idea is to call this function when an error happens

	public static function createAnErrorEntry($errno, $errstr) {
		echo "<b>Error: </b> [$errno] $errstr<br>";
		echo "Logfile for plugin has been updated.";
		error_log("[$errno] $errstr<br>\n", 3, "errorLogFile.log");
	}
}