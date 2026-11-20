<?php

/**
 * TEmailAddress class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

use Prado\Exceptions\TInvalidDataValueException;

/**
 * TEmailAddress class.
 *
 * TEmailAddress is an immutable mailbox: an address and an optional display name.
 * The address is validated on construction, so an instance never carries a line
 * break or a second address into a header.
 *
 * | Input | Address | Name |
 * |---|---|---|
 * | `ann@example.com` | `ann@example.com` | |
 * | `<ann@example.com>` | `ann@example.com` | |
 * | `Ann Lee <ann@example.com>` | `ann@example.com` | `Ann Lee` |
 * | `"Lee, Ann" <ann@example.com>` | `ann@example.com` | `Lee, Ann` |
 *
 * {@see parseList} splits a list on the commas and semicolons that are outside
 * quotes and angle brackets.  An internationalized domain is converted to its
 * ASCII form; a non-ASCII local part is rejected because SMTPUTF8 is not supported.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailAddress implements \Stringable
{
	/** The characters that an address part cannot contain. */
	protected const INVALID_PART = '/[\s<>()\[\]\\\\,;:@"\x00-\x1F\x7F]/';

	/** @var string the address, "local@domain". */
	private string $_address;

	/** @var string the display name, empty when there is none. */
	private string $_name;

	/**
	 * @param string $address the address, "local@domain".
	 * @param string $name the display name.
	 * @throws TInvalidDataValueException when the address or the name is invalid.
	 */
	final public function __construct(string $address, string $name = '')
	{
		$this->_address = static::normalizeAddress($address);
		$name = trim($name);
		if (preg_match('/[\x00-\x1F\x7F]/', $name)) {
			throw new TInvalidDataValueException('emailaddress_name_invalid', $address);
		}
		$this->_name = $name;
	}

	/**
	 * Converts a value to an address.
	 * @param array|string|TEmailAddress $value an address, a string to {@see parse}, or [address, name].
	 * @throws TInvalidDataValueException when the value is not an address.
	 * @return TEmailAddress the address.
	 */
	public static function from(mixed $value): TEmailAddress
	{
		if ($value instanceof TEmailAddress) {
			return $value;
		} elseif (is_string($value)) {
			return static::parse($value);
		} elseif (is_array($value) && is_string($value[0] ?? null)) {
			return new static($value[0], (string) ($value[1] ?? ''));
		}
		throw new TInvalidDataValueException('emailaddress_invalid', is_scalar($value) ? (string) $value : gettype($value));
	}

	/**
	 * Parses one mailbox: "address", "<address>", "Name <address>", or "\"Name\" <address>".
	 * @param string $mailbox the mailbox text.
	 * @throws TInvalidDataValueException when the text is not one mailbox.
	 * @return TEmailAddress the address.
	 */
	public static function parse(string $mailbox): TEmailAddress
	{
		$mailbox = trim($mailbox);
		if (preg_match('/^(.*)<([^<>]*)>$/s', $mailbox, $matches)) {
			$name = trim($matches[1]);
			if (strlen($name) > 1 && $name[0] === '"' && substr($name, -1) === '"') {
				$name = preg_replace('/\\\\(.)/s', '$1', substr($name, 1, -1));
			}
			return new static(trim($matches[2]), $name);
		}
		return new static($mailbox);
	}

	/**
	 * Parses a list of mailboxes.  A string is split on the commas and semicolons
	 * outside quotes and angle brackets; an array holds one {@see from} value per element.
	 * @param null|array|string|TEmailAddress $list the mailboxes.
	 * @throws TInvalidDataValueException when a mailbox is invalid.
	 * @return TEmailAddress[] the addresses.
	 */
	public static function parseList(mixed $list): array
	{
		if ($list === null || $list === '' || $list === []) {
			return [];
		} elseif ($list instanceof TEmailAddress) {
			return [$list];
		} elseif (is_string($list)) {
			$list = static::splitList($list);
		} elseif (!is_array($list)) {
			throw new TInvalidDataValueException('emailaddress_invalid', gettype($list));
		}
		$addresses = [];
		foreach ($list as $item) {
			$addresses[] = static::from($item);
		}
		return $addresses;
	}

	/**
	 * Splits a list on the commas and semicolons outside quotes and angle brackets.
	 * @param string $list the mailbox list.
	 * @return string[] the mailbox texts, without the empty ones.
	 */
	protected static function splitList(string $list): array
	{
		$items = [];
		$item = '';
		$quoted = $angle = false;
		$length = strlen($list);
		for ($i = 0; $i < $length; $i++) {
			$char = $list[$i];
			if ($quoted && $char === '\\' && $i + 1 < $length) {
				$item .= $char . $list[++$i];
				continue;
			}
			if ($char === '"' && !$angle) {
				$quoted = !$quoted;
			} elseif (!$quoted && ($char === '<' || $char === '>')) {
				$angle = $char === '<';
			} elseif (!$quoted && !$angle && ($char === ',' || $char === ';')) {
				$items[] = $item;
				$item = '';
				continue;
			}
			$item .= $char;
		}
		$items[] = $item;
		return array_values(array_filter(array_map('trim', $items), fn ($text) => $text !== ''));
	}

	/**
	 * Validates an address and converts an internationalized domain to ASCII.
	 * @param string $address the address.
	 * @throws TInvalidDataValueException when the address is invalid.
	 * @return string the address.
	 */
	protected static function normalizeAddress(string $address): string
	{
		$address = trim($address);
		$at = strrpos($address, '@');
		if ($at === false || $at === 0 || $at === strlen($address) - 1) {
			throw new TInvalidDataValueException('emailaddress_invalid', $address);
		}
		$local = substr($address, 0, $at);
		$domain = substr($address, $at + 1);
		if (preg_match(static::INVALID_PART, $local) || preg_match(static::INVALID_PART, $domain)) {
			throw new TInvalidDataValueException('emailaddress_invalid', $address);
		}
		if (preg_match('/[^\x20-\x7E]/', $local)) {
			throw new TInvalidDataValueException('emailaddress_local_unsupported', $address);
		}
		if (preg_match('/[^\x20-\x7E]/', $domain)) {
			$ascii = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
			if ($ascii === false) {
				throw new TInvalidDataValueException('emailaddress_invalid', $address);
			}
			$domain = $ascii;
		}
		return $local . '@' . $domain;
	}

	/**
	 * @return string the address, "local@domain".
	 */
	public function getAddress(): string
	{
		return $this->_address;
	}

	/**
	 * @return string the display name, empty when there is none.
	 */
	public function getName(): string
	{
		return $this->_name;
	}

	/**
	 * @return string the domain of the address.
	 */
	public function getDomain(): string
	{
		return substr($this->_address, strrpos($this->_address, '@') + 1);
	}

	/**
	 * Formats the mailbox for display.  The name is in UTF-8 and is quoted when it
	 * holds characters that are special in a header; the MIME builder encodes it for a message.
	 * @return string "address" or "Name <address>".
	 */
	public function format(): string
	{
		if ($this->_name === '') {
			return $this->_address;
		}
		$name = $this->_name;
		if (preg_match('/[()<>\[\]:;@\\\\,."]/', $name)) {
			$name = '"' . addcslashes($name, '"\\') . '"';
		}
		return $name . ' <' . $this->_address . '>';
	}

	/**
	 * Formats a list of addresses for display.
	 * @param TEmailAddress[] $addresses the addresses.
	 * @return string the mailboxes separated by ", ".
	 */
	public static function formatList(array $addresses): string
	{
		return implode(', ', array_map(fn (TEmailAddress $address) => $address->format(), $addresses));
	}

	/**
	 * @return string the mailbox as {@see format} writes it.
	 */
	public function __toString(): string
	{
		return $this->format();
	}
}
