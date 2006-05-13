<?php
/*
 * Created on 7/05/2006
 */

class TAutoComplete extends TActiveTextBox implements ICallbackEventHandler, INamingContainer
{
	/**
	 * @var ITemplate template for repeater items
	 */
	private $_repeater=null;
	private $_resultPanel=null;
	
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

	/**
	 * @return TCallbackClientSideOptions callback client-side options.
	 */
	protected function createClientSideOptions()
	{
		if(($id=$this->getCallbackOptions())!=='' && ($control=$this->findControl($id))!==null)
		{
			if($control instanceof TCallbackOptions)
			{
				$options = clone($control->getClientSide());
				$options->setEnablePageStateUpdate(false);
				return $options;
			}
		}
		$options = new TAutoCompleteClientSideOptions;
		$options->setEnablePageStateUpdate(false);
		return $options;
	}
	
	/**
	 * Sets the ID of a TCallbackOptions component to duplicate the client-side
	 * options for this control. The {@link getClientSide ClientSide}
	 * subproperties has precendent over the CallbackOptions property.
	 * @param string ID of a TCallbackOptions control from which ClientSide
	 * options are cloned.
	 */
	public function setCallbackOptions($value)
	{
		$this->setViewState('CallbackOptions', $value,'');		
	}
	
	/**
	 * @return string ID of a TCallbackOptions control from which ClientSide
	 * options are cloned.
	 */
	public function getCallbackOptions()
	{
		return $this->getViewState('CallbackOptions','');
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
		if($this->canUpdateClientSide())
		{
			$this->getSuggestions()->render($writer); 
			$boundary = $writer->getWriter()->getBoundary();
			$writer->getWriter()->getResponse()->setData($boundary);
		}
		else
			parent::render($writer);	
	}
	
	/**
	 * @return array list of callback options.
	 */
	protected function getCallbackClientSideOptions()
	{
		$options = $this->getClientSide()->getOptions()->toArray();
		if(isset($options['tokens']))
			$options['tokens'] = TJavascript::encode($options['tokens'],false);
		if($this->getAutoPostBack())
			$options = array_merge($options,$this->getPostBackOptions()); 
		$options['ResultPanel'] = $this->getResultPanel()->getClientID();
		$options['ID'] = $this->getClientID();
		$options['EventTarget'] = $this->getUniqueID();
		return $options;
	}
	
	/**
	 * Adds attribute name-value pairs to renderer.
	 * This method overrides the parent implementation with additional textbox specific attributes.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$this->renderClientControlScript($writer);
	}
	
}

/**
 * Client-side options for TAutoComplete.
 * 
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TAutoCompleteClientSideOptions extends TCallbackClientSideOptions
{
	public function getSeparator()
	{
		return $this->getOption('tokens');
	}
	
	public function setSeparator($value)
	{
		$this->setOption('tokens', preg_split('//', $value, -1, PREG_SPLIT_NO_EMPTY));
	}
	
	public function getFrequency()
	{
		return $this->getOption('frequency');
	}
	
	public function setFrequency($value)
	{
		$this->setOption('frequency', TPropertyValue::ensureFloat($value));
	}
	
	public function getMinChars()
	{
		return 	$this->getOption('minChars');
	}
	
	public function setMinChars($value)
	{
		$this->setOption('minChars', TPropertyValue::ensureInteger($value));
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