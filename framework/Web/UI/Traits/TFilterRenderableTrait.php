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
	 * Creates a {@see TRenderFilterParameter} from `$output`, raises the event so
	 * handlers can modify the HTML or DOM, then returns the result via
	 * {@see TRenderFilterParameter::getFilterText}.
	 *
	 * @param string $renderedText captured rendered HTML
	 * @return string filtered HTML
	 * @see TRenderFilterParameter
	 */
	public function onRenderFilter($renderedText)
	{
		$param = new TRenderFilterParameter($renderedText);
		$this->raiseEvent('onRenderFilter', $this, $param);
		return $param->getFilterText();
	}
}
