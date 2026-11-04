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
use Prado\Util\Clock\TMonotonicClockAwareTrait;

/**
 * TPageLoadTime class.
 *
 * Writes the amount of time taken from Request start to rendering the contents of this control.
 * This is the longest possible time to wait.  Localize the suffix "s" with
 * {@see setSecondSuffix} or use PRADO localization.
 *
 * The time is measured on the {@see getMonotonicClock() monotonic clock} from
 * {@see \Prado\Prado::getRequestStartNanoseconds()}, so a wall-clock change during the
 * request does not lengthen or shorten it.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TPageLoadTime extends TLabel
{
	use TMonotonicClockAwareTrait;

	/**
	 * Writes the seconds from the request start to this call, and the suffix.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	public function renderContents($writer)
	{
		$writer->write(round($this->getLoadTime(), 5) . Prado::localize($this->getSecondSuffix()));
	}

	/**
	 * @return float the seconds from the request start to now, on the monotonic clock
	 * @since 4.4.0
	 */
	public function getLoadTime(): float
	{
		return $this->getMonotonicClock()->elapsed(Prado::getRequestStartNanoseconds());
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
