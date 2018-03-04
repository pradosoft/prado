<?php
/**
 * TJavascriptLogger class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TJavascriptLogger class.
 *
 * Provides logging for client-side javascript. Example: template code
 * <code><com:TJavascriptLogger /></code>
 *
 * Client-side javascript code to log info, error, warn, debug
 * <code>Logger.warn('A warning');
 * Logger.info('something happend');
 * </code>
 *
 * To see the logger and console, press ALT-D (or CTRL-D on OS X).
 * More information on the logger can be found at
 * http://web.archive.org/web/20060512041505/gleepglop.com/javascripts/logger/
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TJavascriptLogger extends \Prado\Web\UI\WebControls\TWebControl
{
	private static $_keyCodes = [
		'0' => 48, '1' => 49, '2' => 50, '3' => 51, '4' => 52, '5' => 53, '6' => 54, '7' => 55, '8' => 56, '9' => 57,
		'a' => 65, 'b' => 66, 'c' => 67, 'd' => 68, 'e' => 69, 'f' => 70, 'g' => 71, 'h' => 72,
		'i' => 73, 'j' => 74, 'k' => 75, 'l' => 76, 'm' => 77, 'n' => 78, 'o' => 79, 'p' => 80,
		'q' => 81, 'r' => 82, 's' => 83, 't' => 84, 'u' => 85, 'v' => 86, 'w' => 87, 'x' => 88, 'y' => 89, 'z' => 90];

	/**
	 * @return string tag name of the panel
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * @param string $value keyboard key for toggling the console, default is J.
	 */
	public function setToggleKey($value)
	{
		$this->setViewState('ToggleKey', $value, 'j');
	}

	/**
	 * @return string keyboard key for toggling the console.
	 */
	public function getToggleKey()
	{
		return $this->getViewState('ToggleKey', 'j');
	}

	/**
	 * Registers the required logger javascript.
	 * @param TEventParameter $param event parameter
	 */
	public function onPreRender($param)
	{
		$key = strtolower($this->getToggleKey());
		$code = isset(self::$_keyCodes[$key]) ? self::$_keyCodes[$key] : 74;
		$js = "var logConsole; jQuery(function() { logConsole = new LogConsole($code)}); ";
		$cs = $this->getPage()->getClientScript();
		$cs->registerBeginScript($this->getClientID(), $js);
		$cs->registerPradoScript('logger');
	}

	/**
	 * Register the required javascript libraries and
	 * display some general usage information.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		$code = strtoupper($this->getToggleKey());
		$info = '(<a href="http://web.archive.org/web/20060512041505/gleepglop.com/javascripts/logger/" target="_blank">more info</a>).';
		$link = '<a href="javascript:if(logConsole)logConsole.toggle()">toggle the javascript log console.</a>';
		$usage = 'Press ALT-' . $code . ' (Or CTRL-' . $code . ' on OS X) to';
		$writer->write("{$usage} {$link} {$info}");
	}
}
