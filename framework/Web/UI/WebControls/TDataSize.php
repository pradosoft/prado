<?php
/**
 * TDataSize class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
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
 * @package Prado\Web\UI\WebControls
 * @since 4.2.0
 */
class TDataSize extends TLabel
{
	/**
	 * @var int size of the data
	 */
	private $_size = 0;
	
	/**
	 * @var bool whether to use marketing sizes (base 1000) or technical sizes (base 1024).
	 */
	private $_usemarketingsize = false;
	
	/**
	 * @var bool whether to use abbreviations.
	 */
	private $_abbreviate = true;
	
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
		$s = $this->_size;
		$abbr = $this->_abbreviate;
		$marketingSize = $this->_usemarketingsize;
		
		$d = 1024;
		
		if ($marketingSize) {
			$d = 1000;
		}
		
		$size = $d;
		for ($i = 1; $i <= 8; $i++, $size *= $d) {
			if ($s < $size) {
				break;
			}
		}
		$i--; //go to the previous index and size that worked.
		$size /= $d;
		
		$s /= $size;
		
		$sf = 2;
		if ($s >= 1000) {
			$sf = 3;
		}
			
		$s = round($s, (int) ceil($sf - log10($s)));
		
		if ($abbr && $marketingSize) {
			$decimal = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
			$t = $s . ' ' . Prado::localize($decimal[$i]);
		} elseif ($abbr && !$marketingSize) {
			$binary = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
			$t = $s . ' ' . Prado::localize($binary[$i]);
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
			$t = $s . ' ' . Prado::localize($names[$i] . $appendix);
		}
		$writer->write($t);
	}
	
	/**
	 * @return int data size in bytes.
	 */
	public function getSize()
	{
		return $this->_size;
	}
	
	/**
	 * @param int $size data size in bytes
	 */
	public function setSize($size)
	{
		$this->_size = TPropertyValue::ensureFloat($size);
		if ($this->_size < 0) {
			throw new TInvalidDataValueException('datasize_no_negative_size', $this->_size);
		}
	}
	
	/**
	 * @return bool using marketing sizes (base 1000) or not (base 1024)
	 */
	public function getUseMarketingSize()
	{
		return $this->_usemarketingsize;
	}
	
	/**
	 * @param bool $marketing using marketing sizes (base 1000) or not (base 1024)
	 */
	public function setUseMarketingSize($marketing)
	{
		$this->_usemarketingsize = TPropertyValue::ensureBoolean($marketing);
	}
	
	/**
	 * @return bool using abbreviations or not
	 */
	public function getAbbreviate()
	{
		return $this->_abbreviate;
	}
	
	/**
	 * @param bool $abbr using abbreviations or not
	 */
	public function setAbbreviate($abbr)
	{
		$this->_abbreviate = TPropertyValue::ensureBoolean($abbr);
	}
}
