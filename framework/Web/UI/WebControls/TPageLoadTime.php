<?php
/**
 * TPageLoadTime class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TPageLoadTime class.
 *
 * Writes the amount of time taken from Request start to rendering the contents of this control.
 * This is the longest possible time to wait.  Localize the suffix "s" with
 * {@link setSecondSuffix} or use PRADO localization.
 *
 * @author Brad Anderson <belisoful@icloud.com>
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
		$writer->write(round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 5) . Prado::localize($this->getSecondSuffix()));
	}

	/**
	 * @return string the string that is appended to the time.  default 's' for seconds.
	 */
	public function getSecondSuffix()
	{
		return $this->getViewState('Suffix', 's');
	}

	/**
	 * @param string $suffix the string that is appended to the time.
	 */
	public function setSecondSuffix($suffix)
	{
		$this->setViewState('Suffix', TPropertyValue::ensureString($suffix), 's');
	}
}
