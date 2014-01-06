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
class TJuiSelectable extends TActivePanel implements IJuiOptions
{
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
		static $options;
		if($options===null)
			$options=new TJuiControlOptions($this);
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
	 * @return array list of callback options.
	 */
	protected function getPostBackOptions()
	{
		$options = $this->getOptions()->toArray();
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
		$repeater->setItemTemplate(new TTemplate('<li><%# $this->DataItem %></li>',null));
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