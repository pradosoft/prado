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
 * Writes the amount of time taken from Request start to rendering the contents of this control.
 * This is the longest possible time to wait
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Web\UI\WebControls
 * @since 4.2.0
 */
class TPageLoadTime extends TLabel
{
	/**
	 * writes the difference in time that the request started to the moment of this method call.
	 * @param mixed $writer
	 */
	public function renderContents($writer)
	{
		$writer->write((round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 100000) / 100000) . 's');
	}
}