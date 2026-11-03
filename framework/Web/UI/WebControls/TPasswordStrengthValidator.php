<?php

/**
 * TPasswordStrengthValidator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TPasswordStrengthValidator class
 *
 * TPasswordStrengthValidator validates whether the value of an associated
 * input component is a password of at least {@see setMinStrength MinStrength},
 * which defaults to {@see TPasswordStrength::Medium}.
 *
 * {@see estimateEntropy()} estimates the entropy in bits from the characters of
 * the password:
 * - the pool size is the sum of the character classes present: 26 lower case
 *   letters, 26 upper case letters, 10 digits, 33 other ASCII characters, and
 *   100 non-ASCII characters;
 * - each distinct character adds log2(pool size) bits;
 * - each repeated character adds log2(number of distinct characters) bits.
 *
 * {@see estimateStrength()} maps the entropy to a {@see TPasswordStrength}
 * level through {@see STRENGTH_ENTROPY}. The estimate counts characters only;
 * it has no dictionary, so "Password1!" rates Medium. A
 * {@see TPasswordStrengthMeter} shows the estimate while the password is typed.
 *
 * With {@see setCheckCompromised CheckCompromised}, a password that passes the
 * strength check is also looked up in the Have I Been Pwned Pwned Passwords
 * range API. The request sends the first 5 hexadecimal characters of the SHA-1
 * hash of the password (k-anonymity) and asks for padded responses; the password
 * and its full hash never leave the server. A password found at least
 * {@see setCompromisedThreshold CompromisedThreshold} times fails. When the
 * request fails, the password passes this check and a warning is logged, so an
 * outage of the service does not block the form. The check runs server side only.
 *
 * When {@see TBaseValidator::setEnableClientScript EnableClientScript} is true
 * (the default), the strength check also runs in the browser with the same
 * estimate. The validation succeeds if the associated input control contains
 * empty input. Use a {@see \Prado\Web\UI\WebControls\TRequiredFieldValidator}
 * to ensure the input is not empty.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPasswordStrengthValidator extends TBaseValidator
{
	/**
	 * The minimum estimated entropy in bits of each {@see TPasswordStrength} level,
	 * from weakest to strongest.
	 */
	public const STRENGTH_ENTROPY = [
		TPasswordStrength::VeryWeak => 0,
		TPasswordStrength::Weak => 40,
		TPasswordStrength::Medium => 60,
		TPasswordStrength::Strong => 80,
		TPasswordStrength::VeryStrong => 100,
	];

	/**
	 * The pool size of each character class: lower case letters, upper case
	 * letters, digits, other ASCII characters, and non-ASCII characters.
	 */
	public const CLASS_SIZES = [
		'lower' => 26,
		'upper' => 26,
		'digit' => 10,
		'symbol' => 33,
		'other' => 100,
	];

	/**
	 * The default Pwned Passwords range API URL; the hash prefix is appended.
	 */
	public const PWNED_PASSWORDS_URL = 'https://api.pwnedpasswords.com/range/';

	/**
	 * @var ?THttpClient the HTTP client for the compromised password check.
	 */
	private ?THttpClient $_httpClient = null;

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TPasswordStrengthValidator';
	}

	/**
	 * @return string the minimum {@see TPasswordStrength} level. Defaults to Medium.
	 */
	public function getMinStrength()
	{
		return $this->getViewState('MinStrength', TPasswordStrength::Medium);
	}

	/**
	 * Sets the minimum strength level that a password needs to pass.
	 * @param string $value a {@see TPasswordStrength} level.
	 */
	public function setMinStrength($value)
	{
		$this->setViewState('MinStrength', TPropertyValue::ensureEnum($value, TPasswordStrength::class), TPasswordStrength::Medium);
	}

	/**
	 * @return bool whether the password is looked up in Pwned Passwords. Defaults to false.
	 */
	public function getCheckCompromised()
	{
		return $this->getViewState('CheckCompromised', false);
	}

	/**
	 * Sets whether a password is looked up in the Pwned Passwords range API.
	 * @param bool $value whether the password is looked up in Pwned Passwords.
	 */
	public function setCheckCompromised($value)
	{
		$this->setViewState('CheckCompromised', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return int the number of breach occurrences at which a password fails. Defaults to 1.
	 */
	public function getCompromisedThreshold()
	{
		return $this->getViewState('CompromisedThreshold', 1);
	}

	/**
	 * Sets the number of breach occurrences at which a password fails.
	 * @param int $value the number of occurrences, at least 1.
	 */
	public function setCompromisedThreshold($value)
	{
		$this->setViewState('CompromisedThreshold', max(1, TPropertyValue::ensureInteger($value)), 1);
	}

	/**
	 * @return string the range API URL that the hash prefix is appended to.
	 *   Defaults to {@see PWNED_PASSWORDS_URL}.
	 */
	public function getCompromisedApiUrl()
	{
		return $this->getViewState('CompromisedApiUrl', self::PWNED_PASSWORDS_URL);
	}

	/**
	 * Sets the range API URL, such as a self-hosted mirror of Pwned Passwords.
	 * The 5 character hash prefix is appended to it.
	 * @param string $value the range API URL.
	 */
	public function setCompromisedApiUrl($value)
	{
		$this->setViewState('CompromisedApiUrl', TPropertyValue::ensureString($value), self::PWNED_PASSWORDS_URL);
	}

	/**
	 * @return int the timeout in seconds of the range API request. Defaults to 5.
	 */
	public function getCompromisedTimeout()
	{
		return $this->getViewState('CompromisedTimeout', 5);
	}

	/**
	 * Sets the timeout in seconds of the range API request.
	 * @param int $value the timeout in seconds.
	 */
	public function setCompromisedTimeout($value)
	{
		$this->setViewState('CompromisedTimeout', TPropertyValue::ensureInteger($value), 5);
	}

	/**
	 * Gets the HTTP client for the compromised password check. It is created
	 * with {@see THttpClient::create()} and {@see getCompromisedTimeout CompromisedTimeout}
	 * on first use.
	 * @throws THttpClientException when no HTTP transport is available.
	 * @return THttpClient the HTTP client.
	 */
	public function getHttpClient(): THttpClient
	{
		if ($this->_httpClient === null) {
			$this->_httpClient = THttpClient::create();
			$this->_httpClient->setTimeout($this->getCompromisedTimeout());
		}
		return $this->_httpClient;
	}

	/**
	 * Sets the HTTP client for the compromised password check. The client is
	 * not saved in the page state.
	 * @param ?THttpClient $value the HTTP client; null creates the default client on use.
	 */
	public function setHttpClient(?THttpClient $value): void
	{
		$this->_httpClient = $value;
	}

	/**
	 * Estimates the entropy of a password in bits from its characters.
	 * @param string $password the password.
	 * @return float the estimated entropy in bits.
	 */
	public static function estimateEntropy(string $password): float
	{
		$chars = preg_split('//u', $password, -1, PREG_SPLIT_NO_EMPTY);
		if ($chars === false) {
			$chars = str_split($password);
		}
		if ($chars === [] || $chars === ['']) {
			return 0.0;
		}
		$classes = [];
		foreach ($chars as $char) {
			$code = ord($char);
			$class = match (true) {
				strlen($char) > 1 || $code > 127 => 'other',
				$code >= 97 && $code <= 122 => 'lower',
				$code >= 65 && $code <= 90 => 'upper',
				$code >= 48 && $code <= 57 => 'digit',
				default => 'symbol',
			};
			$classes[$class] = static::CLASS_SIZES[$class];
		}
		$pool = array_sum($classes);
		$distinct = count(array_unique($chars));
		return $distinct * log($pool, 2) + (count($chars) - $distinct) * log($distinct, 2);
	}

	/**
	 * Estimates the strength level of a password.
	 * @param string $password the password.
	 * @return string the {@see TPasswordStrength} level.
	 */
	public static function estimateStrength(string $password): string
	{
		$entropy = static::estimateEntropy($password);
		$strength = TPasswordStrength::VeryWeak;
		foreach (static::STRENGTH_ENTROPY as $level => $bits) {
			if ($entropy >= $bits) {
				$strength = $level;
			}
		}
		return $strength;
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input is empty, or the password is strong
	 * enough and, with CheckCompromised, not found in Pwned Passwords.
	 * @return bool whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		$value = $this->getValidationValue($this->getValidationTarget());
		if ($value === '' || $value === null) {
			return true;
		}
		if (!is_string($value) || static::estimateEntropy($value) < static::STRENGTH_ENTROPY[$this->getMinStrength()]) {
			return false;
		}
		return !$this->getCheckCompromised() || !$this->isCompromised($value);
	}

	/**
	 * Looks a password up in the Pwned Passwords range API. A failed request
	 * logs a warning and reports the password as not compromised.
	 * @param string $password the password.
	 * @return bool whether the password is found at least CompromisedThreshold times.
	 */
	public function isCompromised(string $password): bool
	{
		$hash = strtoupper(sha1($password));
		$suffix = substr($hash, 5);
		try {
			$response = $this->getHttpClient()->download('GET', $this->getCompromisedApiUrl() . substr($hash, 0, 5), [
				'Add-Padding' => 'true',
				'User-Agent' => 'PRADO/' . Prado::getVersion(),
			]);
		} catch (THttpClientException $e) {
			Prado::warning('The compromised password check was skipped: ' . $e->getMessage(), static::class);
			return false;
		}
		if (!$response->isSuccess()) {
			Prado::warning('The compromised password check was skipped: HTTP status ' . $response->getStatusCode(), static::class);
			return false;
		}
		foreach (explode("\n", $response->getBody()) as $line) {
			$parts = explode(':', trim($line));
			if (count($parts) === 2 && strcasecmp($parts[0], $suffix) === 0) {
				return (int) $parts[1] >= $this->getCompromisedThreshold();
			}
		}
		return false;
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['MinEntropy'] = static::STRENGTH_ENTROPY[$this->getMinStrength()];
		return $options;
	}
}
