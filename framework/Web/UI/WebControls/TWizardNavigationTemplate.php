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
 * TWizardNavigationTemplate class.
 * TWizardNavigationTemplate is the base class for various navigation templates.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TWizardNavigationTemplate extends TComponent implements ITemplate
{
	private $_wizard;

	/**
	 * Constructor.
	 * @param TWizard the wizard owning this template
	 */
	public function __construct($wizard)
	{
		$this->_wizard=$wizard;
	}

	/**
	 * @return TWizard the wizard owning this template
	 */
	public function getWizard()
	{
		return $this->_wizard;
	}

	/**
	 * Instantiates the template.
	 * Derived classes should override this method.
	 * @param TControl parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
	}

	/**
	 * Creates a navigation button.
	 * It creates a {@link TButton}, {@link TLinkButton}, or {@link TImageButton},
	 * depending on the given parameters.
	 * @param TWizardNavigationButtonStyle button style
	 * @param boolean whether the button should cause validation
	 * @param string command name for the button's OnCommand event
	 * @throws TInvalidDataValueException if the button type is not recognized
	 */
	protected function createNavigationButton($buttonStyle,$causesValidation,$commandName)
	{
		switch($buttonStyle->getButtonType())
		{
			case TWizardNavigationButtonType::Button:
				$button=new TButton;
				break;
			case TWizardNavigationButtonType::Link:
				$button=new TLinkButton;
				break;
			case TWizardNavigationButtonType::Image:
				$button=new TImageButton;
				$button->setImageUrl($buttonStyle->getImageUrl());
				break;
			default:
				throw new TInvalidDataValueException('wizard_buttontype_unknown',$buttonStyle->getButtonType());
		}
		$button->setText($buttonStyle->getButtonText());
		$button->setCausesValidation($causesValidation);
		$button->setCommandName($commandName);
		return $button;
	}
}