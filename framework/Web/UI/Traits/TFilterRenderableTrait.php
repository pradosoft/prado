<?php

/**
 * TFilterRenderableTrait class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\Traits;

use Prado\Web\UI\TRenderFilterParameter;

/**
 * TFilterRenderableTrait trait.
 *
 * Provides the `onRenderFilter` event method required by {@see \Prado\Web\UI\IFilterRenderable}.
 * The capture-and-restore lifecycle (`preRenderFilter`, `processRenderFilter`,
 * `newRenderFilterWriter`) lives in {@see \Prado\Web\UI\TControl}, which calls
 * `onRenderFilter` at the right moment during {@see \Prado\Web\UI\TControl::renderControl}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 * @see \Prado\Web\UI\IFilterRenderable
 * @see \Prado\Web\UI\TControl::renderControl()
 */
trait TFilterRenderableTrait
{
	/**
	 * Raises `onRenderFilter` and returns the filtered HTML string.
	 *
	 * Creates a {@see TRenderFilterParameter} from `$renderedText`, raises the event so
	 * handlers can modify the HTML or DOM, then returns the result via
	 * {@see TRenderFilterParameter::getFilterText}.  After all handlers run,
	 * {@see TRenderFilterParameter::postRaiseEvent} automatically serialises any
	 * current DOM representation back to an HTML string — handlers do not need to
	 * call `getFilterText()` themselves.
	 *
	 * **String handler example**
	 * ```php
	 * $control->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) {
	 *     $param->setFilterText(strtoupper($param->getFilterText()));
	 * };
	 * ```
	 *
	 * **DOM handler example** — add missing `alt` attributes to every `<img>`:
	 * ```php
	 * $control->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) {
	 *     $dom = $param->getFilterDOM(); // DOMDocument|false; makes DOM the active resource
	 *     if ($dom === false) {
	 *         return; // libxml failed to parse the HTML fragment
	 *     }
	 *     $param->walkElements(function (\DOMElement $el, TRenderFilterParameter $p) {
	 *         if ($el->tagName === 'img' && !$el->hasAttribute('alt')) {
	 *             $el->setAttribute('alt', '');
	 *         }
	 *     });
	 *     // DOM → HTML serialisation happens automatically in postRaiseEvent
	 * };
	 * ```
	 *
	 * @param string $renderedText captured rendered HTML
	 * @return string filtered HTML
	 * @see TRenderFilterParameter
	 * @see TRenderFilterParameter::walkElements()
	 * @see TRenderFilterParameter::postRaiseEvent()
	 */
	public function onRenderFilter($renderedText)
	{
		$param = new TRenderFilterParameter($renderedText);
		$this->raiseEvent('onRenderFilter', $this, $param);
		return $param->getFilterText();
	}
}
