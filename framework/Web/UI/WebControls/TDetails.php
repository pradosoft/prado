<?php

/**
 * TDetails class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TDetails class
 *
 * TDetails represents the HTML5 `<details>` element. The `<details>` element
 * represents a disclosure widget from which the user can obtain additional
 * information or controls. Its content model is a `<summary>` element followed
 * by flow content.
 *
 * Properties:
 * - <b>Summary</b>, string — text used to auto-generate a `<summary>` child
 *   rendered before all other content. Ignored when a {@see TSummary} child
 *   control is present. Empty string means no auto-generated summary.
 * - <b>Open</b>, bool — whether the disclosure widget is expanded. When `true`,
 *   the `open` attribute is rendered. Defaults to `false`.
 * - <b>Group</b>, string — renders the `name` attribute, making the details
 *   part of an exclusive accordion: opening one `<details>` in a group closes
 *   the others with the same name. Empty string renders no attribute.
 * - <b>Encode</b>, bool — whether the Summary text is HTML-encoded when
 *   rendered. Defaults to `false`.
 *
 * Template usage:
 * ```html
 * <com:TDetails Summary="More information">
 *     <p>Additional content revealed on expand.</p>
 * </com:TDetails>
 *
 * <com:TDetails Group="faq" Open="true">
 *     <com:TSummary>Question <b>one</b></com:TSummary>
 *     <p>Answer one.</p>
 * </com:TDetails>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDetails extends TWebControl
{
	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'details';
	}

	/**
	 * @return string the text used to auto-generate a `<summary>` child element.
	 *   Empty string means no auto-generated summary.
	 */
	public function getSummary()
	{
		return $this->getViewState('Summary', '');
	}

	/**
	 * Sets the summary text. When non-empty and no {@see TSummary} child control
	 * is present, a `<summary>` is rendered automatically before all other content.
	 * @param string $value the summary text
	 */
	public function setSummary($value)
	{
		$this->setViewState('Summary', $value, '');
	}

	/**
	 * @return bool whether the Summary text is HTML-encoded when rendered. Defaults to false.
	 */
	public function getEncode()
	{
		return $this->getViewState('Encode', false);
	}

	/**
	 * @param bool $value whether the Summary text is HTML-encoded when rendered
	 */
	public function setEncode($value)
	{
		$this->setViewState('Encode', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether the details is open
	 */
	public function getOpen()
	{
		return $this->getViewState('Open', false);
	}

	/**
	 * Sets whether the details is open.
	 * @param bool $value whether the details is open
	 */
	public function setOpen($value)
	{
		$this->setViewState('Open', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string the exclusive accordion group, rendered as the `name` attribute.
	 *   Empty string renders no attribute.
	 */
	public function getGroup()
	{
		return $this->getViewState('Group', '');
	}

	/**
	 * Sets the exclusive accordion group, rendered as the `name` attribute.
	 * Opening one `<details>` in a group closes the others with the same name.
	 * @param string $value the group name
	 */
	public function setGroup($value)
	{
		$this->setViewState('Group', $value, '');
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if ($this->getOpen()) {
			$writer->addAttribute('open', 'open');
		}
		if (($group = $this->getGroup()) !== '') {
			$writer->addAttribute('name', $group);
		}
	}

	/**
	 * Renders the body content of the details element.
	 *
	 * If a direct {@see TSummary} child control is present, all children render
	 * in template order and the child provides the `<summary>`. Otherwise, when
	 * {@see getSummary Summary} is non-empty, an auto-generated `<summary>` is
	 * rendered before all children.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if (!$this->hasSummaryChild() && ($summary = $this->getSummary()) !== '') {
			$this->renderSummary($writer, $summary);
		}
		parent::renderContents($writer);
	}

	/**
	 * @return bool whether any direct child is a {@see TSummary} control
	 */
	protected function hasSummaryChild(): bool
	{
		if ($this->getHasControls()) {
			foreach ($this->getControls() as $child) {
				if ($child instanceof TSummary) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Renders the auto-generated `<summary>` element from the Summary property.
	 * The text is HTML-encoded when {@see getEncode Encode} is true.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @param string $summary the summary text
	 */
	protected function renderSummary($writer, string $summary)
	{
		$writer->renderBeginTag('summary');
		$writer->write($this->getEncode() ? THttpUtility::htmlEncode($summary) : $summary);
		$writer->renderEndTag();
	}
}
