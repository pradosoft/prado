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
 * TWizardStartNavigationTemplate class.
 * TWizardStartNavigationTemplate is the template used as default wizard start navigation panel.
 * It consists of two buttons, Next and Cancel.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TWizardStartNavigationTemplate extends TWizardNavigationTemplate
{
	/**
	 * Instantiates the template.
	 * @param TControl parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
		$nextButton=$this->createNavigationButton($this->getWizard()->getStartNextButtonStyle(),true,TWizard::CMD_NEXT);
		$cancelButton=$this->createNavigationButton($this->getWizard()->getCancelButtonStyle(),false,TWizard::CMD_CANCEL);

		$controls=$parent->getControls();
		$controls->add($nextButton);
		$controls->add("\n");
		$controls->add($cancelButton);

		$parent->setNextButton($nextButton);
		$parent->setCancelButton($cancelButton);
	}
}