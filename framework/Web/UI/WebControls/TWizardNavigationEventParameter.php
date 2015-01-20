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
 * TWizardNavigationEventParameter class.
 *
 * TWizardNavigationEventParameter represents the parameter for
 * {@link TWizard}'s navigation events.
 *
 * The index of the currently active step can be obtained from
 * {@link getCurrentStepIndex CurrentStepIndex}, while the index
 * of the candidate new step is in {@link getNextStepIndex NextStepIndex}.
 * By modifying {@link setNextStepIndex NextStepIndex}, the new step
 * can be changed to another one. If there is anything wrong with
 * the navigation and it is not wanted, set {@link setCancelNavigation CancelNavigation}
 * to true.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TWizardNavigationEventParameter extends TEventParameter
{
	private $_cancel=false;
	private $_currentStep;
	private $_nextStep;

	/**
	 * Constructor.
	 * @param integer current step index
	 */
	public function __construct($currentStep)
	{
		$this->_currentStep=$currentStep;
		$this->_nextStep=$currentStep;
	}

	/**
	 * @return integer the zero-based index of the currently active step.
	 */
	public function getCurrentStepIndex()
	{
		return $this->_currentStep;
	}

	/**
	 * @return integer the zero-based index of the next step. Default to {@link getCurrentStepIndex CurrentStepIndex}.
	 */
	public function getNextStepIndex()
	{
		return $this->_nextStep;
	}

	/**
	 * @param integer the zero-based index of the next step.
	 */
	public function setNextStepIndex($index)
	{
		$this->_nextStep=TPropertyValue::ensureInteger($index);
	}

	/**
	 * @return boolean whether navigation to the next step should be canceled. Default to false.
	 */
	public function getCancelNavigation()
	{
		return $this->_cancel;
	}

	/**
	 * @param boolean whether navigation to the next step should be canceled.
	 */
	public function setCancelNavigation($value)
	{
		$this->_cancel=TPropertyValue::ensureBoolean($value);
	}
}