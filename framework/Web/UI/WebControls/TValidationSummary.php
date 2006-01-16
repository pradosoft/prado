<?php
/**
 * TValidationSummary class file
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Qiang Xue. All rights reserved.
 *
 * To contact the author write to {@link mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: 1.20 $  $Date: 2005/11/21 07:39:41 $
 * @package System.Web.UI.WebControls
 */

/**
 * TValidationSummary class
 *
 * TValidationSummary displays a summary of all validation errors inline on a Web page,
 * in a message box, or both. The summary can be displayed as a list, as a bulleted list,
 * or as a single paragraph based on the <b>DisplayMode</b> property.
 * The summary can be displayed on the Web page and in a message box by setting
 * the <b>ShowSummary</b> and <b>ShowMessageBox</b> properties, respectively.
 *
 * Namespace: System.Web.UI.WebControls
 *
 * Properties
 * - <b>DisplayMode</b>, string, default=BulletList, kept in viewstate
 *   <br>Gets or sets the display mode (BulletList, List, SingleParagraph) of the validation summary.
 * - <b>HeaderText</b>, string, kept in viewstate
 *   <br>Gets or sets the header text displayed at the top of the summary.
 * - <b>EnableClientScript</b>, boolean, default=true, kept in viewstate
 *   <br>Gets or sets a value indicating whether the TValidationSummary component
 *   updates itself using client-side script.
 * - <b>ShowMessageBox</b>, boolean, default=false, kept in viewstate
 *   <br>Gets or sets a value indicating whether the validation summary is displayed in a message box.
 *   If <b>EnableClientScript</b> is <b>false</b>, this property has no effect.
 * - <b>ShowSummary</b>, boolean, default=true, kept in viewstate
 *   <br>Gets or sets a value indicating whether the validation summary is displayed inline.
 * - <b>Group</b>, string, kept in viewstate
 *   <br>Gets or sets the validation group ID.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version v1.0, last update on 2004/08/13 21:44:52
 * @package System.Web.UI.WebControls
 */
class TValidationSummary extends TWebControl
{

	protected static $currentGroup;

	public static function setCurrentGroup($group)
	{
		self::$currentGroup = $group;
	}

	public static function getCurrentGroup()
	{
		return self::$currentGroup;
	}

	/**
	 * Overrides parent implementation to disable body addition.
	 * @param mixed the object to be added
	 * @return boolean
	 */
	public function allowBody($object)
	{
		return false;
	}

	/**
	 * @return string the header text displayed at the top of the summary
	 */
	public function getHeaderText()
	{
		return $this->getViewState('HeaderText','');
	}

	/**
	 * Sets the header text to be displayed at the top of the summary
	 * @param string the header text
	 */
	public function setHeaderText($value)
	{
		$this->setViewState('HeaderText',$value,'');
	}

	/**
	 * @return string the display mode (BulletList, List, SingleParagraph) of the validation summary.
	 */
	public function getDisplayMode()
	{
		return $this->getViewState('DisplayMode','BulletList');
	}

	/**
	 * Sets the display mode (BulletList, List, SingleParagraph) of the validation summary.
	 * @param string the display mode (BulletList, List, SingleParagraph)
	 */
	public function setDisplayMode($value)
	{
		if($value!='List' && $value!='SingleParagraph')
			$value='BulletList';
		$this->setViewState('DisplayMode',$value,'BulletList');
	}

	/**
	 * @return boolean whether the TValidationSummary component updates itself using client-side script.
	 */
	public function isClientScriptEnabled()
	{
		return $this->getViewState('EnableClientScript',true);
	}

	/**
	 * Sets the value whether the TValidationSummary component updates itself using client-side script.
	 * @param boolean whether the TValidationSummary component updates itself using client-side script.
	 */
	public function enableClientScript($value)
	{
		$this->setViewState('EnableClientScript',$value,true);
	}

	/**
	 * @return boolean whether the validation summary is displayed in a message box.
	 */
	public function isShowMessageBox()
	{
		return $this->getViewState('ShowMessageBox',false);
	}

	/**
	 * Sets the value whether the validation summary is displayed in a message box.
	 * @param boolean whether the validation summary is displayed in a message box.
	 */
	public function setShowMessageBox($value)
	{
		$this->setViewState('ShowMessageBox',$value,false);
	}

	/**
	 * @return boolean whether the validation summary is displayed inline.
	 */
	public function isShowSummary()
	{
		return $this->getViewState('ShowSummary',true);
	}

	/**
	 * Sets the value whether the validation summary is displayed inline.
	 * @param boolean whether the validation summary is displayed inline.
	 */
	public function setShowSummary($value)
	{
		$this->setViewState('ShowSummary',$value,true);
	}

	/**
	 * @return boolean whether the validation summary should be anchored.
	 */
	public function isShowAnchor()
	{
		return $this->getViewState('ShowAnchor',false);
	}

	/**
	 * Sets the value whether the validation summary should be anchored.
	 * @param boolean whether the validation summary should be anchored.
	 */
	public function setShowAnchor($value)
	{
		$this->setViewState('ShowAnchor',$value,false);
	}

	/**
	 * Gets the valiation group.
	 * @param string validation group ID.
	 */
	public function getGroup()
	{
		return $this->getViewState('Group', '');
	}

