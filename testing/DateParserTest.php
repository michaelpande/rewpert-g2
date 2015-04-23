<?php

/**
 *   @author Michael Pande <michaelpande@gmail.com>
 *   @license http://opensource.org/licenses/MIT MIT
 *
 */

require_once(__DIR__.'/../parser/DateParser.php');


class DateParserTest extends PHPUnit_Framework_TestCase {


    public function testShortDateGMT(){
        $actual = DateParser::getGMTDateTime("2015-01-28");
        $expected = self::readableDate(new DateTime("2015-01-28 00:00:00"));

        $this->assertEquals($expected, $actual);

    }

    public function testShortDate2GMT(){
        $actual = DateParser::getGMTDateTime("2015-01");
        $expected = self::readableDate(new DateTime("2015-01-01 00:00:00"));

        $this->assertEquals($expected, $actual);

    }

    public function testEmptyDateGMT(){
        $actual = DateParser::getGMTDateTime("");
        $this->assertNull($actual);

    }


    public function testZeroOffsetGMT(){
        $actual = DateParser::getGMTDateTime("2015-01-28T11:38:30+00:00");
        $expected = self::readableDate(new DateTime("2015-01-28 11:38:30"));

        $this->assertEquals($expected, $actual);

    }

    public function testPositiveOffsetGMT(){
        $actual = DateParser::getGMTDateTime("2013-08-21T16:38:18+02:00");
        $expected = self::readableDate(new DateTime("2013-08-21 14:38:18"));

        $this->assertEquals($expected, $actual);

    }
    public function testNegativeOffsetGMT(){
        $actual = DateParser::getGMTDateTime("2015-01-28T16:14:49-01:30");
        $expected = self::readableDate(new DateTime("2015-01-28 17:44:49"));

        $this->assertEquals($expected, $actual);

    }

    public function testNegativeOffset2GMT(){

        $actual = DateParser::getGMTDateTime("2015-01-28T18:00:00-02:30");
        $expected = self::readableDate(new DateTime("2015-01-28 20:30:00"));

        $this->assertEquals($expected, $actual);

    }


    public function testTimeZoneGMT(){
        $actual = DateParser::getGMTDateTime("2013-08-25T18:11:07.000Z");
        $expected = self::readableDate(new DateTime("2013-08-25 18:11:07"));

        $this->assertEquals($expected, $actual);

    }

    public function testNegativeDateBCEGMT(){
        $actual = DateParser::getGMTDateTime("-0004-01-27");
        $expected = self::readableDate(new DateTime("-0004-01-27 00:00:00"));

        $this->assertEquals($expected, $actual);

    }
    public function testPositiveDateGMT(){
        $actual = DateParser::getGMTDateTime("+0004-01-27");
        $expected = self::readableDate(new DateTime("0004-01-27 00:00:00"));

        $this->assertEquals($expected, $actual);

    }



    public function testShortDate(){
        $actual = DateParser::getNonGMT("2015-01-28");
        $expected = self::readableDate(new DateTime("2015-01-28 00:00:00"));

        $this->assertEquals($expected, $actual);

    }


    public function testShortDate2(){
        $actual = DateParser::getNonGMT("2015-01");
        $expected = self::readableDate(new DateTime("2015-01-01 00:00:00"));

        $this->assertEquals($expected, $actual);

    }

    public function testEmptyDate(){
        $actual = DateParser::getNonGMT("");
        $this->assertNull($actual);

    }

    public function testZeroOffset(){
        $actual = DateParser::getNonGMT("2015-01-28T11:38:30+00:00");
        $expected = self::readableDate(new DateTime("2015-01-28 11:38:30"));

        $this->assertEquals($expected, $actual);

    }

    public function testPositiveOffset(){
        $actual = DateParser::getNonGMT("2013-08-21T16:38:18+02:00");
        $expected = self::readableDate(new DateTime("2013-08-21 16:38:18"));

        $this->assertEquals($expected, $actual);

    }
    public function testNegativeOffset(){
        $actual = DateParser::getNonGMT("2015-01-28T16:14:49-01:30");
        $expected = self::readableDate(new DateTime("2015-01-28 16:14:49"));

        $this->assertEquals($expected, $actual);

    }

    public function testNegativeOffset2(){

        $actual = DateParser::getNonGMT("2015-01-28T18:00:00-02:30");
        $expected = self::readableDate(new DateTime("2015-01-28 18:00:00"));

        $this->assertEquals($expected, $actual);

    }


    public function testTimeZone(){
        $actual = DateParser::getNonGMT("2013-08-25T18:11:07.000Z");
        $expected = self::readableDate(new DateTime("2013-08-25 18:11:07"));

        $this->assertEquals($expected, $actual);

    }

    public function testNegativeDateBCE(){
        $actual = DateParser::getNonGMT("-0004-01-27");
        $expected = self::readableDate(new DateTime("-0004-01-27 00:00:00"));

        $this->assertEquals($expected, $actual);

    }
    public function testPositiveDate(){
        $actual = DateParser::getNonGMT("+0004-01-27");
        $expected = self::readableDate(new DateTime("0004-01-27 00:00:00"));

        $this->assertEquals($expected, $actual);

    }

    public function testUnsupportedDate(){
        try{
            DateParser::getNonGMT("this should fail");
        }catch(InvalidArgumentException $actual){};

        $expected = new InvalidArgumentException(DateParser::$NOT_SUPPORTED_ERROR);

        $this->assertEquals($expected, $actual);

    }

    public function testUnsupportedDateGMT(){
        try{
            DateParser::getGMTDateTime("this should fail");
        }catch(InvalidArgumentException $actual){};

        $expected = new InvalidArgumentException(DateParser::$NOT_SUPPORTED_ERROR);

        $this->assertEquals($expected, $actual);

    }

    public static function readableDate($date){
        return $date->format('Y-m-d H:i:s');
    }


}


