<?php
/**
 * TTemplateControl class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

/**
 * include TTemplate class file
 */
require_once(PRADO_DIR.'/Web/UI/TTemplate.php');

/**
 * TTemplateControl class.
 * TTemplateControl is the base class for all controls that use templates.
 * By default, a control template is assumed to be in a file under the same
 * directory with the control class file. They have the same file name and
 * different extension name. For template file, the extension name is ".tpl".
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class TTemplateControl extends TControl implements INamingContainer
{
	/**
	 * template file extension.
	 */
	const EXT_TEMPLATE='.tpl';
	/**
	 * template cache file extension
	 */
	const EXT_TEMPLATE_CACHE='.tpc';

	/**
	 * @var ITemplate the parsed template structure shared by the same control class
	 */
	protected static $_template=null;
	/**
	 * @var ITemplate the parsed template structure specific for this control instance
	 */
	protected $_localTemplate=null;
	/**
	 * @var TTemplateControl the master control if any
	 */
	private $_master=null;
	/**
	 * @var string master control class name
	 */
	private $_masterClass='';
	/**
	 * @var array list of TContent controls
	 */
	private $_contents=array();
	/**
	 * @var array list of TContentPlaceHolder controls
	 */
	private $_placeholders=array();

	/**
	 * Constructor.
	 * Loads template for the control class if not loaded.
	 * Applies template directive if any.
	 */
	public function __construct()
	{
		if(($tpl=$this->getTemplate(true))!==null)
		{
			foreach($tpl->getDirective() as $name=>$value)
				$this->setPropertyByPath($name,$value);
		}
	}

	/**
	 * @param boolean whether to attempt loading template if it is not loaded yet
	 * @return ITemplate|null the parsed template, null if none
	 */
	protected function getTemplate($load=false)
	{
		if($this->_localTemplate===null)
		{
			eval('$tpl='.get_class($this).'::$_template;');
			return ($tpl===null && $load)?$this->loadTemplate():$tpl;
		}
		else
			return $this->_localTemplate;
	}

	/**
	 * Sets the parsed template.
	 * Note, the template will be applied to the whole control class.
	 * This method should only be used by framework and control developers.
	 * @param ITemplate the parsed template
	 */
	protected function setTemplate($value)
	{
		$this->_localTemplate=$value;
	}

	/**
	 * Loads and parses the control template
	 * @return ITemplate the parsed template structure
	 */
	protected function loadTemplate()
	{
		$template=Prado::getApplication()->getService()->getTemplateManager()->loadTemplateByClassName(get_class($this));
		eval(get_class($this).'::$_template=$template;');
		return $template;
	}

	/**
	 * Creates child controls.
	 * This method is overriden to load and instantiate control template.
	 * This method should only be used by framework and control developers.
	 */
	protected function createChildControls()
	{
		if($tpl=$this->getTemplate())
			$tpl->instantiateIn($this);
	}

	/**
	 * Registers a content control.
	 * @param TContent
	 */
	public function registerContent(TContent $object)
	{
		$this->_contents[$object->getID()]=$object;
	}

	/**
	 * @return string master class name (in namespace form)
	 */
	public function getMasterClass()
	{
		return $this->_masterClass;
	}

	/**
	 * @param string  master control class name (in namespace form)
	 */
	public function setMasterClass($value)
	{
		$this->_masterClass=$value;
	}

	/**
	 * @return TTemplateControl|null master control associated with this control, null if none
	 */
	public function getMaster()
	{
		return $this->_master;
	}

	/**
	 * Registers a content placeholder to this template control.
	 * This method should only be used by framework and control developers.
	 * @param string ID of the placeholder
	 * @param TControl control that directly enloses the placeholder
	 * @param integer the index in the control list of the parent control that the placeholder is at
	 */
	public function registerContentPlaceHolder($id,$parent,$loc)
	{
		$this->_placeholders[$id]=array($parent,$loc);
	}

	/**
	 * Injects all content controls (and their children) to the corresponding content placeholders.
	 * This method should only be used by framework and control developers.
	 * @param string ID of the content control
	 * @param TContent the content to be injected
	 */
	public function injectContent($id,$content)
	{
		if(isset($this->_placeholders[$id]))
		{
			list($parent,$loc)=$this->_placeholders[$id];
			$parent->getControls()->addAt($loc,$content);
		}
	}

	/**
	 * Performs the OnInit step for the control and all its child controls.
	 * This method overrides the parent implementation
	 * by ensuring child controls are created first,
	 * and if master class is set, master will be applied.
	 * Only framework developers should use this method.
	 * @param TControl the naming container control
	 */
	protected function initRecursive($namingContainer)
	{
		$this->ensureChildControls();
		if($this->_masterClass!=='')
		{
			$master=Prado::createComponent($this->_masterClass);
			if(!($master instanceof TTemplateControl))
				throw new TInvalidDataValueException('tplcontrol_required',get_class($master));
			$this->_master=$master;
			$this->getControls()->clear();
			$this->getControls()->add($master);
			$master->ensureChildControls();
			foreach($this->_contents as $id=>$content)
				$master->injectContent($id,$content);
		}
		parent::initRecursive($namingContainer);
	}
}

?>