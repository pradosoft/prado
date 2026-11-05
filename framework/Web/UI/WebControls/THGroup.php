<?php

/**
 * THGroup class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * THGroup class
 * THGroup represents the HTML `<hgroup>` element, a heading grouped with its
 * subtitle or tagline. When {@see setHeaderText() HeaderText} is set, it renders
 * in an `<h1>` through `<h6>` given by {@see setHeaderLevel() HeaderLevel},
 * followed by the child controls. The children are `<p>` elements; without
 * HeaderText, one child is the heading, such as a {@see THeader2}.
 * ```html
 * <com:THGroup HeaderText="Prado" HeaderLevel="1">
 *     <p>Component framework for PHP</p>
 * </com:THGroup>
 * ```
 * {@see setEncode() Encode} HTML-encodes HeaderText.
 * Accessibility: browsers expose `<hgroup>` with the `group` role. The heading
 * alone is in the document outline and the heading list of screen readers; the
 * subtitle is read as ordinary text after it. An `<hgroup>` holds exactly one
 * heading.
 * The default tag is `hgroup`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THGroup extends THtmlElement
{
	use TEncodeTrait;

	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'hgroup';
	}

	/**
	 * @return string the text of the heading. Defaults to ''.
	 */
	public function getHeaderText()
	{
		return $this->getViewState('HeaderText', '');
	}

	/**
	 * @param string $value the text of the heading.
	 */
	public function setHeaderText($value)
	{
		$this->setViewState('HeaderText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return int the level of the heading, 1 through 6. Defaults to 1.
	 */
	public function getHeaderLevel()
	{
		return $this->getViewState('HeaderLevel', 1);
	}

	/**
	 * @param int $value the level of the heading, 1 through 6.
	 * @throws TInvalidDataValueException if the level is not 1 through 6.
	 */
	public function setHeaderLevel($value)
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 1 || $value > 6) {
			throw new TInvalidDataValueException('hgroup_headerlevel_invalid', $value);
		}
		$this->setViewState('HeaderLevel', $value, 1);
	}

	/**
	 * Renders the heading of {@see getHeaderText() HeaderText} when it is set, then
	 * the child controls.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if (($text = $this->getHeaderText()) !== '') {
			$writer->renderBeginTag('h' . $this->getHeaderLevel());
			$writer->write($this->encodeText($text));
			$writer->renderEndTag();
		}
		parent::renderContents($writer);
	}
}
