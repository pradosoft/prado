<?php

/**
 * TCiteTrait trait file.
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TCiteTrait trait.
 * TCiteTrait backs the `Cite` property of an element with a `cite` attribute:
 * {@see TQuote}, {@see TBlockQuote}, {@see TIns}, and {@see TDel}. `Cite` is the
 * URL of the source of a quotation or of the explanation of an edit.
 * {@see addCiteAttributeToRender()} renders the attribute when `Cite` is set.
 * The using class has `getViewState()` and `setViewState()`.
 * Accessibility: browsers do not show or announce the `cite` attribute. A source
 * that readers need is a link or a {@see TCite} in the text.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TCiteTrait
{
	/**
	 * @return string the URL of the source or explanation. Defaults to ''.
	 */
	public function getCite()
	{
		return $this->getViewState('Cite', '');
	}

	/**
	 * @param string $value the URL of the source or explanation.
	 */
	public function setCite($value)
	{
		$this->setViewState('Cite', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * Adds the `cite` attribute to the writer when {@see getCite() Cite} is set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addCiteAttributeToRender($writer)
	{
		if (($cite = $this->getCite()) !== '') {
			$writer->addAttribute('cite', $cite);
		}
	}
}
