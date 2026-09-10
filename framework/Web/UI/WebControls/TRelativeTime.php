<?php

/**
 * TRelativeTime class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Prado\I18N\core\CultureInfo;
use Prado\I18N\core\CultureInfoUnits;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TRelativeTime class
 *
 * TRelativeTime renders a live, self-updating relative time inside an HTML5 `<time>`
 * element, for example "5 minutes ago" or "in 3 hours". It extends {@see TTime}, so it
 * accepts the same `DateTime` inputs, emits a machine-readable `datetime` attribute, and
 * reuses the same localized absolute formatting. Client-side JavaScript keeps the visible
 * text current as time passes and, when {@see getClickForDateTime ClickForDateTime} is
 * enabled, toggles between the relative text and the absolute date on click.
 *
 * The `DateTime` value is the origin instant the relative text counts from. A
 * `DateTimeInterface` is used directly; a `DateInterval` is interpreted as "now minus the
 * interval"; an unset or unparsable value defaults to the current time.
 *
 * **Internationalization.** The visible relative text is composed on the client from
 * localized CLDR duration-unit patterns supplied by the server. The server reads the
 * plural patterns for the control's culture at the {@see getMode Mode} width through
 * {@see \Prado\I18N\core\CultureInfo::getUnitPatterns()}; the client selects the correct
 * plural category with `Intl.PluralRules` and formats the number with `Intl.NumberFormat`.
 * Direction ("ago" / "in") is applied from `Intl.RelativeTimeFormat` when the browser
 * provides it.
 *
 * **Initial content.** The server renders the relative text from the same CLDR data
 * (see {@see \Prado\I18N\core\CultureInfo::getRelativeTimePatterns()}), so the first
 * client render shows no change and a page without JavaScript shows a snapshot of the
 * render time. The click target and the default `title` tooltip are the localized
 * absolute date; a developer-set `ToolTip` replaces the default.
 *
 * **Inherited properties.** `TextFormat` selects the format of the absolute date shown on
 * click and in the tooltip; it defaults to `DateTimeFormat`. The child-text resolution of
 * {@see TTime} does not apply, since the visible text is the relative time.
 *
 * Properties:
 * - <b>Mode</b>, {@see TRelativeTimeMode} — unit width (`Long`, `Short`, `Narrow`).
 *   Default: `Long`.
 * - <b>SignificantElements</b>, int — number of duration units shown. Default: `1`.
 * - <b>PartialElement</b>, bool — show the next smaller unit when the current unit is close
 *   to changing. Default: `true`.
 * - <b>DisplayZero</b>, bool — include units with a zero value. Default: `false`.
 * - <b>Separator</b>, string — text placed between units. Default: `' '`.
 * - <b>UseServerTime</b>, bool — count from the server clock rather than the client clock.
 *   Default: `true`.
 * - <b>ClickForDateTime</b>, bool — toggle to the absolute date on click. Default: `true`.
 * - <b>DurationOnly</b>, bool — render the bare localized duration (`5 minutes`) without the
 *   "ago" / "in" phrasing, for contexts that supply their own direction such as a countdown
 *   label or a column heading. Default: `false`.
 * - <b>YearsWithMonths</b> / <b>MonthsWithWeeks</b> / <b>WeeksWithDays</b> /
 *   <b>DaysWithHours</b> / <b>HoursWithMinutes</b> / <b>MinutesWithSeconds</b>, int — the
 *   partial-element thresholds; when `PartialElement` is enabled and the leading unit's
 *   value is at or below its threshold, the next smaller unit is also shown.
 *
 * **Template examples:**
 * ```xml
 * <!-- "5 minutes ago", updating live, toggling to the date on click -->
 * <com:TRelativeTime DateTime="2024-06-15 10:30:00" />
 *
 * <!-- Narrow units, two components, no click toggle -->
 * <com:TRelativeTime DateTime="1718445000" Mode="Narrow"
 *     SignificantElements="2" ClickForDateTime="false" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see TTime
 * @see TRelativeTimeMode
 * @see \Prado\I18N\core\CultureInfo::getUnitPatterns()
 * @since 4.4.0
 */
