<?php
require_once dirname(__FILE__).'/../../phpunit2.php';

Prado::using('System.I18N.core.DateTimeFormatInfo');

/**
 * @package System.I18N.core
 */
class DateTimeFormatInfoTest extends PHPUnit2_Framework_TestCase {

  protected $format;
  
  function setUp() {
    $this->format = DateTimeFormatInfo::getInstance('en');
  }
  
  function testAbbreviatedDayNames() {
    $names = $this->format->AbbreviatedDayNames;
    $this->assertTrue(is_array($names),'Must be an array!');
    $this->assertEquals(count($names),7,'Must have 7 day names');
    
    //assuming invariant culture.
    $days = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
    $this->assertEquals($names, $days);
    
    //try to set the data
    $data = array('Hel', 'wor');
    $this->format->AbbreviatedDayNames = $data;
    $newNames = $this->format->AbbreviatedDayNames;
    $this->assertTrue(is_array($newNames),'Must be an array!');
    $this->assertEquals(count($newNames),2,'Must have 2 entries');
    $this->assertEquals($newNames, $data);
  }
  
  function testNarrowDayNames() {
    $names = $this->format->NarrowDayNames;
    $this->assertTrue(is_array($names),'Must be an array!');
    $this->assertEquals(count($names),7,'Must have 7 day names');
    
    //assuming invariant culture.
    $days = array("S", "M", "T", "W", "T", "F", "S");
    $this->assertEquals($names, $days);
    
    //try to set the data
    $data = array('H', 'w');
    $this->format->NarrowDayNames = $data;
    $newNames = $this->format->NarrowDayNames;
    $this->assertTrue(is_array($newNames),'Must be an array!');
    $this->assertEquals(count($newNames),2,'Must have 2 entries');
    $this->assertEquals($newNames, $data);
  }
  
  function testDayNames() {
    $names = $this->format->DayNames;
    $this->assertTrue(is_array($names),'Must be an array!');
    $this->assertEquals(count($names),7,'Must have 7 day names');
    
    //assuming invariant culture.
    $days = array(  "Sunday","Monday", "Tuesday", "Wednesday",
		    "Thursday", "Friday", "Saturday");
    $this->assertEquals($names, $days);
    
    //try to set the data
    $data = array('Hello', 'world');
    $this->format->DayNames = $data;
    $newNames = $this->format->DayNames;
    $this->assertTrue(is_array($newNames),'Must be an array!');
    $this->assertEquals(count($newNames),2,'Must have 2 entries');
    $this->assertEquals($newNames, $data);
  }
  
  function testMonthNames() {
    $names = $this->format->MonthNames;
    $this->assertTrue(is_array($names),'Must be an array!');
    $this->assertEquals(count($names),12,'Must have 12 month names');
    
    //assuming invariant culture.
    $days = array(  "January", "February", "March", "April",
		    "May", "June", "July", "August", "September",
		    "October", "November", "December");
    $this->assertEquals($names, $days);
    
    //try to set the data
    $data = array('Hello', 'world');
    $this->format->MonthNames = $data;
    $newNames = $this->format->MonthNames;
    $this->assertTrue(is_array($newNames),'Must be an array!');
    $this->assertEquals(count($newNames),2,'Must have 2 entries');
    $this->assertEquals($newNames, $data);
  }
  
  function testNarrowMonthNames() {
    $names = $this->format->NarrowMonthNames;
    $this->assertTrue(is_array($names),'Must be an array!');
    $this->assertEquals(count($names),12,'Must have 12 month names');
    
    //assuming invariant culture.
    $days = array(  "J", "F", "M", "A", "M", "J", "J",
                        "A", "S", "O", "N", "D");
    $this->assertEquals($names, $days);
    
    //try to set the data
    $data = array('Hello', 'world');
    $this->format->NarrowMonthNames = $data;
    $newNames = $this->format->NarrowMonthNames;
    $this->assertTrue(is_array($newNames),'Must be an array!');
    $this->assertEquals(count($newNames),2,'Must have 2 entries');
    $this->assertEquals($newNames, $data);
  }

  function testAbbreviatedMonthNames() {
    $names = $this->format->AbbreviatedMonthNames;
    $this->assertTrue(is_array($names),'Must be an array!');
    $this->assertEquals(count($names),12,'Must have 12 month names');
    
    //assuming invariant culture.
    $days = array(  "Jan", "Feb", "Mar", "Apr",
		    "May", "Jun", "Jul", "Aug", "Sep",
		    "Oct", "Nov", "Dec");
    $this->assertEquals($names, $days);
    
    //try to set the data
    $data = array('Hello', 'world');
    $this->format->AbbreviatedMonthNames = $data;
    $newNames = $this->format->AbbreviatedMonthNames;
    $this->assertTrue(is_array($newNames),'Must be an array!');
    $this->assertEquals(count($newNames),2,'Must have 2 entries');
    $this->assertEquals($newNames, $data);
  }

  function testEra() {
    //era for invariant culture is assumed to have
    // 1 for AD and 0 for BC
    $this->assertEquals('AD', $this->format->getEra(1));
    $this->assertEquals('BC', $this->format->getEra(0));
  }
  
  function testAMPMMarkers() {
    $am_pm = array('AM','PM');
    $data = $this->format->AMPMMarkers;
    $this->assertTrue(is_array($data));
    $this->assertEquals($am_pm, $data);
    $this->assertEquals('AM', $this->format->AMDesignator);
    $this->assertEquals('PM', $this->format->PMDesignator);
    
    //try to set the data
    $data = array('Hello', 'world');
    $this->format->AMPMMarkers = $data;
    $newNames = $this->format->AMPMMarkers;
    $this->assertTrue(is_array($newNames),'Must be an array!');
    $this->assertEquals(count($newNames),2,'Must have 2 entries');
    $this->assertEquals($newNames, $data);
    
    $this->format->AMDesignator = 'TTTT';
    $this->assertEquals('TTTT',$this->format->AMDesignator);
    
    $this->format->PMDesignator = 'SSS';
    $this->assertEquals('SSS',$this->format->PMDesignator);
  }

  function testPatterns() {
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
    
    foreach($patterns as $property => $pattern) {
      $this->assertEquals($pattern, $this->format->$property);
    }
    
    $hello = 'Hello';
    $world = 'world';
    $expectedResult = $hello.' '.$world;
    $this->assertEquals($expectedResult,
		       $this->format->formatDateTime($hello, $world));
  }
  
  function testInvariantInfo() {
    $format = DateTimeFormatInfo::getInstance();
    
    //the variant datetime format for medium date
    //should be the follow
    $pattern = 'MMM d, yyyy';
    
    $this->assertEquals($pattern, $format->MediumDatePattern);
    
    $invariant = $format->getInvariantInfo();
    
    $this->assertSame($format, $invariant);
  }
  
  function testGetInstance() {
    $format = DateTimeFormatInfo::getInstance('zh_CN');
    
    $pattern = 'yyyy-M-d';
    $this->assertEquals($pattern, $format->MediumDatePattern);
  } 
}
?>