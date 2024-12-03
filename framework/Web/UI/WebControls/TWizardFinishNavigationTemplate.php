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
 * TWizardFinishNavigationTemplate class.
 * TWizardFinishNavigationTemplate is the template used as default wizard finish navigation panel.
 * It consists of three buttons, Previous, Complete and Cancel.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TWizardFinishNavigationTemplate extends TWizardNavigationTemplate
{
	/**
	 * Instantiates the template.
	 * @param \Prado\Web\UI\WebControls\TWizardNavigationContainer $parent parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
		$previousButton = $this->createNavigationButton($this->getWizard()->getFinishPreviousButtonStyle(), false, TWizard::CMD_PREVIOUS);
		$completeButton = $this->createNavigationButton($this->getWizard()->getFinishCompleteButtonStyle(), true, TWizard::CMD_COMPLETE);
		$cancelButton = $this->createNavigationButton($this->getWizard()->getCancelButtonStyle(), false, TWizard::CMD_CANCEL);

		$controls = $parent->getControls();
		$controls->add($previousButton);
		$controls->add("\n");
		$controls->add($completeButton);
		$controls->add("\n");
		$controls->add($cancelButton);

		$parent->setPreviousButton($previousButton);
		$parent->setCompleteButton($completeButton);
		$parent->setCancelButton($cancelButton);
	}
}