class TRelativeTime extends TTime
{
	/**
	 * Duration unit types, ordered largest first, keyed by the identifier used both in the
	 * client `UnitPatterns` map and the JavaScript timing table.
	 * @var array<string, string>
	 */
	private const UNIT_TYPES = [
		'year' => CultureInfoUnits::TYPE_DURATION_YEAR,
		'month' => CultureInfoUnits::TYPE_DURATION_MONTH,
		'week' => CultureInfoUnits::TYPE_DURATION_WEEK,
		'day' => CultureInfoUnits::TYPE_DURATION_DAY,
		'hour' => CultureInfoUnits::TYPE_DURATION_HOUR,
		'minute' => CultureInfoUnits::TYPE_DURATION_MINUTE,
		'second' => CultureInfoUnits::TYPE_DURATION_SECOND,
	];

	/**
	 * Duration unit lengths in seconds, in {@see UNIT_TYPES} order. Year and month use
	 * mean astronomical lengths, matching the client script.
	 * @var array<string, float|int>
	 */
	private const UNIT_SECONDS = [
		'year' => 86400 * 365.2421896698,
		'month' => 2629743.7656,
		'week' => 604800,
		'day' => 86400,
		'hour' => 3600,
		'minute' => 60,
		'second' => 1,
	];

	/**
	 * Resolves the origin instant the relative text counts from.
	 *
	 * A `DateTimeInterface` is returned as-is. A `DateInterval` resolves to the current
	 * time minus the interval. An unset or unparsable value resolves to the current time.
	 *
	 * @return DateTimeInterface the origin instant
	 */
	protected function getOrigin(): DateTimeInterface
	{
		$dateTime = $this->getDateTime();
		if ($dateTime instanceof DateTimeInterface) {
			return $dateTime;
		}
		if ($dateTime instanceof DateInterval) {
			return (new DateTimeImmutable())->sub($dateTime);
		}
		return new DateTimeImmutable();
	}

	/**
	 * Returns the origin instant as a Unix timestamp for the client script.
	 * @return int Unix timestamp of the origin instant
	 */
	protected function getOriginTimestamp(): int
	{
		return $this->getOrigin()->getTimestamp();
	}

	/**
	 * Returns whether the live client-side behavior applies. It requires an attached page
	 * whose client supports JavaScript and an enabled control.
	 * @return bool whether the client script is registered and the element is enhanced
	 */
	protected function getClientEnhanced(): bool
	{
		$page = $this->getPage();
		return $page !== null && $page->getClientSupportsJavaScript() && $this->getEnabled(true);
	}

	/**
	 * Adds the `datetime` attribute, a default `title` of the absolute date when no
	 * `ToolTip` is set, and, when the client is enhanced, the element `id` used by the
	 * client script.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if ($this->getDateTime() === '') {
			$writer->addAttribute('datetime', $this->formatValue($this->getOrigin()));
		}
		if ($this->getToolTip() === '') {
			$writer->addAttribute('title', $this->getAbsoluteText());
		}
		if ($this->getClientEnhanced()) {
			$writer->addAttribute('id', $this->getClientID());
		}
	}

	/**
	 * Renders the localized relative text as the initial content. It matches the client
	 * script's first render, so an enhanced page shows no change on load; without
	 * JavaScript it remains as a snapshot of the render time.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		$writer->write($this->getRelativeText());
	}

	/**
	 * Composes the localized relative text for the origin instant as of now, with the
	 * same rules as the client script.
	 *
	 * Each displayed unit is formatted from {@see collectUnitPatterns} with the plural
	 * category chosen by {@see CultureInfo::selectPluralCategory()}. A single unit renders
	 * through the CLDR relative-time pattern for its direction, which carries any
	 * inflection the language applies. A multi-unit magnitude is spliced into the leading
	 * unit's relative-time pattern; when the leading rendering cannot be located, the bare
	 * magnitude is returned. A zero delta renders as zero seconds without a direction.
	 * When {@see getDurationOnly DurationOnly} is set, the magnitude is returned without a
	 * direction.
	 *
	 * @return string localized relative text, e.g. `5 minutes ago`
	 */
	protected function getRelativeText(): string
	{
		$cultureInfo = $this->getCultureInfo();
		$unitPatterns = $this->collectUnitPatterns();
		$thresholds = $this->getPartialThresholds();

		$delta = time() - $this->getOriginTimestamp();
		$isFuture = $delta < 0;
		$remaining = abs($delta);

		$parts = [];
		$shown = 0;
		$leadingUnit = null;
		$leadingValue = 0;
		$importantNext = false;
		$index = 0;
		foreach (self::UNIT_SECONDS as $unit => $seconds) {
			$num = (int) floor($remaining / $seconds);
			if ($num !== 0 && $leadingUnit === null) {
				$leadingUnit = $unit;
			}
			if ($leadingUnit !== null && ($shown < $this->getSignificantElements()
				|| ($this->getPartialElement() && $shown === 1 && $importantNext))) {
				if ($this->getDisplayZero() || $num !== 0) {
					$parts[] = $this->formatUnitCount($cultureInfo, $unitPatterns[$unit], $num);
					$remaining -= $num * $seconds;
					if ($shown === 0) {
						$leadingValue = $num;
					}
				}
				$shown++;
				$importantNext = ($num <= $thresholds[$index]);
			}
			$index++;
		}

		if ($leadingUnit === null) {
			return $this->formatUnitCount($cultureInfo, $unitPatterns['second'], 0);
		}

		$magnitude = implode($this->getSeparator(), $parts);
		if ($this->getDurationOnly()) {
			return $magnitude;
		}
		$direction = $isFuture ? CultureInfoUnits::RELATIVE_FUTURE : CultureInfoUnits::RELATIVE_PAST;
		$patterns = $cultureInfo->getRelativeTimePatterns(self::UNIT_TYPES[$leadingUnit], $this->getUnitWidth())[$direction] ?? [];
		$pattern = $patterns[$cultureInfo->selectPluralCategory($leadingValue)] ?? $patterns[CultureInfoUnits::UNIT_OTHER_PATTERN] ?? null;
		if ($pattern === null) {
			return $magnitude;
		}

		$relative = str_replace('{0}', $cultureInfo->formatNumber($leadingValue), $pattern);
		if (count($parts) === 1) {
			return $relative;
		}
		$at = strpos($relative, $parts[0]);
		return ($at === false) ? $magnitude : substr_replace($relative, $magnitude, $at, strlen($parts[0]));
	}

