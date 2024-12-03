<?php

/**
 * TActiveHtmlArea5 class file.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link https://github.com/pradosoft/prado4
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\THtmlArea5;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveControlAdapter;

/**
 * TActiveHtmlArea5 class
 *
 * TActiveHtmlArea5 is the active counterpart to {@see \Prado\Web\UI\WebControls\THtmlArea5} with added support
 * for callback handling and the possibility of setting the content of the WYSIWYG
 * text editor during callback.
 *
 * For basic usage please refer to the original documentation of {@see \Prado\Web\UI\WebControls\THtmlArea5}.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @since 4.2
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveHtmlArea5 extends THtmlArea5 implements IActiveControl, ICallbackEventHandler
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
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
		return $this->getActiveControl()->getClientSide();
	}

	/**
	 * Client-side Text property can only be updated after the OnLoad stage. Setting WYSIWYG
	 * text editor content is only available if {@see getEnableVisualEdit} is enabled.
	 * @param string $value text content for the textbox
	 */
	public function setText($value)
	{
		parent::setText($value);
		if ($this->getActiveControl()->canUpdateClientSide() && $this->getHasLoadedPostData()) {
			if ($this->getEnableVisualEdit()) {
				$value = str_ireplace(["\r\n", "\n"], "", $value);
				$command = "tinymce.get('{$this->getClientID()}').setContent('{$value}')";
				$this->getPage()->getCallbackClient()->evaluateScript($command);
			} else {
				$this->getPage()->getCallbackClient()->setValue($this, $value);
			}
		}
	}

	/**
	 * Raises the callback event. This method is required by {@see
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
}
