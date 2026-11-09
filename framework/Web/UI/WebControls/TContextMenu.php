<?php

/**
 * TContextMenu class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TContextMenu class
 *
 * TContextMenu renders an action menu that opens on a right-click on its target,
 * at the pointer, in place of the browser context menu. It is a
 * {@see TDropdownMenu} without a menu button: the client control
 * `Prado.WebUI.TContextMenu` gives it the ARIA `menu` role and the menu keyboard.
 *
 * | Action on the target | Opens the menu |
 * |---|---|
 * | right-click, long press | at the pointer |
 * | Shift+F10, the ContextMenu key | below the start of the target |
 *
 * Opening focuses the first item. The menu closes on Esc, a click outside it,
 * Tab, and the activation of an item; Esc returns the focus to the target. A
 * target that holds no focusable element takes `TabIndex="0"`, so the keyboard
 * reaches it. The menu needs script; without it, the browser context menu shows.
 * ```xml
 * <com:TPanel ID="Photo" TabIndex="0">...</com:TPanel>
 * <com:TContextMenu ForControl="Photo">
 *     <com:TButton Text="Rotate" ButtonTag="Button" OnClick="rotate" />
 *     <com:TButton Text="Delete" ButtonTag="Button" OnClick="delete" />
 * </com:TContextMenu>
 * ```
 *
 * {@see setForControl ForControl} names the target: a control ID or an HTML
 * element ID. {@see setButtonText ButtonText} and {@see setPlacement Placement}
 * do not apply.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TContextMenu extends TDropdownMenu
{
	/**
	 * The client-side class of the control.
	 */
	public const CLIENT_CLASS = 'Prado.WebUI.TContextMenu';

	/**
	 * @return string the ID of the target control or HTML element. Defaults to ''.
	 */
	public function getForControl()
	{
		return $this->getViewState('ForControl', '');
	}

	/**
	 * Sets the target whose context menu this is. A control found by
	 * {@see \Prado\Web\UI\TControl::findControl} is named by its ClientID; any
	 * other value is an HTML element ID.
	 * @param string $value the control ID or HTML element ID
	 */
	public function setForControl($value)
	{
		$this->setViewState('ForControl', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string the client ID of the target: the ClientID of the control found
	 *   by {@see getForControl ForControl}, or ForControl itself.
	 */
	public function getTargetClientID()
	{
		if (($id = $this->getForControl()) === '') {
			return '';
		}
		$control = $this->findControl($id);
		return $control ? $control->getClientID() : $id;
	}

	/**
	 * @return string '', since a context menu has no menu button
	 */
	public function getButtonText()
	{
		return '';
	}

	/**
	 * Registers the stylesheets and, with a target, the client control.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		if ($this->getTargetClientID() === '') {
			TWebControl::onPreRender($param);
			$this->registerStyleSheet();
			return;
		}
		parent::onPreRender($param);
	}

	/**
	 * Adds the menu attributes to the renderer, without `data-placement`: the
	 * client control places the menu at the pointer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->removeAttribute('data-placement');
	}

	/**
	 * @return array the options of the client control: `ID` and `TargetID`
	 */
	protected function getClientOptions()
	{
		return ['ID' => $this->getClientID(), 'TargetID' => $this->getTargetClientID()];
	}
}
