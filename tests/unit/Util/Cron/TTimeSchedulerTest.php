<?php

use Prado\Util\Cron\TTimeScheduler;

//date format = d. fo. = dfo
function dfo($time)
{
	return date('l jS \of F Y h:i:s A', $time);
}

class TTimeSchedulerTest extends PHPUnit\Framework\TestCase
{
	protected $_zone;
	protected $obj;

	protected function setUp(): void
	{
		$this->_zone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$this->obj = new TTimeScheduler();
	}

	protected function tearDown(): void
	{
		date_default_timezone_set($this->_zone);
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('\\Prado\\Util\\Cron\\TTimeScheduler', $this->obj);
	}

	public function testGetSchedule()
	{
		$this->assertNull($this->obj->getSchedule());
		$schedule = '* * * * * *';
		$this->obj->setSchedule($schedule);
		$this->assertEquals($schedule, $this->obj->getSchedule());
	}
	
	public function testSetSchedule()
	{
		$datea = getdate();
		$year = $datea['year'];
		$yearAppend = ['', ' *', ' */5', ' */10', ' '.$year, ' 1970-2099', ' 1970-2099/5', ' 2020-2030/10,2015,1999/5,*/5,2050-2060'];
		$schedules = ['* * * * *', 
			'* * ? * *', '* * * * ?',
			'*/5 * * * *', '* */5 * * *', '* * */5 * *', '* * * */2 *', '* * * * */2',
			'*/15 * * * *', '* */15 * * *', '* * */15 * *', '* * * */10 *',
			'*/59 * * * *', '* */23 * * *', '* * */31 * *',  '* * * */12 *', '* * * * */6',
			'0 0 1 1 ?', '59 23 31 12 ?', '0 0 ? 1 0', '59 23 ? 12 6',
			'0 0 1 1 *', '59 23 31 12 *', '0 0 * 1 0', '59 23 * 12 6',
			'0-59 0-23 1-31 1-12 ?', '0-59 0-23 ? 1-12 0-6',
			'0-59 0-23 1-31 1-12 *', '0-59 0-23 * 1-12 0-6',
			'2-7/2 3-6/2 1-6/2 1-7/2 *', '2-7/2 3-6/2 * 1-7/2 0-5/2',
			'2-7/59 3-6/23 1-6/31 1-7/6 *', '2-7/59 3-6/23 * 1-7/12 0-5/6',
			'11-57/2 11-19/2 12-23/2 10-12/2 *',
			'11-57/59 11-19/23 12-23/31 10-12/12 ?',
			'1,*/10,12,25/10,30-40,40-50/2,0/35 * * * ?',
			'0/10,1,12,25/10,30-40,40-50/2,0/35 * * * ?',
			'*/10,1,12,25/10,30-40,40-50/2,0/35 * * * ?',
			'0/10,1,12,25/10,30-40,40-50/2,*/35 * * * ?',
			'* 1,*/5,12,12/10,7-11,12-20/3 * * ?',
			'* 0/5,1,12,12/10,7-11,12-20/3 * * ?',
			'* */5,1,12,12/10,7-11,12-20/3 * * ?',
			'* 1,12,12/10,7-11,12-20/3,*/5 * * ?',
			'* * 1,*/10,12,12/10,15-20,20-30/3,1/13 * ?',
			'* * 1/10,1,12,12/10,15-20,20-30/3,1/13 * ?',
			'* * */10,1,12,12/10,15-20,20-30/3,1/13 * ?',
			'* * 1,12,12/10,15-20,20-30/3,1/13,*/10 * ?',
			'* * * 1,*/5,12,10/10,1-12,1-8/3,1/6 ?',
			'* * * 1/5,1,12,10/10,1-12,1-8/3,1/6 ?',
			'* * * */5,1,12,10/10,1-12,1-8/3,1/6 ?',
			'* * * 1,12,10/10,1-12,1-8/3,1/6,*/5 ?',
			'* * ? * 1,*/3,2/3,3-5,1-5/4',
			'* * ? * 0/3,1,2/3,3-5,1-5/4',
			'* * ? * */3,1,2/3,3-5,1-5/4',
			'* * ? * 1,2/3,3-5,1-5/4,*/3',
			'* * * jan ?',
			'* * * feb ?',
			'* * * mar ?',
			'* * * apr ?',
			'* * * may ?',
			'* * * jun ?',
			'* * * jul ?',
			'* * * aug ?',
			'* * * sep ?',
			'* * * oct ?',
			'* * * nov ?',
			'* * * dec ?',
			'* * * DEC ?',
			'* * * jan-jul *',
			'* * * feb-aug/3 *',
			'* * * mar-sep/3,apr-oct/2 *',
			'* * * अप्रैल-Сентябрь/3,Апр-déc/2 *',
			
			'* * ? * sun',
			'* * ? * mon',
			'* * ? * tue',
			'* * * * wed',
			'* * * * thu',
			'* * * * fri',
			'* * * * sat',
			'* * * * SAT',
			'* * * * sun-wed',
			'* * * * mon-thu/2',
			'* * * * tue-fri/2,wed-sat/4',
			'* * ? Дек सोम', //Check other languages
			
			'* * 1W * ?',
			'* * 15W * ?',
			'* * 3W/3 * ?',
			'* * 31W * ?',
			'* * 1W,15W,6W-10W,31W * ?',
			'* * L * ?',
			'* * L-1 * ?',
			'* * L-2 * ?',
			'* * L-3 * ?',
			'* * L-4 * ?',
			'* * L-5 * ?',
			'* * L,L-3,3 * ?',
			'* * L,L-3,3W * ?',
			'* * L,L-3,3W/3 * ?',
			
			'* * ? * 0L',
			'* * ? * 1L',
			'* * ? * 6L',
			'* * ? * 1L,3L,5L',
			
			'* * ? * 0#1',
			'* * ? * 1#2',
			'* * ? * 2#3',
			'* * ? * 4#4',
			'* * ? * 6#5',
			'* * ? * 1#1,3#2,5#3,3#4,3L',
		];
		$scheduleErrors = ['* * ? * ?', '* * ? * ? *', '* * ?/5 * *', '* * * * ?/5 *',
			'*/60 * * * *', '* */24 * * *', '* * */32 * *', '* * * */13 *', '* * * * */7',
			'-1 * * * *', '* -1 * * *', '* * 0 * *', '* * * 0 *', '* * * * -1',
			'60 * * * *', '* 24 * * *', '* * 32 * *', '* * * 13 *', '* * * * 7',
			'50-64 * * * *', '* 20-30 * * *', '* * 30-40 * *', '* * * 10-20 *', '* * * * 5-10',
			'60-64 * * * *', '* 24-30 * * *', '* * 32-40 * *', '* * * 13-20 *', '* * * * 7-10',
			'0-59/60 * * * *', '* 0-23/24 * * *', '* * 0-31/32 * *', '* * * 1-12/13 *', '* * * * 0-6/7',
			'* * L-10 * ?', 
			'* * * * 7L', '* * ? * 0#6',
			'* * * * * 1969', '* * * * * 2100',
			'* * ?-8 * *', '* * * * ?-4',
			'* * ?/8 * *', '* * * * ?/4',
			'@', '@test'
		];
		foreach ($schedules as $schedule) {
			foreach ($yearAppend as $yap) {
				$this->obj->setSchedule($schedule . $yap);
				$this->assertEquals($schedule . $yap, $this->obj->getSchedule());
			}
		}
		foreach ($scheduleErrors as $schedule) {
			try {
				$this->obj->setSchedule($schedule);
				$this->fail('Did not raised TInvalidDataValueException on invalid Cron Schedule: ' . $schedule);
			} catch(Exception $e) {
			}
		}
		$analogs = ['@annually', '@yearly', '@monthly', '@weekly', '@daily', '@hourly'];
		foreach ($analogs as $schedule) {
			$this->obj->setSchedule($schedule);
			$this->assertEquals($schedule , $this->obj->getSchedule());
		}
		$this->obj->setSchedule($schedule = '@'.(time() - 120));
		$this->assertEquals($schedule , $this->obj->getSchedule());
	}
	public function testGetNextTriggerTime_AtSecond()
	{
		$time = time();
		$this->obj->setSchedule('@' . $time);
		$this->assertNull($this->obj->getNextTriggerTime($time));
		$this->assertNull($this->obj->getNextTriggerTime($time + 120));
		$this->assertEquals($time, $this->obj->getNextTriggerTime($time - 120));
	}
	public function testGetNextTriggerTime_Minute_Hour()
	{
		$this->obj->setSchedule(null);
		$this->assertNull($this->obj->getNextTriggerTime(time()));
		$this->nextMinuteSpectrum('* * * * *', round(time() / 60) * 60); //check minutes
		
		$this->nextMinuteSpectrum('* * * * *', strtotime("2020-12-01 00:00:00")); // Boundary condition: static month
		$this->nextMinuteSpectrum('* * * * *', strtotime("2021-01-01 00:00:00")); // Boundary condition: year
		$this->nextMinuteSpectrum('* * * * *', strtotime("2020-03-01 00:00:00")); // Boundary condition: changing months
		$this->nextMinuteSpectrum('* * * * *', strtotime("2020-02-29 00:00:00")); // Boundary condition: changing months
		$this->nextMinuteSpectrum('* * * * *', strtotime("2021-02-28 00:00:00")); // Boundary condition: changing months
		$this->nextMinuteSpectrum('* * * * *', strtotime("2021-03-01 00:00:00")); // Boundary condition: changing months
		
		{ // minute
			$this->assertTime(strtotime("2020-12-01 00:00:00"), '0 * * * *', strtotime("2020-11-30 23:59:40"));
			$this->assertTime(strtotime("2020-12-01 01:00:00"), '0 * * * *', strtotime("2020-12-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 * * * *', strtotime("2020-12-31 23:30:00"));
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 * * * *', strtotime("2020-12-31 23:59:40"));
			$this->assertTime(strtotime("2021-01-01 01:00:00"), '0 * * * *', strtotime("2021-01-01 00:00:00"));
			
			$this->assertTime(strtotime("2021-01-01 00:05:00"), '5 * * * *', strtotime("2020-12-31 23:45:34"));
			$this->assertTime(strtotime("2021-01-01 00:05:00"), '5 * * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 01:05:00"), '5 * * * *', strtotime("2021-01-01 00:05:00"));
			$this->assertTime(strtotime("2021-01-01 01:05:00"), '5 * * * *', strtotime("2021-01-01 00:10:00"));
			
			$this->assertTime(strtotime("2021-01-01 00:10:00"), '10/10 * * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 00:20:00"), '10/10 * * * *', strtotime("2021-01-01 00:10:00"));
			$this->assertTime(strtotime("2021-01-01 00:50:00"), '10/10 * * * *', strtotime("2021-01-01 00:40:00"));
			$this->assertTime(strtotime("2021-01-01 01:10:00"), '10/10 * * * *', strtotime("2021-01-01 00:50:00"));
			
			$this->assertTime(strtotime("2021-01-01 00:03:00"), '3-5 * * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 00:04:00"), '3-5 * * * *', strtotime("2021-01-01 00:03:00"));
			$this->assertTime(strtotime("2021-01-01 00:05:00"), '3-5 * * * *', strtotime("2021-01-01 00:04:00"));
			$this->assertTime(strtotime("2021-01-01 01:03:00"), '3-5 * * * *', strtotime("2021-01-01 00:05:00"));
			$this->assertTime(strtotime("2021-01-01 01:03:00"), '3-5 * * * *', strtotime("2021-01-01 00:06:00"));
			
			$this->assertTime(strtotime("2021-01-01 00:10:00"), '10-20/5 * * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 00:15:00"), '10-20/5 * * * *', strtotime("2021-01-01 00:10:00"));
			$this->assertTime(strtotime("2021-01-01 00:15:00"), '10-20/5 * * * *', strtotime("2021-01-01 00:12:00"));
			$this->assertTime(strtotime("2021-01-01 00:20:00"), '10-20/5 * * * *', strtotime("2021-01-01 00:15:00"));
			$this->assertTime(strtotime("2021-01-01 01:10:00"), '10-20/5 * * * *', strtotime("2021-01-01 00:20:00"));
		}
		{// hour
			$this->assertTime(strtotime("2020-12-01 00:00:00"), '* 0 * * *', strtotime("2020-11-30 23:30:30"));
			$this->assertTime(strtotime("2020-12-01 00:00:00"), '* 0 * * *', strtotime("2020-11-30 23:59:59"));
			$this->assertTime(strtotime("2020-12-01 00:01:00"), '* 0 * * *', strtotime("2020-12-01 00:00:00"));
			$this->assertTime(strtotime("2020-12-01 00:59:00"), '* 0 * * *', strtotime("2020-12-01 00:58:00"));
			$this->assertTime(strtotime("2020-12-02 00:00:00"), '* 0 * * *', strtotime("2020-12-01 00:59:00"));
			
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '* 0 * * *', strtotime("2020-12-31 23:30:30"));
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '* 0 * * *', strtotime("2020-12-31 23:59:59"));
			$this->assertTime(strtotime("2021-01-01 00:01:00"), '* 0 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 00:59:00"), '* 0 * * *', strtotime("2021-01-01 00:58:00"));
			$this->assertTime(strtotime("2021-01-02 00:00:00"), '* 0 * * *', strtotime("2021-01-01 00:59:00"));
			
			$this->assertTime(strtotime("2021-01-01 05:00:00"), '* 5 * * *', strtotime("2020-12-31 23:45:34"));
			$this->assertTime(strtotime("2021-01-01 05:00:00"), '* 5 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 05:00:00"), '* 5 * * *', strtotime("2021-01-01 04:59:59"));
			$this->assertTime(strtotime("2021-01-01 05:01:00"), '* 5 * * *', strtotime("2021-01-01 05:00:00"));
			$this->assertTime(strtotime("2021-01-01 05:59:00"), '* 5 * * *', strtotime("2021-01-01 05:58:59"));
			$this->assertTime(strtotime("2021-01-02 05:00:00"), '* 5 * * *', strtotime("2021-01-01 05:59:59"));
			
			$this->assertTime(strtotime("2021-01-01 10:00:00"), '* 10/10 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 10:00:00"), '* 10/10 * * *', strtotime("2021-01-01 00:05:00"));
			$this->assertTime(strtotime("2021-01-01 10:01:00"), '* 10/10 * * *', strtotime("2021-01-01 10:00:00"));
			$this->assertTime(strtotime("2021-01-01 10:59:00"), '* 10/10 * * *', strtotime("2021-01-01 10:58:00"));
			$this->assertTime(strtotime("2021-01-01 20:00:00"), '* 10/10 * * *', strtotime("2021-01-01 10:59:00"));
			$this->assertTime(strtotime("2021-01-01 20:01:00"), '* 10/10 * * *', strtotime("2021-01-01 20:00:00"));
			$this->assertTime(strtotime("2021-01-02 10:00:00"), '* 10/10 * * *', strtotime("2021-01-01 20:59:00"));
			
			$this->assertTime(strtotime("2021-01-01 03:00:00"), '* 3-5 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 03:00:00"), '* 3-5 * * *', strtotime("2021-01-01 02:59:59"));
			$this->assertTime(strtotime("2021-01-01 03:01:00"), '* 3-5 * * *', strtotime("2021-01-01 03:00:00"));
			$this->assertTime(strtotime("2021-01-01 04:01:00"), '* 3-5 * * *', strtotime("2021-01-01 04:00:00"));
			$this->assertTime(strtotime("2021-01-01 05:00:00"), '* 3-5 * * *', strtotime("2021-01-01 04:59:59"));
			$this->assertTime(strtotime("2021-01-01 05:01:00"), '* 3-5 * * *', strtotime("2021-01-01 05:00:00"));
			$this->assertTime(strtotime("2021-01-02 03:00:00"), '* 3-5 * * *', strtotime("2021-01-01 05:59:45"));
			$this->assertTime(strtotime("2021-01-02 03:00:00"), '* 3-5 * * *', strtotime("2021-01-01 06:00:00"));
			
			$this->assertTime(strtotime("2021-01-01 10:00:00"), '* 10-20/5 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 10:00:00"), '* 10-20/5 * * *', strtotime("2021-01-01 09:59:59"));
			$this->assertTime(strtotime("2021-01-01 10:01:00"), '* 10-20/5 * * *', strtotime("2021-01-01 10:00:00"));
			$this->assertTime(strtotime("2021-01-01 10:30:00"), '* 10-20/5 * * *', strtotime("2021-01-01 10:29:00"));
			$this->assertTime(strtotime("2021-01-01 15:00:00"), '* 10-20/5 * * *', strtotime("2021-01-01 10:59:00"));
			$this->assertTime(strtotime("2021-01-01 15:00:00"), '* 10-20/5 * * *', strtotime("2021-01-01 10:59:59"));
			$this->assertTime(strtotime("2021-01-01 15:00:00"), '* 10-20/5 * * *', strtotime("2021-01-01 11:00:00"));
			$this->assertTime(strtotime("2021-01-01 15:00:00"), '* 10-20/5 * * *', strtotime("2021-01-01 14:59:59"));
			$this->assertTime(strtotime("2021-01-01 15:01:00"), '* 10-20/5 * * *', strtotime("2021-01-01 15:00:00"));
			$this->assertTime(strtotime("2021-01-01 20:00:00"), '* 10-20/5 * * *', strtotime("2021-01-01 15:59:59"));
			$this->assertTime(strtotime("2021-01-01 20:01:00"), '* 10-20/5 * * *', strtotime("2021-01-01 20:00:00"));
			$this->assertTime(strtotime("2021-01-02 10:00:00"), '* 10-20/5 * * *', strtotime("2021-01-01 20:59:00"));
			$this->assertTime(strtotime("2021-01-02 10:00:00"), '* 10-20/5 * * *', strtotime("2021-01-01 23:00:00"));
		}
		{ // Hour and Minute
			$this->assertTime(strtotime("2020-12-01 00:00:00"), '0 0 * * *', strtotime("2020-11-30 23:30:30"));
			$this->assertTime(strtotime("2020-12-01 00:00:00"), '0 0 * * *', strtotime("2020-11-30 23:59:59"));
			$this->assertTime(strtotime("2020-12-02 00:00:00"), '0 0 * * *', strtotime("2020-12-01 00:00:00"));
			$this->assertTime(strtotime("2020-12-02 00:00:00"), '0 0 * * *', strtotime("2020-12-01 00:58:00"));
			$this->assertTime(strtotime("2020-12-02 00:00:00"), '0 0 * * *', strtotime("2020-12-01 00:59:00"));
			
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 * * *', strtotime("2020-12-31 23:30:30"));
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 * * *', strtotime("2020-12-31 23:59:59"));
			$this->assertTime(strtotime("2021-01-02 00:00:00"), '0 0 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-02 00:00:00"), '0 0 * * *', strtotime("2021-01-01 00:58:00"));
			$this->assertTime(strtotime("2021-01-02 00:00:00"), '0 0 * * *', strtotime("2021-01-01 00:59:00"));
			
			$this->assertTime(strtotime("2021-01-01 05:05:00"), '5 5 * * *', strtotime("2020-12-31 23:45:34"));
			$this->assertTime(strtotime("2021-01-01 05:05:00"), '5 5 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 05:05:00"), '5 5 * * *', strtotime("2021-01-01 04:59:59"));
			$this->assertTime(strtotime("2021-01-01 05:05:00"), '5 5 * * *', strtotime("2021-01-01 05:04:59"));
			$this->assertTime(strtotime("2021-01-02 05:05:00"), '5 5 * * *', strtotime("2021-01-01 05:05:00"));
			$this->assertTime(strtotime("2021-01-02 05:05:00"), '5 5 * * *', strtotime("2021-01-01 10:00:00"));
			
			$this->assertTime(strtotime("2021-01-01 10:10:00"), '10 10 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 10:10:00"), '10 10 * * *', strtotime("2021-01-01 04:35:00"));
			$this->assertTime(strtotime("2021-01-01 10:10:00"), '10 10 * * *', strtotime("2021-01-01 10:09:59"));
			$this->assertTime(strtotime("2021-01-02 10:10:00"), '10 10 * * *', strtotime("2021-01-01 10:10:00"));
			$this->assertTime(strtotime("2021-01-02 10:10:00"), '10 10 * * *', strtotime("2021-01-01 10:58:00"));
			$this->assertTime(strtotime("2021-01-02 10:10:00"), '10 10 * * *', strtotime("2021-01-01 10:59:00"));
			$this->assertTime(strtotime("2021-01-02 10:10:00"), '10 10 * * *', strtotime("2021-01-01 11:00:00"));
			
			$this->assertTime(strtotime("2021-01-01 03:15:00"), '15 3-5 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 03:15:00"), '15 3-5 * * *', strtotime("2021-01-01 02:59:59"));
			$this->assertTime(strtotime("2021-01-01 03:15:00"), '15 3-5 * * *', strtotime("2021-01-01 03:14:59"));
			$this->assertTime(strtotime("2021-01-01 04:15:00"), '15 3-5 * * *', strtotime("2021-01-01 03:15:00"));
			$this->assertTime(strtotime("2021-01-01 04:15:00"), '15 3-5 * * *', strtotime("2021-01-01 04:00:00"));
			$this->assertTime(strtotime("2021-01-01 04:15:00"), '15 3-5 * * *', strtotime("2021-01-01 04:14:59"));
			$this->assertTime(strtotime("2021-01-01 05:15:00"), '15 3-5 * * *', strtotime("2021-01-01 04:15:00"));
			$this->assertTime(strtotime("2021-01-01 05:15:00"), '15 3-5 * * *', strtotime("2021-01-01 05:00:00"));
			$this->assertTime(strtotime("2021-01-02 03:15:00"), '15 3-5 * * *', strtotime("2021-01-01 05:15:00"));
			$this->assertTime(strtotime("2021-01-02 03:15:00"), '15 3-5 * * *', strtotime("2021-01-01 05:59:45"));
			$this->assertTime(strtotime("2021-01-02 03:15:00"), '15 3-5 * * *', strtotime("2021-01-01 23:59:59"));
			
			$this->assertTime(strtotime("2021-01-01 10:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 10:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 09:59:59"));
			$this->assertTime(strtotime("2021-01-01 10:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 10:29:59"));
			$this->assertTime(strtotime("2021-01-01 15:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 10:30:00"));
			$this->assertTime(strtotime("2021-01-01 15:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 10:59:00"));
			$this->assertTime(strtotime("2021-01-01 15:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 14:59:59"));
			$this->assertTime(strtotime("2021-01-01 15:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 15:29:59"));
			$this->assertTime(strtotime("2021-01-01 20:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 15:30:00"));
			$this->assertTime(strtotime("2021-01-01 20:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 15:59:59"));
			$this->assertTime(strtotime("2021-01-01 20:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 20:00:00"));
			$this->assertTime(strtotime("2021-01-01 20:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 20:29:58"));
			$this->assertTime(strtotime("2021-01-02 10:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 20:30:00"));
			$this->assertTime(strtotime("2021-01-02 10:30:00"), '30 10-20/5 * * *', strtotime("2021-01-01 23:00:00"));
			
			$this->assertTime(strtotime("2021-01-01 10:30:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 10:30:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 09:59:59"));
			$this->assertTime(strtotime("2021-01-01 10:30:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 10:29:59"));
			$this->assertTime(strtotime("2021-01-01 10:31:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 10:30:00"));
			$this->assertTime(strtotime("2021-01-01 10:40:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 10:39:00"));
			$this->assertTime(strtotime("2021-01-01 15:30:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 10:40:00"));
			$this->assertTime(strtotime("2021-01-01 15:30:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 14:59:59"));
			$this->assertTime(strtotime("2021-01-01 15:30:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 15:29:59"));
			$this->assertTime(strtotime("2021-01-01 15:31:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 15:30:00"));
			$this->assertTime(strtotime("2021-01-01 15:40:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 15:39:00"));
			$this->assertTime(strtotime("2021-01-01 20:30:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 15:40:00"));
			$this->assertTime(strtotime("2021-01-01 20:30:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 20:00:00"));
			$this->assertTime(strtotime("2021-01-01 20:30:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 20:29:58"));
			$this->assertTime(strtotime("2021-01-01 20:31:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 20:30:00"));
			$this->assertTime(strtotime("2021-01-01 20:39:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 20:38:00"));
			$this->assertTime(strtotime("2021-01-01 20:40:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 20:39:00"));
			$this->assertTime(strtotime("2021-01-02 10:30:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 20:40:00"));
			$this->assertTime(strtotime("2021-01-02 10:30:00"), '30-40 10-20/5 * * *', strtotime("2021-01-01 23:00:00"));
			
			$this->assertTime(strtotime("2021-01-01 10:30:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 10:30:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 09:59:59"));
			$this->assertTime(strtotime("2021-01-01 10:30:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 10:29:59"));
			$this->assertTime(strtotime("2021-01-01 10:35:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 10:30:00"));
			$this->assertTime(strtotime("2021-01-01 10:40:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 10:39:00"));
			$this->assertTime(strtotime("2021-01-01 15:30:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 10:40:00"));
			$this->assertTime(strtotime("2021-01-01 15:30:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 14:59:59"));
			$this->assertTime(strtotime("2021-01-01 15:30:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 15:29:59"));
			$this->assertTime(strtotime("2021-01-01 15:35:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 15:30:00"));
			$this->assertTime(strtotime("2021-01-01 15:40:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 15:39:00"));
			$this->assertTime(strtotime("2021-01-01 20:30:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 15:40:00"));
			$this->assertTime(strtotime("2021-01-01 20:30:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 20:00:00"));
			$this->assertTime(strtotime("2021-01-01 20:30:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 20:29:58"));
			$this->assertTime(strtotime("2021-01-01 20:35:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 20:30:00"));
			$this->assertTime(strtotime("2021-01-01 20:40:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 20:38:00"));
			$this->assertTime(strtotime("2021-01-01 20:40:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 20:39:00"));
			$this->assertTime(strtotime("2021-01-02 10:30:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 20:40:00"));
			$this->assertTime(strtotime("2021-01-02 10:30:00"), '30-40/5 10-20/5 * * *', strtotime("2021-01-01 23:00:00"));
		}
		{ // testing multiple elements in different orders and formats
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '*/5,10-19,20-30/3,2 */5,10-15,20-23/3,2 * * *', strtotime("2020-12-31 23:58:00"));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:02:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:00:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:05:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:02:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:10:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:05:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:20:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:19:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:23:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:20:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:25:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:23:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:26:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:25:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:29:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:26:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:30:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:29:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:35:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:30:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:55:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:50:00"))));
			
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '2,10-19,20-30/3,*/5 2,10-15,19-23/3,*/5 * * *', strtotime("2020-12-31 23:59:00"));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:02:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:00:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:05:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:02:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:10:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:05:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:20:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:19:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:23:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:20:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:25:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:23:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:26:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:25:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:29:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:26:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:30:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:29:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:35:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:30:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:55:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:50:00"))));
			
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '0/5,10-19,20-30/3,2 0/5,10-15,20-23/3,2 * * *', strtotime("2020-12-31 23:58:00"));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:02:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:00:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:05:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:02:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:10:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:05:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:20:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:19:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:23:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:20:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:25:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:23:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:26:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:25:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:29:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:26:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:30:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:29:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:35:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:30:00"))));
			$this->assertEquals(dfo(strtotime("2021-01-01 00:55:00")), dfo($this->obj->getNextTriggerTime(strtotime("2021-01-01 00:50:00"))));
		}
		
	}
	protected function nextMinuteSpectrum($schedule, $startTime)
	{
		$this->obj->setSchedule($schedule);
		
		//These are unrolled, if/when, to see the errors
		$s = $startTime - 60 - 2;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		
		$s = $startTime - 2;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		
		$s = $startTime + 60 - 2;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
		$s++;
		$nextTime = ceil(($s + 1) / 60) * 60;
		$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)));
	}
	
	
	public function testGetNextTriggerTime_DayOfMonth()
	{	
		// go through DoM */5, 10/5, 5-10, 5-15/5, array
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '* * 1 * *', strtotime("2020-12-15 03:15:45"));
		$this->assertTime(strtotime("2021-01-01 00:01:00"), '* * 1 * *', strtotime("2021-01-01 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '* 0 1 * *', strtotime("2020-12-15 03:15:45"));
		$this->assertTime(strtotime("2021-01-01 00:01:00"), '* 0 1 * *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-01-01 00:02:00"), '* 0 1 * *', strtotime("2021-01-01 00:01:00"));
		$this->assertTime(strtotime("2021-01-01 00:59:00"), '* 0 1 * *', strtotime("2021-01-01 00:58:00"));
		$this->assertTime(strtotime("2021-02-01 00:00:00"), '* 0 1 * *', strtotime("2021-01-01 00:59:00"));
		$this->assertTime(strtotime("2021-02-01 00:01:00"), '* 0 1 * *', strtotime("2021-02-01 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 * 1 * *', strtotime("2020-12-15 03:15:45"));
		$this->assertTime(strtotime("2021-01-01 01:00:00"), '0 * 1 * *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-01-01 02:00:00"), '0 * 1 * *', strtotime("2021-01-01 01:00:00"));
		$this->assertTime(strtotime("2021-02-01 00:00:00"), '0 * 1 * *', strtotime("2021-01-01 23:00:00"));
		
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 1 * *', strtotime("2020-12-15 03:15:45"));
		$this->assertTime(strtotime("2021-02-01 00:00:00"), '0 0 1 * *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2022-01-01 00:00:00"), '0 0 1 * *', strtotime("2021-12-01 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 */3 * *', strtotime("2020-12-31 18:34:00"));
		$this->assertTime(strtotime("2021-01-04 00:00:00"), '0 0 */3 * *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-01-07 00:00:00"), '0 0 */3 * *', strtotime("2021-01-04 00:00:00"));
		$this->assertTime(strtotime("2021-01-10 00:00:00"), '0 0 */3 * *', strtotime("2021-01-07 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-05 00:00:00"), '0 0 5/3 * *', strtotime("2020-12-31 18:34:00"));
		$this->assertTime(strtotime("2021-01-08 00:00:00"), '0 0 5/3 * *', strtotime("2021-01-05 00:00:00"));
		$this->assertTime(strtotime("2021-01-11 00:00:00"), '0 0 5/3 * *', strtotime("2021-01-08 00:00:00"));
		$this->assertTime(strtotime("2021-01-14 00:00:00"), '0 0 5/3 * *', strtotime("2021-01-11 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-05 00:00:00"), '0 0 5-7 * *', strtotime("2020-12-31 18:34:00"));
		$this->assertTime(strtotime("2021-01-06 00:00:00"), '0 0 5-7 * *', strtotime("2021-01-05 00:00:00"));
		$this->assertTime(strtotime("2021-01-07 00:00:00"), '0 0 5-7 * *', strtotime("2021-01-06 00:00:00"));
		$this->assertTime(strtotime("2021-02-05 00:00:00"), '0 0 5-7 * *', strtotime("2021-01-07 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-05 00:00:00"), '0 0 5-9/2 * *', strtotime("2020-12-31 18:34:00"));
		$this->assertTime(strtotime("2021-01-07 00:00:00"), '0 0 5-9/2 * *', strtotime("2021-01-05 00:00:00"));
		$this->assertTime(strtotime("2021-01-09 00:00:00"), '0 0 5-9/2 * *', strtotime("2021-01-07 00:00:00"));
		$this->assertTime(strtotime("2021-02-05 00:00:00"), '0 0 5-9/2 * *', strtotime("2021-01-09 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2020-12-31 18:34:00"));
		$this->assertTime(strtotime("2021-01-02 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-01-06 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-02 00:00:00"));
		$this->assertTime(strtotime("2021-01-10 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-06 00:00:00"));
		$this->assertTime(strtotime("2021-01-16 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-15 00:00:00"));
		$this->assertTime(strtotime("2021-01-20 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-16 00:00:00"));
		$this->assertTime(strtotime("2021-01-21 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-20 00:00:00"));
		$this->assertTime(strtotime("2021-01-23 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-21 00:00:00"));
		$this->assertTime(strtotime("2021-01-26 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-23 00:00:00"));
		$this->assertTime(strtotime("2021-01-31 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-26 00:00:00"));
		$this->assertTime(strtotime("2021-02-01 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-31 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 */5,10-15,20-23/3,2 * *', strtotime("2020-12-31 18:34:00"));
		$this->assertTime(strtotime("2021-01-02 00:00:00"), '0 0 */5,10-15,20-23/3,2 * *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-01-06 00:00:00"), '0 0 */5,10-15,20-23/3,2 * *', strtotime("2021-01-02 00:00:00"));
		$this->assertTime(strtotime("2021-01-10 00:00:00"), '0 0 */5,10-15,20-23/3,2 * *', strtotime("2021-01-06 00:00:00"));
		$this->assertTime(strtotime("2021-01-16 00:00:00"), '0 0 */5,10-15,20-23/3,2 * *', strtotime("2021-01-15 00:00:00"));
		$this->assertTime(strtotime("2021-01-20 00:00:00"), '0 0 */5,10-15,20-23/3,2 * *', strtotime("2021-01-16 00:00:00"));
		$this->assertTime(strtotime("2021-01-21 00:00:00"), '0 0 */5,10-15,20-23/3,2 * *', strtotime("2021-01-20 00:00:00"));
		$this->assertTime(strtotime("2021-01-23 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-21 00:00:00"));
		$this->assertTime(strtotime("2021-01-26 00:00:00"), '0 0 */5,10-15,20-23/3,2 * *', strtotime("2021-01-23 00:00:00"));
		$this->assertTime(strtotime("2021-01-31 00:00:00"), '0 0 */5,10-15,20-23/3,2 * *', strtotime("2021-01-26 00:00:00"));
		$this->assertTime(strtotime("2021-02-01 00:00:00"), '0 0 */5,10-15,20-23/3,2 * *', strtotime("2021-01-31 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 2,10-15,20-23/3,*/5 * *', strtotime("2020-12-31 18:34:00"));
		$this->assertTime(strtotime("2021-01-02 00:00:00"), '0 0 2,10-15,20-23/3,*/5 * *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-01-06 00:00:00"), '0 0 2,10-15,20-23/3,*/5 * *', strtotime("2021-01-02 00:00:00"));
		$this->assertTime(strtotime("2021-01-10 00:00:00"), '0 0 2,10-15,20-23/3,*/5 * *', strtotime("2021-01-06 00:00:00"));
		$this->assertTime(strtotime("2021-01-16 00:00:00"), '0 0 2,10-15,20-23/3,*/5 * *', strtotime("2021-01-15 00:00:00"));
		$this->assertTime(strtotime("2021-01-20 00:00:00"), '0 0 2,10-15,20-23/3,*/5 * *', strtotime("2021-01-16 00:00:00"));
		$this->assertTime(strtotime("2021-01-21 00:00:00"), '0 0 2,10-15,20-23/3,*/5 * *', strtotime("2021-01-20 00:00:00"));
		$this->assertTime(strtotime("2021-01-23 00:00:00"), '0 0 1/5,10-15,20-23/3,2 * *', strtotime("2021-01-21 00:00:00"));
		$this->assertTime(strtotime("2021-01-26 00:00:00"), '0 0 2,10-15,20-23/3,*/5 * *', strtotime("2021-01-23 00:00:00"));
		$this->assertTime(strtotime("2021-01-31 00:00:00"), '0 0 2,10-15,20-23/3,*/5 * *', strtotime("2021-01-26 00:00:00"));
		$this->assertTime(strtotime("2021-02-01 00:00:00"), '0 0 2,10-15,20-23/3,*/5 * *', strtotime("2021-01-31 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-31 00:00:00"), '0 0 L * *', strtotime("2021-01-01 23:59:00"));
		$this->assertTime(strtotime("2021-02-28 00:00:00"), '0 0 L * *', strtotime("2021-01-31 00:00:00"));
		$this->assertTime(strtotime("2021-03-31 00:00:00"), '0 0 L * *', strtotime("2021-02-28 00:00:00"));
		$this->assertTime(strtotime("2021-04-30 00:00:00"), '0 0 L * *', strtotime("2021-03-31 00:00:00"));
		$this->assertTime(strtotime("2021-04-29 00:00:00"), '0 0 L-1 * *', strtotime("2021-03-31 00:00:00"));
		$this->assertTime(strtotime("2021-04-28 00:00:00"), '0 0 L-2 * *', strtotime("2021-03-31 00:00:00"));
		$this->assertTime(strtotime("2021-04-27 00:00:00"), '0 0 L-3 * *', strtotime("2021-03-31 00:00:00"));
		$this->assertTime(strtotime("2021-04-26 00:00:00"), '0 0 L-4 * *', strtotime("2021-03-31 00:00:00"));
		$this->assertTime(strtotime("2021-04-25 00:00:00"), '0 0 L-5 * *', strtotime("2021-03-31 00:00:00"));
		$this->assertTime(strtotime("2021-05-31 00:00:00"), '0 0 L * *', strtotime("2021-04-30 00:00:00"));
		$this->assertTime(strtotime("2021-05-30 00:00:00"), '0 0 L-1 * *', strtotime("2021-04-29 00:00:00"));
		$this->assertTime(strtotime("2021-05-29 00:00:00"), '0 0 L-2 * *', strtotime("2021-04-28 00:00:00"));
		$this->assertTime(strtotime("2021-05-28 00:00:00"), '0 0 L-3 * *', strtotime("2021-04-27 00:00:00"));
		$this->assertTime(strtotime("2021-05-27 00:00:00"), '0 0 L-4 * *', strtotime("2021-04-26 00:00:00"));
		$this->assertTime(strtotime("2021-05-26 00:00:00"), '0 0 L-5 * *', strtotime("2021-04-25 00:00:00"));
		$this->assertTime(strtotime("2021-06-29 00:00:00"), '0 0 L-1 * *', strtotime("2021-05-30 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 2W * *', strtotime("2020-12-31 23:59:59"));
		$this->assertTime(strtotime("2021-02-02 00:00:00"), '0 0 2W * *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-01-18 00:00:00"), '0 0 17W * *', strtotime("2021-01-01 01:00:00"));
		$this->assertTime(strtotime("2021-02-02 00:00:00"), '0 0 2W,17W * *', strtotime("2021-01-18 00:00:00"));
		$this->assertTime(strtotime("2021-02-17 00:00:00"), '0 0 2W,17W * *', strtotime("2021-02-02 00:00:00"));
		
		$this->assertTime(strtotime("2022-01-03 00:00:00"), '0 0 1W * *', strtotime("2021-12-31 23:59:00"));
		$this->assertTime(strtotime("2021-01-29 00:00:00"), '0 0 31W * *', strtotime("2021-01-03 23:59:00"));
		$this->assertTime(strtotime("2021-02-01 00:00:00"), '0 0 1W,28W * *', strtotime("2021-01-29 00:00:00"));
		$this->assertTime(strtotime("2021-02-26 00:00:00"), '0 0 1W,28W * *', strtotime("2021-02-01 00:00:00"));
		
		$this->assertTime(strtotime("2022-01-03 00:00:00"), '0 0 1W-5 * *', strtotime("2021-12-31 23:59:00"));
		$this->assertTime(strtotime("2022-01-04 00:00:00"), '0 0 1W-5 * *', strtotime("2022-01-03 00:00:00"));
		$this->assertTime(strtotime("2022-01-05 00:00:00"), '0 0 1W-5 * *', strtotime("2022-01-04 00:00:00"));
		$this->assertTime(strtotime("2022-02-01 00:00:00"), '0 0 1W-5 * *', strtotime("2022-01-05 00:00:00"));
		
		$this->assertTime(strtotime("2022-01-03 00:00:00"), '0 0 1W-8W * *', strtotime("2021-12-31 23:59:00"));
		$this->assertTime(strtotime("2022-01-04 00:00:00"), '0 0 1W-8W * *', strtotime("2022-01-03 00:00:00"));
		$this->assertTime(strtotime("2022-01-05 00:00:00"), '0 0 1W-8W * *', strtotime("2022-01-04 00:00:00"));
		$this->assertTime(strtotime("2022-01-06 00:00:00"), '0 0 1W-8W * *', strtotime("2022-01-05 00:00:00"));
		$this->assertTime(strtotime("2022-01-07 00:00:00"), '0 0 1W-8W * *', strtotime("2022-01-06 00:00:00"));
		$this->assertTime(strtotime("2022-02-01 00:00:00"), '0 0 1W-8W * *', strtotime("2022-01-07 00:00:00"));
		
		$this->assertTime(strtotime("2022-01-03 00:00:00"), '0 0 1W-8W/2 * *', strtotime("2021-12-31 23:59:00"));
		$this->assertTime(strtotime("2022-01-05 00:00:00"), '0 0 1W-8W/2 * *', strtotime("2022-01-03 00:00:00"));
		$this->assertTime(strtotime("2022-01-07 00:00:00"), '0 0 1W-8W/2 * *', strtotime("2022-01-05 00:00:00"));
		$this->assertTime(strtotime("2022-02-01 00:00:00"), '0 0 1W-8W/2 * *', strtotime("2022-01-07 00:00:00"));
	}
	
	
	public function testGetNextTriggerTime_MonthOfYear()
	{
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '* * * 1 *', strtotime("2020-12-15 03:15:45"));
		$this->assertTime(strtotime("2021-01-01 00:01:00"), '* * * 1 *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2022-01-01 00:00:00"), '* * * 1 *', strtotime("2021-01-31 23:59:00"));
		{
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '* * 1 1 *', strtotime("2020-12-15 03:15:45"));
			$this->assertTime(strtotime("2021-01-01 00:01:00"), '* * 1 1 *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 00:02:00"), '* * 1 1 *', strtotime("2021-01-01 00:01:00"));
			$this->assertTime(strtotime("2021-01-01 00:59:00"), '* * 1 1 *', strtotime("2021-01-01 00:58:00"));
			$this->assertTime(strtotime("2021-01-01 01:00:00"), '* * 1 1 *', strtotime("2021-01-01 00:59:00"));
			$this->assertTime(strtotime("2022-01-01 00:00:00"), '* * 1 1 *', strtotime("2021-01-01 23:59:00"));
			$this->assertTime(strtotime("2022-01-01 00:01:00"), '* * 1 1 *', strtotime("2022-01-01 00:00:00"));
			
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '* 0 1 1 *', strtotime("2020-12-15 03:15:45"));
			$this->assertTime(strtotime("2021-01-01 00:01:00"), '* 0 1 1 *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 00:02:00"), '* 0 1 1 *', strtotime("2021-01-01 00:01:00"));
			$this->assertTime(strtotime("2021-01-01 00:59:00"), '* 0 1 1 *', strtotime("2021-01-01 00:58:00"));
			$this->assertTime(strtotime("2022-01-01 00:00:00"), '* 0 1 1 *', strtotime("2021-01-01 00:59:00"));
			$this->assertTime(strtotime("2022-01-01 00:01:00"), '* 0 1 1 *', strtotime("2022-01-01 00:00:00"));
			
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 * 1 1 *', strtotime("2020-12-15 03:15:45"));
			$this->assertTime(strtotime("2021-01-01 01:00:00"), '0 * 1 1 *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 02:00:00"), '0 * 1 1 *', strtotime("2021-01-01 01:00:00"));
			$this->assertTime(strtotime("2022-01-01 00:00:00"), '0 * 1 1 *', strtotime("2021-01-01 23:00:00"));
			
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 1 1 *', strtotime("2020-12-15 03:15:45"));
			$this->assertTime(strtotime("2022-01-01 00:00:00"), '0 0 1 1 *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2022-01-01 00:00:00"), '0 0 1 1 *', strtotime("2021-12-01 00:00:00"));
		}
		{
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '* 0 * 1 *', strtotime("2020-12-15 03:15:45"));
			$this->assertTime(strtotime("2021-01-01 00:01:00"), '* 0 * 1 *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 00:02:00"), '* 0 * 1 *', strtotime("2021-01-01 00:01:00"));
			$this->assertTime(strtotime("2021-01-01 00:59:00"), '* 0 * 1 *', strtotime("2021-01-01 00:58:00"));
			$this->assertTime(strtotime("2021-01-02 00:00:00"), '* 0 * 1 *', strtotime("2021-01-01 00:59:00"));
			$this->assertTime(strtotime("2021-01-02 00:01:00"), '* 0 * 1 *', strtotime("2021-01-02 00:00:00"));
			$this->assertTime(strtotime("2022-01-01 00:00:00"), '* 0 * 1 *', strtotime("2021-01-31 23:50:00"));
			
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 * * 1 *', strtotime("2020-12-15 03:15:45"));
			$this->assertTime(strtotime("2021-01-01 01:00:00"), '0 * * 1 *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 02:00:00"), '0 * * 1 *', strtotime("2021-01-01 01:00:00"));
			$this->assertTime(strtotime("2021-01-02 00:00:00"), '0 * * 1 *', strtotime("2021-01-01 23:00:00"));
			
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 * 1 *', strtotime("2020-12-15 03:15:45"));
			$this->assertTime(strtotime("2021-01-02 00:00:00"), '0 0 * 1 *', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2022-01-01 00:00:00"), '0 0 * 1 *', strtotime("2021-01-31 00:00:00"));
		}
		
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 1 */3 *', strtotime("2020-12-31 18:34:00"));
		$this->assertTime(strtotime("2021-04-01 00:00:00"), '0 0 1 */3 *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-07-01 00:00:00"), '0 0 1 */3 *', strtotime("2021-04-01 00:00:00"));
		$this->assertTime(strtotime("2021-10-01 00:00:00"), '0 0 1 */3 *', strtotime("2021-07-01 00:00:00"));
		
		$this->assertTime(strtotime("2021-05-01 00:00:00"), '0 0 1 5/3 *', strtotime("2020-12-31 18:34:00"));
		$this->assertTime(strtotime("2021-08-01 00:00:00"), '0 0 1 5/3 *', strtotime("2021-05-01 00:00:00"));
		$this->assertTime(strtotime("2021-11-01 00:00:00"), '0 0 1 5/3 *', strtotime("2021-08-01 00:00:00"));
		$this->assertTime(strtotime("2022-05-01 00:00:00"), '0 0 1 5/3 *', strtotime("2021-11-01 00:00:00"));
		
		$this->assertTime(strtotime("2021-05-01 00:00:00"), '0 0 1 5-7 *', strtotime("2020-12-31 18:34:00"));
		$this->assertTime(strtotime("2021-06-01 00:00:00"), '0 0 1 5-7 *', strtotime("2021-05-01 00:00:00"));
		$this->assertTime(strtotime("2021-07-01 00:00:00"), '0 0 1 5-7 *', strtotime("2021-06-01 00:00:00"));
		$this->assertTime(strtotime("2022-05-01 00:00:00"), '0 0 1 5-7 *', strtotime("2021-07-01 00:00:00"));
		
		$this->assertTime(strtotime("2021-05-01 00:00:00"), '0 0 1 5-9/2 *', strtotime("2020-12-31 18:34:00"));
		$this->assertTime(strtotime("2021-07-01 00:00:00"), '0 0 1 5-9/2 *', strtotime("2021-05-01 00:00:00"));
		$this->assertTime(strtotime("2021-09-01 00:00:00"), '0 0 1 5-9/2 *', strtotime("2021-07-01 00:00:00"));
		$this->assertTime(strtotime("2022-05-01 00:00:00"), '0 0 1 5-9/2 *', strtotime("2021-09-01 00:00:00"));
		
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 1 */5,2,11-12,5-9/2 *', strtotime("2020-12-31 00:00:00"));
		$this->assertTime(strtotime("2021-02-01 00:00:00"), '0 0 1 */5,2,11-12,5-9/2 *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-05-01 00:00:00"), '0 0 1 */5,2,11-12,5-9/2 *', strtotime("2021-02-01 00:00:00"));
		$this->assertTime(strtotime("2021-06-01 00:00:00"), '0 0 1 */5,2,11-12,5-9/2 *', strtotime("2021-05-01 00:00:00"));
		$this->assertTime(strtotime("2021-07-01 00:00:00"), '0 0 1 */5,2,11-12,5-9/2 *', strtotime("2021-06-01 00:00:00"));
		$this->assertTime(strtotime("2021-09-01 00:00:00"), '0 0 1 */5,2,11-12,5-9/2 *', strtotime("2021-07-01 00:00:00"));
		$this->assertTime(strtotime("2021-11-01 00:00:00"), '0 0 1 */5,2,11-12,5-9/2 *', strtotime("2021-09-01 00:00:00"));
		$this->assertTime(strtotime("2021-12-01 00:00:00"), '0 0 1 */5,2,11-12,5-9/2 *', strtotime("2021-11-01 00:00:00"));
		$this->assertTime(strtotime("2022-01-01 00:00:00"), '0 0 1 */5,2,11-12,5-9/2 *', strtotime("2021-12-01 00:00:00"));
	}
	
	
	public function testGetNextTriggerTime_DayOfWeek()
	{
		$this->assertTime(strtotime("2021-01-03 00:00:00"), '* * * 1 0', strtotime("2020-12-31 23:59:19"));
		$this->assertTime(strtotime("2021-01-03 00:01:00"), '* * * 1 0', strtotime("2021-01-03 00:00:00"));
		$this->assertTime(strtotime("2021-01-03 23:59:00"), '* * * 1 0', strtotime("2021-01-03 23:58:00"));
		$this->assertTime(strtotime("2021-01-10 00:00:00"), '* * * 1 0', strtotime("2021-01-03 23:59:00"));
		$this->assertTime(strtotime("2021-01-17 00:00:00"), '* * * 1 0', strtotime("2021-01-10 23:59:00"));
		$this->assertTime(strtotime("2021-01-24 00:00:00"), '* * * 1 0', strtotime("2021-01-17 23:59:00"));
		$this->assertTime(strtotime("2021-01-31 00:00:00"), '* * * 1 0', strtotime("2021-01-24 23:59:00"));
		$this->assertTime(strtotime("2022-01-02 00:00:00"), '* * * 1 0', strtotime("2021-01-31 23:59:00"));
		{
			$this->assertTime(strtotime("2021-01-04 00:00:00"), '* 0 * 1 1', strtotime("2020-12-31 23:59:00"));
			$this->assertTime(strtotime("2021-01-04 00:01:00"), '* 0 * 1 1', strtotime("2021-01-04 00:00:00"));
			$this->assertTime(strtotime("2021-01-04 00:02:00"), '* 0 * 1 1', strtotime("2021-01-04 00:01:00"));
			$this->assertTime(strtotime("2021-01-04 00:59:00"), '* 0 * 1 1', strtotime("2021-01-04 00:58:00"));
			$this->assertTime(strtotime("2021-01-11 00:00:00"), '* 0 * 1 1', strtotime("2021-01-04 00:59:00"));
			$this->assertTime(strtotime("2021-01-18 00:00:00"), '* 0 * 1 1', strtotime("2021-01-11 00:59:00"));
			$this->assertTime(strtotime("2022-01-03 00:00:00"), '* 0 * 1 1', strtotime("2021-01-25 00:59:00"));
			
			$this->assertTime(strtotime("2021-01-05 00:00:00"), '0 * * 1 2', strtotime("2020-12-15 03:15:45"));
			$this->assertTime(strtotime("2021-01-05 00:00:00"), '0 * * 1 2', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-05 01:00:00"), '0 * * 1 2', strtotime("2021-01-05 00:00:00"));
			$this->assertTime(strtotime("2022-01-04 00:00:00"), '0 * * 1 2', strtotime("2021-01-26 23:00:00"));
			
			$this->assertTime(strtotime("2021-01-06 00:00:00"), '0 0 * 1 3', strtotime("2020-12-15 03:15:45"));
			$this->assertTime(strtotime("2021-01-13 00:00:00"), '0 0 * 1 3', strtotime("2021-01-06 00:00:00"));
			$this->assertTime(strtotime("2021-01-20 00:00:00"), '0 0 * 1 3', strtotime("2021-01-13 00:00:00"));
			$this->assertTime(strtotime("2021-01-27 00:00:00"), '0 0 * 1 3', strtotime("2021-01-20 00:00:00"));
			$this->assertTime(strtotime("2022-01-05 00:00:00"), '0 0 * 1 3', strtotime("2021-01-27 00:00:00"));
			
			$this->assertTime(strtotime("2020-12-05 00:00:00"), '0 0 * * */3', strtotime("2020-12-02 02:45:12"));
			$this->assertTime(strtotime("2020-12-02 00:00:00"), '0 0 * * */3', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-30 00:00:00"), '0 0 * * */3', strtotime("2020-12-27 00:00:00"));
			$this->assertTime(strtotime("2021-01-02 00:00:00"), '0 0 * * */3', strtotime("2020-12-30 00:00:00"));
			$this->assertTime(strtotime("2021-02-03 00:00:00"), '0 0 * * */3', strtotime("2021-01-31 00:00:00"));
			
			$this->assertTime(strtotime("2020-12-05 00:00:00"), '0 0 ? * */3', strtotime("2020-12-02 02:45:12"));
			$this->assertTime(strtotime("2020-12-02 00:00:00"), '0 0 ? * */3', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-30 00:00:00"), '0 0 ? * */3', strtotime("2020-12-27 00:00:00"));
			$this->assertTime(strtotime("2021-01-02 00:00:00"), '0 0 ? * */3', strtotime("2020-12-30 00:00:00"));
			$this->assertTime(strtotime("2021-02-03 00:00:00"), '0 0 ? * */3', strtotime("2021-01-31 00:00:00"));
			
			$this->assertTime(strtotime("2020-12-02 00:00:00"), '0 0 ? * 1/2', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-04 00:00:00"), '0 0 ? * 1/2', strtotime("2020-12-02 00:00:00"));
			$this->assertTime(strtotime("2020-12-07 00:00:00"), '0 0 ? * 1/2', strtotime("2020-12-04 00:00:00"));
			$this->assertTime(strtotime("2020-12-09 00:00:00"), '0 0 ? * 1/2', strtotime("2020-12-07 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 ? * 1/2', strtotime("2020-12-30 00:00:00"));
			$this->assertTime(strtotime("2021-01-04 00:00:00"), '0 0 ? * 1/2', strtotime("2021-01-01 00:00:00"));
			
			$this->assertTime(strtotime("2020-12-01 00:00:00"), '0 0 ? * 1-3', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-02 00:00:00"), '0 0 ? * 1-3', strtotime("2020-12-01 00:00:00"));
			$this->assertTime(strtotime("2020-12-07 00:00:00"), '0 0 ? * 1-3', strtotime("2020-12-02 00:00:00"));
			$this->assertTime(strtotime("2020-12-08 00:00:00"), '0 0 ? * 1-3', strtotime("2020-12-07 00:00:00"));
			$this->assertTime(strtotime("2021-01-04 00:00:00"), '0 0 ? * 1-3', strtotime("2020-12-30 00:00:00"));
			
			$this->assertTime(strtotime("2020-12-02 00:00:00"), '0 0 ? * 1-5/2', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-04 00:00:00"), '0 0 ? * 1-5/2', strtotime("2020-12-02 00:00:00"));
			$this->assertTime(strtotime("2020-12-07 00:00:00"), '0 0 ? * 1-5/2', strtotime("2020-12-04 00:00:00"));
			$this->assertTime(strtotime("2020-12-09 00:00:00"), '0 0 ? * 1-5/2', strtotime("2020-12-07 00:00:00"));
			$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 ? * 1-5/2', strtotime("2020-12-30 00:00:00"));
			$this->assertTime(strtotime("2021-01-04 00:00:00"), '0 0 ? * 1-5/2', strtotime("2021-01-01 00:00:00"));
			
			$this->assertTime(strtotime("2020-12-01 00:00:00"), '0 0 ? * 1,2-3,4/2,*/2', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-02 00:00:00"), '0 0 ? * 1,2-3,4/2,*/2', strtotime("2020-12-01 00:00:00"));
			$this->assertTime(strtotime("2020-12-03 00:00:00"), '0 0 ? * 1,2-3,4/2,*/2', strtotime("2020-12-02 00:00:00"));
			$this->assertTime(strtotime("2020-12-05 00:00:00"), '0 0 ? * 1,2-3,4/2,*/2', strtotime("2020-12-04 00:00:00"));
			$this->assertTime(strtotime("2020-12-06 00:00:00"), '0 0 ? * 1,2-3,4/2,*/2', strtotime("2020-12-05 00:00:00"));
			$this->assertTime(strtotime("2021-01-02 00:00:00"), '0 0 ? * 1,2-3,4/2,*/2', strtotime("2021-01-01 00:00:00"));
			$this->assertTime(strtotime("2021-01-03 00:00:00"), '0 0 ? * 1,2-3,4/2,*/2', strtotime("2021-01-02 00:00:00"));
		}
		{
			$this->assertTime(strtotime("2020-12-27 00:00:00"), '0 0 ? * 0L', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-28 00:00:00"), '0 0 ? * 1L', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-29 00:00:00"), '0 0 ? * 2L', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-30 00:00:00"), '0 0 ? * 3L', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-31 00:00:00"), '0 0 ? * 4L', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-25 00:00:00"), '0 0 ? * 5L', strtotime("2020-11-30 15:15:15"));
			$this->assertTime(strtotime("2020-12-26 00:00:00"), '0 0 ? * 6L', strtotime("2020-11-30 15:15:15"));
			
			$this->assertTime(strtotime("2021-01-31 00:00:00"), '0 0 ? * 0L', strtotime("2020-12-27 00:00:00"));
			$this->assertTime(strtotime("2021-01-25 00:00:00"), '0 0 ? * 1L', strtotime("2020-12-28 00:00:00"));
			$this->assertTime(strtotime("2021-01-26 00:00:00"), '0 0 ? * 2L', strtotime("2020-12-29 00:00:00"));
			$this->assertTime(strtotime("2021-01-27 00:00:00"), '0 0 ? * 3L', strtotime("2020-12-30 00:00:00"));
			$this->assertTime(strtotime("2021-01-28 00:00:00"), '0 0 ? * 4L', strtotime("2020-12-31 00:00:00"));
			$this->assertTime(strtotime("2021-01-29 00:00:00"), '0 0 ? * 5L', strtotime("2020-12-25 00:00:00"));
			$this->assertTime(strtotime("2021-01-30 00:00:00"), '0 0 ? * 6L', strtotime("2020-12-26 00:00:00"));
			
			$this->assertTime(strtotime("2021-01-03 00:00:00"), '0 0 ? * 0#1', strtotime("2020-12-31 18:18:18"));
			$this->assertTime(strtotime("2021-02-07 00:00:00"), '0 0 ? * 0#1', strtotime("2021-01-03 00:00:00"));
			$this->assertTime(strtotime("2021-01-10 00:00:00"), '0 0 ? * 0#2', strtotime("2021-01-03 00:00:00"));
			$this->assertTime(strtotime("2021-02-14 00:00:00"), '0 0 ? * 0#2', strtotime("2021-01-10 00:00:00"));
			$this->assertTime(strtotime("2021-01-17 00:00:00"), '0 0 ? * 0#3', strtotime("2021-01-10 00:00:00"));
			$this->assertTime(strtotime("2021-02-21 00:00:00"), '0 0 ? * 0#3', strtotime("2021-01-17 00:00:00"));
			$this->assertTime(strtotime("2021-01-24 00:00:00"), '0 0 ? * 0#4', strtotime("2021-01-17 00:00:00"));
			$this->assertTime(strtotime("2021-02-28 00:00:00"), '0 0 ? * 0#4', strtotime("2021-01-24 00:00:00"));
			$this->assertTime(strtotime("2021-01-31 00:00:00"), '0 0 ? * 0#5', strtotime("2021-01-24 00:00:00"));
			$this->assertTime(strtotime("2021-05-30 00:00:00"), '0 0 ? * 0#5', strtotime("2021-01-31 00:00:00"));
		}
	}
	
	
	public function testGetNextTriggerTime_Year()
	{
		$this->assertTime(strtotime("2020-01-01 00:00:00"), '* * * * * 2020', strtotime("2019-04-05 12:34:56"));
		$this->assertTime(strtotime("2020-01-01 00:01:00"), '* * * * * 2020', strtotime("2020-01-01 00:00:00"));
		$this->assertTime(strtotime("2020-12-01 00:00:00"), '* * * * * 2020', strtotime("2020-11-30 23:59:00"));
		$this->assertTime(null, '* * * * * 2020', strtotime("2020-12-31 23:59:00"));
		
		$this->assertTime(strtotime("2020-01-01 00:00:00"), '* * * * * */5', strtotime("2019-04-05 12:34:56"));
		$this->assertTime(strtotime("2020-01-01 00:01:00"), '* * * * * */5', strtotime("2020-01-01 00:00:00"));
		$this->assertTime(strtotime("2020-12-01 00:00:00"), '* * * * * */5', strtotime("2020-11-30 23:59:00"));
		$this->assertTime(strtotime("2025-01-01 00:00:00"), '* * * * * */5', strtotime("2020-12-31 23:59:00"));
		
		$this->assertTime(strtotime("2020-01-01 00:00:00"), '* * * * * 2020/5', strtotime("1989-04-05 12:34:56"));
		$this->assertTime(strtotime("2020-01-01 00:01:00"), '* * * * * 2020/5', strtotime("2020-01-01 00:00:00"));
		$this->assertTime(strtotime("2020-12-01 00:00:00"), '* * * * * 2020/5', strtotime("2020-11-30 23:59:00"));
		$this->assertTime(strtotime("2025-01-01 00:00:00"), '* * * * * 2020/5', strtotime("2020-12-31 23:59:00"));
		$this->assertTime(strtotime("2030-01-01 00:00:00"), '* * * * * 2020/5', strtotime("2025-12-31 23:59:00"));
		
		$this->assertTime(strtotime("2020-01-01 00:00:00"), '* * * * * 2020-2025', strtotime("2011-01-01 00:00:00"));
		$this->assertTime(strtotime("2020-01-01 00:01:00"), '* * * * * 2020-2025', strtotime("2020-01-01 00:00:00"));
		$this->assertTime(strtotime("2024-04-05 11:11:00"), '* * * * * 2020-2025', strtotime("2024-04-05 11:10:11"));
		$this->assertTime(strtotime("2024-12-31 23:59:00"), '* * * * * 2020-2025', strtotime("2024-12-31 23:58:00"));
		$this->assertTime(null, '* * * * * 2020-2025', strtotime("2025-12-31 23:59:00"));
		
		$this->assertTime(strtotime("2020-01-01 00:00:00"), '* * * * * 2020-2030/5', strtotime("2019-01-01 00:00:00"));
		$this->assertTime(strtotime("2020-01-01 00:01:00"), '* * * * * 2020-2030/5', strtotime("2020-01-01 00:00:00"));
		$this->assertTime(strtotime("2020-12-31 23:59:00"), '* * * * * 2020-2030/5', strtotime("2020-12-31 23:58:00"));
		$this->assertTime(strtotime("2025-01-01 00:00:00"), '* * * * * 2020-2030/5', strtotime("2020-12-31 23:59:00"));
		$this->assertTime(strtotime("2030-01-01 00:00:00"), '* * * * * 2020-2030/5', strtotime("2025-12-31 23:59:00"));
		$this->assertTime(strtotime("2030-01-01 00:01:00"), '* * * * * 2020-2030/5', strtotime("2030-01-01 00:00:00"));
		$this->assertTime(null, '* * * * * 2020-2030/5', strtotime("2030-12-31 23:59:00"));
		
		$this->assertTime(strtotime("2020-01-01 00:00:00"), '0 0 1 1 * 2020-2030/5,2024,2026-2028,2033/5', strtotime("2019-10-31 00:00:00"));
		$this->assertTime(strtotime("2024-01-01 00:00:00"), '0 0 1 1 * 2020-2030/5,2024,2026-2028,2033/5', strtotime("2020-01-01 00:00:00"));
		$this->assertTime(strtotime("2025-01-01 00:00:00"), '0 0 1 1 * 2020-2030/5,2024,2026-2028,2033/5', strtotime("2024-01-01 00:00:00"));
		$this->assertTime(strtotime("2026-01-01 00:00:00"), '0 0 1 1 * 2020-2030/5,2024,2026-2028,2033/5', strtotime("2025-01-01 00:00:00"));
		$this->assertTime(strtotime("2027-01-01 00:00:00"), '0 0 1 1 * 2020-2030/5,2024,2026-2028,2033/5', strtotime("2026-01-01 00:00:00"));
		$this->assertTime(strtotime("2028-01-01 00:00:00"), '0 0 1 1 * 2020-2030/5,2024,2026-2028,2033/5', strtotime("2027-01-01 00:00:00"));
		$this->assertTime(strtotime("2030-01-01 00:00:00"), '0 0 1 1 * 2020-2030/5,2024,2026-2028,2033/5', strtotime("2028-01-01 00:00:00"));
		$this->assertTime(strtotime("2033-01-01 00:00:00"), '0 0 1 1 * 2020-2030/5,2024,2026-2028,2033/5', strtotime("2030-01-01 00:00:00"));
		$this->assertTime(strtotime("2038-01-01 00:00:00"), '0 0 1 1 * 2020-2030/5,2024,2026-2028,2033/5', strtotime("2033-01-01 00:00:00"));
	}
	
	public function assertTime($checkTime, $schedule, $date)
	{	
		$this->obj->setSchedule($schedule);
		$this->assertEquals(dfo($checkTime), dfo($this->obj->getNextTriggerTime($date)));
	}
}
