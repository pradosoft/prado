<?php
/**
 * TJavascriptLogger class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Web.UI.WebControls
 */

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
 * http://gleepglop.com/javascripts/logger/
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TJavascriptLogger extends TWebControl
{
	/**
	 * @return string tag name of the panel
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * Registers the required logger javascript.
	 * @param TEventParameter event parameter
	 */
	public function onPreRender($param)
	{
		$this->getPage()->getClientScript()->registerPradoScript('logger');
	}

	/**
	 * Register the required javascript libraries and
	 * display some general usage information.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		$info = '(<a href="http://gleepglop.com/javascripts/logger/" target="_blank">more info</a>).';
		$usage = 'Press ALT-D (Or CTRL-D on OS X) to toggle the javascript log console';
		$writer->write("{$usage} {$info}");
	}
}

?>