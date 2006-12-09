<?php
/**
 * TSqlMapSelect class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.SqlMap.Statements
 */

/**
 * TSqlMapSelect class file.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.SqlMap.Statements
 * @since 3.1
 */
class TSqlMapSelect extends TSqlMapStatement
{
	private $_generate;

	public function getGenerate(){ return $this->_generate; }
	public function setGenerate($value){ $this->_generate = $value; }
}

?>