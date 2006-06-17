<?php
/*
 * Created on 7/05/2006
 */

class TAutoComplete extends TActiveTextBox implements INamingContainer
{
	/**
	 * @var ITemplate template for repeater items
	 */
	private $_repeater=null;
	private $_resultPanel=null;
	
	public function getSeparator()
	{
		return $this->getViewState('tokens', '');
	}
	
	public function setSeparator($value)
	{
		$this->setViewState('tokens', TPropertyValue::ensureString($value), '');
	}
	
	public function getFrequency()
	{
		return $this->getViewState('frequency', '');
	}
	
	public function setFrequency($value)
	{
		$this->setViewState('frequency', TPropertyValue::ensureFloat($value),'');
	}
	
	public function getMinChars()
	{
		return $this->getViewState('minChars','');
	}
	
	public function setMinChars($value)
	{
		$this->setViewState('minChars', TPropertyValue::ensureInteger($value), '');
	}
	
	/**
	 * Raises the callback event. This method is required by {@link
	 * ICallbackEventHandler} interface. If {@link getCausesValidation
	 * CausesValidation} is true, it will invoke the page's {@link TPage::
	 * validate validate} method first. It will raise {@link onCallback
	 * OnCallback} event and then the {@link onClick OnClick} event. This method
	 * is mainly used by framework and control developers.
	 * @param TCallbackEventParameter the event parameter
	 */	
 	public function raiseCallbackEvent($param)
	{
		$this->onCallback($param);
	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter event parameter to be passed to the event handlers
	 */		
	public function onCallback($param)
	{
		$this->raiseEvent('OnCallback', $this, $param);
	}
		
	public function setDataSource($data)
	{
		$this->getSuggestions()->setDataSource($data);
	}
	
	public function getResultPanel()
	{
		if(is_null($this->_resultPanel))
			$this->_resultPanel = $this->createResultPanel();
		return $this->_resultPanel;
	}
	
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
	 * 
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

	public function renderEndTag($writer)
	{
		$this->getPage()->getClientScript()->registerPradoScript('effects');
		parent::renderEndTag($writer);
		$this->renderResultPanel($writer);
	}
	
	public function renderResultPanel($writer)
	{
		$this->getResultPanel()->render($writer);	
	}
	
	public function render($writer)
	{
		if($this->getPage()->getIsCallback())
		{
			if($this->getActiveControl()->canUpdateClientSide())
				$this->renderSuggestions($writer);
		}
		else
			parent::render($writer);	
	}

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
			$token = preg_split('//', $string, -1, PREG_SPLIT_NO_EMPTY);
			$options['tokens'] = TJavascript::encode($token,false);
		} 
		if($this->getAutoPostBack())
			$options = array_merge($options,$this->getPostBackOptions()); 
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
 * TWizardSideBarTemplate class.
 * TWizardSideBarTemplate is the default template for wizard sidebar.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
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