<?php
/**
 * TPageLoadTime class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TPageLoadTime class.
 *
 * Writes the amount of time taken from application start to rendering the contents of this control.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Web\UI\WebControls
 * @since 4.2.0
 */
class TPageLoadTime extends TLabel
{
	/**
   * writes the difference in time that the application started to the moment of this method call.
   */
	public function renderContents($writer)
	{	
		$now = microtime(true);	
		$writer->write((round(($now - $this->getApplication()->getStartTime()) * 100000) / 100000).'s');
	}

}
