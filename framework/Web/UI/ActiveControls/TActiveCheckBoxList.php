<?php
/**
 * TActiveCheckBoxList class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load active control adapter.
 */
use Prado\Prado;
use Prado\Web\UI\WebControls\TCheckBoxList;

/**
 * TActiveCheckBoxList class.
 *
 * The active control counter part to checkbox list control.
 * The {@link setAutoPostBack AutoPostBack} property is set to true by default.
 * Thus, when a checkbox is clicked a {@link onCallback OnCallback} event is
 * raised after {@link OnSelectedIndexChanged} event.
 *
 * With {@link TBaseActiveControl::setEnableUpdate() ActiveControl.EnableUpdate}
 * set to true (default is true), changes to the selection will be updated
 * on the client side.
 *
 * List items can not be changed dynamically during a callback request.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TActiveCheckBoxList extends TCheckBoxList implements IActiveControl, ICallbackEventHandler
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveListControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		$this->setAdapter(new TActiveListControlAdapter($this));
		$this->setAutoPostBack(true);
		parent::__construct();
	}

	/**
	 * @return TBaseActiveCallbackControl standard callback control options.
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
		return $this->getAdapter()->getBaseActiveControl()->getClientSide();
	}

	/**
	 * Since at least a control with id is needed to update the content during callback,
	 * always force the surrounding span to be rendered, so initially empty lists can be
	 * updated later.
	 *@return bool always true
	 */
	protected function getSpanNeeded()
	{
		return true;
	}

	/**
	 * Override parent implementation, no javascript is rendered here instead
	 * the javascript required for active control is registered in {@link addAttributesToRender}.
	 * @param mixed $writer
	 */
	protected function renderClientControlScript($writer)
	{
	}

	/**
	 * Creates a control used for repetition (used as a template).
	 * @return TActiveCheckBoxListItem the control to be repeated
	 */
	protected function createRepeatedControl()
	{
		$control = new TActiveCheckBoxListItem;
		$control->getAdapter()->setBaseActiveControl($this->getActiveControl());
		return $control;
	}

	/**
	 * Raises the callback event. This method is required by {@link
	 * ICallbackEventHandler} interface.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
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

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control.
	 * @param mixed $writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if ($this->getEnableClientScript()
			&& $this->getAutoPostBack()
			&& $this->getPage()->getClientSupportsJavaScript()) {
			$this->getActiveControl()->registerCallbackClientScript(
				$this->getClientClassName(),
				$this->getPostBackOptions()
		  );
		}
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveCheckBoxList';
	}
}
