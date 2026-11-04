<?php

/**
 * TUrlValidator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TUrlValidator class
 *
 * TUrlValidator validates whether the value of an associated input component
 * is an absolute URL of the form "scheme://host[:port][/path][?query][#fragment]".
 * The value fails when:
 * - the scheme is absent from {@see setValidSchemes ValidSchemes}, which defaults to "http,https";
 * - the host is not a domain name, an IPv4 address, or a bracketed IPv6 address;
 * - the port is above 65535;
 * - the value contains a space or a control character.
 *
 * A domain name with Unicode labels, such as "http://bücher.example", is an
 * internationalized domain name (IDN). With {@see setAllowIdn AllowIdn} (the
 * default), it is converted to its ASCII form under UTS #46 before its labels
 * are checked; otherwise it fails. With {@see setRequireTld RequireTld} (the
 * default), a domain name needs at least two labels and an alphabetic or
 * "xn--" top-level label, so "http://localhost" fails. An IP address host needs
 * no top-level label.
 *
 * The validation checks the syntax only; it makes no network request. The
 * validation succeeds if the associated input control contains empty input.
 * Use a {@see \Prado\Web\UI\WebControls\TRequiredFieldValidator} to ensure the
 * input is not empty.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TUrlValidator extends TBaseValidator
{
	/**
	 * The URL syntax: the scheme, optional user information, the host, the
	 * optional port, and the rest of the URL.
	 */
	public const URL_REGEXP = '/^(?<scheme>[a-z][a-z0-9+.\-]*):\/\/(?:[^\x00-\x20\x7f\/?#@\[\]]+@)?(?<host>\[[0-9a-f:.]+\]|[^\x00-\x20\x7f\/?#@:\[\]%]+)(?::(?<port>[0-9]{1,5}))?(?:[\/?#][^\x00-\x20\x7f]*)?$/iu';

	/**
	 * The ASCII domain name: labels of letters, digits, hyphens, and underscores
	 * that start and end with a letter, digit, or underscore.
	 */
	public const DOMAIN_REGEXP = '/^[a-z0-9_](?:[a-z0-9_\-]{0,61}[a-z0-9_])?(?:\.[a-z0-9_](?:[a-z0-9_\-]{0,61}[a-z0-9_])?)*$/';

	/**
	 * The top-level label required by {@see setRequireTld RequireTld}.
	 */
	public const TLD_REGEXP = '/^(?:[a-z]{2,63}|xn--[a-z0-9\-]{1,59})$/';

	/**
	 * The maximum length of an ASCII domain name.
	 */
	public const MAX_DOMAIN_LENGTH = 253;

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TUrlValidator';
	}

	/**
	 * @return string comma separated list of allowed URL schemes. Defaults to "http,https".
	 */
	public function getValidSchemes()
	{
		return $this->getViewState('ValidSchemes', 'http,https');
	}

	/**
	 * Sets the URL schemes that the value may use, compared case-insensitively.
	 * An empty list allows every scheme.
	 * @param string $value comma separated list of allowed URL schemes.
	 */
	public function setValidSchemes($value)
	{
		$this->setViewState('ValidSchemes', TPropertyValue::ensureString($value), 'http,https');
	}

	/**
	 * @return string[] the lower case allowed URL schemes; empty to allow every scheme.
	 */
	public function getValidSchemeList(): array
	{
		$schemes = array_map(fn ($s) => strtolower(trim($s)), explode(',', $this->getValidSchemes()));
		return array_values(array_unique(array_filter($schemes, fn ($s) => $s !== '')));
	}

	/**
	 * @return bool whether the host may be an internationalized domain name. Defaults to true.
	 */
	public function getAllowIdn()
	{
		return $this->getViewState('AllowIdn', true);
	}

	/**
	 * Sets whether the host may be an internationalized domain name. Its
	 * Unicode labels are converted to ASCII under UTS #46 before they are checked.
	 * @param bool $value whether the host may be an internationalized domain name.
	 */
	public function setAllowIdn($value)
	{
		$this->setViewState('AllowIdn', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether a domain name host requires a top-level label. Defaults to true.
	 */
	public function getRequireTld()
	{
		return $this->getViewState('RequireTld', true);
	}

	/**
	 * Sets whether a domain name host requires at least two labels and an
	 * alphabetic or "xn--" top-level label. An IP address host is exempt.
	 * @param bool $value whether a domain name host requires a top-level label.
	 */
	public function setRequireTld($value)
	{
		$this->setViewState('RequireTld', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input is empty or is a valid URL.
	 * @return bool whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		$value = $this->getValidationValue($this->getValidationTarget());
		if ($value === '' || $value === null) {
			return true;
		}
		return is_string($value) && $this->isValidUrl($value);
	}

	/**
	 * Checks a URL against the scheme, host, and port restrictions.
	 * @param string $value the URL.
	 * @return bool whether the URL is valid.
	 */
	public function isValidUrl(string $value): bool
	{
		if (preg_match(self::URL_REGEXP, $value, $matches) !== 1) {
			return false;
		}
		$schemes = $this->getValidSchemeList();
		if ($schemes !== [] && !in_array(strtolower($matches['scheme']), $schemes, true)) {
			return false;
		}
		if (($matches['port'] ?? '') !== '' && (int) $matches['port'] > 65535) {
			return false;
		}
		return $this->isValidHost($matches['host']);
	}

	/**
	 * Checks the host of a URL: a bracketed IPv6 address, an IPv4 address, or a domain name.
	 * @param string $host the host.
	 * @return bool whether the host is valid.
	 */
	protected function isValidHost(string $host): bool
	{
		if ($host[0] === '[') {
			return filter_var(substr($host, 1, -1), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
		}
		if (preg_match('/^[0-9.]+$/', $host)) {
			return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
		}
		if (preg_match('/[^\x00-\x7f]/', $host)) {
			if (!$this->getAllowIdn()) {
				return false;
			}
			$host = idn_to_ascii($host, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
			if ($host === false) {
				return false;
			}
		}
		$host = strtolower($host);
		if (strlen($host) > self::MAX_DOMAIN_LENGTH || !preg_match(self::DOMAIN_REGEXP, $host)) {
			return false;
		}
		if ($this->getRequireTld()) {
			$labels = explode('.', $host);
			return count($labels) > 1 && preg_match(self::TLD_REGEXP, end($labels)) === 1;
		}
		return true;
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['ValidSchemes'] = $this->getValidSchemeList();
		$options['AllowIdn'] = $this->getAllowIdn();
		$options['RequireTld'] = $this->getRequireTld();
		return $options;
	}
}
