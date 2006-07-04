<?php
/**
 * TClientScript class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TClientScript class
 *
 * Allows importing of Prado Client Scripts from template via the
 * {@link setUsingPradoScripts UsingPradoScripts} property. Multiple Prado
 * client-scripts can be specified using comma delimited string of the
 * javascript library to include on the page. For example,
 *
 * <code>
 * <com:TClientScript UsingPradoScripts="effects, rico" />
 * </code>
 *
 * The {@link setPreRenderControlTypes PreRenderControlTypes} property can
 * be used to specify that controls type/class names that should pre-render itself
 * even though they may not be rendered on the page. This is useful to publish
 * controls that require assets and is only visible after a callback response.
 *
 * Custom javascript files can be register using the {@link setScriptUrl ScriptUrl}
 * property.
 * <code>
 * <com:TClientScript ScriptUrl=<%~ test.js %> />
 * </code>
 *
 * Contents within TClientScript will be treated as javascript code and will be
 * rendered.
 *
 * The {@link setScriptPosition ScriptPosition} property specifies where the script
 * will be rendered. The allows values of {@link setScriptPosition ScriptPosition} are
 *
 *  - <b>Head</b> -- renders the script within the &lt;head&gt;
 *  - <b>Begin</b> -- renders the script within and near the begining of TForm
 *  - <b>Here</b> -- renders the script inplace, this is the default
 *  - <b>End</b> -- renders the script within and near the end of TForm
 *
 * TODO: Allow binding expressions inside scripts
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TClientScript extends TControl
{
	/**
	 * @var string body contents
	 */
	private $_content = '';

	/**
	 * @return string comma delimited list of javascript libraries to included
	 * on the page.
	 */
	public function getUsingPradoScripts()
	{
		return $this->getViewState('PradoScripts', '');
	}

	/**
	 * Include javascript library to the current page. The current supported
	 * libraries are: "prado", "effects", "ajax", "validator", "logger",
	 * "datepicker", "rico", "colorpicker". Library dependencies are
	 * automatically resolved.
	 *
	 * @param string comma delimited list of javascript libraries to include.
	 */
	public function setUsingPradoScripts($value)
	{
		$this->setViewState('PradoScripts', $value, '');
	}

	/**
	 * @return string custom javascript file url.
	 */
	public function getScriptUrl()
	{
		return $this->getViewState('ScriptUrl', '');
	}

	/**
	 * @param string custom javascript file url.
	 */
	public function setScriptUrl($value)
	{
		$this->setViewState('ScriptUrl', $value, '');
	}

	/**
	 * @param string comma delimited list of controls that wish to be prerendered
	 * so as to publish its assets.
	 */
	public function setPreRenderControlTypes($value)
	{
		$this->setViewState('PreRenderControls', $value);
	}

	/**
	 * @return string comma delimited list of controls types that require prerendering.
	 */
	public function getPreRenderControlTypes()
	{
		return $this->getViewState('PreRenderControls', '');
	}

	/**
	 * @return string position the script should be rendered, default is 'Here'.
	 */
	public function getScriptPosition()
	{
		return $this->getViewState('ScriptPosition', 'Here');
	}

	/**
	 * Sets the position where the script will be rendered.
	 * The allow positions are 'Head', 'Begin', 'Here', and 'End', default is 'Here'.
	 * @param string script position 'Head', 'Begin', 'Here' or 'End'.
	 */
	public function setScriptPosition($value)
	{
		$this->setViewState('ScriptPosition',
			TPropertyValue::ensureEnum($value, 'Head', 'Begin', 'Here', 'End'), 'Here');
	}

	/**
	 * Calls the client script manager to add each of the requested client
	 * script libraries.
	 * @param mixed event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$scripts = preg_split('/,|\s+/', $this->getUsingPradoScripts());
		$cs = $this->getPage()->getClientScript();
		foreach($scripts as $script)
		{
			$script = trim($script);
			if(strlen($script) > 0)
				$cs->registerPradoScript($script);
		}
		if($this->getEnabled(true))
		{
			$this->registerCustomScriptFile();
			$this->registerCustomScript();
		}
		$this->preRenderControls($param);
	}

	/**
	 * Registers the custom script file.
	 */
	protected function registerCustomScriptFile()
	{
		$scriptUrl = $this->getScriptUrl();
		if(strlen($scriptUrl))
		{
			$position = $this->getScriptPosition();
			$cs = $this->getPage()->getClientScript();
			switch($this->getScriptPosition())
			{
				case 'Head':
					$cs->registerHeadScriptFile($scriptUrl, $scriptUrl);
					break;
				case 'Begin':
					$cs->registerScriptFile($scriptUrl, $scriptUrl);
					break;
				case 'Here':
					$this->_content .= TJavascript::renderScriptFile($scriptUrl);
					break;
				default :
					throw new TConfigurationException('clientscript_invalid_file_position',
								$this->getID(), $position);
			}
		}
	}

	/**
	 * Renders the body content as javascript block.
	 * Overrides parent implementation, parent renderChildren method is called during
	 * {@link registerCustomScript}.
	 * @param THtmlWriter the renderer
	 */
	public function render($writer)
	{
		if($this->getEnabled(true) && strlen($this->_content) > 0)
			$writer->write($this->_content);
	}

	/**
	 * Registers the body content as scripts at specific locations. Calls
	 * {@link parent::renderChildren} to capture the body contents.
	 */
	protected function registerCustomScript()
	{
		$textWriter=new TTextWriter;
		$this->renderChildren(new THtmlWriter($textWriter));
		$script=$textWriter->flush();
		if(strlen($script)>0)
		{
			$cs = $this->getPage()->getClientScript();
			$position = $this->getScriptPosition();
			if($position == 'Here')
				$this->_content .= TJavaScript::renderScriptBlock($script);
			else
			{
				$key = sprintf('%08X', crc32($script));
				$method = 'register'.$position.'Script';
				$cs->{$method}($key, $script);
			}
		}
	}

	/**
	 * PreRender other controls to allow them to publish their assets. Useful
	 * when callback response components that require assets to be present on the page.
	 * @param mixed event paramater
	 */
	protected function preRenderControls($param)
	{
		$types = preg_split('/,|\s+/', $this->getPreRenderControlTypes());
		foreach($types as $type)
		{
			if(strlen($type))
			{
				$control = Prado::createComponent(trim($type));
				$control->setPage($this->getPage());
				$control->onPreRender($param);
			}
		}
	}
}

?>
