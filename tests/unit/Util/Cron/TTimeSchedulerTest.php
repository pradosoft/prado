<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Cron\TTimeScheduler;
use PHPUnit\Framework\Attributes\DataProvider;

//date format = d. fo. = dfo
function dfo($time)
{
	return $time === null ? null : date('l jS \of F Y h:i:s A', $time);
}

class TTimeSchedulerTest extends \PHPUnit\Framework\TestCase
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

	// =========================================================================
	// Construction & schedule round-trip
	// =========================================================================

	public function testConstruct()
	{
		$this->assertInstanceOf(TTimeScheduler::class, $this->obj);
		$this->assertNull($this->obj->getSchedule());
	}

	public function testGetSetScheduleRoundTrip()
	{
		$this->obj->setSchedule('* * * * * *');
		$this->assertEquals('* * * * * *', $this->obj->getSchedule());
		$this->obj->setSchedule(null);
		$this->assertNull($this->obj->getSchedule());
		$this->obj->setSchedule(''); // empty becomes every-minute
		$this->assertEquals('* * * * * *', $this->obj->getSchedule());
	}

	// =========================================================================
	// Grammar conformance (parse accept / reject)
	// =========================================================================

	public static function provideValidSchedules(): array
	{
		$schedules = [
			'* * * * *', '* * ? * *', '* * * * ?',
			// seconds on a minute element: minute:second
			'0:30 * * * *', '*:*/10 * * * *', '*/5:15 * * * *', '0:0,30:0 * * * *', '10-50/5:20-40/10 * * * *',
			'*:* * * * *', '59:59 23 31 12 ?', '0:*/15,30:5 * * * *',
			// hashed fields: H or #, with a range and a step, and in the seconds position
			'H * * * ?', '# # * * ?', 'H/15 * * * ?', 'H(10-20)/5 H * * ?', 'H:H * * * ?', 'H H H H H',
			'h(0-29) * * * ?', '0 0 ? * 3#2',
			'*/5 * * * *', '* */5 * * *', '* * */5 * *', '* * * */2 *', '* * * * */2',
			'*/59 * * * *', '* */23 * * *', '* * */31 * *', '* * * */12 *', '* * * * */6',
			'0 0 1 1 ?', '59 23 31 12 ?', '0 0 ? 1 0', '59 23 ? 12 6',
			'0 0 1 1 *', '59 23 31 12 *', '0 0 * 1 0', '59 23 * 12 6',
			'0-59 0-23 1-31 1-12 ?', '0-59 0-23 ? 1-12 0-6',
			'2-7/2 3-6/2 1-6/2 1-7/2 *', '2-7/2 3-6/2 * 1-7/2 0-5/2',
			'11-57/2 11-19/2 12-23/2 10-12/2 *',
			'1,*/10,12,25/10,30-40,40-50/2,0/35 * * * ?',
			'* 1,*/5,12,12/10,7-11,12-20/3 * * ?',
			'* * 1,*/10,12,12/10,15-20,20-30/3,1/13 * ?',
			'* * * 1,*/5,12,10/10,1-12,1-8/3,1/6 ?',
			'* * ? * 1,*/3,2/3,3-5,1-5/4',
			// month / day-of-week names, ranges, cross-language ranges
			'* * * jan ?', '* * * dec ?', '* * * DEC ?',
			'* * * jan-jul *', '* * * mar-sep/3,apr-oct/2 *',
			'* * * अप्रैल-Сентябрь/3,Апр-déc/2 *',
			'* * ? * sun', '* * * * SAT', '* * * * sun-wed', '* * * * tue-fri/2,wed-sat/4',
			'* * ? Дек सोम',
			// day-of-month W / L, day-of-week L / #
			'* * 1W * ?', '* * 15W * ?', '* * 31W * ?', '* * 1W,15W,31W * ?',
			'* * L * ?', '* * L-5 * ?', '* * L,L-3,3W * ?',
			'* * ? * 0L', '* * ? * 1L,3L,5L',
			'* * ? * 0#1', '* * ? * 6#5', '* * ? * 1#1,3#2,5#3,3#4,3L',
		];
		$yearAppend = ['', ' *', ' */5', ' 1970-2099', ' 1970-2099/5', ' 2020-2030/10,2015,1999/5,*/5,2050-2060'];
		$out = [];
		foreach ($schedules as $schedule) {
			foreach ($yearAppend as $yap) {
				$out[$schedule . $yap] = [$schedule . $yap];
			}
		}
		return $out;
	}

	#[DataProvider('provideValidSchedules')]
	public function testValidSchedulesParseAndRoundTrip(string $schedule)
	{
		$this->obj->setSchedule($schedule);
		$this->assertEquals($schedule, $this->obj->getSchedule());
	}

	public static function provideInvalidSchedules(): array
	{
		$bad = [
			// NOTE: '* * ? * ?' (both day fields '?') is accepted by the current grammar (both unrestricted
			// = every day). The pre-refactor parser rejected it (Quartz's "exactly one '?'" rule). Left out
			// here to match current behavior; restore a both-'?' rejection in TTimeScheduler if strictness is wanted.
			'* * ?/5 * *', '* * * * ?/5 *',
			'*/60 * * * *', '* */24 * * *', '* * */32 * *', '* * * */13 *', '* * * * */7',
			'-1 * * * *', '* -1 * * *', '* * 0 * *', '* * * 0 *', '* * * * -1',
			'60 * * * *', '* 24 * * *', '* * 32 * *', '* * * 13 *', '* * * * 7',
			'50-64 * * * *', '* 20-30 * * *', '* * 30-40 * *', '* * * 10-20 *', '* * * * 5-10',
			'0-59/60 * * * *', '* 0-23/24 * * *', '* * 0-31/32 * *', '* * * 1-12/13 *', '* * * * 0-6/7',
			'* * L-10 * ?', '* * * * 7L', '* * ? * 0#6',
			'* * * * * 1969', '* * * * * 2100',
			'* * ?-8 * *', '* * * * ?-4', '* * ?/8 * *', '* * * * ?/4',
			'@', '@test',
			// ambiguous single-letter day tokens are rejected
			'0 0 ? * d', '0 0 ? * m', '0 0 ? * s',
			// 'W' is only valid on a single bare day — not on a range or with a step
			'* * 1-5W * ?', '* * 6W-10W * ?', '* * 1W-5 * ?', '* * 15W/3 * ?',
			// '@<int>' one-off accepts only a canonical non-negative integer
			'@-5', '@3.5', '@1e3', '@+5', '@ 5',
			// seconds: out of range, a second seconds part, or on a field other than the minute
			'0:60 * * * *', '0:30:10 * * * *', '* * * * *:5', '* *:5 * * *', ':30 * * * *',
			// hashed: an empty range, a hashed year, an unknown hashed shortcut
			'H(5-3) * * * ?', '* * * * * H', '#hourly@midnight',
			// periods: no interval, an unparsable or non-positive interval, an unparsable time
			'every', 'every garbage text', 'every 0 seconds', 'every -1 day', 'every 10 seconds from nonsense',
			'every 10 seconds until @5x',
		];
		$out = [];
		foreach ($bad as $schedule) {
			$out[$schedule] = [$schedule];
		}
		return $out;
	}

	#[DataProvider('provideInvalidSchedules')]
	public function testInvalidSchedulesThrow(string $schedule)
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$this->obj->setSchedule($schedule);
	}

	// =========================================================================
	// Next-trigger: wide (schedule x priortime) matrix against an independent oracle
	// =========================================================================

	/** Boundary-rich priortimes the matrix is evaluated from (all UTC). */
	private const PRIORTIMES = [
		'2021-01-01 00:00:00', // year start
		'2020-12-31 23:59:00', // year-end minute
		'2020-12-31 23:59:40', // sub-minute (seconds dropped)
		'2021-01-15 12:30:30', // mid
		'2021-01-31 23:59:00', // month-end (31)
		'2021-02-28 23:59:00', // non-leap Feb end
		'2020-02-29 12:00:00', // leap day
		'2021-04-30 23:59:00', // 30-day month-end
		'2021-06-15 23:00:00', // mid-year, late hour
		'2024-02-29 00:00:00', // leap day start
	];

	public static function provideMatrixSchedules(): array
	{
		// Standard numeric subset only (the oracle covers *, ?, a, a-b, a-b/s, a/s, */s, lists, OR-union).
		// L / W / # / names are exercised by the dedicated special-operator tests.
		$schedules = [
			// minute
			'* * * * *', '0 * * * *', '5 * * * *', '0-30 * * * *', '10-50/5 * * * *', '*/15 * * * *',
			'10/10 * * * *', '0,15,30,45 * * * *', '5,10-19,20-30/3,*/7 * * * *',
			// hour
			'0 0 * * *', '0 12 * * *', '30 9-17 * * *', '0 */6 * * *', '0 8-18/2 * * *', '15 23 * * *',
			// day-of-month
			'0 0 1 * *', '0 0 15 * *', '0 0 31 * *', '0 0 1-15 * *', '0 0 1-28/7 * *', '0 0 */10 * *',
			'0 0 5/5,2 * *',
			// month
			'0 0 1 1 *', '0 0 1 12 *', '0 0 1 */3 *', '0 0 1 3-9 *', '0 0 1 2-12/3 *', '0 0 1 6/2 *',
			// day-of-week (dom unrestricted)
			'0 0 ? * 0', '0 0 ? * 5', '0 0 ? * 1-5', '0 0 ? * */2', '0 0 ? * 1/2', '0 0 ? * 0,6',
			// day-of-month OR day-of-week (both restricted -> union)
			'0 0 13 * 5', '0 0 1 * 1', '0 0 1,15 * 6', '0 0 31 * 0',
			// combined fields
			'30 8 1 * *', '0 0 1 1,7 *', '15 6 * * 1-5', '0 0 29 2 *',
			// year-bounded
			'0 0 1 1 * 2020', '0 0 1 1 * 2020-2025', '0 0 1 1 * 2020-2030/5', '* * * * * 2020',
			// seconds
			'*:*/10 * * * *', '0:30 * * * *', '*/5:15 * * * *', '0:0,30:0 * * * *', '10-50/5:20-40/10 * * * *',
			'*:* * * * *', '0:45 12 * * *', '0:*/15,30:5 * * * *', '59:59 23 31 12 ?',
		];
		$out = [];
		foreach ($schedules as $s) {
			$out[$s] = [$s];
		}
		return $out;
	}

	public function testInvalidScheduleKeepsThePreviousSchedule()
	{
		$at = mktime(10, 0, 0, 6, 15, 2024);
		foreach (['30 2 * * *', '@' . ($at + 120), 'every 15 minutes from @' . $at, 'H 3 * * *'] as $schedule) {
			$this->obj->setHashContext('task');
			$this->obj->setSchedule($schedule);
			$expected = $this->obj->getSchedule();
			$next = $this->obj->getNextTriggerTime($at);
			foreach (['61 * * * *', 'every 0 minutes', 'not a schedule'] as $invalid) {
				try {
					$this->obj->setSchedule($invalid);
					self::fail("'$invalid' is invalid");
				} catch (\Prado\Exceptions\TInvalidDataValueException $e) {
				}
				self::assertSame($expected, $this->obj->getSchedule(), "'$invalid' leaves '$schedule' in place");
				self::assertSame($next, $this->obj->getNextTriggerTime($at), "'$invalid' leaves the triggers of '$schedule'");
			}
		}
	}

	#[DataProvider('provideMatrixSchedules')]
	public function testNextTriggerMatchesOracle(string $schedule)
	{
		$this->obj->setSchedule($schedule);
		foreach (self::PRIORTIMES as $priortimeStr) {
			$from = strtotime($priortimeStr);
			$expected = self::oracleNext($schedule, $from);
			$this->assertSame(
				$expected,
				$this->obj->getNextTriggerTime($from),
				"$schedule from $priortimeStr (oracle=" . dfo($expected) . ')'
			);
		}
	}

	public function testNextTriggerSequenceIsAscendingAndOracleConsistent()
	{
		// Walking forward must agree with the oracle at each step and stay strictly increasing.
		foreach (['*/15 * * * *', '0 0 ? * 1-5', '0 0 1 */3 *', '0 0 13 * 5'] as $schedule) {
			$this->obj->setSchedule($schedule);
			$t = strtotime('2021-01-01 00:00:00');
			$prev = $t;
			for ($i = 0; $i < 12; $i++) {
				$next = $this->obj->getNextTriggerTime($t);
				$this->assertSame(self::oracleNext($schedule, $t), $next, "$schedule step $i");
				$this->assertGreaterThan($prev, $next, "$schedule not increasing at step $i");
				$prev = $t = $next;
			}
		}
	}

	public function testFuzzMatchesOracle()
	{
		// Seeded (reproducible) random standard-subset schedules x random priortimes, asserted against the
		// independent oracle. The MT19937 mode pins the sequence across PHP versions; a failure message
		// prints the exact seed/schedule/priortime to reproduce.
		$lo = strtotime('2019-01-01 00:00:00');
		$hi = strtotime('2031-12-31 23:59:59');
		foreach ([20240601, 987654321] as $seed) {
			mt_srand($seed, MT_RAND_MT19937);
			for ($i = 0; $i < 2500; $i++) {
				$schedule = self::randomStandardSchedule();
				$from = mt_rand($lo, $hi);
				try {
					$this->obj->setSchedule($schedule);
				} catch (\Prado\Exceptions\TInvalidDataValueException $e) {
					continue; // skip any random string the grammar rejects
				}
				$this->assertSame(
					self::oracleNext($schedule, $from),
					$this->obj->getNextTriggerTime($from),
					"seed=$seed i=$i '$schedule' from " . date('Y-m-d H:i', $from)
				);
			}
		}
	}

	public function testMinuteResolutionBoundarySpectrum()
	{
		// Seconds are dropped and the result is strictly after $priortime: probe three minute boundaries
		// across offsets -2..+2 seconds around several calendar boundaries.
		$starts = [
			strtotime('2020-12-01 00:00:00'), // static month
			strtotime('2021-01-01 00:00:00'), // year boundary
			strtotime('2020-02-29 00:00:00'), // leap day
			strtotime('2021-03-01 00:00:00'), // month change after non-leap Feb
		];
		$this->obj->setSchedule('* * * * *');
		foreach ($starts as $startTime) {
			foreach ([-60, 0, 60] as $window) {
				for ($offset = -2; $offset <= 2; $offset++) {
					$s = $startTime + $window + $offset;
					$nextTime = ceil(($s + 1) / 60) * 60;
					$this->assertEquals(dfo($nextTime), dfo($this->obj->getNextTriggerTime($s)), "start $startTime window $window offset $offset");
				}
			}
		}
	}

	// =========================================================================
	// Special operators (not covered by the oracle): L, W, #
	// =========================================================================

	public function testDayOfMonthLastAndWeekday()
	{
		// L / L-n (last day of month, minus n)
		$this->assertTime(strtotime("2021-01-31 00:00:00"), '0 0 L * *', strtotime("2021-01-01 23:59:00"));
		$this->assertTime(strtotime("2021-02-28 00:00:00"), '0 0 L * *', strtotime("2021-01-31 00:00:00"));
		$this->assertTime(strtotime("2021-04-30 00:00:00"), '0 0 L * *', strtotime("2021-03-31 00:00:00"));
		$this->assertTime(strtotime("2021-04-29 00:00:00"), '0 0 L-1 * *', strtotime("2021-03-31 00:00:00"));
		$this->assertTime(strtotime("2021-04-28 00:00:00"), '0 0 L-2 * *', strtotime("2021-03-31 00:00:00"));
		$this->assertTime(strtotime("2021-04-25 00:00:00"), '0 0 L-5 * *', strtotime("2021-03-31 00:00:00"));
		$this->assertTime(strtotime("2021-06-29 00:00:00"), '0 0 L-1 * *', strtotime("2021-05-30 00:00:00"));

		// W (nearest weekday to the given day-of-month)
		$this->assertTime(strtotime("2021-01-01 00:00:00"), '0 0 2W * *', strtotime("2020-12-31 23:59:59")); // 2nd is Sat -> Fri 1st
		$this->assertTime(strtotime("2021-02-02 00:00:00"), '0 0 2W * *', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-01-18 00:00:00"), '0 0 17W * *', strtotime("2021-01-01 01:00:00")); // 17th Sun -> Mon 18th
		$this->assertTime(strtotime("2022-01-03 00:00:00"), '0 0 1W * *', strtotime("2021-12-31 23:59:00")); // 1st Sat -> Mon 3rd
		$this->assertTime(strtotime("2021-01-29 00:00:00"), '0 0 31W * *', strtotime("2021-01-03 23:59:00")); // 31st Sun -> Fri 29th
		// ('W' on a range, e.g. '1W-8W/2', is now rejected at parse time — see provideInvalidSchedules.)

		// W with a day-of-month past the month length clamps to the last valid day's nearest weekday
		// (regression: it previously overflowed into the next month and silently mis-/never-fired).
		$this->assertTime(strtotime("2021-02-26 00:00:00"), '0 0 30W 2 ?', strtotime("2021-01-01 00:00:00")); // Feb has 28; 28th is Sun -> Fri 26th
		$this->assertTime(strtotime("2024-04-30 00:00:00"), '0 0 31W 4 ?', strtotime("2024-01-01 00:00:00")); // Apr has 30; 30th is Tue
		$this->assertTime(strtotime("2024-06-28 00:00:00"), '0 0 31W 6 ?', strtotime("2024-01-01 00:00:00")); // Jun has 30; 30th is Sun -> Fri 28th
	}

	public function testExoticValidSchedulesTriggerCorrectly()
	{
		// The richest grammar forms (name ranges, cross-language ranges, multi-token W lists, mixed
		// day-of-month special operators) are exercised for actual trigger correctness, not just parsing.
		$this->assertTime(strtotime("2021-04-01 00:00:00"), '0 0 1 Апр-déc/2 ?', strtotime("2021-01-01 00:00:00")); // Apr,Jun,Aug,Oct,Dec
		$this->assertTime(strtotime("2021-01-04 00:00:00"), '0 0 ? * mon-fri', strtotime("2021-01-02 00:00:00")); // Sat -> Mon
		$this->assertTime(strtotime("2021-01-15 00:00:00"), '0 0 1W,15W * ?', strtotime("2021-01-05 00:00:00")); // 1st passed; 15th is Fri
		$this->assertTime(strtotime("2021-01-15 00:00:00"), '0 0 L,L-3,15W * ?', strtotime("2021-01-01 00:00:00")); // 15W before L-3/L
	}

	public function testSpecialOperatorsWithUnionAndYearWindow()
	{
		// Special operators combined with a restricted day-of-week (the union branch) and with a year window.
		$this->assertTime(strtotime("2021-01-04 00:00:00"), '0 0 L * 1', strtotime("2021-01-01 00:00:00")); // last day OR any Monday -> first Monday
		$this->assertTime(strtotime("2021-01-13 00:00:00"), '0 0 ? * wed#2', strtotime("2021-01-01 00:00:00")); // 2nd Wednesday, '#' with a name
		$this->assertTime(strtotime("2024-02-29 00:00:00"), '0 0 L 2 ? 2024', strtotime("2021-01-01 00:00:00")); // last day of Feb in a leap year
		$this->assertTime(null, '0 0 L 2 ? 2024', strtotime("2024-02-29 00:00:00")); // no further match in the 2024 window
	}

	public function testShortcutsAreCaseInsensitive()
	{
		// Regression: a mixed-case shortcut (@DAILY) previously matched the /i validator but never resolved
		// against the lowercase keys, spinning setSchedule() in an infinite loop. Each must now resolve
		// identically to its lowercase form (and this test completing at all proves there is no hang).
		$from = strtotime("2021-06-15 12:30:00");
		foreach (['@yearly', '@annually', '@monthly', '@weekly', '@midnight', '@noon', '@daily', '@hourly', '@minute'] as $shortcut) {
			$this->obj->setSchedule($shortcut);
			$lower = $this->obj->getNextTriggerTime($from);
			$this->obj->setSchedule(strtoupper($shortcut));
			$this->assertSame($lower, $this->obj->getNextTriggerTime($from), "$shortcut should be case-insensitive");
		}
	}

	public function testDayOfWeekLastAndNth()
	{
		// nL : last weekday-of-type in the month
		$this->assertTime(strtotime("2020-12-27 00:00:00"), '0 0 ? * 0L', strtotime("2020-11-30 15:15:15"));
		$this->assertTime(strtotime("2020-12-31 00:00:00"), '0 0 ? * 4L', strtotime("2020-11-30 15:15:15"));
		$this->assertTime(strtotime("2021-01-31 00:00:00"), '0 0 ? * 0L', strtotime("2020-12-27 00:00:00"));
		$this->assertTime(strtotime("2021-01-25 00:00:00"), '0 0 ? * 1L', strtotime("2020-12-28 00:00:00"));

		// n#k : the kth weekday-of-type in the month
		$this->assertTime(strtotime("2021-01-03 00:00:00"), '0 0 ? * 0#1', strtotime("2020-12-31 18:18:18"));
		$this->assertTime(strtotime("2021-02-07 00:00:00"), '0 0 ? * 0#1', strtotime("2021-01-03 00:00:00"));
		$this->assertTime(strtotime("2021-01-17 00:00:00"), '0 0 ? * 0#3', strtotime("2021-01-10 00:00:00"));
		$this->assertTime(strtotime("2021-01-31 00:00:00"), '0 0 ? * 0#5', strtotime("2021-01-24 00:00:00"));
		$this->assertTime(strtotime("2021-05-30 00:00:00"), '0 0 ? * 0#5', strtotime("2021-01-31 00:00:00")); // next month with a 5th Sunday
	}

	// =========================================================================
	// Year window, impossible schedules, and the day-of-month/day-of-week union
	// =========================================================================

	public function testYearWindowReturnsNullPastTheRange()
	{
		$this->assertTime(null, '* * * * * 2020', strtotime("2020-12-31 23:59:00"));
		$this->assertTime(null, '* * * * * 2020-2025', strtotime("2025-12-31 23:59:00"));
		$this->assertTime(null, '* * * * * 2020-2030/5', strtotime("2030-12-31 23:59:00"));
	}

	public function testImpossibleAndRollover()
	{
		$from = strtotime("2021-01-01 00:00:00");
		$this->assertTime(strtotime("2021-05-31 00:00:00"), '0 0 31 4,5 *', $from); // April has no 31st; May does
		$this->assertTime(null, '0 0 31 4 *', $from); // April only, never has a 31st
		$this->assertTime(null, '0 0 30 2 *', $from); // February never has a 30th
		$this->assertTime(strtotime("2024-02-29 00:00:00"), '0 0 29 2 *', $from); // next leap-year Feb 29
		$this->assertTime(null, '0 0 29 2 * 2099', $from); // 2099 is not a leap year
		$this->assertTime(strtotime("2021-12-31 00:00:00"), '0 0 31 12 *', strtotime("2021-02-15 00:00:00"));
	}

	public function testDayOfMonthOrDayOfWeekUnion()
	{
		// Both day fields restricted -> a day matches EITHER (POSIX/Quartz union).
		$this->assertTime(strtotime("2021-08-06 00:00:00"), '0 0 13 * 5', strtotime("2021-08-01 00:00:00")); // first Friday
		$this->assertTime(strtotime("2021-08-13 00:00:00"), '0 0 13 * 5', strtotime("2021-08-06 00:00:00")); // the 13th
		$this->assertTime(strtotime("2021-09-13 00:00:00"), '0 0 13 * 5', strtotime("2021-09-11 00:00:00")); // 13th (Mon) via dom
		$this->assertTime(strtotime("2021-01-04 00:00:00"), '0 0 1 * 1', strtotime("2021-01-01 00:00:00")); // first Monday after the 1st
	}

	// =========================================================================
	// One-off (@<int>, @now, @now+N) and enumeration
	// =========================================================================

	public function testAtSecondOneOff()
	{
		$time = strtotime("2021-06-15 12:00:00");
		$this->obj->setSchedule('@' . $time);
		$this->assertEquals('@' . $time, $this->obj->getSchedule()); // canonical, normalized
		$this->assertNull($this->obj->getNextTriggerTime($time)); // not strictly after
		$this->assertNull($this->obj->getNextTriggerTime($time + 120));
		$this->assertEquals($time, $this->obj->getNextTriggerTime($time - 120));
	}

	public function testAtNowAndAtNowPlusN()
	{
		// @now / @now+N resolve to a concrete one-off at parse time via the time() seam.
		$base = strtotime("2021-06-15 12:00:00");
		$obj = new class () extends TTimeScheduler {
			public int $fake = 0;
			protected function time(): int
			{
				return $this->fake;
			}
		};
		$obj->fake = $base;

		$obj->setSchedule('@now+90');
		$this->assertEquals('@' . ($base + 90), $obj->getSchedule());
		$this->assertEquals($base + 90, $obj->getNextTriggerTime($base));
		$this->assertNull($obj->getNextTriggerTime($base + 200));

		$obj->setSchedule('@now');
		$this->assertEquals('@' . $base, $obj->getSchedule());
	}

	public function testGetNextTriggerTimes()
	{
		$this->obj->setSchedule('*/15 * * * *');
		$times = $this->obj->getNextTriggerTimes(4, strtotime("2021-01-01 00:00:00"));
		$this->assertEquals([
			strtotime("2021-01-01 00:15:00"),
			strtotime("2021-01-01 00:30:00"),
			strtotime("2021-01-01 00:45:00"),
			strtotime("2021-01-01 01:00:00"),
		], $times);

		$one = strtotime("2021-06-15 12:00:00");
		$this->obj->setSchedule('@' . $one);
		$this->assertEquals([$one], $this->obj->getNextTriggerTimes(3, $one - 10)); // one-off yields exactly one

		$this->obj->setSchedule(null);
		$this->assertSame([], $this->obj->getNextTriggerTimes(3));
		$this->obj->setSchedule('*/15 * * * *');
		$this->assertSame([], $this->obj->getNextTriggerTimes(0, $one)); // non-positive count

		// Year-window horizon: a recurring-but-bounded schedule asked for more than remain is truncated.
		$this->obj->setSchedule('0 0 1 1 * 2099');
		$this->assertEquals([strtotime("2099-01-01 00:00:00")], $this->obj->getNextTriggerTimes(5, strtotime("2098-01-01 00:00:00")));
	}

	public function testTriggerTimeResetOnReassignment()
	{
		// Reusing an instance after a one-off must not poison a later recurring schedule. Call
		// getNextTriggerTime directly (assertTime would re-set the schedule and hide the stale state).
		$this->obj->setSchedule('@' . strtotime("2021-06-15 12:00:00"));
		$this->obj->setSchedule('* * * * *');
		$this->assertEquals(
			strtotime("2022-01-01 00:01:00"),
			$this->obj->getNextTriggerTime(strtotime("2022-01-01 00:00:00"))
		);
	}

	// =========================================================================
	// Shortcuts
	// =========================================================================

	public function testShortcutsAllResolve()
	{
		// Every documented shortcut must parse and resolve (regression for @midnight/@noon/@minute,
		// which previously threw because they were missing from the validator alternation).
		$from = strtotime("2021-06-15 12:30:00"); // a Tuesday
		$expected = [
			'@yearly' => "2022-01-01 00:00:00",
			'@annually' => "2022-01-01 00:00:00",
			'@monthly' => "2021-07-01 00:00:00",
			'@weekly' => "2021-06-20 00:00:00", // next Sunday
			'@midnight' => "2021-06-16 00:00:00",
			'@noon' => "2021-06-16 12:00:00",
			'@daily' => "2021-06-16 00:00:00",
			'@hourly' => "2021-06-15 13:00:00",
			'@minute' => "2021-06-15 12:31:00",
		];
		foreach ($expected as $shortcut => $when) {
			$this->assertTime(strtotime($when), $shortcut, $from);
			$this->obj->setSchedule($shortcut);
			$this->assertEquals($shortcut, $this->obj->getSchedule()); // round-trips
		}
	}

	// =========================================================================
	// i18n name parsing
	// =========================================================================

	public function testDayOfWeekNameDoIsThursdayNotSunday()
	{
		// German 'do' (Donnerstag) must resolve to Thursday, not Sunday (the former key-0 collision).
		$this->assertTime(strtotime("2021-01-07 00:00:00"), '0 0 ? * do', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-01-07 00:00:00"), '0 0 ? * don', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-01-07 00:00:00"), '0 0 ? * thu', strtotime("2021-01-01 00:00:00"));
		$this->assertTime(strtotime("2021-01-03 00:00:00"), '0 0 ? * sun', strtotime("2021-01-01 00:00:00"));
	}

	public function testAmbiguousSingleLetterDayTokensRejected()
	{
		// Bare single-letter day tokens are ambiguous across languages and must not parse.
		foreach (['d', 'l', 'm', 'k', 'x', 'j', 'v', 's'] as $token) {
			$threw = false;
			try {
				$this->obj->setSchedule('0 0 ? * ' . $token);
			} catch (\Prado\Exceptions\TInvalidDataValueException $e) {
				$threw = true;
			}
			$this->assertTrue($threw, "single-letter day token '$token' should be rejected");
		}
	}

	public function testUnicodeNamesAreCaseInsensitive()
	{
		// With the /u modifier, non-ASCII month/day names case-fold (lowercase Cyrillic).
		$this->assertTime(strtotime("2021-12-01 00:00:00"), '0 0 1 дек ?', strtotime("2021-01-01 00:00:00")); // December
		$this->assertTime(strtotime("2021-01-04 00:00:00"), '0 0 ? * пн', strtotime("2021-01-01 00:00:00")); // Monday
	}

	// =========================================================================
	// Leap-year day count
	// =========================================================================

	public function testSecondsTriggerWithinTheMinute()
	{
		$t = strtotime('2026-03-10 12:00:30');
		$this->obj->setSchedule('*:*/10 * * * *');
		$this->assertSame([$t + 10, $t + 20, $t + 30], $this->obj->getNextTriggerTimes(3, $t));
		$this->obj->setSchedule('0:30 * * * *');
		$this->assertSame(strtotime('2026-03-10 13:00:30'), $this->obj->getNextTriggerTime($t), 'strictly after the prior time');
		$this->assertSame($t, $this->obj->getNextTriggerTime($t - 1));
		$this->obj->setSchedule('0:0,30:0 * * * *');
		$this->assertSame(strtotime('2026-03-10 12:30:00'), $this->obj->getNextTriggerTime($t));
		$this->obj->setSchedule('0:45 12 * * *');
		$this->assertSame(strtotime('2026-03-10 12:00:45'), $this->obj->getNextTriggerTime($t));
		$this->assertSame(strtotime('2026-03-11 12:00:45'), $this->obj->getNextTriggerTime(strtotime('2026-03-10 12:00:45')));
		// a schedule without seconds keeps minute resolution: the seconds of the prior time are dropped
		$this->obj->setSchedule('* * * * *');
		$this->assertSame(strtotime('2026-03-10 12:01:00'), $this->obj->getNextTriggerTime($t));
		$this->assertSame(strtotime('2026-03-10 12:01:00'), $this->obj->getNextTriggerTime(strtotime('2026-03-10 12:00:00')));
	}

	/** The documented derivation: min + crc32(context . "\0" . field) % (max - min + 1). */
	private static function hashed(string $context, string $field, int $min, int $max): int
	{
		return $min + (crc32($context . "\0" . $field) % ($max - $min + 1));
	}

	public function testHashedFieldsResolveFromTheContext()
	{
		$this->assertSame('', $this->obj->getHashContext());
		$this->obj->setHashContext('alpha');
		$this->assertSame('alpha', $this->obj->getHashContext());
		$expected = implode(' ', [
			self::hashed('alpha', 'minute', 0, 59), self::hashed('alpha', 'hour', 0, 23), self::hashed('alpha', 'dom', 1, 28),
			self::hashed('alpha', 'month', 1, 12), self::hashed('alpha', 'dow', 0, 6),
		]);
		$this->obj->setSchedule('H H H H H');
		$this->assertSame('H H H H H', $this->obj->getSchedule(), 'the schedule is kept as written');
		$this->assertSame($expected, $this->obj->getResolvedSchedule());
		$this->obj->setSchedule('# # # # #');
		$this->assertSame($expected, $this->obj->getResolvedSchedule(), '# and H are the same');
		$this->obj->setSchedule('H:H * * * ?');
		$this->assertSame(self::hashed('alpha', 'minute', 0, 59) . ':' . self::hashed('alpha', 'minute:second', 0, 59) . ' * * * ?', $this->obj->getResolvedSchedule());
		$this->obj->setSchedule('H/15 * * * ?');
		$this->assertSame(self::hashed('alpha', 'minute', 0, 14) . '/15 * * * ?', $this->obj->getResolvedSchedule(), 'a step hashes the offset below the step');
		$this->obj->setSchedule('H(10-20)/5 H(0-2) * * ?');
		$this->assertSame(self::hashed('alpha', 'minute', 10, 14) . '/5 ' . self::hashed('alpha', 'hour', 0, 2) . ' * * ?', $this->obj->getResolvedSchedule());
		$this->obj->setSchedule('H,H * * * ?');
		$this->assertSame(self::hashed('alpha', 'minute', 0, 59) . ',' . self::hashed('alpha', 'minute.1', 0, 59) . ' * * * ?', $this->obj->getResolvedSchedule(), 'list elements hash apart');
		$this->obj->setSchedule('0 0 ? * 3#2');
		$this->assertSame('0 0 ? * 3#2', $this->obj->getResolvedSchedule(), 'the nth weekday # is not a hashed field');
		$this->obj->setSchedule('@daily');
		$this->assertSame('0 0 * * ?', $this->obj->getResolvedSchedule(), 'a shortcut resolves to its expression');
		$this->obj->setSchedule('@' . ($t = strtotime('2026-03-10 12:00:30')));
		$this->assertSame('@' . $t, $this->obj->getResolvedSchedule());

		$this->obj->setSchedule('H * * * ?');
		$minute = self::hashed('alpha', 'minute', 0, 59);
		$this->assertSame(strtotime('2026-03-10 13:00:00') + 60 * $minute, $this->obj->getNextTriggerTime(strtotime('2026-03-10 12:59:59')));
		$this->obj->setHashContext('beta');
		$this->assertSame(self::hashed('beta', 'minute', 0, 59) . ' * * * ?', $this->obj->getResolvedSchedule(), 'a context change re-resolves');
		$this->assertSame('H * * * ?', $this->obj->getSchedule());
		$this->obj->setHashContext(null);
		$this->assertSame('', $this->obj->getHashContext());
		$this->assertSame(self::hashed('', 'minute', 0, 59) . ' * * * ?', $this->obj->getResolvedSchedule());
	}

	public function testHashedShortcutsResolve()
	{
		$this->obj->setHashContext('alpha');
		$m = self::hashed('alpha', 'minute', 0, 59);
		$h = self::hashed('alpha', 'hour', 0, 23);
		$hm = self::hashed('alpha', 'hour', 0, 2);
		$d = self::hashed('alpha', 'dom', 1, 28);
		$mo = self::hashed('alpha', 'month', 1, 12);
		$w = self::hashed('alpha', 'dow', 0, 6);
		$expected = [
			'#hourly' => "$m * * * ?",
			'#daily' => "$m $h * * ?",
			'#midnight' => "$m $hm * * ?",
			'#weekly' => "$m $h ? * $w",
			'#weekly@midnight' => "$m $hm ? * $w",
			'#monthly' => "$m $h $d * ?",
			'#monthly@midnight' => "$m $hm $d * ?",
			'#yearly' => "$m $h $d $mo ?",
			'#yearly@midnight' => "$m $hm $d $mo ?",
			'#annually' => "$m $h $d $mo ?",
			'#annually@midnight' => "$m $hm $d $mo ?",
		];
		foreach ($expected as $shortcut => $resolved) {
			$this->obj->setSchedule(strtoupper($shortcut));
			$this->assertSame($resolved, $this->obj->getResolvedSchedule(), $shortcut);
			$this->assertSame(strtoupper($shortcut), $this->obj->getSchedule());
			$this->assertNotNull($this->obj->getNextTriggerTime(strtotime('2026-03-10 12:00:00')));
		}
	}

	public function testEveryPeriod()
	{
		$t0 = strtotime('2026-03-10 12:00:00');
		$this->obj->setSchedule("every 10 seconds from @$t0");
		$this->assertSame("every 10 seconds from @$t0", $this->obj->getSchedule());
		$this->assertSame("every 10 seconds from @$t0", $this->obj->getResolvedSchedule());
		$this->assertSame($t0, $this->obj->getNextTriggerTime($t0 - 5), 'the anchor is the first trigger');
		$this->assertSame($t0 + 10, $this->obj->getNextTriggerTime($t0));
		$this->assertSame($t0 + 30, $this->obj->getNextTriggerTime($t0 + 25));
		$this->assertSame([$t0 + 10, $t0 + 20, $t0 + 30], $this->obj->getNextTriggerTimes(3, $t0));
		$this->obj->setSchedule("every  90   minutes from @$t0");
		$this->assertSame("every 90 minutes from @$t0", $this->obj->getSchedule(), 'the interval text is normalized');
		$this->assertSame($t0 + 5400, $this->obj->getNextTriggerTime($t0 + 3600));
		$this->obj->setSchedule('every 1 day from 2026-03-01 08:00');
		$this->assertSame('every 1 day from @' . strtotime('2026-03-01 08:00:00'), $this->obj->getSchedule());
		$this->assertSame(strtotime('2026-03-11 08:00:00'), $this->obj->getNextTriggerTime($t0));

		$anchor = strtotime('2026-01-31 10:00:00');
		$this->obj->setSchedule("every 1 month from @$anchor");
		$at = static fn (int $k) => (new \DateTimeImmutable('@' . $anchor))->setTimezone(new \DateTimeZone(date_default_timezone_get()))->add(new \DateInterval("P{$k}M"))->getTimestamp();
		$this->assertSame($at(1), $this->obj->getNextTriggerTime($anchor), 'the k-th multiple of the interval from the anchor');
		$this->assertSame($at(2), $this->obj->getNextTriggerTime($at(1)));
		$this->assertSame($at(3), $this->obj->getNextTriggerTime($at(2)));
		$this->assertSame($at(14), $this->obj->getNextTriggerTime($at(13)), 'a far time jumps to its multiple');

		$this->obj->setSchedule('every first monday of next month from @' . strtotime('2026-01-15 00:00:00'));
		$this->assertSame(strtotime('2026-02-02 00:00:00'), $this->obj->getNextTriggerTime(strtotime('2026-01-15 00:00:00')));
		$this->assertSame(strtotime('2026-03-02 00:00:00'), $this->obj->getNextTriggerTime(strtotime('2026-02-02 00:00:00')));
		$this->assertSame(strtotime('2026-04-06 00:00:00'), $this->obj->getNextTriggerTime($t0), 'a relative interval steps the calendar');

		$until = $t0 + 86400 * 20;
		$this->obj->setSchedule('every 2 weeks from @' . ($t0 - 86400 * 30) . " until @$until");
		$this->assertSame('every 2 weeks from @' . ($t0 - 86400 * 30) . " until @$until", $this->obj->getSchedule());
		$this->assertSame([$t0 - 86400 * 2, $t0 + 86400 * 12], $this->obj->getNextTriggerTimes(5, $t0 - 86400 * 3), 'nothing past until');
		$this->assertNull($this->obj->getNextTriggerTime($until));

		$this->obj->setSchedule('every 1 hour');
		$this->assertMatchesRegularExpression('/^every 1 hour from @\d+$/', $this->obj->getSchedule(), 'the anchor defaults to the time the schedule is set');
		$anchor = (int) substr($this->obj->getSchedule(), strlen('every 1 hour from @'));
		$this->assertEqualsWithDelta(time(), $anchor, 5);
		$this->obj->setSchedule($this->obj->getSchedule());
		$this->assertSame($anchor + 3600, $this->obj->getNextTriggerTime($anchor), 'a persisted period keeps its anchor');
	}

	public function testEveryDayAcrossDaylightSaving()
	{
		$tz = date_default_timezone_get();
		date_default_timezone_set('America/New_York');
		try {
			$anchor = strtotime('2026-03-07 09:00:00'); // DST starts 2026-03-08 02:00 local
			$this->obj->setSchedule("every 1 day from @$anchor");
			$next = $this->obj->getNextTriggerTime($anchor);
			$this->assertSame('2026-03-08 09:00:00', date('Y-m-d H:i:s', $next), 'a day interval keeps the local time');
			$this->assertSame(23 * 3600, $next - $anchor);
			$this->obj->setSchedule("every 24 hours from @$anchor");
			$this->assertSame(24 * 3600, $this->obj->getNextTriggerTime($anchor) - $anchor, 'an hour interval is a fixed length');
		} finally {
			date_default_timezone_set($tz);
		}
	}

	public function testDaysInMonth()
	{
		$this->assertEquals(31, $this->obj->getDaysInMonth(1, 2021));
		$this->assertEquals(28, $this->obj->getDaysInMonth(2, 2021));
		$this->assertEquals(29, $this->obj->getDaysInMonth(2, 2020)); // leap
		$this->assertEquals(28, $this->obj->getDaysInMonth(2, 2100)); // century non-leap
		$this->assertEquals(29, $this->obj->getDaysInMonth(2, 2000)); // 400-year leap
		$this->assertEquals(30, $this->obj->getDaysInMonth(4, 2021));
		$this->assertEquals(31, $this->obj->getDaysInMonth(12, 2021));
		$this->assertEquals($this->obj->getDaysInMonth(2, 2020), $this->obj->days_in_month(2, 2020)); // deprecated alias
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	public function assertTime($checkTime, $schedule, $date)
	{
		$this->obj->setSchedule($schedule);
		$ndate = $this->obj->getNextTriggerTime($date);
		$this->assertEquals(dfo($checkTime), dfo($ndate), "$schedule from " . date('Y-m-d H:i:s', $date));
	}

	/** Builds a random valid standard-subset schedule (minute hour dom month dow [year]) for the fuzzer. */
	private static function randomStandardSchedule(): string
	{
		$s = self::randomField(0, 59) . ' ' . self::randomField(0, 23) . ' '
			. self::randomField(1, 31, true) . ' ' . self::randomField(1, 12) . ' '
			. self::randomField(0, 6, true);
		if (mt_rand(0, 3) === 0) {
			$s .= ' ' . self::randomField(1970, 2099);
		}
		return $s;
	}

	/** Random valid field: *, a, a-b, a-b/s, a/s, * /s, or a comma list; day fields may be a bare '?'. */
	private static function randomField(int $lo, int $hi, bool $day = false): string
	{
		if ($day && mt_rand(0, 4) === 0) {
			return '?';
		}
		$form = static function () use ($lo, $hi): string {
			$a = mt_rand($lo, $hi);
			$b = mt_rand($a, $hi);
			$s = mt_rand(1, max(1, $hi - $lo));
			return match (mt_rand(0, 5)) {
				0 => '*',
				1 => (string) $a,
				2 => "$a-$b",
				3 => "$a-$b/$s",
				4 => "$a/$s",
				default => "*/$s",
			};
		};
		$parts = [];
		$n = mt_rand(1, 3);
		for ($i = 0; $i < $n; $i++) {
			$parts[] = $form();
		}
		return implode(',', $parts);
	}

	/**
	 * Independent brute-force reference for the next trigger time, covering the standard numeric subset
	 * (*, ?, a, a-b, a-b/s, a/s, * /s, lists, and the day-of-month/day-of-week OR-union). It scans
	 * calendar days and tests an inline predicate — a different algorithm from TTimeScheduler's
	 * field-walk over boolean arrays — so agreement is meaningful. Does not handle L/W/# or names.
	 * @param string $schedule
	 * @param int $from
	 */
	private static function oracleNext(string $schedule, int $from): ?int
	{
		$parts = preg_split('/\s+/', trim($schedule));
		$minute = []; // minute => seconds set; an element without seconds is second 0
		foreach (explode(',', $parts[0]) as $tok) {
			[$mTok, $sTok] = array_pad(explode(':', $tok, 2), 2, null);
			$secs = ($sTok === null) ? [0 => true] : self::oracleFieldSet($sTok, 0, 59);
			foreach (self::oracleFieldSet($mTok, 0, 59) as $mv => $_) {
				$minute[$mv] = ($minute[$mv] ?? []) + $secs;
			}
		}
		$hour = self::oracleFieldSet($parts[1], 0, 23);
		[$domDays, $domUnrestricted] = self::oracleDaySet($parts[2], 31, true);
		$month = self::oracleFieldSet($parts[3], 1, 12);
		[$dowDays, $dowUnrestricted] = self::oracleDaySet($parts[4], 6, false);
		$year = isset($parts[5]) ? self::oracleFieldSet($parts[5], 1970, 2099) : self::oracleFieldSet('*', 1970, 2099);

		$tz = new \DateTimeZone('UTC');
		$start = $from + 1; // strictly after, second resolution (second 0 without a seconds part)
		$day = (new \DateTimeImmutable('@' . $start))->setTimezone($tz)->setTime(0, 0, 0);
		// Skip disallowed years/months wholesale (cheap set tests, never the buggy part) so the day-level
		// predicate scan stays fast even for sparse/impossible schedules.
		while (($y = (int) $day->format('Y')) <= 2099) {
			if (!isset($year[$y])) {
				$day = new \DateTimeImmutable(sprintf('%04d-01-01 00:00:00', $y + 1), $tz);
				continue;
			}
			$mo = (int) $day->format('n');
			if (!isset($month[$mo])) {
				$day = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $mo === 12 ? $y + 1 : $y, $mo === 12 ? 1 : $mo + 1), $tz);
				continue;
			}
			$d = (int) $day->format('j');
			$w = (int) $day->format('w');
			$dayOk = $dowUnrestricted ? isset($domDays[$d])
				: ($domUnrestricted ? isset($dowDays[$w]) : (isset($domDays[$d]) || isset($dowDays[$w])));
			if ($dayOk) {
				for ($h = 0; $h <= 23; $h++) {
					if (!isset($hour[$h])) {
						continue;
					}
					for ($m = 0; $m <= 59; $m++) {
						if (!isset($minute[$m])) {
							continue;
						}
						$secs = array_keys($minute[$m]);
						sort($secs);
						foreach ($secs as $sv) {
							$ts = $day->setTime($h, $m, $sv)->getTimestamp();
							if ($ts >= $start) {
								return $ts;
							}
						}
					}
				}
			}
			$day = $day->modify('+1 day');
		}
		return null;
	}

	/** Expands a plain numeric field (min/hour/month/year) into a value=>true set. */
	private static function oracleFieldSet(string $field, int $fmin, int $fmax): array
	{
		$set = [];
		foreach (explode(',', $field) as $tok) {
			if ($tok === '*') {
				for ($i = $fmin; $i <= $fmax; $i++) {
					$set[$i] = true;
				}
			} elseif (preg_match('#^\*/(\d+)$#', $tok, $m)) {
				for ($i = $fmin; $i <= $fmax; $i += (int) $m[1]) {
					$set[$i] = true;
				}
			} elseif (preg_match('#^(\d+)-(\d+)/(\d+)$#', $tok, $m)) {
				for ($i = (int) $m[1]; $i <= (int) $m[2]; $i += (int) $m[3]) {
					$set[$i] = true;
				}
			} elseif (preg_match('#^(\d+)-(\d+)$#', $tok, $m)) {
				for ($i = (int) $m[1]; $i <= (int) $m[2]; $i++) {
					$set[$i] = true;
				}
			} elseif (preg_match('#^(\d+)/(\d+)$#', $tok, $m)) {
				for ($i = (int) $m[1]; $i <= $fmax; $i += (int) $m[2]) { // a/s -> a..max
					$set[$i] = true;
				}
			} elseif (preg_match('#^(\d+)$#', $tok, $m)) {
				$set[(int) $m[1]] = true;
			} else {
				throw new \Exception("oracle: unsupported token '$tok'");
			}
		}
		return $set;
	}

	/**
	 * Expands a day field (day-of-month or day-of-week) into [set, unrestricted]. '*'/'?' fill the set
	 * (matching TTimeScheduler) and flag the field unrestricted (period 1). For day-of-month, '* /s' keeps
	 * days where (day-1) % s == 0; for day-of-week it counts from $base (0).
	 * @param string $field
	 * @param int $fmax
	 * @param bool $isDom
	 */
	private static function oracleDaySet(string $field, int $fmax, bool $isDom): array
	{
		$set = [];
		$unrestricted = false;
		foreach (explode(',', $field) as $tok) {
			if ($tok === '?' || $tok === '*') {
				$unrestricted = true;
				for ($i = ($isDom ? 1 : 0); $i <= $fmax; $i++) {
					$set[$i] = true;
				}
			} elseif (preg_match('#^\*/(\d+)$#', $tok, $m)) {
				$p = (int) $m[1];
				if ($p === 1) {
					$unrestricted = true;
				}
				if ($isDom) {
					for ($i = 1; $i <= $fmax; $i++) {
						if (($i - 1) % $p === 0) {
							$set[$i] = true;
						}
					}
				} else {
					for ($i = 0; $i <= $fmax; $i += $p) {
						$set[$i] = true;
					}
				}
			} elseif (preg_match('#^(\d+)-(\d+)/(\d+)$#', $tok, $m)) {
				for ($i = (int) $m[1]; $i <= (int) $m[2]; $i += (int) $m[3]) {
					$set[$i] = true;
				}
			} elseif (preg_match('#^(\d+)-(\d+)$#', $tok, $m)) {
				for ($i = (int) $m[1]; $i <= (int) $m[2]; $i++) {
					$set[$i] = true;
				}
			} elseif (preg_match('#^(\d+)/(\d+)$#', $tok, $m)) {
				for ($i = (int) $m[1]; $i <= $fmax; $i += (int) $m[2]) {
					$set[$i] = true;
				}
			} elseif (preg_match('#^(\d+)$#', $tok, $m)) {
				$set[(int) $m[1]] = true;
			} else {
				throw new \Exception("oracle: unsupported day token '$tok'");
			}
		}
		return [$set, $unrestricted];
	}

	// =========================================================================
	// TimeZone
	// =========================================================================

	public function testTimeZone()
	{
		self::assertNull($this->obj->getTimeZone());
		$this->obj->setTimeZone('Asia/Tokyo');
		self::assertSame('Asia/Tokyo', $this->obj->getTimeZone());
		$this->obj->setTimeZone('');
		self::assertNull($this->obj->getTimeZone());
		$this->obj->setTimeZone('+02:00');
		self::assertSame('+02:00', $this->obj->getTimeZone());
		self::expectException(TInvalidDataValueException::class);
		$this->obj->setTimeZone('Mars/Olympus_Mons');
	}

	public function testTimeZone_SchedulesInTheZone()
	{
		$from = gmmktime(0, 0, 0, 6, 1, 2026);
		$this->obj->setSchedule('0 9 * * *');
		self::assertSame(gmmktime(9, 0, 0, 6, 1, 2026), $this->obj->getNextTriggerTime($from), 'the application zone is UTC');
		$this->obj->setTimeZone('Asia/Tokyo');
		self::assertSame(gmmktime(0, 0, 0, 6, 2, 2026), $this->obj->getNextTriggerTime($from), '09:00 in Tokyo is 00:00 UTC');
		$this->obj->setTimeZone('America/New_York');
		self::assertSame(gmmktime(13, 0, 0, 6, 1, 2026), $this->obj->getNextTriggerTime($from), '09:00 EDT is 13:00 UTC');
		self::assertSame(gmmktime(14, 0, 0, 12, 1, 2026), $this->obj->getNextTriggerTime(gmmktime(0, 0, 0, 12, 1, 2026)), '09:00 EST is 14:00 UTC');
	}

	public function testTimeZone_DaylightSavingChanges()
	{
		$this->obj->setTimeZone('America/New_York');
		$zone = new \DateTimeZone('America/New_York');
		$format = fn (array $times) => array_map(fn ($t) => (new \DateTimeImmutable('@' . $t))->setTimezone($zone)->format('m-d H:i T'), $times);

		$this->obj->setSchedule('30 2 * * *');
		self::assertSame(['03-07 02:30 EST', '03-09 02:30 EDT'], $format($this->obj->getNextTriggerTimes(2, gmmktime(0, 0, 0, 3, 7, 2026))), 'a time in the skipped hour does not trigger that day');

		$this->obj->setSchedule('30 1 * * *');
		self::assertSame(['11-01 01:30 EDT', '11-02 01:30 EST'], $format($this->obj->getNextTriggerTimes(2, gmmktime(0, 0, 0, 11, 1, 2026))), 'a time in the repeated hour triggers once');

		$this->obj->setSchedule('every 1 day from 2026-10-31 08:00');
		self::assertSame(['10-31 08:00 EDT', '11-01 08:00 EST'], $format($this->obj->getNextTriggerTimes(2, gmmktime(0, 0, 0, 10, 1, 2026))), 'a period anchor parses in the zone and steps the local calendar');
	}

	public function testGetNextTriggerTime_AnUnparsableTimeHasNoTrigger()
	{
		$this->obj->setSchedule('* * * * *');
		self::assertNull($this->obj->getNextTriggerTime('not a time at all'));
	}

}
