<?php
/**
 * TJuiAutoComplete class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
 */

namespace Prado\Web\UI\JuiControls;

/**
 * Load active text box.
 */
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\ActiveControls\TActiveTextBox;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\WebControls\TRepeater;
use Prado\Web\UI\WebControls\TPanel;

/**
 * TJuiAutoComplete class.
 *
 * TJuiAutoComplete is a textbox that provides a list of suggestion on
 * the current partial word typed in the textbox. The suggestions are
 * requested using callbacks, and raises the {@link onSuggestion OnSuggestion}
 * event. The events of the TActiveText (from which TJuiAutoComplete is extended from)
 * and {@link onSuggestion OnSuggestion} are mutually exculsive. That is,
 * if {@link onTextChange OnTextChange} and/or {@link onCallback OnCallback}
 * events are raise, then {@link onSuggestion OnSuggestion} will not be raise, and
 * vice versa.
 *
 * The list of suggestions should be set in the {@link onSuggestion OnSuggestion}
 * event handler. The partial word to match the suggestion is in the
 * {@link TCallbackEventParameter::getCallbackParameter TCallbackEventParameter::CallbackParameter}
 * property. The datasource of the TJuiAutoComplete must be set using {@link setDataSource}
 * method. This sets the datasource for the suggestions repeater, available through
 * the {@link getSuggestions Suggestions} property. Header, footer templates and
 * other properties of the repeater can be access via the {@link getSuggestions Suggestions}
 * property and its sub-properties.
 *
 * The {@link setTextCssClass TextCssClass} property if set is used to find
 * the element within the Suggestions.ItemTemplate and Suggestions.AlternatingItemTemplate
 * that contains the actual text for the suggestion selected. That is,
 * only text inside elements with CSS class name equal to {@link setTextCssClass TextCssClass}
 * will be used as suggestions.
 *
 * To return the list of suggestions back to the browser, supply a non-empty data source
 * and call databind. For example,
 * <code>
 * function autocomplete_suggestion($sender, $param)
 * {
 *   $token = $param->getToken(); //the partial word to match
 *   $sender->setDataSource($this->getSuggestionsFor($token)); //set suggestions
 *   $sender->dataBind();
 * }
 * </code>
 *
 * The suggestion will be rendered when the {@link dataBind()} method is called
 * <strong>during a callback request</strong>.
 *
 * When an suggestion is selected, that is, when the use has clicked, pressed
 * the "Enter" key, or pressed the "Tab" key, the {@link onSuggestionSelected OnSuggestionSelected}
 * event is raised. The
 * {@link TCallbackEventParameter::getCallbackParameter TCallbackEventParameter::CallbackParameter}
 * property contains the index of the selected suggestion.
 *
 * TJuiAutoComplete allows multiple suggestions within one textbox with each
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
 * @package Prado\Web\UI\JuiControls
 * @since 3.1
 */
