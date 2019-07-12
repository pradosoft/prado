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

/**
 * TWizardStepNavigationTemplate class.
 * TWizardStepNavigationTemplate is the template used as default wizard step navigation panel.
 * It consists of three buttons, Previous, Next and Cancel.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TWizardStepNavigationTemplate extends TWizardNavigationTemplate
{
	/**
	 * Instantiates the template.
	 * @param TControl $parent parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
		$previousButton = $this->createNavigationButton($this->getWizard()->getStepPreviousButtonStyle(), false, TWizard::CMD_PREVIOUS);
		$nextButton = $this->createNavigationButton($this->getWizard()->getStepNextButtonStyle(), true, TWizard::CMD_NEXT);
		$cancelButton = $this->createNavigationButton($this->getWizard()->getCancelButtonStyle(), false, TWizard::CMD_CANCEL);

		$controls = $parent->getControls();
		$controls->add($previousButton);
		$controls->add("\n");
		$controls->add($nextButton);
		$controls->add("\n");
		$controls->add($cancelButton);

		$parent->setPreviousButton($previousButton);
		$parent->setNextButton($nextButton);
		$parent->setCancelButton($cancelButton);
	}
}
