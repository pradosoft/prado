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
 * will output "476 MB"
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
	 * @param $writer object where the method writes output.
	 */
	public function renderContents($writer) {
		
		$s = $this->_size;
		$d = 1024;
		
		if($this->_usemarketingsize)
			$d = 1000;
		
		$number = [1, $d, pow($d, 2), pow($d, 3), pow($d, 4), pow($d, 5), pow($d, 6), pow($d, 7), pow($d, 8)];
		
		$index = 0;
		foreach($number as $k => $size)
			if(abs($s) < $size)
				break;
			else
				$index = $k;
		$size = $number[$index];
		
		$s /= $size;
		
		$sf = 2;
		if($s > 1000)
			$sf = 3;
			
		$s = round($s, (int) ceil($sf - log10(abs($s))));
		$abbr = $this->getAbbreviate();
		$marketingSize = $this->getUseMarketingSize();
		if($abbr && $marketingSize) {
			$decimal = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
			$t = $s . ' ' . Prado::localize($decimal[$index]);
		} elseif(!$abbr && $marketingSize) {
			$decimalname = ['byte', 'kilobyte', 'megabyte', 'gigabyte', 'terabyte', 'petabyte', 'exabyte', 'zettabyte', 'yottabyte'];
			$appendix = '';
			if($s != 1)
				$appendix = 's';
			$t = $s . ' ' . Prado::localize($decimalname[$index] . $appendix);
		} elseif($abbr && !$marketingSize) {
			$binary = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
			$t = $s . ' ' . Prado::localize($binary[$index]);
		} elseif(!$abbr && !$marketingSize) {
			$binaryname = ['byte', 'kibibyte', 'mebibyte', 'gibibyte', 'tebibyte', 'pebibyte', 'exbibyte', 'zebibyte', 'yobibyte'];
			$appendix = '';
			if($s != 1)
				$appendix = 's';
			$t = $s . ' ' . Prado::localize($binaryname[$index] . $appendix);
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
	 * @param $size int data size in bytes
	 */
	public function setSize($size)
	{
		$this->_size = TPropertyValue::ensureInteger($size);
	}
	
	/**
	 * @return bool using marketing sizes (base 1000) or not (base 1024)
	 */
	public function getUseMarketingSize()
	{
		return $this->_usemarketingsize;
	}
	
	/**
	 * @param $marketing bool using marketing sizes (base 1000) or not (base 1024)
	 */
	public function setUseMarketingSize($marketing)
	{
		$this->_usemarketingsize=TPropertyValue::ensureBoolean($marketing);
	}
	
	/**
	 * @return bool using abbreviations or not
	 */
	public function getAbbreviate()
	{
		return $this->_abbreviate;
	}
	
	/**
	 * @param $abbr bool using abbreviations or not
	 */
	public function setAbbreviate($abbr)
	{
		$this->_abbreviate=TPropertyValue::ensureBoolean($abbr);
	}
}
