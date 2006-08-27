<?php
/**
 * TActivePanel file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version :   :
 * @package System.Web.UI.ActiveControls
 */

/**
 * Load active control adapter.
 */
Prado::using('System.Web.UI.ActiveControls.TActiveControlAdapter');

/**
 * TActivePanel is the TPanel active control counterpart.
 *
 * TActivePanel allows the client-side panel contents to be updated during a
 * callback response using the {@link flush} method.
 *
 * Example: Assume $param is an instance of TCallbackEventParameter attached to
 * the OnCallback event a TCallback with ID "callback1", and
 * "panel1" is the ID of a TActivePanel.
 * <code>
 * function callback1_requested($sender, $param)
 * {
 * 	   $this->panel1->render($param->getNewWriter());
 * }
 * </code>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  Sun Jun 18 01:23:54 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TActivePanel extends TPanel implements IActiveControl
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
	 * @return TBaseActiveControl standard active control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * Renders and replaces the panel's content on the client-side.
	 * @param THtmlWriter html writer
	 */
	public function render($writer)
	{
		parent::render($writer);
		if($this->getActiveControl()->canUpdateClientSide())
			$this->getPage()->getCallbackClient()->replaceContent($this,$writer);
	}
}

?>