<?php
/**
 * TWizard component.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Xiang Wei Zhuo.
 *
 * To contact the author write to {@link mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.8 $  $Date: 2005/12/17 06:11:28 $
 * @package System.Web.UI.WebControls
 */

/**
 * TWizard splits a large form and present the user with a series
 * of smaller form to complete. The TWizard is analogous to the
 * installation wizard commonly used to install software in Windows.
 *
 * TWizard centralizes the required events to manipulate the flow of
 * the form. It also renders the appropriate step along with the navigation
 * elements. The wizard allows the steps to be presented linearly or otherwise
 * in a nonlinear fashion. That is, the forms can be filled sequentially or
 * if permitted allowed the user to choose which ever step he/she wishes.
 * In addition, the steps can be programmed to be skipped or repeated.
 *
 * A simple example of 3 steps.
 *<code>
 *  <com:TWizard ID="ContactWizard" >
 *      <com:TWizardStep Title="Step 1: Name">
 *          <com:TLabel ForControl="Name">Full name:</com:TLabel>
 *          <com:TTextBox ID="Name" />
 *      </com:TWizardStep>
 *      <com:TWizardStep Title="Step 2: Contact">
 *          <com:TLabel ForControl="Phone">Telephone Number:</com:TLabel>
 *          <com:TTextBox ID="Phone" />
 *          <com:TLabel ForControl="Email">Email:</com:TLabel>
 *          <com:TTextBox ID="Email" />
 *      </com:TWizardStep>
 *      <com:TWizardStep Title="Step 3: Confirmation">
 *          <table><tr><th>Name:</th>
 *              <td><%= $this->Page->ContactWizard->Name->Text %></td>
 *          </tr><tr><th>Phone:</th>
 *              <td><%= $this->Page->ContactWizard->Phone->Text %></td>
 *          </tr><tr><th>Email:</th>
 *              <td><%= $this->Page->ContactWizard->Email->Text %></td>
 *          </tr></table>
 *      </com:TWizardStep>
 *  </com:TWizard>
 *</code>
 *
 * TWizard also intercepts the following bubbled events. E.g TButton
 * has CommandName and CommandParameter properties that bubbles as
 * "OnBubbleEvent". The following are the supported bubble event names
 * and how TWizard handles them.
 *
 *	Bubble Events
 *	- <b>next</b>, TWizard fires <b>OnNextCommand</b> event.
 *	- <b>previous</b>, TWizard fires <b>OnPreviousCommand</b> event.
 *	- <b>finish</b>, TWizard fires <b>OnFinishCommand</b> event.
 *	- <b>cancel</b>, TWizard fires <b>OnCancelCommand</b> event.
 *	- <b>jumpto</b>, TWizard fires <b>OnJumpToCommand</b> event.
 *                   <b>jumpto</b> requires a parameter, the destination step.
 *
 * E.g. anywhere within the TWizard, a button like the following
 *  <code><com:TButton CommandName="jumpto" CommandParameter="2" /></code>
 * when click will bubble to TWizard and in turn fires the OnJumpToCommand
 * with parameter value of "2".
 *
 * Namespace: System.Web.UI.WebControls
 *
 * Properties
 * - <b>ActiveStep</b>, TWizardStep,
 *   <br>Gets the current active step.
 * - <b>ActiveStepIndex</b>, integer,
 *   <br>Gets or sets the active step specified by a zero-starting index.
 * - <b>DisplaySideBar</b>, boolean
 *	 <br>isSideBarVisible or setDisplaySideBar, show or hides the side bar.
 * - <b>FinishStepButtonText</b>, string
 *   <br>Gets or sets the string for the "Finish" button.
 * - <b>NextStepButtonText</b>, string
 *   <br>Gets or sets the string for the "Next" button.
 * - <b>PreviousStepButtonText</b>, string
 *   <br>Gets or sets the string for the "Previous" button.
 * - <b>CancelButtonText</b>, string
 *   <br>Gets or sets the string for the "Cancel" button.
 *
 * Events
 * - <b>OnStepChanged</b> Occurs when the step is changed.
 * - <b>OnCancelCommand</b> Occurs when the "Cancel" button is pressed.
 * - <b>OnFinishCommand</b> Occurs when the "Finish" button is pressed.
 * - <b>OnNextCommand</b> Occurs when the "Next" button is pressed.
 * - <b>OnPreviousCommand</b> Occurs when the "Previous" button is pressed.
 * - <b>OnJumpToCommand</b> Occurs when the "JumpTo" button is pressed.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version v1.0, last update on Sat Dec 11 15:25:11 EST 2004
 * @package System.Web.UI.WebControls
 */
