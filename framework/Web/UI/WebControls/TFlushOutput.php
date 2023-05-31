<?php
/**
 * TFlushOutput class file
 *
 * @author Berczi Gabor <gabor.berczi@devworx.hu>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TFlushOutput class.
 *
 * TFlushOutput enables forced flushing of the current output buffer
 * at (a) certain point(s) in the page, after rendering of all previous
 * controls has been completed.
 *
 * To use TFlushOutput, simply place it in a template where you want
 * the have the output buffered between the start of the page or the
 * last TFlushOutput to be sent to the client immediately
 * ```php
 * <com:TFlushOutput />
 * ```
 *
 * You can specify whether you want to keep buffering of the output
 * (if it was enabled) till the next occourence of a <com: TFlushOutput />
 * or the end of the page rendering, or stop buffering, by using the
 * {@link setContinueBuffering ContinueBuffering}.
 *
 * @author Berczi Gabor <gabor.berczi@devworx.hu>
 * @since 3.1
 */
class TFlushOutput extends \Prado\Web\UI\TControl
{
	/**
	 * @var bool whether to continue buffering of output
	 */
	private $_continueBuffering = true;


	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setEnableViewState(false);
	}

	/**
	 * @return bool Tells whether buffering of output can continue after this point
	 */
	public function getContinueBuffering()
	{
		return $this->_continueBuffering;
	}

	/**
	 * @param bool $value sets whether buffering of output can continue after this point
	 */
	public function setContinueBuffering($value)
	{
		$this->_continueBuffering = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Flushes the output of all completely rendered controls to the client.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		// ajax responses can't be parsed by the client side before loaded and returned completely,
		// so don't bother with flushing output somewhere mid-page if refreshing in a callback
		if (!$this->getPage()->getIsCallback()) {
			$this->getPage()->flushWriter();
		}
	}
}
