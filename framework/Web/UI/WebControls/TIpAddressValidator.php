<?php

/**
 * TIpAddressValidator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;

/**
 * TIpAddressValidator class
 *
 * TIpAddressValidator validates whether the value of an associated input component
 * is an IPv4 or IPv6 address, optionally in CIDR notation, such as "192.0.2.1",
 * "2001:db8::1", or "10.0.0.0/8". The value fails when:
 * - it is not a dotted decimal IPv4 address without leading zeros or an IPv6
 *   address without a zone;
 * - its version is excluded by {@see setVersion Version};
 * - it has a "/prefix" without {@see setAllowCidr AllowCidr}, or lacks one with
 *   {@see setRequireCidr RequireCidr};
 * - its prefix has a leading zero or exceeds 32 for IPv4 or 128 for IPv6;
 * - {@see setRanges Ranges} rejects it.
 *
 * {@see setRanges Ranges} is an ordered, comma separated list of entries. An
 * entry is an address, a CIDR block, or a range name, optionally prefixed with
 * "!". The first entry that matches decides: valid for a plain entry, invalid
 * for a "!" entry. A value no entry matches is invalid. A plain entry matches
 * a value that lies inside it. A "!" entry matches a value that shares any
 * address with it. An empty list allows every address.
 *
 * The range names are the keys of {@see NAMED_RANGES}, from the IANA special
 * purpose address registries: "private", "loopback", "linklocal", "multicast",
 * "documentation", "reserved", and "any". The name "public" is every address
 * outside the other named ranges. For example:
 * - "public": public addresses only.
 * - "private": private addresses only.
 * - "!10.1.0.0/16, 10.0.0.0/8": addresses in 10.0.0.0/8 outside 10.1.0.0/16.
 *
 * An IPv4-mapped IPv6 address, "::ffff:a.b.c.d", also matches the IPv4 blocks of
 * its embedded address, so "::ffff:127.0.0.1" is in the "loopback" range.
 *
 * The same checks run in the browser. The validation succeeds if the associated
 * input control contains empty input. Use a
 * {@see \Prado\Web\UI\WebControls\TRequiredFieldValidator} to ensure the input
 * is not empty.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TIpAddressValidator extends TBaseValidator
{
	/**
	 * The named ranges of {@see setRanges Ranges}, as CIDR blocks. All but "any"
	 * are not globally reachable or not unicast.
	 */
	public const NAMED_RANGES = [
		'private' => ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'fc00::/7'],
		'loopback' => ['127.0.0.0/8', '::1/128'],
		'linklocal' => ['169.254.0.0/16', 'fe80::/10'],
		'multicast' => ['224.0.0.0/4', 'ff00::/8'],
		'documentation' => ['192.0.2.0/24', '198.51.100.0/24', '203.0.113.0/24', '2001:db8::/32', '3fff::/20'],
		'reserved' => ['0.0.0.0/8', '100.64.0.0/10', '192.0.0.0/24', '198.18.0.0/15', '240.0.0.0/4', '::/128', '64:ff9b:1::/48', '100::/64', '2001::/23', '5f00::/16', 'fec0::/10'],
		'any' => ['0.0.0.0/0', '::/0'],
	];

	/**
	 * The range name for every address outside the other named ranges.
	 */
	public const PUBLIC_RANGE = 'public';

	/**
	 * A CIDR prefix length without leading zeros.
	 */
	public const PREFIX_REGEXP = '/^(?:0|[1-9][0-9]{0,2})$/';

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TIpAddressValidator';
	}

	/**
	 * @return string the accepted {@see TIpAddressVersion}. Defaults to Any.
	 */
	public function getVersion()
	{
		return $this->getViewState('Version', TIpAddressVersion::Any);
	}

	/**
	 * Sets the IP address version that the value may have.
	 * @param string $value a {@see TIpAddressVersion}.
	 */
	public function setVersion($value)
	{
		$this->setViewState('Version', TPropertyValue::ensureEnum($value, TIpAddressVersion::class), TIpAddressVersion::Any);
	}

	/**
	 * @return bool whether the value may be in CIDR notation. Defaults to false.
	 */
	public function getAllowCidr()
	{
		return $this->getViewState('AllowCidr', false);
	}

	/**
	 * Sets whether the value may end in a "/prefix", such as "10.0.0.0/8".
	 * @param bool $value whether the value may be in CIDR notation.
	 */
	public function setAllowCidr($value)
	{
		$this->setViewState('AllowCidr', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether the value must be in CIDR notation. Defaults to false.
	 */
	public function getRequireCidr()
	{
		return $this->getViewState('RequireCidr', false);
	}

	/**
	 * Sets whether the value must end in a "/prefix". True also allows CIDR notation.
	 * @param bool $value whether the value must be in CIDR notation.
	 */
	public function setRequireCidr($value)
	{
		$this->setViewState('RequireCidr', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string the comma separated, ordered range entries. Defaults to '', every address.
	 */
	public function getRanges()
	{
		return $this->getViewState('Ranges', '');
	}

	/**
	 * Sets the ordered range entries that accept or reject the value, such as
	 * "!10.1.0.0/16, 10.0.0.0/8" or "public". An entry is an address, a CIDR
	 * block, or a range name, optionally prefixed with "!".
	 * @param string $value comma separated range entries.
	 */
	public function setRanges($value)
	{
		$this->setViewState('Ranges', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Resolves {@see getRanges Ranges} into its entries. Each entry has "Negate",
	 * whether it starts with "!"; "Blocks", its CIDR blocks; and "Outside",
	 * whether it matches the addresses outside "Blocks", as "public" does.
	 * @throws TConfigurationException when an entry is not an address, a CIDR
	 *   block, or a range name.
	 * @return array[] the range entries in order; empty to allow every address.
	 */
	public function getRangeList(): array
	{
		$list = [];
		foreach (explode(',', $this->getRanges()) as $entry) {
			if (($entry = trim($entry)) === '') {
				continue;
			}
			$negate = $entry[0] === '!';
			$range = strtolower(ltrim(substr($entry, (int) $negate)));
			if ($range === self::PUBLIC_RANGE) {
				$blocks = array_merge(...array_values(array_diff_key(self::NAMED_RANGES, ['any' => true])));
			} elseif (isset(self::NAMED_RANGES[$range])) {
				$blocks = self::NAMED_RANGES[$range];
			} elseif ($this->parseBlock($range) !== null) {
				$blocks = [$range];
			} else {
				throw new TConfigurationException('ipaddressvalidator_range_invalid', $this::class, $entry);
			}
			$list[] = ['Negate' => $negate, 'Blocks' => $blocks, 'Outside' => $range === self::PUBLIC_RANGE];
		}
		return $list;
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input is empty or is a valid IP address.
	 * @return bool whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		$value = $this->getValidationValue($this->getValidationTarget());
		if ($value === '' || $value === null) {
			return true;
		}
		return is_string($value) && $this->isValidIpAddress($value);
	}

	/**
	 * Checks an IP address against the version, CIDR, and range restrictions.
	 * @param string $value the IP address, optionally with a "/prefix".
	 * @return bool whether the IP address is valid.
	 */
	public function isValidIpAddress(string $value): bool
	{
		$block = $this->parseBlock($value);
		if ($block === null) {
			return false;
		}
		[$bytes, , $hasPrefix] = $block;
		$version = $this->getVersion();
		if (($version === TIpAddressVersion::IPv4 && strlen($bytes) !== 4) || ($version === TIpAddressVersion::IPv6 && strlen($bytes) !== 16)) {
			return false;
		}
		if ($hasPrefix ? !$this->getAllowCidr() && !$this->getRequireCidr() : $this->getRequireCidr()) {
			return false;
		}
		$ranges = $this->getRangeList();
		if ($ranges === []) {
			return true;
		}
		$candidates = $this->candidateBlocks($block);
		foreach ($ranges as $range) {
			if ($this->rangeMatches($range, $candidates)) {
				return !$range['Negate'];
			}
		}
		return false;
	}

	/**
	 * Parses an IP address with an optional "/prefix" into a block.
	 * @param string $value the IP address, optionally with a "/prefix".
	 * @return ?array the packed address, the prefix length, and whether the
	 *   value has a prefix; null when the value is not valid.
	 */
	protected function parseBlock(string $value): ?array
	{
		$parts = explode('/', $value);
		if (count($parts) > 2 || filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
			return null;
		}
		$bytes = inet_pton($parts[0]);
		$bits = strlen($bytes) * 8;
		if (!isset($parts[1])) {
			return [$bytes, $bits, false];
		}
		if (!preg_match(self::PREFIX_REGEXP, $parts[1]) || (int) $parts[1] > $bits) {
			return null;
		}
		return [$bytes, (int) $parts[1], true];
	}

	/**
	 * Returns the blocks a value matches the ranges with: the value itself and,
	 * for an IPv4-mapped IPv6 block, the block of its embedded IPv4 address.
	 * @param array $block the packed address and prefix length of the value.
	 * @return array[] the packed address and prefix length of each block.
	 */
	protected function candidateBlocks(array $block): array
	{
		[$bytes, $prefix] = $block;
		$candidates = [[$bytes, $prefix]];
		if (strlen($bytes) === 16 && $prefix >= 96 && strncmp($bytes, str_repeat("\0", 10) . "\xff\xff", 12) === 0) {
			$candidates[] = [substr($bytes, 12), $prefix - 96];
		}
		return $candidates;
	}

	/**
	 * Checks whether a range entry matches the value. A plain entry matches a
	 * value inside one of its blocks; a negated entry matches a value that
	 * overlaps one of its blocks. An "Outside" entry matches the complement.
	 * @param array $range a range entry of {@see getRangeList}.
	 * @param array[] $candidates the blocks of the value from {@see candidateBlocks}.
	 * @return bool whether the range entry matches the value.
	 */
	protected function rangeMatches(array $range, array $candidates): bool
	{
		$inside = $range['Negate'] === $range['Outside'];
		$found = false;
		foreach ($range['Blocks'] as $cidr) {
			[$bytes, $prefix] = $this->parseBlock($cidr);
			foreach ($candidates as [$candidateBytes, $candidatePrefix]) {
				if (strlen($bytes) !== strlen($candidateBytes) || ($inside && $candidatePrefix < $prefix)) {
					continue;
				}
				if (self::prefixMatches($bytes, $candidateBytes, min($prefix, $candidatePrefix))) {
					$found = true;
					break 2;
				}
			}
		}
		return $found !== $range['Outside'];
	}

	/**
	 * Checks whether two packed addresses share their leading bits.
	 * @param string $a a packed address.
	 * @param string $b a packed address of the same length.
	 * @param int $bits the number of leading bits to compare.
	 * @return bool whether the leading bits are equal.
	 */
	public static function prefixMatches(string $a, string $b, int $bits): bool
	{
		$bytes = intdiv($bits, 8);
		if (strncmp($a, $b, $bytes) !== 0) {
			return false;
		}
		if (($rest = $bits % 8) === 0) {
			return true;
		}
		$mask = (0xff00 >> $rest) & 0xff;
		return (ord($a[$bytes]) & $mask) === (ord($b[$bytes]) & $mask);
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['Version'] = $this->getVersion();
		$options['AllowCidr'] = $this->getAllowCidr();
		$options['RequireCidr'] = $this->getRequireCidr();
		$options['Ranges'] = $this->getRangeList();
		return $options;
	}
}
