<?php
/**
 * TActiveControlAdapter and TCallbackPageStateTracker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  : $
 * @package System.Web.UI.ActiveControls
 */

/*
 * Load common active control options.
 */
Prado::using('System.Web.UI.ActiveControls.TBaseActiveControl');

/**
 * TActiveControlAdapter class.
 * 
 * Customize the parent TControl class for active control classes. 
 * TActiveControlAdapter instantiates a common base active control class
 * throught the {@link getBaseActiveControl BaseActiveControl} property.
 * The type of BaseActiveControl can be provided in the second parameter in the
 * constructor. Default is TBaseActiveControl or TBaseActiveCallbackControl if
 * the control adapted implements ICallbackEventHandler.
 * 
 * TActiveControlAdapter will tracking viewstate changes to update the 
 * corresponding client-side properties.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  Sun Jun 18 20:35:34 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TActiveControlAdapter extends TControlAdapter
{
	/**
	 * @var string base active control class name.
	 */
	private $_activeControlType;
	/**
	 * @var TBaseActiveControl base active control instance.
	 */
	private $_baseActiveControl;
	/**
	 * @var TCallbackPageStateTracker view state tracker.
	 */
	private $_stateTracker;
	
	/**
	 * Constructor.
	 * @param IActiveControl active control to adapt.
	 * @param string Base active control class name.
	 */
	public function __construct(IActiveControl $control, $baseCallbackClass=null)
	{
		parent::__construct($control);
		$this->setBaseControlClass($baseCallbackClass);
	}
	
	/**
	 * @param string base active control instance
	 */
	protected function setBaseControlClass($type)
	{
		if(is_null($type))
		{
			if($this->getControl() instanceof ICallbackEventHandler)
				$this->_activeControlType = 'TBaseActiveCallbackControl';
			else
				$this->_activeControlType = 'TBaseActiveControl';
		}
		else
			$this->_activeControlType = $type;
	}
	
	/**
	 * Renders the callback client scripts.
	 */
	public function render($writer)
	{
		$this->renderCallbackClientScripts();
		parent::render($writer);
	}
	
	/**
	 * Register the callback clientscripts and sets the post loader IDs. 
	 */
	protected function renderCallbackClientScripts()
	{
		$cs = $this->getPage()->getClientScript();
		$key = get_class($this);
		if(!$cs->isEndScriptRegistered($key))
		{
			$cs->registerPradoScript('ajax');
			$options = TJavascript::encode($this->getPage()->getPostDataLoaders(),false);
			$script = "Prado.CallbackRequest.addPostLoaders({$options});";
			$cs->registerEndScript($key, $script);
		}
	}
	
	/**
	 * @return TBaseActiveControl Common active control options.
	 */
	public function getBaseActiveControl()
	{
		if(is_null($this->_baseActiveControl))
		{
			$type = $this->_activeControlType;
			$this->_baseActiveControl = new $type($this->getControl());
		}
		return $this->_baseActiveControl;
	}
	
	/**
	 * @return boolean true if the viewstate needs to be tracked.
	 */
	protected function getIsTrackingPageState()
	{
		if($this->getPage()->getIsCallback())
		{
			$target = $this->getPage()->getCallbackEventTarget();
			if($target instanceof ICallbackEventHandler)
			{
				$client = $target->getActiveControl()->getClientSide(); 
				return $client->getEnablePageStateUpdate();
			}
		}
		return false;
	}
	
	/**
	 * Loads additional persistent control state. Starts viewstate tracking
	 * if necessary.
	 */
	public function loadState()
	{
		if($this->getIsTrackingPageState())
		{
			$this->_stateTracker = new TCallbackPageStateTracker($this->getControl());
			$this->_stateTracker->trackChanges();
		}
		parent::loadState();
	}
	
	/**
	 * Saves additional persistent control state. Respond to viewstate changes
	 * if necessary.
	 */
	public function saveState()
	{
		if(!is_null($this->_stateTracker) 
			&& $this->getControl()->getActiveControl()->canUpdateClientSide())
		{
			$this->_stateTracker->respondToChanges();
		}
		parent::saveState();
	}
} 

/**
 * TCallbackPageStateTracker class.
 * 
 * Tracking changes to the page state during callback.
 * 
 * @todo Complete this class! (Wei)
 * 
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  Sun Jun 18 20:51:25 EST 2006 $
 * @package System
 * @since 3.0
 */
class TCallbackPageStateTracker
{
	private $_states = array('Visible', 'Enabled', 'Attributes', 'Style', 'TabIndex', 'ToolTip', 'AccessKey');
	private $_existingState;
	private $_control;
	private $_nullObject;
	
	public function __construct($control)
	{
		$this->_control = $control;
		$this->_existingState = new TMap;
		$this->_nullObject = new stdClass;
	}
	
	public function trackChanges()
	{
		foreach($this->_states as $name)
			$this->_existingState[$name] = $this->_control->getViewState($name);
	}
	
	protected function getChanges()
	{
		$diff = array();
		foreach($this->_states as $name)
		{
			$state = $this->_control->getViewState($name);
		//	echo " $name ";
			$changes = $this->difference($state, $this->_existingState[$name]);
		//	echo " \n ";
			if($changes !== $this->_nullObject)
				$diff[$name] = $changes;		
		}
		return $diff;
	}

	protected function difference($value1, $value2)
	{
//		var_dump($value1, $value2);
		if(gettype($value1) === gettype($value2) 
				&& $value1 === $value2) return $this->_nullObject;
		return $value1;
	}
	
	public function respondToChanges()
	{
		foreach($this->getChanges() as $property => $value)
		{
			$this->{'update'.$property}($value);
		}
	}
	
	protected function client()
	{
		return $this->_control->getPage()->getCallbackClient();
	}
	
	protected function updateToolTip($value)
	{
		$this->client()->setAttribute($this->_control, 'title', $value); 
	}
	
	protected function updateVisible($visible)
	{
		var_dump($visible);
		if($visible === false)
			$this->client()->hide($this->_control);
		else
			$this->client()->show($this->_control);
	}
	
	protected function updateEnabled($enable)
	{
		$this->client()->setAttribute($this->_control, 'disabled', $enable===false);
	}
	
	protected function updateStyle($style)
	{
		var_dump($style);	
	}
}

?>