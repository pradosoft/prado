<?php
/**
 * TWizard and the relevant class definitions.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TWizardStepCollection class.
 *
 * TWizardStepCollection represents the collection of wizard steps owned
 * by a {@link TWizard}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TWizardStepCollection extends \Prado\Collections\TList
{
	/**
	 * @var TWizard
	 */
	private $_wizard;

	/**
	 * Constructor.
	 * @param TWizard $wizard wizard that owns this collection
	 */
	public function __construct(TWizard $wizard)
	{
		$this->_wizard = $wizard;
	}

	/**
	 * Inserts an item at the specified position.
	 * This method overrides the parent implementation by checking if
	 * the item being added is a {@link TWizardStep}.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item being added is not TWizardStep.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TWizardStep) {
			parent::insertAt($index, $item);
			$this->_wizard->getMultiView()->getViews()->insertAt($index, $item);
			$this->_wizard->addedWizardStep($item);
		} else {
			throw new TInvalidDataTypeException('wizardstepcollection_wizardstep_required');
		}
	}

	/**
	 * Removes an item at the specified position.
	 * @param int $index the index of the item to be removed.
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		$step = parent::removeAt($index);
		$this->_wizard->getMultiView()->getViews()->remove($step);
		$this->_wizard->removedWizardStep($step);
		return $step;
	}
}
