<?php
/**
 * TClientScript class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TClientScript class
 *
 * Allows importing of Prado Client Scripts from template via the
 * {@link setPradoScripts PradoScripts} property. Multiple Prado
 * client-scripts can be specified using comma delimited string of the
 * javascript library to include on the page. For example,
 *
 * <code>
 * <com:TClientScript PradoScripts="effects, rico" />
 * </code>
 *
 * Custom javascript files can be register using the {@link setScriptUrl ScriptUrl}
 * property.
 * <code>
 * <com:TClientScript ScriptUrl=<%~ test.js %> />
 * </code>
 *
 * Contents within TClientScript will be treated as javascript code and will be
 * rendered in place.
 *
 * Since Prado 3.2 the property {@link setFlushScriptFiles FlushScriptFiles} controls
 * whether Prado will flush the script files defined in the page before rendering the
 * TClientScript contents.
 * If you're not using any external functions in your TClientScript block, you should
 * set the {@link setFlushScriptFiles FlushScriptFiles} property to false, so Prado
 * can postpone the loading of all the referenced script files further down the page
 * generation cycle.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TClientScript extends \Prado\Web\UI\TControl
{
	/**
	 * @return string comma delimited list of javascript libraries to included
	 * on the page.
	 */
	public function getPradoScripts()
	{
		return $this->getViewState('PradoScripts', '');
	}

	/**
	 * Include javascript library to the current page. The current supported
	 * libraries are: "prado", "effects", "ajax", "validator", "logger",
	 * "datepicker", "colorpicker". Library dependencies are automatically resolved.
	 *
	 * @param string $value comma delimited list of javascript libraries to include.
	 */
	public function setPradoScripts($value)
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
	 * @param string $value custom javascript file url.
	 */
	public function setScriptUrl($value)
	{
		$this->setViewState('ScriptUrl', $value, '');
	}

	/**
	 * @return bool whether to flush script files using TClientScriptManager::flushScriptFiles() before rendering the script block
	 */
	public function getFlushScriptFiles()
	{
		return TPropertyValue::ensureBoolean($this->getViewState('FlushScriptFiles', true));
	}

	/**
	 * @param bool $value whether to flush script files using TClientScriptManager::flushScriptFiles() before rendering the script block
	 */
	public function setFlushScriptFiles($value)
	{
		$this->setViewState('FlushScriptFiles', TPropertyValue::ensureBoolean($value));
	}

	/**
	 * Calls the client script manager to add each of the requested client
	 * script libraries.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$scripts = preg_split('/,|\s+/', $this->getPradoScripts());
		$cs = $this->getPage()->getClientScript();
		foreach ($scripts as $script) {
			if (($script = trim($script)) !== '') {
				$cs->registerPradoScript($script);
			}
		}
	}

	/**
	 * Renders the body content as javascript block.
	 * Overrides parent implementation, parent renderChildren method is called during
	 * {@link registerCustomScript}.
	 * @param THtmlWriter $writer the renderer
	 */
	public function render($writer)
	{
		if ($this->getFlushScriptFiles()) {
			$this->getPage()->getClientScript()->flushScriptFiles($writer, $this);
		}
		$this->renderCustomScriptFile($writer);
		$this->renderCustomScript($writer);
	}

	/**
	 * Renders the custom script file.
	 * @param THtmLWriter $writer the renderer
	 */
	protected function renderCustomScriptFile($writer)
	{
		if (($scriptUrl = $this->getScriptUrl()) !== '') {
			$writer->write("<script src=\"$scriptUrl\"></script>\n");
		}
	}

	/**
	 * Registers the body content as javascript.
	 * @param THtmlWriter $writer the renderer
	 */
	protected function renderCustomScript($writer)
	{
		if ($this->getHasControls()) {
			$writer->write("<script>\n/*<![CDATA[*/\n");
			$this->renderChildren($writer);
			$writer->write("\n/*]]>*/\n</script>\n");
		}
	}
}
