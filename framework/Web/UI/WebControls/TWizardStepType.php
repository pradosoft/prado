<?php
/**
 * TWizard and the relevant class definitions.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TWizardStepType class.
 * TWizardStepType defines the enumerable type for the possible types of {@see TWizard wizard} steps.
 *
 * The following enumerable values are defined:
 * - Auto: the type is automatically determined based on the location of the wizard step in the whole step collection.
 * - Complete: the step is the last summary step.
 * - Start: the step is the first step
 * - Step: the step is between the begin and the end steps.
 * - Finish: the last step before the Complete step.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TWizardStepType extends \Prado\TEnumerable
{
	public const Auto = 'Auto';
	public const Complete = 'Complete';
	public const Start = 'Start';
	public const Step = 'Step';
	public const Finish = 'Finish';
}
