<?php

/**
 * TIbanValidator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TIbanValidator class
 *
 * TIbanValidator validates whether the value of an associated input component
 * is an International Bank Account Number (ISO 13616), such as
 * "DE89 3704 0044 0532 0130 00". Spaces are ignored and letters are compared
 * upper case, so the print format and a pasted lower case IBAN pass. The value
 * fails when:
 * - its first two characters are not a country of {@see COUNTRY_FORMATS}, or
 *   not in {@see setCountries Countries};
 * - characters 3 and 4 are not check digits;
 * - its length or its Basic Bank Account Number (BBAN) does not match the
 *   format of the country, such as 18 digits for Germany;
 * - the ISO 7064 mod 97-10 checksum is not 1.
 *
 * {@see COUNTRY_FORMATS} follows the SWIFT IBAN Registry, Release 99, December
 * 2024. The checks confirm that an IBAN is well formed; they do not confirm that
 * the account exists. The same checks run in the browser. The validation
 * succeeds if the associated input control contains empty input. Use a
 * {@see \Prado\Web\UI\WebControls\TRequiredFieldValidator} to ensure the input
 * is not empty.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TIbanValidator extends TBaseValidator
{
	/**
	 * The BBAN format of each IBAN country: comma separated runs of a length and
	 * a type, "n" for digits, "a" for letters, and "c" for letters or digits. The
	 * IBAN length is 4 plus the run lengths. SWIFT IBAN Registry, Release 99.
	 */
	public const COUNTRY_FORMATS = [
		'AD' => '8n,12c', // Andorra
		'AE' => '3n,16n', // United Arab Emirates
		'AL' => '8n,16c', // Albania
		'AT' => '16n', // Austria
		'AZ' => '4a,20c', // Azerbaijan
		'BA' => '16n', // Bosnia and Herzegovina
		'BE' => '12n', // Belgium
		'BG' => '4a,6n,8c', // Bulgaria
		'BH' => '4a,14c', // Bahrain
		'BI' => '5n,5n,11n,2n', // Burundi
		'BR' => '23n,1a,1c', // Brazil
		'BY' => '4c,4n,16c', // Belarus
		'CH' => '5n,12c', // Switzerland
		'CR' => '18n', // Costa Rica
		'CY' => '8n,16c', // Cyprus
		'CZ' => '20n', // Czech Republic
		'DE' => '18n', // Germany
		'DJ' => '5n,5n,11n,2n', // Djibouti
		'DK' => '14n', // Denmark
		'DO' => '4c,20n', // Dominican Republic
		'EE' => '16n', // Estonia
		'EG' => '25n', // Egypt
		'ES' => '20n', // Spain
		'FI' => '14n', // Finland
		'FK' => '2a,12n', // Falkland Islands
		'FO' => '14n', // Faroe Islands
		'FR' => '10n,11c,2n', // France and its overseas territories
		'GB' => '4a,14n', // United Kingdom, Guernsey, Isle of Man, Jersey
		'GE' => '2a,16n', // Georgia
		'GI' => '4a,15c', // Gibraltar
		'GL' => '14n', // Greenland
		'GR' => '7n,16c', // Greece
		'GT' => '4c,20c', // Guatemala
		'HN' => '4a,20n', // Honduras
		'HR' => '17n', // Croatia
		'HU' => '24n', // Hungary
		'IE' => '4a,6n,8n', // Ireland
		'IL' => '19n', // Israel
		'IQ' => '4a,15n', // Iraq
		'IS' => '22n', // Iceland
		'IT' => '1a,10n,12c', // Italy
		'JO' => '4a,4n,18c', // Jordan
		'KW' => '4a,22c', // Kuwait
		'KZ' => '3n,13c', // Kazakhstan
		'LB' => '4n,20c', // Lebanon
		'LC' => '4a,24c', // Saint Lucia
		'LI' => '5n,12c', // Liechtenstein
		'LT' => '16n', // Lithuania
		'LU' => '3n,13c', // Luxembourg
		'LV' => '4a,13c', // Latvia
		'LY' => '21n', // Libya
		'MC' => '10n,11c,2n', // Monaco
		'MD' => '2c,18c', // Moldova
		'ME' => '18n', // Montenegro
		'MK' => '3n,10c,2n', // North Macedonia
		'MN' => '4n,12n', // Mongolia
		'MR' => '23n', // Mauritania
		'MT' => '4a,5n,18c', // Malta
		'MU' => '4a,19n,3a', // Mauritius
		'NI' => '4a,20n', // Nicaragua
		'NL' => '4a,10n', // Netherlands
		'NO' => '11n', // Norway
		'OM' => '3n,16c', // Oman
		'PK' => '4a,16c', // Pakistan
		'PL' => '24n', // Poland
		'PS' => '4a,21c', // Palestinian territories
		'PT' => '21n', // Portugal
		'QA' => '4a,21c', // Qatar
		'RO' => '4a,16c', // Romania
		'RS' => '18n', // Serbia
		'RU' => '14n,15c', // Russia
		'SA' => '2n,18c', // Saudi Arabia
		'SC' => '4a,20n,3a', // Seychelles
		'SD' => '14n', // Sudan
		'SE' => '20n', // Sweden
		'SI' => '15n', // Slovenia
		'SK' => '20n', // Slovakia
		'SM' => '1a,10n,12c', // San Marino
		'SO' => '4n,3n,12n', // Somalia
		'ST' => '21n', // São Tomé and Príncipe
		'SV' => '4a,20n', // El Salvador
		'TL' => '19n', // East Timor
		'TN' => '20n', // Tunisia
		'TR' => '5n,1n,16c', // Turkey
		'UA' => '6n,19c', // Ukraine
		'VA' => '3n,15n', // Vatican City
		'VG' => '4a,16n', // Virgin Islands, British
		'XK' => '4n,10n,2n', // Kosovo
		'YE' => '4a,4n,18c', // Yemen
	];

	/**
	 * Gets the name of the javascript class responsible for performing validation for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TIbanValidator';
	}

	/**
	 * @return string comma separated allowed country codes. Defaults to '', every IBAN country.
	 */
	public function getCountries()
	{
		return $this->getViewState('Countries', '');
	}

	/**
	 * Sets the country codes, such as "DE,AT,CH", that the IBAN may have.
	 * An empty list allows every country of {@see COUNTRY_FORMATS}.
	 * @param string $value comma separated country codes.
	 */
	public function setCountries($value)
	{
		$this->setViewState('Countries', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Gets the BBAN formats of the allowed countries.
	 * @return array<string, string> the BBAN format of each allowed country code.
	 */
	public function getCountryFormats(): array
	{
		$codes = array_filter(array_map(fn ($c) => strtoupper(trim($c)), explode(',', $this->getCountries())), fn ($c) => $c !== '');
		if ($codes === []) {
			return static::COUNTRY_FORMATS;
		}
		return array_intersect_key(static::COUNTRY_FORMATS, array_flip($codes));
	}

	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input is empty or is a valid IBAN.
	 * @return bool whether the validation succeeds
	 */
	public function evaluateIsValid()
	{
		$value = $this->getValidationValue($this->getValidationTarget());
		if ($value === '' || $value === null) {
			return true;
		}
		return is_string($value) && $this->isValidIban($value);
	}

	/**
	 * Checks an IBAN against the country formats and the mod 97-10 checksum.
	 * @param string $value the IBAN; spaces are ignored and letters compared upper case.
	 * @return bool whether the IBAN is valid.
	 */
	public function isValidIban(string $value): bool
	{
		$iban = strtoupper(str_replace(' ', '', $value));
		$formats = $this->getCountryFormats();
		if (!preg_match('/^([A-Z]{2})[0-9]{2}([A-Z0-9]+)$/', $iban, $matches) || !isset($formats[$matches[1]])) {
			return false;
		}
		if (!preg_match(static::bbanPattern($formats[$matches[1]]), $matches[2])) {
			return false;
		}
		return static::mod97(substr($iban, 4) . substr($iban, 0, 4)) === 1;
	}

	/**
	 * Builds the regular expression of a BBAN format.
	 * @param string $format a BBAN format, such as "4a,6n,8c".
	 * @return string the anchored regular expression.
	 */
	public static function bbanPattern(string $format): string
	{
		$classes = ['n' => '[0-9]', 'a' => '[A-Z]', 'c' => '[A-Z0-9]'];
		$pattern = '';
		foreach (explode(',', $format) as $run) {
			$pattern .= $classes[substr($run, -1)] . '{' . (int) $run . '}';
		}
		return '/^' . $pattern . '$/';
	}

	/**
	 * Computes the ISO 7064 mod 97-10 remainder of a string of digits and
	 * upper case letters; each letter counts as two digits, A as 10 to Z as 35.
	 * @param string $value the digits and letters.
	 * @return int the remainder, 0 to 96.
	 */
	public static function mod97(string $value): int
	{
		$remainder = 0;
		foreach (str_split($value) as $char) {
			$digits = ctype_digit($char) ? $char : (string) (ord($char) - 55);
			foreach (str_split($digits) as $digit) {
				$remainder = ($remainder * 10 + (int) $digit) % 97;
			}
		}
		return $remainder;
	}

	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$options['CountryFormats'] = $this->getCountryFormats();
		return $options;
	}
}
