<?php

/**
 * TTimeScheduler class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Exceptions\TInvalidDataValueException;

/**
 * TTimeScheduler class.
 *
 * This class keeps track of Scheduling event times.  It
 * specifies a schedule and the dates and times to trigger
 *
 * cron setup
 *     '*     *         *                *              *         *'
 * (minute) (hour) (day of month) (month of year) (day of week) (year)
 *  (0-59) (0-23)  (1-31)    (1-12|Jan,Feb,-,Dec) (0-6|Sun-Sat) (20xx)
 *
 * '*' (star) is any time.
 * adding '/(\d)' is every \d moments.
 * Day of Month can add '(\d)W' after the day to signify closest weekday.
 * Day of Month can add 'L' for last day, or 'L-2' for last day minus two.
 * Day of Week can add '(\d)L' after the day of week for last day of month of that type.
 * Day of Week can add "#(\d)" after the day to signify which week the day is from.
 *
 * Generally avoid 01:00:00 to 2:59:59 as they may not trigger or double
 * trigger during daylight savings time changes.
 *
 * The Months of the Year supports English, German, Spanish, French, Italian,
 * Russian, Hindi, and Arabic and their abbreviations.  The Days of the Week supports English, German,
 * Spanish, French, Italian, Russian, and Hindi and their abbreviations.
 *
 * There are schedule shortcuts:
 *     '@yearly' => '0 0 1 1 ?'
 *     '@annually' => '0 0 1 1 ?'
 *     '@monthly' => '0 0 1 * ?'
 *     '@weekly' => '0 0 ? * 0'
 *     '@midnight' => '0 0 * * ?'
 *     '@daily' => '0 0 * * ?'
 *     '@hourly' => '0 * * * ?'
 *
 * An efficient one-off task at a specified unix time can be scheduled with
 * '@(\d)' where \d is the unix time in the local php instance time zone.
 * This minimizes costly validation, parsing, and nextTriggerTime processing.
 * This allows for a higher cron task throughput to handle more cron tasks.
 *
 * e.g. '@1668165071' for 2022-11-11 11:11:11 and will trigger after the
 * specified time at '12 11 11 11 ? 2022'.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @see https://crontab.guru For more info on Crontab Schedule Expressions.
 */
class TTimeScheduler extends \Prado\TComponent
{
	public const YEAR_MIN = 1970;

	public const YEAR_MAX = 2099;

	/** the minute attributes of the schedule */
	protected const MINUTE = 0;

	/** the hour attributes of the schedule */
	protected const HOUR = 1;

	/** the day of month attributes of the schedule */
	protected const DAY_OF_MONTH = 2;

	/** the month of year attributes of the schedule */
	protected const MONTH_OF_YEAR = 3;

	/** the day of week attributes of the schedule */
	protected const DAY_OF_WEEK = 4;

	/** the year attributes of the schedule */
	protected const YEAR = 5;

	/** The cron schedule */
	private $_schedule;

	/** efficient one off trigger time */
	protected $_triggerTime;

	/** the parsed attributes of the schedule */
	private $_attr = [];

