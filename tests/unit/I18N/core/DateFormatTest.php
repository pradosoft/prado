<?php
require_once dirname(__FILE__).'/../../phpunit2.php';

Prado::using('System.I18N.core.DateFormat');

/**
 * @package System.I18N.core
 */
class DateFormatTest extends PHPUnit2_Framework_TestCase {

  public function testStandardPatterns() {
    $dateFormatter = new DateFormat();
    
    $time = @mktime(9, 9, 9, 9, 1, 2004);
    $zone = @date('T', $time);
    //var_dump(date('c',$time));
    //for ShortDatePattern  "M/d/yy"
    $this->assertEquals('9/1/04', $dateFormatter->format($time, 'd'));
    //var_dump(date('c',strtotime($dateFormatter->format($time,'d'))));
    
    //for LongDatePattern  "MMMM d, yyyy"
    $wants = 'September 1, 2004';
    $this->assertEquals($wants, $dateFormatter->format($time, 'D'));
    //var_dump(date('c',strtotime($dateFormatter->format($time,'D'))));
    
    //for Full date and time  "MMMM d, yyyy h:mm a"
    $wants = 'September 1, 2004 9:09 AM';
    $this->assertEquals($wants, $dateFormatter->format($time, 'f'));
    //var_dump(date('c',strtotime($dateFormatter->format($time,'f'))));
    
    //for FullDateTimePattern  "MMMM d, yyyy h:mm:ss a z"
    $wants = 'September 1, 2004 9:09:09 AM '.$zone;
    $this->assertEquals($wants, $dateFormatter->format($time, 'F'));
    
    //for General "M/d/yy h:mm a"
    $wants = '9/1/04 9:09 AM';
    $this->assertEquals($wants, $dateFormatter->format($time, 'g'));
    //var_dump(date('c',strtotime($dateFormatter->format($time,'g'))));
    
    //for General "M/d/yy h:mm:ss a z"
    $wants = '9/1/04 9:09:09 AM '.$zone;
    $this->assertEquals($wants, $dateFormatter->format($time, 'G'));	
    
    //for MonthDayPattern  "MMMM dd" (invariant)
    $wants = 'September 01';
    $this->assertEquals($wants, $dateFormatter->format($time, 'm'));
    //var_dump(date('c',strtotime($dateFormatter->format($time,'m'))));
    
    //for RFC1123Pattern  "EEE, dd MMM yyyy HH:mm:ss" (invariant)
    $wants = 'Wed, 01 Sep 2004 09:09:09';
    $this->assertEquals($wants, $dateFormatter->format($time, 'r'));	
    //var_dump(date('c',strtotime($dateFormatter->format($time,'r'))));
    
    //for SortableDateTimePattern "yyyy-MM-ddTHH:mm:ss" (invariant)
    $wants = '2004-09-01T09:09:09';
    $this->assertEquals($wants, $dateFormatter->format($time, 's'));	
    //var_dump(date('c',strtotime($dateFormatter->format($time,'s'))));
    
    //for ShortTimePattern  "H:mm a"
    $wants = '9:09 AM';
    $this->assertEquals($wants, $dateFormatter->format($time, 't'));	
    //(date('c',strtotime($dateFormatter->format($time,'t'))));
    
    //for LongTimePattern  "H:mm:ss a z"
    $wants = '9:09:09 AM '.$zone;
    $this->assertEquals($wants, $dateFormatter->format($time, 'T'));	
    
    //for UniversalSortableDateTimePattern "yyyy-MM-dd HH:mm:ss z" 
    //(invariant)
    $wants = '2004-09-01 09:09:09 '.$zone;
    $this->assertEquals($wants, $dateFormatter->format($time, 'u'));	
		
    //for Full date and time  "EEEE dd MMMM yyyy HH:mm:ss" (invariant)
    $wants = 'Wednesday 01 September 2004 09:09:09';
    $this->assertEquals($wants, $dateFormatter->format($time, 'U'));	
    //var_dump(date('c',strtotime($dateFormatter->format($time,'U'))));
    
    //for YearMonthPattern  "yyyy MMMM" (invariant)
    $wants = '2004 September';
    $this->assertEquals($wants, $dateFormatter->format($time, 'y'));	
    //var_dump(date('c',strtotime($dateFormatter->format($time,'y'))));
  }
  
  public function testCustomPatterns() {
    $dateFormatter = new DateFormat();
    
    $time = @mktime(9, 9, 9, 9, 1, 2004);
    
    $pattern = "'Hello' EEEE, 'it should be' MMM yyyy HH:mm:ss!!!";
    $wants = 'Hello Wednesday, it should be Sep 2004 09:09:09!!!';
    $this->assertEquals($wants, $dateFormatter->format($time, $pattern));  
  }
}

?>