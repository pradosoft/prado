<?php
/**
 * TJuiSelectable class file.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2013-2015 PradoSoft
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package System.Web.UI.JuiControls
 */

Prado::using('System.Web.UI.JuiControls.TJuiControlAdapter');
Prado::using('System.Web.UI.ActiveControls.TActivePanel');

/**
 * TJuiSelectable class.
 *
 * TJuiSelectable is an extension to {@link TActivePanel} based on jQuery-UI's
 * {@link http://jqueryui.com/selectable/ Selectable} interaction.
 * TJuiSelectable can be feed a {@link setDataSource DataSource} and will interally
 * render a {@link TRepeater} that displays items in an unordered list.
 * Items can be selected by clicking on them, individually or in a group.
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
	 * @return string the name of the jQueryUI widget method
	 */
	public function getWidget()
	{
	  return 'selectable';
	}

	/**
	 * @return string the clientid of the jQueryUI widget element
	 */
	public function getWidgetID()
	{
	  return $this->getClientID() . '_0';
	}

	/**
	 * Object containing defined javascript options
	 * @return TJuiControlOptions
	 */
	public function getOptions()
	{
		if (($options=$this->getViewState('JuiOptions'))===null)
		{
		  $options=new TJuiControlOptions($this);
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
		// overload the "OnStop" event to add information about the current selected items
		if(isset($options['stop']))
		{
			$options['stop']=new TJavaScriptLiteral('function( event, ui ) { ui.index = new Array(); jQuery(\'#'.$this->getClientID().' .ui-selected\').each(function(idx, item){ ui.index.push(item.id) }); Prado.JuiCallback('.TJavascript::encode($this->getUniqueID()).', \'stop\', event, ui, this); }');
		}
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
		$code="jQuery('#".$this->getWidgetID()."').".$this->getWidget()."(".$options.");";
		$cs->registerEndScript(sprintf('%08X', crc32($code)), $code);
	}

	/**
	 * Raises callback event. This method is required by the {@link ICallbackEventHandler}
	 * interface.
	 * @param TCallbackEventParameter the parameter associated with the callback event
	 */
	public function raiseCallbackEvent($param)
	{
		$this->getOptions()->raiseCallbackEvent($param);
	}

	/**
	 * Raises the OnCreate event
	 * @param object $params event parameters
	 */
	public function onCreate ($params)
	{
		$this->raiseEvent('OnCreate', $this, $params);
	}

	/**
	 * Raises the OnSelected event
	 * @param object $params event parameters
	 */
	public function onSelected ($params)
	{
		$this->raiseEvent('OnSelected', $this, $params);
	}

	/**
	 * Raises the OnSelecting event
	 * @param object $params event parameters
	 */
	public function onSelecting ($params)
	{
		$this->raiseEvent('OnSelecting', $this, $params);
	}

	/**
	 * Raises the OnStart event
	 * @param object $params event parameters
	 */
	public function onStart ($params)
	{
		$this->raiseEvent('OnStart', $this, $params);
	}

	/**
	 * Raises the OnStop event
	 * @param object $params event parameters
	 */
	public function onStop ($params)
	{
		$this->raiseEvent('OnStop', $this, $params);
	}

	/**
	 * Raises the OnUnselected event
	 * @param object $params event parameters
	 */
	public function onUnselected ($params)
	{
		$this->raiseEvent('OnUnselected', $this, $params);
	}

	/**
	 * Raises the OnUnselecting event
	 * @param object $params event parameters
	 */
	public function onUnselecting ($params)
	{
		$this->raiseEvent('OnUnselecting', $this, $params);
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
		$repeater->setHeaderTemplate(new TJuiSelectableTemplate('<ul id="'.$this->getWidgetID().'">'));
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