	private static $_intervals = [
		'@yearly' => '0 0 1 1 ?',
		'@annually' => '0 0 1 1 ?',
		'@monthly' => '0 0 1 * ?',
		'@weekly' => '0 0 ? * 0',
		'@midnight' => '0 0 * * ?',
		'@daily' => '0 0 * * ?',
		'@hourly' => '0 * * * ?'
		];
	private static $_keywords = [
		self::MONTH_OF_YEAR => [
			//English, German, Spanish, French, Italian, Russian, Hindi, Arabic
					1 => '(?:january|januar|enero|janvier|gennaio|jan|ene|janv|genn|Январь|Янв|जनवरी|يناير)',
					2 => '(?:february|februar|febrero|février|febbraio|feb|févr|febbr|Февраль|Фев|फरवरी|فبراير)',
					3 => '(?:march|maerz|märz|marzo|mars|mar|mae|mär|Март|Мар|मार्च|مارس)',
					4 => '(?:april|abril|avril|aprile|apr|abr|Апрель|Апр|अप्रैल|إبريل|أبريل)',
					5 => '(?:may|mai|mayo|maggio|magg|Май|मई|مايو)',
					6 => '(?:june|juni|junio|juin|giugno|jun|Июнь|Июн|जून|يونيه|يونيو)',
					7 => '(?:july|juli|julio|juillet|luglio|jul|juil|Июль|Июл|जुलाई|يوليه|يوليو)',
					8 => '(?:august|agosto|août|aug|ago|ag|Август|Авг|अगस्त|أغسطس)',
					9 => '(?:september|septiembre|septembre|settembre|sep|sept|sett|Сентябрь|Сен|सितम्बर|سبتمبر)',
					10 => '(?:october|oktober|octubre|octobre|ottobre|okt|oct|ott|Октябрь|Окт|अक्टूबर|أكتوبر)',
					11 => '(?:november|noviembre|novembre|nov|Ноябрь|Ноя|नवम्बर|نوفمبر)',
					12 => '(?:december|dezember|diciembre|décembre|dicembre|dec|dez|déc|dic|Декабрь|Дек|दिसम्बर|ديسمبر)'
				],
		self::DAY_OF_WEEK => [ //no Arabic as those are just numbered days of the week
					0 => '(?:sunday|sonntag|sun|son|su|so|domingo|do|d|dimanche|dim|domenica|dom|Воскресенье|Вск|Вс|रविवार|रवि)',
					1 => '(?:monday|montag|mon|mo|lune|lu|l|lundi|lun|lunedì|Понедельник|Пнд|Пн|सोमवार|सोम)',
					2 => '(?:tuesday|dienstag|die|tue|tu|di|martes|ma|m|k|mardi|mar|martedì|Вторник|Втр|Вт|मंगलवार|मंगल)',
					3 => '(?:wednesday|mittwoch|mit|wed|we|mi|miércoles|x|mercredi|mer|mercoledì|me|Среда|Сре|Ср|बुधवार|बुध)',
					4 => '(?:thursday|donnerstag|don|thu|th|do|jueves|ju|j|jeudi|jeu|giovedì|gio|gi|Четверг|Чтв|Чт|गुरुवार|गुरु)',
					5 => '(?:friday|freitag|fre|fri|fr|viernes|vi|v|vendredi|ven|venerdì|ve|Пятница|Птн|Пт|शुक्रवार|शुक्र)',
					6 => '(?:saturday|samstag|sam|sat|sa|sábado|s|samedi|sabato|sab|Суббота|Сбт|Сб|शनिवार|शनि)'
				]
		];

	/** validation is computed only once */
	private static $_validatorCache;

	/**
	 * @return string This returns the cron schedule
	 */
	public function getSchedule()
	{
		return $this->_schedule;
	}