class TJuiAutoComplete extends TActiveTextBox implements INamingContainer, IJuiOptions
{
	/**
	 * @var ITemplate template for repeater items
	 */
	private $_repeater;
	/**
	 * @var TPanel result panel holding the suggestion items.
	 */
	private $_resultPanel;

	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TJuiControlAdapter($this));
	}

	/**
	 * @return string the name of the jQueryUI widget method
	 */
	public function getWidget()
	{
		return 'autocomplete';
	}

	/**
	 * @return string the clientid of the jQueryUI widget element
	 */
	public function getWidgetID()
	{
		return $this->getClientID();
	}

	/**
	 * Object containing defined javascript options
	 * @return TJuiControlOptions
	 */
	public function getOptions()
	{
		if (($options = $this->getViewState('JuiOptions')) === null) {
			$options = new TJuiControlOptions($this);
			$this->setViewState('JuiOptions', $options);
		}
		return $options;
	}

	/**
	 * Array containing valid javascript options
	 * @return array()
	 */
	public function getValidOptions()
	{
		return ['appendTo', 'autoFocus', 'delay', 'disabled', 'minLength', 'position', 'source'];
	}

	/**
	 * Array containing valid javascript events
	 * @return array()
	 */
	public function getValidEvents()
	{
		return [];
	}

	/**
	 * @param string $value Css class name of the element to use for suggestion.
	 */
	public function setTextCssClass($value)
	{
		$this->setViewState('TextCssClass', $value);
	}

	/**
	 * @return string Css class name of the element to use for suggestion.
	 */
	public function getTextCssClass()
	{
		return $this->getViewState('TextCssClass');
	}


	/**
	 * @return string word or token separators (delimiters).
	 */
	public function getSeparator()
	{
		return $this->getViewState('separator', '');
	}

	/**
	 * @param string $value word or token separators (delimiters).
	 */
	public function setSeparator($value)
	{
		$this->setViewState('separator', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return float maximum delay (in seconds) before requesting a suggestion.
	 */
	public function getFrequency()
	{
		return $this->getViewState('frequency', 0.4);
	}

	/**
	 * @param float $value maximum delay (in seconds) before requesting a suggestion.
	 * Default is 0.4.
	 */
	public function setFrequency($value)
	{
		$this->setViewState('frequency', TPropertyValue::ensureFloat($value), 0.4);
	}

	/**
	 * @return int minimum number of characters before requesting a suggestion.
	 */
	public function getMinChars()
	{
		return $this->getViewState('minChars', '');
	}

	/**
	 * @param int $value minimum number of characters before requesting a suggestion.
	 * Default is 1
	 */
	public function setMinChars($value)
	{
		$this->setViewState('minChars', TPropertyValue::ensureInteger($value), 1);
	}

	/**
	 * Raises the callback event. This method is overrides the parent implementation.
	 * If {@link setAutoPostBack AutoPostBack} is enabled it will raise
	 * {@link onTextChanged OnTextChanged} event event and then the
	 * {@link onCallback OnCallback} event. The {@link onSuggest OnSuggest} event is
	 * raise if the request is to find sugggestions, the {@link onTextChanged OnTextChanged}
	 * and {@link onCallback OnCallback} events are <b>NOT</b> raised.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		$token = $param->getCallbackParameter();
		if (is_array($token) && count($token) == 2) {
			if ($token[1] === '__TJuiAutoComplete_onSuggest__') {
				$parameter = new TJuiAutoCompleteEventParameter($this->getResponse(), $token[0]);
				$this->onSuggest($parameter);
			} elseif ($token[1] === '__TJuiAutoComplete_onSuggestionSelected__') {
				$parameter = new TJuiAutoCompleteEventParameter($this->getResponse(), null, $token[0]);
				$this->onSuggestionSelected($parameter);
			}
		} elseif ($this->getAutoPostBack()) {
			parent::raiseCallbackEvent($param);
		}
	}

	/**
	 * This method is invoked when an autocomplete suggestion is requested.
	 * The method raises 'OnSuggest' event. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onSuggest($param)
	{
		$this->raiseEvent('OnSuggest', $this, $param);
	}

	/**
	 * This method is invoked when an autocomplete suggestion is selected.
	 * The method raises 'OnSuggestionSelected' event. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onSuggestionSelected($param)
	{
		$this->raiseEvent('OnSuggestionSelected', $this, $param);
	}

	/**
	 * @param array $data data source for suggestions.
	 */
	public function setDataSource($data)
	{
		$this->getSuggestions()->setDataSource($data);
	}

	/**
	 * Overrides parent implementation. Callback {@link renderSuggestions()} when
	 * page's IsCallback property is true.
	 */
	public function dataBind()
	{
		parent::dataBind();
		if ($this->getPage()->getIsCallback()) {
			$this->renderSuggestions($this->getResponse()->createHtmlWriter());
		}
	}

	/**
	 * @return TPanel suggestion results panel.
	 */
	public function getResultPanel()
	{
		if ($this->_resultPanel === null) {
			$this->_resultPanel = $this->createResultPanel();
		}
		return $this->_resultPanel;
	}

	/**
	 * @return TPanel new instance of result panel. Default uses TPanel.
	 */
	protected function createResultPanel()
	{
		$panel = new TPanel;
		$this->getControls()->add($panel);
		$panel->setID('result');
		return $panel;
	}

	/**
	 * @return TRepeater suggestion list repeater
	 */
	public function getSuggestions()
	{
		if ($this->_repeater === null) {
			$this->_repeater = $this->createRepeater();
		}
		return $this->_repeater;
	}

	/**
	 * @return TRepeater new instance of TRepater to render the list of suggestions.
	 */
	protected function createRepeater()
	{
		$repeater = new TRepeater;
		$repeater->setItemTemplate(new TTemplate('<%# $this->Data %>', null));
		$this->getControls()->add($repeater);
		return $repeater;
	}

	/**
	 * Renders the end tag and registers javascript effects library.
	 * @param mixed $writer
	 */
	public function renderEndTag($writer)
	{
		parent::renderEndTag($writer);
		$this->renderResultPanel($writer);
	}

	/**
	 * Renders the result panel.
	 * @param THtmlWriter $writer the renderer.
	 */
	protected function renderResultPanel($writer)
	{
		$this->getResultPanel()->render($writer);
	}

	/**
	 * Renders the suggestions during a callback respones.
	 * @param THtmlWriter $writer the renderer.
	 */
	public function renderCallback($writer)
	{
		$this->renderSuggestions($writer);
	}

	/**
	 * Renders the suggestions repeater.
	 * @param THtmlWriter $writer the renderer.
	 */
	public function renderSuggestions($writer)
	{
		if ($this->getActiveControl()->canUpdateClientSide(true)) {
			$data = [];
			$items = $this->getSuggestions()->getItems();
			$writer = new TTextWriter;
			for ($i = 0; $i < $items->Count; $i++) {
				$items->itemAt($i)->render($writer);
				$item = $writer->flush();
				$data[] = ['id' => $i, 'label' => $item];
			}

			$this->getResponse()->getAdapter()->setResponseData($data);
		}
	}

	/**
	 * @return array list of callback options.
	 */
	protected function getPostBackOptions()
	{
		$options = $this->getOptions()->toArray();

		if (strlen($separator = $this->getSeparator())) {
			$options['Separators'] = $separator;
		}

		if ($this->getAutoPostBack()) {
			$options = array_merge($options, parent::getPostBackOptions());
			$options['AutoPostBack'] = true;
		}
		if (strlen($textCssClass = $this->getTextCssClass())) {
			$options['textCssClass'] = $textCssClass;
		}
		$options['minLength'] = $this->getMinChars();
		$options['delay'] = $this->getFrequency() * 1000.0;
		$options['appendTo'] = '#' . $this->getResultPanel()->getClientID();
		$options['ID'] = $this->getClientID();
		$options['EventTarget'] = $this->getUniqueID();
		$options['CausesValidation'] = $this->getCausesValidation();
		$options['ValidationGroup'] = $this->getValidationGroup();
		return $options;
	}

	/**
	 * Override parent implementation, no javascript is rendered here instead
	 * the javascript required for active control is registered in {@link addAttributesToRender}.
	 * @param mixed $writer
	 */
	protected function renderClientControlScript($writer)
	{
	}

	/**
	 * @return string corresponding javascript class name for this TActiveButton.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TJuiAutoComplete';
	}
}
