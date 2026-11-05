<?php

/**
 * TSummary class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidOperationException;

/**
 * TSummary class
 *
 * TSummary represents the HTML5 `<summary>` element. The `<summary>` element
 * provides the caption, or legend, for the disclosure widget of its parent
 * {@see TDetails} (`<details>`) element.
 *
 * A `TSummary` must be a direct child of a {@see TDetails} control. If its
 * parent is not a `TDetails`, a {@see TInvalidOperationException} is thrown
 * when the control initializes.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSummary extends TWebControl
{
	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'summary';
	}

	/**
	 * Validates that this control is a direct child of a {@see TDetails}.
	 * @param mixed $param event parameter
	 * @throws TInvalidOperationException if the parent is not a TDetails
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		if (!($this->getParent() instanceof TDetails)) {
			throw new TInvalidOperationException('summary_requires_details_parent', $this->getID());
		}
	}
}