	/**
	 * Parses a Cron Time Tag
	 * @param mixed $schedule
	 */
	public function setSchedule($schedule)
	{
		if ($schedule === '') {
			$schedule = '* * * * * *';
		} elseif ($schedule === null) {
			$this->_schedule = $schedule;
			return;
		}
		$schedule = trim($schedule);
		$this->_schedule = $schedule;
		$this->_attr = [];
		if (strlen($schedule) > 1 && $schedule[0] == '@') {
			if (is_numeric($triggerTime = substr($schedule, 1))) {
				$this->_triggerTime = (int) $triggerTime;
				return;
			}
		}

		if (self::$_validatorCache) {
			$minuteValidator = self::$_validatorCache['m'];
			$hourValidator = self::$_validatorCache['h'];
			$domValidator = self::$_validatorCache['dom'];
			$monthValidator = self::$_validatorCache['mo'];
			$dowValidator = self::$_validatorCache['dow'];
			$yearValidator = self::$_validatorCache['y'];
			$fullValidator = self::$_validatorCache['f'];
		} else {
			$minute = '(?:[0-9]|[1-5][0-9])';
			$minuteStar = '\*(?:\/(?:[1-9]|[1-5][0-9]))?';
			$minuteRegex = $minute . '(?:\-(?:[1-9]|[1-5][0-9]))?(?:\/(?:[1-9]|[1-5][0-9]))?';
			$hour = '(?:[0-9]|1[0-9]|2[0-3])';
			$hourStar = '\*(?:\/(?:[1-9]|1[0-9]|2[0-3]))?';
			$hourRegex = $hour . '(?:\-(?:[1-9]|1[0-9]|2[0-3]))?(?:\/(?:[1-9]|1[0-9]|2[0-3]))?';
			$dom = '(?:(?:[1-9]|[12][0-9]|3[01])W?)';
			$domWOW = '(?:[1-9]|[12][0-9]|3[01])';
			$domStar = '\*(?:\/(?:[1-9]|[12][0-9]|3[01]))?';
			$domRegex = '(?:' . $dom . '(?:\-' . $dom . ')?(?:\/' . $domWOW . ')?|' . '(?:L(?:\-[1-5])?)' . ')';
			$month = '(?:[1-9]|1[012]|' .
				self::$_keywords[self::MONTH_OF_YEAR][1] . '|' .
				self::$_keywords[self::MONTH_OF_YEAR][2] . '|' .
				self::$_keywords[self::MONTH_OF_YEAR][3] . '|' .
				self::$_keywords[self::MONTH_OF_YEAR][4] . '|' .
				self::$_keywords[self::MONTH_OF_YEAR][5] . '|' .
				self::$_keywords[self::MONTH_OF_YEAR][6] . '|' .
				self::$_keywords[self::MONTH_OF_YEAR][7] . '|' .
				self::$_keywords[self::MONTH_OF_YEAR][8] . '|' .
				self::$_keywords[self::MONTH_OF_YEAR][9] . '|' .
				self::$_keywords[self::MONTH_OF_YEAR][10] . '|' .
				self::$_keywords[self::MONTH_OF_YEAR][11] . '|' .
				self::$_keywords[self::MONTH_OF_YEAR][12] . ')';
			$monthStar = '\*(?:\/(?:[1-9]|1[012]))?';
			$monthRegex = $month . '(?:\-' . $month . ')?(?:\/(?:[1-9]|1[012]))?';
			$dow = '(?:[0-6]|' .
				self::$_keywords[self::DAY_OF_WEEK][0] . '|' .
				self::$_keywords[self::DAY_OF_WEEK][1] . '|' .
				self::$_keywords[self::DAY_OF_WEEK][2] . '|' .
				self::$_keywords[self::DAY_OF_WEEK][3] . '|' .
				self::$_keywords[self::DAY_OF_WEEK][4] . '|' .
				self::$_keywords[self::DAY_OF_WEEK][5] . '|' .
				self::$_keywords[self::DAY_OF_WEEK][6] . ')';
			$dowStar = '\*(?:\/[0-6])?';
			$dowRegex = '(?:[0-6]L|' . $dow . '(?:(?:\-' . $dow . ')?(?:\/[0-6])?|#[1-5])?)';
			$year = '(?:19[7-9][0-9]|20[0-9][0-9])';
			$yearStar = '\*(?:\/[0-9]?[0-9])?';
			$yearRegex = $year . '(?:\-' . $year . ')?(?:\/[0-9]?[0-9])?';
			$fullValidator = '/^(?:(@(?:annually|yearly|monthly|weekly|daily|hourly))|' .
				'(?#minute)((?:' . $minuteStar . '|' . $minuteRegex . ')(?:\,(?:' . $minuteStar . '|' . $minuteRegex . '))*)[\s]+' .
				'(?#hour)((?:' . $hourStar . '|' . $hourRegex . ')(?:\,(?:' . $hourStar . '|' . $hourRegex . '))*)[\s]+' .
				'(?#DoM)(\?|(?:(?:' . $domStar . '|' . $domRegex . ')(?:,(?:' . $domStar . '|' . $domRegex . '))*))[\s]+' .
				'(?#month)((?:' . $monthStar . '|' . $monthRegex . ')(?:\,(?:' . $monthStar . '|' . $monthRegex . '))*)[\s]+' .
				'(?#DoW)(\?|(?:' . $dowStar . '|' . $dowRegex . ')(?:\,(?:' . $dowStar . '|' . $dowRegex . '))*)' .
				'(?#year)(?:[\s]+' .
					'((?:' . $yearStar . '|' . $yearRegex . ')(?:\,(?:' . $yearStar . '|' . $yearRegex . '))*)' .
				')?' .
			')$/i';

			$minuteValidator = '/^(\*|' . $minute . ')(?:\-(' . $minute . '))?(?:\/(' . $minute . '))?$/i';
			$hourValidator = '/^(\*|' . $hour . ')(?:\-(' . $hour . '))?(?:\/(' . $hour . '))?$/i';
			$domValidator = '/^(\*|\?|L|' . $domWOW . ')(W)?(?:\-(' . $domWOW . ')(W)?)?(?:\/(' . $domWOW . '))?$/i';
			$monthValidator = '/^(\*|' . $month . ')(?:\-(' . $month . '))?(?:\/(' . $month . '))?$/i';
			$dowValidator = '/^(\*|\?|' . $dow . ')(L)?(?:\-(' . $dow . ')(L)?)?(?:\/(' . $dow . '))?(?:#([1-5]))?$/i';
			$yearValidator = '/^(\*|' . $year . ')(?:\-(' . $year . '))?(?:\/([1-9]?[0-9]))?$/i';
			self::$_validatorCache = [
					'm' => $minuteValidator,
					'h' => $hourValidator,
					'dom' => $domValidator,
					'mo' => $monthValidator,
					'dow' => $dowValidator,
					'y' => $yearValidator,
					'f' => $fullValidator
				];
		}

		$i = 0;
		do {
			if (!preg_match($fullValidator, $schedule, $matches)) {
				throw new TInvalidDataValueException('timescheduler_invalid_string', $schedule);
			}
			if ($matches[1]) {
				foreach (self::$_intervals as $interval => $intervalSchedule) {
					if ($interval == $matches[1]) {
						$schedule = $intervalSchedule;
					}
				}
			}
		} while ($matches[1]);

		$this->_attr[self::MINUTE] = [];
		foreach (explode(',', $matches[2]) as $match) {
			if (preg_match($minuteValidator, $match, $m2)) {
				if ($m2[1] === '*') {
					$data = ['min' => 0, 'end' => 59];
				} else {
					$data = ['min' => $m2[1]];
					$data['end'] = $m2[2] ?? $data['min'];
				}
				$data['period'] = (int) max($m2[3] ?? 1, 1);
				if (!($m2[2] ?? 0) && ($m2[3] ?? 0)) {
					$data['end'] = 59; //No end with period
				}
				$this->_attr[self::MINUTE][] = $data;
			} else {
				throw new TInvalidDataValueException('timescheduler_invalid_string', $match);
			}
		}

		$this->_attr[self::HOUR] = [];
		foreach (explode(',', $matches[3]) as $match) {
			if (preg_match($hourValidator, $match, $m2)) {
				if ($m2[1] === '*') {
					$data = ['hour' => 0, 'end' => 23];
				} else {
					$data = ['hour' => $m2[1]];
					$data['end'] = $m2[2] ?? $m2[1];
				}
				$data['period'] = (int) max($m2[3] ?? 1, 1);
				if (!($m2[2] ?? 0) && ($m2[3] ?? 0)) {
					$data['end'] = 23; //No end with period
				}
				$this->_attr[self::HOUR][] = $data;
			} else {
				throw new TInvalidDataValueException('timescheduler_invalid_string', $match);
			}
		}

		$this->_attr[self::DAY_OF_MONTH] = [];
		foreach (explode(',', $matches[4]) as $match) {
			if (preg_match($domValidator, $match, $m2)) {
				$data = ['dom' => $m2[1]]; // *, ?, \d, L
				$data['domWeekday'] = $m2[2] ?? null;
				$data['end'] = $m2[3] ?? ($m2[1] != 'L' ? $m2[1] : null);
				//$data['endWeekday'] = $m2[4] ?? null;
				$data['endWeekday'] = $m2[4] ?? (($m2[3] ?? null) ? null : $data['domWeekday']);
				$data['period'] = (int) max($m2[5] ?? 1, 1);
				if (!($m2[3] ?? 0) && ($m2[5] ?? 0)) {
					$data['end'] = 31; //No end with period
				}
				$this->_attr[self::DAY_OF_MONTH][] = $data;
			} else {
				throw new TInvalidDataValueException('timescheduler_invalid_string', $match);
			}
		}

		$this->_attr[self::MONTH_OF_YEAR] = [];
		foreach (explode(',', $matches[5]) as $match) {
			if (preg_match($monthValidator, $match, $m2)) {
				if ($m2[1] === '*') {
					$data = ['moy' => 1, 'end' => 12];
				} else {
					$data = ['moy' => (int) $this->translateMonthOfYear($m2[1])];
					$data['end'] = (isset($m2[2]) && $m2[2]) ? (int) $this->translateMonthOfYear($m2[2]) : $data['moy'];
				}
				$data['period'] = (int) max($m2[3] ?? 1, 1);
				if (!($m2[2] ?? 0) && ($m2[3] ?? 0)) {
					$data['end'] = 12; //No end with period
				}
				$this->_attr[self::MONTH_OF_YEAR][] = $data;
			} else {
				throw new TInvalidDataValueException('timescheduler_invalid_string', $match);
			}
		}

		$this->_attr[self::DAY_OF_WEEK] = [];
		foreach (explode(',', $matches[6]) as $match) {
			if (preg_match($dowValidator, $match, $m2)) {
				if ($m2[1] === '*' || $m2[1] === '?') {
					$data = ['dow' => 0, 'end' => 6];
				} else {
					$data = ['dow' => (int) $this->translateDayOfWeek($m2[1])];
					$data['end'] = (isset($m2[3]) && $m2[3]) ? (int) $this->translateDayOfWeek($m2[3]) : $data['dow'];
				}
				$data['lastDow'] = $m2[2] ?? null;
				$data['lastEnd'] = $m2[4] ?? null;
				$data['period'] = (int) max($m2[5] ?? 1, 1);
				$data['week'] = $m2[6] ?? null;
				if (!($m2[3] ?? 0) && ($m2[5] ?? 0)) {
					$data['end'] = 6; //No end with period
				}
				$this->_attr[self::DAY_OF_WEEK][] = $data;
			} else {
				throw new TInvalidDataValueException('timescheduler_invalid_string', $match);
			}
		}

		$this->_attr[self::YEAR] = [];
		$matches[7] = $matches[7] ?? '*';
		foreach (explode(',', $matches[7]) as $match) {
			if (preg_match($yearValidator, $match, $m2)) {
				if ($m2[1] === '*') {
					$data = ['year' => self::YEAR_MIN];
					$data['end'] = self::YEAR_MAX;
				} else {
					$data = ['year' => $m2[1]];
					$data['end'] = $m2[2] ?? $m2[1];
				}
				$data['period'] = max($m2[3] ?? 1, 1);
				if (!($m2[2] ?? 0) && ($m2[3] ?? 0)) {
					$data['end'] = self::YEAR_MAX; //No end with period
				}
				$this->_attr[self::YEAR][] = $data;
			} else {
				throw new TInvalidDataValueException('timescheduler_invalid_string', $match);
			}
		}
	}

