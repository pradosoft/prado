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

use Prado\TPropertyValue;

/**
 * TWizardStep class.
 *
 * TWizardStep represents a wizard step. The wizard owning the step
 * can be obtained by {@link getWizard Wizard}.
 * To specify the type of the step, set {@link setStepType StepType};
 * For step title, set {@link setTitle Title}. If a step can be re-visited,
 * set {@link setAllowReturn AllowReturn} to true.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TWizardStep extends TView
{
	private $_wizard;

	/**
	 * @return TWizard the wizard owning this step
	 */
	public function getWizard()
	{
		return $this->_wizard;
	}

	/**
	 * Sets the wizard owning this step.
	 * This method is used internally by {@link TWizard}.
	 * @param TWizard $wizard the wizard owning this step
	 */
	public function setWizard($wizard)
	{
		$this->_wizard = $wizard;
	}

	/**
	 * @return string the title for this step.
	 */
	public function getTitle()
	{
		return $this->getViewState('Title', '');
	}

	/**
	 * @param string $value the title for this step.
	 */
	public function setTitle($value)
	{
		$this->setViewState('Title', $value, '');
		if ($this->_wizard) {
			$this->_wizard->wizardStepsChanged();
		}
	}

	/**
	 * @return bool whether this step can be re-visited. Default to true.
	 */
	public function getAllowReturn()
	{
		return $this->getViewState('AllowReturn', true);
	}

	/**
	 * @param bool $value whether this step can be re-visited.
	 */
	public function setAllowReturn($value)
	{
		$this->setViewState('AllowReturn', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return TWizardStepType the wizard step type. Defaults to TWizardStepType::Auto.
	 */
	public function getStepType()
	{
		return $this->getViewState('StepType', TWizardStepType::Auto);
	}

	/**
	 * @param TWizardStepType $type the wizard step type.
	 */
	public function setStepType($type)
	{
		$type = TPropertyValue::ensureEnum($type, 'Prado\\Web\\UI\\WebControls\\TWizardStepType');
		if ($type !== $this->getStepType()) {
			$this->setViewState('StepType', $type, TWizardStepType::Auto);
			if ($this->_wizard) {
				$this->_wizard->wizardStepsChanged();
			}
		}
	}
}
