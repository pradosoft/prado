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

use Prado\Exceptions\TInvalidOperationException;

/**
 * TCompleteWizardStep class.
 *
 * TCompleteWizardStep represents a wizard step of type TWizardStepType::Complete.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
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
	 * @param string $value the wizard step type.
	 * @throws TInvalidOperationException whenever this method is invoked.
	 */
	public function setStepType($value)
	{
		throw new TInvalidOperationException('completewizardstep_steptype_readonly');
	}
}