	protected function translateMonthOfYear($v)
	{
		if (is_numeric($v)) {
			return $v;
		}
		foreach (self::$_keywords[self::MONTH_OF_YEAR] as $moy => $regex) {
			if (preg_match('/^(' . $regex . ')$/i', $v)) {
				return $moy;
			}
		}
		return $v;
	}

	protected function translateDayOfWeek($v)
	{
		if (is_numeric($v)) {
			return $v;
		}
		foreach (self::$_keywords[self::DAY_OF_WEEK] as $dow => $regex) {
			if (preg_match('/^(' . $regex . ')$/i', $v)) {
				return $dow;
			}
		}
		return $v;
	}


	/**
	 * Given which minutes are valid for the parsed time stamp, this generates
	 * an array of valid minutes.  Each minute is available as the key to the array
	 * and the value contains true or false whether the minute meets the cron
	 * time stamp
	 */
	protected function getMinutesArray()
	{
		$ma = array_pad([], 60, null);
		foreach ($this->_attr[self::MINUTE] as $m) {
			if (is_numeric($m['min'])) {
				for ($i = $m['min']; $i <= $m['end'] && $i < 60; $i += $m['period']) {
					$ma[$i] = 1;
				}
			}
		}
		return $ma;
	}


	/**
	 * Given which hours are valid for the parsed time stamp, this generates
	 * an array of valid hours.  Each hour is available as the key to the array
	 * and the value contains true or false whether the hour meets the cron
	 * time stamp
	 */
	protected function getHoursArray()
	{
		$ha = array_pad([], 24, null);
		foreach ($this->_attr[self::HOUR] as $h) {
			if (is_numeric($h['hour'])) {
				for ($i = $h['hour']; $i <= $h['end'] && $i < 24; $i += $h['period']) {
					$ha[$i] = 1;
				}
			}
		}
		return $ha;
	}


