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
 * TCompleteWizardStep class.
 *
 * TCompleteWizardStep represents a wizard step of type TWizardStepType::Complete.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TCompleteWizardStep extends TWizardStep
{
	/**
	 * @return TWizardStepType the wizard step type. Always TWizardStepType::Complete.
	 */
	public function getStepType()
	{
		return TWizardStepType::Complete;
	}

	/**
	 * @param string the wizard step type.
	 * @throws TInvalidOperationException whenever this method is invoked.
	 */
	public function setStepType($value)
	{
		throw new TInvalidOperationException('completewizardstep_steptype_readonly');
	}
}