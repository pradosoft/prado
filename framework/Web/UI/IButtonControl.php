<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

/**
 * IButtonControl interface
 *
 * IButtonControl specifies the common properties and events that must
 * be implemented by a button control, such as {@link TButton}, {@link TLinkButton},
 * {@link TImageButton}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
interface IButtonControl
{
	/**
	 * @return string caption of the button
	 */
	public function getText();

	/**
	 * @param string $value caption of the button
	 */
	public function setText($value);

	/**
	 * @return bool whether postback event trigger by this button will cause input validation
	 */
	public function getCausesValidation();

	/**
	 * @param bool $value whether postback event trigger by this button will cause input validation
	 */
	public function setCausesValidation($value);

	/**
	 * @return string the command name associated with the {@link onCommand OnCommand} event.
	 */
	public function getCommandName();

	/**
	 * @param string $value the command name associated with the {@link onCommand OnCommand} event.
	 */
	public function setCommandName($value);

	/**
	 * @return string the parameter associated with the {@link onCommand OnCommand} event
	 */
	public function getCommandParameter();

	/**
	 * @param string $value the parameter associated with the {@link onCommand OnCommand} event.
	 */
	public function setCommandParameter($value);

	/**
	 * @return string the group of validators which the button causes validation upon postback
	 */
	public function getValidationGroup();

	/**
	 * @param string $value the group of validators which the button causes validation upon postback
	 */
	public function setValidationGroup($value);

	/**
	 * Raises <b>OnClick</b> event.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onClick($param);

	/**
	 * Raises <b>OnCommand</b> event.
	 * @param TCommandEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onCommand($param);

	/**
	 * @param bool $value set by a panel to register this button as the default button for the panel.
	 */
	public function setIsDefaultButton($value);

	/**
	 * @return bool true if this button is registered as a default button for a panel.
	 */
	public function getIsDefaultButton();
}
