<?php

Prado::using('System.I18N.core.DateTimeFormatInfo');

class TestOfDateTimeFormatInfo extends UnitTestCase
{  
    protected $format;

    function TestOfDateTimeFormatInfo()
    {
        $this->UnitTestCase();
    }

    function setUp()
    {
		$this->format = DateTimeFormatInfo::getInstance('en');
    }

    function testAbbreviatedDayNames()
    {
        $names = $this->format->AbbreviatedDayNames;
        $this->assertTrue(is_array($names),'Must be an array!');
        $this->assertEqual(count($names),7,'Must have 7 day names');

        //assuming invariant culture.
        $days = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
        $this->assertEqual($names, $days);

        //try to set the data
        $data = array('Hel', 'wor');
        $this->format->AbbreviatedDayNames = $data;
        $newNames = $this->format->AbbreviatedDayNames;
        $this->assertTrue(is_array($newNames),'Must be an array!');
        $this->assertEqual(count($newNames),2,'Must have 2 entries');
        $this->assertEqual($newNames, $data);
    }

    function testNarrowDayNames()
    {
        $names = $this->format->NarrowDayNames;
        $this->assertTrue(is_array($names),'Must be an array!');
        $this->assertEqual(count($names),7,'Must have 7 day names');

        //assuming invariant culture.
        $days = array("S", "M", "T", "W", "T", "F", "S");
        $this->assertEqual($names, $days);

         //try to set the data
        $data = array('H', 'w');
        $this->format->NarrowDayNames = $data;
        $newNames = $this->format->NarrowDayNames;
        $this->assertTrue(is_array($newNames),'Must be an array!');
        $this->assertEqual(count($newNames),2,'Must have 2 entries');
        $this->assertEqual($newNames, $data);
    }

    function testDayNames()
    {
        $names = $this->format->DayNames;
        $this->assertTrue(is_array($names),'Must be an array!');
        $this->assertEqual(count($names),7,'Must have 7 day names');

        //assuming invariant culture.
        $days = array(  "Sunday","Monday", "Tuesday", "Wednesday",
                        "Thursday", "Friday", "Saturday");
        $this->assertEqual($names, $days);

         //try to set the data
        $data = array('Hello', 'world');
        $this->format->DayNames = $data;
        $newNames = $this->format->DayNames;
        $this->assertTrue(is_array($newNames),'Must be an array!');
        $this->assertEqual(count($newNames),2,'Must have 2 entries');
        $this->assertEqual($newNames, $data);
    }

    function testMonthNames()
    {
        $names = $this->format->MonthNames;
        $this->assertTrue(is_array($names),'Must be an array!');
        $this->assertEqual(count($names),12,'Must have 12 month names');

        //assuming invariant culture.
        $days = array(  "January", "February", "March", "April",
	                    "May", "June", "July", "August", "September",
                        "October", "November", "December");
        $this->assertEqual($names, $days);

         //try to set the data
        $data = array('Hello', 'world');
        $this->format->MonthNames = $data;
        $newNames = $this->format->MonthNames;
        $this->assertTrue(is_array($newNames),'Must be an array!');
        $this->assertEqual(count($newNames),2,'Must have 2 entries');
        $this->assertEqual($newNames, $data);
    }

    function testNarrowMonthNames()
    {
        $names = $this->format->NarrowMonthNames;
        $this->assertTrue(is_array($names),'Must be an array!');
        $this->assertEqual(count($names),12,'Must have 12 month names');

        //assuming invariant culture.
        $days = array(  "J", "F", "M", "A", "M", "J", "J",
                        "A", "S", "O", "N", "D");
        $this->assertEqual($names, $days);

         //try to set the data
        $data = array('Hello', 'world');
        $this->format->NarrowMonthNames = $data;
        $newNames = $this->format->NarrowMonthNames;
        $this->assertTrue(is_array($newNames),'Must be an array!');
        $this->assertEqual(count($newNames),2,'Must have 2 entries');
        $this->assertEqual($newNames, $data);
    }

    function testAbbreviatedMonthNames()
    {
        $names = $this->format->AbbreviatedMonthNames;
        $this->assertTrue(is_array($names),'Must be an array!');
        $this->assertEqual(count($names),12,'Must have 12 month names');

        //assuming invariant culture.
        $days = array(  "Jan", "Feb", "Mar", "Apr",
	                    "May", "Jun", "Jul", "Aug", "Sep",
                        "Oct", "Nov", "Dec");
        $this->assertEqual($names, $days);

         //try to set the data
        $data = array('Hello', 'world');
        $this->format->AbbreviatedMonthNames = $data;
        $newNames = $this->format->AbbreviatedMonthNames;
        $this->assertTrue(is_array($newNames),'Must be an array!');
        $this->assertEqual(count($newNames),2,'Must have 2 entries');
        $this->assertEqual($newNames, $data);
    }

    function testEra()
    {
        //era for invariant culture is assumed to have
        // 1 for AD and 0 for BC
        $this->assertEqual('AD', $this->format->getEra(1));
        $this->assertEqual('BC', $this->format->getEra(0));
    }

    function testAMPMMarkers()
    {
        $am_pm = array('AM','PM');
        $data = $this->format->AMPMMarkers;
        $this->assertTrue(is_array($data));
        $this->assertEqual($am_pm, $data);
        $this->assertEqual('AM', $this->format->AMDesignator);
        $this->assertEqual('PM', $this->format->PMDesignator);

        //try to set the data
        $data = array('Hello', 'world');
        $this->format->AMPMMarkers = $data;
        $newNames = $this->format->AMPMMarkers;
        $this->assertTrue(is_array($newNames),'Must be an array!');
        $this->assertEqual(count($newNames),2,'Must have 2 entries');
        $this->assertEqual($newNames, $data);

        $this->format->AMDesignator = 'TTTT';
        $this->assertEqual('TTTT',$this->format->AMDesignator);

        $this->format->PMDesignator = 'SSS';
        $this->assertEqual('SSS',$this->format->PMDesignator);
    }

    function testPatterns()
    {
        //patterns for invariant
        $patterns = array(
            'FullTimePattern' =>      'h:mm:ss a z',
            'LongTimePattern' =>      'h:mm:ss a z',
            'MediumTimePattern' =>    'h:mm:ss a',
            'ShortTimePattern' =>     'h:mm a',
            'FullDatePattern' =>      'EEEE, MMMM d, yyyy',
            'LongDatePattern' =>      'MMMM d, yyyy',
            'MediumDatePattern' =>    'MMM d, yyyy',
            'ShortDatePattern' =>     'M/d/yy',
            'DateTimeOrderPattern' => '{1} {0}'
        );

        foreach($patterns as $property => $pattern)
        {
            $this->assertEqual($pattern, $this->format->$property);
        }

        $hello = 'Hello';
        $world = 'world';
        $expectedResult = $hello.' '.$world;
        $this->assertEqual($expectedResult,
                $this->format->formatDateTime($hello, $world));
    }

    function testInvariantInfo()
    {
        $format = DateTimeFormatInfo::getInstance();

        //the variant datetime format for medium date
        //should be the follow
        $pattern = 'MMM d, yyyy';

        $this->assertEqual($pattern, $format->MediumDatePattern);

        $invariant = $format->getInvariantInfo();

        $this->assertIdentical($format, $invariant);
    }

    function testGetInstance()
    {
    	$format = DateTimeFormatInfo::getInstance('zh_CN');
    	
    	$pattern = 'yyyy-M-d';
        $this->assertEqual($pattern, $format->MediumDatePattern);
    }
}


?>