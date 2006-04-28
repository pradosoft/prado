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
 * TValidationSummary displays a summary of validation errors inline on a Web page,
 * in a message box, or both. By default, a validation summary will collect
 * {@link TBaseValidator::getErrorMessage ErrorMessage} of all failed validators
 * on the page. If {@link getValidationGroup ValidationGroup} is not
 * empty, only those validators who belong to the group will show their error messages
 * in the summary.
 *
 * The summary can be displayed as a list, as a bulleted list, or as a single
 * paragraph based on the {@link setDisplayMode DisplayMode} property.
 * The messages shown can be prefixed with {@link setHeaderText HeaderText}.
 *
 * The summary can be displayed on the Web page and in a message box by setting
 * the {@link setShowSummary ShowSummary} and {@link setShowMessageBox ShowMessageBox}
 * properties, respectively. Note, the latter is only effective when
 * {@link setEnableClientScript EnableClientScript} is true.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TValidationSummary extends TWebControl
{
	/**
	 * @var TValidatorClientScript validator client-script options.
	 */
	private $_clientScript;
	
	/**
	 * Constructor.
	 * This method sets the foreground color to red.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setForeColor('red');
	}

	/**
	 * @return string the display behavior (None, Static, Dynamic) of the error message in a validation summary component.
	 */
	public function getDisplay()
	{
		return $this->getViewState('Display','Static');
	}

	/**
	 * Sets the display behavior (None, Static, Dynamic) of the error message in a validation summary component.
	 * @param string the display behavior (None, Static, Dynamic)
	 */
	public function setDisplay($value)
	{
		$this->setViewState('Display',TPropertyValue::ensureEnum($value,'None','Dynamic','Static'),'Static');
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

	protected function addAttributesToRender($writer)
	{
		$display=$this->getDisplay();
		$visible=$this->getEnabled(true) && count($this->getErrorMessages()) > 0;
		if(!$visible)
		{
			if($display==='None' || $display==='Dynamic')
				$writer->addStyleAttribute('display','none');
			else
				$writer->addStyleAttribute('visibility','hidden');
		}
		$writer->addAttribute('id',$this->getClientID());
		parent::addAttributesToRender($writer);
	}

	/**
	 * Render the javascript for validation summary.
	 * @param array list of options for validation summary.
	 */
	protected function renderJsSummary()
	{
		if(!$this->getEnabled(true) || !$this->getEnableClientScript())
			return;
		$options=TJavaScript::encode($this->getClientScriptOptions());
		$script = "new Prado.WebUI.TValidationSummary({$options});";
		$this->getPage()->getClientScript()->registerEndScript($this->getClientID(), $script);
	}

	/**
	 * Get a list of options for the client-side javascript validation summary.
	 * @return array list of options for the summary
	 */
	protected function getClientScriptOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['FormID'] = $this->getPage()->getForm()->getClientID();
		if($this->getShowMessageBox())
			$options['ShowMessageBox']=true;
		if(!$this->getShowSummary())
			$options['ShowSummary']=false;

		$options['HeaderText']=$this->getHeaderText();
		$options['DisplayMode']=$this->getDisplayMode();

		$options['Refresh'] = $this->getAutoUpdate();
		$options['ValidationGroup'] =  $this->getValidationGroup();
		$options['Display'] = $this->getDisplay();
		
		if(!is_null($this->_clientScript))
			$options = array_merge($options,$this->_clientScript->getOptions());
					
		return $options;
	}

	/**
	 * @return TValidationSummaryClientScript client-side validation summary
	 * event options.
	 */
	public function getClientValidation()
	{
		if(is_null($this->_clientScript))
			$this->_clientScript = $this->createClientScript();
		return $this->_clientScript;
	}
	
	/**
	 * @return TValidationSummaryClientScript javascript validation summary
	 * event options.
	 */
	protected function createClientScript()
	{
		return new TValidationSummaryClientScript;
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
	public function renderContents($writer)
	{
		$this->renderJsSummary();
		if($this->getShowSummary())
		{
//		    $this->setStyle('display:block');
			switch($this->getDisplayMode())
			{
				case 'List':
					$this->renderList($writer);
					break;
				case 'SingleParagraph':
					$this->renderSingleParagraph($writer);
					break;
				case 'BulletList':
					$this->renderBulletList($writer);
					break;
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
		if(count($messages)>0)
		{
			$content .= "<ul>\n";
			foreach($messages as $message)
				$content.= '<li>'.$message."</li>\n";
			$content .= "</ul>\n";
		}
		$writer->write($content);
	}
}

/**
 * TValidationSummaryClientScript class.
 * 
 * Client-side validation summary events such as {@link setOnHideSummary
 * OnHideSummary} and {@link setOnShowSummary OnShowSummary} can be modified
 * through the {@link TBaseValidator:: getClientValidation ClientValidation}
 * property of a validation summary. 
 * 
 * The <tt>OnHideSummary</tt> event is raise when the validation summary
 * requests to hide the messages.
 * 
 * The <tt>OnShowSummary</tt> event is raised when the validation summary
 * requests to show the messages.
 * 
 * See the quickstart documentation for further details.
 * 
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TValidationSummaryClientScript extends TComponent
{
	/**
	 * @var TMap client-side validation summary event javascript code.
	 */
	private $_options;
	
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->_options = new TMap;
	}

	/**
	 * @return string javascript code for client-side OnHideSummary event.
	 */
	public function getOnHideSummary()
	{
		return $this->_options->itemAt['OnHideSummary'];	
	}
	
	/**
	 * Client-side OnHideSummary validation summary event is raise when all the
	 * validators are valid. This will override the default client-side
	 * validation summary behaviour.
	 * @param string javascript code for client-side OnHideSummary event.
	 */
	public function setOnHideSummary($javascript)
	{
		$this->_options->add('OnHideSummary', $this->ensureFunction($javascript));
	}
	
	/**
	 * Client-side OnShowSummary event is raise when one or more validators are
	 * not valid. This will override the default client-side validation summary
	 * behaviour.
	 * @param string javascript code for client-side OnShowSummary event.
	 */
	public function setOnShowSummary($javascript)
	{
		$this->_options->add('OnShowSummary', $this->ensureFunction($javascript));
	}
	
	/**
	 * @return string javascript code for client-side OnShowSummary event.
	 */
	public function getOnShowSummary()
	{
		return $this->_options->itemAt('OnShowSummary');
	}
	
	/**
	 * @return array list of client-side event code.
	 */
	public function getOptions()
	{
		return $this->_options->toArray();
	}
	
	/**
	 * Ensure the string is a valid javascript function. If the string begins
	 * with "javascript:" valid javascript function is assumed, otherwise the
	 * code block is enclosed with "function(summary, validators){ }" block.
	 * @param string javascript code.
	 * @return string javascript function code.
	 */
	private function ensureFunction($javascript)
	{
		if(TJavascript::isFunction($javascript))
			return $javascript;
		else
		{
			$code = "function(summary, validators){ {$javascript} }";
			return TJavascript::quoteFunction($code);
		}
	}	
}

?>