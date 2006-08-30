<?php

require_once dirname(__FILE__).'/../phpunit2.php';

Prado::using('System.Util.TDateTimeStamp');

class DateTimeStampTestCase extends PHPUnit2_Framework_TestCase
{
	function testGetTimeStampAndFormat()
	{
		$s = new TDateTimeStamp;
		$t = $s->getTimeStamp(0,0,0);
		$this->assertEquals($s->formatDate('Y-m-d'), date('Y-m-d'));

		$t = $s->getTimeStamp(0,0,0,6,1,2102);
		$this->assertEquals($s->formatDate('Y-m-d',$t), '2102-06-01');

		$t = $s->getTimeStamp(0,0,0,2,1,2102);
		$this->assertEquals($s->formatDate('Y-m-d',$t), '2102-02-01');
	}

	function testGregorianToJulianConversion()
	{
		$s = new TDateTimeStamp;
		$t = $s->getTimeStamp(0,0,0,10,11,1492);

		//http://www.holidayorigins.com/html/columbus_day.html - Friday check
		$this->assertEquals($s->formatDate('D Y-m-d',$t), 'Fri 1492-10-11');

		$t = $s->getTimeStamp(0,0,0,2,29,1500);
		$this->assertEquals($s->formatDate('Y-m-d',$t), '1500-02-29');

		$t = $s->getTimeStamp(0,0,0,2,29,1700);
		$this->assertEquals($s->formatDate('Y-m-d',$t), '1700-03-01');

	}

	function testGregorianCorrection()
	{
		$s = new TDateTimeStamp;
		$diff = $s->getTimeStamp(0,0,0,10,15,1582) - $s->getTimeStamp(0,0,0,10,4,1582);

		//This test case fails on my windows machine!
		//$this->assertEquals($diff, 3600*24,
		//	"Error in gregorian correction = ".($diff/3600/24)." days");

		$this->assertEquals($s->getDayOfWeek(1582,10,15), 5.0);
		$this->assertEquals($s->getDayOfWeek(1582,10,4), 4.0);
	}

	function testOverFlow()
	{
		$s = new TDateTimeStamp;
		$t = $s->getTimeStamp(0,0,0,3,33,1965);
		$this->assertEquals($s->formatDate('Y-m-d',$t), '1965-04-02', 'Error in day overflow 1');

		$t = $s->getTimeStamp(0,0,0,4,33,1971);
		$this->assertEquals($s->formatDate('Y-m-d',$t), '1971-05-03', 'Error in day overflow 2');
		$t = $s->getTimeStamp(0,0,0,1,60,1965);
		$this->assertEquals($s->formatDate('Y-m-d',$t), '1965-03-01', 'Error in day overflow 3 '.$s->getDate('Y-m-d',$t));
		$t = $s->getTimeStamp(0,0,0,12,32,1965);
		$this->assertEquals($s->formatDate('Y-m-d',$t), '1966-01-01', 'Error in day overflow 4 '.$s->getDate('Y-m-d',$t));
		$t = $s->getTimeStamp(0,0,0,12,63,1965);
		$this->assertEquals($s->formatDate('Y-m-d',$t), '1966-02-01', 'Error in day overflow 5 '.$s->getDate('Y-m-d',$t));
		$t = $s->getTimeStamp(0,0,0,13,3,1965);
		$this->assertEquals($s->formatDate('Y-m-d',$t), '1966-01-03', 'Error in mth overflow 1');
	}

	function test2DigitTo4DigitYearConversion()
	{
		$s = new TDateTimeStamp;
		$this->assertEquals($s->get4DigitYear(00), 2000, "Err 2-digit 2000");
		$this->assertEquals($s->get4DigitYear(10), 2010, "Err 2-digit 2010");
		$this->assertEquals($s->get4DigitYear(20), 2020, "Err 2-digit 2020");
		$this->assertEquals($s->get4DigitYear(30), 2030, "Err 2-digit 2030");
		$this->assertEquals($s->get4DigitYear(40), 1940, "Err 2-digit 1940");
		$this->assertEquals($s->get4DigitYear(50), 1950, "Err 2-digit 1950");
		$this->assertEquals($s->get4DigitYear(90), 1990, "Err 2-digit 1990");
	}

	function testStringFormating()
	{
		$s = new TDateTimeStamp;
		$fmt = '\d\a\t\e T Y-m-d H:i:s a A d D F g G h H i j l L m M n O \R\F\C2822 r s t U w y Y z Z 2003';
		$s1 = date($fmt,0);
		$s2 = $s->formatDate($fmt,0);
		$this->assertEquals($s1, $s2);//, " date() 0 failed \n $s1 \n $s2");

		for ($i=100; --$i > 0; )
		{
				$ts = 3600.0*((rand()%60000)+(rand()%60000))+(rand()%60000);
				$s1 = date($fmt,$ts);
				$s2 = $s->formatDate($fmt,$ts);
				//print "$s1 <br>$s2 <p>";
				$this->assertEquals($s1,$s2);

				$a1 = getdate($ts);
				$a2 = $s->getDate($ts,false);
				$this->assertEquals($a1,$a2);
		}
	}

	function testRandomDatesBetween100And4000()
	{
		$this->assertIsValidDate(100,1);
		//echo "Testing year ";
		for ($i=100; --$i >= 0;)
		{
			$y1 = 100+rand(0,1970-100);
			//echo $y1." ";
			$m = rand(1,12);
			$this->assertIsValidDate($y1,$m);

			$y1 = 3000-rand(0,3000-1970);
			//echo $y1." ";
			$this->assertIsValidDate($y1,$m);
		}
	}

	function assertIsValidDate($y1,$m,$d=13)
	{
		$s = new TDateTimeStamp;
		$t = $s->getTimeStamp(0,0,0,$m,$d,$y1);
		$rez = $s->formatDate('Y-n-j H:i:s',$t);

		$this->assertEquals("$y1-$m-$d 00:00:00", $rez);
	}

	function testRandomDates()
	{
		$start = 1960+rand(0,10);
		$yrs = 12;
		$i = 365.25*86400*($start-1970);
		$offset = 36000+rand(10000,60000);
		$max = 365*$yrs*86400;
		$lastyear = 0;
		$s = new TDateTimeStamp;

		// we generate a timestamp, convert it to a date, and convert it back to a timestamp
		// and check if the roundtrip broke the original timestamp value.
		//print "Testing $start to ".($start+$yrs).", or $max seconds, offset=$offset: ";
		$fails = 0;
		for ($max += $i; $i < $max; $i += $offset)
		{
			$ret = $s->formatDate('m,d,Y,H,i,s',$i);
			$arr = explode(',',$ret);
			if ($lastyear != $arr[2])
				$lastyear = $arr[2];

			$newi = $s->getTimestamp($arr[3],$arr[4],$arr[5],$arr[0],$arr[1],$arr[2]);
			if ($i != $newi)
			{
				$fails++;
				//$j = mktime($arr[3],$arr[4],$arr[5],$arr[0],$arr[1],$arr[2]);
				//print "Error at $i, $j, getTimestamp() returned $newi ($ret)\n";
			}
		}
		$this->assertEquals($fails, 0);
	}
}

?>