	/**
	 * Formats a unit count with the plural pattern for its category in this culture.
	 * @param CultureInfo $cultureInfo the culture supplying plural rules and number format
	 * @param array<string, string> $patterns plural-category patterns for the unit
	 * @param int $count the unit count
	 * @return string the localized count, e.g. `5 minutes`
	 */
	private function formatUnitCount(CultureInfo $cultureInfo, array $patterns, int $count): string
	{
		$pattern = $patterns[$cultureInfo->selectPluralCategory($count)]
			?? $patterns[CultureInfoUnits::UNIT_OTHER_PATTERN]
			?? $patterns[CultureInfoUnits::UNIT_ONE_PATTERN]
			?? '{0}';
		return str_replace('{0}', $cultureInfo->formatNumber($count), $pattern);
	}

	/**
	 * Returns the localized absolute date used for the click target and the default
	 * `title` tooltip. The format is {@see getTextFormat TextFormat} when set, otherwise
	 * {@see getDateTimeFormat DateTimeFormat}.
	 * @return string localized absolute date
	 */
	protected function getAbsoluteText(): string
	{
		return $this->formatTextValue($this->getOrigin(), $this->getTextFormat() ?? $this->getDateTimeFormat());
	}

	/**
	 * Registers the client script when the client supports JavaScript and the control is
	 * enabled.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getClientEnhanced()) {
			$this->registerClientScript();
		}
	}

	/**
	 * Registers the relative-time client script and its instantiation.
	 */
	protected function registerClientScript()
	{
		$options = TJavaScript::encode($this->getClientOptions());
		$className = $this->getClientClassName();
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('relativetime');
		$cs->registerEndScript('prado:' . $this->getClientID(), "new $className($options);");
	}

	/**
	 * Returns the name of the client-side JavaScript class for this control.
	 * @return string the JavaScript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TRelativeTime';
	}

	/**
	 * Builds the options passed to the client script.
	 *
	 * The options carry the origin and server time, the display settings, the localized
	 * absolute text, the culture as a BCP 47 tag, and the localized plural patterns for
	 * each duration unit at the selected {@see getMode Mode} width.
	 *
	 * @return array the client options
	 */
	protected function getClientOptions(): array
	{
		$options = [];
		$options['ID'] = $this->getClientID();
		$options['ServerTime'] = time();
		$options['OriginTime'] = $this->getOriginTimestamp();
		$options['UseServerTime'] = $this->getUseServerTime();
		$options['Separator'] = $this->getSeparator();
		$options['DisplayZero'] = $this->getDisplayZero();
		$options['SignificantElements'] = $this->getSignificantElements();
		$options['PartialElement'] = $this->getPartialElement();
		$options['PartialCount'] = $this->getPartialThresholds();
		$options['ClickForDateTime'] = $this->getClickForDateTime();
		$options['DurationOnly'] = $this->getDurationOnly();
		$options['Culture'] = str_replace('_', '-', $this->getCulture());
		$options['Mode'] = $this->getUnitWidth();
		$options['AbsoluteText'] = $this->getAbsoluteText();
		$options['UnitPatterns'] = $this->collectUnitPatterns();
		return $options;
	}