	/**
	 * Returns the number of days in a given month and year, taking into account leap years.
	 *
	 * @param numeric $month: month (integers 1-12)
	 * @param numeric $year: year (any integer)
	 */
	public function days_in_month($month, $year)
	{
		return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
	}


	/**
	 * Given which days are valid for the parsed time stamp, this generates
	 * an array of valid days for the given month and year.  Each day is available
	 * as the key to the array and the value contains true or false whether the day
	 * meets the cron time stamp
	 * @param mixed $month
	 * @param mixed $year
	 */
	protected function getDaysArray($month, $year)
	{
		$daysinmonth = $this->days_in_month($month, $year);
		$domStar = false;
		$dowStar = false;
		$da = array_pad([], $daysinmonth + 1, null);
		unset($da[0]);
		$dwa = array_pad([], $daysinmonth + 1, null);
		unset($dwa[0]);
		foreach ($this->_attr[self::DAY_OF_MONTH] as $d) {
			if ($d['dom'] === '*' || $d['dom'] === '?') {
				$domStar = (($d['dom'] == '?') || ($d['period'] == 1));
				foreach ($da as $key => $value) {
					if (($key - 1) % $d['period'] == 0) {
						$da[$key] = 1;
					}
				}
			} elseif (is_numeric($d['dom'])) {
				$startDay = $d['dom'];
				if ($d['domWeekday']) {
					$datea = getdate(strtotime("$year-$month-$startDay"));
					if ($datea['wday'] == 6) {
						if ($startDay == 1) {
							$startDay = 3;
						} else {
							$startDay--;
						}
					} elseif ($datea['wday'] == 0) {
						if ($startDay == $daysinmonth) {
							$startDay = $daysinmonth - 2;
						} else {
							$startDay++;
						}
					}
				}
				$endDay = $d['end'];
				if ($d['endWeekday']) {
					$datea = getdate(strtotime("$year-$month-$endDay"));
					if ($datea['wday'] == 6) {
						if ($endDay == 1) {
							$endDay = 3;
						} else {
							$endDay--;
						}
					} elseif ($datea['wday'] == 0) {
						if ($endDay == $daysinmonth) {
							$endDay = $daysinmonth - 2;
						} else {
							$endDay++;
						}
					}
				}
				for ($i = $startDay; $i <= $endDay && $i <= 31; $i += $d['period']) {
					$da[$i] = 1;
				}
			} elseif ($d['dom'] == 'L') {
				$less = empty($d['end']) ? 0 : $d['end'];
				$da[$daysinmonth - $less] = 1;
			}
		}
		$firstDatea = getdate(strtotime("$year-$month-01"));
		foreach ($this->_attr[self::DAY_OF_WEEK] as $d) {
			if (is_numeric($d['dow'])) {
				//start at the first sunday on or before the 1st day of the month
				for ($i = 1 - $firstDatea['wday']; $i <= $daysinmonth; $i += 7) {
					for ($ii = $d['dow']; ($ii <= $d['end']) && ($ii < 7) && (($i + $ii) <= $daysinmonth); $ii += $d['period']) {
						$iii = $i + $ii;
						$w = floor(($iii + 6) / 7);
						$lw = floor(($daysinmonth - $iii) / 7);

						if (($iii >= 0) && ((!$d['week'] || $d['week'] == $w) && (!$d['lastDow'] || $lw == 0))) {
							$dwa[$iii] = 1;
						}
					}
				}
			}
		}
		if ($dowStar) {
			return $da;
		} elseif ($domStar) {
			return $dwa;
		}
		foreach ($da as $key => $value) {
			$da[$key] = $value && ($dwa[$key] ?? 0);
		}
		return $da;
	}


