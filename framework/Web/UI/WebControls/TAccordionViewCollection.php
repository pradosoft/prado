<?php
/**
 * TAccordion class file.
 *
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.2
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * Class TAccordionViewCollection.
 *
 * TAccordionViewCollection is a collection of {@see \Prado\Web\UI\WebControls\TAccordionView} to be used inside a {@see \Prado\Web\UI\WebControls\TAccordion}.
 *
 * @author Gabor Berczi, DevWorx Hungary <gabor.berczi@devworx.hu>
 * @since 3.2
 */
class TAccordionViewCollection extends \Prado\Web\UI\TControlCollection
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing sanity check on the type of new item.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a {@see \Prado\Web\UI\WebControls\TAccordionView} object.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TAccordionView) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('tabviewcollection_tabview_required');
		}
	}

	/**
	 * Finds the index of the accordion view whose ID is the same as the one being looked for.
	 * @param string $id the explicit ID of the accordion view to be looked for
	 * @return int the index of the accordion view found, -1 if not found.
	 */
	public function findIndexByID($id)
	{
		foreach ($this as $index => $view) {
			if ($view->getID(false) === $id) {
				return $index;
			}
		}
		return -1;
	}
}
