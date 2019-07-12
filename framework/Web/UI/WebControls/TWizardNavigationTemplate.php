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

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\ITemplate;

/**
 * TWizardNavigationTemplate class.
 * TWizardNavigationTemplate is the base class for various navigation templates.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TWizardNavigationTemplate extends \Prado\TComponent implements ITemplate
{
	private $_wizard;

	/**
	 * Constructor.
	 * @param TWizard $wizard the wizard owning this template
	 */
	public function __construct($wizard)
	{
		$this->_wizard = $wizard;
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
	 * @param TControl $parent parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
	}

	/**
	 * Creates a navigation button.
	 * It creates a {@link TButton}, {@link TLinkButton}, or {@link TImageButton},
	 * depending on the given parameters.
	 * @param TWizardNavigationButtonStyle $buttonStyle button style
	 * @param bool $causesValidation whether the button should cause validation
	 * @param string $commandName command name for the button's OnCommand event
	 * @throws TInvalidDataValueException if the button type is not recognized
	 */
	protected function createNavigationButton($buttonStyle, $causesValidation, $commandName)
	{
		switch ($buttonStyle->getButtonType()) {
			case TWizardNavigationButtonType::Button:
				$button = new TButton;
				break;
			case TWizardNavigationButtonType::Link:
				$button = new TLinkButton;
				break;
			case TWizardNavigationButtonType::Image:
				$button = new TImageButton;
				$button->setImageUrl($buttonStyle->getImageUrl());
				break;
			default:
				throw new TInvalidDataValueException('wizard_buttontype_unknown', $buttonStyle->getButtonType());
		}
		$button->setText($buttonStyle->getButtonText());
		$button->setCausesValidation($causesValidation);
		$button->setCommandName($commandName);
		return $button;
	}
}