	/**
	 * Given which month are valid for the parsed time stamp, this generates
	 * an array of valid months.  Each month is available as the key to the array
	 * and the value contains true or false whether the month meets the cron
	 * time stamp
	 */
	protected function getMonthsArray()
	{
		$ma = array_pad([], 13, null);
		unset($ma[0]);
		foreach ($this->_attr[self::MONTH_OF_YEAR] as $m) {
			if (is_numeric($m['moy'])) {
				for ($i = $m['moy']; $i <= $m['end'] && $i <= 12; $i += $m['period']) {
					$ma[$i] = 1;
				}
			}
		}
		return $ma;
	}



	/**
	 * Given which years are valid for the parsed time stamp, this generates
	 * an array of valid years.  Each year is available as the key to the array
	 * and the value contains true or false whether the year meets the cron
	 * time stamp.  Only the previos 2 years and next 33 years are available
	 * @return array
	 */
	protected function getYearsArray()
	{
		$ya = [];
		for ($i = self::YEAR_MIN - 1; $i <= self::YEAR_MAX; $i++) {
			$ya['' . $i] = 0;
		}
		foreach ($this->_attr[self::YEAR] as $m) {
			if (is_numeric($m['year'])) {
				for ($i = $m['year']; $i <= $m['end']; $i += $m['period']) {
					$ya[$i] = 1;
				}
			}
		}
		return $ya;
	}

