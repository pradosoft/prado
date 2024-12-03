<?php

/**
 * TWizard and the relevant class definitions.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TWizardNavigationEventParameter class.
 *
 * TWizardNavigationEventParameter represents the parameter for
 * {@see \Prado\Web\UI\WebControls\TWizard}'s navigation events.
 *
 * The index of the currently active step can be obtained from
 * {@see getCurrentStepIndex CurrentStepIndex}, while the index
 * of the candidate new step is in {@see getNextStepIndex NextStepIndex}.
 * By modifying {@see setNextStepIndex NextStepIndex}, the new step
 * can be changed to another one. If there is anything wrong with
 * the navigation and it is not wanted, set {@see setCancelNavigation CancelNavigation}
 * to true.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TWizardNavigationEventParameter extends \Prado\TEventParameter
{
	private $_cancel = false;
	private $_currentStep;
	private $_nextStep;

	/**
	 * Constructor.
	 * @param int $currentStep current step index
	 */
	public function __construct($currentStep)
	{
		$this->_currentStep = $currentStep;
		$this->_nextStep = $currentStep;
		parent::__construct();
	}

	/**
	 * @return int the zero-based index of the currently active step.
	 */
	public function getCurrentStepIndex()
	{
		return $this->_currentStep;
	}

	/**
	 * @return int the zero-based index of the next step. Default to {@see getCurrentStepIndex CurrentStepIndex}.
	 */
	public function getNextStepIndex()
	{
		return $this->_nextStep;
	}

	/**
	 * @param int $index the zero-based index of the next step.
	 */
	public function setNextStepIndex($index)
	{
		$this->_nextStep = TPropertyValue::ensureInteger($index);
	}

	/**
	 * @return bool whether navigation to the next step should be canceled. Default to false.
	 */
	public function getCancelNavigation()
	{
		return $this->_cancel;
	}

	/**
	 * @param bool $value whether navigation to the next step should be canceled.
	 */
	public function setCancelNavigation($value)
	{
		$this->_cancel = TPropertyValue::ensureBoolean($value);
	}
}
