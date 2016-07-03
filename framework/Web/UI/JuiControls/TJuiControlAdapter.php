<?php
/**
 * TJuiControlAdapter class file.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2013-2015 PradoSoft
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package System.Web.UI.JuiControls
 */

Prado::using('System.Web.UI.ActiveControls.TActiveControlAdapter');
Prado::using('System.Web.UI.JuiControls.TJuiControlOptions');
Prado::using('System.Web.UI.ActiveControls.TCallbackEventParameter');

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
	 * Replace default StateTracker with {@link TJuiCallbackPageStateTracker} for
	 * options tracking in ViewState.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	public function onInit($param)
	{
	  parent::onInit($param);
	  $this->setStateTracker('TJuiCallbackPageStateTracker');
	}

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

	/**
	 * Calls the parent implementation first and sets the parent control for the
	 * {@link TJuiControlOptions} again afterwards since it was not serialized in viewstate.
	 */
	public function loadState() {
	  parent::loadState();
    $this->getControl()->getOptions()->setControl($this->getControl());
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
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
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

/**
 * TJuiCallbackPageStateTracker class.
 *
 * Tracking changes to the page state during callback, including {@link TJuiControlOptions}.
 *
 * @author LANDWEHR Computer und Software GmbH
 * @package System.Web.UI.JuiControls
 * @since 3.3
 */
class TJuiCallbackPageStateTracker extends TCallbackPageStateTracker {

  /**
   * Add the {@link TJuiControlOptions} to the states to track.
   */
  protected function addStatesToTrack()
  {
    parent::addStatesToTrack();
    $states = $this->getStatesToTrack();
    $states['JuiOptions'] = array('TMapCollectionDiff', array($this, 'updateJuiOptions'));
  }

	/**
	 * Updates the options of the jQueryUI widget.
	 * @param array list of widget options to change.
	 */
  protected function updateJuiOptions($options)
  {
    foreach ($options as $key => $value) $options[$key] = $key . ': ' . (is_string($value) ? "'{$value}'" : TPropertyValue::ensureString($value));
    $code = "jQuery('#{$this->_control->getWidgetID()}').{$this->_control->getWidget()}('option', { " . implode(', ', $options) . " });";
    $this->_control->getPage()->getClientScript()->registerEndScript(sprintf('%08X', crc32($code)), $code);
  }

}