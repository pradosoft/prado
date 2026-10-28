<?php

/**
 * TBaseEdit class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use DateTimeImmutable;
use DateTimeInterface;
use Prado\TPropertyValue;

/**
 * TBaseEdit class
 * TBaseEdit is the base class of the HTML edit elements, {@see TIns} and
 * {@see TDel}, which mark text added to or removed from a document.
 * {@see TCiteTrait::setCite() Cite} is the URL of the explanation of the edit,
 * and {@see setDateTime() DateTime} is when the edit was made.
 * ```html
 * <com:TDel DateTime="2026-10-01">$40</com:TDel> <com:TIns DateTime="2026-10-01">$30</com:TIns>
 * ```
 * An edit element in running text holds phrasing content. Holding whole
 * paragraphs or list items, it wraps them.
 * Accessibility: browsers expose `<ins>` with the `insertion` role and `<del>` with
 * the `deletion` role. Most screen readers do not announce them by default, and
 * none reads `cite` or `datetime`. An edit that readers need to know about says
 * so in its text or in a visually hidden span, such as "removed:" before the
 * deleted text. Color alone does not mark an edit; browsers also strike through
 * deleted text and underline inserted text.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TBaseEdit extends THtmlElement
{
	use TCiteTrait;

	/**
	 * @return string the date and time of the edit as rendered in the `datetime`
	 *   attribute. Defaults to ''.
	 */
	public function getDateTime()
	{
		return $this->getViewState('DateTime', '');
	}

	/**
	 * Sets the date and time of the edit.
	 * - `DateTimeInterface` → formatted as `Y-m-d\TH:i:sP`.
	 * - int, float, or numeric string → a Unix timestamp, formatted in UTC.
	 * - other string → rendered as given; it is a valid HTML date string, with an
	 *   optional time.
	 * @param DateTimeInterface|float|int|string $value the date and time of the edit.
	 */
	public function setDateTime($value)
	{
		if ($value instanceof DateTimeInterface) {
			$value = $value->format(DateTimeInterface::ATOM);
		} elseif (is_numeric($value)) {
			$value = (new DateTimeImmutable('@' . (int) $value))->format(DateTimeInterface::ATOM);
		} else {
			$value = trim(TPropertyValue::ensureString($value));
		}
		$this->setViewState('DateTime', $value, '');
	}

	/**
	 * Adds the `cite` and `datetime` attributes when they are set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$this->addCiteAttributeToRender($writer);
		if (($dateTime = $this->getDateTime()) !== '') {
			$writer->addAttribute('datetime', $dateTime);
		}
	}
}
