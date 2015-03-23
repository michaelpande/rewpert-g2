<?php

class unitTestManager {
	
	public static function performUnitTest() {
		require("newsitemParseTest.php");
		
		$testsPerfomed = 2;
		$successful = 0;
		
		$successful += newsitemParseTest::runAllTests();
		
		echo "<h3>" . $successful . "/" . $testsPerfomed . " successful tests</h3>";
	}
}

?>