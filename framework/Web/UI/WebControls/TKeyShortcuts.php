<?php

/**
 * TKeyShortcuts class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;

/**
 * TKeyShortcuts validates and normalizes keyboard shortcuts in the
 * `aria-keyshortcuts` syntax.
 *
 * A value is a space-separated list of shortcuts. A shortcut is zero or more
 * modifier keys and one key, joined by `+`:
 * ```php
 * TKeyShortcuts::normalize('ctrl+s cmd+s');   // 'Control+S Meta+S'
 * TKeyShortcuts::normalize('shift+alt+p');    // 'Alt+Shift+P'
 * TKeyShortcuts::normalize('?');              // '?'
 * ```
 *
 * Normalization:
 * - Modifiers are `Control`, `Alt`, `Shift`, and `Meta`, in that order. `Ctrl`,
 *   `Option`, `Cmd`, and `Command` are accepted as aliases.
 * - A letter key is uppercase, unless its uppercase form is more than one
 *   character (`ß`). Other single characters are kept as given.
 * - Named keys use their KeyboardEvent `key` value: `Space`, `Plus`, `Enter`,
 *   `Escape`, `Tab`, `Backspace`, `Delete`, `Insert`, `Home`, `End`, `PageUp`,
 *   `PageDown`, the arrow keys, and `F1` to `F24`. Token case is ignored, and
 *   `Esc`, `Return`, `Del`, `Ins`, `Spacebar`, `Up`, `Down`, `Left`, and `Right`
 *   are accepted as aliases.
 * - The space and plus keys are `Space` and `Plus`, since both characters separate tokens.
 * - A repeated shortcut is removed.
 *
 * {@see isReserved} identifies shortcuts used by the browser, by text editing,
 * or by focus navigation. The client registry, `Prado.KeyShortcuts`, applies the
 * same rules; `keyshortcuts.js` holds its copy of the reserved list.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see https://w3c.github.io/aria/#aria-keyshortcuts aria-keyshortcuts
 */
class TKeyShortcuts
{
	/** Modifier keys in normalized order. */
	public const MODIFIERS = ['Control', 'Alt', 'Shift', 'Meta'];

	/** Lowercase modifier names and aliases mapped to modifier keys. */
	private const MODIFIER_NAMES = [
		'control' => 'Control', 'ctrl' => 'Control',
		'alt' => 'Alt', 'option' => 'Alt',
		'shift' => 'Shift',
		'meta' => 'Meta', 'cmd' => 'Meta', 'command' => 'Meta',
	];

	/** Lowercase named keys and aliases mapped to KeyboardEvent `key` values. */
	private const KEY_NAMES = [
		'space' => 'Space', 'spacebar' => 'Space', 'plus' => 'Plus',
		'enter' => 'Enter', 'return' => 'Enter', 'escape' => 'Escape', 'esc' => 'Escape',
		'tab' => 'Tab', 'backspace' => 'Backspace', 'delete' => 'Delete', 'del' => 'Delete',
		'insert' => 'Insert', 'ins' => 'Insert', 'home' => 'Home', 'end' => 'End',
		'pageup' => 'PageUp', 'pagedown' => 'PageDown',
		'arrowup' => 'ArrowUp', 'up' => 'ArrowUp', 'arrowdown' => 'ArrowDown', 'down' => 'ArrowDown',
		'arrowleft' => 'ArrowLeft', 'left' => 'ArrowLeft', 'arrowright' => 'ArrowRight', 'right' => 'ArrowRight',
	];

	/** @var ?bool whether the mbstring extension is loaded, resolved on first use */
	private static ?bool $_mbstring = null;

	/**
	 * @var ?array<string, true> reserved shortcuts, built on first use
	 */
	private static $_reserved;

