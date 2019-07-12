<?php
/**
 * TJuiDialog class file.
 *
 * @author  David Otto <ottodavid[at]gmx[dot]net>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
 */

namespace Prado\Web\UI\JuiControls;

use Prado\Web\UI\TControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveControlAdapter;
use Prado\Web\Javascripts\TJavaScriptLiteral;

/**
 * TJuiDialogButton class
 *
 * This button must be child of a TJuiDialog. It can be used to bind an callback
 * to the buttons of the dialog.
 *
 * <code>
 * <com:TJuiDialog> * >
 * Text
 * 	<com:TJuiDialogButton Text="Ok" OnClick="Ok" />
 *
 * </com:TJuiDialog>
 * </code>
 *
 * @author David Otto <ottodavid[at]gmx[dot]net>
 * @package Prado\Web\UI\JuiControls
 * @since 3.3
 */
class TJuiDialogButton extends TControl implements ICallbackEventHandler, IActiveControl
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
	 * Array containing defined javascript options
	 * @return array
	 */
	public function getPostBackOptions()
	{
		return [
			'text' => $this->getText(),
			'click' => new TJavaScriptLiteral(
				"function(){new Prado.Callback('" . $this->getUniqueID() . "', 'onClick');}"
			),
		];
	}

	/**
	 * @return string caption of the button
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * @param string $value caption of the button
	 */
	public function setText($value)
	{
		$this->setViewState('Text', $value, '');
	}

	/**
	 * Raises the OnClick event
	 * @param object $params event parameters
	 */
	public function onClick($params)
	{
		$this->raiseEvent('OnClick', $this, $params);
	}

	/**
	 * Raises callback event.
	 * raises the appropriate event(s) (e.g. OnClick)
	 * @param TCallbackEventParameter $param the parameter associated with the callback event
	 */
	public function raiseCallbackEvent($param)
	{
		if ($param->CallbackParameter === 'onClick') {
			$this->onClick($param);
		}
	}
}
