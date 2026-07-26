<?php

/**
 * TFigureCaption class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidOperationException;

/**
 * TFigureCaption class
 *
 * TFigureCaption represents the HTML5 `<figcaption>` element. The `<figcaption>`
 * element represents a caption or a legend for the content of its parent
 * {@see TFigure} (`<figure>`) element.
 *
 * A `TFigureCaption` must be a direct child of a {@see TFigure} control. If its
 * parent is not a `TFigure`, an {@see TInvalidOperationException} is thrown when
 * the control initializes.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFigureCaption extends TWebControl
{
	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'figcaption';
	}

	/**
	 * Validates that this control is a direct child of a {@see TFigure}.
	 * @param mixed $param event parameter
	 * @throws TInvalidOperationException if the parent is not a TFigure
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		if (!($this->getParent() instanceof TFigure)) {
			throw new TInvalidOperationException('figcaption_requires_figure_parent', $this->getID());
		}
	}
}