	/**
	 * Sets the validation group.
	 * @param string ID of the validation group.
	 */
	public function setGroup($value)
	{
		$this->setViewState('Group', $value, '');
	}

	/**
	 * Sets the summary to auto-update on the client-side
	 * @param boolean true for automatic summary updates.
	 */
	public function setAutoUpdate($value)
	{
		$this->setViewState('AutoUpdate', $value, true);
	}

	/**
	 * Gets the auto-update for this summary.
	 * @return boolean automatic client-side summary updates.
	 */
	public function isAutoUpdate()
	{
		return $this->getViewState('AutoUpdate', true);
	}

	/**
	 * @return string the group which this validator belongs to
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup','');
	}

	/**
	 * @param string the group which this validator belongs to
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup',$value,'');
	}

	/**
	 * Get a list of validators considering the validation groups.
	 * @return array list of validators.
	 */
	protected function getValidators()
	{
		$groupID = $this->getGroup();
		if(empty($groupID)) return $this->getPage()->getValidators();

		$parent = $this->getParent();
		$group = $parent->findObject($groupID);

		$validators = array();

		foreach($group->getMembers() as $member)
		{
			$control = $parent->findObject($member);
			if(!is_null($control))
				$validators[] = $control;
		}
		return $validators;
	}

	/**
	 * Render the javascript for validation summary.
	 * @param array list of options for validation summary.
	 */
	protected function renderJsSummary($options)
	{
		if(!$this->isEnabled() || !$this->isClientScriptEnabled())
			return;
		$option = TJavascript::toList($options);
		$script = "new Prado.Validation.Summary({$option});";
		$this->Page->registerEndScript($this->ClientID, $script);
	}

	/**
	 * Get a list of options for the client-side javascript validation summary.
	 * @return array list of options for the summary
	 */
	protected function getJsOptions()
	{
		$options['id'] = $this->ClientID;
		$options['form'] = $this->Page->Form->ClientID;
		if($this->isShowMessageBox())
			$options['showmessagebox']='True';
		if(!$this->isShowSummary())
			$options['showsummary']='False';

		$options['headertext']=$this->getHeaderText();
		$options['displaymode']=$this->getDisplayMode();

		$group = $this->getGroup();
		if(!empty($group))
			$options['group'] = $this->getParent()->findObject($group)->ClientID;

		$options['refresh'] = $this->isAutoUpdate();
		$options['validationgroup'] =  $this->getValidationGroup();
		return $options;
	}

	/**
	 * Get the list of validation error messages.
	 * @return array list of validator error messages.
	 */
	protected function getMessages()
	{
		$validators=$this->getValidators();
		$messages = array();
		foreach(array_keys($validators) as $i)
		{
			if(!$validators[$i]->isValid())
			{
				$msg = $validators[$i]->getErrorMessage();
				if(strlen($msg))
					$messages[] = $validators[$i]->getAnchoredMessage($msg);
			}
		}
		return $messages;
	}

	/**
	 * Overrides parent implementation by rendering TValidationSummary-specific presentation.
	 * @return string the rendering result
	 */
	public function render()
	{

		$this->renderJsSummary($this->getJsOptions());

		$content = "";
		if($this->isRenderSummary())
		{
		    $this->setStyle('display:block');
			$messages = $this->getMessages();
			$headerText = $this->getHeaderText();
			switch($this->getDisplayMode())
			{
				case 'List':
					$content = $this->renderList($messages, $headerText);
					break;
				case 'SingleParagraph':
					$content = $this->renderSingleParagraph($messages, $headerText);
					break;
				case 'BulletList':
				default:
					$content = $this->renderBulletList($messages, $headerText);
			}
		}
		return "<div {$this->renderAttributes()}>{$content}</div>";
	}

	protected function isRenderSummary()
	{
		$group = $this->getGroup();
		$active = TValidatorGroup::isGroupValidation() ? false : true;
		if(!empty($group))
			$active = $this->getParent()->findObject($group)->isActive();
		$render = $this->isEnabled() && $active;
		$render = $render && !$this->Page->isValid() && $this->isShowSummary();
		$current = self::getCurrentGroup();
		if(!is_null($current))
			$render = $render && $this->getValidationGroup() == $current;
		return $render;
	}

	/**
	 * Render the validation summary as a simple list.
	 * @param array list of messages
	 * @param string the header text
	 * @return string summary list
	 */
	protected function renderList($messages, $header)
	{
		$content = '';
		if(strlen($header))
			$content.= $header."<br/>\n";
		foreach($messages as $message)
			$content.="$message<br/>\n";
		return $content;
	}

	/**
	 * Render the validation summary as a paragraph.
	 * @param array list of messages
	 * @param string the header text
	 * @return string summary paragraph
	 */
	protected function renderSingleParagraph($messages, $header)
	{
		$content = $header;
		foreach($messages as $message)
			$content.= ' '.$message;
		return $content;
	}

	/**
	 * Render the validation summary as a bullet list.
	 * @param array list of messages
	 * @param string the header text
	 * @return string summary bullet list
	 */
	protected function renderBulletList($messages, $header)
	{
		$content = $header;
		$show = count($messages) > 0;
		if($show) $content .= "<ul>\n";
		foreach($messages as $message)
			$content.= '<li>'.$message."</li>\n";
		if($show) $content .= "</ul>\n";
		return $content;
	}
}

?>