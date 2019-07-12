<?php
/**
 * TValidationSummary class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\TClientSideOptions;

/**
 * TClientSideValidationSummaryOptions class.
 *
 * Client-side validation summary events such as {@link setOnHideSummary
 * OnHideSummary} and {@link setOnShowSummary OnShowSummary} can be modified
 * through the {@link TBaseValidator:: getClientSide ClientSide} property of a
 * validation summary.
 *
 * The <tt>OnHideSummary</tt> event is raise when the validation summary
 * requests to hide the messages.
 *
 * The <tt>OnShowSummary</tt> event is raised when the validation summary
 * requests to show the messages.
 *
 * See the quickstart documentation for further details.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TClientSideValidationSummaryOptions extends TClientSideOptions
{
	/**
	 * @return string javascript code for client-side OnHideSummary event.
	 */
	public function getOnHideSummary()
	{
		return $this->getOption('OnHideSummary');
	}

	/**
	 * Client-side OnHideSummary validation summary event is raise when all the
	 * validators are valid. This will override the default client-side
	 * validation summary behaviour.
	 * @param string $javascript javascript code for client-side OnHideSummary event.
	 */
	public function setOnHideSummary($javascript)
	{
		$this->setFunction('OnHideSummary', $javascript);
	}

	/**
	 * Client-side OnShowSummary event is raise when one or more validators are
	 * not valid. This will override the default client-side validation summary
	 * behaviour.
	 * @param string $javascript javascript code for client-side OnShowSummary event.
	 */
	public function setOnShowSummary($javascript)
	{
		$this->setFunction('OnShowSummary', $javascript);
	}

	/**
	 * @return string javascript code for client-side OnShowSummary event.
	 */
	public function getOnShowSummary()
	{
		return $this->getOption('OnShowSummary');
	}

	/**
	 * Ensure the string is a valid javascript function. The code block
	 * is enclosed with "function(summary, validators){ }" block.
	 * @param string $javascript javascript code.
	 * @return string javascript function code.
	 */
	protected function ensureFunction($javascript)
	{
		return "function(summary, validators){ {$javascript} }";
	}
}