	/**
	 * Normalizes a space-separated list of shortcuts.
	 * @param ?string $value the shortcuts, in the `aria-keyshortcuts` syntax
	 * @throws TInvalidDataValueException when a shortcut is not valid
	 * @return string the normalized shortcuts, or '' for an empty value
	 */
	public static function normalize($value): string
	{
		$value = (string) $value;
		$shortcuts = [];
		foreach (preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) as $token) {
			if (($shortcut = static::normalizeShortcut($token)) === null) {
				throw new TInvalidDataValueException('keyshortcuts_invalid', $value, $token);
			}
			$shortcuts[$shortcut] = $shortcut;
		}
		return implode(' ', $shortcuts);
	}

	/**
	 * Normalizes one shortcut.
	 * @param string $shortcut one shortcut, such as 'ctrl+shift+s'
	 * @return ?string the normalized shortcut, or null when it has no key, more
	 *   than one key, an empty token, or an unknown key name
	 */
	public static function normalizeShortcut(string $shortcut): ?string
	{
		$modifiers = [];
		$key = null;
		foreach (explode('+', $shortcut) as $token) {
			$lower = strtolower($token);
			if (isset(self::MODIFIER_NAMES[$lower])) {
				$modifiers[self::MODIFIER_NAMES[$lower]] = true;
				continue;
			}
			if ($key !== null || ($key = static::normalizeKey($token)) === null) {
				return null;
			}
		}
		if ($key === null) {
			return null;
		}
		$parts = [];
		foreach (self::MODIFIERS as $modifier) {
			if (isset($modifiers[$modifier])) {
				$parts[] = $modifier;
			}
		}
		$parts[] = $key;
		return implode('+', $parts);
	}

	/**
	 * Tells whether a token is one character.
	 * With the mbstring extension loaded, `mb_strlen()` counts the characters; without
	 * it, the token is one character when it is one UTF-8 code point.
	 * @param string $token the key token
	 * @return bool whether the token is one character
	 */
	private static function isCharacter(string $token): bool
	{
		self::$_mbstring ??= extension_loaded('mbstring');
		return self::$_mbstring ? mb_strlen($token) === 1 : preg_match('/^.\z/su', $token) === 1;
	}

	/**
	 * Returns the uppercase of a character.
	 * With the mbstring extension loaded, `mb_strtoupper()` applies the full case
	 * mapping, so the result may be two characters (`ß` is `SS`); without it,
	 * `\IntlChar::toupper()` of the intl extension applies the simple mapping.
	 * @param string $character one character
	 * @return string the uppercase
	 */
	private static function upperCharacter(string $character): string
	{
		self::$_mbstring ??= extension_loaded('mbstring');
		if (self::$_mbstring) {
			return mb_strtoupper($character);
		}
		$upper = \IntlChar::toupper($character);
		return is_string($upper) ? $upper : $character;
	}

	/**
	 * Normalizes one key token.
	 * @param string $token the key token
	 * @return ?string the key value, or null for an empty token or an unknown key name
	 */
	protected static function normalizeKey(string $token): ?string
	{
		if (self::isCharacter($token)) {
			$upper = self::upperCharacter($token);
			return self::isCharacter($upper) ? $upper : $token;
		}
		$lower = strtolower($token);
		if (isset(self::KEY_NAMES[$lower])) {
			return self::KEY_NAMES[$lower];
		}
		if (preg_match('/^f([1-9]|1[0-9]|2[0-4])$/', $lower, $match)) {
			return 'F' . $match[1];
		}
		return null;
	}

	/**
	 * Returns whether a normalized shortcut is reserved.
	 *
	 * Reserved shortcuts are focus navigation and activation (Tab, Enter, Space,
	 * the arrow and paging keys), editing and clipboard keys, zoom, reload, history
	 * navigation, and browser tab and window keys.
	 * @param string $shortcut one normalized shortcut
	 * @return bool whether the shortcut is reserved
	 */
	public static function isReserved(string $shortcut): bool
	{
		return isset(static::getReservedShortcuts()[$shortcut]);
	}

	/**
	 * Returns the reserved shortcuts.
	 * @return array<string, true> the normalized reserved shortcuts as keys
	 */
	public static function getReservedShortcuts(): array
	{
		if (self::$_reserved === null) {
			$reserved = ['Tab', 'Shift+Tab', 'Control+Tab', 'Control+Shift+Tab', 'Enter', 'Space',
				'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'PageUp', 'PageDown',
				'F5', 'F6', 'F11', 'F12', 'Alt+ArrowLeft', 'Alt+ArrowRight'];
			foreach (['Control', 'Meta'] as $modifier) {
				foreach (['A', 'C', 'V', 'X', 'Z', 'Y', 'L', 'N', 'Q', 'R', 'T', 'W', 'Plus', '-', '=', '0'] as $key) {
					$reserved[] = $modifier . '+' . $key;
				}
				foreach (['N', 'T', 'W', 'Z'] as $key) {
					$reserved[] = static::normalizeShortcut($modifier . '+Shift+' . $key);
				}
			}
			self::$_reserved = array_fill_keys($reserved, true);
		}
		return self::$_reserved;
	}
}
