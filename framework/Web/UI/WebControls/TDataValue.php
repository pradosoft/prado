<?php

/**
 * TDataValue class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TDataValue class
 * TDataValue represents the HTML `<data>` element, content paired with a
 * machine-readable {@see setValue() Value}: a product code for a product name, a
 * number for a formatted amount. A date or time is a {@see TTime}.
 * ```html
 * <com:TDataValue Value="398">Mini Ketchup</com:TDataValue>
 * ```
 * `<data>` requires `value`, so the attribute renders even when Value is empty.
 * Accessibility: `<data>` has no ARIA role. Screen readers read the content and
 * not the value, so the content is the human-readable form.
 * The default tag is `data`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDataValue extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'data';
	}

	/**
	 * @return string the machine-readable value of the content. Defaults to ''.
	 */
	public function getValue()
	{
		return $this->getViewState('Value', '');
	}

	/**
	 * @param string $value the machine-readable value of the content.
	 */
	public function setValue($value)
	{
		$this->setViewState('Value', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Adds the `value` attribute.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('value', $this->getValue());
	}
}
