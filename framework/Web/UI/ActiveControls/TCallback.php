<?php
/**
 * TCallback class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load active control adapter.
 */
use Prado\Prado;
use Prado\Web\UI\TControl;

/**
 * TCallback component class.
 *
 * The TCallback provides a basic callback handler that can be invoked from the
 * client side by running the javascript code obtained from the
 * {@see \Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl::getJavascript ActiveControl.Javascript} property.
 * The event {@see onCallback OnCallback} is raised when a callback is requested made.
 *
 * Example usage:
 * ```php
 * 	<com:TCallback ID="callback1" OnCallback="callback1_Requested" />
 *  <script>
 * 		function do_callback1()
 *      {
 *           var request = <%= $this->callback1->ActiveControl->Javascript %>;
 *			 request.dispatch();
 *      }
 *  </script>
 *  <div onclick="do_callback1()">Click Me!</div>
 * ```
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 * @method TActiveControlAdapter getAdapter()
 */
class TCallback extends TControl implements IActiveControl, ICallbackEventHandler
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, call this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	/**
	 * @return TBaseActiveCallbackControl standard callback options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * @return TCallbackClientSide client side request options.
	 */
	public function getClientSide()
	{
		return $this->getActiveControl()->getClientSide();
	}

	/**
	 * Raises the callback event. This method is required by
	 * {@see ICallbackEventHandler ICallbackEventHandler} interface. If
	 * {@see getCausesValidation ActiveControl.CausesValidation} is true,
	 * it will invoke the page's {@see \Prado\Web\UI\TPage::validate validate} method first.
	 * It will raise {@see onCallback OnCallback} event. This method is mainly
	 * used by framework and control developers.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		if ($this->getActiveControl()->canCauseValidation()) {
			$this->getPage()->validate($this->getActiveControl()->getValidationGroup());
		}
		$this->onCallback($param);
	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onCallback($param)
	{
		$this->raiseEvent('OnCallback', $this, $param);
	}
}