	/**
	 * Returns the partial-element thresholds in {@see UNIT_TYPES} order, with a fixed
	 * value for seconds. When the leading unit's value is at or below its threshold, the
	 * next smaller unit is also shown.
	 * @return int[] thresholds for year, month, week, day, hour, minute, second
	 */
	protected function getPartialThresholds(): array
	{
		return [
			$this->getYearsWithMonths(),
			$this->getMonthsWithWeeks(),
			$this->getWeeksWithDays(),
			$this->getDaysWithHours(),
			$this->getHoursWithMinutes(),
			$this->getMinutesWithSeconds(),
			5,
		];
	}

	/**
	 * Returns the localized plural patterns for every duration unit at the selected
	 * {@see getMode Mode} width, keyed by unit identifier (`year`, `month`, `week`, `day`,
	 * `hour`, `minute`, `second`).
	 * @return array<string, array<string, string>> per-unit plural-category pattern maps
	 */
	protected function collectUnitPatterns(): array
	{
		$cultureInfo = $this->getCultureInfo();
		$width = $this->getUnitWidth();

		$patterns = [];
		foreach (self::UNIT_TYPES as $name => $unitType) {
			$patterns[$name] = $cultureInfo->getUnitPatterns($unitType, $width);
		}
		return $patterns;
	}

	/**
	 * Maps the {@see getMode Mode} to its {@see CultureInfoUnits} unit width, which is
	 * also the `style` of the client `Intl.RelativeTimeFormat`.
	 * @return string a {@see CultureInfoUnits} `WIDTH_*` value
	 */
	protected function getUnitWidth(): string
	{
		return match ($this->getMode()) {
			TRelativeTimeMode::Short => CultureInfoUnits::WIDTH_SHORT,
			TRelativeTimeMode::Narrow => CultureInfoUnits::WIDTH_NARROW,
			default => CultureInfoUnits::WIDTH_LONG,
		};
	}

	/**
	 * Returns the unit width mode.
	 * @return string a {@see TRelativeTimeMode} value; default `Long`
	 */
	public function getMode()
	{
		return $this->getViewState('Mode', TRelativeTimeMode::Long);
	}

	/**
	 * Sets the unit width mode.
	 * @param string $value a {@see TRelativeTimeMode} value
	 */
	public function setMode($value)
	{
		$this->setViewState('Mode', TPropertyValue::ensureEnum($value, TRelativeTimeMode::class), TRelativeTimeMode::Long);
	}

	/**
	 * Returns whether clicking toggles between the relative time and the absolute date.
	 * @return bool whether clicking toggles the display; default `true`
	 */
	public function getClickForDateTime()
	{
		return $this->getViewState('ClickForDateTime', true);
	}

