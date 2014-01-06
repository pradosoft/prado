<?php
/**
 * TJuiControlAdapter class file.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2013-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TJuiControl.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.JuiControls
 */

Prado::using('System.Web.UI.ActiveControls.TActiveControlAdapter');

/**
 * TJuiControlAdapter class
 *
 * TJuiControlAdapter is the base adapter class for controls that are
 * derived from a jQuery-ui widget.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @version $Id: TJuiControlAdapter.php 3245 2013-01-07 20:23:32Z ctrlaltca $
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
 * @version $Id: TJuiControlAdapter.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.JuiControls
 * @since 3.3
 */
interface IJuiOptions
{
	public function getOptions();
	public function getValidOptions();
}

/**
 * TJuiControlOptions interface
 *
 * TJuiControlOptions is an helper class that can collect a series of options
 * for a control. The control must implement {@link IJuiOptions}.
 * The options are validated againg an array of valid options provided by the control.
 * Since component properties are case insensitive, the array of valid options is used
 * to ensure the option name has the correct case.
 * The options array can then get retrieved using {@link toArray} and applied to the jQuery-ui widget.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @version $Id: TJuiControlAdapter.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.JuiControls
 * @since 3.3
 */
class TJuiControlOptions
{
	/**
	 * @var TMap map of javascript options.
	 */
	private $_options;

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
	 * @param mixed new value.
	 * @param mixed default value.
	 * @return mixed options value.
	 */
	public function __set($name,$value)
	{
		if($this->_options===null)
			$this->_options=array();
		foreach($this->_control->getValidOptions() as $option)
		{
			if($name == strtolower($option))
			{
				$this->_options[$option] = $value;
				return;
			}
		}
		throw new THttpException(500,'juioptions_option_invalid',$control->ID, $name);
	}

	/**
	 * Gets an option named value. Options are used to store and retrive
	 * named values for the base active controls.
	 * @param string option name.
	 * @param mixed default value.
	 * @return mixed options value.
	 */
	public function __get($name)
	{
		if($this->_options===null)
			$this->_options=array();
		return isset($this->_options[$name]) ? $this->_options[$name] : null;
	}

	/**
	 * @return TMap active control options
	 */
	public function toArray()
	{
		if($this->_options===null)
			$this->_options=array();
		return $this->_options;
	}
}
