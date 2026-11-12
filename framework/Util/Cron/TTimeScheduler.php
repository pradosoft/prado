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
use Prado\TPropertyValue;

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
 * Seconds attach to a minute element after a colon, 'minute:second', with the same syntax as the minute
 * (single, range, step, star): '0:30 * * * *' is every hour at 00:30, '*:*\/10 * * * *' is every ten
 * seconds, '*\/5:15 * * * *' is every five minutes at second 15, and '0:0,30:0 * * * *' is at :00 and
 * :30.  An element without seconds triggers at second 0, so the field count and every existing
 * expression are unchanged.  A sub-minute schedule runs on time under the cron daemon or
 * {@see \Prado\Util\Cron\TDbCronManager::setPollsPerMinute PollsPerMinute}; a per-minute tick runs it
 * at its next pass.
 *
 * A hashed field spreads the tasks of an application over a field's range so they do not all fire at
 * the same instant: 'H' (Jenkins) or '#' (Symfony) in the minute, hour, day of month (1-28), month, day
 * of week, or second position is a stable value derived from {@see setHashContext HashContext}, the
 * task name in {@see \Prado\Util\Cron\TCronTask}; 'H(a-b)' hashes within a range and 'H/15' is every
 * 15 from a hashed start below 15 ('H(a-b)/s' both).  The hashed schedule shortcuts are
 *     '#hourly' => 'H * * * ?'
 *     '#daily' => 'H H * * ?'
 *     '#midnight' => 'H H(0-2) * * ?'
 *     '#weekly' => 'H H ? * H'
 *     '#weekly@midnight' => 'H H(0-2) ? * H'
 *     '#monthly' => 'H H H * ?'
 *     '#monthly@midnight' => 'H H(0-2) H * ?'
 *     '#yearly', '#annually' => 'H H H H ?'
 *     '#yearly@midnight', '#annually@midnight' => 'H H(0-2) H H ?'
 * {@see getSchedule} keeps the expression as written and {@see getResolvedSchedule} is the concrete
 * expression; the value is `min + crc32(context . "\0" . field) % (max - min + 1)`, so two tasks with
 * the same schedule and different names fire at different times and a task keeps its times across runs.
 *
 * A period, 'every <interval>', triggers at a fixed interval from an anchor: 'every 10 seconds',
 * 'every 90 minutes', 'every 2 days', 'every 1 month', 'every first monday of next month', any
 * relative string of {@see \DateInterval::createFromDateString}.  'from <time>' sets the anchor, the
 * first trigger (default: the time the schedule is set), and 'until <time>' the last; both take '@<unix
 * time>' or a {@see \strtotime} string.  The anchor is resolved at parse time and the stored schedule is
 * 'every <interval> from @<unix time>[ until @<unix time>]', so a persisted period keeps its anchor.
 * Second, minute, and hour intervals are fixed lengths; day, month, year, and relative intervals step
 * the calendar in the time zone, as the k-th multiple of the interval from the anchor.
 *
 * 'every <interval> after end' counts each trigger from the time before it, which the cron module sets
 * to the end of the previous run ({@see getIsAfterEnd}), instead of the anchor's grid: a fixed delay
 * between runs.
 *
 * '<schedule> except <dates>' skips the triggers on excluded local dates: a comma list of 'YYYY-MM-DD',
 * a range 'YYYY-MM-DD..YYYY-MM-DD', a yearly 'MM-DD', or a yearly range 'MM-DD..MM-DD' that may span
 * the new year, such as '0 9 * * 1-5 except 12-24..12-26, 01-01, 2026-04-03'.
 *
 * '@never' has no trigger, for a task that only runs from a chain or on request.
 *
 * The schedule is in the {@see setTimeZone TimeZone}, default the application time zone
 * ({@see \date_default_timezone_get()}); the trigger times are Unix timestamps.  Across a daylight
 * saving change, a time in the skipped hour does not trigger that day, and a time in the repeated
 * hour triggers once, at its first occurrence.
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

	/** @var ?string the time zone of the schedule, or null for the application time zone */
	private ?string $_timeZone = null;

	/** @var ?array[] the excluded dates of the 'except' clause, each [yearly, from, to] */
	private ?array $_except = null;

	/** The most excluded spans a trigger search skips before it gives up. */
	protected const EXCEPT_LIMIT = 1000;

	/** @var string[] the properties holding a parsed schedule; restored when a new schedule is invalid */
	private const SCHEDULE_STATE = ['_schedule', '_resolved', '_hasHash', '_period', '_triggerTime', '_attr', '_minutesCache', '_secondsCache', '_hoursCache', '_monthsCache', '_except'];

	/** The cron schedule */
	private $_schedule;

	/** The concrete expression after shortcut and hashed field resolution, null for a one-off or a period */
	private ?string $_resolved = null;

	/** The string the hashed fields derive from */
	private string $_hashContext = '';

	/** whether the schedule holds a hashed field, so a context change re-resolves it */
	private bool $_hasHash = false;

	/** The parsed period: interval, anchor, until, fixed seconds, and whether the interval is relative */
	private ?array $_period = null;

	/** efficient one off trigger time */
	protected $_triggerTime;

	/** the parsed attributes of the schedule */
	private $_attr = [];

	/** schedule-invariant minute lookup, memoized between {@see getNextTriggerTime} calls */
	private $_minutesCache;

	/** schedule-invariant seconds per minute, memoized with the minute lookup */
	private $_secondsCache;

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
	/** The hashed schedule shortcuts, each resolved per {@see setHashContext HashContext} */
	private static $_hashIntervals = [
		'#hourly' => 'H * * * ?',
		'#daily' => 'H H * * ?',
		'#midnight' => 'H H(0-2) * * ?',
		'#weekly' => 'H H ? * H',
		'#weekly@midnight' => 'H H(0-2) ? * H',
		'#monthly' => 'H H H * ?',
		'#monthly@midnight' => 'H H(0-2) H * ?',
		'#yearly' => 'H H H H ?',
		'#yearly@midnight' => 'H H(0-2) H H ?',
		'#annually' => 'H H H H ?',
		'#annually@midnight' => 'H H(0-2) H H ?',
		];
	/** The hashed value range of each field; the day of month stops at 28 so every month has the day */
	private static $_hashRanges = [
		self::MINUTE => [0, 59],
		self::HOUR => [0, 23],
		self::DAY_OF_MONTH => [1, 28],
		self::MONTH_OF_YEAR => [1, 12],
		self::DAY_OF_WEEK => [0, 6],
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
	 * @return string This returns the cron schedule as written, with '@now' and a period's anchor resolved
	 */
	public function getSchedule()
	{
		return $this->_schedule;
	}

	/**
	 * @return ?string the concrete expression the schedule resolves to: shortcuts expanded and hashed
	 *   fields replaced by their values; the schedule itself for a one-off or a period; null when unset
	 * @since 4.4.0
	 */
	public function getResolvedSchedule(): ?string
	{
		return $this->_resolved ?? $this->_schedule;
	}

	/**
	 * @return string the string the hashed fields ('H', '#') derive their values from, default ''
	 * @since 4.4.0
	 */
	public function getHashContext(): string
	{
		return $this->_hashContext;
	}

	/**
	 * Sets the hash context and re-resolves a schedule with hashed fields.
	 * @param ?string $context the string the hashed fields derive their values from, such as the task name
	 * @since 4.4.0
	 */
	public function setHashContext($context)
	{
		$context = ($context === null) ? '' : TPropertyValue::ensureString($context);
		if ($context === $this->_hashContext) {
			return;
		}
		$this->_hashContext = $context;
		if ($this->_hasHash) {
			$this->setSchedule($this->_schedule);
		}
	}

	/**
	 * @return ?string the time zone of the schedule, or null for the application time zone
	 * @since 4.4.0
	 */
	public function getTimeZone(): ?string
	{
		return $this->_timeZone;
	}

	/**
	 * @param ?string $timeZone the time zone of the schedule, an identifier such as 'America/New_York' or
	 *   an offset such as '+02:00'; null or '' for the application time zone
	 * @throws TInvalidDataValueException when the time zone is not known
	 * @since 4.4.0
	 */
	public function setTimeZone($timeZone)
	{
		$timeZone = ($timeZone === null || $timeZone === '') ? null : TPropertyValue::ensureString($timeZone);
		if ($timeZone !== null) {
			try {
				new \DateTimeZone($timeZone);
			} catch (\Exception $e) {
				throw new TInvalidDataValueException('timescheduler_invalid_timezone', $timeZone);
			}
		}
		$this->_timeZone = $timeZone;
	}

	/**
	 * @return \DateTimeZone the time zone the schedule is evaluated in: {@see getTimeZone TimeZone}, or the application time zone
	 * @since 4.4.0
	 */
	protected function getDateTimeZone(): \DateTimeZone
	{
		return new \DateTimeZone($this->_timeZone ?? date_default_timezone_get());
	}

	/**
	 * Parses a time string in the {@see getDateTimeZone time zone}, relative to {@see time}.
	 * @param string $value a {@see \strtotime} string
	 * @return false|int the Unix timestamp, or false when the string does not parse
	 * @since 4.4.0
	 */
	protected function parseTime(string $value): false|int
	{
		$now = (new \DateTimeImmutable('@' . $this->time()))->setTimezone($this->getDateTimeZone());
		try {
			$time = @$now->modify($value); // false and a warning before PHP 8.3
		} catch (\Throwable $e) { // DateMalformedStringException since PHP 8.3
			$time = false;
		}
		return ($time === false) ? false : $time->getTimestamp();
	}

	/**
	 * Parses a Cron Time Tag.  An invalid schedule throws and leaves the previous
	 * schedule in place.
	 * @param mixed $schedule
	 * @throws TInvalidDataValueException when the schedule does not parse
	 */
	public function setSchedule($schedule)
	{
		$state = $this->getScheduleState();
		try {
			$this->parseSchedule($schedule);
		} catch (TInvalidDataValueException $e) {
			$this->setScheduleState($state);
			throw $e;
		}
	}

	/**
	 * Returns the parsed schedule state that {@see setSchedule} replaces.
	 * @return array the property values, keyed by property name.
	 * @since 4.4.0
	 */
	protected function getScheduleState(): array
	{
		$state = [];
		foreach (self::SCHEDULE_STATE as $name) {
			$state[$name] = $this->$name;
		}
		return $state;
	}

	/**
	 * Restores a parsed schedule state from {@see getScheduleState}.
	 * @param array $state the property values, keyed by property name.
	 * @since 4.4.0
	 */
	protected function setScheduleState(array $state): void
	{
		foreach (self::SCHEDULE_STATE as $name) {
			$this->$name = $state[$name];
		}
	}

	/**
	 * Parses a Cron Time Tag into the schedule state.
	 * @param mixed $schedule
	 * @throws TInvalidDataValueException when the schedule does not parse
	 * @since 4.4.0
	 */
	protected function parseSchedule($schedule): void
	{
		$this->_attr = [];
		$this->_triggerTime = null;
		$this->_resolved = null;
		$this->_period = null;
		$this->_hasHash = false;
		$this->_minutesCache = null;
		$this->_secondsCache = null;
		$this->_hoursCache = null;
		$this->_monthsCache = null;
		if ($schedule === '') {
			$schedule = '* * * * * *';
		} elseif ($schedule === null) {
			$this->_schedule = null;
			return;
		}
		$schedule = trim($schedule);
		$this->_except = null;
		$except = null;
		if (preg_match('/^(.+?)\s+except\s+(.+)$/i', $schedule, $exceptMatch)) {
			$schedule = trim($exceptMatch[1]);
			$except = $this->parseExcept($exceptMatch[2]);
		}
		$this->parseExpression($schedule);
		if ($except !== null) {
			$this->_except = $except[0];
			$this->_schedule .= ' except ' . $except[1];
			if ($this->_resolved !== null) {
				$this->_resolved .= ' except ' . $except[1];
			}
		}
	}

	/**
	 * Parses a schedule without its 'except' clause into the schedule state.
	 * @param string $schedule the trimmed schedule
	 * @throws TInvalidDataValueException when the schedule does not parse
	 * @since 4.4.0
	 */
	protected function parseExpression(string $schedule): void
	{
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
		// '@never' has no trigger: a task that only runs from a chain or on request.
		if (strcasecmp($schedule, '@never') === 0) {
			$this->_triggerTime = -1;
			$this->_schedule = '@never';
			return;
		}
		if (preg_match('/^every\s+(.+?)(\s+after\s+end)?(?:\s+from\s+(\S+(?:\s+\S+)*?))?(?:\s+until\s+(\S+(?:\s+\S+)*?))?$/i', $schedule, $everyMatch)) {
			$this->parsePeriod($everyMatch[1], ($everyMatch[3] ?? '') ?: null, ($everyMatch[4] ?? '') ?: null, ($everyMatch[2] ?? '') !== '');
			return;
		}
		$this->_schedule = $schedule;
		$resolved = self::$_hashIntervals[strtolower($schedule)] ?? $schedule;
		$resolved = $this->resolveHashedFields($resolved);

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
			$secondsPart = '(?::(?:' . $minuteStar . '|' . $minuteRegex . '))?';
			$fullValidator = '/^(?:(@(?:' . $shortcuts . '))|' .
				'(?#minute)((?:' . $minuteStar . '|' . $minuteRegex . ')' . $secondsPart . '(?:\,(?:' . $minuteStar . '|' . $minuteRegex . ')' . $secondsPart . ')*)[\s]+' .
				'(?#hour)((?:' . $hourStar . '|' . $hourRegex . ')(?:\,(?:' . $hourStar . '|' . $hourRegex . '))*)[\s]+' .
				'(?#DoM)(\?|(?:(?:' . $domStar . '|' . $domRegex . ')(?:,(?:' . $domStar . '|' . $domRegex . '))*))[\s]+' .
				'(?#month)((?:' . $monthStar . '|' . $monthRegex . ')(?:\,(?:' . $monthStar . '|' . $monthRegex . '))*)[\s]+' .
				'(?#DoW)(\?|(?:' . $dowStar . '|' . $dowRegex . ')(?:\,(?:' . $dowStar . '|' . $dowRegex . '))*)' .
				'(?#year)(?:[\s]+' .
					'((?:' . $yearStar . '|' . $yearRegex . ')(?:\,(?:' . $yearStar . '|' . $yearRegex . '))*)' .
				')?' .
			')$/iu';

			$minuteValidator = '/^(\*|' . $minute . ')(?:\-(' . $minute . '))?(?:\/(' . $minute . '))?(?::(\*|' . $minute . ')(?:\-(' . $minute . '))?(?:\/(' . $minute . '))?)?$/iu';
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
			if (!preg_match($fullValidator, $resolved, $matches)) {
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
				$resolved = self::$_intervals[$key];
			}
		} while ($matches[1]);
		$this->_resolved = $resolved;

		$this->_attr[self::MINUTE] = [];
		foreach (explode(',', $matches[2]) as $match) {
			if (preg_match($minuteValidator, $match, $m2)) {
				if ($m2[1] === '*') {
					$data = ['min' => 0, 'end' => 59];
				} else {
					$data = ['min' => $m2[1]];
					$data['end'] = (($m2[2] ?? '') !== '') ? $m2[2] : $data['min'];
				}
				$data['period'] = (int) max($m2[3] ?? 1, 1);
				if ((($m2[2] ?? '') === '') && ($m2[3] ?? 0)) {
					$data['end'] = 59; //No end with period
				}
				$data['sec'] = null;
				if (($m2[4] ?? '') !== '') {
					$sec = ['sec' => ($m2[4] === '*') ? 0 : (int) $m2[4]];
					$sec['end'] = ($m2[4] === '*') ? 59 : (int) ((($m2[5] ?? '') !== '') ? $m2[5] : $m2[4]);
					$sec['period'] = (int) max($m2[6] ?? 1, 1);
					if ((($m2[5] ?? '') === '') && ($m2[6] ?? 0)) {
						$sec['end'] = 59; //No end with period
					}
					$data['sec'] = $sec;
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

	/**
	 * Replaces each hashed token ('H', '#', 'H(a-b)', 'H/s', 'H(a-b)/s') of the minute, hour, day of
	 * month, month, day of week, and second positions with its value for the {@see getHashContext
	 * HashContext}.  The year field is never hashed.
	 * @param string $schedule the expression
	 * @throws TInvalidDataValueException when a hashed range is empty
	 * @return string the expression with concrete values
	 * @since 4.4.0
	 */
	protected function resolveHashedFields(string $schedule): string
	{
		if (!preg_match('/[#H]/i', $schedule)) {
			return $schedule;
		}
		$names = [self::MINUTE => 'minute', self::HOUR => 'hour', self::DAY_OF_MONTH => 'dom', self::MONTH_OF_YEAR => 'month', self::DAY_OF_WEEK => 'dow'];
		$fields = preg_split('/\s+/', $schedule);
		foreach ($fields as $f => &$field) {
			if (!isset(self::$_hashRanges[$f])) {
				continue;
			}
			$elements = explode(',', $field);
			foreach ($elements as $e => &$element) {
				$parts = ($f === self::MINUTE) ? explode(':', $element, 2) : [$element];
				foreach ($parts as $i => &$part) {
					[$min, $max] = ($i === 1) ? [0, 59] : self::$_hashRanges[$f];
					$part = $this->resolveHashedToken($part, $names[$f] . (($i === 1) ? ':second' : '') . (($e > 0) ? '.' . $e : ''), $min, $max);
				}
				unset($part);
				$element = implode(':', $parts);
			}
			unset($element);
			$field = implode(',', $elements);
		}
		return implode(' ', $fields);
	}

	/**
	 * Resolves one hashed token to `min + crc32(context . "\0" . name) % (max - min + 1)`; a token that
	 * is not hashed is returned as is.  'H(a-b)' narrows the range and 'H/s' narrows it to the first
	 * step so the value is the offset of an every-s sequence.
	 * @param string $token the field element
	 * @param string $name the field name the hash is salted with
	 * @param int $min the lowest value of the field
	 * @param int $max the highest value of the field
	 * @throws TInvalidDataValueException when the hashed range is empty
	 * @return string the element with the hashed value
	 * @since 4.4.0
	 */
	protected function resolveHashedToken(string $token, string $name, int $min, int $max): string
	{
		if (!preg_match('/^[#H](?:\((\d+)-(\d+)\))?(?:\/([1-9]\d*))?$/i', $token, $m)) {
			return $token;
		}
		$this->_hasHash = true;
		$lo = (($m[1] ?? '') !== '') ? max($min, (int) $m[1]) : $min;
		$hi = (($m[2] ?? '') !== '') ? min($max, (int) $m[2]) : $max;
		$step = (int) ($m[3] ?? 0);
		if ($step > 0) {
			$hi = min($hi, $lo + $step - 1);
		}
		if ($hi < $lo) {
			throw new TInvalidDataValueException('timescheduler_invalid_string', $token);
		}
		$value = $lo + (crc32($this->_hashContext . "\0" . $name) % ($hi - $lo + 1));
		return ($step > 0) ? $value . '/' . $step : (string) $value;
	}

	/**
	 * Parses 'every <interval> [after end] [from <time>] [until <time>]' into the period and stores the
	 * schedule with the anchor resolved: 'every <interval>[ after end] from @<unix time>[ until @<unix time>]'.
	 * @param string $text the interval, a relative string of {@see \DateInterval::createFromDateString}
	 * @param ?string $from the anchor, '@<unix time>' or a {@see \strtotime} string; null for now
	 * @param ?string $until the last trigger time, '@<unix time>' or a {@see \strtotime} string
	 * @param bool $afterEnd whether each trigger counts from the time before it instead of the anchor's grid
	 * @throws TInvalidDataValueException when the interval does not parse or is not positive, or a time does not parse
	 * @since 4.4.0
	 */
	protected function parsePeriod(string $text, ?string $from, ?string $until, bool $afterEnd = false)
	{
		$text = preg_replace('/\s+/', ' ', trim($text));
		try {
			$interval = @\DateInterval::createFromDateString($text); // false and a warning before PHP 8.3
		} catch (\Throwable $e) { // @phpstan-ignore catch.neverThrown (DateMalformedIntervalStringException since PHP 8.3)
			$interval = false;
		}
		if ($interval === false) {
			throw new TInvalidDataValueException('timescheduler_invalid_period', $text);
		}
		$anchor = $this->parsePeriodTime($from) ?? $this->time();
		$last = $this->parsePeriodTime($until);
		$tz = $this->getDateTimeZone();
		$start = (new \DateTimeImmutable('@' . $anchor))->setTimezone($tz);
		if ($start->add($interval) <= $start) {
			throw new TInvalidDataValueException('timescheduler_invalid_period', $text);
		}
		// A relative interval ('first monday of next month', 'next weekday') carries more than its y/m/d/h/i/s,
		// so one step of it differs from one step of the plain copy; it is stepped cumulatively, never scaled.
		// The probe is in UTC, where a daylight-saving change cannot make a plain interval look relative.
		$probe = new \DateTimeImmutable('2026-01-15 00:00:00', new \DateTimeZone('UTC'));
		$relative = $probe->add($interval) != $probe->add($this->scaleInterval($interval, 1));
		$fixed = ($interval->y === 0 && $interval->m === 0 && $interval->d === 0 && !$relative)
			? $interval->h * 3600 + $interval->i * 60 + $interval->s : null;
		$this->_period = ['interval' => $interval, 'anchor' => $anchor, 'until' => $last, 'seconds' => $fixed, 'relative' => $relative, 'afterEnd' => $afterEnd];
		$this->_schedule = 'every ' . $text . ($afterEnd ? ' after end' : '') . ' from @' . $anchor . (($last !== null) ? ' until @' . $last : '');
	}

	/**
	 * @param ?string $value '@<unix time>' or a {@see \strtotime} string, relative to {@see time}
	 * @throws TInvalidDataValueException when the string does not parse
	 * @return ?int the unix time, or null for an absent value
	 * @since 4.4.0
	 */
	protected function parsePeriodTime(?string $value): ?int
	{
		if ($value === null || $value === '') {
			return null;
		}
		if (preg_match('/^@(\d+)$/', $value, $m)) {
			return (int) $m[1];
		}
		$time = (str_starts_with($value, '@')) ? false : $this->parseTime($value);
		if ($time === false) {
			throw new TInvalidDataValueException('timescheduler_invalid_period', $value);
		}
		return $time;
	}

	/**
	 * The next trigger of a period strictly after a time: the anchor, or the first multiple of the
	 * interval from the anchor past the time, bounded by 'until'.  A fixed interval is integer
	 * arithmetic; a calendar interval is the anchor plus k times the interval (a relative interval is
	 * stepped cumulatively, as it cannot be scaled).
	 * @param int $priortime the time to search after
	 * @return ?int the next trigger, or null past 'until'
	 * @since 4.4.0
	 */
	protected function getNextPeriodTime(int $priortime): ?int
	{
		$period = $this->_period;
		if ($priortime < $period['anchor']) {
			$next = $period['anchor'];
		} elseif ($period['afterEnd']) {
			$next = ($period['seconds'] !== null) ? $priortime + $period['seconds']
				: (new \DateTimeImmutable('@' . $priortime))->setTimezone($this->getDateTimeZone())->add($period['interval'])->getTimestamp();
		} elseif ($period['seconds'] !== null) {
			$next = $period['anchor'] + (intdiv($priortime - $period['anchor'], $period['seconds']) + 1) * $period['seconds'];
		} else {
			$tz = $this->getDateTimeZone();
			$anchor = (new \DateTimeImmutable('@' . $period['anchor']))->setTimezone($tz);
			$interval = $period['interval'];
			if ($period['relative']) {
				$dt = $anchor;
				while ($dt->getTimestamp() <= $priortime) {
					$dt = $dt->add($interval);
				}
			} else {
				$approx = ($interval->y * 365 + $interval->m * 28 + $interval->d) * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
				$k = max(1, intdiv($priortime - $period['anchor'], max($approx, 1)) - 1);
				do {
					$dt = $anchor->add($this->scaleInterval($interval, $k++));
				} while ($dt->getTimestamp() <= $priortime);
			}
			$next = $dt->getTimestamp();
		}
		if ($period['until'] !== null && $next > $period['until']) {
			return null;
		}
		return $next;
	}

	/**
	 * @param \DateInterval $interval a plain interval
	 * @param int $k the multiple
	 * @return \DateInterval the interval times $k
	 * @since 4.4.0
	 */
	protected function scaleInterval(\DateInterval $interval, int $k): \DateInterval
	{
		$scaled = new \DateInterval('PT0S');
		$scaled->y = $interval->y * $k;
		$scaled->m = $interval->m * $k;
		$scaled->d = $interval->d * $k;
		$scaled->h = $interval->h * $k;
		$scaled->i = $interval->i * $k;
		$scaled->s = $interval->s * $k;
		return $scaled;
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
		$sa = [];
		foreach ($this->_attr[self::MINUTE] as $m) {
			if (is_numeric($m['min'])) {
				$seconds = [0];
				if ($m['sec'] !== null) {
					$seconds = [];
					for ($s = $m['sec']['sec']; $s <= $m['sec']['end'] && $s < 60; $s += $m['sec']['period']) {
						$seconds[] = $s;
					}
				}
				for ($i = $m['min']; $i <= $m['end'] && $i < 60; $i += $m['period']) {
					$ma[$i] = 1;
					$sa[$i] = array_merge($sa[$i] ?? [], $seconds);
				}
			}
		}
		foreach ($sa as &$seconds) {
			$seconds = array_values(array_unique($seconds));
			sort($seconds);
		}
		unset($seconds);
		$this->_secondsCache = $sa;
		return $this->_minutesCache = $ma;
	}

	/**
	 * @return array the sorted seconds that trigger in each valid minute, keyed by minute; [0] for a
	 *   minute element without a seconds part
	 * @since 4.4.0
	 */
	protected function getSecondsArray()
	{
		if ($this->_secondsCache === null) {
			$this->getMinutesArray();
		}
		return $this->_secondsCache;
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
	 *   in the {@see getTimeZone TimeZone}.
	 */
	public function getNextTriggerTime($priortime = false)
	{
		if ($priortime === null || $this->_schedule === null) {
			return null;
		}
		if ($priortime === false) {
			$priortime = $this->time();
		} elseif (!is_numeric($priortime)) {
			$priortime = $this->parseTime($priortime);
			if ($priortime === false) {
				return null;
			}
		}
		$next = $this->getNextScheduledTime((int) $priortime);
		for ($i = 0; $next !== null && $this->_except !== null && ($until = $this->getExcludedUntil($next)) !== null; $i++) {
			if ($i >= static::EXCEPT_LIMIT) {
				return null;
			}
			$next = $this->getNextScheduledTime($until);
		}
		return $next;
	}

	/**
	 * Calculates the next trigger time strictly after a time, without the 'except' clause.
	 * @param int $priortime the time to search after
	 * @return ?int the Unix timestamp of the next trigger, or null
	 * @since 4.4.0
	 */
	protected function getNextScheduledTime(int $priortime): ?int
	{
		if ($this->_triggerTime !== null) {
			if ($priortime >= $this->_triggerTime) {
				return null;
			}
			return $this->_triggerTime;
		}
		if ($this->_period !== null) {
			return $this->getNextPeriodTime($priortime);
		}

		$minutes = $this->getMinutesArray();
		$seconds = $this->getSecondsArray();
		$hours = $this->getHoursArray();
		$months = $this->getMonthsArray();

		// Search strictly after $priortime: from the next second, the instant is advanced field-by-field
		// from coarsest (year) to finest (second); each carry resets the finer fields, and
		// DateTimeImmutable handles month length and overflow, so the (year, month, day) tuple can never
		// desync. A minute element without seconds triggers at second 0, so a schedule without seconds
		// keeps minute resolution. The loop is bounded by YEAR_MAX.
		$tz = $this->getDateTimeZone();
		$dt = (new \DateTimeImmutable('@' . ($priortime + 1)))->setTimezone($tz);

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
			$second = (int) $dt->format('s');
			foreach ($seconds[$minute] as $s) {
				if ($s >= $second) {
					return $dt->setTime($hour, $minute, $s)->getTimestamp();
				}
			}
			$dt = $dt->setTime($hour, $minute + 1, 0); // no second left in this minute
		}
		return null;
	}

	/**
	 * @return bool whether the schedule is a period 'every <interval> after end', whose trigger counts from
	 *   the time before it; the cron module sets that time to the end of each run
	 * @since 4.4.0
	 */
	public function getIsAfterEnd(): bool
	{
		return (bool) ($this->_period['afterEnd'] ?? false);
	}

	/**
	 * Parses the dates of an 'except' clause.
	 * @param string $text a comma list of 'YYYY-MM-DD', 'YYYY-MM-DD..YYYY-MM-DD', 'MM-DD', or 'MM-DD..MM-DD'
	 * @throws TInvalidDataValueException when an item is not a date or a range of dates of one kind
	 * @return array{0: array[], 1: string} the items, each [yearly, from, to], and the normalized text
	 * @since 4.4.0
	 */
	protected function parseExcept(string $text): array
	{
		$items = [];
		$texts = [];
		foreach (explode(',', $text) as $item) {
			$item = trim($item);
			if (preg_match('/^(\d{4}-\d{2}-\d{2})(?:\s*\.\.\s*(\d{4}-\d{2}-\d{2}))?$/', $item, $m)) {
				$yearly = false;
			} elseif (preg_match('/^(\d{2}-\d{2})(?:\s*\.\.\s*(\d{2}-\d{2}))?$/', $item, $m)) {
				$yearly = true;
			} else {
				throw new TInvalidDataValueException('timescheduler_invalid_except', $item);
			}
			$from = $m[1];
			$to = ($m[2] ?? '') ?: $from;
			foreach ([$from, $to] as $date) {
				$parts = array_map('intval', explode('-', $yearly ? '2000-' . $date : $date));
				if (!checkdate($parts[1], $parts[2], $parts[0])) {
					throw new TInvalidDataValueException('timescheduler_invalid_except', $item);
				}
			}
			if (!$yearly && $from > $to) {
				throw new TInvalidDataValueException('timescheduler_invalid_except', $item);
			}
			$items[] = [$yearly, $from, $to];
			$texts[] = ($from === $to) ? $from : $from . '..' . $to;
		}
		return [$items, implode(', ', $texts)];
	}

	/**
	 * Returns the end of the excluded dates that hold a time: the last second of the excluded span's
	 * last local day.
	 * @param int $time the time
	 * @return ?int the last second of the exclusion, or null when the time is not excluded
	 * @since 4.4.0
	 */
	protected function getExcludedUntil(int $time): ?int
	{
		$tz = $this->getDateTimeZone();
		$local = (new \DateTimeImmutable('@' . $time))->setTimezone($tz);
		$date = $local->format('Y-m-d');
		$day = $local->format('m-d');
		$year = (int) $local->format('Y');
		$end = null;
		foreach ($this->_except ?? [] as [$yearly, $from, $to]) {
			if (!$yearly) {
				$last = ($date >= $from && $date <= $to) ? $to : null;
			} elseif ($from <= $to) {
				$last = ($day >= $from && $day <= $to) ? sprintf('%04d-%s', $year, $to) : null;
			} elseif ($day >= $from) {
				$last = sprintf('%04d-%s', $year + 1, $to);
			} else {
				$last = ($day <= $to) ? sprintf('%04d-%s', $year, $to) : null;
			}
			if ($last !== null) {
				$until = (new \DateTimeImmutable($last . ' 23:59:59', $tz))->getTimestamp();
				$end = max($end ?? $until, $until);
			}
		}
		return $end;
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
