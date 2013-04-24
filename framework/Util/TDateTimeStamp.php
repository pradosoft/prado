<?php
/**
 * TDateTimeStamp class file.

 * @author Fabio Bas ctrlaltca[AT]gmail[DOT]com
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TDateTimeStamp.php 3246 2013-01-07 21:07:38Z ctrlaltca $
 * @package System.Util
 */

/**
 * TDateTimeStamp Class
 *
 * TDateTimeStamp Class is a wrapper to DateTime: http://php.net/manual/book.datetime.php
 * Prado implemented this class before php started shipping the DateTime extension.
 * This class is deprecated and left here only for compatibility for legacy code.
 * Please note that this class doesn't support automatic conversion from gregorian to
 * julian dates anymore.
 *
 * @author Fabio Bas ctrlaltca[AT]gmail[DOT]com
 * @version $Id: TDateTimeStamp.php 3246 2013-01-07 21:07:38Z ctrlaltca $
 * @package System.Util
 * @since 3.0.4
 * @deprecated since 3.2.1
 */
class TDateTimeStamp
{
	protected static $_month_normal = array("",31,28,31,30,31,30,31,31,30,31,30,31);
	protected static $_month_leaf = array("",31,29,31,30,31,30,31,31,30,31,30,31);

	/**
	 * Returns the day of the week (0=Sunday, 1=Monday, .. 6=Saturday)
	 * @param int year
	 * @param int month
	 * @param int day
	 */
	public function getDayofWeek($year, $month, $day)
	{
		$dt = new DateTime();
		$dt->setDate($year, $month, $day);
		return (int) $dt->format('w');
	}

	/**
	 * Checks for leap year, returns true if it is. No 2-digit year check. Also
	 * handles julian calendar correctly.
	 * @param float year to check
	 * @return boolean true if is leap year
	 */
	public function isLeapYear($year)
	{
		$year = $this->digitCheck($year);
		$dt = new DateTime();
		$dt->setDate($year, 1, 1);
		return (bool) $dt->format('L');
	}

	/**
	 * Fix 2-digit years. Works for any century.
	 * Assumes that if 2-digit is more than 30 years in future, then previous century.
	 * @return integer change two digit year into multiple digits
	 */
	protected function digitCheck($y)
	{
		if ($y < 100){
			$yr = (integer) date("Y");
			$century = (integer) ($yr /100);

			if ($yr%100 > 50) {
				$c1 = $century + 1;
				$c0 = $century;
			} else {
				$c1 = $century;
				$c0 = $century - 1;
			}
			$c1 *= 100;
			// if 2-digit year is less than 30 years in future, set it to this century
			// otherwise if more than 30 years in future, then we set 2-digit year to the prev century.
			if (($y + $c1) < $yr+30) $y = $y + $c1;
			else $y = $y + $c0*100;
		}
		return $y;
	}

	public function get4DigitYear($y)
	{
		return $this->digitCheck($y);
	}

	/**
	 * @return integer get local time zone offset from GMT
	 */
	public function getGMTDiff($ts=false)
	{
		$dt = new DateTime();
		if($ts)
			$dt->setTimeStamp($ts);
		else
		 	$dt->setDate(1970, 1, 2);

		return (int) $dt->format('Z');
	}

	/**
	 * @return array an array with date info.
	 */
	function parseDate($txt=false)
	{
		if ($txt === false) return getdate();

		$dt = new DateTime($txt);

		return array(
			'seconds' => (int) $dt->format('s'),
			'minutes' => (int) $dt->format('i'),
			'hours' => (int) $dt->format('G'),
			'mday' => (int) $dt->format('j'),
			'wday' => (int) $dt->format('w'),
			'mon' => (int) $dt->format('n'),
			'year' => (int) $dt->format('Y'),
			'yday' => (int) $dt->format('z'),
			'weekday' => $dt->format('l'),
			'month' => $dt->format('F'),
			0 => (int) $dt->format('U'),
			);
	}

	/**
	 * @return array an array with date info.
	 */
	function getDate($d=false,$fast=false)
	{
		if ($d === false) return getdate();

		$dt = new DateTime();
		$dt->setTimestamp($d);

		return array(
			'seconds' => (int) $dt->format('s'),
			'minutes' => (int) $dt->format('i'),
			'hours' => (int) $dt->format('G'),
			'mday' => (int) $dt->format('j'),
			'wday' => (int) $dt->format('w'),
			'mon' => (int) $dt->format('n'),
			'year' => (int) $dt->format('Y'),
			'yday' => (int) $dt->format('z'),
			'weekday' => $dt->format('l'),
			'month' => $dt->format('F'),
			0 => (int) $dt->format('U'),
			);
	}

	/**
	 * @return boolean true if valid date, semantic check only.
	 */
	public function isValidDate($y,$m,$d)
	{
		if ($this->isLeapYear($y))
			$marr =& self::$_month_leaf;
		else
			$marr =& self::$_month_normal;

		if ($m > 12 || $m < 1) return false;

		if ($d > 31 || $d < 1) return false;

		if ($marr[$m] < $d) return false;

		if ($y < 1000 && $y > 3000) return false;

		return true;
	}

	/**
	 * @return string formatted date based on timestamp $d
	 */
	function formatDate($fmt,$ts=false,$is_gmt=false)
	{
		$dt = new DateTime();
		if($is_gmt)
			$dt->setTimeZone(new DateTimeZone('UTC'));
		$dt->setTimestamp($ts);

		return $dt->format($fmt);
	}

	/**
	 * @return integer|float a timestamp given a local time
     */
	function getTimeStamp($hr,$min,$sec,$mon=false,$day=false,$year=false,$is_gmt=false)
	{
		$dt = new DateTime();
		if($is_gmt)
			$dt->setTimeZone(new DateTimeZone('UTC'));
		$dt->setDate($year!==false ? $year : date('Y'), 
			$mon!==false ? $mon : date('m'), 
			$day!==false ? $day : date('d'));
		$dt->setTime($hr, $min, $sec);
		return (int) $dt->format('U');
	}
}

