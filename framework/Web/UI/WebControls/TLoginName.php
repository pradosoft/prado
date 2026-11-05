<?php

/**
 * TLoginName class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TComponent;
use Prado\TPropertyValue;

/**
 * TLoginName class.
 *
 * TLoginName displays the name of the current user in a `<span>`.  The user is
 * {@see \Prado\TApplication::getUser()}, set by {@see \Prado\Security\TAuthManager}
 * during authentication.
 *
 * | User | Renders |
 * |---|---|
 * | authenticated | the name, formatted by {@see setFormatString FormatString} |
 * | guest, {@see setDisplayGuest DisplayGuest} false (default) | nothing |
 * | guest, DisplayGuest true | the guest name, formatted |
 * | none, or an empty name | nothing |
 *
 * The name is read from {@see setNameProperty NameProperty}, `Name` by default.  A
 * user class that is a {@see \Prado\TComponent} can expose another property, such as
 * a display name, and NameProperty selects it with a {@see \Prado\TComponent::getSubProperty()}
 * path.
 *
 * The name is plain text and is always encoded by `htmlspecialchars()`, `&` included.
 * {@see setFormatString FormatString} is HTML from the template, and `{0}` in it is
 * replaced by the encoded name.
 *
 * ```php
 * <com:TLoginName FormatString="Signed in as <b>{0}</b>" />
 * <com:TLoginName NameProperty="DisplayName" DisplayGuest="true" />
 * ```
 *
 * The `<span>` has no role, and screen readers read it as text.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLoginName extends TWebControl
{
	/**
	 * The placeholder in {@see getFormatString FormatString} for the encoded name.
	 */
	public const NAME_PLACEHOLDER = '{0}';

	/**
	 * @return string the HTML around the name, with `{0}` as the name.  Defaults to '', the name alone.
	 */
	public function getFormatString()
	{
		return $this->getViewState('FormatString', '');
	}

	/**
	 * @param string $value the HTML around the name, with `{0}` as the name.
	 */
	public function setFormatString($value)
	{
		$this->setViewState('FormatString', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the user property path of the name.  Defaults to 'Name'.
	 */
	public function getNameProperty()
	{
		return $this->getViewState('NameProperty', 'Name');
	}

	/**
	 * Sets the user property the name is read from.  A user that is not a
	 * {@see \Prado\TComponent} always supplies {@see \Prado\Security\IUser::getName()}.
	 * @param string $value the user property path of the name; '' restores 'Name'.
	 */
	public function setNameProperty($value)
	{
		$value = TPropertyValue::ensureString($value);
		$this->setViewState('NameProperty', $value === '' ? 'Name' : $value, 'Name');
	}

	/**
	 * @return bool whether a guest's name renders.  Defaults to false.
	 */
	public function getDisplayGuest()
	{
		return $this->getViewState('DisplayGuest', false);
	}

	/**
	 * @param bool $value whether a guest's name renders.
	 */
	public function setDisplayGuest($value)
	{
		$this->setViewState('DisplayGuest', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Returns the name the control displays, read from {@see getNameProperty NameProperty}
	 * of the current user.
	 * @return string the name; '' when there is no user, or the user is a guest and
	 *   {@see getDisplayGuest DisplayGuest} is false.
	 */
	public function getUserName()
	{
		$user = $this->getUser();
		if ($user === null) {
			return '';
		}
		if ($user->getIsGuest() && !$this->getDisplayGuest()) {
			return '';
		}
		$path = $this->getNameProperty();
		if ($path === 'Name' || !($user instanceof TComponent)) {
			return TPropertyValue::ensureString($user->getName());
		}
		return TPropertyValue::ensureString($user->getSubProperty($path));
	}

	/**
	 * Renders the control when {@see getUserName()} is not empty.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	public function render($writer)
	{
		if ($this->getUserName() !== '') {
			parent::render($writer);
		}
	}

	/**
	 * Renders the encoded name, formatted by {@see getFormatString FormatString}.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	public function renderContents($writer)
	{
		$name = htmlspecialchars($this->getUserName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$format = $this->getFormatString();
		$writer->write($format === '' ? $name : strtr($format, [self::NAME_PLACEHOLDER => $name]));
	}
}
