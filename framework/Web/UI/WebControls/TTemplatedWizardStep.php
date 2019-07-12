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

use Prado\Web\UI\ITemplate;

/**
 * TTemplatedWizardStep class.
 *
 * TTemplatedWizardStep represents a wizard step whose content and navigation
 * can be customized using templates. To customize the step content, specify
 * {@link setContentTemplate ContentTemplate}. To customize navigation specific
 * to the step, specify {@link setNavigationTemplate NavigationTemplate}. Note,
 * if the navigation template is not specified, default navigation will be used.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TTemplatedWizardStep extends TWizardStep implements \Prado\Web\UI\INamingContainer
{
	/**
	 * @var ITemplate the template for displaying the navigation UI of a wizard step.
	 */
	private $_navigationTemplate;
	/**
	 * @var ITemplate the template for displaying the content within the wizard step.
	 */
	private $_contentTemplate;
	/**
	 * @var TWizardNavigationContainer
	 */
	private $_navigationContainer;

	/**
	 * Creates child controls.
	 * This method mainly instantiates the content template, if any.
	 */
	public function createChildControls()
	{
		$this->getControls()->clear();
		if ($this->_contentTemplate) {
			$this->_contentTemplate->instantiateIn($this);
		}
	}

	/**
	 * Ensures child controls are created.
	 * @param mixed $param event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		$this->ensureChildControls();
	}

	/**
	 * @return ITemplate the template for the content of the wizard step.
	 */
	public function getContentTemplate()
	{
		return $this->_contentTemplate;
	}

	/**
	 * @param ITemplate $value the template for the content of the wizard step.
	 */
	public function setContentTemplate($value)
	{
		$this->_contentTemplate = $value;
	}

	/**
	 * @return ITemplate the template for displaying the navigation UI of a wizard step. Defaults to null.
	 */
	public function getNavigationTemplate()
	{
		return $this->_navigationTemplate;
	}

	/**
	 * @param ITemplate $value the template for displaying the navigation UI of a wizard step.
	 */
	public function setNavigationTemplate($value)
	{
		$this->_navigationTemplate = $value;
	}

	/**
	 * @return TWizardNavigationContainer the control containing the navigation.
	 * It could be null if no navigation template is specified.
	 */
	public function getNavigationContainer()
	{
		return $this->_navigationContainer;
	}

	/**
	 * Instantiates the navigation template if any
	 */
	public function instantiateNavigationTemplate()
	{
		if (!$this->_navigationContainer && $this->_navigationTemplate) {
			$this->_navigationContainer = new TWizardNavigationContainer;
			$this->_navigationTemplate->instantiateIn($this->_navigationContainer);
		}
	}
}
