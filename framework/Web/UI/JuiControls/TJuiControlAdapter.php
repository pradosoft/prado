<?php
/**
 * TJuiControlAdapter class file.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2013-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.JuiControls
 */

Prado::using('System.Web.UI.ActiveControls.TActiveControlAdapter');

/**
 * TJuiControlAdapter class
 *
 * TJuiControlAdapter is the base adapter class for controls that are
 * derived from a jQuery-ui widget. It exposes convenience methods to
 * publish jQuery-UI javascript and css assets.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @package System.Web.UI.JuiControls
 * @since 3.3
 */
class TJuiControlAdapter extends TActiveControlAdapter
{
	const SCRIPT_PATH = 'jquery';
	const CSS_PATH = 'css';
	const BASE_CSS_FILENAME ='jquery-ui.css';

	/**
	 * @param string set the jquery-ui style
	 */
	public function setJuiBaseStyle($value)
	{
	   $this->getControl()->setViewState('JuiBaseStyle', $value, 'base');
	}

	/**
	 * @return string current jquery-ui style
	 */
	public function getJuiBaseStyle()
	{
	   return $this->getControl()->getViewState('JuiBaseStyle', 'base');
	}

	/**
	 * Inject jquery script and styles before render
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->getPage()->getClientScript()->registerPradoScript('jqueryui');
		$this->publishJuiStyle(self::BASE_CSS_FILENAME);
	}

	/**
	 * @param string jQuery asset file in the self::SCRIPT_PATH directory.
	 * @return string jQuery asset url.
	 */
	protected function getAssetUrl($file='')
	{
		$base = $this->getPage()->getClientScript()->getPradoScriptAssetUrl();
		return $base.'/'.self::SCRIPT_PATH.'/'.$file;
	}

	/**
	 * Publish the jQuery-ui style Css asset file.
	 * @param file name
	 * @return string Css file url.
	 */
	public function publishJuiStyle($file)
	{
		$url = $this->getAssetUrl(self::CSS_PATH.'/'.$this->getJuiBaseStyle().'/'.$file);
		$cs = $this->getPage()->getClientScript();
		if(!$cs->isStyleSheetFileRegistered($url))
			$cs->registerStyleSheetFile($url, $url);
		return $url;
	}

}

/**
 * IJuiOptions interface
 *
 * IJuiOptions is the interface that must be implemented by controls using
 * {@link TJuiControlOptions}.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @package System.Web.UI.JuiControls
 * @since 3.3
 */
interface IJuiOptions
{
	public function getOptions();
	public function getValidOptions();
	public function getValidEvents();
}

/**
 * TJuiControlOptions interface
 *
 * TJuiControlOptions is an helper class that can collect a list of options
 * for a control. The control must implement {@link IJuiOptions}.
 * The options are validated againg an array of valid options provided by the control itself.
 * Since component properties are case insensitive, the array of valid options is used
 * to ensure the option name has the correct case.
 * The options array can then get retrieved using {@link toArray} and applied to the jQuery-ui widget.
 * In addition to the options, this class will render the needed javascript to raise a callback
 * for any event for which an handler is defined in the control.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @package System.Web.UI.JuiControls
 * @since 3.3
 */
class TJuiControlOptions
{
	/**
	 * @var TMap map of javascript options.
	 */
	private $_options;
	/**
	 * @var TControl parent control.
	 */
	private $_control;

	public function __construct($control)
	{
		if(!$control instanceof IJuiOptions)
			throw new THttpException(500,'juioptions_control_invalid',$control->ID);
		$this->_control=$control;
	}
	/**
	 * Sets a named options with a value. Options are used to store and retrive
	 * named values for the javascript control.
	 * @param string option name.
	 * @param mixed option value.
	 * @throws THttpException
	 */
	public function __set($name,$value)
	{
		if($this->_options===null)
			$this->_options=array();

		foreach($this->_control->getValidOptions() as $option)
		{
			if(0 == strcasecmp($name, $option))
			{
				$low = strtolower($value);
				if($low === 'null')
				{
					$this->_options[$option] = null;
				} elseif($low === 'true') {
					$this->_options[$option] = true;
				} elseif($low === 'false') {
					$this->_options[$option] = false;
				} elseif(is_numeric($value)) {
					// trick to get float or integer automatically when needed
					$this->_options[$option] = $value + 0;
				} else {
					$this->_options[$option] = $value;
				}
				return;
			}
		}

		throw new THttpException(500,'juioptions_option_invalid',$this->_control->ID, $name);
	}