	/**
	 * This calculates the next trigger time for the schedule based on the $priortime
	 * If no parameter time is given, the current system time is used.
	 * @param false|numeric|string $priortime the time or date/time from which to compute the next Trigger Time
	 * @return numeric the unix time of the next trigger event.
	 */
	public function getNextTriggerTime($priortime = false)
	{
		if ($priortime === null || $this->_schedule === null) {
			return null;
		}
		if ($priortime === false) {
			$priortime = time();
		} elseif (!is_numeric($priortime)) {
			$priortime = strtotime($priortime);
		}
		if ($this->_triggerTime !== null) {
			if ($priortime >= $this->_triggerTime) {
				return null;
			}
			return $this->_triggerTime;
		}

		$lastdata = getdate($priortime);

		$oyear = $year = $lastdata['year'];
		$omonth = $month = $lastdata['mon'];

		$minutes = $this->getMinutesArray();
		$hours = $this->getHoursArray();
		$days = $this->getDaysArray($month, $year);
		$months = $this->getMonthsArray();
		$years = $this->getYearsArray();

		// Do Minutes
		$nmin = null;
		$z = -1;
		$s = (!$hours[$lastdata['hours']] || !$days[$lastdata['mday']] || !$months[$month] || !$years[$year]) ? 0 : $lastdata['minutes'] + 1;
		for ($i = $s, $hextra = 0; $i != $s + $z; $i++) {
			if ($i > 59) {
				$hextra = 1;
				$i = 0;
			}
			if ($minutes[$i]) {
				$nmin = $i;
				break;
			}
			$z = 0;
		}

		// Do Hours
		$nhour = null;
		$z = -1;
		$s = (!$days[$lastdata['mday']] || !$months[$lastdata['mon']] || !$years[$year]) ? 0 : $lastdata['hours'];
		for ($i = $s + $hextra, $dextra = 0; $i != $s + $hextra + $z ; $i++) {
			if ($i > 23) {
				$dextra = 1;
				$i = 0;
			}
			if ($hours[$i]) {
				$nhour = $i;
				break;
			}
			$z = 0;
		}

		// Adjust Month/year for extra day
		$nday = '01';
		if ($dextra) {
			$lastdata = getdate($priortime + $dextra * 86400);
			if ($lastdata['mon'] != $month) {
				$year = $lastdata['year'];
				$month = $lastdata['mon'];
				$days = $this->getDaysArray($month, $year);
			}
		}

		// Do the Day of Month
		$dim = $this->days_in_month($month, $year);
		$zeroIndex = !$months[$lastdata['mon']] || !$years[$year];
		$s = ($zeroIndex) ? 1 : $lastdata['mday'];
		for ($i = $s; $i != $s - 1; $i++) {
			if ($i > $dim) {
				$month++;
				if ($month > 12) {
					$month = 1;
					$year++;
					if ($year > self::YEAR_MAX) {
						return null;
					}
				}
				$lastdata = getdate(strtotime("$year-$month-01"));
				$dim = $this->days_in_month($month, $year);
				$i = 1;
				$days = $this->getDaysArray($month, $year);
				$s = ($zeroIndex) ? 1 : $lastdata['mday'];
			}
			if ($days[$i]) {
				$nday = $i;
				break;
			}
		}

		// Do the Month of the Year
		$nmonth = null;
		$z = -1;
		$s = (!$years[$year]) ? 1 : $lastdata['mon'];
		for ($i = $s, $yextra = 0; $i != $s + $z; $i++) {
			if ($i > 12) {
				$yextra = 1;
				$year++;
				$i = 1;
			}
			if ($months[$i]) {
				$nmonth = $i;
				break;
			}
			$z = 0;
		}

		// If month or year different, recompute day of month for that month/year
		if ($yextra || $nmonth != $omonth) {
			$month = $nmonth;
			$dim = $this->days_in_month($month, $year);
			$days = $this->getDaysArray($month, $year);
			$zeroIndex = !$months[$omonth] || !$years[$oyear];
			$s = ($zeroIndex) ? 1 : $lastdata['mday'];
			for ($i = $s; $i != $s - 1; $i++) {
				if ($i > $dim) {
					$month++;
					if ($month > 12) {
						$month = 1;
						$year++;
						if ($year > self::YEAR_MAX) {
							return null;
						}
					}
					$lastdata = getdate(strtotime("$year-$month-02"));
					$dim = $this->days_in_month($month, $year);
					$i = 1;
					$days = $this->getDaysArray($month, $year);
					$s = ($zeroIndex) ? 1 : $lastdata['mday'];
				}
				if ($days[$i]) {
					$nday = $i;
					break;
				}
			}
		}

		// Do Year
		$nyear = null;
		for ($i = $lastdata['year'] + $yextra; $i <= self::YEAR_MAX; $i++) {
			if ($years[$i]) {
				$nyear = $i;
				break;
			}
		}
		if ($nyear === null) {
			$time = null;
		} else {
			$time = strtotime("$nyear-$nmonth-$nday $nhour:$nmin:00");
		}
		return $time;
	}
}
