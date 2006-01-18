<?php
/**
 * TJavascriptLogger component class file.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Wei Zhuo. All rights reserved.
 *
 * To contact the author write to {@link mailto:weizhuo[at]gmail[dot]com Wei Zhuo}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.2 $  $Date: 2005/11/06 23:02:33 $
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
 * @version $Revision: 1.2 $  $Date: 2005/11/06 23:02:33 $
 * @package System.Web.UI.WebControls
 * @since 2.0.2
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
	 * Register the required javascript libraries and 
	 * display some general usage information.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function renderContents($writer)
	{
		$this->Page->ClientScript->registerClientScript('logger');
		$info = '(<a href="http://gleepglop.com/javascripts/logger/" target="_blank">more info</a>).';
		$usage = 'Press ALT-D (Or CTRL-D on OS X) to toggle the javascript log console';
		$writer->write("{$usage} {$info}");
		parent::renderContents($writer);
	}
}

?>