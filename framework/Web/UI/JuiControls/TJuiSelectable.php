<?php
/**
 * TJuiSelectable class file.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2013-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.JuiControls
 */

Prado::using('System.Web.UI.JuiControls.TJuiControlAdapter');
Prado::using('System.Web.UI.ActiveControls.TActivePanel');

/**
 * TJuiSelectable class.
 *
 *
 * <code>
 * <style>
 * .ui-selecting { background: #FECA40; }
 * .ui-selected { background: #F39814; color: white; }
 * </style>
 * <com:TJuiSelectable ID="repeater1" />
 * </code>
 *
 * <code>
 * $this->repeater1->DataSource=array('home', 'office', 'car', 'boat', 'plane');
 * $this->repeater1->dataBind();
 * </code>
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package System.Web.UI.JuiControls
 * @since 3.3
 */
class TJuiSelectable extends TActivePanel implements IJuiOptions, ICallbackEventHandler
{
	protected $_options;

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
	 * Object containing defined javascript options
	 * @return TJuiControlOptions
	 */
	public function getOptions()
	{
		if($this->_options===null)
			$this->_options=new TJuiControlOptions($this);
		return $this->_options;
	}

	/**
	 * Array containing valid javascript options
	 * @return array()
	 */
	public function getValidOptions()
	{
		return array('appendTo', 'autoRefresh', 'cancel', 'delay', 'disabled', 'distance', 'filter', 'tolerance');
	}

	/**
	 * Array containing valid javascript events
	 * @return array()
	 */
	public function getValidEvents()
	{
		return array('create', 'selected', 'selecting', 'start', 'stop', 'unselected', 'unselecting');
	}

	/**
	 * @return array list of callback options.
	 */
	protected function getPostBackOptions()
	{
		$options = $this->getOptions()->toArray();
		$options['stop'] = new TJavaScriptLiteral('function( event, ui ) { var selected = new Array(); jQuery(\'#'.$this->getClientID().' .ui-selected\').each(function(idx, item){ selected.push(item.id) }); Prado.Callback('.TJavascript::encode($this->getUniqueID()).', { \'indexes\' : selected }) }');
		return $options;
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control.
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id',$this->getClientID());
		$options=TJavascript::encode($this->getPostBackOptions());
		$cs=$this->getPage()->getClientScript();
		$code="jQuery('#".$this->getClientId()."_0').selectable(".$options.");";
		$cs->registerEndScript(sprintf('%08X', crc32($code)), $code);
	}

	/**
	 * Raises callback event. This method is required bu {@link ICallbackEventHandler}
	 * interface.
	 * It raises the {@link onSelectedIndexChanged onSelectedIndexChanged} event, then, the {@link onCallback OnCallback} event
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter the parameter associated with the callback event
	 */
	public function raiseCallbackEvent($param)
	{
		$this->onSelectedIndexChanged($param->getCallbackParameter());
		$this->onCallback($param);
	}

	/**
	 * Raises the onSelect event.
	 * The selection parameters are encapsulated into a {@link TJuiSelectableEventParameter}
	 *
	 * @param object $params
	 */
	public function onSelectedIndexChanged($params)
	{
		$this->raiseEvent('onSelectedIndexChanged', $this, new TJuiSelectableEventParameter ($this->getResponse(), $params));

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

	/**
	 * @var ITemplate template for repeater items
	 */
	private $_repeater=null;

	/**
	 * @param array data source for Selectables.
	 */
	public function setDataSource($data)
	{
		$this->getSelectables()->setDataSource($data);
	}

	/**
	 * Overrides parent implementation. Callback {@link renderSelectables()} when
	 * page's IsCallback property is true.
	 */
	public function dataBind()
	{
		parent::dataBind();
		if($this->getPage()->getIsCallback())
			$this->renderSelectables($this->getResponse()->createHtmlWriter());
	}

	/**
	 * @return TRepeater suggestion list repeater
	 */
	public function getSelectables()
	{
		if($this->_repeater===null)
			$this->_repeater = $this->createRepeater();
		return $this->_repeater;
	}

	/**
	 * @return TRepeater new instance of TRepater to render the list of Selectables.
	 */
	protected function createRepeater()
	{
		$repeater = Prado::createComponent('System.Web.UI.WebControls.TRepeater');
		$repeater->setHeaderTemplate(new TJuiSelectableTemplate('<ul id="'.$this->getClientId().'_0'.'">'));
		$repeater->setFooterTemplate(new TJuiSelectableTemplate('</ul>'));
		$repeater->setItemTemplate(new TTemplate('<li id="<%# $this->ItemIndex %>"><%# $this->DataItem %></li>',null));
		$repeater->setEmptyTemplate(new TJuiSelectableTemplate('<ul></ul>'));
		$this->getControls()->add($repeater);
		return $repeater;
	}
}


/**
 * TJuiSelectableTemplate class.
 *
 * TJuiSelectableTemplate is the default template for TJuiSelectableTemplate
 * item template.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TJuiSelectableTemplate extends TComponent implements ITemplate
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

/**
 * TJuiSelectableEventParameter class
 *
 * TJuiSelectableEventParameter encapsulate the parameter
 * data for <b>OnSelectedIndexChanged</b> event of TJuiSelectable components
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @license http://www.pradosoft.com/license
 * @package System.Web.UI.JuiControls
 */
class TJuiSelectableEventParameter extends TCallbackEventParameter
{
	public function getSelectedIndexes()		{ return $this->getCallbackParameter()->indexes; }
}