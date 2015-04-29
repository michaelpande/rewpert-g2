<?php

/**
 *   @author Michael Pande <michaelpande@gmail.com>
 *   @license http://opensource.org/licenses/MIT MIT
 *
 */
include(__DIR__.'/../functions/functions.php');


class functionsTest extends PHPUnit_Framework_TestCase {



    public function testDebugString(){

        global $DEBUG;
        $DEBUG = true;
        ob_start();
        output("test");
        $actual = ob_get_clean();

        $expected = "<p>test</p>";

        $this->assertEquals($expected, $actual);

    }


    public function testDebugNull(){

        global $DEBUG;
        $DEBUG = true;
        ob_start();
        output(null);
        $actual = ob_get_clean();

        $expected = "";

        $this->assertEquals($expected, $actual);

    }


    public function testDebugItem(){

        global $DEBUG;
        $DEBUG = true;
        ob_start();
        output(array("test", "TEST"));
        $actual = ob_get_clean();

        ob_start();
        var_dump(array("test", "TEST"));
        $expected = ob_get_clean();

        $this->assertEquals($expected, $actual);

    }


}


