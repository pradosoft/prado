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
 * TTimeScheduler tracks scheduling event times. It parses a schedule expression
 * and computes the dates and times to trigger.
 *
 * cron setup
 *     '*     *         *                *              *         *'
 * (minute) (hour) (day of month) (month of year) (day of week) (year)
 *  (0-59) (0-23)  (1-31)    (1-12|Jan,Feb,-,Dec) (0-6|Sun-Sat) (1970-2099)
 *
 * '*' (star) is any time.
 * adding '/(\d)' is every \d moments.
 * Ranges ('1-5') and lists ('1,3,5') are supported, and month/day-of-week names participate in ranges
 * (e.g. 'jan-mar', 'mon-fri', including cross-language ranges).
 * Day of Month can add 'W' after a single day (e.g. '15W') to signify the closest weekday; 'W' is not
 * allowed on a range or with a step.
 * Day of Month can add 'L' for last day, or 'L-2' for last day minus two.
 * Day of Week can add '(\d)L' after the day of week for last day of month of that type.
 * Day of Week can add "#(\d)" after the day to signify which week the day is from.
 * The 'L' day-of-week qualifier requires a numeric day (e.g. '5L', not 'friL'); '#' accepts a numeric
 * day or a name (e.g. '3#2' or 'wed#2').
 *
 * The year field is optional, so both five-field ('* * * * *') and six-field ('* * * * * *')
 * expressions parse. Either day field may be '?' (or '*') to leave it unrestricted. When BOTH the day of
 * month and the day of week are restricted, a day matches when it matches EITHER one (the POSIX/Vixie cron
 * union, as documented at crontab.guru); when one is '*'/'?', only the other applies. A date-and-weekday
 * conjunction (a true "Friday the 13th") is therefore not expressible in one expression; schedule the
 * superset and filter in the task, as standard cron also requires.
 *
 * The supported year window is {@see YEAR_MIN} (1970) through {@see YEAR_MAX} (2099);
 * {@see getNextTriggerTime} returns null when no trigger exists in that window.
 *
 * Generally avoid 01:00:00 to 2:59:59 as they may not trigger or double
 * trigger during daylight savings time changes.
 *
 * Month names support English, German, Spanish, French, Italian, Russian, Hindi, and Arabic and their
 * abbreviations. Day-of-week names support the same languages except Arabic (Arabic uses numbered days),
 * and their abbreviations of at least two letters (bare single letters are not accepted, being ambiguous
 * across languages). All names are case-insensitive, including non-ASCII (Cyrillic, Hindi, accented).
 *
 * There are schedule shortcuts:
 *     '@yearly' => '0 0 1 1 ?'
 *     '@annually' => '0 0 1 1 ?'
 *     '@monthly' => '0 0 1 * ?'
 *     '@weekly' => '0 0 ? * 0'
 *     '@midnight' => '0 0 * * ?'
 *     '@noon' => '0 12 * * ?'
 *     '@daily' => '0 0 * * ?'
 *     '@hourly' => '0 * * * ?'
 *     '@minute' => '* * * * ?'
 *
 * An efficient one-off task at a specified unix time can be scheduled with
 * '@(\d)' where \d is the unix time in the local php instance time zone.
 * This minimizes costly validation, parsing, and nextTriggerTime processing.
 * This allows for a higher cron task throughput to handle more cron tasks.
 *
 * e.g. '@1668165071' triggers at exactly 2022-11-11 11:11:11 (the one-off keeps second precision). The
 * recurring expression '11 11 11 11 ? 2022' (minute hour day month dow year) is only an approximate
 * analog: it fires at 11:11:00, since cron expressions have minute resolution.
 *
 * '@now' and '@now+N' (N seconds, N >= 0) are resolved to a concrete '@(\d)' one-off at parse time
 * using {@see time}, so '@now+90' schedules a single run 90 seconds after the schedule is set.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @see https://crontab.guru For more info on Crontab Schedule Expressions.
 * @todo symfony style shortcuts
 * @todo add "seconds", maybe requires a special prefix character like ':'
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

	/** schedule-invariant minute lookup, memoized between {@see getNextTriggerTime} calls */
	private $_minutesCache;

	/** schedule-invariant hour lookup, memoized between {@see getNextTriggerTime} calls */
	private $_hoursCache;

	/** schedule-invariant month lookup, memoized between {@see getNextTriggerTime} calls */
	private $_monthsCache;

	private static $_intervals = [
		'@yearly' => '0 0 1 1 ?',
		'@annually' => '0 0 1 1 ?',
		'@monthly' => '0 0 1 * ?',
		'@weekly' => '0 0 ? * 0',
		'@midnight' => '0 0 * * ?',
		'@noon' => '0 12 * * ?',
		'@daily' => '0 0 * * ?',
		'@hourly' => '0 * * * ?',
		'@minute' => '* * * * ?',
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
					12 => '(?:december|dezember|diciembre|décembre|dicembre|dec|dez|déc|dic|Декабрь|Дек|दिसम्बर|ديسمبر)',
				],
		self::DAY_OF_WEEK => [ //no Arabic as those are just numbered days of the week
					// Bare single letters are omitted (ambiguous across languages), and 'do' belongs only to
					// Thursday (German Donnerstag); it is not a Sunday token, so it resolves unambiguously.
					0 => '(?:sunday|sonntag|sun|son|su|so|domingo|dimanche|dim|domenica|dom|Воскресенье|Вск|Вс|रविवार|रवि)',
					1 => '(?:monday|montag|mon|mo|lune|lu|lundi|lun|lunedì|Понедельник|Пнд|Пн|सोमवार|सोम)',
					2 => '(?:tuesday|dienstag|die|tue|tu|di|martes|ma|mardi|mar|martedì|Вторник|Втр|Вт|मंगलवार|मंगल)',
					3 => '(?:wednesday|mittwoch|mit|wed|we|mi|miércoles|mercredi|mer|mercoledì|me|Среда|Сре|Ср|बुधवार|बुध)',
					4 => '(?:thursday|donnerstag|don|thu|th|do|jueves|ju|jeudi|jeu|giovedì|gio|gi|Четверг|Чтв|Чт|गुरुवार|गुरु)',
					5 => '(?:friday|freitag|fre|fri|fr|viernes|vi|vendredi|ven|venerdì|ve|Пятница|Птн|Пт|शुक्रवार|शुक्र)',
					6 => '(?:saturday|samstag|sam|sat|sa|sábado|samedi|sabato|sab|Суббота|Сбт|Сб|शनिवार|शनि)',
				],
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
		$this->_attr = [];
		$this->_triggerTime = null;
		$this->_minutesCache = null;
		$this->_hoursCache = null;
		$this->_monthsCache = null;
		if ($schedule === '') {
			$schedule = '* * * * * *';
		} elseif ($schedule === null) {
			$this->_schedule = null;
			return;
		}
		$schedule = trim($schedule);
		// Resolve '@now' / '@now+N' to a concrete one-off timestamp at parse time (via the time() seam),
		// so it becomes a stable one-off that survives persistence/reload without "now" drifting.
		if (preg_match('/^@now(?:\+(\d+))?$/i', $schedule, $nowMatch)) {
			$schedule = '@' . ($this->time() + (int) ($nowMatch[1] ?? 0));
		}
		// One-off at a concrete Unix time: only a canonical non-negative integer (e.g. '@1668165071').
		// Non-canonical numerics ('@-5', '@3.5', '@1e3', '@+5', '@ 5') fall through to the full validator,
		// which rejects them; the stored schedule is normalized so a persist/reload round-trips.
		if (preg_match('/^@(\d+)$/', $schedule, $atMatch)) {
			$this->_triggerTime = (int) $atMatch[1];
			$this->_schedule = '@' . $this->_triggerTime;
			return;
		}
		$this->_schedule = $schedule;

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
			$domWOW = '(?:[1-9]|[12][0-9]|3[01])';
			$domStar = '\*(?:\/(?:[1-9]|[12][0-9]|3[01]))?';
			// 'W' (nearest weekday) attaches only to a single bare day; ranges and steps may not carry 'W'.
			$domRegex = '(?:' . $domWOW . 'W|' . $domWOW . '(?:\-' . $domWOW . ')?(?:\/' . $domWOW . ')?|L(?:\-[1-5])?)';
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
			// Build the shortcut alternation from the keys of $_intervals (longest first so no
			// shorter key shadows a longer one), so every documented shortcut resolves.
			$shortcutKeys = array_map(static fn ($k) => substr($k, 1), array_keys(self::$_intervals));
			usort($shortcutKeys, static fn ($a, $b) => strlen($b) <=> strlen($a));
			$shortcuts = implode('|', array_map(static fn ($k) => preg_quote($k, '/'), $shortcutKeys));
			$fullValidator = '/^(?:(@(?:' . $shortcuts . '))|' .
				'(?#minute)((?:' . $minuteStar . '|' . $minuteRegex . ')(?:\,(?:' . $minuteStar . '|' . $minuteRegex . '))*)[\s]+' .
				'(?#hour)((?:' . $hourStar . '|' . $hourRegex . ')(?:\,(?:' . $hourStar . '|' . $hourRegex . '))*)[\s]+' .
				'(?#DoM)(\?|(?:(?:' . $domStar . '|' . $domRegex . ')(?:,(?:' . $domStar . '|' . $domRegex . '))*))[\s]+' .
				'(?#month)((?:' . $monthStar . '|' . $monthRegex . ')(?:\,(?:' . $monthStar . '|' . $monthRegex . '))*)[\s]+' .
				'(?#DoW)(\?|(?:' . $dowStar . '|' . $dowRegex . ')(?:\,(?:' . $dowStar . '|' . $dowRegex . '))*)' .
				'(?#year)(?:[\s]+' .
					'((?:' . $yearStar . '|' . $yearRegex . ')(?:\,(?:' . $yearStar . '|' . $yearRegex . '))*)' .
				')?' .
			')$/iu';

			$minuteValidator = '/^(\*|' . $minute . ')(?:\-(' . $minute . '))?(?:\/(' . $minute . '))?$/iu';
			$hourValidator = '/^(\*|' . $hour . ')(?:\-(' . $hour . '))?(?:\/(' . $hour . '))?$/iu';
			$domValidator = '/^(\*|\?|L|' . $domWOW . ')(W)?(?:\-(' . $domWOW . ')(W)?)?(?:\/(' . $domWOW . '))?$/iu';
			$monthValidator = '/^(\*|' . $month . ')(?:\-(' . $month . '))?(?:\/(' . $month . '))?$/iu';
			$dowValidator = '/^(\*|\?|' . $dow . ')(L)?(?:\-(' . $dow . ')(L)?)?(?:\/(' . $dow . '))?(?:#([1-5]))?$/iu';
			$yearValidator = '/^(\*|' . $year . ')(?:\-(' . $year . '))?(?:\/([1-9]?[0-9]))?$/iu';
			self::$_validatorCache = [
					'm' => $minuteValidator,
					'h' => $hourValidator,
					'dom' => $domValidator,
					'mo' => $monthValidator,
					'dow' => $dowValidator,
					'y' => $yearValidator,
					'f' => $fullValidator,
				];
		}

		do {
			if (!preg_match($fullValidator, $schedule, $matches)) {
				throw new TInvalidDataValueException('timescheduler_invalid_string', $schedule);
			}
			if ($matches[1]) {
				// Resolve the shortcut case-insensitively: the validator matches it with /i, so a
				// case-sensitive compare against the lowercase keys never matched, leaving $schedule
				// unchanged and spinning this loop forever on any mixed-case shortcut (e.g. '@DAILY').
				$key = strtolower($matches[1]);
				if (!isset(self::$_intervals[$key])) {
					throw new TInvalidDataValueException('timescheduler_invalid_string', $schedule);
				}
				$schedule = self::$_intervals[$key];
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
				if ((($m2[2] ?? '') === '') && ($m2[3] ?? 0)) {
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
				if ((($m2[2] ?? '') === '') && ($m2[3] ?? 0)) {
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
				if ((($m2[3] ?? '') === '') && ($m2[5] ?? 0)) {
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
				if ((($m2[2] ?? '') === '') && ($m2[3] ?? 0)) {
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
					$data = ['dow' => 0, 'end' => 6, 'star' => $m2[1]];
				} else {
					$data = ['dow' => (int) $this->translateDayOfWeek($m2[1]), 'star' => false];
					$data['end'] = (isset($m2[3]) && $m2[3]) ? (int) $this->translateDayOfWeek($m2[3]) : $data['dow'];
				}
				$data['lastDow'] = $m2[2] ?? null;
				$data['lastEnd'] = $m2[4] ?? null;
				$data['period'] = (int) max($m2[5] ?? 1, 1);
				$data['week'] = $m2[6] ?? null;
				if ((($m2[3] ?? '') === '') && ($m2[5] ?? 0)) {
					$data['end'] = 6; //No end with period
				}
				$this->_attr[self::DAY_OF_WEEK][] = $data;
			} else {
				throw new TInvalidDataValueException('timescheduler_invalid_string', $match);
			}
		}

		$this->_attr[self::YEAR] = [];
		$matches[7] ??= '*';
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
				if ((($m2[2] ?? '') === '') && ($m2[3] ?? 0)) {
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
			if (preg_match('/^(' . $regex . ')$/iu', $v)) {
				return $moy;
			}
		}
		// Unreachable for validator-accepted input; fail loud rather than silently mapping to 0 (January)
		// if the keyword table and the field validator ever diverge.
		throw new TInvalidDataValueException('timescheduler_invalid_string', $v);
	}

	protected function translateDayOfWeek($v)
	{
		if (is_numeric($v)) {
			return $v;
		}
		foreach (self::$_keywords[self::DAY_OF_WEEK] as $dow => $regex) {
			if (preg_match('/^(' . $regex . ')$/iu', $v)) {
				return $dow;
			}
		}
		// Unreachable for validator-accepted input; fail loud rather than silently mapping to 0 (Sunday)
		// if the keyword table and the field validator ever diverge.
		throw new TInvalidDataValueException('timescheduler_invalid_string', $v);
	}


	/**
	 * Given which minutes are valid for the parsed time stamp, this generates
	 * an array of valid minutes.  Each minute is available as the key to the array
	 * and the value contains true or false whether the minute meets the cron
	 * time stamp
	 */
	protected function getMinutesArray()
	{
		if ($this->_minutesCache !== null) {
			return $this->_minutesCache;
		}
		$ma = array_pad([], 60, null);
		foreach ($this->_attr[self::MINUTE] as $m) {
			if (is_numeric($m['min'])) {
				for ($i = $m['min']; $i <= $m['end'] && $i < 60; $i += $m['period']) {
					$ma[$i] = 1;
				}
			}
		}
		return $this->_minutesCache = $ma;
	}


	/**
	 * Given which hours are valid for the parsed time stamp, this generates
	 * an array of valid hours.  Each hour is available as the key to the array
	 * and the value contains true or false whether the hour meets the cron
	 * time stamp
	 */
	protected function getHoursArray()
	{
		if ($this->_hoursCache !== null) {
			return $this->_hoursCache;
		}
		$ha = array_pad([], 24, null);
		foreach ($this->_attr[self::HOUR] as $h) {
			if (is_numeric($h['hour'])) {
				for ($i = $h['hour']; $i <= $h['end'] && $i < 24; $i += $h['period']) {
					$ha[$i] = 1;
				}
			}
		}
		return $this->_hoursCache = $ha;
	}


	/**
	 * Returns the number of days in a given month and year, taking into account leap years.
	 * @param int $month the month (1-12)
	 * @param int $year the year (any integer)
	 * @return int the number of days in the month
	 * @since 4.4.0
	 */
	public function getDaysInMonth($month, $year)
	{
		return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
	}

	/**
	 * Returns the number of days in a given month and year.
	 * @param int $month the month (1-12)
	 * @param int $year the year (any integer)
	 * @return int the number of days in the month
	 * @deprecated 4.4.0 use {@see getDaysInMonth}; retained as a backward-compatible alias.
	 */
	public function days_in_month($month, $year)
	{
		return $this->getDaysInMonth($month, $year);
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
		$daysinmonth = $this->getDaysInMonth($month, $year);
		$domStar = false;
		$dowStar = false;
		$da = array_pad([], $daysinmonth + 1, null);
		unset($da[0]);
		$dwa = array_pad([], $daysinmonth + 1, null);
		unset($dwa[0]);
		foreach ($this->_attr[self::DAY_OF_MONTH] as $d) {
			if ($d['dom'] === '*' || $d['dom'] === '?') {
				// Accumulate (do not overwrite): any unrestricted element ('?' or plain '*') leaves the
				// day-of-month unrestricted, even when a later list element (e.g. '*/5') is a restriction.
				$domStar = $domStar || ($d['dom'] == '?') || ($d['period'] == 1);
				foreach ($da as $key => $value) {
					if (($key - 1) % $d['period'] == 0) {
						$da[$key] = 1;
					}
				}
			} elseif (is_numeric($d['dom'])) {
				$startDay = (int) $d['dom'];
				if ($d['domWeekday']) {
					// Clamp an over-large W day (e.g. '30W' in February, '31W' in a 30-day month) to the
					// last valid day before finding the nearest weekday, so it does not overflow into the
					// next month via strtotime() and silently mis-/never-fire. Plain numeric days are NOT
					// clamped — the loop bound below correctly skips a day the month does not have.
					$startDay = min($startDay, $daysinmonth);
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
				$endDay = (int) $d['end'];
				if ($d['endWeekday']) {
					$endDay = min($endDay, $daysinmonth);
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
				for ($i = $startDay; $i <= $endDay && $i <= $daysinmonth; $i += $d['period']) {
					$da[$i] = 1;
				}
			} elseif ($d['dom'] == 'L') {
				$less = empty($d['end']) ? 0 : $d['end'];
				$da[$daysinmonth - $less] = 1;
			}
		}
		$firstDatea = getdate(strtotime("$year-$month-01"));
		foreach ($this->_attr[self::DAY_OF_WEEK] as $d) {
			if (!empty($d['star']) && (($d['star'] === '?') || ($d['period'] == 1))) {
				$dowStar = true;
			}
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
		if ($dowStar) { // day-of-week unrestricted: day-of-month determines the days
			return $da;
		} elseif ($domStar) { // day-of-month unrestricted: day-of-week determines the days
			return $dwa;
		}
		// Both fields restricted: a day matches when it matches EITHER (POSIX/Quartz union).
		foreach ($da as $key => $value) {
			$da[$key] = $value || ($dwa[$key] ?? 0);
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
		if ($this->_monthsCache !== null) {
			return $this->_monthsCache;
		}
		$ma = array_pad([], 13, null);
		unset($ma[0]);
		foreach ($this->_attr[self::MONTH_OF_YEAR] as $m) {
			if (is_numeric($m['moy'])) {
				for ($i = $m['moy']; $i <= $m['end'] && $i <= 12; $i += $m['period']) {
					$ma[$i] = 1;
				}
			}
		}
		return $this->_monthsCache = $ma;
	}



	/**
	 * Tests whether a year satisfies the parsed year field. This replaces the former dense year array
	 * with an O(year-tuples) predicate, so no ~130-entry array is allocated per query.
	 * @param int $year the year to test
	 * @return bool whether the year matches the schedule
	 * @since 4.4.0
	 */
	protected function isYearValid($year)
	{
		foreach ($this->_attr[self::YEAR] as $m) {
			if (is_numeric($m['year']) && $year >= $m['year'] && $year <= $m['end']
					&& ($year - $m['year']) % $m['period'] == 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Calculates the next trigger time strictly after $priortime. When no time is given the current system
	 * time (from {@see time}) is used; a string is parsed with {@see \strtotime}.
	 * @param false|numeric|string $priortime the time from which to compute the next trigger time
	 * @return ?int the Unix timestamp of the next trigger, or null when the schedule is unset, a one-off
	 *   time has already passed, or no trigger exists on or before {@see YEAR_MAX}. The instant is computed
	 *   in the application default timezone.
	 */
	public function getNextTriggerTime($priortime = false)
	{
		if ($priortime === null || $this->_schedule === null) {
			return null;
		}
		if ($priortime === false) {
			$priortime = $this->time();
		} elseif (!is_numeric($priortime)) {
			$priortime = strtotime($priortime);
		}
		if ($this->_triggerTime !== null) {
			if ($priortime >= $this->_triggerTime) {
				return null;
			}
			return $this->_triggerTime;
		}

		$minutes = $this->getMinutesArray();
		$hours = $this->getHoursArray();
		$months = $this->getMonthsArray();

		// Search strictly after $priortime at minute resolution (seconds are dropped). The instant is
		// advanced field-by-field from coarsest (year) to finest (minute); each carry resets the finer
		// fields, and DateTimeImmutable handles month length and overflow, so the (year, month, day) tuple
		// can never desync. The loop is bounded by YEAR_MAX.
		$tz = new \DateTimeZone(date_default_timezone_get());
		$dt = (new \DateTimeImmutable('@' . (int) $priortime))->setTimezone($tz);
		$dt = $dt->setTime((int) $dt->format('G'), (int) $dt->format('i') + 1, 0);

		$days = null;
		$daysMonth = $daysYear = null;
		while (($year = (int) $dt->format('Y')) <= self::YEAR_MAX) {
			if (!$this->isYearValid($year)) {
				$dt = new \DateTimeImmutable(sprintf('%04d-01-01 00:00:00', $year + 1), $tz);
				continue;
			}
			$month = (int) $dt->format('n');
			if (empty($months[$month])) {
				$nextMonth = ($month % 12) + 1;
				$dt = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year + ($nextMonth === 1 ? 1 : 0), $nextMonth), $tz);
				continue;
			}
			if ($daysMonth !== $month || $daysYear !== $year) {
				$days = $this->getDaysArray($month, $year);
				$daysMonth = $month;
				$daysYear = $year;
			}
			if (empty($days[(int) $dt->format('j')])) {
				$dt = $dt->modify('tomorrow'); // next calendar day at 00:00:00
				continue;
			}
			$hour = (int) $dt->format('G');
			if (empty($hours[$hour])) {
				$dt = $dt->setTime($hour + 1, 0, 0); // next hour at :00 (24 rolls to next day)
				continue;
			}
			$minute = (int) $dt->format('i');
			if (empty($minutes[$minute])) {
				$dt = $dt->setTime($hour, $minute + 1, 0); // next minute (60 rolls to next hour)
				continue;
			}
			return $dt->getTimestamp();
		}
		return null;
	}

	/**
	 * Returns up to the next $count trigger times strictly after $priortime, in ascending order. Each is
	 * the {@see getNextTriggerTime} of the previous, so the list stops early when the schedule has no
	 * further triggers (e.g. a one-off, or the {@see YEAR_MAX} horizon). Useful for previews and catch-up.
	 * @param int $count the maximum number of trigger times to return
	 * @param false|numeric|string $priortime the time to start after; false for the current time
	 * @return int[] the next trigger timestamps (at most $count, fewer when the schedule is exhausted)
	 * @since 4.4.0
	 */
	public function getNextTriggerTimes(int $count, $priortime = false): array
	{
		$times = [];
		$time = $priortime;
		for ($i = 0; $i < $count; $i++) {
			$time = $this->getNextTriggerTime($time);
			if ($time === null) {
				break;
			}
			$times[] = $time;
		}
		return $times;
	}

	/**
	 * Returns the current Unix timestamp. Wraps the global {@see \time()} as a single
	 * overridable seam so that subclasses and tests can control the clock.
	 * @return int the current Unix timestamp
	 * @since 4.4.0
	 */
	protected function time(): int
	{
		return \time();
	}
}
