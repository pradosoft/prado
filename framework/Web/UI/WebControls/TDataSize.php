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

/**
 * TDataSize class
 *
 * TDataSize produces the size of a file in its natural size for human
 * readability rather than bytes.
 * <code>
 *		<com:TDataSize Size="475837458" UseMarketingSize="true" Abbreviate="true"/>
 * </code>
 * will output "476 MB".
 *
 * The TDataSize output depends on {@link getAbbreviate Abbreviate} and
 * {@link getUseMarketingSize UseMarketingSize}.
 *
 * {@link getUseMarketingSize UseMarketingSize} will change the size of a
 * kilobyte to be 1000 rather the technical 1024.  This changes the output
 * between bytes, kilobytes, megabytes, gigabytes, terabytes, petabytes,
 * exabytes, zettabytes, and yottabytes for UseMarketingSize="True" (base 1000) and
 * the technical bytes, kibibytes, mebibytes, gibibytes, tebibytes, pebibytes,
 * exbibytes, zebibytes, and yobibytes for UseMarketingSize="False" (base 1024).
 * The singular and plural of these these outputted words are localized.
 *
 * For {@link getAbbreviate Abbreviate} that is true, with UseMarketingSize="True"
 * then B, KB, MB, GB, TB, PB, EB, ZB, YB is outputted. Otherwise with
 * UseMarketingSize="False" then B, KiB, MiB, GiB, TiB, PiB, EiB, ZiB, YiB is
 * outputted.  These outputted abbreviations are localized.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TDataSize extends TLabel
{
	/**
	 * renders the size in the closes base, bytes, kilobytes, megabytes,
	 * gigabytes, terabytes, petabytes, exabytes, zettabytes, and yottabytes
	 * for marketing terms, and bytes, kibibytes, mebibytes gibibytes,
	 * tebibytes, pebibytes, exbibytes, zebibytes, and yobibytes for technical
	 * terms.
	 * @param object $writer  where the method writes output.
	 */
	public function renderContents($writer)
	{
		$s = $this->getSize();
		$abbr = $this->getAbbreviate();
		$marketingSize = $this->getUseMarketingSize();

		$d = $marketingSize ? 1000 : 1024;
		$index = min(max(floor(log($s, $d)), 0), 8);
		$s /= pow($d, $index);

		$sf = ($s >= 1000) ? 3 : 2;
		$s = round($s, (int) ceil($sf - log10($s)));

		if ($abbr && $marketingSize) {
			$decimal = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
			$t = $s . ' ' . Prado::localize($decimal[$index]);
		} elseif ($abbr && !$marketingSize) {
			$binary = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
			$t = $s . ' ' . Prado::localize($binary[$index]);
		} else {
			if ($marketingSize) {
				$names = ['byte', 'kilobyte', 'megabyte', 'gigabyte', 'terabyte', 'petabyte', 'exabyte', 'zettabyte', 'yottabyte'];
			} else {
				$names = ['byte', 'kibibyte', 'mebibyte', 'gibibyte', 'tebibyte', 'pebibyte', 'exbibyte', 'zebibyte', 'yobibyte'];
			}
			$appendix = '';
			if ($s != 1) {
				$appendix = 's';
			}
			$t = $s . ' ' . Prado::localize($names[$index] . $appendix);
		}
		$writer->write($t);
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
}
