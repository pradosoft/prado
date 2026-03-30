<?php

/**
 * TDataSize class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\I18N\core\CultureInfo;
use Prado\I18N\core\CultureInfoUnits;

/**
 * TDataSize class
 *
 * TDataSize produces the size of a file in its natural size for human
 * readability rather than bytes.
 * ```php
 *		<com:TDataSize Size="475837458" UseMarketingSize="true" Abbreviate="true"/>
 * ```
 * will output "476 MB".
 *
 * The TDataSize output depends on {@see getAbbreviate Abbreviate} and
 * {@see getUseMarketingSize UseMarketingSize}.
 *
 * {@see getUseMarketingSize UseMarketingSize} will change the size of a
 * kilobyte to be 1000 rather the technical 1024.  This changes the output
 * between bytes, kilobytes, megabytes, gigabytes, terabytes, petabytes,
 * exabytes, zettabytes, yottabytes, ronnabyte, and quettabyte for
 * UseMarketingSize="True" (base 1000; decimal) and the technical bytes,
 * kibibytes, mebibytes, gibibytes, tebibytes, pebibytes, exbibytes,
 * zebibytes, yobibytes, robibytes, and quebibyte for UseMarketingSize="False"
 * (base 1024; binary).
 *
 * The singular and plural of these these outputted words are localized.
 *
 * For {@see getAbbreviate Abbreviate} that is true, with UseMarketingSize="True"
 * then B, KB, MB, GB, TB, PB, EB, ZB, YB, RB, QB is outputted. Otherwise with
 * UseMarketingSize="False" then B, KiB, MiB, GiB, TiB, PiB, EiB, ZiB, YiB, RiB,
 * QiB is outputted.  These outputted abbreviations pass through localization.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TDataSize extends TLabel
{
	/**
	 * renders the size in the closes base, bytes, kilobytes, megabytes,
	 * gigabytes, terabytes, petabytes, exabytes, zettabytes, yottabytes, ronnabyte,
	 * and quettabyte  for marketing terms, and bytes, kibibytes, mebibytes gibibytes,
	 * tebibytes, pebibytes, exbibytes, zebibytes, yobibytes, robibyte, and quebibyte
	 * for technical terms.
	 * @param object $writer  where the method writes output.
	 */
	public function renderContents($writer)
	{
		$size = $this->getSize();
		$abbr = $this->getAbbreviate();
		$marketingSize = $this->getUseMarketingSize();

		$base = $marketingSize ? 1000 : 1024;
		$orderOfMagnitude = min(max(floor(log($size, $base)), 0), 10);
		$size /= pow($base, $orderOfMagnitude);

		$sf = ($size >= 1000) ? 3 : 2;
		$size = round($size, (int) ceil($sf - ($size == 0 ? 0 : log10($size))));
		$culture = $this->getLocalizedInfo();

		$contents = null;
		if ($abbr) {
			if ($culture) {
				$sizeString = $culture->formatNumber($size);
			} else {
				$sizeString = number_format($size, 2, '.', ',');
			}
			if ($marketingSize) {
				// For the decimal system (1000), we just use the traditional approach without formatUnit since no constants defined
				$decimal = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB', 'RB', 'QB'];
				$contents = $sizeString . ' ' . Prado::localize($decimal[$orderOfMagnitude]);
			} else {
				// For the binary system (1024), we just use the traditional approach without formatUnit since no constants defined
				$binary = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB', 'RiB', 'QiB'];
				$contents = $sizeString . ' ' . Prado::localize($binary[$orderOfMagnitude]);
			}
		} else {
			if ($culture && $marketingSize) {
				$unitType = $this->unitFromMagnitude($orderOfMagnitude);
				if ($unitType) {
					$contents = $culture->formatUnit($size, $unitType);
				}
			}
			if (!$contents) {
				if ($culture) {
					$sizeString = $culture->formatNumber($size);
				} else {
					$sizeString = number_format($size, 2, '.', ',');
				}

				if ($marketingSize) {
					$names = ['byte', 'kilobyte', 'megabyte', 'gigabyte', 'terabyte', 'petabyte', 'exabyte', 'zettabyte', 'yottabyte', 'ronnabyte', 'quettabyte'];
				} else {
					// For binary system, we'll use the traditional approach without formatUnit since no constants defined
					$names = ['byte', 'kibibyte', 'mebibyte', 'gibibyte', 'tebibyte', 'pebibyte', 'exbibyte', 'zebibyte', 'yobibyte', 'robibyte', 'quebibyte'];
				}

				$appendix = '';
				if ($size != 1) {
					$appendix = 's';
				}
				$contents = $sizeString . ' ' . Prado::localize($names[$orderOfMagnitude] . $appendix);
			}
		}
		$writer->write($contents);
	}

	/**
	 * @return int data size in bytes.
	 */
	public function getSize()
	{
		return $this->getViewState('Size', 0);
	}

	/**
	 * @param int $size data size in bytes
	 */
	public function setSize($size)
	{
		$size = TPropertyValue::ensureFloat($size);
		if ($size < 0) {
			throw new TInvalidDataValueException('datasize_no_negative_size', $size);
		}
		$this->setViewState('Size', $size, 0);
	}

	/**
	 * @return bool using marketing sizes (base 1000) or not (technical sizes, base 1024)
	 */
	public function getUseMarketingSize()
	{
		return $this->getViewState('UseMarketingSize', true);
	}

	/**
	 * @param bool $marketing using marketing sizes (base 1000) or not (technical sizes, base 1024)
	 */
	public function setUseMarketingSize($marketing)
	{
		$this->setViewState('UseMarketingSize', TPropertyValue::ensureBoolean($marketing), true);
	}

	/**
	 * @return bool using abbreviations or not
	 */
	public function getAbbreviate()
	{
		return $this->getViewState('Abbreviate', true);
	}

	/**
	 * @param bool $abbr using abbreviations or not
	 */
	public function setAbbreviate($abbr)
	{
		$this->setViewState('Abbreviate', TPropertyValue::ensureBoolean($abbr), true);
	}

	/**
	 * @return string the current culture, falls back to application if culture is not set.
	 * @since 4.3.3
	 */
	protected function getCurrentCulture()
	{
		$app = $this->getApplication()->getGlobalization(false);
		return $this->getCulture() == '' ?
				($app ? $app->getCulture() : 'en') : $this->getCulture();
	}

	/**
	 * @return \Prado\I18N\core\CultureInfo date time format information for the current culture.
	 * @since 4.3.3
	 */
	protected function getLocalizedInfo()
	{
		//expensive operations
		$culture = $this->getCurrentCulture();
		$info = new CultureInfo($culture);
		return $info;
	}

	/**
	 * Gets the current culture.
	 * @return string current culture, e.g. en_AU.
	 * @since 4.3.3
	 */
	public function getCulture()
	{
		return $this->getViewState('Culture', '');
	}

	/**
	 * Sets the culture/language for the date picker.
	 * @param string $value a culture string, e.g. en_AU.
	 * @since 4.3.3
	 */
	public function setCulture($value)
	{
		$this->setViewState('Culture', $value, '');
	}

	/**
	 * For the Decimal (marketing) versions of TDataSize,
	 * this takes the number magnitude and returns the ICU unit.
	 * @param int $magnitude the magnitude of the unit (per 1000, decimal).
	 * @return null|string
	 * @since 4.3.3
	 */
	protected function unitFromMagnitude($magnitude)
	{
		if (!$this->getUseMarketingSize()) {
			return null;
		}
		switch ($magnitude) {
			case 0: return CultureInfoUnits::TYPE_DIGITAL_BYTE;
				break;
			case 1: return CultureInfoUnits::TYPE_DIGITAL_KILOBYTE;
				break;
			case 2: return CultureInfoUnits::TYPE_DIGITAL_MEGABYTE;
				break;
			case 3: return CultureInfoUnits::TYPE_DIGITAL_GIGABYTE;
				break;
			case 4: return CultureInfoUnits::TYPE_DIGITAL_TERABYTE;
				break;
			case 5: return CultureInfoUnits::TYPE_DIGITAL_PETABYTE;
				break;
			case 6: return CultureInfoUnits::TYPE_DIGITAL_EXABYTE;
				break;
			case 7: return CultureInfoUnits::TYPE_DIGITAL_ZETTABYTE;
				break;
			case 8: return CultureInfoUnits::TYPE_DIGITAL_YOTTABYTE;
				break;
			case 9: return CultureInfoUnits::TYPE_DIGITAL_RONNABYTE;
				break;
			case 10: return CultureInfoUnits::TYPE_DIGITAL_QUETTABYTE;
				break;
		}
		return null;
	}


}
