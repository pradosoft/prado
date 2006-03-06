<?php

Prado::using('System.Web.UI.WebControls.TMultiView');

/**
 * Class TWizard.
 * TWizard splits a large form and present the user with a series of smaller
 * form to complete. TWizard is analogous to the installation wizard commonly
 * used to install software in Windows.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TWizard extends TWebControl implements INamingContainer
{
	/**
	 * @var mixed navigation template for the start step.
	 */
	private $_startNavigationTemplate=null;
	/**
	 * @var mixed navigation template for internal steps.
	 */
	private $_stepNavigationTemplate=null;
	/**
	 * @var mixed navigation template for the finish step.
	 */
	private $_finishNavigationTemplate=null;
	/**
	 * @var mixed template for wizard header.
	 */
	private $_headerTemplate=null;
	/**
	 * @var mixed template for the side bar.
	 */
	private $_sideBarTemplate=null;

	/**
	 * @return string tag name for the wizard
	 */
	protected function getTagName()
	{
		return 'table';
	}

	// SideBarDataList, MultiView, History
	/**
	 * Creates a style object for the wizard.
	 * This method creates a {@link TTableStyle} to be used by the wizard.
	 * @return TTableStyle control style to be used
	 */
	protected function createStyle()
	{
		return new TTableStyle;
	}

	/**
	 * @return integer the cellspacing for the table used by wizard. Defaults to -1, meaning not set.
	 */
	public function getCellSpacing()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getCellSpacing();
		else
			return -1;
	}

	/**
	 * @param integer the cellspacing for the table used by wizard. Defaults to -1, meaning not set.
	 */
	public function setCellSpacing($value)
	{
		$this->getStyle()->setCellSpacing($value);
	}

	/**
	 * @return integer the cellpadding for the table used by wizard. Defaults to -1, meaning not set.
	 */
	public function getCellPadding()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getCellPadding();
		else
			return -1;
	}

	/**
	 * @param integer the cellpadding for the table used by wizard. Defaults to -1, meaning not set.
	 */
	public function setCellPadding($value)
	{
		$this->getStyle()->setCellPadding($value);
	}

	/**
	 * @return TWizardStepBase the currently active wizard step
	 */
	public function getActiveStep()
	{

	}

	/**
	 * @return integer the zero-based index of the active wizard step
	 */
	public function getActiveStepIndex()
	{
	}

	/**
	 * @param integer the zero-based index of the wizard step to be activated
	 */
	public function setActiveStepIndex($value)
	{
	}

	public function getWizardSteps()
	{
	}

	public function getTemplatedSteps()
	{
	}

	public function getNavigationTableCell()
	{
	}

	/**
	 * @return boolean whether to display a cancel in each wizard step. Defaults to false.
	 */
	public function getDisplayCancelButton()
	{
		return $this->getViewState('DisplayCancelButton',false);
	}

	/**
	 * @param boolean whether to display a cancel in each wizard step.
	 */
	public function setDisplayCancelButton($value)
	{
		$this->setViewState('DisplayCancelButton',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean whether to display a side bar that contains links to wizard steps. Defaults to true.
	 */
	public function getDisplaySideBar()
	{
		return $this->getViewState('DisplaySideBar',true);
	}

	/**
	 * @param boolean whether to display a side bar that contains links to wizard steps.
	 */
	public function setDisplaySideBar($value)
	{
		$this->setViewState('DisplaySideBar',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return ITemplate navigation template for the start step. Defaults to null.
	 */
	public function getStartNavigationTemplate()
	{
		return $this->_startNavigationTemplate;
	}

	/**
	 * @param ITemplate navigation template for the start step.
	 */
	public function setStartNavigationTemplate($value)
	{
		$this->_startNavigationTemplate=$value;
	}

	/**
	 * @return ITemplate navigation template for internal steps. Defaults to null.
	 */
	public function getStepNavigationTemplate()
	{
		return $this->_stepNavigationTemplate;
	}

	/**
	 * @param ITemplate navigation template for internal steps.
	 */
	public function setStepNavigationTemplate($value)
	{
		$this->_stepNavigationTemplate=$value;
	}

	/**
	 * @return ITemplate navigation template for the finish step. Defaults to null.
	 */
	public function getFinishNavigationTemplate()
	{
		return $this->_finishNavigationTemplate;
	}

	/**
	 * @param ITemplate navigation template for the finish step.
	 */
	public function setFinishNavigationTemplate($value)
	{
		$this->_finishNavigationTemplate=$value;
	}

	/**
	 * @return ITemplate template for wizard header. Defaults to null.
	 */
	public function getHeaderTemplate()
	{
		return $this->_headerTemplate;
	}

	/**
	 * @param ITemplate template for wizard header.
	 */
	public function setHeaderTemplate($value)
	{
		$this->_headerTemplate=$value;
	}

	/**
	 * @return ITemplate template for the side bar. Defaults to null.
	 */
	public function getSideBarTemplate()
	{
		return $this->_sideBarTemplate;
	}

	/**
	 * @param ITemplate template for the side bar.
	 */
	public function setSideBarTemplate($value)
	{
		$this->_sideBarTemplate=$value;
	}

	/**
	 * @return string header text. Defaults to ''.
	 */
	public function getHeaderText()
	{
		return $this->getViewState('HeaderText','');
	}

	/**
	 * @param string header text.
	 */
	public function setHeaderText($value)
	{
		$this->setViewState('HeaderText',TPropertyValue::ensureString($value),'');
	}

	/**
	 * @return string the URL that the browser will be redirected to if the cancel button in the
	 * wizard is clicked. Defaults to ''.
	 */
	public function getCancelDestinationUrl()
	{
		return $this->getViewState('CancelDestinationUrl','');
	}

	/**
	 * @param string the URL that the browser will be redirected to if the cancel button in the
	 * wizard is clicked.
	 */
	public function setCancelDestinationUrl($value)
	{
		$this->setViewState('CancelDestinationUrl',TPropertyValue::ensureString($value),'');
	}

	/**
	 * @return string the URL that the browser will be redirected to if the wizard finishes.
	 * Defaults to ''.
	 */
	public function getFinishDestinationUrl()
	{
		return $this->getViewState('FinishDestinationUrl','');
	}

	/**
	 * @param string the URL that the browser will be redirected to if the wizard finishes.
	 */
	public function setFinishDestinationUrl($value)
	{
		$this->setViewState('FinishDestinationUrl',TPropertyValue::ensureString($value),'');
	}

	/**
	 * @return TWizardNavigationButtonStyle the style for the next button in the start wizard step.
	 */
	public function getStartNextButtonStyle()
	{
		if(($style=$this->getViewState('StartNextButtonStyle',null))===null)
		{
			$style=new TWizardNavigationButtonStyle;
			$style->setText('Next');
			$this->setViewState('StartNextButtonStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TWizardNavigationButtonStyle the style for the next button in each internal wizard step.
	 */
	public function getStepNextButtonStyle()
	{
		if(($style=$this->getViewState('StepNextButtonStyle',null))===null)
		{
			$style=new TWizardNavigationButtonStyle;
			$style->setText('Next >');
			$this->setViewState('StepNextButtonStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TWizardNavigationButtonStyle the style for the previous button in the start wizard step.
	 */
	public function getStepPreviousButtonStyle()
	{
		if(($style=$this->getViewState('StepPreviousButtonStyle',null))===null)
		{
			$style=new TWizardNavigationButtonStyle;
			$style->setText('< Previous');
			$this->setViewState('StepPreviousButtonStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TWizardNavigationButtonStyle the style for the complete button in the finish wizard step.
	 */
	public function getFinishCompleteButtonStyle()
	{
		if(($style=$this->getViewState('FinishCompleteButtonStyle',null))===null)
		{
			$style=new TWizardNavigationButtonStyle;
			$style->setText('Complete');
			$this->setViewState('FinishCompleteButtonStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TWizardNavigationButtonStyle the style for the previous button in the start wizard step.
	 */
	public function getFinishPreviousButtonStyle()
	{
		if(($style=$this->getViewState('FinishPreviousButtonStyle',null))===null)
		{
			$style=new TWizardNavigationButtonStyle;
			$style->setText('Previous');
			$this->setViewState('FinishPreviousButtonStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for the side bar.
	 */
	public function getSideBarStyle()
	{
		if(($style=$this->getViewState('SideBarStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('SideBarStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for the header.
	 */
	public function getHeaderStyle()
	{
		if(($style=$this->getViewState('HeaderStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('HeaderStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the style for each internal wizard step.
	 */
	public function getStepStyle()
	{
		if(($style=$this->getViewState('StepStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('StepStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TStyle the style for the cancel button
	 */
	public function getCancelButtonStyle()
	{
		if(($style=$this->getViewState('CancelButtonStyle',null))===null)
		{
			$style=new TStyle;
			$this->setViewState('CancelButtonStyle',$style,null);
		}
		return $style;
	}

	/**
	 * Raises <b>OnActiveStepChanged</b> event.
	 * This event is raised when the current visible step is changed in the
	 * wizard.
	 * @param TEventParameter event parameter
	 */
	public function onActiveStepChanged($param)
	{
		$this->raiseEvent('OnActiveStepChanged',$this,$param);
	}

	/**
	 * Raises <b>OnCancelButtonClick</b> event.
	 * This event is raised when a cancel navigation button is clicked in the
	 * current active step.
	 * @param TEventParameter event parameter
	 */
	public function onCancelButtonClick($param)
	{
		$this->raiseEvent('OnCancelButtonClick',$this,$param);
	}

	/**
	 * Raises <b>OnFinishButtonClick</b> event.
	 * This event is raised when a finish navigation button is clicked in the
	 * current active step.
	 * @param TEventParameter event parameter
	 */
	public function onFinishButtonClick($param)
	{
		$this->raiseEvent('OnFinishButtonClick',$this,$param);
	}

	/**
	 * Raises <b>OnNextButtonClick</b> event.
	 * This event is raised when a next navigation button is clicked in the
	 * current active step.
	 * @param TEventParameter event parameter
	 */
	public function onNextButtonClick($param)
	{
		$this->raiseEvent('OnNextButtonClick',$this,$param);
	}

	/**
	 * Raises <b>OnPreviousButtonClick</b> event.
	 * This event is raised when a previous navigation button is clicked in the
	 * current active step.
	 * @param TEventParameter event parameter
	 */
	public function onPreviousButtonClick($param)
	{
		$this->raiseEvent('OnPreviousButtonClick',$this,$param);
	}

	/**
	 * Raises <b>OnSideBarButtonClick</b> event.
	 * This event is raised when a link button in the side bar is clicked.
	 * @param TEventParameter event parameter
	 */
	public function onSideBarButtonClick($param)
	{
		$this->raiseEvent('OnSideBarButtonClick',$this,$param);
	}

	public function addedWizardStep($step)
	{
		if(($owner=$step->getOwner())!==null)
			$owner->getWizardSteps()->remove($step);
		$step->setOwner($this);
		$this->getMultiView()->getViews()->add($step);
		if($step instanceof TTemplateWizardStep)
		{
			// $this->_templatedSteps[]=$step;
			//$this->getTemplateWizardSteps()->add($step);
			// register it ???
		}
		$this->onWizardStepsChanged();
	}

	public function removedWizardStep($step)
	{
		$this->getMultiView()->getViews()->remove($step);
		$step->setOwner(null);
		if($step instanceof TTemplateWizardStep)
		{
			// $this->_templatedSteps....
			//$this->getTemplateWizardSteps()->remove($step);
		}
		$this->onWizardStepsChanged();
	}
}

/**
 * TWizardNavigationButtonStyle class.
 * TWizardNavigationButtonStyle defines the style applied to a wizard navigation button.
 * The button type can be specified via {@link setButtonType ButtonType}, which
 * can be 'Button', 'Image' or 'Link'.
 * If the button is an image button, {@link setImageUrl ImageUrl} will be
 * used to load the image for the button.
 * Otherwise, {@link setText Text} will be displayed as the button caption.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TWizardNavigationButtonStyle extends TStyle
{
	private $_imageUrl=null;
	private $_text=null;
	private $_buttonType=null;

	/**
	 * Sets the style attributes to default values.
	 * This method overrides the parent implementation by
	 * resetting additional TWizardButtonStyle specific attributes.
	 */
	public function reset()
	{
		parent::reset();
		$this->_imageUrl=null;
		$this->_text=null;
		$this->_buttonType=null;
	}

	/**
	 * Copies the fields in a new style to this style.
	 * If a style field is set in the new style, the corresponding field
	 * in this style will be overwritten.
	 * @param TStyle the new style
	 */
	public function copyFrom($style)
	{
		parent::copyFrom($style);
		if($style instanceof TWizardButtonStyle)
		{
			if($this->_imageUrl===null && $style->_imageUrl!==null)
				$this->_imageUrl=$style->_imageUrl;
			if($this->_text===null && $style->_text!==null)
				$this->_text=$style->_text;
			if($this->_buttonType===null && $style->_buttonType!==null)
				$this->_buttonType=$style->_buttonType;
		}
	}

	/**
	 * Merges the style with a new one.
	 * If a style field is not set in this style, it will be overwritten by
	 * the new one.
	 * @param TStyle the new style
	 */
	public function mergeWith($style)
	{
		parent::mergeWith($style);
		if($style instanceof TWizardButtonStyle)
		{
			if($style->_imageUrl!==null)
				$this->_imageUrl=$style->_imageUrl;
			if($style->_text!==null)
				$this->_text=$style->_text;
			if($style->_buttonType!==null)
				$this->_buttonType=$style->_buttonType;
		}
	}

	public function getImageUrl()
	{
		return $this->_imageUrl===null?'':$this->_imageUrl;
	}

	public function setImageUrl($value)
	{
		$this->_imageUrl=$value;
	}

	public function getText()
	{
		return $this->_text===null?'':$this->_text;
	}

	public function setText($value)
	{
		$this->_text=$value;
	}

	public function getButtonType()
	{
		return $this->_buttonType===null?'Button':$this->_buttonType;
	}

	public function setButtonType($value)
	{
		$this->_buttonType=TPropertyValue::ensureEnum($value,'Button','Image','Link');
	}
}

abstract class TWizardStepBase extends TView
{
	private $_owner;

	public function loadState()
	{
		if($this->_owner && ($this->getTitle()!=='' || $this->getStepType()!==''))
			$this->_owner->onWizardStepsChanged();
	}

	public function getOwner()
	{
		return $this->_owner;
	}

	public function setOwner($owner)
	{
		$this->_owner=$owner;
	}

	public function getWizard()
	{
		return $this->_owner;
	}

	public function getTitle()
	{
		return $this->getViewState('Title','');
	}

	public function setTitle($value)
	{
		$this->setViewState('Title',$value,'');
		if($this->_owner)
			$this->_owner->onWizardStepsChanged();
	}

	public function getName()
	{
		if(($title=$this->getTitle())==='')
			return $this->getID();
		else
			return $title;
	}

	public function getAllowReturn()
	{
		return $this->getViewState('AllowReturn',true);
	}

	public function setAllowReturn($value)
	{
		$this->setViewState('AllowReturn',TPropertyValue::ensureBoolean($value),true);
	}

	public function getStepType()
	{
		return $this->getViewState('StepType','Auto');
	}

	public function setStepType($type)
	{
		$type=TPropertyValue::ensureEnum($type,'Auto','Complete','Finish','Start','Step');
		if($type!==$this->getStepType())
		{
			$this->setViewState('StepType',$type,'Auto');
			if($this->_owner)
				$this->_owner->onWizardStepsChanged();
		}
	}
}

class TWizardStep extends TWizardStepBase
{
}

class TTemplateWizardStep extends TWizardStepBase
{
	/**
	 * @var ITemplate the template for displaying the content of a wizard step.
	 */
	private $_contentTemplate=null;
	/**
	 * @var ITemplate the template for displaying the navigation UI of a wizard step.
	 */
	private $_navigationTemplate=null;

	/**
	 * @return ITemplate the template for displaying the content of a wizard step. Defaults to null.
	 */
	public function getContentTemplate()
	{
		return $this->_contentTemplate;
	}

	/**
	 * @param ITemplate the template for displaying the content of a wizard step.
	 */
	public function setContentTemplate($value)
	{
		$this->_contentTemplate=$value;
	}

	/**
	 * @return ITemplate the template for displaying the navigation UI of a wizard step. Defaults to null.
	 */
	public function getNavigationTemplate()
	{
		return $this->_navigationTemplate;
	}

	/**
	 * @param ITemplate the template for displaying the navigation UI of a wizard step.
	 */
	public function setNavigationTemplate($value)
	{
		$this->_navigationTemplate=$value;
	}
}

class TCompleteWizardStep extends TTemplateWizardStep
{
	public function getStepType()
	{
		return 'Complete';
	}

	public function setStepType($value)
	{
		throw new TInvalidOperationException('completewizardstep_steptype_readonly');
	}
}

class TWizardStepCollection extends TList
{
	/**
	 * Constructor.
	 * @param TWizard wizard that owns this collection
	 */
	public function __construct(TWizard $wizard)
	{
		$this->_wizard=$wizard;
	}

	/**
	 * Inserts an item at the specified position.
	 * This method overrides the parent implementation by checking if
	 * the item being added is a {@link TWizardStepBase}.
	 * @param integer the speicified position.
	 * @param mixed new item
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TWizardStepBase)
		{
			parent::insertAt($index,$item);
			$this->_wizard->addedWizardStep($item);
		}
		else
			throw new TInvalidDataTypeException('wizardstepcollection_wizardstepbase_required');
	}

	/**
	 * Removes an item at the specified position.
	 * @param integer the index of the item to be removed.
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		$step=parent::removeAt($index);
		$this->_wizard->removedWizardStep($step);
		return $step;
	}
}

?>