<?php
/**
 * TJuiSortable class file.
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
 * TJuiSortable class.
 *
 * TJuiSortable is an extension to {@link TActivePanel} based on jQuery-UI's
 * {@link http://jqueryui.com/sortable/ Sortable} interaction.
 * The panel can be feed a {@link setDataSource DataSource} and will interally
 * render a {@link TRepeater} that displays items in an unordered list.
 * Items can be sortered dragging and dropping them.
 *
 * <code>
 * <com:TJuiSortable ID="repeater1" />
 * </code>
 *
 * <code>
 * $this->repeater1->DataSource=array('home', 'office', 'car', 'boat', 'plane');
 * $this->repeater1->dataBind();
 * </code>
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package System.Web.UI.JuiControls
 * @since 3.3
 */
class TJuiSortable extends TActivePanel implements IJuiOptions, ICallbackEventHandler
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
	  return 'sortable';
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
		return array('appendTo', 'axis', 'cancel', 'connectWith', 'containment', 'cursor', 'cursorAt', 'delay', 'disabled', 'distance', 'dropOnEmpty', 'forceHelperSize', 'forcePlaceholderSize', 'grid', 'handle', 'helper', 'items', 'opacity', 'placeholder', 'revert', 'scroll', 'scrollSensitivity', 'scrollSpeed', 'tolerance', 'zIndex');
	}

	/**
	 * Array containing valid javascript events
	 * @return array()
	 */
	public function getValidEvents()
	{
		return array('activate', 'beforeStop', 'change', 'create', 'deactivate', 'out', 'over', 'receive', 'remove', 'sort', 'start', 'stop', 'update');
	}

	/**
	 * @return array list of callback options.
	 */
	protected function getPostBackOptions()
	{
		$options = $this->getOptions()->toArray();
		// overload some events to add information about the items order
		foreach($options as $event => $implementation)
		{
			if($event=='sort' || $event=='stop')
				$options[$event]=new TJavaScriptLiteral('function( event, ui ) { ui.index = jQuery(this).sortable(\'toArray\'); Prado.JuiCallback('.TJavascript::encode($this->getUniqueID()).', \''.$event.'\', event, ui, this); }');
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
	 * Raises the OnActivate event
	 * @param object $params event parameters
	 */
	public function onActivate ($params)
	{
		$this->raiseEvent('OnActivate', $this, $params);
	}

	/**
	 * Raises the OnBeforeStop event
	 * @param object $params event parameters
	 */
	public function onBeforeStop ($params)
	{
		$this->raiseEvent('OnBeforeStop', $this, $params);
	}

	/**
	 * Raises the OnChange event
	 * @param object $params event parameters
	 */
	public function onChange ($params)
	{
		$this->raiseEvent('OnChange', $this, $params);
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
	 * Raises the OnDeactivate event
	 * @param object $params event parameters
	 */
	public function onDeactivate ($params)
	{
		$this->raiseEvent('OnDeactivate', $this, $params);
	}

	/**
	 * Raises the OnOut event
	 * @param object $params event parameters
	 */
	public function onOut ($params)
	{
		$this->raiseEvent('OnOut', $this, $params);
	}

	/**
	 * Raises the OnOver event
	 * @param object $params event parameters
	 */
	public function onOver ($params)
	{
		$this->raiseEvent('OnOver', $this, $params);
	}

	/**
	 * Raises the OnReceive event
	 * @param object $params event parameters
	 */
	public function onReceive ($params)
	{
		$this->raiseEvent('OnReceive', $this, $params);
	}

	/**
	 * Raises the OnRemove event
	 * @param object $params event parameters
	 */
	public function onRemove ($params)
	{
		$this->raiseEvent('OnRemove', $this, $params);
	}

	/**
	 * Raises the OnSort event
	 * @param object $params event parameters
	 */
	public function onSort ($params)
	{
		$this->raiseEvent('OnSort', $this, $params);
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
	public function OnStop ($params)
	{
		$this->raiseEvent('OnStop', $this, $params);
	}

	/**
	 * Raises the OnUpdate event
	 * @param object $params event parameters
	 */
	public function onUpdate ($params)
	{
		$this->raiseEvent('OnUpdate', $this, $params);
	}

	/**
	 * @var ITemplate template for repeater items
	 */
	private $_repeater=null;

	/**
	 * @param array data source for Sortables.
	 */
	public function setDataSource($data)
	{
		$this->getSortables()->setDataSource($data);
	}

	/**
	 * Overrides parent implementation. Callback {@link renderSortables()} when
	 * page's IsCallback property is true.
	 */
	public function dataBind()
	{
		parent::dataBind();
		if($this->getPage()->getIsCallback())
			$this->renderSortables($this->getResponse()->createHtmlWriter());
	}

	/**
	 * @return TRepeater suggestion list repeater
	 */
	public function getSortables()
	{
		if($this->_repeater===null)
			$this->_repeater = $this->createRepeater();
		return $this->_repeater;
	}

	/**
	 * @return TRepeater new instance of TRepater to render the list of Sortables.
	 */
	protected function createRepeater()
	{
		$repeater = Prado::createComponent('System.Web.UI.WebControls.TRepeater');
		$repeater->setHeaderTemplate(new TJuiSortableTemplate('<ul id="'.$this->getWidgetID().'">'));
		$repeater->setFooterTemplate(new TJuiSortableTemplate('</ul>'));
		$repeater->setItemTemplate(new TTemplate('<li id="<%# $this->ItemIndex %>"><%# $this->Data %></li>',null));
		$repeater->setEmptyTemplate(new TJuiSortableTemplate('<ul></ul>'));
		$this->getControls()->add($repeater);
		return $repeater;
	}
}


/**
 * TJuiSortableTemplate class.
 *
 * TJuiSortableTemplate is the default template for TJuiSortableTemplate
 * item template.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TJuiSortableTemplate extends TComponent implements ITemplate
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
