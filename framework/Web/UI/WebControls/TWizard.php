<?php

Prado::using('System.Web.UI.WebControls.TMultiView');
Prado::using('System.Web.UI.WebControls.TPanel');

/**

containment relationship

wizard <div>
    sidebar <div>
    header <div>
    step <div>
    <div>
      navigation
*/

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
	const CMD_PREVIOUS='PreviousStep';
	const CMD_NEXT='NextStep';
	const CMD_CANCEL='Cancel';
	const CMD_COMPLETE='Complete';
	const CMD_MOVETO='MoveTo';

	/**
	 * @var TMultiView multiview that contains the wizard steps
	 */
	private $_multiView=null;
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
	 * @var TWizardStepCollection
	 */
	private $_wizardSteps=null;

	private $_header;
	private $_startNavigation;
	private $_stepNavigation;
	private $_finishNavigation;
	private $_activeStepIndexSet=false;
	/**
	 * @return string tag name for the wizard
	 */
	protected function getTagName()
	{
		return 'div';
	}

	public function addParsedObject($object)
	{
		if(is_object($object))
			$this->getWizardSteps()->add($object);
	}

	// SideBarDataList, History

	/**
	 * @return TWizardStep the currently active wizard step
	 */
	public function getActiveStep()
	{
		return $this->getMultiView()->getActiveView();
	}

	/**
	 * @return integer the zero-based index of the active wizard step
	 */
	public function getActiveStepIndex()
	{
		return $this->getMultiView()->getActiveViewIndex();
	}

	/**
	 * @param integer the zero-based index of the wizard step to be activated
	 */
	public function setActiveStepIndex($value)
	{
		$value=TPropertyValue::ensureInteger($value);
		$multiView=$this->getMultiView();
		if($multiView->getActiveViewIndex()!==$value)
		{
			$multiView->setActiveViewIndex($value);
			$this->_activeStepIndexSet=true;
			// update sidebar list
		}
	}

	public function getWizardSteps()
	{
		if($this->_wizardSteps===null)
			$this->_wizardSteps=new TWizardStepCollection($this);
		return $this->_wizardSteps;
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

	public function getNavigationButtonStyle()
	{
		if(($style=$this->getViewState('NavigationButtonStyle',null))===null)
		{
			$style=new TWizardNavigationButtonStyle;
			$this->setViewState('NavigationButtonStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TWizardNavigationButtonStyle the style for the next button in the start wizard step.
	 */
	public function getStartNextButtonStyle()
	{
		if(($style=$this->getViewState('StartNextButtonStyle',null))===null)
		{
			$style=new TWizardNavigationButtonStyle;
			$style->setButtonText('Next >');
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
			$style->setButtonText('Next >');
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
			$style->setButtonText('< Previous');
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
			$style->setButtonText('Complete');
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
			$style->setButtonText('< Previous');
			$this->setViewState('FinishPreviousButtonStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TWizardNavigationButtonStyle the style for the cancel button
	 */
	public function getCancelButtonStyle()
	{
		if(($style=$this->getViewState('CancelButtonStyle',null))===null)
		{
			$style=new TWizardNavigationButtonStyle;
			$style->setButtonText('Cancel');
			$this->setViewState('CancelButtonStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TPanelStyle the style for the side bar.
	 */
	public function getSideBarStyle()
	{
		if(($style=$this->getViewState('SideBarStyle',null))===null)
		{
			$style=new TPanelStyle;
			$this->setViewState('SideBarStyle',$style,null);
		}
		return $style;
	}

	// getSideBarButtonStyle

	/**
	 * @return TPanelStyle the style for the header.
	 */
	public function getHeaderStyle()
	{
		if(($style=$this->getViewState('HeaderStyle',null))===null)
		{
			$style=new TPanelStyle;
			$this->setViewState('HeaderStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TPanelStyle the style for each internal wizard step.
	 */
	public function getStepStyle()
	{
		if(($style=$this->getViewState('StepStyle',null))===null)
		{
			$style=new TPanelStyle;
			$this->setViewState('StepStyle',$style,null);
		}
		return $style;
	}

	public function getNavigationStyle()
	{
		if(($style=$this->getViewState('NavigationStyle',null))===null)
		{
			$style=new TPanelStyle;
			$this->setViewState('NavigationStyle',$style,null);
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

	protected function getMultiView()
	{
		if($this->_multiView===null)
		{
			$this->_multiView=new TMultiView;
			$this->_multiView->setID('WizardMultiView');
			// add handler to OnActiveViewChanged
			// ignore bubble events
		}
		return $this->_multiView;
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
		//$this->wizardStepsChanged();
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
		$this->wizardStepsChanged();
	}

	public function onInit($param)
	{
		parent::onInit($param);
		if($this->getActiveStepIndex()<0 && $this->getWizardSteps()->getCount()>0)
			$this->setActiveStepIndex(0);
		$this->ensureChildControls();
	}

	public function saveState()
	{
		$index=$this->getActiveStepIndex();
		$history=$this->getHistory();
		if(!$history->getCount() || $history->peek()!==$index)
			$history->push($index);
	}

	public function render($writer)
	{
		$this->applyControlProperties();
		parent::render($writer);
	}

	protected function applyControlProperties()
	{
		$this->applyHeaderProperties();
		$this->applyNavigationProperties();
	}

	protected function applyHeaderProperties()
	{
		$headerTemplate=$this->getHeaderTemplate();
		if($headerTemplate===null && $this->getHeaderText()==='')
			$this->_header->setVisible(false);
		else
		{
			if(($style=$this->getViewState('HeaderStyle',null))!==null)
				$this->_header->getStyle()->mergeWith($style);
			if($headerTemplate===null)
			{
				$this->_header->getControls()->clear();
				$this->_header->getControls()->add($this->getHeaderText());
			}
		}
	}

	protected function applyNavigationProperties()
	{
		$wizardSteps=$this->getWizardSteps();
		$activeStep=$this->getActiveStep();
		$activeStepIndex=$this->getActiveStepIndex();

		if(!$this->_startNavigation || !$this->_stepNavigation || !$this->_finishNavigation || $activeStepIndex<0 || $activeStepIndex>=$wizardSteps->getCount())
			return;

		if(($navigationStyle=$this->getViewState('NavigationStyle',null))!==null)
		{
			$this->_startNavigation->getStyle()->mergeWith($navigationStyle);
			$this->_stepNavigation->getStyle()->mergeWith($navigationStyle);
			$this->_finishNavigation->getStyle()->mergeWith($navigationStyle);
		}
		$activeStepType=$this->getStepType($activeStep);

		$this->_startNavigation->setVisible($activeStepType==='Start');
		$this->_stepNavigation->setVisible($activeStepType==='Step');
		$this->_finishNavigation->setVisible($activeStepType==='Finish');

		$displayCancelButton=$this->getDisplayCancelButton();
		$cancelButtonStyle=$this->getCancelButtonStyle();
		$buttonStyle=$this->getViewState('NavigationButtonStyle',null);
		if($buttonStyle!==null)
			$cancelButtonStyle->mergeWith($buttonStyle);

		if($this->getStartNavigationTemplate()===null)
		{
			$cancelButton=$this->_startNavigation->getCancelButton();
			$cancelButton->setVisible($displayCancelButton);
			$cancelButtonStyle->apply($cancelButton);

			$button=$this->_startNavigation->getNextButton();
			$button->setVisible(true);
			$style=$this->getStartNextButtonStyle();
			if($buttonStyle!==null)
				$style->mergeWith($buttonStyle);
			$style->apply($button);
		}

		if($this->getFinishNavigationTemplate()===null)
		{
			$cancelButton=$this->_finishNavigation->getCancelButton();
			$cancelButton->setVisible($displayCancelButton);
			$cancelButtonStyle->apply($cancelButton);

			// todo: whether prev should be displayed
			$button=$this->_finishNavigation->getPreviousButton();
			$button->setVisible(true);
			$style=$this->getFinishPreviousButtonStyle();
			if($buttonStyle!==null)
				$style->mergeWith($buttonStyle);
			$style->apply($button);

			$button=$this->_finishNavigation->getCompleteButton();
			$button->setVisible(true);
			$style=$this->getFinishCompleteButtonStyle();
			if($buttonStyle!==null)
				$style->mergeWith($buttonStyle);
			$style->apply($button);
		}

		if($this->getStepNavigationTemplate()===null)
		{
			$cancelButton=$this->_stepNavigation->getCancelButton();
			$cancelButton->setVisible($displayCancelButton);
			$cancelButtonStyle->apply($cancelButton);

			// todo: whether prev should be displayed
			$button=$this->_stepNavigation->getPreviousButton();
			$button->setVisible(true);
			$style=$this->getStepPreviousButtonStyle();
			if($buttonStyle!==null)
				$style->mergeWith($buttonStyle);
			$style->apply($button);

			$button=$this->_stepNavigation->getNextButton();
			$button->setVisible(true);
			$style=$this->getStepNextButtonStyle();
			if($buttonStyle!==null)
				$style->mergeWith($buttonStyle);
			$style->apply($button);
		}
	}

	protected function getHistory()
	{
		if(($history=$this->getControlState('History',null))===null)
		{
			$history=new TStack;
			$this->setControlState('History',$history);
		}
		return $history;
	}

	protected function getStepType($wizardStep)
	{
		if(($type=$wizardStep->getStepType())==='Auto')
		{
			$steps=$this->getWizardSteps();
			if(($index=$steps->indexOf($wizardStep))>=0)
			{
				$stepCount=$steps->getCount();
				if($stepCount===1 || ($index<$stepCount-1 && $steps->itemAt($index+1)->getStepType()==='Complete'))
					return 'Finish';
				else if($index===0)
					return 'Start';
				else if($index===$stepCount-1)
					return 'Finish';
				else
					return 'Step';
			}
			else
				return $type;
		}
		else
			return $type;
	}

	protected function createChildControls()
	{
		// reset wizard in case this was invoked previously
		$this->getControls()->clear();
		$this->_header=null;
		$this->_startNavigation=null;
		$this->_stepNavigation=null;
		$this->_finishNavigation=null;

		// side bar
		if($this->getDisplaySideBar())
		{
			// render side bar here
		}

		// header
		$this->_header=new TPanel;
		if(($template=$this->getHeaderTemplate())!==null)
			$template->instantiateIn($this->_header);
		else
			$this->_header->getControls()->add($this->getHeaderText());
		$this->getControls()->add($this->_header);

		// steps
		$content=new TPanel;
		$content->setID('WizardStep');
		$content->getControls()->add($this->getMultiView());
		$this->getMultiView()->setActiveViewIndex(0);
		$this->getControls()->add($content);

		$this->createStartNavigation();
		$this->createStepNavigation();
		$this->createFinishNavigation();

		$this->clearChildState();
	}

	protected function createStartNavigation()
	{
		if(($template=$this->getStartNavigationTemplate())!==null)
		{
			$this->_startNavigation=new TPanel;
			$template->instantiateIn($this->_startNavigation);
		}
		else
			$this->_startNavigation=$this->createDefaultStartNavigation();
		$this->getControls()->add($this->_startNavigation);
	}

	protected function createStepNavigation()
	{
		if(($template=$this->getStepNavigationTemplate())!==null)
		{
			$this->_stepNavigation=new TPanel;
			$template->instantiateIn($this->_stepNavigation);
		}
		else
			$this->_stepNavigation=$this->createDefaultStepNavigation();
		$this->getControls()->add($this->_stepNavigation);
	}

	protected function createFinishNavigation()
	{
		if(($template=$this->getFinishNavigationTemplate())!==null)
		{
			$this->_finishNavigation=new TPanel;
			$template->instantiateIn($this->_finishNavigation);
		}
		else
			$this->_finishNavigation=$this->createDefaultFinishNavigation();
		$this->getControls()->add($this->_finishNavigation);
	}

	protected function createDefaultStartNavigation()
	{
		$nextButton=$this->createNavigationButton($this->getStartNextButtonStyle(),true,self::CMD_NEXT);
		$cancelButton=$this->createNavigationButton($this->getCancelButtonStyle(),false,self::CMD_CANCEL);
		$navigation=new TWizardNavigationPanel(null,$nextButton,$cancelButton,null);
		$controls=$navigation->getControls();
		$controls->add($nextButton);
		$controls->add('&nbsp;');
		$controls->add($cancelButton);
		return $navigation;
	}

	protected function createDefaultStepNavigation()
	{
		$previousButton=$this->createNavigationButton($this->getStepPreviousButtonStyle(),false,self::CMD_PREVIOUS);
		$nextButton=$this->createNavigationButton($this->getStepNextButtonStyle(),true,self::CMD_NEXT);
		$cancelButton=$this->createNavigationButton($this->getCancelButtonStyle(),false,self::CMD_CANCEL);
		$navigation=new TWizardNavigationPanel($previousButton,$nextButton,$cancelButton,null);
		$controls=$navigation->getControls();
		$controls->add($previousButton);
		$controls->add('&nbsp;');
		$controls->add($nextButton);
		$controls->add('&nbsp;');
		$controls->add($cancelButton);
		return $navigation;
	}

	protected function createDefaultFinishNavigation()
	{
		$previousButton=$this->createNavigationButton($this->getFinishPreviousButtonStyle(),false,self::CMD_PREVIOUS);
		$completeButton=$this->createNavigationButton($this->getFinishCompleteButtonStyle(),true,self::CMD_COMPLETE);
		$cancelButton=$this->createNavigationButton($this->getCancelButtonStyle(),false,self::CMD_CANCEL);
		$navigation=new TWizardNavigationPanel($previousButton,null,$cancelButton,$completeButton);
		$controls=$navigation->getControls();
		$controls->add($previousButton);
		$controls->add('&nbsp;');
		$controls->add($completeButton);
		$controls->add('&nbsp;');
		$controls->add($cancelButton);
		return $navigation;
	}

	protected function createNavigationButton($buttonStyle,$causesValidation,$commandName)
	{
		switch($buttonStyle->getButtonType())
		{
			case 'Button':
				$button=Prado::createComponent('System.Web.UI.WebControls.TButton');
				break;
			case 'Link'  :
				$button=Prado::createComponent('System.Web.UI.WebControls.TLinkButton');
				break;
			case 'Image' :
				$button=Prado::createComponent('System.Web.UI.WebControls.TImageButton');
				$button->setImageUrl($style->getImageUrl());
				break;
			default:
				throw new TInvalidDataValueException('wizard_buttontype_unknown',$style->getButtonType());
		}
		$button->setText($buttonStyle->getButtonText());
		$button->setCausesValidation($causesValidation);
		$button->setCommandName($commandName);
		return $button;
	}

	public function onWizardStepsChanged()
	{
		if($this->_sideBarDataList!==null)
		{
			$this->_sideBarDataList->setDataSource($this->getWizardSteps());
			$this->_sideBarDataList->setSelectedIndex($this->getActiveStepIndex());
			$this->_sideBarDataList->dataBind();
		}
	}

	protected function getPreviousStepIndex($popStack)
	{
		$history=$this->getHistory();
		if($history->getCount()>=0)
		{
			$activeStepIndex=$this->getActiveStepIndex();
			$previousStepIndex=-1;
			if($popStack)
			{
				$previousStepIndex=$history->pop();
				if($activeStepIndex===$previousStepIndex && $history->getCount()>0)
					$previousStepIndex=$history->pop();
			}
			else
			{
				$previousStepIndex=$history->peek();
				if($activeStepIndex===$previousStepIndex && $history->getCount()>1)
				{
					$saveIndex=$history->pop();
					$previousStepIndex=$history->peek();
					$history->push($saveIndex);
				}
			}
			return $activeStepIndex===$previousStepIndex ? -1 : $previousStepIndex;
		}
		else
			return -1;
	}

	protected function allowNavigationToStep($index)
	{
		if($this->getHistory()->contains($index))
			return $this->getWizardSteps()->itemAt($index)->getAllowReturn();
		else
			return true;
	}

	public function onBubbleEvent($sender,$param)
	{
		if($param instanceof TCommandEventParameter)
		{
			$command=$param->getCommandName();
			if(strcasecmp($command,self::CMD_CANCEL)===0)
			{
				$this->onCancelButtonClick($param);
				return true;
			}

			$type=$this->getStepType($this->getActiveStep());
			$index=$this->getActiveStepIndex();
			$navParam=new TWizardNavigationEventParameter($index);

			$handled=false;
			$movePrev=false;
			$this->_activeStepIndexSet=false;

			if(strcasecmp($command,self::CMD_NEXT)===0)
			{
				if($type!=='Start' && $type!=='Step')
					throw new TInvalidDataValueException('wizard_command_invalid',self::CMD_NEXT);
				if($index<$this->getWizardSteps()->getCount()-1)
					$navParam->setNextStepIndex($index+1);
				$this->onNextButtonClick($navParam);
				$handled=true;
			}
			else if(strcasecmp($command,self::CMD_PREVIOUS)===0)
			{
				if($type!=='Finish' && $type!=='Step')
					throw new TInvalidDataValueException('wizard_command_invalid',self::CMD_PREVIOUS);
				$movePrev=true;
				if(($prevIndex=$this->getPreviousStepIndex(false))>=0)
					$navParam->setNextStepIndex($prevIndex);
				$this->onPreviousButtonClick($navParam);
				$handled=true;
			}
			else if(strcasecmp($command,self::CMD_COMPLETE)===0)
			{
				if($type!=='Finish')
					throw new TInvalidDataValueException('wizard_command_invalid',self::CMD_COMPLETE);
				if($index<$this->getWizardSteps()->getCount()-1)
					$navParam->setNextStepIndex($index+1);
				$this->onFinishButtonClick($navParam);
				$handled=true;
			}
			else if(strcasecmp($command,self::CMD_MOVETO)===0)
			{
				$navParam->setNextStepIndex(TPropertyValue::ensureInteger($param->getCommandParameter()));
				$handled=true;
			}

			if($handled)
			{
				if(!$navParam->getCancelNavigation())
				{
					$nextStepIndex=$navParam->getNextStepIndex();
					if(!$this->_activeStepIndexSet && $this->allowNavigationToStep($nextStepIndex))
					{
						if($movePrev)
							$this->getPreviousStepIndex(true);  // pop out the previous move from history
						$this->setActiveStepIndex($nextStepIndex);
					}
				}
				else
					$this->setActiveStepIndex($index);
				return true;
			}
		}
		return false;
	}
}

/**
 * TWizardNavigationButtonStyle class.
 * TWizardNavigationButtonStyle defines the style applied to a wizard navigation button.
 * The button type can be specified via {@link setButtonType ButtonType}, which
 * can be 'Button', 'Image' or 'Link'.
 * If the button is an image button, {@link setImageUrl ImageUrl} will be
 * used to load the image for the button.
 * Otherwise, {@link setButtonText ButtonText} will be displayed as the button caption.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TWizardNavigationButtonStyle extends TStyle
{
	private $_imageUrl=null;
	private $_buttonText=null;
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
		$this->_buttonText=null;
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
			if($this->_buttonText===null && $style->_buttonText!==null)
				$this->_buttonText=$style->_buttonText;
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
			if($style->_buttonText!==null)
				$this->_buttonText=$style->_buttonText;
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

	public function getButtonText()
	{
		return $this->_buttonText===null?'':$this->_buttonText;
	}

	public function setButtonText($value)
	{
		$this->_buttonText=$value;
	}

	public function getButtonType()
	{
		return $this->_buttonType===null?'Button':$this->_buttonType;
	}

	public function setButtonType($value)
	{
		$this->_buttonType=TPropertyValue::ensureEnum($value,'Button','Image','Link');
	}

	public function apply($button)
	{
		if($button instanceof TImageButton)
		{
			if($button->getImageUrl()==='')
				$button->setImageUrl($this->getImageUrl());
		}
		if($button->getText()==='')
			$button->setText($this->getButtonText());
		$button->getStyle()->mergeWith($this);
	}
}

class TWizardStep extends TView
{
	private $_owner;
	/**
	 * @var ITemplate the template for displaying the navigation UI of a wizard step.
	 */
	private $_navigationTemplate=null;
/*
	public function loadState()
	{
		if($this->_owner && ($this->getTitle()!=='' || $this->getStepType()!==''))
			$this->_owner->onWizardStepsChanged();
	}
*/
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

class TCompleteWizardStep extends TWizardStep
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
	 * @var TWizard
	 */
	private $_wizard;

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
	 * the item being added is a {@link TWizardStep}.
	 * @param integer the speicified position.
	 * @param mixed new item
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TWizardStep)
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

class TWizardNavigationPanel extends TPanel
{
	private $_previousButton=null;
	private $_nextButton=null;
	private $_cancelButton=null;
	private $_completeButton=null;

	public function __construct($previousButton,$nextButton,$cancelButton,$completeButton)
	{
		$this->_previousButton=$previousButton;
		$this->_nextButton=$nextButton;
		$this->_cancelButton=$cancelButton;
		$this->_completeButton=$completeButton;
	}

	public function getPreviousButton()
	{
		return $this->_previousButton;
	}

	public function getNextButton()
	{
		return $this->_nextButton;
	}

	public function getCancelButton()
	{
		return $this->_cancelButton;
	}

	public function getCompleteButton()
	{
		return $this->_completeButton;
	}
}

class TWizardNavigationEventParameter extends TEventParameter
{
	private $_cancel=false;
	private $_currentStep;
	private $_nextStep;

	public function __construct($currentStep)
	{
		$this->_currentStep=$currentStep;
		$this->_nextStep=$currentStep;
	}

	public function getCurrentStepIndex()
	{
		return $this->_currentStep;
	}

	public function getNextStepIndex()
	{
		return $this->_nextStep;
	}

	public function setNextStepIndex($index)
	{
		$this->_nextStep=TPropertyValue::ensureInteger($index);
	}

	public function getCancelNavigation()
	{
		return $this->_cancel;
	}

	public function setCancelNavigation($value)
	{
		$this->_cancel=TPropertyValue::ensureBoolean($value);
	}
}

?>