class TWizard extends TPanel implements INamingContainer
{
	/**
	 * The command name for the OnNextCommand.
	 * @var string
	 */
	const CMD_NEXT = 'next';

	/**
	 * The command name for the OnPreviousCommand.
	 * @var string
	 */
	const CMD_PREVIOUS = 'previous';

	/**
	 * The command name for the OnFinishCommand.
	 * @var string
	 */
	const CMD_FINISH = 'finish';

	/**
	 * The command name for the OnCancelCommand.
	 * @var string
	 */
	const CMD_CANCEL = 'cancel';

	/**
	 * The command name for the OnJumpToCommand.
	 * @var string
	 */
	const CMD_JUMP = 'jumpto';

	/**
	 * A list of steps.
	 * @var array
	 */
	private $_steps=array();

	/**
	 * A list of navigation templates, including built-in defaults.
	 * @var array
	 */
	private $_navigation = array();

	/**
	 * A list of links for the side bar.
	 * @var array
	 */
	private $_sidebarLinks = array();

	/**
	 * Set the Finish button text.
	 * @param string button text
	 */
	public function setFinishStepButtonText($value)
	{
		$this->setViewState('FinishStepButtonText', $value, 'Finish');
	}

	/**
	 * Get the Finish button text.
	 * @return string button text.
	 */
	public function getFinishStepButtonText()
	{
		return $this->getViewState('FinishStepButtonText', 'Finish');
	}

	/**
	 * Set the Next button text.
	 * @param string button text
	 */
	public function setNextStepButtonText($value)
	{
		$this->setViewState('NextStepButtonText', $value, 'Next >');

	}

	/**
	 * Get the Next button text.
	 * @return string button text.
	 */
	public function getNextStepButtonText()
	{
		return $this->getViewState('NextStepButtonText', 'Next >');
	}

	/**
	 * Set the Previous button text.
	 * @param string button text
	 */
	public function setPreviousStepButtonText($value)
	{
		$this->setViewState('PreviousStepButtonText',$value, '< Back');
	}

	/**
	 * Get the Previous button text.
	 * @return string button text.
	 */
	public function getPreviousStepButtonText()
	{
		return $this->getViewState('PreviousStepButtonText', '< Back');
	}

	/**
	 * Set the Cancel button text.
	 * @param string button text
	 */
	public function setCancelButtonText($value)
	{
		$this->setViewState('CancelButtonText', $value, 'Cancel');
	}

	/**
	 * Get the Cancel button text.
	 * @return string button text.
	 */
	public function getCancelButtonText()
	{
		return $this->getViewState('CancelButtonText', 'Cancel');
	}

