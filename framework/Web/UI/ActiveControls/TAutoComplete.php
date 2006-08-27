<?php
/**
 * TAutoComplete class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  : $
 * @package System.Web.UI.ActiveControls
 */

/**
 * Load active text box.
 */
Prado::using('System.Web.UI.ActiveControls.TActiveTextBox');

/**
 * TAutoComplete class.
 *
 * TAutoComplete is a textbox that provides a list of suggestion on
 * the current partial word typed in the textbox. The suggestions are
 * requested using callbacks, and raises the {@link onSuggestion OnSuggestion}
 * event. The events of the TActiveText (from which TAutoComplete is extended from)
 * and {@link onSuggestion OnSuggestion} are mutually exculsive. That is,
 * if {@link onTextChange OnTextChange} and/or {@link onCallback OnCallback}
 * events are raise, then {@link onSuggestion OnSuggestion} will not be raise, and
 * vice versa.
 *
 * The list of suggestions should be set in the {@link onSuggestion OnSuggestion}
 * event handler. The partial word to match the suggestion is in the
 * {@link TCallbackEventParameter::getParameter TCallbackEventParameter::Parameter}
 * property. The datasource of the TAutoComplete must be set using {@link setDataSource}
 * method. This sets the datasource for the suggestions repeater, available through
 * the {@link getSuggestions Suggestions} property. Header, footer templates and
 * other properties of the repeater can be access via the {@link getSuggestions Suggestions}
 * property (e.g. they can be set in the .page templates).
 *
 * To return the list of suggestions back to the browser, in your {@link onSuggestion OnSuggestion}
 * event handler, do
 * <code>
 * function autocomplete_suggestion($sender, $param)
 * {
 *   $token = $param->getParameter(); //the partial word to match
 *   $sender->setDataSource($this->getSuggestionsFor($token)); //set suggestions
 *   $sender->dataBind();
 *   $sender->render($param->getNewWriter()); //sends suggestion back to browser.
 * }
 * </code>
 *
 * TAutoComplete allows multiple suggestions within one textbox with each
 * word or phrase separated by any characters specified in the
 * {@link setSeparator Separator} property. The {@link setFrequency Frequency}
 * and {@link setMinChars MinChars} properties sets the delay and minimum number
 * of characters typed, respectively, before requesting for sugggestions.
 *
 * Use {@link onTextChange OnTextChange} and/or {@link onCallback OnCallback} events
 * to handle post backs due to {@link setAutoPostBack AutoPostBack}.
 *
 * In the {@link getSuggestions Suggestions} TRepater item template, all HTML text elements
 * are considered as text for the suggestion. Text within HTML elements with CSS class name
 * "informal" are ignored as text for suggestions.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  Mon Jun 19 03:50:05 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TAutoComplete extends TActiveTextBox implements INamingContainer
{
	/**
	 * @var ITemplate template for repeater items
	 */
	private $_repeater=null;
	/**
	 * @var TPanel result panel holding the suggestion items.
	 */
	private $_resultPanel=null;

	/**
	 * @return string word or token separators (delimiters).
	 */
	public function getSeparator()
	{
		return $this->getViewState('tokens', '');
	}

	/**
	 * @return string word or token separators (delimiters).
	 */
	public function setSeparator($value)
	{
		$this->setViewState('tokens', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return float maximum delay (in seconds) before requesting a suggestion.
	 */
	public function getFrequency()
	{
		return $this->getViewState('frequency', '');
	}

	/**
	 * @param float maximum delay (in seconds) before requesting a suggestion.
	 * Default is 0.4.
	 */
	public function setFrequency($value)
	{
		$this->setViewState('frequency', TPropertyValue::ensureFloat($value),'');
	}

	/**
	 * @return integer minimum number of characters before requesting a suggestion.
	 */
	public function getMinChars()
	{
		return $this->getViewState('minChars','');
	}

	/**
	 * @param integer minimum number of characters before requesting a suggestion.
	 */
	public function setMinChars($value)
	{
		$this->setViewState('minChars', TPropertyValue::ensureInteger($value), '');
	}

	/**
	 * Raises the callback event. This method is overrides the parent implementation.
	 * If {@link setAutoPostBack AutoPostBack} is enabled it will raise
	 * {@link onTextChanged OnTextChanged} event event and then the
	 * {@link onCallback OnCallback} event. The {@link onSuggest OnSuggest} event is
	 * raise if the request is to find sugggestions, the {@link onTextChanged OnTextChanged}
	 * and {@link onCallback OnCallback} events are <b>NOT</b> raised.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter the event parameter
	 */
 	public function raiseCallbackEvent($param)
	{
		$token = $param->getParameter();
		if(is_array($token) && count($token) == 2 && $token[1] === '__TAutoComplete_onSuggest__')
		{
			$parameter = new TCallbackEventParameter($this->getResponse(), $token[0]);
			$this->onSuggest($parameter);
		}
		else if($this->getAutoPostBack())
			parent::raiseCallbackEvent($param);
	}

	/**
	 * This method is invoked when a autocomplete suggestion is requested.
	 * The method raises 'OnSuggest' event. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter event parameter to be passed to the event handlers
	 */
	public function onSuggest($param)
	{
		$this->raiseEvent('OnSuggest', $this, $param);
	}

	/**
	 * @param array data source for suggestions.
	 */
	public function setDataSource($data)
	{
		$this->getSuggestions()->setDataSource($data);
	}

	/**
	 * @return TPanel suggestion results panel.
	 */
	public function getResultPanel()
	{
		if(is_null($this->_resultPanel))
			$this->_resultPanel = $this->createResultPanel();
		return $this->_resultPanel;
	}

	/**
	 * @return TPanel new instance of result panel. Default uses TPanel.
	 */
	protected function createResultPanel()
	{
		$panel = Prado::createComponent('System.Web.UI.WebControls.TPanel');
		$this->getControls()->add($panel);
		$panel->setID('result');
		return $panel;
	}

	/**
	 * @return TRepeater suggestion list repeater
	 */
	public function getSuggestions()
	{
		if(is_null($this->_repeater))
			$this->_repeater = $this->createRepeater();
		return $this->_repeater;
	}

	/**
	 * @return TRepeater new instance of TRepater to render the list of suggestions.
	 */
	protected function createRepeater()
	{
		$repeater = Prado::createComponent('System.Web.UI.WebControls.TRepeater');
		$repeater->setHeaderTemplate(new TAutoCompleteTemplate('<ul>'));
		$repeater->setFooterTemplate(new TAutoCompleteTemplate('</ul>'));
		$repeater->setItemTemplate(new TTemplate('<li><%# $this->DataItem %></li>',null));
		$this->getControls()->add($repeater);
		return $repeater;
	}

	/**
	 * Renders the end tag and registers javascript effects library.
	 */
	public function renderEndTag($writer)
	{
		$this->getPage()->getClientScript()->registerPradoScript('effects');
		parent::renderEndTag($writer);
		$this->renderResultPanel($writer);
	}

	/**
	 * Renders the result panel.
	 * @param THtmlWriter the renderer.
	 */
	protected function renderResultPanel($writer)
	{
		$this->getResultPanel()->render($writer);
	}

	/**
	 * Flush and returns the suggestions content back to the browser client.
	 * @param THtmlWriter the renderer.
	 */
	public function render($writer)
	{
		if(!$this->getPage()->getIsCallback())
			parent::render($writer);
		if($this->getActiveControl()->canUpdateClientSide())
				$this->renderSuggestions($writer);
	}

	/**
	 * Renders the suggestions repeater.
	 * @param THtmlWriter the renderer.
	 */
	protected function renderSuggestions($writer)
	{
		if($this->getSuggestions()->getItems()->getCount() > 0)
		{
			$this->getSuggestions()->render($writer);
			$boundary = $writer->getWriter()->getBoundary();
			$this->getResponse()->getAdapter()->setResponseData($boundary);
		}
	}

	/**
	 * @return array list of callback options.
	 */
	protected function getAutoCompleteOptions()
	{
		$this->getActiveControl()->getClientSide()->setEnablePageStateUpdate(false);
		if(strlen($string = $this->getSeparator()))
		{
			$string = strtr($string,array('\t'=>"\t",'\n'=>"\n",'\r'=>"\r"));
			$token = preg_split('//', $string, -1, PREG_SPLIT_NO_EMPTY);
			$options['tokens'] = TJavascript::encode($token,false);
		}
		if($this->getAutoPostBack())
		{
			$options = array_merge($options,$this->getPostBackOptions());
			$options['AutoPostBack'] = true;
		}
		$options['ResultPanel'] = $this->getResultPanel()->getClientID();
		$options['ID'] = $this->getClientID();
		$options['EventTarget'] = $this->getUniqueID();
		return $options;
	}

	/**
	 * Override parent implementation, no javascript is rendered here instead
	 * the javascript required for active control is registered in {@link addAttributesToRender}.
	 */
	protected function renderClientControlScript($writer)
	{
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control.
	 */

	public function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id',$this->getClientID());
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(), $this->getAutoCompleteOptions());
	}

	/**
	 * @return string corresponding javascript class name for this TActiveButton.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TAutoComplete';
	}
}

/**
 * TAutoCompleteTemplate class.
 *
 * TAutoCompleteTemplate is the default template for TAutoCompleteTemplate
 * item template.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  Mon Jun 19 03:50:05 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TAutoCompleteTemplate extends TComponent implements ITemplate
{
	private $_template;

	public function __construct($template)
	{
		$this->_template = $template;
	}
	/**
	 * Instantiates the template.
	 * It creates a {@link TDataList} control.
	 * @param TControl parent to hold the content within the template
	 */
	public function instantiateIn($parent)
	{
		$parent->getControls()->add($this->_template);
	}
}

?>