	/**
	 * Sets whether clicking toggles between the relative time and the absolute date.
	 * @param bool $value whether clicking toggles the display
	 */
	public function setClickForDateTime($value)
	{
		$this->setViewState('ClickForDateTime', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Returns whether the bare duration is rendered without the "ago" / "in" phrasing.
	 * @return bool whether only the duration is rendered; default `false`
	 */
	public function getDurationOnly()
	{
		return $this->getViewState('DurationOnly', false);
	}

	/**
	 * Sets whether the bare duration is rendered without the "ago" / "in" phrasing. Use
	 * this when the surrounding text supplies the direction, for example a countdown label
	 * or a column heading.
	 * @param bool $value whether only the duration is rendered
	 */
	public function setDurationOnly($value)
	{
		$this->setViewState('DurationOnly', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Returns whether the relative time counts from the server clock.
	 * @return bool `true` for the server clock, `false` for the client clock; default `true`
	 */
	public function getUseServerTime()
	{
		return $this->getViewState('UseServerTime', true);
	}

	/**
	 * Sets whether the relative time counts from the server clock.
	 * @param bool $value `true` for the server clock, `false` for the client clock
	 */
	public function setUseServerTime($value)
	{
		$this->setViewState('UseServerTime', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Returns the text placed between displayed units.
	 * @return string the separator; default a single space
	 */
	public function getSeparator()
	{
		return $this->getViewState('Separator', ' ');
	}

	/**
	 * Sets the text placed between displayed units.
	 * @param string $value the separator
	 */
	public function setSeparator($value)
	{
		$this->setViewState('Separator', TPropertyValue::ensureString($value), ' ');
	}

	/**
	 * Returns whether units with a zero value are displayed.
	 * @return bool whether zero-value units are displayed; default `false`
	 */
	public function getDisplayZero()
	{
		return $this->getViewState('DisplayZero', false);
	}

	/**
	 * Sets whether units with a zero value are displayed.
	 * @param bool $value whether zero-value units are displayed
	 */
	public function setDisplayZero($value)
	{
		$this->setViewState('DisplayZero', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Returns the number of duration units displayed.
	 * @return int the number of units; default `1`
	 */
	public function getSignificantElements()
	{
		return $this->getViewState('SignificantElements', 1);
	}

	/**
	 * Sets the number of duration units displayed.
	 * @param int $value the number of units
	 */
	public function setSignificantElements($value)
	{
		$this->setViewState('SignificantElements', TPropertyValue::ensureInteger($value), 1);
	}

	/**
	 * Returns whether the next smaller unit is shown when the leading unit is close to
	 * changing.
	 * @return bool whether the partial next unit is shown; default `true`
	 */
	public function getPartialElement()
	{
		return $this->getViewState('PartialElement', true);
	}

	/**
	 * Sets whether the next smaller unit is shown when the leading unit is close to
	 * changing.
	 * @param bool $value whether the partial next unit is shown
	 */
	public function setPartialElement($value)
	{
		$this->setViewState('PartialElement', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Returns the minute threshold below which seconds are also shown.
	 * @return int number of minutes; default `4`
	 */
	public function getMinutesWithSeconds()
	{
		return $this->getViewState('MinutesWithSeconds', 4);
	}

	/**
	 * Sets the minute threshold below which seconds are also shown.
	 * @param int $value number of minutes
	 */
	public function setMinutesWithSeconds($value)
	{
		$this->setViewState('MinutesWithSeconds', TPropertyValue::ensureInteger($value), 4);
	}

	/**
	 * Returns the hour threshold below which minutes are also shown.
	 * @return int number of hours; default `3`
	 */
	public function getHoursWithMinutes()
	{
		return $this->getViewState('HoursWithMinutes', 3);
	}

	/**
	 * Sets the hour threshold below which minutes are also shown.
	 * @param int $value number of hours
	 */
	public function setHoursWithMinutes($value)
	{
		$this->setViewState('HoursWithMinutes', TPropertyValue::ensureInteger($value), 3);
	}

	/**
	 * Returns the day threshold below which hours are also shown.
	 * @return int number of days; default `3`
	 */
	public function getDaysWithHours()
	{
		return $this->getViewState('DaysWithHours', 3);
	}

	/**
	 * Sets the day threshold below which hours are also shown.
	 * @param int $value number of days
	 */
	public function setDaysWithHours($value)
	{
		$this->setViewState('DaysWithHours', TPropertyValue::ensureInteger($value), 3);
	}

	/**
	 * Returns the week threshold below which days are also shown.
	 * @return int number of weeks; default `2`
	 */
	public function getWeeksWithDays()
	{
		return $this->getViewState('WeeksWithDays', 2);
	}

	/**
	 * Sets the week threshold below which days are also shown.
	 * @param int $value number of weeks
	 */
	public function setWeeksWithDays($value)
	{
		$this->setViewState('WeeksWithDays', TPropertyValue::ensureInteger($value), 2);
	}

	/**
	 * Returns the month threshold below which weeks are also shown.
	 * @return int number of months; default `3`
	 */
	public function getMonthsWithWeeks()
	{
		return $this->getViewState('MonthsWithWeeks', 3);
	}

	/**
	 * Sets the month threshold below which weeks are also shown.
	 * @param int $value number of months
	 */
	public function setMonthsWithWeeks($value)
	{
		$this->setViewState('MonthsWithWeeks', TPropertyValue::ensureInteger($value), 3);
	}

	/**
	 * Returns the year threshold below which months are also shown.
	 * @return int number of years; default `2`
	 */
	public function getYearsWithMonths()
	{
		return $this->getViewState('YearsWithMonths', 2);
	}

	/**
	 * Sets the year threshold below which months are also shown.
	 * @param int $value number of years
	 */
	public function setYearsWithMonths($value)
	{
		$this->setViewState('YearsWithMonths', TPropertyValue::ensureInteger($value), 2);
	}
}