	/**
	 * Show or hide the side bar.
	 * @param boolean true to show the side bar, false hides it.
	 */
	public function setDisplaySideBar($value)
	{
		$this->setViewState('DisplaySideBar',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * Determine if the side bar's visibility.
	 * @return boolean true if visible, false otherwise.
	 */
	public function getDisplaySideBar()
	{
		return $this->getViewState('DisplaySideBar',true);
	}

	/**
	 * Get the current step. null if the ActiveStepIndex is not valid.
	 * @return TWizardStep
	 */
	public function getActiveStep()
	{
		$index = $this->getActiveStepIndex();
		if(isset($this->_steps[$index]))
			return $this->_steps[$index];
		else
			return null;
	}

	/**
	 * Set the active step index. This determines which step to show.
	 * @param int the current step to show.
	 */
	public function setActiveStepIndex($index)
	{
		$this->setViewState('ActiveStepIndex',$index,0);
	}

	/**
	 * Get the current step index.
	 * @return int current step index.
	 */
	public function getActiveStepIndex()
	{
		return $this->getViewState('ActiveStepIndex', 0);
	}

	/**
	 * Override the parent implementation.
	 * It adds any components that are instance of TWizardStep or TWizardTemplate
	 * as a child and body of the TWizard. Other components are handled by the parent.
	 * By adding components as child of TWizard, these component's parent
	 * is the TWizard.
	 * @param object a component object.
	 */
	public function addParsedObject($object,$context)
	{
		if($object instanceof TWizardStep)
		{
			   $object->setVisible(false);
			   $this->_steps[] = $object;
			   $this->getControls()->add($object);
		}
		else if ($object instanceof TWizardTemplate)
		{
			   $object->setVisible(false);
			   $this->_navigation[$object->getType()][] = $object;
			   $this->getControls()->add($object);
		}
		else
			parent::addParsedObject($object,$context);
	}

	/**
	 * Initalize and add the default navigation templates. Add the side bar
	 * if required.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	public function onLoad($param)
	{
		parent::onLoad($param);

		$this->addNavigationButtons();

		if($this->isSideBarVisible())
			$this->addNavigationSideBar();
	}

	/**
	 * Determins which wizard step to show and appropriate navigation elements.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);

		$index = $this->getActiveStepIndex();
		$totalSteps = count($this->_steps);

		//show the current step
		for($i = 0; $i < $totalSteps; $i++)
			$this->_steps[$i]->setVisible($i == $index);

		//determine which link is active
		for($i = 0, $k = count($this->_sidebarLinks); $i < $k; $i++)
			$this->_sidebarLinks[$i]->CssClass= ($i == $index)?'active':'';

		//hide all the navigations first.
		foreach($this->_navigation as $navigation)
		{
			foreach($navigation as $nav)
				$nav->setVisible(false);
		}

		$final = $this->_steps[$index]->Type == TWizardStep::TYPE_FINAL;

		//if it is not the final step
		if(!$final && $this->isSideBarVisible())
			$this->showNavigation(TWizardTemplate::ID_SIDEBAR);

		$finishStep = $index == $totalSteps-1;
		$finishStep = $finishStep || (isset($this->_steps[$index+1]) &&
					$this->_steps[$index+1]->Type == TWizardStep::TYPE_FINAL);

		//now show the appropriate navigation elements.
		if($index == 0)
			$this->showNavigation(TWizardTemplate::ID_START);
		else if($final) ; //skip it
		else if($finishStep)
			$this->showNavigation(TWizardTemplate::ID_FINISH);
		else
			$this->showNavigation(TWizardTemplate::ID_STEP);
	}

	/**
	 * Show of the navigation elements for a particular type.
	 * @param string navigation type.
	 */
	private function showNavigation($index)
	{
		if(!isset($this->_navigation[$index])) return;
		foreach($this->_navigation[$index] as $nav)
		{
			$nav->setVisible(true);
			$nav->dataBind();
		}
	}

	/**
	 * Construct the default navigation elements for the wizard.
	 * The default navigations are only added if the template for that
	 * particular navigation type is not customized.
	 */
	private function addNavigationButtons()
	{
		//create the 3 navigation components
		$start = $this->createComponent('TPanel',TWizardTemplate::ID_START);
		$start->CssClass = 'navigation';

		$step = $this->createComponent('TPanel',TWizardTemplate::ID_STEP);
		$step->CssClass = 'navigation';

		$finish = $this->createComponent('TPanel',TWizardTemplate::ID_FINISH);
		$finish->CssClass = 'navigation';

		$previousButton = $this->createComponent('TButton');
		$previousButton->setText($this->getPreviousStepButtonText());
		$previousButton->setCommandName(self::CMD_PREVIOUS);
		$previousButton->setCausesValidation(false);

		$finishButton = $this->createComponent('TButton');
		$finishButton->setText($this->getFinishStepButtonText());
		$finishButton->setCommandName(self::CMD_FINISH);

		$nextButton = $this->createComponent('TButton');
		$nextButton->setText($this->getNextStepButtonText());
		$nextButton->setCommandName(self::CMD_NEXT);

		$hiddenButton = $this->createComponent('TButton');
		$hiddenButton->setCommandName(self::CMD_NEXT);
		$hiddenButton->setStyle(array('display'=>'none'));

		$cancelButton = $this->createComponent('TButton');
		$cancelButton->setText($this->getCancelButtonText());
		$cancelButton->setCommandName(self::CMD_CANCEL);
		$cancelButton->CssClass='Cancel';
		$cancelButton->setCausesValidation(false);

		if(!isset($this->_navigation[TWizardTemplate::ID_START]))
		{
			$start->addBody($nextButton);
			$start->addBody($cancelButton);
			$this->addBody($start);
			$this->_navigation[TWizardTemplate::ID_START][] = $start;
		}

		if(!isset($this->_navigation[TWizardTemplate::ID_STEP]))
		{

			$step->addBody($hiddenButton);
			$step->addBody($previousButton);
			$step->addBody($nextButton);
			$step->addBody($cancelButton);
			$this->addBody($step);
			$this->_navigation[TWizardTemplate::ID_STEP][] = $step;
		}

		if(!isset($this->_navigation[TWizardTemplate::ID_FINISH]))
		{
			$finish->addBody($previousButton);
			$finish->addBody($finishButton);
			$finish->addBody($cancelButton);
			$this->addBody($finish);
			$this->_navigation[TWizardTemplate::ID_FINISH][] = $finish;
		}

	}

	/**
	 * Add the navigation side bar, a list of links to each step.
	 * The default navigation is added only if the templates for
	 * side bar are not present in the TWizard.
	 */
	private function addNavigationSideBar()
	{
		if(isset($this->_navigation[TWizardTemplate::ID_SIDEBAR]))
			return;

		$total = count($this->_steps);
		$current = $this->getActiveStepIndex();

		$sidebar = $this->createComponent('TPanel',TWizardTemplate::ID_SIDEBAR);
		$sidebar->CssClass = 'sidebar';

		if($total > 0) $sidebar->addBody("<ul>\n");
		for($i = 0; $i < $total; $i++)
		{
			if($this->_steps[$i]->Type == TWizardStep::TYPE_FINAL)
				continue;
			$sidebar->addBody("<li>");
			$link = $this->createComponent('TLinkButton');
			$link->setCommandName(self::CMD_JUMP);
			$link->setCommandParameter($i);
			$link->Text = $this->_steps[$i]->Title;
			$this->_sidebarLinks[] = $link;
			$sidebar->addBody($link);
			$sidebar->addBody("</li>\n");
		}
		if($total > 0) $sidebar->addBody("</ul>\n");

		$this->addBody($sidebar);
		$this->_navigation[TWizardTemplate::ID_SIDEBAR][] = $sidebar;
	}

	/**
	 * This method responds to a bubbled event. It will capture the event
	 * and fire the appropriate events, e.g. OnNextCommand if the parameter
	 * event name is "next". After the command event, a step changed event
	 * (OnStepChanged) is fire unless the event parameter variable $cancel
	 * is set to true.
	 * @param TComponent sender of the event
	 * @param TEventParameter event parameters
	 */
	public function onBubbleEvent($sender,$param)
	{
		//if false on validation, do nothing.
		if (!$this->Page->isValid()) return;

		$event = new TWizardCommandEventParameter();
		$event->currentStepIndex = $this->getActiveStepIndex();
		$event->nextStepIndex = $event->currentStepIndex;

		switch($param->name)
		{
			case self::CMD_NEXT:
				$event->nextStepIndex++;
				$this->raiseEvent('OnNextCommand',$this,$event);
				if(!$event->cancel)
				{
					$this->setActiveStepIndex($event->nextStepIndex);
					$this->raiseEvent('OnStepChanged',$this,$event);
				}
				break;
			case self::CMD_PREVIOUS:
				$event->nextStepIndex--;
				$this->raiseEvent('OnPreviousCommand',$this,$event);
				if(!$event->cancel)
				{
					$this->setActiveStepIndex($event->nextStepIndex);
					$this->raiseEvent('OnStepChanged',$this,$event);
				}
				break;
			case self::CMD_FINISH:
				if(isset($this->_steps[$event->nextStepIndex+1]))
					$event->nextStepIndex++;
				$this->raiseEvent('OnFinishCommand',$this,$event);
				if(!$event->cancel)
				{
					$this->setActiveStepIndex($event->nextStepIndex);
					$this->raiseEvent('OnStepChanged',$this,$event);
				}
				break;
			case self::CMD_CANCEL:
				$event->cancel = true;
				$this->raiseEvent('OnCancelCommand',$this,$event);
				break;
			case self::CMD_JUMP:
				$event->nextStepIndex = $param->parameter;
				$this->raiseEvent('OnJumpToCommand',$this,$event);
				if(!$event->cancel)
				{
					$this->setActiveStepIndex($event->nextStepIndex);
					$this->raiseEvent('OnStepChanged',$this,$event);
				}
				break;
		}
	}
}

/**
 * TWizard command event parameter.
 *
 * This is passed as the parameter to all event orginating from TWizard.
 * If the event was a particular OnXXXXCommand, the variable $cancel
 * determine if the step will be changed. e.g in handling the "next" command
 * setting the parameter, $param->cancel = true will not result in a step change.
 *
 * The parameter also contains the current step index, and the next step index.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail.com>
 * @version v1.0, last update on Sat Jan 22 13:59:56 EST 2005
 * @package System.Web.UI.WebControls
 */
class TWizardCommandEventParameter extends TEventParameter
{
	public $currentStepIndex = null;
	public $nextStepIndex = null;
	public $cancel = false;
}

?>