	/**
	 * Gets an option named value. Options are used to store and retrive
	 * named values for the base active controls.
	 * @param string option name.
	 * @return mixed options value or null if not set.
	 */
	public function __get($name)
	{
		if($this->_options===null)
			$this->_options=array();

		foreach($this->_control->getValidOptions() as $option)
		{
			if(0 == strcasecmp($name, $option) && isset($this->_options[$option]))
			{
				return $this->_options[$option];
			}
		}

		return null;
	}

	/**
	 * @return Array of active control options
	 */
	public function toArray()
	{
		$ret= ($this->_options===null) ? array() : $this->_options;

		foreach($this->_control->getValidEvents() as $event)
			if($this->_control->hasEventHandler('on'.$event))
				$ret[$event]=new TJavaScriptLiteral("function( event, ui ) { Prado.JuiCallback(".TJavascript::encode($this->_control->getUniqueID()).", ".TJavascript::encode($event).", event, ui, this); }");

		return $ret;
	}

	/**
	 * Raise the specific callback event handler of the target control.
	 * @param mixed callback parameters
	 */
	public function raiseCallbackEvent($param)
	{
		$callbackParam=$param->CallbackParameter;
		if(isset($callbackParam->event))
		{
			$eventName = 'On'.ucfirst($callbackParam->event);
			if($this->_control->hasEventHandler($eventName))
			{
				$this->_control->$eventName( new TJuiEventParameter(
					$this->_control->getResponse(),
					isset($callbackParam->ui) ? $callbackParam->ui : null)
				);
			}
		}
	}
}

/**
 * TJuiEventParameter class
 *
 * TJuiEventParameter encapsulate the parameters for callback
 * events of TJui* components.
 * Any parameter representing a control is identified by its
 * clientside ID.
 * TJuiEventParameter contains a {@link getControl} helper method
 * that retrieves an existing PRADO control on che current page from its
 * clientside ID as returned by the callback.
 * For example, if the parameter contains a "draggable" item (as returned in
 * {@link TJuiDroppable}::OnDrop event), the relative PRADO control can be
 * retrieved using:
 * <code>
 * $draggable = $param->getControl($param->getCallbackParameter()->draggable);
 * </code>
 *
 * A shortcut __get() method is implemented, too:
 * <code>
 * $draggable = $param->DraggableControl;
 * </code>
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @license http://www.pradosoft.com/license
 * @package System.Web.UI.JuiControls
 */
class TJuiEventParameter extends TCallbackEventParameter
{
 	/**
	 * getControl
	 *
	 * Compatibility method to get a control from its clientside id
	 * @return TControl control, or null if not found
 	 */
	public function getControl($id)
	{
		$control=null;
		$service=prado::getApplication()->getService();
		if ($service instanceof TPageService)
		{
			// Find the control
			// Warning, this will not work if you have a '_' in your control Id !
			$controlId=str_replace(TControl::CLIENT_ID_SEPARATOR,TControl::ID_SEPARATOR,$id);
			$control=$service->getRequestedPage()->findControl($controlId);
		}
		return $control;
	}

	/**
	 * Gets a control instance named after a returned control id.
	 * Example: if a $param->draggable control id is returned from clientside,
	 * calling $param->DraggableControl will return the control instance
	 * @return mixed control or null if not set.
	 */
	public function __get($name)
	{
		$pos=strpos($name, 'Control',1);
		$name=strtolower(substr($name, 0, $pos));

		$cp=$this->getCallbackParameter();
		if(!isset($cp->$name) || $cp->$name=='')
			return null;

		return $this->getControl($cp->$name);
	}
}