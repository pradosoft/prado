<?php
/**
 * TWizard and the relevant class definitions.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TWizardStepCollection class.
 *
 * TWizardStepCollection represents the collection of wizard steps owned
 * by a {@link TWizard}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TWizardStepCollection extends TList
{
	/**
	 * @var TWizard
	 */
	private $_wizard;

	/**
	 * Constructor.
	 * @param TWizard wizard that owns this collection
	 */
	public function __construct(TWizard $wizard)
	{
		$this->_wizard=$wizard;
	}

	/**
	 * Inserts an item at the specified position.
	 * This method overrides the parent implementation by checking if
	 * the item being added is a {@link TWizardStep}.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item being added is not TWizardStep.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TWizardStep)
		{
			parent::insertAt($index,$item);
			$this->_wizard->getMultiView()->getViews()->insertAt($index,$item);
			$this->_wizard->addedWizardStep($item);
		}
		else
			throw new TInvalidDataTypeException('wizardstepcollection_wizardstep_required');
	}

	/**
	 * Removes an item at the specified position.
	 * @param integer the index of the item to be removed.
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		$step=parent::removeAt($index);
		$this->_wizard->getMultiView()->getViews()->remove($step);
		$this->_wizard->removedWizardStep($step);
		return $step;
	}
}