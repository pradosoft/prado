<?php
/**
 * TValidationSummary class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
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
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
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
	 * @return string the display mode (BulletList, List, SingleParagraph) of the validation summary. Defaults to BulletList.
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
		$this->setViewState('DisplayMode',TPropertyValue::ensureEnum($value,'List','SingleParagraph','BulletList'),'BulletList');
	}

	/**
	 * @return boolean whether the TValidationSummary component updates itself using client-side script. Defaults to true.
	 */
	public function getEnableClientScript()
	{
		return $this->getViewState('EnableClientScript',true);
	}

	/**
	 * @param boolean whether the TValidationSummary component updates itself using client-side script.
	 */
	public function setEnableClientScript($value)
	{
		$this->setViewState('EnableClientScript',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return boolean whether the validation summary is displayed in a message box. Defaults to false.
	 */
	public function getShowMessageBox()
	{
		return $this->getViewState('ShowMessageBox',false);
	}

	/**
	 * @param boolean whether the validation summary is displayed in a message box.
	 */
	public function setShowMessageBox($value)
	{
		$this->setViewState('ShowMessageBox',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean whether the validation summary is displayed inline. Defaults to true.
	 */
	public function getShowSummary()
	{
		return $this->getViewState('ShowSummary',true);
	}

	/**
	 * @param boolean whether the validation summary is displayed inline.
	 */
	public function setShowSummary($value)
	{
		$this->setViewState('ShowSummary',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return boolean whether the validation summary should be anchored. Defaults to false.
	 */
	public function getShowAnchor()
	{
		return $this->getViewState('ShowAnchor',false);
	}

	/**
	 * @param boolean whether the validation summary should be anchored.
	 */
	public function setShowAnchor($value)
	{
		$this->setViewState('ShowAnchor',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * Gets the auto-update for this summary.
	 * @return boolean automatic client-side summary updates. Defaults to true.
	 */
	public function getAutoUpdate()
	{
		return $this->getViewState('AutoUpdate', true);
	}

	/**
	 * Sets the summary to auto-update on the client-side
	 * @param boolean true for automatic summary updates.
	 */
	public function setAutoUpdate($value)
	{
		$this->setViewState('AutoUpdate', TPropertyValue::ensureBoolean($value), true);
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
	protected function renderJsSummary()
	{
		if(!$this->getEnabled(true) || !$this->getEnableClientScript())
			return;
		$options = TJavascript::toList($this->getClientScriptOptions());
		$script = "new Prado.Validation.Summary({$options});";
		$this->getPage()->getClientScript()->registerEndScript($this->getClientID(), $script);
	}

	/**
	 * Get a list of options for the client-side javascript validation summary.
	 * @return array list of options for the summary
	 */
	protected function getClientScriptOptions()
	{
		$options['id'] = $this->ClientID;
		$options['form'] = $this->Page->Form->ClientID;
		if($this->getShowMessageBox())
			$options['showmessagebox']='True';
		if(!$this->getShowSummary())
			$options['showsummary']='False';

		$options['headertext']=$this->getHeaderText();
		$options['displaymode']=$this->getDisplayMode();

		$options['refresh'] = $this->getAutoUpdate();
		$options['validationgroup'] =  $this->getValidationGroup();
		return $options;
	}

	/**
	 * Get the list of validation error messages.
	 * @return array list of validator error messages.
	 */
	protected function getErrorMessages()
	{
		$validators=$this->getPage()->getValidators($this->getValidationGroup());
		$messages = array();
		foreach($validators as $validator)
		{
			if(!$validator->getIsValid() && ($msg=$validator->getErrorMessage())!=='')
				//$messages[] = $validator->getAnchoredMessage($msg);
				$messages[] = $msg;
		}
		return $messages;
	}

	/**
	 * Overrides parent implementation by rendering TValidationSummary-specific presentation.
	 * @return string the rendering result
	 */
	protected function renderContents($writer)
	{
		$this->renderJsSummary();
		if($this->getShowSummary())
		{
//		    $this->setStyle('display:block');
			switch($this->getDisplayMode())
			{
				case 'List':
					$content = $this->renderList($writer);
					break;
				case 'SingleParagraph':
					$content = $this->renderSingleParagraph($writer);
					break;
				case 'BulletList':
				default:
					$content = $this->renderBulletList($writer);
			}
		}
	}

	/**
	 * Render the validation summary as a simple list.
	 * @param array list of messages
	 * @param string the header text
	 * @return string summary list
	 */
	protected function renderList($writer)
	{
		$header=$this->getHeaderText();
		$messages=$this->getErrorMessages();
		$content = '';
		if(strlen($header))
			$content.= $header."<br/>\n";
		foreach($messages as $message)
			$content.="$message<br/>\n";
		$writer->write($content);
	}

	/**
	 * Render the validation summary as a paragraph.
	 * @param array list of messages
	 * @param string the header text
	 * @return string summary paragraph
	 */
	protected function renderSingleParagraph($writer)
	{
		$header=$this->getHeaderText();
		$messages=$this->getErrorMessages();
		$content = $header;
		foreach($messages as $message)
			$content.= ' '.$message;
		$writer->write($content);
	}

	/**
	 * Render the validation summary as a bullet list.
	 * @param array list of messages
	 * @param string the header text
	 * @return string summary bullet list
	 */
	protected function renderBulletList($writer)
	{
		$header=$this->getHeaderText();
		$messages=$this->getErrorMessages();
		$content = $header;
		$show = count($messages) > 0;
		if($show) $content .= "<ul>\n";
		foreach($messages as $message)
			$content.= '<li>'.$message."</li>\n";
		if($show) $content .= "</ul>\n";
		$writer->write($content);
	}
}

?>