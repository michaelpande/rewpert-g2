<?php

class unitTestManager {
	
	public static function performUnitTest() {
		require("newsitemParseTest.php");
		require("DateParserTest.php");
		echo "<h3>Starting unit test";
		
		$class = new ReflectionClass('newsitemParseTest');
		$methods = $class->getMethods(ReflectionMethod::IS_PRIVATE);
		$testsPerfomed += count($methods);
		
		$class = new ReflectionClass('DateParserTest');
		$methods = $class->getMethods(ReflectionMethod::IS_PRIVATE);
		$testsPerfomed += count($methods) -1;
		
		$successful = 0;
		
		$successful += newsitemParseTest::runAllTests();
		$successful += DateParserTest::runAllTests();
		
		echo "<h3>" . $successful . "/" . $testsPerfomed . " successful test methods</h3>";
	}
}

?>