<?php
/*
 * Created on 29/04/2006
 */
Prado::using('System.Web.UI.ActiveControls.TBaseActiveControl');

class TActiveControlAdapter extends TControlAdapter
{
	private static $_renderedPosts = false;
	
	private $_activeControlType;
	
	private $_baseActiveControl;
	
	private $_stateTracker;
	
	public function __construct($control, $baseCallbackClass=null)
	{
		parent::__construct($control);
		$this->setBaseControlType($baseCallbackClass);
	}
	
	private function setBaseControlType($type)
	{
		if(is_null($type))
		{
			if($this->getControl() instanceof ICallbackEventHandler)
				$this->_activeControlType = 'TBaseActiveCallbackControl';
			else
				$this->_activeControlType = 'TBaseActiveControl';
		}
		else
		{
			$this->_activeControlType = $type;
		}
	}
	
	/**
	 * Render the callback request post data loaders once only.
	 */
	public function render($writer)
	{
		$this->renderCallbackClientScripts();
		parent::render($writer);
		if($this->getPage()->getIsCallback())
			$this->getPage()->getCallbackClient()->replace($this->getControl(), $writer);
	}
	
	protected function renderCallbackClientScripts()
	{
		$cs = $this->getPage()->getClientScript();
		$key = get_class($this);
		if(!$cs->isEndScriptRegistered($key))
		{
			$cs->registerPradoScript('ajax');
			$options = TJavascript::encode($this->getPage()->getPostDataLoaders(),false);
			$script = "Prado.CallbackRequest.PostDataLoaders = {$options};";
			$cs->registerEndScript($key, $script);
		}
	}
	
	public function getActiveControl()
	{
		if(is_null($this->_baseActiveControl))
		{
			$type = $this->_activeControlType;
			$this->_baseActiveControl = new $type($this->getControl());
		}
		return $this->_baseActiveControl;
	}
	
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
	
	public function loadState()
	{
		if($this->getIsTrackingPageState())
		{
			$this->_stateTracker = new TCallbackPageStateTracker($this->getControl());
			$this->_stateTracker->trackChanges();
		}
		parent::loadState();
	}
	
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